<?php
class ControllerExtensionModuleFeatured extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featured');
		$this->load->model('catalog/product');

		$products = array();

		if (!$setting['limit']) {
			$setting['limit'] = 10;
		}

		if (!empty($setting['product'])) {
			$products = array_slice($setting['product'], 0, (int)$setting['limit']);
			$product_list = [];
			foreach ($products as $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id);
			}
			$data['products'] = $this->model_catalog_product->prepareProductList($product_list, null);
		}

		if (!empty($products)) {
			return $this->load->view('extension/module/featured', $data);
		}
	}
}