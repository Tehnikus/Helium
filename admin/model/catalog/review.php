<?php


class ModelCatalogReview extends Model {
	public function addReview($data) {
		$this->db->query("
			INSERT INTO " . DB_PREFIX . "review 
			SET 
			language_id = '". (int)$this->config->get('config_language_id') ."',
			author = '" . $this->db->escape($data['author']) . "', 
			product_id = '" . (int)$data['product_id'] . "', 
			text = '" . $this->db->escape(strip_tags($data['text'])) . "', 
			rating = '" . (int)$data['rating'] . "', 
			status = '" . (int)$data['status'] . "', 
			date_added = '" . $data['date_added'] ? $this->db->escape($data['date_added']) : ''. "'
		");

		$this->db->query("
			UPDATE " . DB_PREFIX . "product 
			SET 
				rating = rating + " . (int)$data['rating'] . ",
				review_count = review_count + 1
			WHERE product_id = '" . (int)$data['product_id'] . "'
		");

		$review_id = $this->db->getLastId();

		$this->cache->delete('product');

		return $review_id;
	}

	public function editReview($review_id, $data) {

		$old_review = $this->db->query("
			SELECT 
				* 
			FROM " . DB_PREFIX . "review 
			WHERE review_id = '".$review_id."'
		");

		(int)$old_rating = $old_review->rows['0']['rating'];
		(int)$new_rating = $data['rating'];

		$this->db->query("
			UPDATE " . DB_PREFIX . "review 
			SET 
				author = '" . $this->db->escape($data['author']) . "', 
				product_id = '" . (int)$data['product_id'] . "', 
				text = '" . $this->db->escape(strip_tags($data['text'])) . "', 
				rating = '" . (int)$data['rating'] . "', 
				status = '" . (int)$data['status'] . "', 
				date_added = '" . $this->db->escape($data['date_added']) . "', 
				date_modified = NOW() 
			WHERE review_id = '" . (int)$review_id . "'");
		$this->db->query("
			UPDATE " . DB_PREFIX . "product 
			SET 
				rating = rating + " . $new_rating . " - ". $old_rating ."
			WHERE product_id = '" . (int)$data['product_id'] . "'
		");
		$this->cache->delete('product');
	}

	public function deleteReview($review_id) {

		// Get review data
		$old_review = $this->db->query("
			SELECT 
				* 
			FROM " . DB_PREFIX . "review 
			WHERE review_id = '".$review_id."'
		");

		(int)$old_rating = $old_review->rows['0']['rating'];
		(int)$product_id = $old_review->rows['0']['product_id'];

		// Update overall rating of the product
		$this->db->query("
			UPDATE " . DB_PREFIX . "product 
			SET 
				rating = rating - '". $old_rating ."',
				review_count = review_count - 1
			WHERE product_id = '" . $product_id . "'
		");

		$this->db->query("
			DELETE FROM " . DB_PREFIX . "review 
			WHERE review_id = '" . (int)$review_id . "'
		");

		$this->cache->delete('product');
	}

	public function getReview($review_id) {
		$query = $this->db->query("
			SELECT DISTINCT 
				*, 
				(SELECT 
						pd.name 
					FROM " . DB_PREFIX . "product_description pd 
					WHERE pd.product_id = r.product_id 
					AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
				) AS product 
			FROM " . DB_PREFIX . "review r 
			WHERE r.review_id = '" . (int)$review_id . "'");

		return $query->row;
	}

	public function getReviews($data = array()) {
		$sql = "
			SELECT 
				r.review_id, pd.name, r.author, r.rating, r.status, r.date_added 
			FROM " . DB_PREFIX . "review r 
			LEFT JOIN " . DB_PREFIX . "product p 
				ON (r.product_id = p.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_description pd 
				ON (r.product_id = pd.product_id) 
			WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'
			";

		if (!empty($data['filter_product'])) {
			$sql .= " 
				AND 
				(pd.name LIKE '%" . $this->db->escape($data['filter_product']) . "%' 
					OR p.model LIKE '%" . $this->db->escape($data['filter_product']) . "%'
				) 
			";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '%" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(r.date_added) >= DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		$sort_data = array(
			'pd.name',
			'r.author',
			'r.rating',
			'r.status',
			'r.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.date_added";
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

	public function getTotalReviews($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product_description pd ON (r.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_product'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_product']) . "%'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalReviewsAwaitingApproval() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review WHERE status = '0'");

		return $query->row['total'];
	}
}