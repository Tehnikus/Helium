<?php
class ControllerCatalogFilterPages extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/filter_pages');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/filter_pages');

		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/filter_pages');
		$this->document->setTitle($this->language->get('create_filter_page'));
		$this->load->model('catalog/filter_pages');


		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_filter_pages->addFilterPage($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			// $url = '';

			// if (isset($this->request->get['sort'])) {
			// 	$url .= '&sort=' . $this->request->get['sort'];
			// }

			// if (isset($this->request->get['order'])) {
			// 	$url .= '&order=' . $this->request->get['order'];
			// }

			// if (isset($this->request->get['page'])) {
			// 	$url .= '&page=' . $this->request->get['page'];
			// }

			// $this->response->redirect($this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		// сопроводительная инфа
		$this->load->language('catalog/filter_pages');
		$this->document->setTitle($this->language->get('edit_filter_page'));
		$this->load->model('catalog/filter_pages');

		// Если метод пост и форма прошла валидацию
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			// сохранить данные в БД

			$this->model_catalog_filter_pages->editFilterPage($this->request->get['filter_page_id'], $this->request->post);


			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			// Редирект на страницу списка
			$this->response->redirect($this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	// Готово
	public function delete() {
		$this->load->language('catalog/filter_pages');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('catalog/filter_pages');

		if ((isset($this->request->post['selected']) || isset($this->request->post['filter_page_id'])) && $this->validateDelete()) {
			if(isset($this->request->post['selected'])) {
				foreach ($this->request->post['selected'] as $filter_page_id) {
					$this->model_catalog_filter_pages->deleteFilterPage($filter_page_id);
				}
			}
			if(isset($this->request->post['filter_page_id'])) {
				$this->model_catalog_filter_pages->deleteFilterPage($this->request->post['filter_page_id']);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	// DONE
	protected function getList() {
		// Default sorting of listing
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'fpd.date_modified';
		}
		// Order by date created, new pages above
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		// Breadcrumbs
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);
		// Action buttons
		$data['add'] = $this->url->link('catalog/filter_pages/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/filter_pages/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['filters'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		// Total filter pages
		$filter_total = $this->model_catalog_filter_pages->getTotalFilterPages();
		
		// Get data from DB
		$results = $this->model_catalog_filter_pages->getFilterPages($filter_data);

		// Stores
		// Default store
		$this->load->model('setting/store');
		$stores = array();
		$stores[0] = array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
			'url'      => $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG,
		);
		// Oyher stores if exist
		$stores_raw = $this->model_setting_store->getStores();
		foreach ($stores_raw as $store) {
			$stores[$store['store_id']] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name'],
				'url'      => $store['url'],
			);
		}
		$data['stores'] = $stores;


		// Display data
		foreach ($results as $result) {
			$data['filter_pages'][] = array(
				'filter_page_id'  => $result['filter_page_id'],
				'name'            => $result['name'],
				'filter_names'    => $result['filter_names'],
				'product_count'   => $result['product_count'],
				'seo_url'         => $result['seo_url'],
				'date_modified'   => $result['date_modified'],
				'store'   		  => $stores[$result['store_id']]['name'],
				'edit'            => $this->url->link('catalog/filter_pages/edit', 'user_token=' . $this->session->data['user_token'] . '&filter_page_id=' . $result['filter_page_id'] . $url, true),
				'delete'          => $this->url->link('catalog/filter_pages/delete', 'user_token=' . $this->session->data['user_token'] . '&filter_page_id=' . $result['filter_page_id'] . $url, true),
				// DONE Route to frontend filter page
				'view'   		  => HTTP_CATALOG . 'index.php?route=product/category&path=' . ($result['default_category']) . '&filter=' . ($result['filters']),
			);
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=fpd.name' . $url, true);
		$data['sort_date_modified'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=fpd.date_modified' . $url, true);
		$data['sort_product_count'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=product_count' . $url, true);
		$data['sort_filter_names'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=filter_names' . $url, true);
		$data['sort_store_id'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=fpd.store_id' . $url, true);
		$data['sort_seo_url'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . '&sort=seo_url' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $filter_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($filter_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($filter_total - $this->config->get('config_limit_admin'))) ? $filter_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $filter_total, ceil($filter_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('catalog/filter_pages_list', $data));
	}

	// Filter page form
	protected function getForm() {
		// Heading
		$data['text_form'] = !isset($this->request->get['filter_page_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		// Errors if persist
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['group'])) {
			$data['error_group'] = $this->error['group'];
		} else {
			$data['error_group'] = array();
		}

		if (isset($this->error['filter'])) {
			$data['error_filter'] = $this->error['filter'];
		} else {
			$data['error_filter'] = array();
		}
		// $url for breadcrumbs. Stores previous page state - sort order, page number
		$url = '';
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$this->load->model('catalog/category');
		$data['categories'] = $this->model_catalog_category->getAllCategories();

		// 
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('edit_filter_page'),
			'href' => $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['filter_page_id'])) {
			$data['action'] = $this->url->link('catalog/filter_pages/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/filter_pages/edit', 'user_token=' . $this->session->data['user_token'] . '&filter_page_id=' . $this->request->get['filter_page_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/filter_pages', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['user_token'] = $this->session->data['user_token'];
		
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		// Stores list
		// Default store (stored in config)
		$this->load->model('setting/store');
		$stores = array();
		$stores[0] = array(
			'store_id' => '0',
			'name'     => $this->config->get('config_name'),
			'url'      => $this->config->get('config_secure') ? HTTPS_CATALOG : HTTP_CATALOG,
		);
		// Other stores (stored in DB)
		$stores_raw = $this->model_setting_store->getStores();
		foreach ($stores_raw as $store) {
			$stores[$store['store_id']] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name'],
				'url'      => $store['url'],
			);
		}
		$data['stores'] = $stores;


		// Здесь получаем данные из БД
		if (isset($this->request->get['filter_page_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$filter_pages_info = $this->model_catalog_filter_pages->getFilterPage($this->request->get['filter_page_id']);
		}
		


		// Вот это похоже на передачу текста в БД
		// Так нужно сделать для каждого поля
		if (isset($this->request->post['filter_page_description'])) {
			// print_r($this->request->post['filter_page_description']);
			$data['filter_page_description'] = $this->request->post['filter_page_description'];
		} elseif (isset($this->request->get['filter_page_id'])) {
			// Здесь получаем данные из БД
			$data['filter_page_description'] = $this->model_catalog_filter_pages->getFilterPage($this->request->get['filter_page_id']);
		} else {
			$data['filter_page_description'] = array();
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} else {
			$data['sort_order'] = '';
		}

		// if (isset($this->request->post['filter'])) {
		// 	$data['filters'] = $this->request->post['filter'];
		// } elseif (isset($this->request->get['filter_group_id'])) {
		// 	$data['filters'] = $this->model_catalog_filter_pages->getFilterDescriptions($this->request->get['filter_group_id']);
		// } else {
		// 	$data['filters'] = array();
		// }

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/filter_pages_form', $data));
	}

	// TODO
	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/filter_pages')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		// Это переделать, чтобы не было совпадающих урлов
		// Смотри в контроллере категории

		// foreach ($this->request->post['filter_group_description'] as $language_id => $value) {
		// 	if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 64)) {
		// 		$this->error['group'][$language_id] = $this->language->get('error_group');
		// 	}
		// }

		// if (isset($this->request->post['filter'])) {
		// 	foreach ($this->request->post['filter'] as $filter_id => $filter) {
		// 		foreach ($filter['filter_description'] as $language_id => $filter_description) {
		// 			if ((utf8_strlen($filter_description['name']) < 1) || (utf8_strlen($filter_description['name']) > 64)) {
		// 				$this->error['filter'][$filter_id][$language_id] = $this->language->get('error_name');
		// 			}
		// 		}
		// 	}
		// }

		return !$this->error;
	}


	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/filter_pages')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	// Переделать на поиск категории по имени
	// public function autocomplete() {
	// 	$json = array();

	// 	if (isset($this->request->get['filter_name'])) {
	// 		$this->load->model('catalog/filter_pages');

	// 		$filter_data = array(
	// 			'filter_name' => $this->request->get['filter_name'],
	// 			'start'       => 0,
	// 			'limit'       => 20
	// 		);
	// 		// Эту функцию я заккоментировал в 
	// 		// C:\OpenServer\domains\test9.loc\www\admin\model\catalog\filter_pages.php
	// 		// Ее надо будет заменить на поиск категорий по имени
	// 		$filters = $this->model_catalog_filter_pages->getFilters($filter_data);

	// 		foreach ($filters as $filter) {
	// 			$json[] = array(
	// 				'filter_id' => $filter['filter_id'],
	// 				'name'      => strip_tags(html_entity_decode($filter['group'] . ' &gt; ' . $filter['name'], ENT_QUOTES, 'UTF-8'))
	// 			);
	// 		}
	// 	}

	// 	$sort_order = array();

	// 	foreach ($json as $key => $value) {
	// 		$sort_order[$key] = $value['name'];
	// 	}

	// 	array_multisort($sort_order, SORT_ASC, $json);

	// 	$this->response->addHeader('Content-Type: application/json');
	// 	$this->response->setOutput(json_encode($json));
	// }
}