<?php
class ModelLocalisationLocation extends Model {
	public function getLocation($location_id, $language_id = null) {
		if ($language_id == null) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("
			SELECT 
				l.location_id, 
				ld.name, 
				ld.address, 
				ld.geocode, 
				ld.telephone, 
				ld.fax, 
				l.image, 
				ld.open, 
				ld.map, 
				ld.comment 
			FROM " . DB_PREFIX . "location l
			LEFT JOIN " . DB_PREFIX . "location_description ld
			ON l.location_id = ld.location_id 
				AND ld.language_id = '". $language_id ."'
			WHERE l.location_id = '" . (int)$location_id . "'");

		return $query->row;
	}

	public function getLocationsByStore($store_id) {
		$query = "
			SELECT 
				* 
			FROM `oc_location`
			WHERE `store_id` = ".$store_id."
			AND `status` = '1'
			ORDER BY `sort_order` ASC;
		";
		$this->db->query($query);
	}
}