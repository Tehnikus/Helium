<?php
class ControllerExtensionModuleViewed extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/viewed');
		$this->load->model('catalog/product');

		$products = array();

		if (!$setting['limit']) {
			$setting['limit'] = 10;
		}

		if (isset($this->session->data['viewed']) && !empty($this->session->data['viewed'])) {
			$products = array_reverse($this->session->data['viewed'], false);
			$products = array_splice($products, (int)$setting['limit']);
			foreach ($products as $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id);
			}
			$data['products'] = $this->model_catalog_product->prepareProductList($product_list, null);
			return $this->load->view('extension/module/viewed', $data);
		}
	}
}