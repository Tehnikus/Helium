<?php
class ModelCatalogCategory extends Model {
	public function getCategory($category_id) {
		$query = $this->db->query("
			SELECT  
				*,
				(SELECT 
						MIN(p.price) 
					FROM " . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "product_to_category p2c
					ON p2c.category_id = c.category_id
					WHERE p.product_id = p2c.product_id AND p.price > 0
				) AS min_price,
				
				(SELECT 
						MAX(p.price) 
					FROM " . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "product_to_category p2c
					ON p2c.category_id = c.category_id
					WHERE p.product_id = p2c.product_id
				) AS max_price,
				
				(SELECT 
						SUM(p.review_count) 
					FROM " . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "product_to_category p2c
					ON p2c.category_id = c.category_id
					WHERE p.product_id = p2c.product_id
				) AS review_count,
				
				(SELECT 
						TRUNCATE(SUM(p.rating) / SUM(p.review_count), 2)
					FROM " . DB_PREFIX . "product p 
					LEFT JOIN " . DB_PREFIX . "product_to_category p2c
					ON p2c.category_id = c.category_id
					WHERE p.product_id = p2c.product_id
					AND p.rating > 0
				) AS rating,

				(SELECT 
                 		COUNT(p2c.product_id) 
                 	FROM " . DB_PREFIX . "product_to_category p2c
                 	LEFT JOIN " . DB_PREFIX . "product p 
                 	ON p.product_id = p2c.product_id
                 	WHERE p2c.category_id = c.category_id
                 	AND p.status = 1
				) AS offer_count,

				(SELECT 
						COUNT(*) 
					FROM " . DB_PREFIX . "category_filter cf
					WHERE cf.category_id = c.category_id
				) AS filter_count,

				(SELECT 
						COUNT(*) 
					FROM " . DB_PREFIX . "product_related_wb pr
					WHERE pr.category_id = c.category_id
				) AS featured

			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd 
				ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s 
				ON (c.category_id = c2s.category_id) 
			WHERE c.category_id = '" . (int)$category_id . "' 
			AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
			AND c.status = '1'
			LIMIT 1
		");

		return $query->row;
	}

	// Выбор дочерних категорий
	public function getCategories($parent_id = 0) {
		$query = $this->db->query("
			SELECT * FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd 
			ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s 
			ON (c.category_id = c2s.category_id) 
			WHERE c.parent_id = '" . (int)$parent_id . "' 
			AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  
			AND c.status = '1' 
			ORDER BY c.sort_order, 
			LCASE(cd.name)
		");

		return $query->rows;
	}

	public function getCategoryFilters($category_id) {
		$implode = array();

		$query = $this->db->query("
			SELECT filter_id 
			FROM " . DB_PREFIX . "category_filter 
			WHERE category_id = '" . (int)$category_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("
				SELECT DISTINCT 
					f.filter_group_id, 
					fgd.name, 
					fg.sort_order, 
					fg.filter_type 
				FROM " . DB_PREFIX . "filter f 
				LEFT JOIN " . DB_PREFIX . "filter_group fg 
					ON (f.filter_group_id = fg.filter_group_id) 
				LEFT JOIN " . DB_PREFIX . "filter_group_description fgd 
					ON (fg.filter_group_id = fgd.filter_group_id) 
				WHERE f.filter_id IN (" . implode(',', $implode) . ") 
					AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				GROUP BY f.filter_group_id 
				ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("
					SELECT DISTINCT 
					f.filter_id, fd.name 
					FROM " . DB_PREFIX . "filter f 
					LEFT JOIN " . DB_PREFIX . "filter_description fd 
						ON (f.filter_id = fd.filter_id) 
					WHERE f.filter_id IN (" . implode(',', $implode) . ") 
						AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' 
						AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
					ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name'],
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter_type' 	  => $filter_group['filter_type'],
						'filter'          => $filter_data
					);
				}
			}
		}
		// print_r($filter_group_data);
		return $filter_group_data;
	}

	// Получаем список ссылок на страницы фильтров, привязанные к этой категории
	// возвращает:
	// 1. ID категории страницы фильтра по умолчанию для построения урла 
	// 2. ID фильтров, которые относятся к этому урлу
	// Затем строится ссылка так:
	// $url = $this->url->link('product/category', 'path=ID категории страницы фильтра' . '&filter=ID фильтров, которые относятся к этому урлу')
	// Например:
	// $url = $this->url->link('product/category', 'path=13' . '&filter=1,2,3')
	// См. www\system\library\seopro.php -> public function baseRewrite($data, $language_id)
	public function getFilterLinks($category_id = null, $language_id = null, $store_id = null) {
		$language_id = null ? $language_id = (int)$this->config->get('config_language_id') : $language_id;
		$store_id = null ? $store_id = (int)$this->config->get('config_store_id') : $store_id;

		$sql = "
			SELECT DISTINCT
				fpd.default_category,
				fpd.filters,
				fpd.name,
				fpd.date_modified
			FROM " . DB_PREFIX . "filter_page_description fpd ";
			if ($category_id) {
				$sql .= "
					LEFT JOIN  " . DB_PREFIX . "filter_page_to_category fp2c 
					ON fp2c.category_id = '" . (int)$category_id . "' 
					WHERE fp2c.filter_page_id = fpd.filter_page_id 
				";
			} else {
				$sql .= " WHERE 1 ";
			}
			
		$sql .= "
			AND fpd.enabled = '1'
			AND fpd.language_id = '".(int)$this->config->get('config_language_id')."'
			AND fpd.store_id = '".(int)$this->config->get('config_store_id')."'
		";
		$query = $this->db->query($sql);
		return($query->rows);
	}

	// Get filter page data by filter_page_id or filters ids
	// Arguments are null to be able to get all filter pages for sitemap
	// DONE select min_price, max_price, review_count, offer_count, rating for filter microdata
	public function getFilterPageData($filters) {

		if ($filters !== null) {
			// Clear filters from everything except integers and commas
			$filters = preg_replace('/[^\\d,]+/', '', $filters);
			// Sort in ascending order
			$filters = explode(',', $filters);
			asort($filters);
			// remove dublicates and empty values
			$f = array_unique(array_filter($filters));
			$filters = implode(',',$f);
		} else {
			return false;
		}

		$language_id = (int)$this->config->get('config_language_id');
		$store_id 	 = (int)$this->config->get('config_store_id');

		$sql = "
			SELECT 
				fpd.filter_page_id,
				fpd.name,
				fpd.description,
				fpd.meta_title,
				fpd.meta_description,
				fpd.meta_keyword,
				fpd.default_category,
				
				MIN(p.price) AS min_price,
				MAX(p.price) AS max_price,
				COUNT(DISTINCT(pf.product_id)) AS offer_count,
				SUM(DISTINCT(p.review_count)) AS review_count,
				(SUM(DISTINCT(p.rating)) / SUM(DISTINCT(p.review_count))) AS rating
			
			FROM " . DB_PREFIX . "product p 
			
			LEFT JOIN " . DB_PREFIX . "product_filter pf ON p.product_id = pf.product_id
			LEFT JOIN " . DB_PREFIX . "filter_page_description fpd ON fpd.filters = '".$filters."'
			WHERE p.product_id = pf.product_id 
				AND pf.filter_id IN(".$filters.")
				AND fpd.language_id = ".$language_id."
				AND fpd.store_id = ".$store_id."
				AND fpd.enabled = 1
			LIMIT 1
		";

		$query = $this->db->query($sql);

		// check if data exists
		// because MySQL MIN(), MAX(), COUNT() return NULL instead of empty result
		if ($query->row['filter_page_id'] !== null) {
			return ($query->row);
		} else {
			return false;
		}
	}

	public function getCategoryLayoutId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_to_layout WHERE category_id = '" . (int)$category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}

	// Faster way to check if category exists
	public function categoryExists($category_id) {
		$query = $this->db->query("
			SELECT EXISTS(
				SELECT 1 
				FROM " . DB_PREFIX . "category c 
				LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id)
				WHERE c.category_id ='". $category_id ."' 
				AND c.status = '1' 
				AND c2s.store_id = '".$this->config->get('config_store_id')."'
				LIMIT 1
			) AS category_exists
		");

		return (bool)$query->row['category_exists'];
	}
	// Faster way to check if category exists
	public function filterPageExists($filters) {
		$filters = preg_replace('/[^\\d,]+/', '', $filters);
		$filters = explode(',', $filters);
		foreach ($filters as $key => $a) {
			$filters[$key] = $this->db->escape($a);
		}
		asort($filters);
		$f = array_unique(array_filter($filters));

		$filters_string = implode(',',$f);

		$query = $this->db->query("
			SELECT EXISTS(
				SELECT 1 
				FROM " . DB_PREFIX . "filter_page_description fpd 
				
				WHERE fpd.filters = '".$filters_string."'
				AND fpd.enabled = '1' 
				AND fpd.store_id = '".$this->config->get('config_store_id')."'
				LIMIT 1
			) AS filter_exists
		");
		return (bool)$query->row['filter_exists'];
	}
}