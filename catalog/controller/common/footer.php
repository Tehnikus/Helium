<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');
		$cache_name = 'footer.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id');
		$data = $this->cache->get($cache_name);

		if (!$data) {
			$data = $this->renderFooter();
			$this->cache->set($cache_name, $data);
		}

		return $this->load->view('common/footer', $data);
	}

	public function renderFooter() {

		$cache_name = 'footer.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id');

		$data = $this->cache->get($cache_name);
		if (!$data) {

			$this->load->model('catalog/information');
			$data = array();
			$data['informations'] = array();

			foreach ($this->model_catalog_information->getInformations() as $result) {
				if ($result['bottom']) {
					$data['informations'][] = array(
						'title' => $result['title'],
						'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
					);
				}
			}

			$data['contact'] = $this->url->link('information/contact');
			$data['return'] = $this->url->link('account/return/add', '', true);
			$data['sitemap'] = $this->url->link('information/sitemap');
			$data['tracking'] = $this->url->link('information/tracking');
			$data['manufacturer'] = $this->url->link('product/manufacturer');
			$data['voucher'] = $this->url->link('account/voucher', '', true);
			$data['affiliate'] = $this->url->link('affiliate/login', '', true);
			$data['special'] = $this->url->link('product/special');
			$data['account'] = $this->url->link('account/account', '', true);
			$data['order'] = $this->url->link('account/order', '', true);
			$data['wishlist'] = $this->url->link('account/wishlist', '', true);
			$data['newsletter'] = $this->url->link('account/newsletter', '', true);
			$data['powered'] = $this->config->get('config_name').' - '.date('Y', time());
			$data['scripts'] = $this->document->getScripts('footer');
			$data['styles'] = $this->document->getStyles('footer');

			$this->cache->set($cache_name, $data);
		}
			
		return $data;
	}
}
