<?php
class ControllerStartupSeoUrl extends Controller {

	public $current_language_id;
	public $current_language_prefix;
	public $language_prefixes;

	public function __construct($registry) {
		parent::__construct($registry);	
		$this->language_prefixes = $this->getLanguagePrefixes();
		$this->current_language_id = $this->detectLanguage($this->language_prefixes);
	}


	// $this->request->get['_route_'] contains URL
	// $this->request->get['route'] contains query

	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		if (isset($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			// return;
		}
		// Set language detected in url
		// print_r($this->current_language_id);
		$this->config->set('config_language_id', $this->current_language_id);

		// $this->response->redirect($this->url->link('common/home'));
		// $language_prefixes = $this->getLanguagePrefixes();
		

		// Decode URL
		if (isset($this->request->get['_route_'])) {

			// $parts = explode('/', $this->request->get['_route_']);

			$request = $this->decodeUrl();

			$decoded_request = explode('=', $request);
				
			if (isset($decoded_request[0]) && isset($decoded_request[1])) {
				// Pages with entity id
				// as products, categories, articles, etc
				$this->request->get[htmlspecialchars($decoded_request[0])] = htmlspecialchars($decoded_request[1]);

				if ($decoded_request[0] == 'category_id') {
					$this->request->get['route'] = 'product/category';
					$this->request->get['path'] = $decoded_request[1];
				} elseif ($decoded_request[0] == 'product_id') {
					$this->request->get['route'] = 'product/product';
				} elseif ($decoded_request[0] == 'manufacturer_id') {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif ($decoded_request[0] == 'information_id') {
					$this->request->get['route'] = 'information/information';
				} elseif ($decoded_request[0] == 'blog_category_id') {
					$this->request->get['route'] = 'blog/category';
				} elseif ($decoded_request[0] == 'article_id') {
					$this->request->get['route'] = 'blog/article';
				} else {
					$this->request->get['route'] = 'error/not_found';
				}
			} else {
				// Pages without entity id
				// as homepage, account, order, etc
				$this->request->get['route'] = $request;
			}
			
			
			// print_r($this->request->get);

			// remove any empty arrays from trailing
			// if (utf8_strlen(end($parts)) == 0) {
			// 	array_pop($parts);
			// }

			// print_r($parts);

			// foreach ($parts as $part) {
			// 	$query = $this->db->query("
			// 		SELECT 
			// 			* 
			// 		FROM " . DB_PREFIX . "seo_url 
			// 		WHERE keyword = '" . $this->db->escape($part) . "' 
			// 		AND store_id = '" . (int)$this->config->get('config_store_id') . "'
			// 	");

			// 	if ($query->num_rows) {
			// 		$url = explode('=', $query->row['query']);

			// 		// print_r($url);

			// 		if ($url[0] == 'product_id') {
			// 			$this->request->get['product_id'] = $url[1];
			// 		}

			// 		if ($url[0] == 'category_id') {
			// 			if (!isset($this->request->get['path'])) {
			// 				$this->request->get['path'] = $url[1];
			// 			} else {
			// 				$this->request->get['path'] = $url[1];
			// 			}
			// 		}

			// 		if ($url[0] == 'manufacturer_id') {
			// 			$this->request->get['manufacturer_id'] = $url[1];
			// 		}

			// 		if ($url[0] == 'information_id') {
			// 			$this->request->get['information_id'] = $url[1];
			// 		}

			// 		if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
			// 			$this->request->get['route'] = $query->row['query'];
			// 		}
			// 	} else {
			// 		$this->request->get['route'] = 'error/not_found';

			// 		break;
			// 	}
			// }

			// if (!isset($this->request->get['route'])) {
			// 	if (isset($this->request->get['product_id'])) {
			// 		$this->request->get['route'] = 'product/product';
			// 	} elseif (isset($this->request->get['path'])) {
			// 		$this->request->get['route'] = 'product/category';
			// 	} elseif (isset($this->request->get['manufacturer_id'])) {
			// 		$this->request->get['route'] = 'product/manufacturer/info';
			// 	} elseif (isset($this->request->get['information_id'])) {
			// 		$this->request->get['route'] = 'information/information';
			// 	}
			// }
		} else {
			// If no _route_ this is main page
			$this->request->get['route'] = 'common/home';
		}
	}

	// $link - запрос вида 
	// http://oc.loc/index.php?route=common/home
	// http://oc.loc/index.php?route=product/category&path=18
	public function rewrite($link) {
		
		// Получаем массив вида
		//  Array ( [scheme] => http [host] => oc.loc [path] => /index.php [query] => route=blog/article&blog_category_id=1&article_id=47 )
		$url_info = parse_url(str_replace('&amp;', '&', $link));
		// Пустой url
		$url = '';
		// Из $url_info['query'] получаем части запроса:
		//  $data = array( 
		// 	[route] => blog/article 
		// 	[blog_category_id] => 1 
		// 	[article_id] => 46 
		// ) 
		$data = array();
		parse_str($url_info['query'], $data);
		
		if (isset($data['route'])) {
			if ($data['route'] == 'common/home') {
				$query = $this->db->query('
					SELECT
						keyword
					FROM '.DB_PREFIX.'seo_url
					WHERE query = "common/home"
					AND language_id = "'.$this->current_language_id.'"
					AND store_id = "'.(int)$this->config->get('config_store_id').'"
					LIMIT 1
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['article_id']);
				}
			}
			if ($data['route'] == 'blog/article') {
				$query = $this->db->query('
					SELECT
						keyword
					FROM '.DB_PREFIX.'seo_url
					WHERE query = "article_id='.$data['article_id'].'"
					AND language_id = "'.$this->current_language_id.'"
					AND store_id = "'.(int)$this->config->get('config_store_id').'"
					LIMIT 1
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['article_id']);
				}
			}
			if ($data['route'] == 'blog/latest') {
				$query = $this->db->query('
					SELECT
						keyword
					FROM '.DB_PREFIX.'seo_url
					WHERE query = "blog/latest"
					AND language_id = "'.$this->current_language_id.'"
					AND store_id = "'.(int)$this->config->get('config_store_id').'"
					LIMIT 1
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['article_id']);
				}
			}
			if ($data['route'] == 'blog/category') {
				$query = $this->db->query('
					SELECT
						keyword
					FROM '.DB_PREFIX.'seo_url
					WHERE query = "blog_category_id='.$data['blog_category_id'].'"
					AND language_id = "'.$this->current_language_id.'"
					AND store_id = "'.(int)$this->config->get('config_store_id').'"
					LIMIT 1
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['blog_category_id']);
				}
			}
			if ($data['route'] == 'product/category') {
				// print_r(($data));
				$query = $this->db->query('
					SELECT
						keyword
					FROM '.DB_PREFIX.'seo_url
					WHERE query = "category_id='.$data['path'].'"
					AND language_id = "'.$this->current_language_id.'"
					AND store_id = "'.(int)$this->config->get('config_store_id').'"
					LIMIT 1
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['path']);
				}
			}
			if ($data['route'] == 'product/product') {
				// Single query product url
				$query = $this->db->query('
					SELECT CONCAT(
						(SELECT 
							su.keyword
						FROM oc_seo_url su
						WHERE su.query = (SELECT CONCAT("category_id=", (SELECT p.main_category FROM oc_product p WHERE p.product_id = '.$data['product_id'].')))
						AND su.language_id = "'.$this->current_language_id.'"
						AND su.store_id = "'.(int)$this->config->get('config_store_id').'"
						LIMIT 1),

						"/",

						(SELECT 
							su.keyword
						FROM oc_seo_url su
						WHERE su.query = "product_id='.$data['product_id'].'"
						AND su.language_id = "'.$this->current_language_id.'"
						AND su.store_id = "'.(int)$this->config->get('config_store_id').'"
						LIMIT 1)
					) AS keyword
				');
				if ($query->row) {
					$url = '/'.$query->row['keyword'];
					unset($data['product_id']);
					unset($data['path']);
				}
			}
		}


		

		if ($url) {
			// print_r($url);
			unset($data['route']);

			$query = '';

			if ($data) {
				foreach ($data as $key => $value) {
					$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
				}

				if ($query) {
					$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
				}
			}

			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}
	public function detectLanguage($language_prefixes) {
		$language_prefixes = $this->getLanguagePrefixes();
		$defult_language = $this->config->get('config_language_id');
		$current_language_id = $defult_language;
		if (isset($this->request->get['_route_'])) {
			$current_language_prefix = explode('/', $this->request->get['_route_'])[0];
		} else {
			$current_language_prefix = '';
		}

		foreach ($language_prefixes as $lang_id => $prefix) {
			if ($prefix == $current_language_prefix) {
				$current_language_id = $lang_id;
			}
		}

		return $current_language_id;
	}
	
	// Get prefixes for all languages
	// Gets keyword from common/home and language_id
	// Returns array
	// Array(
	// 	[1] => '',
	// 	[2] => 'uk',
	// 	[3] => 'pl'
	// );
	// where key = language_id, value = language prefix
	public function getLanguagePrefixes() {
		$prefixes = [];
		$query = $this->db->query("
			SELECT 
				keyword,
				language_id
			FROM " . DB_PREFIX . "seo_url 
			WHERE query = 'common/home' 
			AND store_id = '" . (int)$this->config->get('config_store_id') . "'
		");
		foreach ($query->rows as $row) {
			$prefixes[$row['language_id']] = $row['keyword']; 
		}
		return $prefixes;
	}

	// Get URL part by GET request
	// public function getUrlPart($keyword)
	// {
	// 	$part = '';
	// 	$query = $this->db->query("
	// 		SELECT 
	// 			* 
	// 		FROM " . DB_PREFIX . "seo_url 
	// 		WHERE keyword = '" . $this->db->escape($part) . "' 
	// 		AND store_id = '" . (int)$this->config->get('config_store_id') . "'
	// 	");
	// }
	
	public function decodeUrl() {
		$parts = explode('/', $this->request->get['_route_']);
		$parts = array_filter($parts);
		// print_r($parts);
		$end = end($parts);
		$query = $this->db->query("
			SELECT
				query,
				language_id
			FROM ". DB_PREFIX ."seo_url
			WHERE keyword = '".$this->db->escape($end)."'
			-- AND language_id = '".(int)$this->current_language_id."'
			AND store_id = '" . (int)$this->config->get('config_store_id') . "'
			LIMIT 1
		");
		return $query->row['query'];

	}
}
