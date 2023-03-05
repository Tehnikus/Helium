<?php

class ControllerStartupSeoUrl extends Controller {
	
	
		private $seo_pro;
		public $lang_data;
		public $current_lang;

		public function __construct($registry) {
			parent::__construct($registry);	
			$this->seo_pro = new SeoPro($registry);
			$this->lang_data = $this->getLanguages();
			$this->current_lang = $this->lang_data[(int)$this->config->get('config_language_id')];
		}
	
	
	public function index() {

		// print_r($this->session->data['language']);
		// print_r($this->seo_pro->lang_data);

		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

	
		// Decode URL
		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			foreach ($this->lang_data as $lang_id => $language) {
				// echo($language['prefix']);
				// echo($parts[0]);
				// Если первая часть УРЛа (сразу после домена) совпадает с префиксом языка из таблицы oc_seo_url
				// сайт.ком/префикс_языка/бла-бла-бла/путин-хуйло.хтмл
				if ($parts[0] == $language['prefix']) {
					// Удаляем первую часть УРЛа из роутинга, чтобы остальной уод работал как всегда
					array_shift($parts);
					$this->current_lang = $language;
					// Если язык сессии не совпадает с языком, привязанным к префиксу
					// if ($this->session->data['language'] !== $language['code']) {
					// 	// Принудительно устанавливаем требуемый язык исходя из перфикса в УРЛе
					// 	$new_language = new Language($this->current_lang['code']);
					// 	$new_language->load($this->current_lang['code']);
					// 	$this->registry->set('language', $new_language);
					// 	$this->config->set('config_language_id', $this->current_lang['language_id']);
					// }
				} else {
					$this->current_lang = $this->lang_data[(int)$this->config->get('config_language_id')];
				}
			}
			
			//seopro prepare route
			if($this->config->get('config_seo_pro')){		
				$parts = $this->seo_pro->prepareRoute($parts);
			}
			//seopro prepare route end

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("
				SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

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
		//seopro validate
		
	}

	public function rewrite($link, $language_id = null) {
		if (is_null($language_id)) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		if($this->config->get('config_seo_pro')){		
			$url = null;
		} else {
			$url = '';
		}

		$data = array();

		parse_str($url_info['query'], $data);
		
		//seo_pro baseRewrite
		if($this->config->get('config_seo_pro')){		
			list($url, $data, $postfix) = $this->seo_pro->baseRewrite($data, $language_id);	
		}
		//seo_pro baseRewrite



		// foreach ($data as $key => $value) {
		// 	if (isset($data['route'])) {
		// 		if (($data['route'] == 'product/product' && $key == 'product_id') || (($data['route'] == 'product/manufacturer/info' || $data['route'] == 'product/product') && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id')) {
		// 			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

		// 			if ($query->num_rows && $query->row['keyword']) {
		// 				$url .= '/' . $query->row['keyword'];

		// 				unset($data[$key]);
		// 			}
		// 		} elseif ($key == 'path') {
		// 			$categories = explode('_', $value);

		// 			foreach ($categories as $category) {
		// 				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

		// 				if ($query->num_rows && $query->row['keyword']) {
		// 					$url .= '/' . $query->row['keyword'];
		// 				} else {
		// 					$url = '';

		// 					break;
		// 				}
		// 			}

		// 			unset($data[$key]);
		// 		}
		// 	}
		// }

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
				} elseif($this->config->get('config_seopro_addslash') || !empty($query)) {
					$url .= '/';
				} 
			}

			// Добавляем индекс языка к урлу
			$this->current_lang = $this->lang_data[(int)$this->config->get('config_language_id')];
			if (!empty($this->current_lang['prefix']) && $this->current_lang['prefix'] !== ''  && $url_info['query'] !== 'route=common/home') {
				$url_info['path'] = '/'.$this->current_lang['prefix'].$url_info['path'];
				// $url = $url.'/'.$this->current_lang['prefix'];
				// echo($query.'<br/>');
			}


			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;

		} else {
			return $link;
		}
	}

	// Данные языков
    public function getLanguages() {
		$language_data = array();
		$query = $this->db->query("
			SELECT 
				l.language_id,
				l.code,
				su.keyword AS prefix,

				(SELECT 
					'true' 
					FROM " . DB_PREFIX . "setting s 
					WHERE s.key = 'config_language' 
					AND s.value = l.code 
					AND s.store_id = '".(int)$this->config->get('config_store_id')."'
				) as 'default'

			FROM " . DB_PREFIX . "language l
			LEFT JOIN " . DB_PREFIX . "seo_url su 
				ON su.query = 'common/home' 
				AND su.language_id = l.language_id 
				AND su.store_id = '".(int)$this->config->get('config_store_id')."'
			WHERE l.status = '1'
		");

		foreach ($query->rows as $result) {
			$language_data[$result['language_id']] = array(
				'language_id' => $result['language_id'],
				'code'        => $result['code'],
				'prefix'      => $result['prefix'],
				'default'     => ($result['default'] == 'true') ? 'true' : 'false',
			);
		}

		return $language_data;
	}
}
