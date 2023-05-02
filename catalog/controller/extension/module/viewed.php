<?php
class ControllerExtensionModuleViewed extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/viewed');
		$this->load->model('catalog/product');

		$products = array();

		if (!$setting['limit']) {
			$setting['limit'] = 10;
		}

		if (isset($this->session->data['viewed'])) {
			$products = $this->session->data['viewed'];
			foreach ($products as $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id);
			}
			$data['products'] = $this->model_catalog_product->prepareProductList($product_list, null);
		}
		

		if (!empty($products)) {
			return $this->load->view('extension/module/viewed', $data);
		}
	}
}