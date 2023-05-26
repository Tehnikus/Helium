<?php


/**
 * Summary of ModelCatalogProduct
 */
class ModelCatalogProduct extends Model {

	public $allowed_sort_data = array(
		'optgroup_default' => array(
			'ASC' => array(
				'p.sort_order',
			)
		),
		'optgroup_popular' => array(
			'ASC' => array(
				'p.returned',
			),
			'DESC' => array(
				'p.sold',
				'p.viewed',
				'p.date_added',
				'p.date_modified',
				'p.rating',
				'p.points',
				'p.quantity',
				'discounts',
			),
		),
		'optgroup_price' => array(
			'ASC' => array(
				'p.price',
				'price_to_weight',
			),
			'DESC' => array(
				'p.price',
				'price_to_weight',
			)
		),

		'optgroup_name' => array(
			'ASC' => array(
				'pd.name',
				'p.model',
			),
			'DESC' => array(
				'pd.name',
				'p.model',
			),
		),
		'optgroup_weight' => array(
			'ASC' => array(
				'p.weight',
			),
			'DESC' => array(
				'p.weight',
			),
		)
	);

	public function getSorts() {
		return $this->allowed_sort_data;
	}

	// Update product views
	public function updateViewed(int $product_id) {
		$this->db->query("
			UPDATE " . DB_PREFIX . "product 
			SET 
				viewed = (viewed + 1) 
			WHERE product_id = '" . (int)$product_id . "'
		");
	}

	// DONE Add viewed products
	// Add viewed product_id to session
	// return void
	public function addViewedProduct(int $product_id) {
		if (!isset($this->session->data['viewed'])) {
			$this->session->data['viewed'] = array();
		}
		if (!in_array((int)$product_id, $this->session->data['viewed'])) {
			if (count($this->session->data['viewed']) >= 10) {
				array_shift($this->session->data['viewed']);
			}
			$this->session->data['viewed'][] = (int)$product_id;
		}
	}

	// TODO Здесь и везде по коду переделать $product_id в массив, а в SQL запросе сделать WHERE IN()
	// DONE Добавить информацию о списке желаний
	// DONE Добавить информацию о просмотренных товарах
	// DONE Добавить информацию о сравнении товарах
	// DONE Split to different functions so caching control would be easier
	// DONE Move flags render to separate function so it is available on any page where needed

	// Query single product from DB
	// @product_id = int
	// return array() || false
	// Used only in $this->getProduct($product_id)
	public function renderProduct($product_id) {
		if (is_array($product_id)) {
			$product_id = implode(',', $product_id);
		}
		$sql = ("
			SELECT DISTINCT 
				p.*, 
				pd.*,
				m.name AS manufacturer, 
				(SELECT 
					price 
					FROM " . DB_PREFIX . "product_discount pd2 
					WHERE pd2.product_id = p.product_id 
					AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND pd2.quantity = '1' 
					AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) 
					AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) 
					ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) 
				AS discount, 

				(SELECT 
					price 
					FROM " . DB_PREFIX . "product_special ps 
					WHERE ps.product_id = p.product_id 
					AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
					AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
					ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
				AS special, 

				(SELECT 
					price 
					FROM " . DB_PREFIX . "product_discount ps 
					WHERE ps.product_id = p.product_id 
					AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
					AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
					ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
				AS discount, 
					
				(SELECT 
						date_end 
					FROM " . DB_PREFIX . "product_special ps 
					WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
						AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
					ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
				AS special_date_end,

				(SELECT 
						date_end 
					FROM " . DB_PREFIX . "product_discount ps 
					WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
						AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
					ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
				AS discount_date_end,

				(SELECT 
						quantity 
					FROM " . DB_PREFIX . "product_discount ps 
					WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
					AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
					AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
					ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
				AS discount_quantity,

				(SELECT 
					points 
					FROM " . DB_PREFIX . "product_reward pr 
					WHERE pr.product_id = p.product_id 
					AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') 
				AS reward, 
				
				(SELECT 
					ss.name 
					FROM " . DB_PREFIX . "stock_status ss 
					WHERE ss.stock_status_id = p.stock_status_id 
					AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') 
				AS stock_status, 
				
				(SELECT 
					wcd.unit 
					FROM " . DB_PREFIX . "weight_class_description wcd 
					WHERE p.weight_class_id = wcd.weight_class_id 
					AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') 
				AS weight_class, 
				
				(SELECT 
					lcd.unit 
					FROM " . DB_PREFIX . "length_class_description lcd 
					WHERE p.length_class_id = lcd.length_class_id 
					AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') 
				AS length_class, 
				
				(SELECT 
					AVG(rating) 
					AS total 
					FROM " . DB_PREFIX . "review r1 
					WHERE r1.product_id = p.product_id 
					AND r1.status = '1' 
					GROUP BY r1.product_id) 
				AS rating, 
					
				(SELECT 
					COUNT(*) 
					AS total 
					FROM " . DB_PREFIX . "review r2 
					WHERE r2.product_id = p.product_id 
					AND r2.status = '1' 
					GROUP BY r2.product_id) 
				AS reviews
			
			FROM " . DB_PREFIX . "product p 
				
			LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON p.product_id = p2s.product_id
			LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id) 
			WHERE p.product_id = '" . (int)$product_id . "' 
			AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND p.status = '1' 
			-- AND p.date_available <= NOW() 
			AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
		");

		$query = $this->db->query($sql);
	
		if ($query->num_rows) {
			$product =  array(
				'product_id'        	=> $query->row['product_id'],
				'main_category'     	=> $query->row['main_category'],
				'name'              	=> $query->row['name'],
				'description'       	=> $query->row['description'],
				'meta_title'        	=> $query->row['meta_title'],
				'noindex'           	=> $query->row['noindex'],
				'meta_h1'	        	=> $query->row['meta_h1'],
				'meta_description'  	=> $query->row['meta_description'],
				'meta_keyword'      	=> $query->row['meta_keyword'],
				'tag'               	=> $query->row['tag'],
				'model'             	=> $query->row['model'],
				'sku'               	=> $query->row['sku'],
				'upc'               	=> $query->row['upc'],
				'ean'               	=> $query->row['ean'],
				'jan'               	=> $query->row['jan'],
				'isbn'              	=> $query->row['isbn'],
				'mpn'               	=> $query->row['mpn'],
				'location'          	=> $query->row['location'],
				'quantity'          	=> $query->row['quantity'],
				'stock_status'      	=> $query->row['stock_status'],
				'image'             	=> $query->row['image'],
				'manufacturer_id'   	=> $query->row['manufacturer_id'],
				'manufacturer'      	=> $query->row['manufacturer'],
				'price'             	=> $query->row['price'],
				'discount'          	=> $query->row['discount'],
				'special'           	=> $query->row['special'],
				'special_date_end'  	=> $query->row['special_date_end'] !== '0000-00-00' ? $query->row['special_date_end'] : false,
				'discount_date_end' 	=> $query->row['discount_date_end']!== '0000-00-00' ? $query->row['discount_date_end'] : false,
				'discount_quantity' 	=> $query->row['discount_quantity'],
				'reward'            	=> $query->row['reward'],
				'points'            	=> $query->row['points'],
				'tax_class_id'      	=> $query->row['tax_class_id'],
				'date_available'    	=> $query->row['date_available'],
				'weight'            	=> $query->row['weight'],
				'weight_class_id'   	=> $query->row['weight_class_id'],
				'length'            	=> $query->row['length'],
				'width'             	=> $query->row['width'],
				'height'            	=> $query->row['height'],
				'length_class_id'   	=> $query->row['length_class_id'],
				'subtract'          	=> $query->row['subtract'],
				'rating'            	=> ($query->row['rating'] !== null) ? round((float)$query->row['rating'], 2) : false,
				'reviews'           	=> $query->row['reviews'] ? $query->row['reviews'] : 0,
				'sold'              	=> $query->row['sold'],
				'returned'          	=> $query->row['returned'],
				'minimum'           	=> $query->row['minimum'],
				'sort_order'        	=> $query->row['sort_order'],
				'status'            	=> $query->row['status'],
				'date_added'        	=> $query->row['date_added'],
				'date_modified'     	=> $query->row['date_modified'],
				'viewed'            	=> $query->row['viewed'],
				// 'product_flags'	    	=> $product_flags
			);

			return $product;

		} else {
			return false;
		}
	}

	// Get product data and cache if needed
	// $product_id = int
	// return array()
	// Returns the same as $this->renderProduct($product_id) only refers to cache if needed
	// TODO Add chacing setting in backoffice
	public function getProduct(int $product_id) {
		// if ($this->config->get('cache_products')) {
		if (true) {
			$cache_name = 'product.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id').'.'.(int)$product_id;
			$product = $this->cache->get($cache_name);
			if ($product) {
				return $product; 
			} else {
				$product = $this->renderProduct($product_id);
				if ($product) {
					$this->cache->set($cache_name, $product);
					return $product;
				} else {
					return false;
				}
			}
		} else {
			$product = $this->renderProduct($product_id);
			return $product;
		}
	}

	// Render product flags
	// @product - single product array with all it's data: prices, specials etc.
	// @category_id - the category, where to look static data - best rewiews or bestsellers
	// If it is not the category scope, uses $main_category of product
	// return array() with translated flags types
	public function renderFlags($product, $category_id = null)
	{
		// Dynamic user interaction part - viewed, compared, wishlisted
		$product_flags = array();
		$product_flags_data = array();
		$bestsellers = array();
		$bestreviews = array();
		// Set session to avoid crashes on srict PHP 8.1 rules
		if (!isset($this->session->data['compare'])) {
			$this->session->data['compare'] = array();
		}
		if (!isset($this->session->data['viewed'])) {
			$this->session->data['viewed'] = array();
		}
		if (!isset($this->session->data['wishlist'])) {
			$this->session->data['wishlist'] = array();
		}
		// Load wishlist model
		$this->load->model('account/wishlist');
		$user_data = array(
			'wishlist' =>  $this->model_account_wishlist->getWishlist(),
			'compare' =>  $this->session->data['compare'],
			'viewed' =>  $this->session->data['viewed'],
		);
		// Check if product_id is in any array
		foreach ($user_data as $type => $data) {
			$key = array_search($product['product_id'], $data);
			$product_flags[$type] = is_numeric($key) ? true : false;
		}

		// Static part - special prices, sales, bestsellers by category, etc.

		// Special - when product has lowered price
		if (!is_null($product['special']) && (float)$product['special'] >= 0) {
			$product_flags['special'] = true;
		}
		// Discount - when there is the discount for several items (also shown in flag) 
		if (!is_null($product['discount']) && (float)$product['discount'] >= 0) {
			$product_flags['discount'] = true;
		}
		// Sale - when any price has end date
		if ($product['discount_date_end'] || $product['special_date_end']) {
			$product_flags['sale'] = true;
		}
		// video - when description contains embedded video
		if (str_contains($product['description'], 'youtube.com/embed/')) {
			$product_flags['video'] = true;
		}

		// New product
		// TODO make configurable
		$end_date = strtotime('+1000 day', strtotime($product['date_added']));
		if ($end_date > time()) {
			$product_flags['new'] = true;
		}

		$bestsellers = $this->getBestSellers($category_id);
		$bestreviews = $this->getBestReviews($category_id);

		$product_flags['bestseller'] = false;
		$product_flags['bestreviews'] = false;
		// Bestsellers
		foreach ($bestsellers as $bestseller) {
			if ($bestseller == $product['product_id']) {
				$product_flags['bestseller'] = true;
			}
		}
		// Best rewievs
		foreach ($bestreviews as $bestreview) {
			if ($bestreview == $product['product_id']) {
				$product_flags['bestreviews'] = true;
			}
		}

		// Add translation
		foreach ($product_flags as $type => $flag) {
			if ($flag == true) {
				if ($type == 'discount') {
					$product_flags_data[$type] = sprintf($this->language->get('flag_'.$type), $product['discount_quantity']);
				} else {
					$product_flags_data[$type] = $this->language->get('flag_'.$type);
				}
			}
		}

		return $product_flags_data;
	}


	// Prepare all product data to display
	// @products = array() with products data that was queried before
	// @category_scope = (int)$category_id, used to determine scope of bestsellers and bestreviews - if set, by category_id or if not set, by default product category
	// return array() with all product data ready to display
	// 
	public function prepareProductList(array $products, int $category_scope = null) {
		$this->load->language('product/product');
		$this->load->model('tool/image');
		// $products = $this->getProducts($filter);

		$data = array();
		if ($products !== null) {
			foreach ($products as $product) {
				if ($product['image']) {
					$image = $this->model_tool_image->resize($product['image'], $this->config->get('image_product_width'), $this->config->get('image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('image_product_width'), $this->config->get('image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if (!is_null($product['special']) && (float)$product['special'] >= 0) {
					$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$product['special'];
				} else {
					$special = false;
					$tax_price = (float)$product['price'];
				}

				if (!is_null($product['discount']) && (float)$product['discount'] >= 0) {
					$discount = $this->currency->format($this->tax->calculate($product['discount'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					$tax_price = (float)$product['discount'];
				} else {
					$discount = false;
					$tax_price = (float)$product['price'];
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format($tax_price, $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (float)$product['rating'];
				} else {
					$rating = false;
				}

				// Cut product description at the end of the word respecting UTF-8
				$product_description = trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8')));
				if (mb_strlen($product_description) > $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) {
					$product_description_short = utf8_substr($product_description, 0, mb_strpos($product_description, ' ', $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')));
					$product_description_short .= '...';
				} else {
					$product_description_short = $product_description;
				}
				
				// Product flags
				$product_flags = array();
				if ($category_scope == null) {
					$category_scope = $product['main_category'];
				}
				$product_flags = $this->renderFlags($product, $category_scope);

				$data[] = array(
					'product_id'  			=> $product['product_id'],
					'model'  				=> $product['model'],
					'ean'  					=> $product['ean'],
					'name'       		    => $product['name'],
					'description'           => $product_description_short,
					'thumb'       			=> $image,
					'width'		            => $this->config->get('image_product_width'),
					'height'	            => $this->config->get('image_product_height'),
					'price'       			=> $price,
					'price_value'			=> $product['price'],
					'special'     			=> $special,
					'discount'     			=> $discount,
					'special_date_end'     	=> $product['special_date_end'],
					'discount_date_end'     => $product['discount_date_end'],
					'discount_quantity'     => $product['discount_quantity'],
					'tax'         			=> $tax,
					'minimum'     			=> $product['minimum'] > 0 ? $product['minimum'] : 1,
					'rating'      			=> $rating,
					'reviews'               => $product['reviews'],
					'href'                  => $this->url->link('product/product', 'path=' . $product['main_category'] . '&product_id=' . $product['product_id']),
					'product_flags'			=> $product_flags,
				);
			}
		}
		return $data;
	}

	// Get product ids according to filter
	// return array() of product ids
	public function getProducts($data = array()) 
	{
		// DONE Добавить дату начала и окончания скидки
		$sql = "
		SELECT
			p.product_id,
			(p.price / NULLIF(p.weight, 0)) as price_to_weight,
			(SELECT 
				price 
				FROM " . DB_PREFIX . "product_discount pd2 
				WHERE pd2.product_id = p.product_id 
				AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
				-- AND pd2.quantity = '1' 
				AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) 
				AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) 
				ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) 
			AS discount, 
			(SELECT 
				price 
				FROM " . DB_PREFIX . "product_special ps 
				WHERE ps.product_id = p.product_id 
				AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
				AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
				AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
				ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) 
			AS special 
		";
		
		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " 
					FROM " . DB_PREFIX . "category_path cp 
					LEFT JOIN " . DB_PREFIX . "product_to_category p2c 
					ON (cp.category_id = p2c.category_id) 
				";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c ";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " 
					LEFT JOIN " . DB_PREFIX . "product_filter pf 
					ON (p2c.product_id = pf.product_id) 
					LEFT JOIN " . DB_PREFIX . "product p 
					ON (pf.product_id = p.product_id) 
				";
			} else {
				$sql .= " 
					LEFT JOIN " . DB_PREFIX . "product p 
					ON (p2c.product_id = p.product_id) 
				";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " 
			LEFT JOIN " . DB_PREFIX . "product_description pd 
			ON (p.product_id = pd.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
			WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND p.status = '1' 
			AND p.date_available <= NOW() 
			AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
		";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
		    }

		    if (!empty($data['filter_filter'])) {
                $implode = array();
                $filters = explode(',', $data['filter_filter']);
                foreach ($filters as $filter_id) {
                    $implode[] = (int)$filter_id;
                }
				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
		    }
        }

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= "
			 	AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'
			";
		}

		$sql .= " GROUP BY p.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
			'discounts',
			'p.rating',
			'p.sort_order',
			'p.date_added',
			'p.date_modified',
			'p.viewed',
			'p.sold',
			'p.returned',
			'p.points',
			'p.weight',
			'price_to_weight'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} elseif ($data['sort'] == 'p.price') {
				// DONE Sort including special and discount prices
				// DONE Fix ordering when price is null
				$sql .= " 
					ORDER BY p.price = 0, (
						CASE 
						WHEN special IS NOT NULL 
						THEN special 
						WHEN discount IS NOT NULL 
						THEN discount 
						ELSE p.price 
					END)
				";
			} elseif ($data['sort'] == 'discounts') {
				$sql .= " 
					ORDER BY special IS NULL, discount IS NULL, special, discount
				";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);
			

		foreach ($query->rows as $result) {
			$product_ids[] = $result['product_id'];
			$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
		}

		// print_r($product_ids);

		return $product_data;
	}

	public function getBestReviews($category_id)
	{
		$limit = (int)$this->config->get('best_reviews_limit');
		if (!$limit) {
			(int)$limit = '15';
		}

		$best_rewievs = [];
		$sql = "
			SELECT DISTINCT
				p.product_id
			FROM oc_product p 
			LEFT JOIN oc_product_to_category p2c 
				ON p.product_id = p2c.product_id 
			WHERE 
			p.rating > 4 AND 
			(p2c.category_id = '".$category_id."' OR p.main_category = '".$category_id."')

			ORDER by p.rating DESC
			LIMIT ".$limit.";
		";
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			$best_rewievs[] = $row['product_id'];
		}
		return $best_rewievs;
	}
	public function getBestSellers($category_id)
	{
		$limit = (int)$this->config->get('bestsellers_limit');
		if (!$limit) {
			(int)$limit = '15';
		}
		
		$bestsellers = [];
		$sql = "
			SELECT DISTINCT
				p.product_id
			FROM oc_product p 
			LEFT JOIN oc_product_to_category p2c 
				ON p.product_id = p2c.product_id 
			WHERE 
				(p2c.category_id = '".$category_id."' OR p.main_category = '".$category_id."')
				AND p.sold > 10
			ORDER by p.sold DESC
			LIMIT ".$limit.";
		";
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			$bestsellers[] = $row['product_id'];
		}
		return $bestsellers;
	}

	public function getMostViewed($category_id)
	{
		$limit = (int)$this->config->get('most_viewed_limit');
		if (!$limit) {
			(int)$limit = '15';
		}

		$most_viewed = [];
		$sql = "
			SELECT DISTINCT
				p.product_id
			FROM oc_product p 
			LEFT JOIN oc_product_to_category p2c 
				ON p.product_id = p2c.product_id 
			WHERE 
				(p2c.category_id = '".$category_id."' OR p.main_category = '".$category_id."')
				AND p.viewed > 100
			ORDER by p.viewed DESC
			LIMIT ".$limit.";
		";
		$query = $this->db->query($sql);
		foreach ($query->rows as $row) {
			$most_viewed[] = $row['product_id'];
		}
		return $most_viewed;
	}

	public function getProductSpecials($data = array()) {
		$sql = "
			SELECT DISTINCT 
			ps.product_id, 
			(SELECT AVG(rating) 
				FROM " . DB_PREFIX . "review r1 
				WHERE r1.product_id = ps.product_id 
				AND r1.status = '1' 
				GROUP BY r1.product_id) 
			AS rating 
			FROM " . DB_PREFIX . "product_special ps 
			LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
			WHERE p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
				AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
				AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
				AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) 
			GROUP BY ps.product_id";

		$sort_data = array(
			'pd.name',
			'p.model',
			'ps.price',
			'rating',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			if ($data['sort'] == 'pd.name' || $data['sort'] == 'p.model') {
				$sql .= " ORDER BY LCASE(" . $data['sort'] . ")";
			} else {
				$sql .= " ORDER BY " . $data['sort'];
			}
		} else {
			$sql .= " ORDER BY p.sort_order";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC, LCASE(pd.name) DESC";
		} else {
			$sql .= " ASC, LCASE(pd.name) ASC";
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

		$product_data = array();

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getLatestProducts($limit) {
		$product_data = $this->cache->get('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$query = $this->db->query("SELECT p.product_id FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY p.date_added DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.latest.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getPopularProducts($limit) {
		$product_data = $this->cache->get('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);
	
		if (!$product_data) {
			$query = $this->db->query("
				SELECT p.product_id 
				FROM " . DB_PREFIX . "product p 
				LEFT JOIN " . DB_PREFIX . "product_to_store p2s 
				ON (p.product_id = p2s.product_id) 
				WHERE p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
				ORDER BY p.viewed DESC, p.date_added DESC LIMIT " . (int)$limit);
	
			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}
			
			$this->cache->set('product.popular.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}
		
		return $product_data;
	}

	public function getBestSellerProducts($limit) {
		$product_data = $this->cache->get('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit);

		if (!$product_data) {
			$product_data = array();

			$query = $this->db->query("
			SELECT 
				op.product_id, 
				SUM(op.quantity) AS total 
			FROM " . DB_PREFIX . "order_product op 
			LEFT JOIN `" . DB_PREFIX . "order` o 
				ON (op.order_id = o.order_id) 
			LEFT JOIN `" . DB_PREFIX . "product` p 
				ON (op.product_id = p.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s 
				ON (p.product_id = p2s.product_id) 
			WHERE o.order_status_id > '0' 
				AND p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
			GROUP BY op.product_id ORDER BY total DESC LIMIT " . (int)$limit);

			foreach ($query->rows as $result) {
				$product_data[$result['product_id']] = $this->getProduct($result['product_id']);
			}

			$this->cache->set('product.bestseller.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->config->get('config_customer_group_id') . '.' . (int)$limit, $product_data);
		}

		return $product_data;
	}

	public function getProductAttributes($product_id) {
		$product_attribute_group_data = array();

		$product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

		foreach ($product_attribute_group_query->rows as $product_attribute_group) {
			$product_attribute_data = array();

			$product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$this->config->get('config_language_id') . "' AND pa.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY a.sort_order, ad.name");

			foreach ($product_attribute_query->rows as $product_attribute) {
				$product_attribute_data[] = array(
					'attribute_id' => $product_attribute['attribute_id'],
					'name'         => $product_attribute['name'],
					'text'         => $product_attribute['text']
				);
			}

			$product_attribute_group_data[] = array(
				'attribute_group_id' => $product_attribute_group['attribute_group_id'],
				'name'               => $product_attribute_group['name'],
				'attribute'          => $product_attribute_data
			);
		}

		return $product_attribute_group_data;
	}

	public function getProductOptions($product_id) {
		$product_option_data = array();

		$product_option_query = $this->db->query("
			SELECT * FROM " . DB_PREFIX . "product_option po 
			LEFT JOIN `" . DB_PREFIX . "option` o 
				ON (po.option_id = o.option_id) 
			LEFT JOIN " . DB_PREFIX . "option_description od 
				ON (o.option_id = od.option_id) 
			WHERE po.product_id = '" . (int)$product_id . "' 
				AND od.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			ORDER BY o.sort_order
		");

		foreach ($product_option_query->rows as $product_option) {
			$product_option_value_data = array();
			$product_option_value_query = $this->db->query("
				SELECT * FROM " . DB_PREFIX . "product_option_value pov 
				LEFT JOIN " . DB_PREFIX . "option_value ov 
					ON (pov.option_value_id = ov.option_value_id) 
				LEFT JOIN " . DB_PREFIX . "option_value_description ovd 
					ON (ov.option_value_id = ovd.option_value_id) 
				WHERE pov.product_id = '" . (int)$product_id . "' 
					AND pov.product_option_id = '" . (int)$product_option['product_option_id'] . "' 
					AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
				ORDER BY ov.sort_order
			");
			$default_option_isset = 'false';

			foreach ($product_option_value_query->rows as $product_option_value) {
				if ($product_option_value['default_option'] == '1') {
					$default_option_isset = 'true';
				}
				$product_option_value_data[] = array(
					'product_option_value_id' => $product_option_value['product_option_value_id'],
					'option_value_id'         => $product_option_value['option_value_id'],
					'name'                    => $product_option_value['name'],
					'image'                   => $product_option_value['image'],
					'quantity'                => $product_option_value['quantity'],
					'subtract'                => $product_option_value['subtract'],
					'price'                   => $product_option_value['price'],
					'price_prefix'            => $product_option_value['price_prefix'],
					'weight'                  => $product_option_value['weight'],
					'weight_prefix'           => $product_option_value['weight_prefix'],
					// DONE Вывод опции по умолчанию
					'default_option' 		  => $product_option_value['default_option'],
				);
			}

			$product_option_data[] = array(
				'product_option_id'    => $product_option['product_option_id'],
				'product_option_value' => $product_option_value_data,
				'option_id'            => $product_option['option_id'],
				'name'                 => $product_option['name'],
				'type'                 => $product_option['type'],
				'value'                => $product_option['value'],
				'required'             => $product_option['required'],
				// DONE Если не задана опция по умолчанию, выбрать первую опцию
				// См \catalog\view\theme\helium\template\product\product_page_options.twig
				'default_option_isset' => $default_option_isset,
			);
		}
		return $product_option_data;
	}

	public function getProductDiscounts($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND quantity > 1 AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY quantity ASC, priority ASC, price ASC");

		return $query->rows;
	}

	public function getProductImages($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getProductRelated($product_id) {
		$product_data = array();

		$query = $this->db->query("
			SELECT 
			* 
			FROM " . DB_PREFIX . "product_related pr 
			LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
			WHERE pr.product_id = '" . (int)$product_id . "' 
				AND p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'
		");

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $this->getProduct($result['related_id']);
		}

		return $product_data;
	}

	public function getProductLayoutId($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return (int)$query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getCategories($product_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

		return $query->rows;
	}

	public function getTotalProducts($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.product_id) AS total";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "product_to_category p2c";
			}

			if (!empty($data['filter_filter'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product_filter pf ON (p2c.product_id = pf.product_id) LEFT JOIN " . DB_PREFIX . "product p ON (pf.product_id = p.product_id)";
			} else {
				$sql .= " LEFT JOIN " . DB_PREFIX . "product p ON (p2c.product_id = p.product_id)";
			}
		} else {
			$sql .= " FROM " . DB_PREFIX . "product p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_filter'])) {
				$implode = array();

				$filters = explode(',', $data['filter_filter']);

				foreach ($filters as $filter_id) {
					$implode[] = (int)$filter_id;
				}

				$sql .= " AND pf.filter_id IN (" . implode(',', $implode) . ")";
			}
		}

		if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
			$sql .= " AND (";

			if (!empty($data['filter_name'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_name'])));

				foreach ($words as $word) {
					$implode[] = "pd.name LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}

				if (!empty($data['filter_description'])) {
					$sql .= " OR pd.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
				}
			}

			if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
				$sql .= " OR ";
			}

			if (!empty($data['filter_tag'])) {
				$implode = array();

				$words = explode(' ', trim(preg_replace('/\s+/', ' ', $data['filter_tag'])));

				foreach ($words as $word) {
					$implode[] = "pd.tag LIKE '%" . $this->db->escape($word) . "%'";
				}

				if ($implode) {
					$sql .= " " . implode(" AND ", $implode) . "";
				}
			}

			if (!empty($data['filter_name'])) {
				$sql .= " OR LCASE(p.model) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.sku) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.upc) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.ean) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.jan) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.isbn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
				$sql .= " OR LCASE(p.mpn) = '" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "'";
			}

			$sql .= ")";
		}

		if (!empty($data['filter_manufacturer_id'])) {
			$sql .= " AND p.manufacturer_id = '" . (int)$data['filter_manufacturer_id'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getProfile($product_id, $recurring_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "recurring r JOIN " . DB_PREFIX . "product_recurring pr ON (pr.recurring_id = r.recurring_id AND pr.product_id = '" . (int)$product_id . "') WHERE pr.recurring_id = '" . (int)$recurring_id . "' AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "'");

		return $query->row;
	}

	public function getProfiles($product_id) {
		$query = $this->db->query("SELECT rd.* FROM " . DB_PREFIX . "product_recurring pr JOIN " . DB_PREFIX . "recurring_description rd ON (rd.language_id = " . (int)$this->config->get('config_language_id') . " AND rd.recurring_id = pr.recurring_id) JOIN " . DB_PREFIX . "recurring r ON r.recurring_id = rd.recurring_id WHERE pr.product_id = " . (int)$product_id . " AND status = '1' AND pr.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getTotalProductSpecials() {
		$query = $this->db->query("
			SELECT 
				COUNT(DISTINCT ps.product_id) 
			AS total FROM " . DB_PREFIX . "product_special ps 
			LEFT JOIN " . DB_PREFIX . "product p ON (ps.product_id = p.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) 
			WHERE p.status = '1' 
				AND p.date_available <= NOW() 
				AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
				AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' 
				AND (
					(ps.date_start = '0000-00-00' OR ps.date_start < NOW()) 
					AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())
				)");

		if (isset($query->row['total'])) {
			return $query->row['total'];
		} else {
			return 0;
		}
	}

	// Search products, tags, attributes, categories, manufacturers, filter pages, blog articles
	public function searchProducts($search) {

		// Requred data

		// Escaped search word
		$search = mb_strtolower($this->db->escape($search), 'UTF-8');
		// Language
		$language_id = (int)$this->config->get('config_language_id');
		// Customer group
		$customer_group_id = (int)$this->config->get('config_customer_group_id');
		// Store
		$store_id = (int)$this->config->get('config_store_id');
		

		$sql = "

			SELECT 
				p.product_id AS product_id
			
			FROM " . DB_PREFIX . "product p
			
			LEFT JOIN " . DB_PREFIX . "product_description pd 
				ON p.product_id = pd.product_id
				AND pd.language_id = '".$language_id."'


			LEFT JOIN " . DB_PREFIX . "product_to_store p2s
				ON p.product_id = p2s.product_id
				
			LEFT JOIN " . DB_PREFIX . "product_to_category p2c
				ON p.product_id = p2c.product_id
				AND p2c.main_category = '1'
			
			WHERE 
				p2s.store_id = '".$store_id."' AND 
				pd.language_id = '".$language_id."' AND
				p.status = '1' AND
			
			#Search words
			(LCASE(pd.name)			LIKE '%".$search."%' OR
			 LCASE(p.model)			LIKE '%".$search."%' OR
			 LCASE(pd.description)	LIKE '%".$search."%')
			
			ORDER BY 
				# First sort by matching product name, then by description
				CASE
					WHEN p.model		LIKE '".$search."' THEN 1
					WHEN pd.name		LIKE '".$search."' THEN 2
					WHEN pd.name		LIKE '%".$search."%' THEN 3
					WHEN pd.name		LIKE '%".$search." ' THEN 4
					# TODO Add case when space follows product name like 'name '
					WHEN pd.description	LIKE '%".$search."%' THEN 5
					ELSE 10
				END 
			ASC
			LIMIT 10
		";
		
		$query = $this->db->query($sql);

		return $query->rows;

		
	}

	public function searchManufacturers($search, $manufacturers_ids) {
		$sql = "
		SELECT 
			md.name,
			# md.description,
			m.image,
			m.sort_order,
			su.keyword AS url,
			(SELECT COUNT(p.product_id) FROM oc_product p WHERE p.manufacturer_id = md.manufacturer_id) AS product_count
		FROM oc_manufacturer_description md 
		LEFT JOIN oc_manufacturer m ON m.manufacturer_id = md.manufacturer_id
		LEFT JOIN oc_seo_url su ON su.query = CONCAT('manufacturer_id=', md.manufacturer_id) AND su.language_id = '" . (int)$this->config->get('config_language_id') . "'
		WHERE 
			md.language_id = '" . (int)$this->config->get('config_language_id') . "' 
		AND (
		";

		if (is_array($manufacturers_ids) && !empty($manufacturers_ids)) {
			$sql .= "md.manufacturer_id IN (1,2) OR";
		}

		$sql .= "LCASE(md.name) LIKE '%".$search."%')
		ORDER BY m.sort_order ASC
		LIMIT 10
		";
		
		$query = $this->db->query($sql);
		return $query->rows;
	}

	// Check if sort and order corresponds allowed sorting data in 
	function searchArrayForKeyValue($array, $key, $value) {
		foreach ($array as $optgroup) {
			foreach ($optgroup as $sortDirection => $fields) {
				if (in_array($value, $fields) && $sortDirection == $key) {
					return true;
				}
			}
		}
		return false;
	}
}
