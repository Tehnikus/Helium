<?php


class ControllerCommonContentBottom extends Controller {
	// Set routes where content_bottom will be cached
	public $allowed_routes = ['product/category', 'product/product', 'common/home'];
	// Set disallowed modules to ommit caching 
	public $non_cached_modules = ['viewed', 'filter'];
	public function index() {
		$this->load->model('design/layout');

		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		} else {
			$route = 'common/home';
		}

		$layout_id = 0;

		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getCategoryLayoutId(end($path));
		}
		
		if ($route == 'product/manufacturer/info' && isset($this->request->get['manufacturer_id'])) {
			$this->load->model('catalog/manufacturer');
		
			$layout_id = $this->model_catalog_manufacturer->getManufacturerLayoutId($this->request->get['manufacturer_id']);
		}

		if ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getProductLayoutId($this->request->get['product_id']);
		}

		if ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getInformationLayoutId($this->request->get['information_id']);
		}

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = $this->config->get('config_layout_id');
		}

		$this->load->model('setting/module');

		$data['modules'] = array();

		$modules = $this->model_design_layout->getLayoutModules($layout_id, 'content_bottom');

		foreach ($modules as $module) {
			$part = explode('.', $module['code']);
			$module_name = $part[0];

			if (isset($part[0]) && $this->config->get('module_' . $part[0] . '_status')) {
				if (in_array($module_name, $this->non_cached_modules)) {
					// If module name is in array of non cached modules - render normally
					$module_data = $this->load->controller('extension/module/' . $part[0]);
					if ($module_data) {
						$data['modules'][] = $module_data;
					}
				} else {
					// Else cache module
					$cache_name = $this->renderCacheName([$module_name]); 	// Set cache name
					$module_data = $this->cache->get($cache_name); 			// Try to get cache
					// If no cache present
					if ($module_data === false) {
						$module_data = $this->load->controller('extension/module/' . $part[0]); // Get module data 
						if ($module_data) {
							$this->cache->set($cache_name, $module_data);  // Set module data to cache
						}
					}
					$data['modules'][] = $module_data;
				}
			}

			if (isset($part[1])) {
				if (in_array($module_name, $this->non_cached_modules)) {
					$setting_info = $this->model_setting_module->getModule($part[1]);
					if ($setting_info && $setting_info['status']) {
						$output = $this->load->controller('extension/module/' . $part[0], $setting_info);
						if ($output) {
							$data['modules'][] = $output;
						}
					}
				} else {
					$cache_name = $this->renderCacheName([$module_name, $part[1]]);
					$output = $this->cache->get($cache_name);
					if ($output === false) {
						$setting_info = $this->model_setting_module->getModule($part[1]);
						if ($setting_info && $setting_info['status']) {
							$output = $this->load->controller('extension/module/' . $part[0], $setting_info);
							if ($output) {
								$this->cache->set($cache_name, $output);
							}
						}
					}
					$data['modules'][] = $output;
				}
			}
		}

		return $this->load->view('common/content_bottom', $data);
	}

	
	/**
	 * Render module cache name
	 * @param array $cache_name contains module name and setting info if needed:
	 * $cache_name = ['featured_articles', '33']
	 * @return string with cache name which represents all settings data and where the module is displayed:
	 * Data separated by points: 
	 * store_id, lang_id, route, column or block, module_name, setting_id
	 * Example:
	 * 0.1.category.13.content_bottom.special.380
	 */
	public function renderCacheName(array $cache_name)
	{
		$cache_name_data = [
			'store_id'   => (int) $this->config->get('config_store_id'), 							 // store id
			'lang_id'    => (int) $this->config->get('config_language_id'), 						 // language id
			'route_name' => explode('/', $this->request->get['route'])[1], 							 // route name: product, category, manufacturer, home, etc
			'route_id'   => isset($this->request->get['path']) ? $this->request->get['path'] : null, // id of route if present: category.1, product.15, etc
			'cache_id'   => 'content_bottom', 														 // cache name: content_bottom, content_top, etc
		];
		// remove null values if route ID is not set
		$cache_name = implode('.', array_merge(array_map('trim', $cache_name_data), $cache_name));
		
		return (string) $cache_name;
	}
}
