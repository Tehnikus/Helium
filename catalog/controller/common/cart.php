<?php
class ControllerCommonCart extends Controller {
	public function displayCart() {
		$this->load->language('common/cart');

		// Totals
		$this->load->model('setting/extension');

		$totals = array();
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Because __call can not keep var references so we put them into an array.
		$total_data = array(
			'totals' => &$totals,
			'taxes'  => &$taxes,
			'total'  => &$total
		);
			
		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$sort_order = array();

			$results = $this->model_setting_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
			}

			array_multisort($sort_order, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get('total_' . $result['code'] . '_status')) {
					$this->load->model('extension/total/' . $result['code']);

					// We have to put the totals in an array so that they pass by reference.
					$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
				}
			}

			$sort_order = array();

			foreach ($totals as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}

			array_multisort($sort_order, SORT_ASC, $totals);
		}
		if ($this->cart->countProducts() !== null && $this->cart->countProducts() > 0) {
			$data['product_count'] = $this->cart->countProducts();
			$data['total_cart'] = $this->currency->format($total, $this->session->data['currency']);
			// $data['text_items'] = sprintf($this->language->get('text_items'), $this->cart->countProducts() + (isset($this->session->data['vouchers']) ? count($this->session->data['vouchers']) : 0), $this->currency->format($total, $this->session->data['currency']));
		} else {
			$data['total_cart'] = $this->language->get('text_header_cart');
			// $data['text_items'] = $this->language->get('text_header_cart');
		}
		$this->load->model('tool/image');
		$this->load->model('tool/upload');

		$data['products'] = array();

		foreach ($this->cart->getProducts() as $product) {
			if ($product['image']) {
				$image = $this->model_tool_image->resize($product['image'], $this->config->get('image_product_width'), $this->config->get('image_product_height'));
			} else {
				$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('image_product_width'), $this->config->get('image_product_height'));
			}

			$option_data = array();

			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$value = $option['value'];
				} else {
					$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

					if ($upload_info) {
						$value = $upload_info['name'];
					} else {
						$value = '';
					}
				}

				$option_data[] = array(
					'name'  => $option['name'],
					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value),
					'type'  => $option['type']
				);
			}

			// Display prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
				
				$price = $this->currency->format($unit_price, $this->session->data['currency']);
				$total = $this->currency->format($unit_price * $product['quantity'], $this->session->data['currency']);
			} else {
				$price = false;
				$total = false;
			}

			$data['products'][] = array(
				'product_id'=> $product['product_id'],
				'cart_id'   => $product['cart_id'],
				'thumb'     => $image,
				'name'      => $product['name'],
				'model'     => $product['model'],
				'option'    => $option_data,
				'recurring' => ($product['recurring'] ? $product['recurring']['name'] : ''),
				'quantity'  => $product['quantity'],
				'price'     => $price,
				'total'     => $total,
				'href'      => $this->url->link('product/product', 'product_id=' . $product['product_id'])
			);
		}

		// Gift Voucher
		$data['vouchers'] = array();

		if (!empty($this->session->data['vouchers'])) {
			foreach ($this->session->data['vouchers'] as $key => $voucher) {
				$data['vouchers'][] = array(
					'key'         => $key,
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $this->session->data['currency'])
				);
			}
		}

		$data['totals'] = array();

		foreach ($totals as $total) {
			$data['totals'][] = array(
				'title' => $total['title'],
				'text'  => $this->currency->format($total['value'], $this->session->data['currency']),
			);
		}

		$data['cart'] = $this->url->link('checkout/cart');
		$data['checkout'] = $this->url->link('checkout/checkout', '', true);
		return $data;
	}

	// Отображение корзины в Header
	public function index() {
		$this->load->language('common/cart');
		if ($this->cart->countProducts() !== null && $this->cart->countProducts() > 0) {
			$data['product_count'] = $this->cart->countProducts();
			$data['total_cart'] = $this->currency->format($this->cart->getTotal(), $this->session->data['currency']);
		} else {
			$data['total_cart'] = $this->language->get('text_header_cart');
		}
		return $this->load->view('common/cart_button', $data);
	}

	// Отображение модального окна
	public function modal() {
		$data = $this->displayCart();
		echo($this->load->view('common/cart_modal', $data));
	}

	// Display additional modal window upon adding to cart, if product has required options
	public function displayAdditionalModal() {
		if (isset($this->request->post['product_id'])) {
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$this->load->language('common/cart');

			$product_id = (int)$this->request->post['product_id'];
			$product = $this->model_catalog_product->getProduct($product_id);
			$data = array();
			// JSON object to calculate product price when option is selected or quantity discounts present

			$data['product_id'] = $product_id;
			$data['name'] = $product['name'];

			// Image
			if ($product['image']) {
				$data['image'] = $this->model_tool_image->resize($product['image'], $this->config->get('image_product_width'), $this->config->get('image_product_height'));
			} else {
				$data['image'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('image_product_width'), $this->config->get('image_product_height'));
			}

			// Prices
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$data['price'] = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$data['price'] = false;
			}

			// Special prices
			if (!is_null($product['special']) && (float)$product['special'] >= 0) {
				$data['special'] = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				$tax_price = (float)$product['special'];
			} else {
				$data['special'] = false;
				$tax_price = (float)$product['price'];
			}

			// Taxes
			if ($this->config->get('config_tax')) {
				$data['tax'] = $this->currency->format($tax_price, $this->session->data['currency']);
			} else {
				$data['tax'] = false;
			}

			// Discounts
			$discounts = $this->model_catalog_product->getProductDiscounts($product_id);
			$data['discounts'] = array();
			foreach ($discounts as $discount) {
				$data['discounts'][] = array(
					'quantity' => $discount['quantity'],
					'price'    => $this->currency->format($this->tax->calculate($discount['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				);
			}

			if ($product['minimum']) {
				$data['minimum'] = $product['minimum'];
			} else {
				$data['minimum'] = 1;
			}

			// Options
			$data['options'] = array();
			$options = $this->model_catalog_product->getProductOptions($product_id);
			foreach ($options as $option) {
				$product_option_value_data = array();

				foreach ($option['product_option_value'] as $option_value) {
					if (!$option_value['subtract'] || ($option_value['quantity'] > 0)) {
						if ((($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) && (float)$option_value['price']) {
							$price = $this->currency->format($this->tax->calculate($option_value['price'], $product['tax_class_id'], $this->config->get('config_tax') ? 'P' : false), $this->session->data['currency']);
							$price_value = $this->currency->format(
								$this->tax->calculate(
									$option_value['price'], $product['tax_class_id'], 
									$this->config->get('config_tax') ? 'P' : false
								), 
								$this->session->data['currency'], 
								'',
								$format = false);
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

			$json_prices = array();
			// $json_prices['product_id_'.$product_id] = array();
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$json_prices['product_id_'.$product_id]['base_price'] = round((float)$product['price'], 2);
			}
			if (!is_null($product['special']) && (float)$product['special'] >= 0) {
				$json_prices['product_id_'.$product_id]['base_price'] = round((float)$product['special'], 2);
			}
			foreach ($discounts as $discount) {
				$json_prices['product_id_'.$product_id]['discounts'][] = array(
					'quantity' 			=> (int)$discount['quantity'],
					'discount_price' 	=> round((float)$discount['price'], 2)
				);
			}
			foreach ($options as $option) {
				foreach ($option['product_option_value'] as $option_value) {
					if ($option_value['price'] > 0) {
						$json_prices['product_id_'.$product_id]['options'][] = array(
							'option_id' 		=> (int)$option_value['option_value_id'],
							'option_price' 		=> $option_value['price_prefix'].round((float)$option_value['price'], 2),
						);
					}
				}
			}
			$data['json_prices'] = json_encode($json_prices);

			echo($this->load->view('common/cart_select_options', $data));
		} else {
			echo('product ID not set');
			return;
		}
	}
}