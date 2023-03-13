<?php


class ControllerProductCategory extends Controller {
	public function index() {
		// print_r($this->request->get);
		$this->load->language('product/category');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		

		// Массив со всеми данными для отрисовки страницы
		$data = [];
		$data['text_empty'] = $this->language->get('text_empty');
		if (isset($this->request->get['path'])) {
			$path = '';
			$parts = explode('_', (string)$this->request->get['path']);
			$category_id = (int)array_pop($parts);
			// Это нужно для полных хлебных крошек с вложенностью категорий
			$data['breadcrumbs'] = $this->renderBreadcrumbs($category_id);
		} else {
			$category_id = 0;
		}

        if ($this->config->get('config_noindex_disallow_params')) {
            $params = explode ("\r\n", $this->config->get('config_noindex_disallow_params'));
            if(!empty($params)) {
                $disallow_params = $params;
            }
        }

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
			if (!in_array('filter', $disallow_params, true) && $this->config->get('config_noindex_status')){
                // $this->document->setRobots('noindex,follow');
            }
		} else {
			$filter = '';
		}

		// Add noindex to sort pages
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
            if (!in_array('sort', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$sort = 'p.sort_order';
		}

		// Add noindex to sort order pages 
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
            if (!in_array('order', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$order = 'ASC';
		}

		// Add noindex to pagination pages
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
            if (!in_array('page', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
            if (!in_array('limit', $disallow_params, true) && $this->config->get('config_noindex_status')) {
                $this->document->setRobots('noindex,follow');
            }
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		$filter_data = array(
			'filter_category_id' => $category_id,
			'filter_filter'      => $filter,
			'sort'               => $sort,
			'order'              => $order,
			'start'              => ($page - 1) * $limit,
			'limit'              => $limit
		);

		$filter_data = $this->securePostData($filter_data);


		// if($this->model_catalog_category->filterPageExists($filter)) {
		// 	echo('ololo');
		// } else {
		// 	echo('ne ololo');
		// }

			
		// Filter links
		$data['filter_links'] = $this->renderFilterLinksList($category_id);
		// Category info
		$category_info = $this->renderCategoryInfo($category_id);

		if ($category_info) {
		

			// Дочерние категории
			$data['categories'] = $this->renderChildCategories($category_id);

			if ($category_info['meta_title']) {
				$this->document->setTitle($category_info['meta_title']);
			} else {
				$this->document->setTitle($category_info['name']);
			}

			if ($category_info['meta_h1']) {
				$data['heading_title'] = $category_info['meta_h1'];
			} else {
				$data['heading_title'] = $category_info['name'];
			}

			$this->document->setDescription($category_info['meta_description']);
			$data['meta_description'] = $category_info['meta_description'];
			$this->document->setKeywords($category_info['meta_keyword']);

			if ($category_info['image']) {
				$data['thumb'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			} else {
				$data['thumb'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			




			// Fortuner 
			// Убираем описание категории на страницах фильтра
			// Задаем index,follow для нужных страниц
			// оказывается, страницы пагинации должны индексироваться
			if (isset($this->request->get['filter']) || isset($this->request->get['sort']) || isset($this->request->get['order'])) {
				$this->document->setRobots('noindex,nofollow');
				$data['description'] = '';
				$data['categories'] = '';
			}

			// Fortuner
			// Set breadcrumbs for filter values
			if (isset($filter_data['filter_filter']) && $filter_data['filter_filter'] !== '') {
				$filter_data['filter_filter'] = preg_replace('/[^\\d,]+/', '', $filter_data['filter_filter']);
				$filter_data_query = $this->db->query("
					SELECT 
						fpd.name,
						fpd.description,
						fpd.meta_title,
						fpd.meta_description,
						fpd.meta_keyword,
						(SELECT 
							su.keyword 
							FROM " . DB_PREFIX . "seo_url su 
							WHERE su.seo_url_id = fpd.seo_url_id 
							AND su.language_id = '" . (int)$this->config->get('config_language_id') . "'
							AND su.store_id = '".(int)$this->config->get('config_store_id')."') 
						AS url,
						fpd.seo_url_id
					FROM " . DB_PREFIX . "filter_page_description fpd
					WHERE fpd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
					AND fpd.store_id = '".(int)$this->config->get('config_store_id')."'
					AND fpd.filters = '" . $filter_data['filter_filter']. "'
					LIMIT 1
				");
				$filter_data_rows = $filter_data_query->rows;

				// Если в БД есть совпадающая статичная страница фильтра
				if (isset($filter_data_rows) && !empty($filter_data_rows) && (isset($filter_data_rows[0]) && !empty($filter_data_rows[0]))) {
					
					$filter_data_rows = $filter_data_rows[0];
					$this->document->setTitle($filter_data_rows['meta_title'] ?: $filter_data_rows['name']);
					$this->document->setDescription($filter_data_rows['meta_description'] ?: $category_info['meta_description']);
					$data['meta_description'] = $filter_data_rows['meta_description'] ?: $category_info['meta_description'];
					$this->document->setKeywords($filter_data_rows['meta_keyword'] ?: $category_info['meta_keyword']);
					// Canonical url for filter page
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_id . '&filter=' . $this->request->get['filter']), 'canonical');
					if (isset($this->request->get['sort']) || isset($this->request->get['page'])) {
						$this->document->setRobots('noindex,follow');
					} else {
						$this->document->setRobots('index,follow');
					}

					// Заголовок H1
					$data['heading_title'] = $filter_data_rows['name'];
					// SEO Текст
					$data['description'] = $filter_data_rows['description'] ? html_entity_decode($filter_data_rows['description'], ENT_QUOTES, 'UTF-8')  : '';
					// Хлебные крошки фильтра
					$data['breadcrumbs'][] = array(
						'text' => $filter_data_rows['name'],
						'href' => $filter_data_rows['url'],
					);
				} else {
					// Получаем список названий выбранных фильтров
					$filter_query = $this->db->query("
						SELECT 
							fd.filter_id,
							fd.name
						FROM " . DB_PREFIX . "filter_description fd
						WHERE fd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
						AND fd.filter_id IN (".$filter_data['filter_filter'].") 
						ORDER BY fd.filter_id"
					);
					$names_query = $filter_query->rows;
					$filter_names = [];
					foreach($names_query as $name) {
						$filter_names[$name['filter_id']] = $name['name'];
					}
					// Хлебные крошки
					$data['breadcrumbs'][] = array(
						'text' => implode(", ", $filter_names),
						'href' => '',
					);
					// Заголовок H1
					$data['heading_title'] = $category_info['name'].' - '.implode(", ", $filter_names);
				}
			}

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);
			$data['products'] = array();
			$data['products'] = $this->renderProductList($filter_data);
			$data['sorts'] = $this->renderSorts();
	

			

			$url = '';

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			// Пагинация
			$pagination = new Pagination();
			$pagination->total = $product_total;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');
			$data['pagination'] = $pagination->render();

			// Добавляем в заголовок номер страницы, чтоыб тупой гугл наконец понял, что это пагинация
			if ($page > 1) {
				$data['heading_title'] .= ' - '.$this->language->get('page').' '.$page;
			}
			// Добавляем заголовки сортировки
			if (isset($this->request->get['sort']) && isset($this->request->get['order'])) {
				if (($this->request->get['sort'] === 'pd.name') && ($this->request->get['order']) === 'DESC') {
					$data['heading_title'] .= ': '.$this->language->get('text_name_desc');
				}
				if (($this->request->get['sort'] === 'pd.name') && ($this->request->get['order']) === 'ASC') {
					$data['heading_title'] .= ': '.$this->language->get('text_name_asc');
				}
				if (($this->request->get['sort'] === 'p.price') && ($this->request->get['order']) === 'DESC') {
					$data['heading_title'] .= ': '.$this->language->get('text_price_desc');
				}
				if (($this->request->get['sort'] === 'p.price') && ($this->request->get['order']) === 'ASC') {
					$data['heading_title'] .= ': '.$this->language->get('text_price_asc');
				}
				if (($this->request->get['sort'] === 'rating') && ($this->request->get['order']) === 'DESC') {
					$data['heading_title'] .= ': '.$this->language->get('text_rating_desc');
				}
				if (($this->request->get['sort'] === 'rating') && ($this->request->get['order']) === 'ASC') {
					$data['heading_title'] .= ': '.$this->language->get('text_rating_asc');
				}
				if (($this->request->get['sort'] === 'p.model') && ($this->request->get['order']) === 'DESC') {
					$data['heading_title'] .= ': '.$this->language->get('text_model_desc');
				}
				if (($this->request->get['sort'] === 'p.model') && ($this->request->get['order']) === 'ASC') {
					$data['heading_title'] .= ': '.$this->language->get('text_model_asc');
				}
			}


			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// Canonical, next, prev
			// TODO Add filter next and prev
			if (!isset($this->request->get['filter']) || $this->request->get['filter'] == '') {
				// Add category canonical only on non-filter pages
				$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'canonical');
				if ($page > 1) {
					// Fortuner
					// Добавляем каноникал для страниц пагинации
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']).'?page='. $page, 'canonical');
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . (($page - 2) ? '&page='. ($page - 1) : '')), 'prev');
                }
				
                if ($limit && ceil($product_total / $limit) > $page) {
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id'] . '&page=' . ($page + 1)), 'next');
                } else {
					// Fortuner
					// Зацикливаем ссылку на главную страницу категории
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_info['category_id']), 'next');
				}
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			
			// Fortuner
			// DONE Microdata
			// TODO Переделать в json_encode
			// TODO добавить микроразметку в кеширование
			
			if ($page == 1 && $sort == 'p.sort_order' && $order == 'ASC') {
				// Условия, при которых нужно спрятать микродату
				$data['microdata']['show'] = true;
			}


			// $microdata_array = array(
			// 	'@context' => 'http://schema.org/',
			// 	'@type' => 'Product',
			// 	'name' => $data['heading_title'],
			// 	'description' => $data['meta_description'],
			// 	'image' => $data['thumb'],
			// 	'offers' => array(
			// 		'@type' => 'AggregateOffer',
			// 		'offerCount' => $category_info['offer_count'],
			// 		'lowPrice' => $category_info['min_price'],
			// 		'highPrice' => $category_info['max_price'],
			// 		'priceCurrency' => $this->session->data['currency'],
			// 		'availability' =>'http://schema.org/InStock',
			// 		'offers' => array()
			// 	),
			// 	'seller' => array(
			// 		'@type' => 'Organization',
			// 		'name' => $this->config->get('config_name')
			// 	),
			// 	'aggregateRating' => array(
			// 		'@type' => 'AggregateRating',
			// 		'ratingValue' => $category_info['rating'],
			// 		'ratingCount' => $category_info['review_count'],
			// 		'reviewCount' => $category_info['review_count'],
			// 		'bestRating' => '5',
			// 		'worstRating' => '1'
			// 	),
			// );
			// $microdata = (json_encode($microdata_array, JSON_UNESCAPED_UNICODE));

			
			
			

			// Собираем данные первой страницы
			$price_list = [];
			$manufacturer_list = [];
			$sku_list = [];
			$ean_list = [];
			$revievs_count = 0;
			$reviews_avg = [];
			$offers = [];
			if (!empty($data['products'])) {
				foreach ($data['products'] as $product) {
					$offers[$product['product_id']]['name'] = $product['name'];
					$offers[$product['product_id']]['url'] = html_entity_decode($product['href']);
					$offers[$product['product_id']]['image'] = $product['thumb'];
				}
				foreach ($data['products'] as $result) {
					// Список производителей
					if (isset($result['manufacturer']) && !empty($result['manufacturer'])) {
						$manufacturer_list[] = $result['manufacturer'];
					}
					// Список SKU
					if (isset($result['sku']) && !empty($result['sku'])) {
						$sku_list[] = $result['sku'];
					} else {
						$sku_list[] = $result['model'];
					}
					// Список EAN
					if (isset($result['ean']) && !empty($result['ean'])) {
						$ean_list[] = $result['ean'];
					}
					$price_list[] = (isset($result['special']) &&  $result['special'] > 0) ? $result['special'] : $result['price'];
					$offers[$result['product_id']]['price'] = (isset($result['special']) &&  $result['special'] > 0) ? $result['special'] : $result['price'];
					$offers[$result['product_id']]['gtin'] = $result['ean'];
					if (isset($result['reviews']) && !empty($result['reviews']) && $result['reviews'] !== "0") {
						$revievs_count += $result['reviews'];
						if ($result['rating'] != 0) {
							$reviews_avg[] = $result['rating'];
						};
					}
				}
				asort($price_list);
			}

			// Ссылка на изображение категории
			
			if ((isset($category_info['image']) && !empty($category_info['image']))) {
				$img_file = ($this->request->server['HTTPS'] ? HTTPS_SERVER : HTTP_SERVER). 'image/'. $category_info['image'];
			} elseif (isset($data['products'][0]['thumb']) && !empty($data['products'][0]['thumb'])) {
				$img_file = $data['products'][0]['thumb'];
			} else {
				$img_file = file_exists(DIR_IMAGE . $this->config->get('config_logo')) ? $this->config->get('config_logo') : 'catalog/opencart-logo.png';
			}
			// Передаем данные 
			$data['microdata']['title'] = $data['heading_title'];
			$data['microdata']['description'] = $category_info['meta_description'];
			$data['microdata']['price_currency'] = $this->session->data['currency'];

			$data['microdata']['manufacturers'] = $manufacturer_list;
			$data['microdata']['sku_list'] = $sku_list;
			$data['microdata']['ean_list'] = $ean_list;
			$data['microdata']['thumb'] = $img_file;
			$data['microdata']['product_count'] = $product_total;
			$data['microdata']['offers'] = $offers;
			$data['microdata']['lowest_price'] = round((float)reset($price_list), 2);
			$data['microdata']['highest_price'] = round((float)end($price_list), 2);
			$data['microdata']['review_count'] = $revievs_count;
			$data['microdata']['rating'] = (count($reviews_avg) > 0) ? round(array_sum($reviews_avg) / count($reviews_avg), 1) : "";
			$data['microdata']['shop_name'] = $this->config->get('config_name');
			// $data['microdata_encoded'] = json_encode($data['microdata'], JSON_UNESCAPED_UNICODE);
			// Microdata end

			$this->response->setOutput($this->load->view('product/category', $data));

		} else {
			$url = '';

			// if (isset($this->request->get['path'])) {
			// 	$url .= '&path=' . $this->request->get['path'];
			// }

			// if (isset($this->request->get['filter'])) {
			// 	$url .= '&filter=' . $this->request->get['filter'];
			// }

			// if (isset($this->request->get['sort'])) {
			// 	$url .= '&sort=' . $this->request->get['sort'];
			// }

			// if (isset($this->request->get['order'])) {
			// 	$url .= '&order=' . $this->request->get['order'];
			// }

			// if (isset($this->request->get['page'])) {
			// 	$url .= '&page=' . $this->request->get['page'];
			// }

			// if (isset($this->request->get['limit'])) {
			// 	$url .= '&limit=' . $this->request->get['limit'];
			// }

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/category', $url)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	// DONE Отображение и кеширование списчка дочерних категорий
	public function renderChildCategories($category_id) {
		$cache_name = 'category_child_categories.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id').'.'.$category_id;
		$child_categories = $this->cache->get($cache_name);
		if (!$child_categories) {
			$child_categories = array();
			$results = $this->model_catalog_category->getCategories($category_id);

			foreach ($results as $result) {
				// Фильтр для подсчета кол-ва товаров в дочерней категории
				if ($this->config->get('config_product_count') !== null) {
					$filter_data = array(
						'filter_category_id'  => $result['category_id'],
						'filter_sub_category' => true
					);
					$subcategory_product_count = $this->model_catalog_product->getTotalProducts($filter_data);
				}


				// Изображение дочерней категории
				// DONE - добавить картинку no_image.webp
				if ($result['image']) {
					$image['thumb'] = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
				} else {
					$image['thumb'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height'));
				}

				$child_categories[] = array(
					'name' => $result['name'],
					'product_count' => $subcategory_product_count ?: '',
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id']),
					'thumb' => $image['thumb'],
					'width' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'),
					'height' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')
				);
			}
			$this->cache->set($cache_name, $child_categories);
		}
		return $child_categories;
	}
	
	// DONE Получаем список ссылок на страницы фильтров, которые привязаны к этой категории
	// См. www\catalog\model\catalog\category.php -> 
	// public function getFilterLinks($category_id)
	public function renderFilterLinksList($category_id) {
		$cache_name = 'category_filter_links.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id').'.'.$category_id;
		$filter_links = $this->cache->get($cache_name);
		if (!$filter_links) {
			$filter_links = array();
			$filter_links_data = $this->model_catalog_category->getFilterLinks($category_id);
			foreach ($filter_links_data as $key => $link_data) {
				$filter_links[$key] = array();
				$filter_links[$key]['name'] = $link_data['name'];
				$filter_links[$key]['url'] = $this->url->link('product/category', 'path=' . $link_data['default_category'] . '&filter=' . $link_data['filters']);
			}
			$this->cache->set($cache_name, $filter_links);
		}
		return $filter_links;
	}



	// Отображение и кеширование списка сортировок
	// DONE Добавить условия для фильтров
	public function renderSorts() {
		$sorts = array();
		if (!$sorts) {
			// DONE Убрать get параметры из сортировки по умолчанию
			$sorts[] = array(
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'])
			);

			$sorts[] = array(
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=ASC')
			);

			$sorts[] = array(
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.price&order=DESC')
			);

			$sorts[] = array(
				'text'  => $this->language->get('text_bestsellers_desc'),
				'value' => 'p.sold-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.sold&order=DESC')
			);

			if ($this->config->get('config_review_status')) {
				$sorts[] = array(
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.rating&order=DESC')
				);
			}

			$sorts[] = array(
				'text'  => $this->language->get('text_date_added_desc'),
				'value' => 'p.date_added-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.date_added&order=DESC')
			);
			
			$sorts[] = array(
				'text'  => $this->language->get('text_views_desc'),
				'value' => 'p.viewed-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.viewed&order=DESC')
			);





			$sorts[] = array(
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=ASC')
			);

			$sorts[] = array(
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=pd.name&order=DESC')
			);


			$sorts[] = array(
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=ASC')
			);

			$sorts[] = array(
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '&sort=p.model&order=DESC')
			);




		}
		// DONE Добавить фильтр в сортировку
		if (isset($this->request->get['filter'])) {
			foreach ($sorts as &$sort) {
				$sort['href'] .= '&filter='.$this->request->get['filter'];
			}
		}
		// DONE Добавить пагинацию в сортировку
		if (isset($this->request->get['page'])) {
			foreach ($sorts as &$sort) {
				$sort['href'] .= '&page='.$this->request->get['page'];
			}
		}
		return $sorts;
	}

	// Список хлебных крошек
	// DONE прописать полный путь к категории
	// DONE Переписать эту часть, чтобы все данные получались одним запросом в БД
	// TODO Сделать кеширование хлебных крошек
	public function renderBreadcrumbs($category_id) {
		$breadcrumbs = array();
		// В закоментированных строках формирование path для категории
		// Пусть пока останется
		$path_query = $this->db->query("
			SELECT 
				cp.path_id AS category_id,
				cd.name
				-- GROUP_CONCAT(path_id SEPARATOR '_') as path
			FROM " . DB_PREFIX . "category_path cp
			LEFT JOIN ".DB_PREFIX."category_description cd ON cd.category_id = cp.path_id AND cd.language_id = '".(int)$this->config->get('config_language_id')."'
			WHERE cp.category_id = '" . (int)$category_id . "'
			-- AND cp.path_id <> '" . (int)$category_id . "'
			ORDER BY cp.level ASC
		");

		// Домашняя страница
		$breadcrumbs[] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$path = array();
		if ($path_query->rows) {
			$path = $path_query->rows;
			foreach ($path as $category) {
				$breadcrumbs[] = array(
					'text' => $category['name'],
					'href' => $this->url->link('product/category', 'path=' . $category['category_id'])
				);
			}
		}

		return $breadcrumbs;
	}

	public function loadMore($filter_data) {

	}

	public function renderCanonicalLinks($filter_data) {

	}

	public function renderProductList($filter_data) {

		// Кеш списка товаров
		if (!$this->customer->isLogged() && 
			(isset($filter_data['sort']) && $filter_data['sort'] == 'p.sort_order') &&
			(isset($filter_data['start']) && $filter_data['start'] == '0') &&
			(isset($filter_data['order']) && $filter_data['order'] == 'ASC')
		) {
			if (isset($filter_data) && $filter_data['filter_filter'] == '') {
			// Product list in category
				$cache_name = 'category.product_list.'.'store_id_'.(int)$this->config->get('config_store_id').'lang_id_.'.(int)$this->config->get('config_language_id').'category_id_.'.$filter_data['filter_category_id'];
			} else {
				// Product list in filter
				$cache_name = 'category.product_list.'.'store_id_'.(int)$this->config->get('config_store_id').'lang_id_.'.(int)$this->config->get('config_language_id').'filter_id_.'.$filter_data['filter_filter'];
			}
			$product_list = $this->cache->get($cache_name);
			if (!$product_list) {
				$product_list = $this->model_catalog_product->prepareProductList($filter_data);
				$this->cache->set($cache_name, $product_list);
			}
		} else {
		}
		$product_list = $this->model_catalog_product->prepareProductList($filter_data);
		return $product_list;
	}

	public function renderCategoryInfo($category_id)
	{
		$cache_name = 'category.category_info.'.'store_id_'.(int)$this->config->get('config_store_id').'lang_id_.'.(int)$this->config->get('config_language_id').'category_id_.'.$category_id;
		$category_info = $this->cache->get($cache_name);
		if (!$category_info) {
			$category_info = $this->model_catalog_category->getCategory($category_id);
			$this->cache->set($cache_name, $category_info);
		}
		return $category_info;
	}

	// TODO Get this done
	public function renderCachedData($filter_data) {
		$category_id = $filter_data['filter_category_id'];
		$cache_name = 'category.'.'store_id_'.(int)$this->config->get('config_store_id').'.lang_id_'.(int)$this->config->get('config_language_id').'.category_id_'.$category_id;
		$data = $this->cache->get($cache_name);
		if (!$data) {
			$data = [];
			
			$data['breadcrumbs'] 		= $this->renderBreadcrumbs($category_id);
			$data['filter_links']		= $this->renderFilterLinksList($category_id);
			$data['category_info']		= $this->renderCategoryInfo($category_id);
			$data['child_categories']	= $this->renderChildCategories($category_id);
			$data['sorts']				= $this->renderSorts();
			$data['product_list']		= $this->renderProductList($filter_data);

			$this->cache->set($cache_name, $data);
		}
		return $data;
	}

	// Clear post data from any possible injections
	public function securePostData($filter_data) {

		// Allowed order strings
		$allowed_sort_data = array(
			'pd.name',
			'p.model',
			'p.quantity',
			'p.price',
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

		// Clear post from possible sql injections
		foreach ($filter_data as $key => $val) {
			if ($key == 'sort') {
				if (!in_array($val, $allowed_sort_data) || $val == '') {
					$val = 'p.sort_order';
				}
			} elseif ($key == 'order' && ($key !== 'ASC' || $key !== 'DESC')) {
				$key = 'ASC';
			} else {
				$val = preg_replace('/[^\\d,]+/', '', $val);
				$val = explode(',', $val);
				foreach ($val as $k => $string) {
					// Watch this, may cause slowdowns
					$strings[$k] = $this->db->escape($string);
					// $strings[$k] = (int)$string;
				}
				asort($strings);
				$strings = array_unique(array_filter($strings));
				$string = implode(',',$strings);
				$filter_data[$key] = $string;
			}
		}
		$this->request->get['filter'] = $filter_data['filter_filter'];
		return $filter_data;
	}

}
