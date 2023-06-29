<?php
class ModelExtensionPaymentCOD extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/cod');

		if (isset($address)) {
			if (isset($address['country_id'])) {
				$country_id = (int)$address['country_id'];
			} else {
				$country_id = (int)$this->config->get('config_country_id');
			}
			if (isset($address['zone_id'])) {
				$zone_id = (int)$address['zone_id'];
			} else {
				$zone_id = (int)$this->config->get('config_zone_id');
			}
		}

		$query = $this->db->query("
			SELECT 
				* 
			FROM " . DB_PREFIX . "zone_to_geo_zone 
			WHERE 
				geo_zone_id = '" . (int)$this->config->get('payment_cod_geo_zone_id') . "' 
				AND country_id = '" . $country_id . "' OR country_id = '".$this->config->get('config_country_id')."' 
				AND (zone_id = '" . $zone_id . "' OR zone_id = '0')
		");

		if ($this->config->get('payment_cod_total') > 0 && $this->config->get('payment_cod_total') > $total) {
			$status = false;
		} elseif (!$this->cart->hasShipping()) {
			$status = false;
		} elseif (!$this->config->get('payment_cod_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'cod',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payment_cod_sort_order')
			);
		}

		return $method_data;
	}
}
