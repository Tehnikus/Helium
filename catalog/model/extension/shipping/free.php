<?php
class ModelExtensionShippingFree extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/free');

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
			WHERE geo_zone_id = '" . (int)$this->config->get('shipping_free_geo_zone_id') . "' 
			AND country_id = '" . $country_id . "' 
			AND (zone_id = '" . $zone_id . "' OR zone_id = '0')
		");

		if (!$this->config->get('shipping_free_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		if ($this->cart->getSubTotal() < $this->config->get('shipping_free_total')) {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$quote_data = array();

			$quote_data['free'] = array(
				'code'         => 'free.free',
				'title'        => $this->language->get('text_description'),
				'cost'         => 0.00,
				'tax_class_id' => 0,
				'text'         => $this->currency->format(0.00, $this->session->data['currency'])
			);

			$method_data = array(
				'code'       => 'free',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_free_sort_order'),
				'error'      => false
			);
		} else {
			$quote_data = array();

			$quote_data['free'] = array(
				'code'         => 'free.free',
				'title'        => $this->language->get('text_description'),
				'cost'         => 0.00,
				'tax_class_id' => 0,
				'text'         => sprintf($this->language->get('spend_for_free_shipping'), $this->currency->format(($this->config->get('shipping_free_total') - $this->cart->getSubTotal()), $this->session->data['currency'])),
				'disabled'     => true
			);

			$method_data = array(
				'code'       => 'free',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_free_sort_order'),
				'error'      => false,
				'disabled'   => true
			);
		}

		return $method_data;
	}
}