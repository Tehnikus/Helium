<?php
class ControllerExtensionModuleFilter extends Controller {
	public function index() {

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($this->request->get['sort']) && isset($this->request->get['order'])) {
			$sort  = $this->request->get['sort'];
			$order = $this->request->get['order'];
		} else {
			$sort  = 'p.sort_order';
			$order =  'ASC';
		}

		$this->load->model('catalog/product');
		$allowed_sort_data = $this->model_catalog_product->getSorts();

		// Sanitize input data
		if ($this->searchArrayForKeyValue($allowed_sort_data, $order, $sort)) {
			$data['sort']  = $sort;
			$data['order'] = $order;
		} else {
			$data['sort']  = 'p.sort_order';
			$data['order'] = 'ASC';	
		}



		$category_id = end($parts);

		$this->load->model('catalog/category');

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->load->language('extension/module/filter');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['action'] = str_replace('&amp;', '&', $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url));

			if (isset($this->request->get['filter'])) {
				$data['filter_category'] = explode(',', $this->request->get['filter']);
			} else {
				$data['filter_category'] = array();
			}

			$this->load->model('catalog/product');

			$data['filter_groups'] = array();

			$filter_groups = $this->model_catalog_category->getCategoryFilters($category_id);

			// TODO добавить кеширование фильтров:
			// фильтры/магазин.язык.категория.фильтр
			if ($filter_groups) {
				foreach ($filter_groups as $filter_group) {
					$childen_data = array();

					foreach ($filter_group['filter'] as $filter) {
						$filter_data = array(
							'filter_category_id' => $category_id,
							'filter_filter'      => $filter['filter_id']
						);

						$childen_data[] = array(
							'filter_id' 		 => $filter['filter_id'],
							'name'      		 => $filter['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '')
						);
					}

					$data['filter_groups'][] = array(
						'filter_group_id'   	 => $filter_group['filter_group_id'],
						'name'              	 => $filter_group['name'],
						'filter_type' 	    	 => $filter_group['filter_type'],
						'filter'            	 => $childen_data
					);
					// TODO Добавить ссылки на страницы фильтров
					// Смотреть seo_url.php, функция public function rewrite($link)
					// $data['filter_links'][] = array(
					// 	'name' => 'link name',
					// 	'url'  => $this->url->link('product/category', 'path=13' . '&filter=4'),
					// );
				}
				$data['sorts'] = $this->renderSorts($allowed_sort_data);
				return $this->load->view('extension/module/filter', $data);
			}
		}
	}

	public function renderSorts($sort_data) {
		$sorts = [];
		foreach ($sort_data as $optgroup_name => $optgroup) {
			$sorts[$optgroup_name] = array(
				'name' => $this->language->get($optgroup_name),
				'values' => array(),
			);
			
			foreach ($optgroup as $order_direction => $sorting_values_array) {
				foreach ($sorting_values_array as $sort_value) {
					if (in_array($sort_value, $sort_data[$optgroup_name][$order_direction])) {
						$value = $sort_value.'-'.$order_direction;
						$text = $this->language->get($value);
						if ($sort_value === 'p.sort_order' && $order_direction === 'ASC') {
							// If sort is default - set canonical link of the category
							$href = $this->url->link('product/category', 'path=' . $this->request->get['path']);
						} else {
							// Else add sorting data to strings
							$href = $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort='.$sort_value.'&order='.$order_direction);
						}
						
						// DONE Add filter conditions to sorting
						if (isset($this->request->get['filter'])) {

							// Clear filter from anything except numbers and commas
							$filter = $this->request->get['filter'];
							$filter = preg_replace('/[^\\d,]+/', '', $filter);
							$filter = explode(',', $filter);
							foreach ($filter as $k => $string) {
								$strings[$k] = (int) $string;
							}
							asort($strings);
							$strings = array_unique(array_filter($strings));
							$filter = implode(',', $strings);
							// Join filter to sorting href
							$href .= '&filter='.$filter;
						}
						
						// DONE Add page request to sorting
						if (isset($this->request->get['page'])) {
							// Cast as (int) so no side data put into request
							$page = (int)$this->request->get['page'];
							$href .= '&page='.(int)$page;
						}
		
						$sorts[$optgroup_name]['values'][] = array(
							'text'  => $text,
							'value' => $value,
							'href'  => $href,
						);
					}
				}
			}
		}
		return $sorts;
	}
	function searchArrayForKeyValue($array, $key, $value) {
		foreach ($array as $optgroup) {
			foreach ($optgroup as $sortDirection => $fields) {
				if (in_array($value, $fields) && $sortDirection == $key) {
					return true;
				}
			}
		}
		return false;
	}
}