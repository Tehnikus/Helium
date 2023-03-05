<?php
class ModelCatalogFilterPages extends Model {

	public function getStores() {
		// Список магазинов
		// Магазин по умолчанию
		$this->load->model('setting/store');
		$stores = array();
		$stores[0] = array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
			'url'      => $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG,
		);
		// Остальные магазины
		$stores_raw = $this->model_setting_store->getStores();
		foreach ($stores_raw as $store) {
			$stores[$store['store_id']] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name'],
				'url'      => $store['url'],
			);
		}
		return $stores;
	}

	public function addFilterPage($data) {
		// print_r($data);
		// return;

		// Id следующей страницы фильтра
		$last_id = $this->db->query("
			SELECT filter_page_id FROM " . DB_PREFIX . "filter_page_description ORDER BY filter_page_id DESC LIMIT 1
		");
		if ($last_id->num_rows) {
			$last_id = ($last_id->rows[0]['filter_page_id']);
			$last_id = (int)$last_id + 1;
		} else {
			$last_id = '1';
		}

		foreach ($data['filter_page_description']['lang'] as $language_id => $lang_value) {
			foreach ($data['filter_page_seo_url'] as $store_id => $store_value) {
				$filters = explode(",", $data['filter_page_description']['filters']);
				// Удаляем пустые значения
				$filters = array_filter($filters, 'strlen');
				$filters = array_unique($filters);
				// Сортируем фильтры, чтобы ID  всегда шли в порядке возрастания
				asort($filters);

				$data['filter_page_description']['filters'] = implode(",", $filters);
				
				// Создаем SEO URL
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "seo_url 
					SET 
						query = 'filter=" . $data['filter_page_description']['filters'] . "', 
						keyword = '" . $store_value[$language_id] . "',
						language_id = '" . $language_id . "',
						store_id = '" . $store_id . "'
				");
				// Получаем ID этого урла
				$seo_url_id = $this->db->getLastId();
				// Записываем остальную информацию в БД
				$this->db->query("
					INSERT INTO " . DB_PREFIX . "filter_page_description 
					SET 
						name = '" . $this->db->escape($lang_value['name']) . "', 
						meta_title = '" . $this->db->escape($lang_value['meta_title']) . "', 
						filters = '" . $data['filter_page_description']['filters'] . "',
						description = '" . $this->db->escape($lang_value['description']) . "',
						meta_description = '" . $this->db->escape($lang_value['meta_description']) . "',
						meta_keyword = '" . $this->db->escape($lang_value['meta_keyword']) . "',
						language_id = '" . $language_id . "',
						seo_url_id = '".$seo_url_id."',
						default_category = '" . $data['filter_page_description']['default_category'] . "',
						filter_page_id = '".$last_id."',
						store_id = '" . $store_id . "'
				");
			}
		}
		return true;
	}

	// Готово
	// Добавить список категорий, в которых будут отображаться ссылки под фильтром на статичные страницы
	// 
	public function editFilterPage($filter_page_id, $post_data) {		
		$page_data = $this->getFilterPage($filter_page_id);

		foreach ($post_data['filter_page_description']['lang'] as $language_id => $value) {
			foreach ($post_data['filter_page_seo_url'] as $store_id => $store_value) {
				$filters = explode(",", $post_data['filter_page_description']['filters']);
				// Удаляем пустые значения
				$filters = array_filter($filters, 'strlen');
				$filters = array_unique($filters);
				// Сортируем фильтры, чтобы ID  всегда шли в порядке возрастания
				asort($filters);

				$post_data['filter_page_description']['filters'] = implode(",", $filters);

				$this->db->query("
					UPDATE " . DB_PREFIX . "filter_page_description 
					SET 
						name = '" . $this->db->escape($value['name']) . "', 
						description = '" . $this->db->escape(trim($value['description'])) . "', 
						meta_title = '" . $this->db->escape(trim($value['meta_title'])) . "', 
						meta_description = '" . $this->db->escape(trim($value['meta_description'])) . "', 
						meta_keyword = '" . $this->db->escape(trim($value['meta_keyword'])) . "', 

						default_category = '" . $this->db->escape(trim($post_data['filter_page_description']['default_category'])) . "', 
						filters = '" . $this->db->escape(trim($post_data['filter_page_description']['filters'])) . "'
					WHERE language_id = '". (int)$language_id ."' 
					AND store_id = '".$store_id."' 
					AND filter_page_id = '".(int)$page_data['filter_page_id']."'
				");
				$filter_page_description_last_id = $this->db->getLastId();
				if (isset($page_data['filter_page_seo_url'][$store_id][$language_id]['seo_url_id'])) {
					// print_r($post_data['filter_page_seo_url'][$store_id][$language_id]);
					$this->db->query("
						UPDATE " . DB_PREFIX . "seo_url 
						SET 
							query = 'filter=".trim($this->db->escape($post_data['filter_page_description']['filters']))."', 
							keyword = '".$this->db->escape($post_data['filter_page_seo_url'][$store_id][$language_id])."' 
						WHERE language_id = '". $language_id ."' 
						AND store_id = '".$store_id."' 
						AND seo_url_id = '". (int)$page_data['filter_page_seo_url'][$store_id][$language_id]['seo_url_id'] ."'
					");
				} else {
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "seo_url 
						VALUES 
							query = 'filter=".trim($this->db->escape($post_data['filter_page_description']['filters']))."', 
							keyword = '".trim($this->db->escape($post_data['filter_page_seo_url'][$store_id][$language_id]))."', 
							language_id = '" . $language_id . "',
							store_id = '".$store_id."', 
					");
					$seo_url_id = $this->db->getLastId();
					$this->db->query("
						INSERT INTO " . DB_PREFIX . "filter_page_description 
						VALUES
							seo_url_id = '". $seo_url_id ."'
						WHERE 
							filter_page_description_last_id = '".$filter_page_description_last_id."'
					");
				}
			}
		}
	}

	// Добавить удаление урлов из таблицы seo_url
	public function deleteFilterPage($filter_page_id) {
		$seo_url_id = $this->db->query("
			SELECT seo_url_id FROM `" . DB_PREFIX . "filter_page_description` 
			WHERE filter_page_id = '" . (int)$filter_page_id . "'
		");
		if ($seo_url_id->num_rows) {
			$seo_urls = $seo_url_id->rows;
			foreach ($seo_urls as $seo_url) {
				$this->db->query("
					DELETE FROM `" . DB_PREFIX . "seo_url` 
					WHERE seo_url_id = '" . $seo_url['seo_url_id'] . "'
				");
			}
		}
		$this->db->query("
			DELETE FROM `" . DB_PREFIX . "filter_page_description` 
			WHERE filter_page_id = '" . (int)$filter_page_id . "'
		");
		
	}

	// Готово
	// Получаем данные из БД для редактирования
	public function getFilterPage($filter_page_id) {
		$filter_pages_data = array();
		$stores = $this->getStores();
		$query = $this->db->query("
			SELECT 
				*, 
				(SELECT su.keyword FROM " . DB_PREFIX . "seo_url su WHERE su.seo_url_id = fpd.seo_url_id) AS `seo_url`
			FROM `" . DB_PREFIX . "filter_page_description` fpd 
			WHERE fpd.filter_page_id = '" . (int)$filter_page_id . "' 
			AND fpd.store_id = '" . (int)$this->config->get('config_store_id') . "'
		");

		foreach ($query->rows as $result) {
			// Неязыковые данные
			$filter_pages_data['filter_page_description_id'] = $result['filter_page_description_id'];
			$filter_pages_data['filter_page_id'] 			 = $result['filter_page_id'];
			$filter_pages_data['default_category']			 = $result['default_category'];
			$filter_pages_data['filters']      			 	 = $result['filters'];
			// Языковые данные
			$filter_pages_data['lang'][$result['language_id']] = array(
				'language_id' 			 	 => $result['language_id'],
				'name'             			 => $result['name'],
				'meta_title'       			 => $result['meta_title'],
				'meta_description' 			 => $result['meta_description'],
				'meta_keyword'     			 => $result['meta_keyword'],
				'description'      			 => $result['description'],
			);
			// Данные для разных магазинов и разных языков
			foreach ($stores as $store_id => $store) {
				$filter_pages_data['filter_page_seo_url'][$store_id][$result['language_id']] = array(
					'seo_url_id'      			 => $result['seo_url_id'],
					'seo_url'      			 	 => $result['seo_url'],
				);
			}
		}
		// print_r($filter_pages_data);
		return $filter_pages_data;
	}

	// Готово
	// Получаем список страниц фильтров
	public function getFilterPages($data = array()) {
		$sql = "
			SELECT 
				-- Данные страницы
				*,
				-- Урл фильтра
				(SELECT su.keyword FROM " . DB_PREFIX . "seo_url su WHERE su.seo_url_id = fpd.seo_url_id) AS `seo_url`,
				-- Названия фильтров
				(SELECT 
					GROUP_CONCAT(fd.name SEPARATOR ', ') 
					FROM " . DB_PREFIX . "filter_description fd 
					WHERE FIND_IN_SET(fd.filter_id, fpd.filters)
					AND fd.language_id = '".(int)$this->config->get('config_language_id')."'
				) AS `filter_names`,
				-- Подсчет количества товаров на странице фильтра с учетом основной категории фильтра
				(SELECT 
					COUNT(distinct pf.product_id) 
					FROM oc_product_filter pf 
					INNER JOIN oc_product_to_category p2c 
					ON pf.product_id = p2c.product_id 
					WHERE FIND_IN_SET(pf.filter_id, fpd.filters)
					AND p2c.category_id = fpd.default_category 
				) AS `product_count`
			FROM `" . DB_PREFIX . "filter_page_description` fpd
			WHERE fpd.language_id = '" . (int)$this->config->get('config_language_id') . "'
		";

		
		$sort_data = array(
			'fpd.name',
			'fpd.date_modified',
			'fpd.store_id',
			'filter_names',
			'seo_url',
			'product_count'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY fpd.date_modified ";
		}

		if (isset($data['order']) && ($data['order'] == 'ASC')) {
			$sql .= " ASC";
		} else {
			$sql .= " DESC";
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

	// Это испрользуется для пагинации
	public function getTotalFilterPages() {
		$query = $this->db->query("
			SELECT 
				COUNT(*) AS total 
			FROM `" . DB_PREFIX . "filter_page_description`
			WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'
			");

		return $query->row['total'];
	}


	// Это используется только для autocomplete
	// public function getFilters($data) {
	// 	$sql = "
	// 		SELECT *, 
	// 		(SELECT name FROM " . DB_PREFIX . "filter_group_description fgd WHERE f.filter_group_id = fgd.filter_group_id AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS `group` 
	// 		FROM " . DB_PREFIX . "filter f 
	// 		LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) 
	// 		WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "'
	// 	";

	// 	if (!empty($data['filter_name'])) {
	// 		$sql .= " AND fd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
	// 	}

	// 	$sql .= " ORDER BY f.sort_order ASC";

	// 	if (isset($data['start']) || isset($data['limit'])) {
	// 		if ($data['start'] < 0) {
	// 			$data['start'] = 0;
	// 		}

	// 		if ($data['limit'] < 1) {
	// 			$data['limit'] = 20;
	// 		}

	// 		$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
	// 	}

	// 	$query = $this->db->query($sql);

	// 	return $query->rows;
	// }



}
