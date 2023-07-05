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

		$json = array();

		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			if (!in_array($this->request->post['product_id'], $this->session->data['compare'])) {
				if (count($this->session->data['compare']) >= 4) {
					array_shift($this->session->data['compare']);
				}

				$this->session->data['compare'][] = $this->request->post['product_id'];
			}

			$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('product/compare'));
			$table = $this->renderCompareProducts();
			$json['dialog'] = $this->load->view('product/compare_table', $table);
			$json['html']['replace']['#compare-total'] = '<i class="icon-compare"></i>'.sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function remove() {
		// TODO
		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}
		if (isset($this->request->get['remove'])) {
			$key = array_search($this->request->get['remove'], $this->session->data['compare']);
			if ($key !== false) {
				unset($this->session->data['compare'][$key]);
				$this->response->setOutput($this->language->get('text_removed'));
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
		// Set first column with headers of compared data
		// Product data is compared to this keys so if key doesn't exist in first column, it will not be added
		$table['name'][]         = $this->language->get('text_name');
		$table['thumb'][]        = $this->language->get('text_image');
		$table['price'][]        = $this->language->get('text_price');
		$table['model'][]        = $this->language->get('text_model');
		$table['manufacturer'][] = $this->language->get('text_manufacturer');
		$table['stock_status'][] = $this->language->get('text_availability');
		$table['rating'][]       = $this->language->get('text_rating');
		$table['reviews'][]      = $this->language->get('text_reviews');
		$table['description'][]  = $this->language->get('text_summary');
		$table['attributes'][]     = $this->language->get('text_features');
		
		// Service data to add product to cart, remove from comparison, fire some scripts etc
		$table['product_id'][]        = '';
		$table['href'][]              = '';
		$table['special_date_end'][]  = '';
		$table['discount_date_end'][] = '';
		$table['discount_quantity'][] = '';
		$table['price_value'][] = '';

		// Service data that will NOT be displayed in table rows
		$service = ['href', 'special_date_end', 'discount_date_end', 'discount_quantity', 'reviews', 'special', 'price_value'];
		// Service var to create horizontal comparison table
		$attrs = [];

		if (isset($this->session->data['compare']) && is_array($this->session->data['compare']) && count($this->session->data['compare']) > 0) {
			// Get products data
			foreach ($this->session->data['compare'] as $key => $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id);
			}
			$products = $this->model_catalog_product->prepareProductList($product_list, null);

			// Build horizontal table
			foreach ($products as $product) {
				// Check if product exists
				if (!$product) {
					// Delete from comparison if product doesn't exist
					unset($this->session->data['compare'][$key]);
				}
				foreach ($product as $key => $product_feature) {
					// Only existing keys allowed
					if (isset($table[$key])) {
						$table[$key][$product['product_id']] = $product_feature;
					}
				}

				$attrs[$product['product_id']] = $this->model_catalog_product->getProductAttributes($product['product_id']);
				// $attribute_groups = $this->model_catalog_product->getProductAttributes($product['product_id']);
				// foreach ($attribute_groups as $attribute_group) {
				// 	$attrs[$attribute_group['attribute_group_id']]['name'] = $attribute_group['name'];

				// 	foreach ($attribute_group['attribute'] as $attribute) {
				// 		$attrs[$attribute_group['attribute_group_id']]['values'][$product['product_id']][$attribute_group['attribute_group_id']]['name'] = $attribute['name'];
				// 		$attrs[$attribute_group['attribute_group_id']]['values'][$product['product_id']][$attribute_group['attribute_group_id']]['text'] = $attribute['text'];

				// 	}
				// }
			}
			// $product_ids_attributes = array_keys($attrs);
			// foreach ($product_ids_attributes as  $id) {
			// 	print_r(array_diff($attrs[$id]));
			// }
			$temp_attrs = [];
			foreach ($attrs as $product_id => $attr_group) {
				// Check if product has attributes
				if (!empty($attr_group)) {
					foreach ($attr_group as $attr_set) {
						$temp_attrs[$product_id][$attr_set['attribute_group_id']]['name'] = $attr_set['name'];
						foreach ($attr_set['attribute'] as $attr) {
							$temp_attrs[$product_id][$attr_set['attribute_group_id']]['values'][$attr['attribute_id']]['name'] = $attr['name'];
							$temp_attrs[$product_id][$attr_set['attribute_group_id']]['values'][$attr['attribute_id']]['text'] = $attr['text'];
						}
					}
				}
			}

			$attribute_table = $this->compareAndCopyFeatures($temp_attrs);
			unset($temp_attrs);

			$features = [];
			foreach ($attribute_table as $product_id => $feature_set) {
				foreach ($feature_set as $key => $feature) {
					// print_r($feature);
					$features[$key]['name'] = $feature['name'];
					// $features[$key]['values'][$product_id] = $feature_set;
				}
				// print_r($feature_set);
			}
			// Pass data
			$data['table'] = $table;
			$data['service'] = $service;
			$data['attribute_table'] = $features;

		}
		// print_r($attribute_table);
		return $data;
	}

	public function showCompareModal(){
		$data = [];
		$response = [];
		$data = $this->renderCompareProducts();
		$response['dialog'] = $this->load->view('product/compare_table', $data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}
	// Compare features and pass '--' if product doesn't have the feature that other has
	function compareAndCopyFeatures($allProductFeatures)
	{
		$allFeatures = [];

		// Collect all features from all products
		foreach ($allProductFeatures as $productFeatures) {
			foreach ($productFeatures as $featureID => $feature) {
				$allFeatures[$featureID] = $feature['name'];
			}
		}

		// Add missing features to each product
		foreach ($allProductFeatures as &$productFeatures) {
			foreach ($allFeatures as $featureID => $featureName) {
				if (!isset($productFeatures[$featureID])) {
					$productFeatures[$featureID] = [
						'name' => $featureName,
						'values' => ['--'],
					];
				}
			}
		}

		// Sort features by their IDs
		// foreach ($allProductFeatures as &$productFeatures) {
		// 	ksort($productFeatures);
		// }

		unset($productFeatures); // Unset the reference

		return $allProductFeatures;
	}
}
