<?php
class ModelLocalisationCountry extends Model {
	public function getCountry($country_id) {
		$query = $this->db->query("
			SELECT 
				c.*,
				(SELECT 
					COUNT(z.zone_id) 
				FROM " . DB_PREFIX . "zone z 
				WHERE z.country_id = " . (int)$country_id . " 
				AND z.status = '1') as zones
			FROM " . DB_PREFIX . "country c 
			WHERE c.status = 1 AND c.country_id = " . (int)$country_id . ";
		");

		return $query->row;
	}

	public function getCountries() {
		$country_data = $this->cache->get('country.catalog');

		if (!$country_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE status = '1' ORDER BY name ASC");

			$country_data = $query->rows;

			$this->cache->set('country.catalog', $country_data);
		}

		return $country_data;
	}
}