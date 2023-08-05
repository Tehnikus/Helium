<?php
class ControllerExtensionFeedGoogleSitemap extends Controller {
	// DONE Добавить ссылки на блог 
	// страницы фильтра
	// DONE Сделать вывод данных в файл
	public function index() {
		if ($this->config->get('feed_google_sitemap_status')) {

			$language_id = (int)$this->config->get('config_language_id');
			$store_id = (int)$this->config->get('config_store_id');
			$filename = "sitemap_store_".$store_id."_lang_".$language_id.".xml";
			
			// Create file for each store and language
			// Prepare stores
			$this->load->model('setting/store');
			$stores = array();
			$stores[] = array(
				'store_id' => 0,
				'name'     => 'default'
			);
			$stores_array = $this->model_setting_store->getStores();
			foreach ($stores_array as $key => $store) {
				$stores[] =array(
					'store_id' => $store['store_id'],
					'name' => $store['name'],
				);
			}
			// Prepare languages
			$this->load->model('localisation/language');
			$languages = $this->model_localisation_language->getLanguages();
			// Create sitemaps for each store and language
			foreach ($stores as $store) {
				foreach ($languages as $language) {
					echo($this->createFile($language['language_id'], $store['store_id']));
				}
			}
			
			return;
			$this->response->addHeader('Content-Type: application/xml');
			// return $this->response->setOutput(file_get_contents($filename));
		}
	}



	public function createFile($language_id = null, $store_id = null) {
		$language_id == null ? $language_id = (int)$this->config->get('config_language_id') : $language_id;
		$store_id == null ? $store_id = (int)$this->config->get('config_store_id') : $store_id;
		
		$text = $this->setSitemapText($language_id, $store_id);
		// Wrife file
		$language_id == null ? $language_id = (int)$this->config->get('config_language_id') : $language_id;
		$store_id == null ? $store_id = (int)$this->config->get('config_store_id') : $store_id;
		$filename = "sitemap_store_".$store_id."_lang_".$language_id.".xml";
		$now   = time();
		// Create file initially
		if (!file_exists($filename)) {
			$myfile = fopen($filename, "w") or die("Unable to open file! Check CHMOD on the main folder");
			fwrite($myfile, $text);
			fclose($myfile);
			return $filename." created";
		} else {
			// Then check if older than 7 days
			if (is_file($filename) && $now - filemtime($filename) >= 60 * 60 * 24 * 7) { 
				unlink($filename);
				$myfile = fopen($filename, "w") or die("Unable to open file!".$filename."</br>");
				fwrite($myfile, $text);
				fclose($myfile);
				return "New ".$filename." created, old was successfully deleted <br/>";
			} else {
				return $filename." not created, because it is less than 1 week old <br/>";
			}
		}
	}

	public function setSitemapText($language_id, $store_id) {
		$output  = '<?xml version="1.0" encoding="UTF-8"?>';
		$output .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
		
		$output .= $this->getProducts($language_id, $store_id);
		$output .= $this->getCategories($language_id, $store_id);
		$output .= $this->getFilterLinks($language_id, $store_id);
		$output .= $this->getArticles($language_id, $store_id);
		$output .= $this->getManufacturers($language_id, $store_id);
		$output .= $this->getInformaions($language_id, $store_id);
		
		$output .= '</urlset>';
		return $output;
	}

	protected function getCategories($language_id, $store_id) {
		$this->load->model('tool/image');
		$output = '';


		$query = $this->db->query("
			SELECT 
				c.category_id,
				cd.name,
				c.image,
				c.date_modified
			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd 
			ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s 
			ON (c.category_id = c2s.category_id) 
			WHERE c.status = '1' 
			AND cd.language_id = '" . $language_id . "' 
			AND c2s.store_id = '" . $store_id . "'  
			ORDER BY c.sort_order, 
			LCASE(cd.name)
		");

		$categories = $query->rows;
		if (isset($categories) && !empty($categories)) {
			foreach ($categories as $category) {
				$output .= '<!--category-->';
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('product/category', 'path=' . $category['category_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($category['date_modified'])) . '</lastmod>';
				$output .= '  <priority>0.7</priority>';
				if ($category['image']) {
					$image = $this->model_tool_image->resize($category['image'], $this->config->get('image_category_width'), $this->config->get('image_category_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('image_category_width'), $this->config->get('image_category_height'));
				}
				$output .= '  <image:image>';
				$output .= '  <image:loc>' . $image . '</image:loc>';
				$output .= '  <image:caption>' . $category['name'] . '</image:caption>';
				$output .= '  <image:title>' . $category['name'] . '</image:title>';
				$output .= '  </image:image>';
				$output .= '</url>';
			}
		}

		return $output;
	}

	protected function getProducts($language_id, $store_id) 
	{

		$this->load->model('tool/image');
		$output = '';
		$language_id == null ? $language_id = (int)$this->config->get('config_language_id') : $language_id;
		$store_id == null ? $store_id = (int)$this->config->get('config_store_id') : $store_id;

		$query = $this->db->query("
			SELECT 
				p.product_id,
				pd.name,
				p.image,
				p.date_modified
			FROM " . DB_PREFIX . "product p 
			LEFT JOIN " . DB_PREFIX . "product_description pd 
			ON (p.product_id = pd.product_id) 
			LEFT JOIN " . DB_PREFIX . "product_to_store p2s 
			ON (p.product_id = p2s.product_id) 
			WHERE p.status = '1' 
			AND pd.language_id = '" . $language_id . "' 
			AND p2s.store_id = '" . $store_id . "'  
			ORDER BY p.sort_order, 
			LCASE(pd.name)
		");
		$products = $query->rows;
		
		if (isset ($products) && !empty($products)) {
			foreach ($products as $product) {

				$output .= '<!--product-->';
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('product/product', 'product_id=' . $product['product_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])) . '</lastmod>';
				$output .= '  <priority>1.0</priority>';

				if ($product['image']) {
					$output .= '  <image:image>';
					$output .= '  <image:loc>' . $this->model_tool_image->resize($product['image'], $this->config->get('image_product_width'), $this->config->get('image_product_height')) . '</image:loc>';
					$output .= '  <image:caption>' . $product['name'] . '</image:caption>';
					$output .= '  <image:title>' . $product['name'] . '</image:title>';
					$output .= '  </image:image>';
				}

				$output .= '</url>';
			}
		}
		return $output;
	}

	protected function getArticles($language_id, $store_id)
	{
		$output = '';
		$language_id == null ? $language_id = (int)$this->config->get('config_language_id') : $language_id;
		$store_id == null ? $store_id = (int)$this->config->get('config_store_id') : $store_id;

		$sql = "
			SELECT 
				a.article_id,
				a.image,
				a.date_modified,
				ad.name
			FROM " . DB_PREFIX . "article a
			LEFT JOIN " . DB_PREFIX . "article_description ad on ad.article_id = a.article_id
			LEFT JOIN " . DB_PREFIX . "article_to_store a2s ON a2s.article_id = a.article_id 
			WHERE a.status = 1 
			AND ad.language_id = '" . $language_id . "' 
			AND a2s.store_id = '" . $store_id . "'
			ORDER BY a.date_modified
		";
		$query = $this->db->query($sql);
		$articles = $query->rows;
		if (isset($articles) && !empty($articles)) {
			$this->load->model('tool/image');
			foreach ($articles as $key => $article) {
				$articles[$key]['url'] = $this->url->link('blog/article', 'article_id=' . $article['article_id']);
				if (isset($article['image']) && $article['image'] !== '') {
					$articles[$key]['image'] = $this->model_tool_image->resize($article['image'], $this->config->get('configblog_article_miniature_image_width'), $this->config->get('configblog_article_miniature_image_height'));
				}
			}
		}
		foreach ($articles as $article) {
			$output .= '<!--article-->';
			$output .= '<url>';
			$output .= '  <loc>' . $article['url'] . '</loc>';
			$output .= '  <changefreq>weekly</changefreq>';
			$output .= '  <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($article['date_modified'])) . '</lastmod>';
			$output .= '  <priority>1.0</priority>';

			if ($article['image']) {
				$output .= '  <image:image>';
				$output .= '  <image:loc>' . $article['image'] . '</image:loc>';
				$output .= '  <image:caption>' . $article['name'] . '</image:caption>';
				$output .= '  <image:title>' . $article['name'] . '</image:title>';
				$output .= '  </image:image>';
			}

			$output .= '</url>';
		}
		return $output;
	}

	protected function getManufacturers($language_id, $store_id)
	{
		$output = '';
		$this->load->model('catalog/manufacturer');
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers($data = array(), $language_id);
		if (isset ($manufacturers) && !empty($manufacturers)) {
			foreach ($manufacturers as $manufacturer) {
				$output .= '<!--manufacturer-->';
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <priority>0.7</priority>';
				$output .= '</url>';
			}
		}
		return $output;
	}

	protected function getInformaions($language_id, $store_id) 
	{
		$output = '';
		$this->load->model('catalog/information');

		$informations = $this->model_catalog_information->getInformations();
		if (isset ($informations) && !empty($informations)) {
			foreach ($informations as $information) {
				$output .= '<!--information-->';
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('information/information', 'information_id=' . $information['information_id']) . '</loc>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <priority>0.5</priority>';
				$output .= '</url>';
			}
		}
		return $output;
	}

	protected function getFilterLinks($language_id, $store_id) 
	{
		$output = '';
		$this->load->model('catalog/category');

		$filter_links = $this->model_catalog_category->getFilterLinks();
		if (isset ($filter_links) && !empty($filter_links)) {
			foreach ($filter_links as $link) {
				$output .= '<!--filter-->';
				$output .= '<url>';
				$output .= '  <loc>' . $this->url->link('product/category', 'path=' . $link['default_category'] . '&filter=' . $link['filters']) . '</loc>';
				$output .= '  <lastmod>' . date('Y-m-d\TH:i:sP', strtotime($link['date_modified'])) . '</lastmod>';
				$output .= '  <changefreq>weekly</changefreq>';
				$output .= '  <priority>0.5</priority>';
				$output .= '</url>';
			}
		}
		return $output;
	}
}
