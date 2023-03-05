<?php
class ControllerProductProduct extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('product/product');
		$this->document->setRobots('index,follow');
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$this->load->model('catalog/category');

		if (isset($this->request->get['path'])) {
			$path = '';
			$parts = explode('_', (string)$this->request->get['path']);
			$category_id = (int)array_pop($parts);

			// foreach ($parts as $path_id) {
			// 	if (!$path) {
			// 		$path = $path_id;
			// 	} else {
			// 		$path .= '_' . $path_id;
			// 	}

			// 	$category_info = $this->model_catalog_category->getCategory($path_id);

			// 	if ($category_info) {
			// 		$data['breadcrumbs'][] = array(
			// 			'text' => $category_info['name'],
			// 			'href' => $this->url->link('product/category', 'path=' . $path)
			// 		);
			// 	}
			// }

			// Set the last category breadcrumb
			$category_info = $this->model_catalog_category->getCategory($category_id);

			if ($category_info) {
				$data['breadcrumbs'][] = array(
					'text' => $category_info['name'],
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'])
				);
			}
		}

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if ($product_info) {
			$product_flags = array();
			$product_flags = $this->model_catalog_product->renderFlags((array)$product_info, (int)$product_info['main_category']);
			$data['product_flags'] = $product_flags;
			$data['breadcrumbs'][] = array(
				'text' => $product_info['name'],
				'href' => $this->url->link('product/product', '&product_id=' . $this->request->get['product_id'])
			);

			if ($product_info['meta_title']) {
				$this->document->setTitle($product_info['meta_title']);
			} else {
				$this->document->setTitle($product_info['name']);
			}
			
				
			
			if ($product_info['meta_h1']) {
				$data['heading_title'] = $product_info['meta_h1'];
			} else {
				$data['heading_title'] = $product_info['name'];
			}
			
			$this->document->setDescription($product_info['meta_description']);
			$this->document->setKeywords($product_info['meta_keyword']);
			$this->document->addLink($this->url->link('product/product', 'product_id=' . $this->request->get['product_id']), 'canonical');



			$data['text_minimum'] = sprintf($this->language->get('text_minimum'), $product_info['minimum']);
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));

			$this->load->model('catalog/review');

			$data['tab_review'] = sprintf($this->language->get('tab_review'), $product_info['reviews']);

			$data['product_id'] = (int)$this->request->get['product_id'];
			$data['manufacturer'] = $product_info['manufacturer'];
			$data['manufacturers'] = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $product_info['manufacturer_id']);
			$data['model'] = $product_info['model'];
			$data['reward'] = $product_info['reward'];
			$data['points'] = $product_info['points'];
			$data['special_date_end'] = $product_info['special_date_end'];
			$data['description'] = html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8');

			if ($product_info['quantity'] <= 0) {
				$data['stock'] = $product_info['stock_status'];
			} elseif ($this->config->get('config_stock_display')) {
				$data['stock'] = $product_info['quantity'];
			} else {
				$data['stock'] = $this->language->get('text_instock');
			}


			// Images
			$this->load->model('tool/image');
			if ($product_info['image']) {
				$data['popup'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
				$data['thumb'] = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			} else {
				$data['popup'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height'));
				$data['thumb'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_thumb_height'));
			}
			$data['images'] = array();
			$additional_images = $this->model_catalog_product->getProductImages($this->request->get['product_id']);
			foreach ($additional_images as $img) {
				$data['images'][] = array(
					'popup' => $this->model_tool_image->resize($img['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')),
					'thumb' => $this->model_tool_image->resize($img['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_additional_height'))
				);
			}

			// Prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}
			if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
				$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$tax_price = (float)$product_info['special'];
			} else {
				$data['special'] = false;
				$tax_price = (float)$product_info['price'];
			}
			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format($tax_price, $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			// Wholesale discounts
			$discounts = $this->model_catalog_product->getProductDiscounts($this->request->get['product_id']);
			$data['discounts'] = array();
			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
					'discount_date_end' => $discount['date_end']
				);
			}

			// Options
			$data['options'] = array();
			$options = $this->model_catalog_product->getProductOptions($this->request->get['product_id']);
			foreach ($options as $option) {
				$product_option_value_data = array();

				foreach ($option['product_option_value'] as $option_value) {
					// TODO Здесь сделать опцию невыбираемой если она закончилась на складе
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
							$price_value = $this->currency->format($this->tax->calculate($option_value['price'], $product_info['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency'], '', false);
						} else {
							$price = false;
							$price_value = false;
						}

						$product_option_value_data[] = array(
							'product_option_value_id' => $option_value['product_option_value_id'],
							'option_value_id'         => $option_value['option_value_id'],
							'name'                    => $option_value['name'],
							'image'                   => $option_value['image'] !== '' ? $this->model_tool_image->resize($option_value['image'], 50, 50) : '',
							'price'                   => $price,
							'price_value'			  => $price_value ? $option_value['price_prefix'].$price_value : '',
							'price_prefix'            => $option_value['price_prefix'],
							'default_option'		  => $option_value['default_option']
						);
					}
				}

				$data['options'][] = array(
					'product_option_id'    => $option['product_option_id'],
					'product_option_value' => $product_option_value_data,
					'option_id'            => $option['option_id'],
					'name'                 => $option['name'],
					'type'                 => $option['type'],
					'value'                => $option['value'],
					'required'             => $option['required'],
					'default_option_isset' => $option['default_option_isset']
				);
			}

			if ($product_info['minimum']) {
				$data['minimum'] = $product_info['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			$data['review_status'] = $this->config->get('config_review_status');

			if ($this->config->get('config_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

			$data['reviews'] = sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']);
			$data['rating'] = (int)$product_info['rating'];

			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			$data['share'] = $this->url->link('product/product', 'product_id=' . (int)$this->request->get['product_id']);

			$data['attribute_groups'] = $this->model_catalog_product->getProductAttributes($this->request->get['product_id']);

			$data['products'] = array();

			$related_products = $this->model_catalog_product->getProductRelated($this->request->get['product_id']);

			foreach ($related_products as $related_product) {
				if ($related_product['image']) {
					$image = $this->model_tool_image->resize($related_product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($related_product['price'], $related_product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if (!is_null($related_product['special']) && (float)$related_product['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($related_product['special'], $related_product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$related_product['special'];
				} else {
					$special = false;
					$tax_price = (float)$related_product['price'];
				}
	
				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$related_product['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $related_product['product_id'],
					'thumb'       => $image,
					'name'        => $related_product['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($related_product['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $related_product['minimum'] > 0 ? $related_product['minimum'] : 1,
					'rating'      => $rating,
					'reviews'     => $related_product['reviews'],
					'href'        => $this->url->link('product/product', 'product_id=' . $related_product['product_id'])
				);
			}

			$data['tags'] = array();

			if ($product_info['tag']) {
				$tags = explode(',', $product_info['tag']);

				foreach ($tags as $tag) {
					$data['tags'][] = array(
						'tag'  => trim($tag),
						'href' => $this->url->link('product/search', 'tag=' . trim($tag))
					);
				}
			}

			$data['recurrings'] = $this->model_catalog_product->getProfiles($this->request->get['product_id']);

			$this->model_catalog_product->updateViewed($this->request->get['product_id']);
			$this->model_catalog_product->addViewedProduct($this->request->get['product_id']);
			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$data['review_list'] = $this->reviews();

			$this->response->setOutput($this->load->view('product/product', $data));
		} else {
			// 404 Not found
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', '&product_id=' . $product_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	// Show reviews list statically
	public function reviews() {
		$this->load->language('product/product');

		$this->load->model('catalog/review');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($this->request->get['product_id']);

		$results = $this->model_catalog_review->getReviewsByProductId($this->request->get['product_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('product/product/review', 'product_id=' . $this->request->get['product_id'] . '&page={page}');

		$data['reviews_pagination'] = $pagination->render();
		$data['reviews_results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		return $data;
	}

	// Отдельная функция для подгрузки отзывов Аяксом
	// Получает отзывы из функции reviews()
	// И отображает файл шаблона с отзывами
	public function review() {
		$data = array();
		$data['review_list'] = $this->reviews();
		$this->response->setOutput($this->load->view('product/review', $data));
	}

	// Display review modal window 
	public function displayReviewModal() {
		$this->load->language('product/product');
		$data['entity_id'] = (int)$this->request->post['entity_id'];
		$data['type'] = 'product/product';
		$this->response->setOutput($this->load->view('common/review_form', $data));
	}

	public function sendReview() {
		$this->load->language('product/product');

		$json = array();

		if (isset($this->request->get['entity_id']) && $this->request->get['entity_id']) {
			if ($this->request->server['REQUEST_METHOD'] == 'POST') {
				if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
					$json['error'] = $this->language->get('error_name');
				}

				if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
					$json['error'] = $this->language->get('error_text');
				}

				if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
					$json['error'] = $this->language->get('error_rating');
				}

				// Captcha
				if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
					$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

					if ($captcha) {
						$json['error'] = $captcha;
					}
				}

				if (!isset($json['error'])) {
					$this->load->model('catalog/review');

					$this->model_catalog_review->addReview($this->request->get['entity_id'], $this->request->post);

					$json['success'] = $this->language->get('text_success');
				}
			}
		} else {
			$json['error'] = $this->language->get('error_product');
		} 

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getRecurringDescription() {
		$this->load->language('product/product');
		$this->load->model('catalog/product');

		if (isset($this->request->post['product_id'])) {
			$product_id = $this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (isset($this->request->post['recurring_id'])) {
			$recurring_id = $this->request->post['recurring_id'];
		} else {
			$recurring_id = 0;
		}

		if (isset($this->request->post['quantity'])) {
			$quantity = $this->request->post['quantity'];
		} else {
			$quantity = 1;
		}

		$product_info = $this->model_catalog_product->getProduct($product_id);
		
		$recurring_info = $this->model_catalog_product->getProfile($product_id, $recurring_id);

		$json = array();

		if ($product_info && $recurring_info) {
			if (!$json) {
				$frequencies = array(
					'day'        => $this->language->get('text_day'),
					'week'       => $this->language->get('text_week'),
					'semi_month' => $this->language->get('text_semi_month'),
					'month'      => $this->language->get('text_month'),
					'year'       => $this->language->get('text_year'),
				);

				if ($recurring_info['trial_status'] == 1) {
					$price = $this->currency->format($this->tax->calculate($recurring_info['trial_price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$trial_text = sprintf($this->language->get('text_trial_description'), $price, $recurring_info['trial_cycle'], $frequencies[$recurring_info['trial_frequency']], $recurring_info['trial_duration']) . ' ';
				} else {
					$trial_text = '';
				}

				$price = $this->currency->format($this->tax->calculate($recurring_info['price'] * $quantity, $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

				if ($recurring_info['duration']) {
					$text = $trial_text . sprintf($this->language->get('text_payment_description'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				} else {
					$text = $trial_text . sprintf($this->language->get('text_payment_cancel'), $price, $recurring_info['cycle'], $frequencies[$recurring_info['frequency']], $recurring_info['duration']);
				}

				$json['success'] = $text;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
