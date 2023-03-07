<?php
class ModelLocalisationLocation extends Model {
	public function addLocation($data) {

		$this->db->query("
			INSERT INTO " . DB_PREFIX . "location 
			SET 
				store_id 	= '" . $this->db->escape($data['location']['store_id']) . "', 
				image 		= '" . $this->db->escape($data['location']['image']) . "', 
				status 		= '" . $this->db->escape($data['location']['status']) . "',
				sort_order 	= '" . $this->db->escape($data['location']['status']) . "'
		");

		$location_last_id = $this->db->getLastId();
		// Language data
		foreach ($data['location_description'] as $language_id => $value) {
			$this->db->query("
				INSERT INTO ".DB_PREFIX."location_description 
					SET
					location_id = '".(int)$location_last_id."',
					language_id = '" . (int)$language_id . "', 
					name 		= '" . $this->db->escape($value['name']) . "', 
					address 	= '" . $this->db->escape($value['address']) . "', 
					geocode 	= '" . $this->db->escape($value['geocode']) . "', 
					telephone 	= '" . $this->db->escape($value['telephone']) . "', 
					fax 		= '" . $this->db->escape($value['fax']) . "', 
					open 		= '" . $this->db->escape($value['open']) . "', 
					map 		= '" . $this->db->escape($value['map']) . "',
					comment 	= '" . $this->db->escape($value['comment']) . "'
			");
		}

		return $location_last_id;
	}

	public function editLocation($location_id, $data) {

		$this->db->query("
			UPDATE " . DB_PREFIX . "location 
			SET 
				store_id 	= '" . $this->db->escape($data['location']['store_id']) . "', 
				image 		= '" . $this->db->escape($data['location']['image']) . "', 
				status 		= '" . $this->db->escape($data['location']['status']) . "',
				sort_order 	= '" . $this->db->escape($data['location']['sort_order']) . "'
			WHERE 
				location_id = '" . (int)$location_id . "'
		");


		// Language data
		// Why delete, if we can update?
		foreach ($data['location_description'] as $language_id => $value) {
			$query = $this->db->query("
				SELECT * FROM " . DB_PREFIX . "location_description 
				WHERE location_id = '" . (int)$location_id . "' 
				AND language_id = '" . (int)$language_id . "'
			");
			if ($query->rows) {
				// Update, if entry exist
				$this->db->query("
					UPDATE ".DB_PREFIX."location_description 
					SET
						name		 	= '" . $this->db->escape($value['name']) . "', 
						address 	 	= '" . $this->db->escape($value['address']) . "', 
						geocode 	 	= '" . $this->db->escape($value['geocode']) . "', 
						telephone 		= '" . $this->db->escape($value['telephone']) . "', 
						fax 			= '" . $this->db->escape($value['fax']) . "', 
						open 			= '" . $this->db->escape($value['open']) . "', 
						map 			= '" . $this->db->escape($value['map']) . "',
						comment 		= '" . $this->db->escape($value['comment']) . "'
					WHERE
						location_id 	= '" . (int)$location_id."' AND 
						language_id 	= '" . (int)$language_id . "'
				");
			} else {
				// Insert if entry does not exist
				$this->db->query("
					INSERT INTO ".DB_PREFIX."location_description 
					SET
						name		 	= '" . $this->db->escape($value['name']) . "', 
						address 	 	= '" . $this->db->escape($value['address']) . "', 
						geocode 	 	= '" . $this->db->escape($value['geocode']) . "', 
						telephone 		= '" . $this->db->escape($value['telephone']) . "', 
						fax 			= '" . $this->db->escape($value['fax']) . "', 
						open 			= '" . $this->db->escape($value['open']) . "', 
						map 			= '" . $this->db->escape($value['map']) . "',
						comment 		= '" . $this->db->escape($value['comment']) . "',
						location_id 	= '" . (int)$location_id."', 
						language_id 	= '" . (int)$language_id . "'
				");
			}
		}
	}

	public function deleteLocation($location_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "location WHERE location_id = " . (int)$location_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "location_description WHERE location_id = " . (int)$location_id);
	}

	public function getLocation($location_id) {
		$query = $this->db->query("
			SELECT 
				* 
			FROM " . DB_PREFIX . "location 
			WHERE location_id = '" . (int)$location_id . "'");
		return $query->row;
	}

	public function getLocationDescriptions($location_id) {
		$location_description_data = array();
		$query = $this->db->query("
			SELECT 
				* 
			FROM " . DB_PREFIX . "location_description 
			WHERE location_id = '" . (int)$location_id . "'
		");

		foreach ($query->rows as $result) {
			$location_description_data[$result['language_id']] = array(
				'location_id'   => $result['location_id'],
				'language_id'   => $result['language_id'],
				'name'          => $result['name'],
				'address'       => $result['address'],
				'telephone'     => $result['telephone'],
				'fax'           => $result['fax'],
				'geocode'       => $result['geocode'],
				'open'          => $result['open'],
				'map'           => $result['map'],
				'comment'		=> $result['comment']
			);
		}

		return $location_description_data;
	}

	public function getLocations($data = array()) {
		$sql = "
			SELECT 
				*
			FROM " . DB_PREFIX . "location l
			LEFT JOIN " . DB_PREFIX . "location_description ld 
			ON ld.location_id = l.location_id
			WHERE ld.language_id = '".$this->config->get('config_language_id')."'
		";

		$sort_data = array(
			'ld.name',
			// 'address',
			'l.sort_order',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY l.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalLocations() {
		$query = $this->db->query("
			SELECT 
				COUNT(*) AS total 
			FROM " . DB_PREFIX . "location
		");
		return $query->row['total'];
	}
}
