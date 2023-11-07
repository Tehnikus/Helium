<?php


class ControllerAccountReturn extends Controller {
	private $error = array();

	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/return', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/return');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$url = '';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return', $url, true)
		);

		$this->load->model('account/return');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['returns'] = array();

		$return_total = $this->model_account_return->getTotalReturns();

		$results = $this->model_account_return->getReturns(($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['returns'][] = array(
				'return_id'  => $result['return_id'],
				'order_id'   => $result['order_id'],
				'name'       => $result['firstname'] . ' ' . $result['lastname'],
				'status'     => $result['status'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'href'       => $this->url->link('account/return/info', 'return_id=' . $result['return_id'] . $url, true)
			);
		}

		$pagination = new Pagination();
		$pagination->total = $return_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		$pagination->url = $this->url->link('account/return', 'page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($return_total) ? (($page - 1) * $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')) + 1 : 0, ((($page - 1) * $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')) > ($return_total - $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'))) ? $return_total : ((($page - 1) * $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')) + $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')), $return_total, ceil($return_total / $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit')));

		$data['continue'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/return_list', $data));
	}

	public function info() {
		$this->load->language('account/return');

		if (isset($this->request->get['return_id'])) {
			$return_id = $this->request->get['return_id'];
		} else {
			$return_id = 0;
		}

		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/return/info', 'return_id=' . $return_id, true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->model('account/return');

		$return_info = $this->model_account_return->getReturn($return_id);

		if ($return_info) {
			$this->document->setTitle($this->language->get('text_return'));

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', '', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true)
			);

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/return', $url, true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_return'),
				'href' => $this->url->link('account/return/info', 'return_id=' . $this->request->get['return_id'] . $url, true)
			);

			$data['return_id']    = $return_info['return_id'];
			$data['order_id']     = $return_info['order_id'];
			// $data['date_ordered'] = date($this->language->get('date_format_short'), strtotime($return_info['date_ordered']));
			$data['date_added']   = date($this->language->get('date_format_short'), strtotime($return_info['date_added']));
			$data['firstname']    = $return_info['firstname'];
			$data['lastname']     = $return_info['lastname'];
			$data['email']        = $return_info['email'];
			$data['telephone']    = $return_info['telephone'];
			$data['product']      = $return_info['product'];
			$data['model']        = $return_info['model'];
			$data['quantity']     = $return_info['quantity'];
			$data['reason']       = $return_info['reason'];
			$data['opened']       = $return_info['opened'] ? $this->language->get('text_yes') : $this->language->get('text_no');
			$data['comment']      = nl2br($return_info['comment']);
			$data['action']       = $return_info['action'];

			$data['histories'] = array();

			$results = $this->model_account_return->getReturnHistories($this->request->get['return_id']);

			foreach ($results as $result) {
				$data['histories'][] = array(
					'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'status'     => $result['status'],
					'comment'    => nl2br($result['comment'])
				);
			}

			$data['continue'] = $this->url->link('account/return', $url, true);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('account/return_info', $data));
		} else {
			$this->document->setTitle($this->language->get('text_return'));

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', '', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/return', '', true)
			);

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_return'),
				'href' => $this->url->link('account/return/info', 'return_id=' . $return_id . $url, true)
			);

			$data['continue'] = $this->url->link('account/return', '', true);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function add() {

		
		// Load neccessary models and language 
		$this->load->language('account/return');
		$this->load->model('account/return');
		$this->load->model('account/order');
		$this->load->model('catalog/product');
		
		// Set title and robots
		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');

		// Empty data
		$data = [];

		// Breadcrumbs
		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return/add', '', true)
		);

		// Customer orders list
		// Create pretty array $orders[$order_id] = $order;
		// Format date and price according to user language settings
		$temp_orders = $this->model_account_order->getOrders();
		$orders = [];
		foreach ($temp_orders as $temp_order) {
			$orders[$temp_order['order_id']] 							 				= $temp_order;
			$orders[$temp_order['order_id']]['date_added_format'] = date($this->language->get('date_format_short'), strtotime($temp_order['date_added']));
			$orders[$temp_order['order_id']]['total']      				= $this->currency->format($temp_order['total'], $temp_order['currency_code'], $temp_order['currency_value']);
			if ((isset($this->request->get['order_id']) && $this->request->get['order_id'] == $temp_order['order_id']) || (isset($this->request->post['order_id']) && $this->request->post['order_id'] == $temp_order['order_id'])) {
				$orders[$temp_order['order_id']]['selected'] = true;
			}
		}
		$data['orders'] = $orders;

		// Selected order_id in orders list in <select>
		if (isset($this->request->get['order_id'])) {
			$data['order_id'] = (int)$this->request->get['order_id'];
		} elseif (isset($this->request->post['order_id'])) {
			$data['order_id'] = (int)$this->request->post['order_id'];
		} elseif (isset($orders) && !empty($orders)) {
			$last_order = end($orders);
			$data['order_id'] = $last_order['order_id'];
		} else {
			$data['order_id'] = '';
		}

		// List of products in order
		if (isset($data['order_id']) && $data['order_id'] !== '') {
			$temp_products = $this->model_account_order->getOrderProducts((int)$data['order_id']);
			foreach ($temp_products as $temp_product ) {
				$data['products'][$temp_product['product_id']] = $temp_product;
			}
			// if (isset($this->request->post['product_id'])) {
			// 	foreach ($data['products'] as $product) {
			// 		if ($product['product_id'] == (int)$this->request->post['product_id']) {
			// 			// set maximum return qty in form
			// 			$data['maximum'] = $product['quantity'];
			// 		}
			// 	}
			// }
		}

		// If form is submitted and valid redirect to success page
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			// DONE clean post values from unsafe data
			$return_data = [];
			$return_data['order_id']  				  = (int)$this->request->post['order_id'];
			$return_data['product_id']  				= (int)$this->request->post['product_id'];
			$return_data['firstname'] 					= $orders[(int)$this->request->post['order_id']]['firstname'];
			$return_data['lastname']  					= $orders[(int)$this->request->post['order_id']]['lastname'];
			$return_data['email']  					    = (string)$this->request->post['email'];
			$return_data['telephone']  					= (string)$this->request->post['telephone'];
			// Product name
			$return_data['product'] = $data['products'][(int)$this->request->post['product_id']]['name'];
			$return_data['model'] = $data['products'][(int)$this->request->post['product_id']]['model'];
			// Model
			$return_data['quantity']    				= (int)$this->request->post['quantity'];
			$return_data['opened']    					= (int)$this->request->post['opened'];
			$return_data['return_reason_id']    = (int)$this->request->post['return_reason_id'];
			$return_data['comment']    					= (string)$this->request->post['comment'];
			// Date ordered
			$return_data['date_ordered'] = $data['orders'][(int)$this->request->post['order_id']]['date_added'];
			
			// print_r($return_data);

			$this->model_account_return->addReturn($return_data);
			$this->response->redirect($this->url->link('account/return/success', '', true));
		}

		// Errors
		// Order ID
		if (isset($this->error['order_id'])) {
			$data['error_order_id'] = $this->error['order_id'];
		} else {
			$data['error_order_id'] = false;
		}
		// Product
		if (isset($this->error['product'])) {
			$data['error_product'] = $this->error['product'];
		} else {
			$data['error_product'] = false;
		}
		// Email
		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = false;
		}
		// Phone
		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = false;
		}
		// Return reason radiobuttons
		if (isset($this->error['reason'])) {
			$data['error_reason'] = $this->error['reason'];
		} else {
			$data['error_reason'] = false;
		}
		// User description of product return reason
		if (isset($this->error['comment'])) {
			$data['error_comment'] = $this->error['comment'];
		} else {
			$data['error_comment'] = false;
		}
		// User description of product return reason
		if (isset($this->error['quantity'])) {
			$data['error_quantity'] = $this->error['quantity'];
		} else {
			$data['error_quantity'] = false;
		}
		// Any warnings
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = false;
		}

		// Submit button action
		$data['action'] = $this->url->link('account/return/add', '', true);

		
		// if (isset($this->request->get['order_id'])) {
		// 	$order_info = $this->model_account_order->getOrder($this->request->get['order_id']);
		// }

		

		// if (isset($this->request->get['r_product_id'])) {
		// 	$product_info = $this->model_catalog_product->getProduct($this->request->get['r_product_id']);
		// }

		// if (isset($this->request->post['order_id'])) {
		// 	$data['order_id'] = $this->request->post['order_id'];
		// } elseif (!empty($order_info)) {
		// 	$data['order_id'] = $order_info['order_id'];
		// } else {
		// 	$data['order_id'] = '';
		// }

		// if (isset($this->request->post['r_product_id'])) {
		// 	$data['product_id'] = $this->request->post['r_product_id'];
		// } elseif (!empty($product_info)) {
		// 	$data['product_id'] = $product_info['product_id'];
		// } else {
		// 	$data['product_id'] = '';
		// }

		// if (isset($this->request->post['date_ordered'])) {
		// 	$data['date_ordered'] = $this->request->post['date_ordered'];
		// } elseif (!empty($order_info)) {
		// 	$data['date_ordered'] = date('Y-m-d', strtotime($order_info['date_added']));
		// } else {
		// 	$data['date_ordered'] = '';
		// }

		// if (isset($this->request->post['firstname'])) {
		// 	$data['firstname'] = $this->request->post['firstname'];
		// } elseif (!empty($order_info)) {
		// 	$data['firstname'] = $order_info['firstname'];
		// } else {
		// 	$data['firstname'] = $this->customer->getFirstName();
		// }

		// if (isset($this->request->post['lastname'])) {
		// 	$data['lastname'] = $this->request->post['lastname'];
		// } elseif (!empty($order_info)) {
		// 	$data['lastname'] = $order_info['lastname'];
		// } else {
		// 	$data['lastname'] = $this->customer->getLastName();
		// }



		// if (isset($this->request->post['product'])) {
		// 	$data['product'] = $this->request->post['product'];
		// } elseif (!empty($product_info)) {
		// 	$data['product'] = $product_info['name'];
		// } else {
		// 	$data['product'] = '';
		// }


		// User submitted data
		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($order_info)) {
			$data['email'] = $order_info['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} elseif (!empty($order_info)) {
			$data['telephone'] = $order_info['telephone'];
		} else {
			$data['telephone'] = $this->customer->getTelephone();
		}

		if (isset($this->request->post['model'])) {
			$data['model'] = $this->request->post['model'];
		} elseif (!empty($product_info)) {
			$data['model'] = $product_info['model'];
		} else {
			$data['model'] = '';
		}

		if (isset($this->request->post['quantity'])) {
			$data['quantity'] = $this->request->post['quantity'];
		} else {
			$data['quantity'] = 1;
		}

		if (isset($this->request->post['opened'])) {
			$data['opened'] = $this->request->post['opened'];
		} else {
			$data['opened'] = false;
		}

		if (isset($this->request->post['return_reason_id'])) {
			$data['return_reason_id'] = $this->request->post['return_reason_id'];
		} else {
			$data['return_reason_id'] = '';
		}

		$this->load->model('localisation/return_reason');

		$data['return_reasons'] = $this->model_localisation_return_reason->getReturnReasons();

		if (isset($this->request->post['comment'])) {
			$data['comment'] = $this->request->post['comment'];
		} else {
			$data['comment'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('return', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('config_return_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_return_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_return_id'), true), $information_info['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		$data['back'] = $this->url->link('account/account', '', true);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/return_form', $data));
	}

	protected function validate() {
		if (!$this->request->post['order_id']) {
			$this->error['order_id'] = $this->language->get('error_order_id');
		}

		if (!$this->request->post['product_id']) {
			$this->error['product_id'] = $this->language->get('error_product');
		}

		if ((utf8_strlen($this->request->post['comment']) < 10) || (utf8_strlen($this->request->post['comment']) > 500)) {
			$this->error['comment'] = $this->language->get('error_comment');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		if (empty($this->request->post['return_reason_id'])) {
			$this->error['reason'] = $this->language->get('error_reason');
		}

		// Check if returned quantity is lower or equals that in product
		if (isset($this->request->post['order_id']) && isset($this->request->post['product_id'])) {

			$temp_products = $this->model_account_order->getOrderProducts((int)$this->request->post['order_id']);
			foreach ($temp_products as $temp_product) {
				if ($temp_product['product_id'] == (int)$this->request->post['product_id']) {
					if ($temp_product['quantity'] < (int)$this->request->post['quantity']) {
						$this->error['quantity'] = sprintf($this->language->get('error_quantity'), $temp_product['quantity']);
					}
				}
			}
		}

		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('return', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		if ($this->config->get('config_return_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_return_id'));

			if ($information_info && !isset($this->request->post['agree'])) {
				$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
			}
		}

		return !$this->error;
	}

	public function success() {
		$this->load->language('account/return');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->setRobots('noindex,nofollow');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/return', '', true)
		);

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}

	// Get product list in order by (int)order_id
	public function getOrderProductList (int $order_id) {
		$data = [];

		// check if posted variable is correct and customer is logged
		if (!isset($order_id) || !$this->customer->isLogged()) {
			return false;
		}
		
		// Check if order belongs to this customer
		$this->load->model('account/order');
		$temp_orders = $this->model_account_order->getOrders(0, 200);
		$orders = [];
		foreach ($temp_orders as $temp_order) {
			$orders[$temp_order['order_id']] = $temp_order;
		}
		if (!array_key_exists((int)$order_id, $orders)) {
			return false;
		}

		$this->load->model('account/order');
		$data['products'] = $this->model_account_order->getOrderProducts((int)$this->request->post['order_id']);
		return $data;
	}

	// Fetch order product list by JS
	public function fetchGetOrderProductsList() {
		$json = $this->getOrderProductList((int)$this->request->post['order_id']);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
