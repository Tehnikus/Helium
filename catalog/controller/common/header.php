<?php


class ControllerCommonHeader extends Controller {
	public function index() {


		// Can not be cached
		// /////////////////

		// Analytics
		$this->load->model('setting/extension');
		$this->load->model('tool/image');

		$data['analytics'] = array();

		$analytics = $this->model_setting_extension->getExtensions('analytics');

		foreach ($analytics as $analytic) {
			if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
				$data['analytics'][] = $this->load->controller('extension/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
			}
		}

		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
		}

		$this->load->language('common/header');
		$data['title'] = $this->document->getTitle();
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['robots'] = $this->document->getRobots();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');
		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
		// Og Image
		$host = isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1')) ? HTTPS_SERVER : HTTP_SERVER;
		if ($this->request->server['REQUEST_URI'] == '/') {
			$data['og_url'] = $this->url->link('common/home');
		} else {
			$data['og_url'] = $host . substr($this->request->server['REQUEST_URI'], 1, (strlen($this->request->server['REQUEST_URI'])-1));
		}

		$data['og_image'] = $this->document->getOgImage();
		$data['logged'] = $this->customer->isLogged();
		$data['cart'] = $this->load->controller('common/cart');

		// if (!isset($this->session->data['compare'])) {
		// 	$this->session->data['compare'] = array();
		// }
		// if (!isset($this->session->data['viewed'])) {
		// 	$this->session->data['viewed'] = array();
		// }
		// if (!isset($this->session->data['wishlist'])) {
		// 	$this->session->data['wishlist'] = array();
		// }

		$data['language'] = 	 $this->load->controller('common/language');
		$data['currency'] = 	 $this->load->controller('common/currency');
		if ($this->language->get('code') !== false) {
			$data['lang'] = $this->language->get('code');
		}
		else {
			$data['lang'] = $this->config->get('config_language');
		}
		// Compare
		if (isset($this->session->data['compare']) && count($this->session->data['compare']) > 0) {
			$data['text_compare_count'] = sprintf($this->language->get('text_compare_count'), count($this->session->data['compare']));
		} else {
			$data['text_compare_count'] = $this->language->get('text_compare');
		}
		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');
			if ($this->model_account_wishlist->getTotalWishlist() !== null && $this->model_account_wishlist->getTotalWishlist() > 0) {
				$data['text_wishlist_count'] = sprintf($this->language->get('text_wishlist_count'), $this->model_account_wishlist->getTotalWishlist());
			} else {
				$data['text_wishlist_count'] = $this->language->get('text_wishlist');
			}
		} else {
			if (isset($this->session->data['wishlist']) && count($this->session->data['wishlist']) > 0) {
				$data['text_wishlist_count'] = sprintf($this->language->get('text_wishlist_count'), count($this->session->data['wishlist']));
			} else {
				$data['text_wishlist_count'] = $this->language->get('text_wishlist');
			}
		}
		$cache_data = $this->getCachedData();
		$all_data = array_merge($data,$cache_data);
		return $this->load->view('common/header', $all_data);
	}

	public function getCachedData() {
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}
		$cache_name = 'header.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id');

		if ($this->config->get('theme_'.$this->config->get('config_theme').'_header_caching')) {
			$data = $this->cache->get($cache_name);
		} else {
			$data = false;
		}
		
		if (!$data) {
			$data = array();

			$data['direction'] = $this->language->get('direction');
			$data['logo_height'] = $this->config->get('theme_'.$this->config->get('config_theme').'_image_logo_height');
			$data['logo_width'] = $this->config->get('theme_'.$this->config->get('config_theme').'_image_logo_width');
			$data['name'] = $this->config->get('config_name');
			$data['base'] = $server;
			$data['home'] = 		 $this->url->link('common/home');
			$data['wishlist'] = 	 $this->url->link('account/wishlist', '', true);
			$data['compare'] = 		 $this->url->link('product/compare');
			$data['text_wishlist'] = $this->language->get('text_wishlist');
			$data['text_compare'] =  $this->language->get('text_compare');
			$data['account'] = 		 $this->url->link('account/account', '', true);
			$data['register'] = 	 $this->url->link('account/register', '', true);
			$data['login'] = 		 $this->url->link('account/login', '', true);
			$data['order'] = 		 $this->url->link('account/order', '', true);
			$data['transaction'] = 	 $this->url->link('account/transaction', '', true);
			$data['download'] = 	 $this->url->link('account/download', '', true);
			$data['logout'] = 		 $this->url->link('account/logout', '', true);
			$data['shopping_cart'] = $this->url->link('checkout/cart');
			$data['checkout'] = 	 $this->url->link('checkout/checkout', '', true);
			$data['contact'] = 		 $this->url->link('information/contact');
			$data['telephone'] = 	 $this->config->get('config_telephone');
			
			if ($this->config->get('configblog_blog_menu')) {
				$data['blog_menu'] = $this->load->controller('blog/menu');
			} else {
				$data['blog_menu'] = '';
			}
			$data['search'] = $this->load->controller('common/search');
			$data['menu'] = $this->load->controller('common/menu');



			if (is_file(DIR_IMAGE . $this->config->get('config_logo'))) {
				$logo_file = $this->config->get('config_logo');
				$logo = $this->model_tool_image->resize($logo_file, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_logo_height'));
				$data['logo'] = $logo;
			} else {
				$data['logo'] = '';
			}
			if ($this->config->get('theme_'.$this->config->get('config_theme').'_header_caching')) {
				$this->cache->set($cache_name, $data);
			}
		}
		return $data;
	}
}
