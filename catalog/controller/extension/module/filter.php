<?php
class ControllerExtensionModuleFilter extends Controller {
	public function index() {

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		$sort =  $this->request->get['sort'];
		$order = $this->request->get['order'];

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

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
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
			
			foreach ($optgroup as $order2 => $sort2) {
				foreach ($sort2 as $sort_order2) {
					if (in_array($sort_order2, $sort_data[$optgroup_name][$order2])) {
						$value = $sort_order2.'-'.$order2;
						$text = $this->language->get($value);
						$href = $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort='.$sort_order2.'&order='.$order2);
						
						// DONE Add filter conditions to sorting
						if (isset($this->request->get['filter'])) {
							$href .= '&filter='.$this->request->get['filter'];
						}
						
						// DONE Add page request to sorting
						if (isset($this->request->get['page'])) {
							$href .= '&page='.$this->request->get['page'];
						}
		
						$sorts[$optgroup_name]['values'][] = array(
							'text'  => $text,
							'value' => $value,
							'href'  => $href,
						);
						// echo('$_[\''.$value.'\'] = \''.$text.'\';<br/>');
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