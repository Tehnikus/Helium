<?php
class ControllerCommonCart extends Controller {
	public function getCartData() {
		
		$this->load->language('common/cart');
		$this->load->language('product/product');

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
		$this->load->language('product/product');
		if ($this->cart->countProducts() !== null && $this->cart->countProducts() > 0) {
			$data['product_count'] = $this->cart->countProducts();
			$data['total_cart'] = $this->currency->format($this->cart->getTotal(), $this->session->data['currency']);
		} else {
			$data['total_cart'] = $this->language->get('text_header_cart');
		}
		return $this->load->view('common/cart_button', $data);
	}

	// Отображение модального окна
	public function displayCartModal() {
		$data = [];
		
		// print_r($this->session->data['shipping_methods']);
		$data = $this->getCartData();
		$this->getQuickCheckoutData($data);
		echo($this->load->view('common/cart_modal', $data));
	}

	// Display additional modal window upon adding to cart, if product has required options
	public function displayAdditionalModal() {
		if (isset($this->request->post['product_id'])) {
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$this->load->language('common/cart');
			$this->load->language('product/product');
			
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

			//////////////////////////////////////////////////
			// JSON object to calculate product price when option is selected or quantity discounts present
			//////////////////////////////////////////////////
			$json_prices = array();
			// $json_prices[$product_id] = array();
			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$json_prices[$product_id]['base_price'] = (float)$product['price'];
			}
			if (!is_null($product['special']) && (float)$product['special'] >= 0) {
				$json_prices[$product_id]['base_price'] = (float)$product['special'];
			}

			// Discounts
			$json_prices[$product_id]['discounts'][$data['minimum']] = round((float)$json_prices[$product_id]['base_price'], 2);
			
			foreach ($discounts as $discount) {
				$json_prices[$product_id]['discounts'][(int)$discount['quantity']] = round((float)$discount['price'], 2);
			}

			foreach ($options as $option) {
				foreach ($option['product_option_value'] as $option_value) {
					if ($option_value['price'] > 0) {
						$json_prices[$product_id]['options'][] = array(
							(int)$option_value['product_option_value_id'] => $option_value['price_prefix'].$option_value['price'],
						);
					}
				}
			}
			$data['json_prices'] = json_encode($json_prices);
			////////////////////////////////////////////////////
			$data['json_prices'] = json_encode($json_prices);
			$json = array();
			$json['data'] = $this->load->view('common/cart_select_options', $data);
			$json['script'] = 'var json_prices='.json_encode($json_prices);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			// echo($this->load->view('common/cart_select_options', $data));
		} else {
			echo('product ID not set');
			return;
		}
	}
	public function fetchProductCount() {
		echo($this->cart->countProducts());
	}

	public function getQuickCheckoutData(&$data) {
		$this->load->language('checkout/checkout');
		$this->load->model('account/address');
		$this->load->model('localisation/country');
		$this->load->model('account/custom_field');
		$this->load->model('setting/extension');
		if(!$data) {
			$data = [];
		}

		$data['addresses'] = $this->model_account_address->getAddresses();

		// $customer_data = [];
		// foreach ($this->session->data as $account_type => $field) {
		// 	if ($account_type == 'payment_address' || $account_type == 'guest' || $account_type == 'shipping_address') {
		// 		if (is_array($this->session->data[$account_type])) {
		// 			foreach ($this->session->data[$account_type] as $field_name => $field_value) {
		// 				if ($field_value !== '') {
		// 					$customer_data[$field_name] = $field_value;
		// 				}
		// 			}
		// 		}
		// 	}
		// }
		// print_r($customer_data);


		$data['shipping_methods'] = $this->getShippingMethods();



		

		if (isset($this->session->data['payment_address']['address_id'])) {
			$data['address_id'] = $this->session->data['payment_address']['address_id'];
		} else {
			$data['address_id'] = $this->customer->getAddressId();
		}


		if (isset($this->session->data['payment_address']['country_id'])) {
			$data['country_id'] = $this->session->data['payment_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}


		if (isset($this->session->data['guest']['customer_group_id'])) {
			$data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
		} else {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (isset($this->session->data['guest']['firstname'])) {
			$data['firstname'] = $this->session->data['guest']['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->session->data['guest']['lastname'])) {
			$data['lastname'] = $this->session->data['guest']['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->session->data['guest']['email'])) {
			$data['email'] = $this->session->data['guest']['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->session->data['guest']['telephone'])) {
			$data['telephone'] = $this->session->data['guest']['telephone'];
		} else {
			$data['telephone'] = '';
		}

		if (isset($this->session->data['payment_address']['company'])) {
			$data['company'] = $this->session->data['payment_address']['company'];
		} else {
			$data['company'] = '';
		}

		if (isset($this->session->data['payment_address']['address_1'])) {
			$data['address_1'] = $this->session->data['payment_address']['address_1'];
		} else {
			$data['address_1'] = '';
		}

		if (isset($this->session->data['payment_address']['address_2'])) {
			$data['address_2'] = $this->session->data['payment_address']['address_2'];
		} else {
			$data['address_2'] = '';
		}

		if (isset($this->session->data['payment_address']['postcode'])) {
			$data['postcode'] = $this->session->data['payment_address']['postcode'];
		} elseif (isset($this->session->data['shipping_address']['postcode'])) {
			$data['postcode'] = $this->session->data['shipping_address']['postcode'];
		} else {
			$data['postcode'] = '';
		}

		if (isset($this->session->data['payment_address']['city'])) {
			$data['city'] = $this->session->data['payment_address']['city'];
		} else {
			$data['city'] = '';
		}

		if (isset($this->session->data['payment_address']['country_id'])) {
			$data['country_id'] = $this->session->data['payment_address']['country_id'];
		} elseif (isset($this->session->data['shipping_address']['country_id'])) {
			$data['country_id'] = $this->session->data['shipping_address']['country_id'];
		} else {
			$data['country_id'] = $this->config->get('config_country_id');
		}

		if (isset($this->session->data['payment_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['payment_address']['zone_id'];
		} elseif (isset($this->session->data['shipping_address']['zone_id'])) {
			$data['zone_id'] = $this->session->data['shipping_address']['zone_id'];
		} else {
			$data['zone_id'] = '';
		}

		

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();

		if (isset($this->session->data['guest']['custom_field'])) {
			if (isset($this->session->data['guest']['custom_field'])) {
				$guest_custom_field = $this->session->data['guest']['custom_field'];
			} else {
				$guest_custom_field = array();
			}

			if (isset($this->session->data['payment_address']['custom_field'])) {
				$address_custom_field = $this->session->data['payment_address']['custom_field'];
			} else {
				$address_custom_field = array();
			}

			$data['guest_custom_field'] = $guest_custom_field + $address_custom_field;
		} else {
			$data['guest_custom_field'] = array();
		}

		$data['shipping_required'] = $this->cart->hasShipping();

		if (isset($this->session->data['guest']['shipping_address'])) {
			$data['shipping_address'] = $this->session->data['guest']['shipping_address'];
		} else {
			$data['shipping_address'] = true;
		}
		return $data;
		// echo($this->load->view('common/quick_checkout', $data));
	}

	public function displayQuickCheckout() {
		$data = false;
		$data = $this->getQuickCheckoutData($data);
		if ($data) {
			echo($this->load->view('common/quick_checkout', $data));
		} else {
			echo('Quick checkout loading error, no data provided');
		}
	}


	
	// public function getShipping() {
	// 	$this->load->language('checkout/checkout');

	// 	if (isset($this->session->data['shipping_address'])) {
	// 		// Shipping Methods
	// 		$method_data = array();

	// 		$this->load->model('setting/extension');

	// 		$results = $this->model_setting_extension->getExtensions('shipping');

	// 		foreach ($results as $result) {
	// 			if ($this->config->get('shipping_' . $result['code'] . '_status')) {
	// 				$this->load->model('extension/shipping/' . $result['code']);

	// 				$quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

	// 				if ($quote) {
	// 					$method_data[$result['code']] = array(
	// 						'title'      => $quote['title'],
	// 						'quote'      => $quote['quote'],
	// 						'sort_order' => $quote['sort_order'],
	// 						'error'      => $quote['error']
	// 					);
	// 				}
	// 			}
	// 		}

	// 		$sort_order = array();

	// 		foreach ($method_data as $key => $value) {
	// 			$sort_order[$key] = $value['sort_order'];
	// 		}

	// 		array_multisort($sort_order, SORT_ASC, $method_data);

	// 		$this->session->data['shipping_modules'] = $method_data;
	// 	}

	// 	if (empty($this->session->data['shipping_modules'])) {
	// 		$data['error_warning'] = sprintf($this->language->get('error_no_shipping'), $this->url->link('information/contact'));
	// 	} else {
	// 		$data['error_warning'] = '';
	// 	}

	// 	if (isset($this->session->data['shipping_modules'])) {
	// 		$data['shipping_modules'] = $this->session->data['shipping_modules'];
	// 	} else {
	// 		$data['shipping_modules'] = array();
	// 	}

	// 	if (isset($this->session->data['shipping_method']['code'])) {
	// 		$data['code'] = $this->session->data['shipping_method']['code'];
	// 	} else {
	// 		$data['code'] = '';
	// 	}

	// 	if (isset($this->session->data['comment'])) {
	// 		$data['comment'] = $this->session->data['comment'];
	// 	} else {
	// 		$data['comment'] = '';
	// 	}
		
	// 	return  $data;
	// }



	// get Shipping methods
	public function getShippingMethods() {
		$this->load->model('setting/extension');

		$shipping_methods = [];
		$shipping_modules = $this->model_setting_extension->getExtensions('shipping');
		// If cart has shipping
		// Prepare shipping modules
		if ($this->cart->hasShipping()) {

			// If shipping address is present - display full delivery methods with prices
			// Render modules as normal
			if (is_array($this->session->data) && isset($this->session->data['shipping_address'])) {
				foreach ($shipping_modules as $module) {
					if ($this->config->get('shipping_' . $module['code'] . '_status')) {
						$this->load->model('extension/shipping/' . $module['code']);
						// Request shipping module method
						$quote = $this->{'model_extension_shipping_' . $module['code']}->getQuote($this->session->data['shipping_address']);
						// If module returns some data - add it to the list
						if ($quote) {
							$shipping_methods[$module['code']] = array(
								'title'      => $quote['title'],
								'quote'      => $quote['quote'],
								'sort_order' => $quote['sort_order'],
								'error'      => $quote['error']
							);
						}
					}
				}
			} else {
				// Else - just delivery names
				foreach ($shipping_modules as $module) {
					// Load language of every shipping module
					$this->load->language('extension/shipping/'.$module['code']);
					// Set title and code for every module
					$shipping_methods[$module['code']] = array(
						'title' => $this->language->get('text_title'),
						'code' => $module['code'],
						// Place unordered shipping methods at the end 
						'sort_order' =>  ($this->config->get('shipping_'.$module['code'].'_sort_order') !== '') ? $this->config->get('shipping_'.$module['code'].'_sort_order') : '99',
						// Get module status related to countries set in module settings
						'status' => (int)$this->config->get('shipping_'.$module['code'].'_geo_zone_id')
					);
				}
				// print_r($shipping_methods);
			}

			// Sort modules by sort order set in admin panel
			$sort_order = array();
			foreach ($shipping_methods as $key => $value) {
				$sort_order[$key] = $value['sort_order'];
			}
			array_multisort($sort_order, SORT_ASC, $shipping_methods);
		}
		// print_r($this->session->data['quick_checkout']);
		return $shipping_methods;
	}
	// Display shipping for fetch requests
	public function displayShipping($data) {
		$this->response->setOutput($this->load->view('checkout/shipping_method', $data));
	}



	// Save fields by fetch request while typing
	public function fetchSaveQuickCheckoutfields() {
		$json_errors = [];
		// remove empty entries so nothing falsy triggers
		$data = $this->removeEmptyArrayValues($this->request->post);

		// Add required data to guest
		if (isset($data['guest']) &&
			isset($data['guest']['firstname']) &&
			isset($data['guest']['lastname']) &&
			isset($data['guest']['telephone'])) {
			// Default guest customer group
			$data['guest']['customer_group_id'] = $this->config->get('config_customer_group_id');
			// Shipping address is the same as payment
			$data['guest']['shipping_address'] = true;
		}
		// Save Guest fields in any case 
		foreach ($data['guest'] as $guest_field => $guest_value) {
			$this->session->data['guest'][$guest_field] = $guest_value;
		}

		// Country data
		if (isset($data['shipping_address'])) {
			// Get country data required for shipping
			if (isset($data['shipping_address']['country_id'])) {
				$this->load->model('localisation/country');
				$country_info = $this->model_localisation_country->getCountry($data['shipping_address']['country_id']);
				$data['country'] = $country_info;
			}
			// Get zone data
			if (isset($data['shipping_address']['zone_id'])) {
				$this->load->model('localisation/zone');
				$zone_info = $this->model_localisation_zone->getZone($data['shipping_address']['zone_id']);
				$data['zone'] = $zone_info;
			}

			if ((isset($data['country']) && !empty($data['country'])) && (isset($data['zone']) && !empty($data['zone']))) {
				$country_and_zone = array_merge($data['country'], $data['zone']);
				$data['shipping_address'] = array_merge($data['shipping_address'], $country_and_zone);
				unset($data['country'], $data['zone']);
			}

			// Check country errors
			$address_errors = $this->checkAddressErrors($data['shipping_address'], $country_info);
			if (empty($address_errors)) {
				// If no errors, copy shipping address to payment address
				$data['payment_address'] = $data['shipping_address'];
				// Get shipping methods
				$data['shipping_methods'] = $this->getShippingMethods();
				// Set session data
				$this->session->data['shipping_address'] = $data['shipping_address'];
				$this->session->data['payment_address'] = $data['payment_address'];
			} else {
				// Else return errors and handleErrors() in javascript
				echo(json_encode($address_errors));
				die;
			}
		}

		// If available shipping methods are rendered successfully and one of them is selected
		// TODO Load language file here
		if (isset($data['shipping_method']) && isset($data['shipping_methods'])) {
			$shipping_errors = [];
			$shipping = explode('.', $data['shipping_method']);
			// echo($data['shipping_method']);
			if (!isset($shipping[0]) || !isset($shipping[1]) || !isset($data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])) {
				$shipping_errors['errors'] = $this->language->get('error_shipping');
				echo(json_encode($shipping_errors));
				die;
			} else {
				$data['shipping_method'] = $data['shipping_methods'][$shipping[0]];
			}
		}
		
		if (!isset($this->session->data['quick_checkout'])) {
			$this->session->data['quick_checkout'] = [];
		}
		// Save data to session
		$this->session->data['quick_checkout'] = $data;
		
		// Now just print out data, so I'll see if something missing
		echo(json_encode($this->session->data['quick_checkout']));
	}

	// Check if all address fields filled correctly
	public function checkAddressErrors($data, $country_info) {
		// TODO Load language file here
		$json = [];
		if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($data['postcode'])) < 2 || utf8_strlen(trim($data['postcode'])) > 10)) {
			$json['error']['postcode'] = $this->language->get('error_postcode');
		}

		if (!isset($data['country_id']) || $data['country_id'] == '') {
			$json['error']['country_id'] = $this->language->get('error_country');
		}

		if (!isset($data['zone_id']) || $data['zone_id'] == '') {
			$json['error']['zone_id'] = $this->language->get('error_zone');
		}
		return $json;
	}


	// Remove empty values from array
	// Thanks, stackoverflow
	function removeEmptyArrayValues($array) {
		if (!is_array($array)) {
			return $array;
		}
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array[$k] = $this->removeEmptyArrayValues($v);

                if (0 == count($array[$k])) {
                    unset($array[$k]);
                }
            } elseif (empty($v)) {
                unset($array[$k]);
            }
        }
		return $array;
	}
}