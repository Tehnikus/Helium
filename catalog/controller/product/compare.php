<?php
class ControllerProductCompare extends Controller {
	public function index() {
		
		$this->load->language('product/compare');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}

		if (isset($this->request->get['remove'])) {
			$key = array_search($this->request->get['remove'], $this->session->data['compare']);

			if ($key !== false) {
				unset($this->session->data['compare'][$key]);

				$this->session->data['success'] = $this->language->get('text_removed');
			}

			$this->response->redirect($this->url->link('product/compare'));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/compare')
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$products = $this->renderCompareProducts();
		$data = array_merge($data, $products);
		

		$data['continue'] = $this->url->link('common/home');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/compare', $data));
	}

	public function add() {
		$this->load->language('product/compare');
		$this->load->model('catalog/product');

		$json = array();

		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			return;
		}

		$product_info = $this->model_catalog_product->getProduct((int)$product_id);

		if ($product_info) {
			if (!in_array($this->request->post['product_id'], $this->session->data['compare'])) {
				if (count($this->session->data['compare']) >= 3) {
					array_shift($this->session->data['compare']);
				}
				$this->session->data['compare'][] = $this->request->post['product_id'];
			}

			// $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('product/compare'));
			$dialog = $this->renderCompareProducts();
			$json['dialog'] = $this->load->view('product/compare_table', $dialog);
			$json['html']['replace']['#compare-total'] = '<i class="icon-compare"></i>'.sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove() {
		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}
		if (isset($this->request->post['product_id'])) {
			$key = array_search($this->request->post['product_id'], $this->session->data['compare']);
			if ($key !== false) {
				unset($this->session->data['compare'][$key]);
				$json = [];
				$json['toasts']['success'][] = $this->language->get('text_removed') . ' ' . $this->request->post['product_id'];
				$this->response->setOutput(json_encode($json));
			}
		}
	}

	public function renderCompareProducts() {
		$this->load->language('product/compare');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$data['review_status'] = $this->config->get('config_review_status');
		$data['products'] = array();
		$data['attribute_groups'] = array();

		$table = [];
		$attrs = [];
		$attribute_table = [];
		// Set first column with headers of compared data
		// Product data is compared to this keys so if key doesn't exist in first column, it will not be added

		
		// Service data to add product to cart, remove from comparison, fire some scripts etc
		// $table[0]['product_id']        = '';
		// $table[0]['href']              = '';
		// $table[0]['special_date_end']  = '';
		// $table[0]['discount_date_end'] = '';
		// $table[0]['discount_quantity'] = '';
		// $table[0]['price_value'] 		= '';

		

		if (isset($this->session->data['compare']) && is_array($this->session->data['compare']) && count($this->session->data['compare']) > 0) {
			// Get products data
			foreach ($this->session->data['compare'] as $key => $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id);
			}
			$products = $this->model_catalog_product->prepareProductList($product_list, null);
			$table['name']['line_name'][0]         	= $this->language->get('text_name');
			$table['thumb']['line_name'][0]        	= $this->language->get('text_image');
			$table['price']['line_name'][0]        	= $this->language->get('text_price');
			$table['model']['line_name'][0]        	= $this->language->get('text_model');
			$table['manufacturer']['line_name'][0] 	= $this->language->get('text_manufacturer');
			$table['stock_status']['line_name'][0] 	= $this->language->get('text_availability');
			$table['rating']['line_name'][0]       	= $this->language->get('text_rating');
			$table['description']['line_name'][0]  	= $this->language->get('text_summary');
			$table['attributes']['line_name'][0]   	= $this->language->get('text_features');
			$table['actions']['line_name'][0]   	= '';
			// Build horizontal table
			foreach ($products as $product) {
				if (!$product) {
					unset($this->session->data['compare'][$key]);
				}
				
				$table['name']['value'][$product['product_id']]['name'] 				= $product['name'];
				$table['name']['value'][$product['product_id']]['href'] 				= $product['href'];

				$table['thumb']['value'][$product['product_id']] 						= $product['thumb'];

				$table['price']['value'][$product['product_id']]['price'] 				= $product['price'];
				$table['price']['value'][$product['product_id']]['price_value'] 		= $product['price_value'];
				$table['price']['value'][$product['product_id']]['special'] 			= $product['special'];
				$table['price']['value'][$product['product_id']]['minimum'] 			= $product['minimum'];
				$table['price']['value'][$product['product_id']]['special_date_end'] 	= $product['special_date_end'];

				$table['model']['value'][$product['product_id']] 						= $product['model'];
				$table['manufacturer']['value'][$product['product_id']] 				= $product['manufacturer'];
				$table['stock_status']['value'][$product['product_id']] 				= $product['stock_status'];
				$table['rating']['value'][$product['product_id']]['rating'] 			= $product['rating'];
				$table['rating']['value'][$product['product_id']]['reviews'] 			= $product['reviews'];
				$table['description']['value'][$product['product_id']] 					= $product['description'];
				$table['actions']['value'][$product['product_id']] 						= '';

				$attrs[$product['product_id']] = $this->model_catalog_product->getProductAttributes($product['product_id']);
			}

			
			foreach ($attrs as $product_id => $product_attribute_set) {
				foreach ($product_attribute_set as $attribute_group) {
					// $attribute_table[$attribute['attribute_group_id']]['name'] = $attribute['name'];
					// $attribute_table[$attribute['attribute_group_id']][$product_id] = $attribute['attribute'];
					// $table['attributes']['names'][$attribute['attribute_group_id']] = $attribute['name'];
					// $table['attributes']['values'][$product_id][$attribute['attribute_group_id']] = $attribute['attribute'];
					$table['attributes']['line_name'][$attribute_group['attribute_group_id']] = $attribute_group['name'];
					foreach ($attribute_group['attribute'] as $attribute) {
						// print_r($attribute);
						$table['attributes']['value'][$product_id][$attribute_group['attribute_group_id']][$attribute['attribute_id']] = $attribute['name'];
						// $table['attributes']['value'][$product_id][] = $attribute['name'];
					}
					// print_r($attribute['attribute']);
				}
			}

			// print_r($table);

			// Pass data
			$data['table'] = $table;
			// $data['service'] = $service;
			$data['attribute_table'] = $attribute_table;

		}
		return $data;
	}

	public function showCompareModal(){
		$data = [];
		$json = [];
		$data = $this->renderCompareProducts();
		$json['dialog'] = $this->load->view('product/compare_table', $data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
