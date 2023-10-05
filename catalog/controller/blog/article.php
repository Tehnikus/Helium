<?php


class ControllerBlogArticle extends Controller {
	private $error = array(); 
	
	public function index() {
		// Load models
		$this->load->model('blog/article');
		$this->load->model('tool/image');
		$this->load->language('blog/article');
		// Update article viewed number
		$this->model_blog_article->updateViewed($this->request->get['article_id']);
	
		// Breadcrumbs
		// TODO Maybe rewrite this so no heavy queries involved
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home'),			
			'separator' => false
		);
		
		$configblog_name = $this->config->get('configblog_name');
		if (!empty($configblog_name)) {
			$name = $this->config->get('configblog_name');
		} else {
			$name = $this->language->get('text_blog');
		}
		
		$data['breadcrumbs'][] = array(
			'text' => $name,
			'href' => $this->url->link('blog/latest')
		);
		
		$this->load->model('blog/category');
		if (isset($this->request->get['blog_category_id'])) {
			$blog_category_id = '';
			foreach (explode('_', $this->request->get['blog_category_id']) as $path_id) {
				if (!$blog_category_id) {
					$blog_category_id = $path_id;
				} else {
					$blog_category_id .= '_' . $path_id;
				}
				$category_info = $this->model_blog_category->getCategory($path_id);
				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text'      => $category_info['name'],
						'href'      => $this->url->link('blog/category', 'blog_category_id=' . $blog_category_id)
					);
				}
			}
		}
		
		
		if (isset($this->request->get['article_id'])) {
			$article_id = (int)$this->request->get['article_id'];
		} else {
			$article_id = 0;
		}
		
		$article_info = $this->model_blog_article->getArticle($article_id);
		
		if ($article_info) {
			
			$data['breadcrumbs'][] = array(
				'text' => $article_info['name'],
				'href' => $this->url->link('blog/article', 'article_id=' . $this->request->get['article_id'])
			);
			
			if ($article_info['meta_title']) {
				$this->document->setTitle($article_info['meta_title']);
			} else {
				$this->document->setTitle($article_info['name']);
			}
			
			if ($article_info['noindex'] <= 0 && $this->config->get('config_noindex_status')) {
				$this->document->setRobots('noindex,follow');
			} else {
				$this->document->setRobots('index,follow');
			}

			$this->document->setDescription($article_info['meta_description']);
			$this->document->setKeywords($article_info['meta_keyword']);
			$this->document->addLink($this->url->link('blog/article', 'article_id=' . $this->request->get['article_id']), 'canonical');


			if ($article_info['meta_h1']) {	
				$data['heading_title'] = $article_info['meta_h1'];
			} else {
				$data['heading_title'] = $article_info['name'];
			}

			// Language texts
			$data['text_select']          = $this->language->get('text_select');
			$data['text_write']           = $this->language->get('text_write');
			$data['text_login']           = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
			$data['text_loading']         = $this->language->get('text_loading');
			$data['text_note']            = $this->language->get('text_note');
			$data['text_share']           = $this->language->get('text_share');
			$data['text_wait']            = $this->language->get('text_wait');
			$data['button_cart']          = $this->language->get('button_cart');
			$data['button_wishlist']      = $this->language->get('button_wishlist');
			$data['button_compare']       = $this->language->get('button_compare');
			$data['entry_name']           = $this->language->get('entry_name');
			$data['entry_review']         = $this->language->get('entry_review');
			$data['entry_rating']         = $this->language->get('entry_rating');
			$data['entry_captcha']        = $this->language->get('entry_captcha');
			$data['share']                = $this->url->link('blog/article', 'article_id=' . $this->request->get['article_id']);
			$data['text_related']         = $this->language->get('text_related');
			$data['text_related_product'] = $this->language->get('text_related_product');
			$data['button_more']          = $this->language->get('button_more');
			$data['text_views']           = $this->language->get('text_views');
			$data['text_on']              = $this->language->get('text_on');
			$data['text_no_reviews']      = $this->language->get('text_no_reviews');
			// $data['button_send_review'] = $this->language->get('button_continue');
			



			$data['article_id'] = $this->request->get['article_id'];
			
			
			
			if ($this->config->get('configblog_review_guest') || $this->customer->isLogged()) {
				$data['review_guest'] = true;
			} else {
				$data['review_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}
			
			// Captcha
			if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'));
			} else {
				$data['captcha'] = '';
			}

			// $data['article_review'] = (int) $article_info['article_review'];
			$data['reviews_count']  = (int) $article_info['reviews'];
			$data['rating']         = (int) $article_info['rating'];
			$data['review_status'] = $this->config->get('configblog_review_status');
			$data['gstatus']        = (int) $article_info['gstatus'];
			$data['description']    = html_entity_decode($article_info['description'], ENT_QUOTES, 'UTF-8');

			// Main image
			$data['thumb']['link']   = $this->model_tool_image->resize($article_info['image'], $this->config->get('configblog_article_miniature_image_width'), $this->config->get('configblog_article_miniature_image_height'));
			$data['thumb']['width']  = $this->config->get('configblog_article_miniature_image_width');
			$data['thumb']['height'] = $this->config->get('configblog_article_miniature_image_height');

			

			
			
			// Related articles to this article
			$data['articles'] = array();
			$results = $this->model_blog_article->getArticleRelated($this->request->get['article_id']);
			foreach ($results as $result) {
				$image = [];
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('configblog_article_miniature_image_width'), $this->config->get('configblog_article_miniature_image_height'));
				} else {
					$image = $this->model_tool_image->resize('no_image.webp', $this->config->get('configblog_article_miniature_image_width'), $this->config->get('configblog_article_miniature_image_height'));
				}
				
				
				
				if ($this->config->get('configblog_review_status')) {
					$rating = round((float)$result['rating'], 2);
				} else {
					$rating = false;
				}
							
				$data['articles'][] = array(
					'article_id' 		=> $result['article_id'],
					'thumb'   			=> $image,
					'width' 			=> $this->config->get('configblog_article_miniature_image_width'),
					'height' 			=> $this->config->get('configblog_article_miniature_image_height'),
					'name'    	 		=> $result['name'],
					'description' 		=> utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('configblog_article_description_length')) . '..',
					'rating'     		=> $rating,
					'date_added'  		=> date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'viewed'      		=> $result['viewed'],
					'reviews_count'    	=> (int)$result['reviews'],
					'href'    	 		=> $this->url->link('blog/article', 'article_id=' . $result['article_id']),
				);
			}

			
			$data['products'] = array();
			
			// Product relateds to this article
			$product_list = [];
			$related_products = $this->model_blog_article->getArticleRelatedProduct($this->request->get['article_id']);
			foreach ($related_products as $product_id) {
				$product_list[] = $this->model_catalog_product->getProduct($product_id['product_id']);				
			}	
			$data['products'] = $this->model_catalog_product->prepareProductList($product_list, null);


			
			$data['download_status'] = $this->config->get('configblog_article_download');
			
			$data['downloads'] = array();
			
			$results = $this->model_blog_article->getDownloads($this->request->get['article_id']);
 
            foreach ($results as $result) {
                if (file_exists(DIR_DOWNLOAD . $result['filename'])) {
                    $size = filesize(DIR_DOWNLOAD . $result['filename']);
                    $i = 0;
                    $suffix = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
 
                    while (($size / 10024) > 1) {
                        $size = $size / 10024;
                        $i++;
                    }
 
                    $data['downloads'][] = array(
                        'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                        'name'       => $result['name'],
                        'size'       => round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i],
                        'href'       => $this->url->link('blog/article/download', '&article_id='. $this->request->get['article_id']. '&download_id=' . $result['download_id'])
                    );
                }
            } 
			
			$data['url'] = $this->url->link('blog/article', 'article_id=' . $this->request->get['article_id']);
			
			
			
			// Load controllers
			$data['column_left']    = $this->load->controller('common/column_left');
			$data['column_right']   = $this->load->controller('common/column_right');
			$data['content_top']    = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer']         = $this->load->controller('common/footer');
			$data['header']         = $this->load->controller('common/header');
			$data['reviews'] 		= $this->reviews();
			$data['reviews_count']  = (int) $article_info['reviews'];
			$data['rating']         = (int) $article_info['rating'];

			// print_r($data);
			// return;

			// Output result
			$this->response->setOutput($this->load->view('blog/article', $data));
		} else {
			// 404 page

			// Set proper breadcrumbs
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('product/product', '&product_id=' . $article_id)
			);

			// Set title
			$this->document->setTitle($this->language->get('text_error'));
			// Add 404 header
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			// Home link
			$data['continue'] = $this->url->link('common/home');

			// Language texts
			$data['heading_title']   = $this->language->get('text_error');
			$data['text_error']      = $this->language->get('text_error');
			$data['button_continue'] = $this->language->get('button_continue');

			// Load controllers
			$data['column_left']    = $this->load->controller('common/column_left');
			$data['column_right']   = $this->load->controller('common/column_right');
			$data['content_top']    = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer']         = $this->load->controller('common/footer');
			$data['header']         = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
	
	public function download() {

		$this->load->model('blog/article');

		if (isset($this->request->get['download_id'])) {
			$download_id = $this->request->get['download_id'];
		} else {
			$download_id = 0;
		}

		if (isset($this->request->get['article_id'])) {
			$article_id = $this->request->get['article_id'];
		} else {
			$article_id = 0;
		}

		$download_info = $this->model_blog_article->getDownload($article_id, $download_id);
		
		

		if ($download_info) {
			$file = DIR_DOWNLOAD . $download_info['filename'];
			$mask = basename($download_info['mask']);

			if (!headers_sent()) {
				if (file_exists($file)) {
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename="' . ($mask ? $mask : basename($file)) . '"');
					header('Content-Transfer-Encoding: binary');
					header('Expires: 0');
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					header('Content-Length: ' . filesize($file));

					// readfile($file, 'rb');
					readfile($file);

					

					exit;
				} else {
					exit('Error: Could not find file ' . $file . '!');
				}
			} else {
				exit('Error: Headers already sent out!');
			}
		} else {
			$this->redirect(HTTP_SERVER . 'index.php?route=account/download');
		}
	}
	
	public function reviews() {
		$this->load->language('blog/article');
		$this->load->model('blog/review');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$review_total = $this->model_blog_review->getTotalReviewsByArticleId($this->request->get['article_id']);
		$results = $this->model_blog_review->getReviewsByArticleId($this->request->get['article_id'], ($page - 1) * 5, 5);

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => nl2br($result['text']),
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('blog/article/review', 'article_id=' . $this->request->get['article_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();
		$data['reviews_count'] = sprintf($this->language->get('text_review_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		return $data;
	}

	public function review() {
		$data = array();
		$data['reviews'] = $this->reviews();
		$this->response->setOutput($this->load->view('common/review_grid', $data));
	}

	// Display review modal window 
	public function showReviewModal() {
		$data = [];
		$response = [];
		$this->load->language('blog/article');
		$data['entity_id'] = (int)$this->request->get['entity_id'];
		$data['type'] = 'blog/article';
		$response['dialog'] = $this->load->view('common/review_form', $data);
		$this->response->setOutput(json_encode($response));
	}
	
	public function sendReview() {
		$this->load->language('common/errors');

		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error']['name'] = $this->language->get('error_firstname');
			}

			if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error']['text'] = $this->language->get('error_review_text');
			}

			if (empty($this->request->post['rating']) || $this->request->post['rating'] < 0 || $this->request->post['rating'] > 5) {
				$json['error']['rating'] = $this->language->get('error_review_rating');
			}

			// Captcha
			if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

				if ($captcha) {
					$json['error']['captcha'] = $captcha;
				}
			}

			if (!isset($json['error'])) {
				$this->load->model('blog/review');

				$this->model_blog_review->addReview($this->request->get['entity_id'], $this->request->post);

				$json['dialog'] = $this->language->get('text_success_reviews');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
}
?>