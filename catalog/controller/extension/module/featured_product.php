<?php


class ControllerExtensionModuleFeaturedProduct extends Controller {
	public function index($setting) {		
		if (!$setting['limit']) {
			$setting['limit'] = 4;
		}
		$this->load->language('extension/module/featured_product');
		$this->load->model('catalog/product');
		$this->load->model('catalog/cms');

		$results = array();
		if (isset($this->request->get['manufacturer_id'])) {
			$filter_data = array(
				'manufacturer_id'  => $this->request->get['manufacturer_id'],
				'limit' => $setting['limit']
			);
			$results = $this->model_catalog_cms->getProductRelatedByManufacturer($filter_data);
		} else {
			$parts = explode('_', (string)$this->request->get['path']);
			if(!empty($parts) && is_array($parts)) {
				$filter_data = array(
					'category_id'  => array_pop($parts),
					'limit' => $setting['limit']
				);
				$results = $this->model_catalog_cms->getProductRelatedByCategory($filter_data);
			}
		}
		
		$data['products'] = array();
		if (!empty($results)) {
			$data['products'] = $this->model_catalog_product->prepareProductList($results, null);
		}
		
		return $this->load->view('extension/module/featured_product', $data);
	}
	
}