<?php


class ControllerProductCategory extends Controller {

	public	$allowed_sort_data = array(
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
	public $noindex_follow_requests = array(
		'filter',
		'page',
		'limit',
		'sort',
		'order'
	);

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
			$parts = explode('_', (string)$this->request->get['path']);
			$category_id = (int)array_pop($parts);
		} else {
			$category_id = 0;
		}

        if ($this->config->get('config_noindex_disallow_params')) {
            $params = explode ("\r\n", $this->config->get('config_noindex_disallow_params'));
            if(!empty($params)) {
                $disallow_params = $params;
            }
        }

		// Add noindex for sort, filter and page requests
		foreach ($this->noindex_follow_requests as $request_type) {
			if (isset($this->request->get[$request_type])) {
				$this->document->setRobots('noindex,follow');
			}
			// if (!in_array('limit', $disallow_params, true) && $this->config->get('config_noindex_status')) {
            //     $this->document->setRobots('noindex,follow');
            // }
		}

		// sanitize filter values - only int and comma alloved
		// Sort filter values ascending for the needs of static filter pages
		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
			$filter = preg_replace('/[^\\d,]+/', '', $filter);
			$filter = explode(',', $filter);
			foreach ($filter as $k => $string) {
				$strings[$k] = (int) $string;
			}
			asort($strings);
			$strings = array_unique(array_filter($strings));
			$string = implode(',', $strings);
			$filter = $string;
		} else {
			$filter = '';
		}

		// Sanitize sort request
		if (isset($this->request->get['sort']) && (in_array($this->request->get['sort'], $this->allowed_sort_data))) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		// Sanitize order request
		if (isset($this->request->get['order']) && ($this->request->get['order'] == 'ASC' || $this->request->get['order'] == 'DESC')) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		// Sanitize page request
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		// Satitize limit request
		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}

		// Secure data before it goes to DB
		$filter_data = array(
			'filter_category_id' => (int)$category_id,
			'filter_filter'      => $filter,
			'sort'               => $sort,
			'order'              => $order,
			'start'              => ((int)$page - 1) * (int)$limit,
			'limit'              => (int)$limit
		);
		// $filter_data = $this->securePostData($filter_data);

		if ($this->model_catalog_category->categoryExists($filter_data['filter_category_id'])) {
		
			$data = $this->renderCachedData($filter_data);
			$this->setPageMetaData($data);
			$this->renderCanonicalLinks($data);

			$data['thumb'] 			= $this->model_tool_image->resize($data['image'], $this->config->get('image_category_width'), $this->config->get('image_category_height'));
			$data['description'] 	= html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');
			$data['products'] 		= $this->renderProductList($filter_data);
			$data['sorts'] 			= $this->renderSorts();

			// Remove category description and child categories, set noindex, nofollow on sort and order pages
			if ((isset($this->request->get['filter']) 	&& $this->request->get['filter'] !== '') || 
				(isset($this->request->get['sort']) 	&& $this->request->get['sort'] 	!== '') || 
				(isset($this->request->get['order']) 	&& $this->request->get['order'] !== '')) {
				$this->document->setRobots('noindex,follow');
				$data['description'] = '';
				$data['categories'] = '';
			}

			

			// Fortuner
			// Set breadcrumbs for filter values
			if (isset($filter_data['filter_filter']) && $filter_data['filter_filter'] !== '') {
				// DONE remove sql request here
				// New function in:
				// catalog\model\catalog\category.php
				$filter_page_data = $this->model_catalog_category->getFilterPageData($filter_data['filter_filter']);

				// If static filter page exists
				if ($filter_page_data) {

					// Microdata
					$data['name'] 				= $filter_page_data['name'];
					$data['meta_description'] 	= $filter_page_data['meta_description'] ?: $data['meta_description'];
					$data['offer_count'] 		= $filter_page_data['offer_count'];
					$data['min_price'] 			= $filter_page_data['min_price'];
					$data['max_price'] 			= $filter_page_data['max_price'];
					$data['rating']				= $filter_page_data['rating'];
					$data['review_count']		= $filter_page_data['review_count'];

					// Meta data
					$this->document->setTitle($filter_page_data['meta_title'] ?: $filter_page_data['name']);
					$this->document->setDescription($filter_page_data['meta_description'] ?: $data['meta_description']);
					$this->document->setKeywords($filter_page_data['meta_keyword'] ?: $data['meta_keyword']);
					
					// Canonical url for filter page
					$this->document->addLink($this->url->link('product/category', 'path=' . $category_id . '&filter=' . $this->request->get['filter']), 'canonical');
					if (isset($this->request->get['sort']) || isset($this->request->get['page'])) {
						$this->document->setRobots('noindex,follow');
					} else {
						$this->document->setRobots('index,follow');
					}

					// Заголовок H1
					$data['heading_title'] = $filter_page_data['name'];
					// SEO Текст
					$data['description'] = $filter_page_data['description'] ? html_entity_decode($filter_page_data['description'], ENT_QUOTES, 'UTF-8')  : '';
					// Хлебные крошки фильтра
					$data['breadcrumbs'][] = array(
						'text' => $filter_page_data['name'],
						'href' => '',
					);
					$data['offer_count'] = $filter_page_data['offer_count'];
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
					$data['heading_title'] = $data['name'].' - '.implode(", ", $filter_names);
				}
			}

			if ($page > 1) {
				$data['heading_title'] .= ' - '.sprintf($this->language->get('page'), $page);
			}

			$data['microdata'] = $this->renderMicrodata($data);
			
			// Pagination
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
			$pagination = new Pagination();
			$pagination->total = $data['offer_count'];
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('product/category', 'path=' . $this->request->get['path'] . $url . '&page={page}');
			$data['pagination'] = $pagination->render();



			// Results text after product list
			$data['results'] = sprintf(
				$this->language->get('text_pagination'), 
				($data['offer_count']) ? (($page - 1) * $limit) + 1 : 0, 
				((($page - 1) * $limit) > ($data['offer_count'] - $limit)) ? $data['offer_count'] : ((($page - 1) * $limit) + $limit), $data['offer_count'], ceil($data['offer_count'] / $limit)
			);



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

			$this->response->setOutput($this->load->view('product/category', $data));

		} else {
			// Not found page
			$url = '';

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

	// DONE Get this done
	public function renderCachedData($filter_data)
	{
		$category_id = $filter_data['filter_category_id'];
		$cache_name  = 'category.' . 'store_id_' . (int) $this->config->get('config_store_id') . '.lang_id_' . (int) $this->config->get('config_language_id') . '.category_id_' . $category_id;
		$data        = $this->cache->get($cache_name);

		if (!$data) {
			$data = [];
			$data					  = $this->renderCategoryInfo($category_id);
			$data['breadcrumbs']      = $this->renderBreadcrumbs($category_id);
			$data['filter_links']     = $this->renderFilterLinksList($category_id);
			$data['categories'] 	  = $this->renderChildCategories($category_id);
			// $data['products_total']   = $this->model_catalog_product->getTotalProducts($filter_data);

			$this->cache->set($cache_name, $data);
		}

		return $data;
	}

	// DONE Отображение и кеширование списчка дочерних категорий
	public function renderChildCategories($category_id) {
		// $cache_name = 'category_child_categories.'.(int)$this->config->get('config_store_id').'.'.(int)$this->config->get('config_language_id').'.'.$category_id;
		// $child_categories = $this->cache->get($cache_name);
		// if (!$child_categories) {
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
					$image['thumb'] = $this->model_tool_image->resize($result['image'], $this->config->get('image_category_width'), $this->config->get('image_category_height'));
				} else {
					$image['thumb'] = $this->model_tool_image->resize('no_image.webp', $this->config->get('image_category_width'), $this->config->get('image_category_height'));
				}

				$child_categories[] = array(
					'name' => $result['name'],
					'product_count' => $subcategory_product_count ?: '',
					'href' => $this->url->link('product/category', 'path=' . $this->request->get['path'] . '_' . $result['category_id']),
					'thumb' => $image['thumb'],
					'width' => $this->config->get('image_category_width'),
					'height' => $this->config->get('image_category_height')
				);
			}
		// 	$this->cache->set($cache_name, $child_categories);
		// }
		return $child_categories;
	}
	
	// DONE Получаем список ссылок на страницы фильтров, которые привязаны к этой категории
	// См. www\catalog\model\catalog\category.php -> 
	// public function getFilterLinks($category_id)
	public function renderFilterLinksList($category_id) {
		$filter_links = array();
		$filter_links_data = $this->model_catalog_category->getFilterLinks($category_id);
		foreach ($filter_links_data as $key => $link_data) {
			$filter_links[$key] = array();
			$filter_links[$key]['name'] = $link_data['name'];
			$filter_links[$key]['url'] = $this->url->link('product/category', 'path=' . $link_data['default_category'] . '&filter=' . $link_data['filters']);
		}
		return $filter_links;
	}



	// Отображение списка сортировок
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
	// DONE Refactor code so all breadcrumbs are got by single DB request
	// DONE Cache breadcrumbs
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
		// TODO
		// return json encoded $this->renderProductList($filter_data)
		$product_list = $this->renderProductList($filter_data);
		echo(json_encode($product_list));
	}

	public function renderCanonicalLinks($data) {
		$page = (isset($this->request->get['page'])) ? (int)$this->request->get['page'] : 1;
		$limit = (isset($this->request->get['limit'])) ? (int)$this->request->get['limit'] : $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		// Canonical, next, prev
		// DONE Add filter next and prev
		if (!isset($this->request->get['filter']) || $this->request->get['filter'] == '') {
			// Add category canonical only on non-filter pages
			$this->document->addLink($this->url->link('product/category', 'path=' . $data['category_id']), 'canonical');
		}
		if ($page > 1) {
			// Добавляем каноникал для страниц пагинации
			$this->document->addLink($this->url->link('product/category', 'path=' . $data['category_id']).'?page='. $page, 'canonical');
			$this->document->addLink($this->url->link('product/category', 'path=' . $data['category_id'] . (($page > 2) ? '&page='. ($page - 1) : '')), 'prev');
		}
		
		if ($limit && ceil($data['offer_count'] / $limit) > $page) {
			$this->document->addLink($this->url->link('product/category', 'path=' . $data['category_id'] . '&page=' . ($page + 1)), 'next');
		} else {
			// Зацикливаем ссылку на главную страницу категории
			$this->document->addLink($this->url->link('product/category', 'path=' . $data['category_id']), 'next');
		}
	}

	// DONE Microdata
	// DONE Remove huge code, make json_encode
	// DONE Cache microdata
	public function renderMicrodata($data)
	{
		$microdata_array = array(
			'@context' => 'http://schema.org/',
			'@type' => 'Product',
			'name' => $data['name'],
			'description' => $data['meta_description'],
			'image' => $this->model_tool_image->resize($data['image'], $this->config->get('image_category_width'), $this->config->get('image_category_height')),
			'offers' => array(
				'@type' => 'AggregateOffer',
				'offerCount' 	=> $data['offer_count'],
				'lowPrice' 		=> round($data['min_price'], 2),
				'highPrice' 	=> round($data['max_price'], 2),
				'priceCurrency' => $this->session->data['currency'],
				'availability'	=>'http://schema.org/InStock',
			),
			'seller' => array(
				'@type' => 'Organization',
				'name' => $this->config->get('config_name')
			),
			'aggregateRating' => array(
				'@type' => 'AggregateRating',
				'ratingValue' => round($data['rating'], 2),
				'ratingCount' => $data['review_count'],
				'reviewCount' => $data['review_count'],
				'bestRating' => '5',
				'worstRating' => '1'
			),
		);
		$microdata = (json_encode($microdata_array, JSON_UNESCAPED_UNICODE));
		return $microdata;
	}

	public function renderProductList($filter_data) {
		$product_list = $this->model_catalog_product->prepareProductList($filter_data);
		return $product_list;
	}

	public function renderCategoryInfo($category_id)
	{
		$category_info = $this->model_catalog_category->getCategory($category_id);
		return $category_info;
	}

	public function setPageMetaData(&$data) {
		if ($data['meta_title']) {
			$this->document->setTitle($data['meta_title']);
		} else {
			$this->document->setTitle($data['name']);
		}

		if ($data['meta_h1']) {
			$data['heading_title'] = $data['meta_h1'];
		} else {
			$data['heading_title'] = $data['name'];
		}

		$this->document->setDescription($data['meta_description']);
		$this->document->setKeywords($data['meta_keyword']);
	}
}
