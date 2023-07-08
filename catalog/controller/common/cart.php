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
	public function showCartModal() {
		$data = [];
		$response = [];
		$data = $this->getCartData();
		$this->getQuickCheckoutData($data);
		$response['dialog'] = $this->load->view('common/cart_modal', $data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	// Display additional modal window upon adding to cart, if product has required options
	public function showAdditionalModal() {
		if (isset($this->request->post['product_id'])) {
			$data = $this->renderAdditionalModal();
			$json = array();
			$json['data'] = $this->load->view('common/cart_select_options', $data);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
		}
	}
	public function renderAdditionalModal() {
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
			return $data;
		}
	}

	public function fetchProductCount() {
		echo($this->cart->countProducts());
	}


	// Fill entries of Quick checkout inputs
	// Should comly with regular checkout so if you fill one form they will appear in all other forms
	public function getQuickCheckoutData(&$data) {
		$this->load->language('checkout/cart');

		
		$this->load->model('localisation/country');
		$this->load->model('account/custom_field');
		$this->load->model('setting/extension');
		if(!$data) {
			$data = [];
		}

		// Get customer addreses
		// If customer is registered address selector will appear instead of checkut form
		$this->load->model('account/address');
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

		$data['shipping_required'] = $this->cart->hasShipping();


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

		
		if (isset($this->session->data['guest']['telephone'])) {
			$data['phone'] = $this->session->data['guest']['telephone'];
		} else {
			$data['phone'] = '';
		}
		
		
		if (isset($this->session->data['payment_address']['address_1'])) {
			$data['address_1'] = $this->session->data['payment_address']['address_1'];
		} else {
			$data['address_1'] = '';
		}
		
		if (isset($this->session->data['guest']['email'])) {
			$data['email'] = $this->session->data['guest']['email'];
		} else {
			$data['email'] = '';
		}
		// if (isset($this->session->data['payment_address']['company'])) {
		// 	$data['company'] = $this->session->data['payment_address']['company'];
		// } else {
		// 	$data['company'] = '';
		// }
		// if (isset($this->session->data['payment_address']['address_2'])) {
		// 	$data['address_2'] = $this->session->data['payment_address']['address_2'];
		// } else {
		// 	$data['address_2'] = '';
		// }

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

		if (isset($this->session->data['shipping_method']) && isset($this->session->data['shipping_method']['code'])) {
			$data['selected_shipping_method'] = $this->session->data['shipping_method']['code'];
		} else {
			$data['selected_shipping_method'] = '';
		}
		if (isset($this->session->data['payment_method']) && isset($this->session->data['payment_method']['code'])) {
			$data['selected_payment_method'] = $this->session->data['payment_method']['code'];
		} else {
			$data['selected_payment_method'] = '';
		}

		$data['countries'] = $this->model_localisation_country->getCountries();

		// Custom Fields
		// Set input values if present
		// TODO in twig template make condition for select and radio inputs
		// Something like this: if isset $custom_field['value'] then $custom_field['custom_field_value'] = selected
		// TODO Fix custom fields
		$data['custom_fields'] = $this->model_account_custom_field->getCustomFields();
		foreach ($data['custom_fields'] as &$custom_field) {
			if (isset($this->session->data['shipping_address'])) {
				if (isset($this->session->data['shipping_address']['custom_field']) && isset($this->session->data['shipping_address']['custom_field']['address'])) {
					foreach ($this->session->data['shipping_address']['custom_field']['address'] as $key => $user_custom_field) {
						if ($custom_field['custom_field_id'] == $key) {
							$custom_field['value'] = $user_custom_field;
						}
					}
				}
			}
		}

		$data['shipping_methods'] = $this->getShippingMethods();
		$data['payment_methods'] = $this->getPaymentMethods();
		if (isset($this->session->data['guest']['shipping_address'])) {
			$data['shipping_address'] = $this->session->data['guest']['shipping_address'];
		}
		return $data;
	}


	// Get Shipping methods with native method
	// Native method saves data to session
	public function getShippingMethods() {
		$data = $this->load->controller('checkout/shipping_method/getShippingMethodsData');
		// If shipping method is requested by fetch, we need to add customer selected method value, if present
		$data['selected_shipping_method'] = (isset($this->session->data['shipping_method']) && isset($this->session->data['shipping_method']['code'])) ? $this->session->data['shipping_method']['code'] : '';
		return $data;
	}

	// Display shipping methods html
	public function fetchDisplayShippingHtml() {
		$this->load->language('common/cart');
		$data = [];
		$response = [];
		$data = $this->getShippingMethods();
		// $data['selected_shipping_method'] = $this->session->data['shipping_method']['code'];
		$response['html']['replace']['#js_qc_delivery'] = $this->load->view('checkout/quick_checkout_shipping', $data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	// Get payment methods
	public function getPaymentMethods() {
		$data = $this->load->controller('checkout/payment_method/getPaymentMethodsData');
		// If payment method is requested by fetch, we need to add customer selected method value, if present
		$data['selected_payment_method'] = (isset($this->session->data['payment_method']) && isset($this->session->data['payment_method']['code'])) ? $this->session->data['payment_method']['code'] : '';
		return $data;
	}

	public function fetchDisplayPaymentHtml() {
		$this->load->language('common/cart');
		$data = [];
		$response = [];
		$data = $this->getPaymentMethods();
		// if (($this->session->data['shipping_address']['custom_field'] ?? null) !== null) {
		// 	unset($this->session->data['shipping_address']['custom_field']);
		// }
		// $data['selected_payment_method'] = $this->session->data['payment_method']['code'];
		$response['html']['replace']['#js_qc_payment'] = $this->load->view('checkout/quick_checkout_payment', $data);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($response));
	}

	public function fetchSaveExistingAddress()
	{
		$this->load->model('account/address');
		if (isset($this->request->post['address_id']) && $this->request->post['address_id'] !== false) {
			
			// Load reqired models
			$this->load->model('account/customer');
			$this->load->model('account/address');
			
			// Set selected shipping and payment address
			$data =  $this->model_account_address->getAddress($this->request->post['address_id']);
			$this->session->data['shipping_address'] = $data;
			$this->session->data['payment_address'] = $data;

			// Get customer data
			$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
			// Set array if not set
			if (!isset($this->session->data['customer'])) {
				$this->session->data['customer'] = [];
			}
			// Set customer name according to address 
			$this->session->data['customer']['firstname'] = $data['firstname'];
			$this->session->data['customer']['lastname']  = $data['lastname'];
			// Set phone and email
			$this->session->data['customer']['telephone'] 			= $customer_info['telephone'];
			$this->session->data['customer']['email']     			= $customer_info['email'];
			$this->session->data['customer']['customer_group_id']   = $customer_info['customer_group_id'];

			// Unset guest data
			unset($this->session->data['guest']);
			echo(json_encode($customer_info));
		}
	}

	public function getConfirmOrder() {
		$json = [];
		$json = $this->checkErrors();
		if (!empty($json)) {
			// If errors occured return JSON with errors and then process handleErrors() in JS
			echo(json_encode($json));
			die;
		} else {
			// If no errors, load confirm order controller
			$json = $this->load->controller('checkout/confirm/getConfirmData');
			// Check if payment method needs redirect
			// if (isset($json['redirect'])) {
			// 	echo(json_encode($json));
			// 	die;
			// } else {
				// If no redirect needed, load payment controller with confirm link and finally redirect to successfully created order
				// $json = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code'] . '/confirm');
				// echo(json_encode($json));
				// echo($this->load->controller('extension/payment/' . $this->session->data['payment_method']['code'] . '/confirm'));
				
				
				// TODO Add config for Quick checkout payment mathods - which one creates order, which one displays payment form
				// Allowed payment methods that just redirect to successful order page
				
				$allowed_payment_methods = ['bank_transfer', 'cheque', 'cod', 'free_checkout'];
				if (in_array($this->session->data['payment_method']['code'], $allowed_payment_methods)) {
					$this->load->model('checkout/order');
					// DONE Set default order status instead of payment_cod_order_status_id
					$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('config_order_status_id'));
					$json['redirect'] = $this->url->link('checkout/success');
				} else {
					// If payment method is not in array of allowed payment methods, then load it's view (e.g. payment form)
					$json['html'] = $this->load->controller('extension/payment/' . $this->session->data['payment_method']['code']);
				}
			// }
			
			echo(json_encode($json));
			die;
		}
	}

	// Debug only 
	// TODO Remove
	// public function fetchSessionData() {
	// 	$data = $this->session->data;
	// 	// unset($this->session->data);
	// 	echo(json_encode($data));
	// }

	// Save fields from fetch request while typing
	public function fetchSaveQuickCheckoutfields() {
		
		// Set empty values to avoid warnings
		if (!isset($this->session->data['shipping_address'])) {
			$this->session->data['shipping_address'] = [];
		}
		if (!isset($this->session->data['payment_address'])) {
			$this->session->data['payment_address'] = [];
		}
		// Set empty values to the fields that are not used in quick checkout
		$this->session->data['shipping_address']['email'] = '';
		$this->session->data['shipping_address']['company'] = '';
		$this->session->data['shipping_address']['address_2'] = '';

		$this->session->data['payment_address']['email'] = '';
		$this->session->data['payment_address']['company'] = '';
		$this->session->data['payment_address']['address_2'] = '';
		
		// Process existing customer data
		$data = $this->request->post;

		$guest_or_customer = 'guest';
		if ($this->customer->isLogged()) {
			$guest_or_customer = 'customer';
		}
		// Set defult customer group
		// TODO add condition if customer is logged in
		$this->session->data['guest']['customer_group_id'] = $this->config->get('config_customer_group_id');
		$this->session->data['shipping_address']['customer_group_id'] 		= $this->config->get('config_customer_group_id');
		$this->session->data['payment_address']['customer_group_id'] 		= $this->config->get('config_customer_group_id');
		
		// Set empty email
		// $this->session->data['guest']['email'] = '';
		
		if (isset($data['firstname'])) {
			$this->session->data['guest']['firstname'] 				= $data['firstname'];
			$this->session->data['shipping_address']['firstname'] 	= $data['firstname'];
			$this->session->data['payment_address']['firstname'] 	= $data['firstname'];
		}
		if (isset($data['lastname'])) {
			$this->session->data['guest']['lastname'] 				= $data['lastname'];
			$this->session->data['shipping_address']['lastname'] 	= $data['lastname'];
			$this->session->data['payment_address']['lastname'] 	= $data['lastname'];
		}
		if (isset($data['phone'])) {
			$this->session->data['guest']['telephone'] 				= $data['phone'];
			$this->session->data['shipping_address']['telephone'] 	= $data['phone'];
			$this->session->data['payment_address']['telephone'] 	= $data['phone'];
		}
		if (isset($data['email'])) {
			$this->session->data['guest']['email'] 				= $data['email'];
			$this->session->data['shipping_address']['email'] 	= $data['email'];
			$this->session->data['payment_address']['email'] 	= $data['email'];
		}
		if (isset($data['city'])) {
			$this->session->data['guest']['city'] 					= $data['city'];
			$this->session->data['shipping_address']['city'] 		= $data['city'];
			$this->session->data['payment_address']['city'] 		= $data['city'];
		}
		if (isset($data['address_1'])) {
			$this->session->data['guest']['address_1'] 				= $data['address_1'];
			$this->session->data['shipping_address']['address_1'] 	= $data['address_1'];
			$this->session->data['payment_address']['address_1'] 	= $data['address_1'];
		}
		if (isset($data['shipping_address']['custom_field'])) {
			$this->session->data['guest']['custom_field'] 			= $data['guest']['custom_field'];
		} else {
			$this->session->data['guest']['custom_field'] 			= [];
		}


		// Save custom fields
		if (isset($data['custom_field'])) {
			foreach ($data['custom_field'] as $key => $custom_field) {
				if ($key === 'address') {
					// $this->session->data['shipping_address']['custom_field'][$key] = $custom_field;
					$this->session->data['shipping_address']['custom_field'] = $custom_field;
				}
				if ($key === 'account') {
					// if ($this->customer->isLogged()) {
					$this->session->data['customer']['custom_field'] = $custom_field;
					// }
				}
			}
		}

		// Save address
		if (isset($data['shipping_address'])) {
			if (isset($data['shipping_address']['country_id'])) {
				$this->load->model('localisation/country');
				$country_info = $this->model_localisation_country->getCountry($data['shipping_address']['country_id']);
				// Check if country exists and selected
				if ($country_info) {
					$this->session->data['shipping_address']['country_id'] 			= $data['shipping_address']['country_id'];
					$this->session->data['shipping_address']['country'] 			= $country_info['name'];
					$this->session->data['shipping_address']['address_format']    	= $country_info['address_format'];
					
					$this->session->data['payment_address']['country_id'] 			= $data['shipping_address']['country_id'];
					$this->session->data['payment_address']['country'] 				= $country_info['name'];
					$this->session->data['payment_address']['address_format']    	= $country_info['address_format'];
				}
			}
			if (isset($data['shipping_address']['zone_id'])) {
				$this->load->model('localisation/zone');
				$zone_info = $this->model_localisation_zone->getZone($data['shipping_address']['zone_id']);

				$this->session->data['shipping_address']['zone_id'] 	= $data['shipping_address']['zone_id'];
				$this->session->data['shipping_address']['zone'] 		= $zone_info['name'];
				$this->session->data['payment_address']['zone_id'] 		= $data['shipping_address']['zone_id'];
				$this->session->data['payment_address']['zone'] 		= $zone_info['name'];
			}
		}
		if (isset($data['postcode'])) {
			$this->session->data['shipping_address']['postcode']  = $data['postcode'];
			$this->session->data['payment_address']['postcode'] = $data['postcode'];
		} else {
			$this->session->data['shipping_address']['postcode']  = '';
			$this->session->data['payment_address']['postcode']   = '';
		}
	}

	// Check if all address fields filled correctly
	public function checkErrors() {
		$json = [];
		$data = $this->session->data;
		// DONE Load corresponting language file here
		$this->load->language('common/cart');

		// Condition for logged in user
		$guest_or_customer = 'guest';
		if ($this->customer->isLogged()) {
			$guest_or_customer = 'customer';
		}

		// Array keys inside ['error'] must comply to form input names for JS handleErrors() to find right elements
		if (!isset($data[$guest_or_customer]['firstname']) || (utf8_strlen(trim($data[$guest_or_customer]['firstname'])) < 1 || utf8_strlen(trim($data[$guest_or_customer]['firstname'])) > 32)) {
			$json['error']['firstname'] = $this->language->get('error_firstname');
		}
		if (!isset($data[$guest_or_customer]['lastname']) || (utf8_strlen(trim($data[$guest_or_customer]['lastname'])) < 1 || utf8_strlen(trim($data[$guest_or_customer]['lastname'])) > 32)) {
			$json['error']['lastname'] = $this->language->get('error_lastname');
		}
		if (!isset($data[$guest_or_customer]['telephone']) || (utf8_strlen(trim($data[$guest_or_customer]['telephone'])) < 3 || utf8_strlen(trim($data[$guest_or_customer]['telephone'])) > 32)) {
			$json['error']['phone'] = $this->language->get('error_telephone');
		}
		// Condition if order needs shipping
		if ($this->cart->hasShipping()) {
			if (!isset($data['shipping_address']['city']) || (utf8_strlen(trim($data['shipping_address']['city'])) < 2 || utf8_strlen(trim($data['shipping_address']['city'])) > 128)) {
				$json['error']['city'] = $this->language->get('error_city');
			}
			if (!isset($data['shipping_address']['address_1']) || (utf8_strlen(trim($data['shipping_address']['address_1'])) < 3 || utf8_strlen(trim($data['shipping_address']['address_1'])) > 128)) {
				$json['error']['address_1'] = $this->language->get('error_address_1');
			}
			if (!isset($data['shipping_address']['country_id']) || $data['shipping_address']['country_id'] == '') {
				$json['error']['shipping_address[country_id]'] = $this->language->get('error_country');
			}
			if (isset($data['shipping_address']['country_id']))	{
	
				$this->load->model('localisation/country');
				$this->load->model('localisation/zone');
				$country_info = $this->model_localisation_country->getCountry($data['shipping_address']['country_id']);
				$country_zones = $this->model_localisation_zone->getZonesByCountryId($data['shipping_address']['country_id']);
	
				if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($data['postcode'])) < 2 || utf8_strlen(trim($data['postcode'])) > 10)) {
					$json['error']['postcode'] = $this->language->get('error_postcode');
				}
				if ($country_info && $country_zones && !empty($country_zones)) {
					if (!isset($data['shipping_address']['zone_id'])) {
						$json['error']['zone_id'] = $this->language->get('error_zone');
					}
				}
			}
			if (!isset($data['shipping_method'])) {
				$json['error']['shipping_method'] = $this->language->get('error_shipping');
			}
		}

		// DONE Check custom fields
		$this->load->model('account/custom_field');
		$custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));

		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'address') {
				// Name of custom field that corresponds input name i.e. <input name=custom_field[address][2] ...>
				$custom_field_name = 'custom_field[address]['.$custom_field['custom_field_id'].']';
				if ($custom_field['required'] && empty($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
					$json['error'][$custom_field_name] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($data['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
					$json['error'][$custom_field_name] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
				}
			}
		}
			
		if (!isset($data['payment_method'])) {
			$json['error']['payment_method'] = $this->language->get('error_payment');
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