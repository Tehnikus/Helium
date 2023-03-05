<?php


class ControllerStartupSeoUrl extends Controller {
	
	//seopro start
		private $seo_pro;
		private $config_store_id;
		private $config_language_id;
		private $langs = array(
			'1' => array(
				'index' => '',
				'language_id' => '1'
			),
			'2' => array(
				'index' => 'uk',
				'language_id' => '2'
			)
		);
		public function __construct($registry) {
			parent::__construct($registry);	
			$this->config_store_id = $this->config->get('config_store_id');
			$this->config_language_id = $this->config->get('config_language_id');
			$this->seo_pro = new SeoPro($registry);
		}
	//seopro end
	
	public function index() {

		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

	
		// Decode URL
		if (isset($this->request->get['_route_'])) {

			// print_r($this->request->get['_route_']);

			$parts = explode('/', $this->request->get['_route_']);
			// print_r($parts);
			
			// // Убираем индекс из роутинга
			if ($parts[0] == $this->langs[(int)$this->config->get('config_language_id')]['index']) {
				// print_r($this->langs[(int)$this->config->get('config_language_id')]);
				unset($parts[0]);
				$parts = array_values($parts);
			}
			
			//seopro prepare route
			if($this->config->get('config_seo_pro')){		
				$parts = $this->seo_pro->prepareRoute($parts);
			}
			//seopro prepare route end
			// print_r($this->seo_pro->current_lang['prefix']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				// TODO
				// Тут прописать язык из урла
				$query = $this->db->query("
					SELECT * FROM " . DB_PREFIX . "seo_url 
					WHERE keyword = '" . $this->db->escape($part) . "' 
					AND store_id = '" . (int)$this->config->get('config_store_id') . "'
					AND language_id = '" . $this->seo_pro->current_lang['language_id'] . "'
				");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'filter') {
						$this->request->get['filter'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					if(!$this->config->get('config_seo_pro')){		
						$this->request->get['route'] = 'error/not_found';
					}
					
				break;
			}
				
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				}
			}
		}
		
		//seopro validate
		if($this->config->get('config_seo_pro')){		
			$this->seo_pro->validate();
		}
		// print_r($this->request->get);
	}

	// Преобразование параметров в человеко-понятную ссылку
	// http://oc.loc/index.php?route=product/category&filter=5&path=13  => http://oc.loc/medychna-tehnika/storinka-filtra.html
	public function rewrite($link) {

		if ($this->config_store_id != $this->config->get('config_store_id') || $this->config_language_id != $this->config->get('config_language_id')) {
			$this->__construct($this->registry);
		}

		$url_info = parse_url(str_replace('&amp;', '&', $link));

		if($this->config->get('config_seo_pro')){		
			$url = null;
		} else {
			$url = '';
		}

		$data = array();

		parse_str($url_info['query'], $data);

		// print_r($url_info);
		
		//seo_pro baseRewrite
		if($this->config->get('config_seo_pro')){		
			list($url, $data, $postfix) =  $this->seo_pro->baseRewrite($data, (int)$this->config->get('config_language_id'));	
		}
		
		
		// print_r($this->seo_pro->current_lang);
		// $url .= $this->seo_pro->current_lang['prefix'];
		// print_r($url_info);
		if (isset($this->seo_pro->current_lang['prefix']) && $this->seo_pro->current_lang['prefix'] !== '') {
			// $url = '/'.$this->seo_pro->current_lang['prefix'].$url;
			$url_info['path'] = '/'.$this->seo_pro->current_lang['prefix'].$url_info['path'];
		}
		// print_r($url);
		//seo_pro baseRewrite

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if (($data['route'] == 'product/product' && $key == 'product_id') 
					|| (($data['route'] == 'product/manufacturer/info' 
					|| $data['route'] == 'product/product') && $key == 'manufacturer_id') 
					|| ($data['route'] == 'information/information' && $key == 'information_id')
				) {
					$query = $this->db->query("
						SELECT * FROM " . DB_PREFIX . "seo_url 
						WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' 
						AND store_id = '" . (int)$this->config->get('config_store_id') . "' 
						AND language_id = '" . (int)$this->seo_pro->current_lang['language_id'] . "'
					");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];

						unset($data[$key]);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("
							SELECT * FROM " . DB_PREFIX . "seo_url 
							WHERE `query` = 'category_id=" . (int)$category . "' 
							AND store_id = '" . (int)$this->config->get('config_store_id') . "' 
							AND language_id = '" . (int)$this->seo_pro->current_lang['language_id'] . "'
						");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
						} else {
							$url = '';

							break;
						}
					}

					unset($data[$key]);
				}
			}
		}

		//seo_pro add blank url
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
		
		if($this->config->get('config_seo_pro')) {		
			$condition = ($url !== null);
		} else {
			$condition = $url;
		}

		if ($condition) {
			if($this->config->get('config_seo_pro')){		
				if($this->config->get('config_page_postfix') && $postfix) {
					$url .= $this->config->get('config_page_postfix');
				} elseif($this->config->get('config_seopro_addslash') || !empty( $query)) {
					$url .= '/';
				} 
			}

			// print_r($url);
			
			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}
}
