<?php
class ControllerCommonMenu extends Controller {
	public function index() {
		$this->load->language('common/header');

		// Get cached data
		$cache_name = 'top_menu.'.(int)$this->config->get('config_language_id').'.'.(int)$this->config->get('config_store_id');
		$data = $this->cache->get($cache_name);
		if (!$data) {
			$data = $this->renderTree();
			$this->cache->set($cache_name, $data);
		}
		$output['categories'] = $data;
		
		// Return
		return $this->load->view('common/menu', $output);
	}

	// Get all categories and arrange them in parent => children tree
	public function renderTree() {
		$tree = array();
		// $flat = array();
		// DONE Maybe subquery to count products instead query in foreach loop?
		$query = $this->db->query("
			SELECT 
				c.category_id,
				cd.name,
				c.image,
				c.parent_id,
				c.column, 
				c.sort_order,
				(SELECT COUNT(pc.product_id) FROM ".DB_PREFIX."product_to_category pc WHERE pc.category_id = c.category_id) AS product_count,
				(SELECT COUNT(cc.category_id) FROM ".DB_PREFIX."category cc WHERE cc.parent_id = c.category_id) AS children_count

			FROM " . DB_PREFIX . "category c 
			LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id) 
			LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id) 
			WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' 
			AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' 
			AND c.status = '1' 
			ORDER BY c.sort_order, c.category_id"
		);

		$result = $query->rows;
		// print_r($this->buildFlat($result));
		// $tree = $this->buildTree($result);
		$tree = $this->buildFlat($result);
		// print_r($tree);
		// foreach ($result as $category) {
		// 	$category['href'] = $this->url->link('product/category', 'path=' . $category['category_id']);
		// 	$flat[$category['parent_id']][] = $category;
		// }
		return $tree;
	}

	public function buildFlat($elements) {
		$flat_tree = [];
		foreach ($elements as $key => $element) {
			$element['href'] = $this->url->link('product/category', 'path=' . $element['category_id']);
			$flat_tree[$element['parent_id']][] = $element;
		}
		return $flat_tree;
	}

	// // Build mutilevel tree from flat array
	public function buildTree(array $elements, $parentId = 0) {
		$branch = array();
		foreach ($elements as $key => $element) {
			// Create href for every category
			$element['href'] = $this->url->link('product/category', 'path=' . $element['category_id']);

			if ($element['parent_id'] == $parentId) {
				$children = $this->buildTree($elements, $element['category_id']);
				if ($children) {
					$element['children'] = $children;
				}
				$branch[] = $element;
			}
		}
		return $branch;
	}
}
