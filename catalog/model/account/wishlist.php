<?php
class ModelAccountWishlist extends Model {
	public function addWishlist($product_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id = '" . (int)$this->customer->getId() . "' AND product_id = '" . (int)$product_id . "'");

		$this->db->query("INSERT INTO " . DB_PREFIX . "customer_wishlist SET customer_id = '" . (int)$this->customer->getId() . "', product_id = '" . (int)$product_id . "', date_added = NOW()");
	}

	public function deleteWishlist($product_id) {
		$this->db->query("
			DELETE FROM " . DB_PREFIX . "customer_wishlist 
			WHERE customer_id = '" . (int)$this->customer->getId() . "' 
			AND product_id = '" . (int)$product_id . "'
		");
	}

	public function getWishlist() {
		if ($this->customer->isLogged()){
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id = '" . (int)$this->customer->getId() . "'");
			return $query->rows;
		} else {
			if (!isset ($this->session->data['wishlist'])) {
				$this->session->data['wishlist'] = array();
			}
			return $this->session->data['wishlist'];
		}

	}

	public function getTotalWishlist() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "customer_wishlist WHERE customer_id = '" . (int)$this->customer->getId() . "'");

		return $query->row['total'];
	}
}
