<?php
class ControllerExtensionModuleSpecial extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/special');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['products'] = array();

		$filter_data = array(
			'sort'  => 'pd.name',
			'order' => 'ASC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$special_products = $this->model_catalog_product->getProductSpecials($filter_data);
		if ($special_products) {
			$product_list = [];
			foreach ($special_products as $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id['product_id']);
			}
			$data['products'] = $this->model_catalog_product->prepareProductList($product_list, null);
			return $this->load->view('extension/module/special', $data);
		}
	}
}