<?php
class ControllerCommonHome extends Controller {
    private $id = 'd_blog_module';
    private $route = 'extension/d_blog_module/category';
    private $sub_versions = array('lite', 'light', 'free');
    private $mbooth = '';
    private $prefix = '';
    private $config_file = '';
    private $error = array();
    private $debug = false;
    private $setting = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        if (!isset($this->user)) {
            if (VERSION >= '2.2.0.0') {
                $this->user = new Cart\User($registry);
            } else {
                $this->user = new User($registry);
            }

            $this->load->model('extension/d_opencart_patch/load');
//            $this->theme = $this->config->get($this->config->get('config_theme') . '_directory');
        }
        //fix theme detection
		if (VERSION >= '3.0.0.0') {
			$this->theme = $this->config->get('theme_' . $this->config->get('config_theme') . '_directory');
		} elseif (VERSION >= '2.2.0.0') {
			$this->theme = $this->config->get($this->config->get('config_theme') . '_directory');
		} else {
			$this->theme = $this->config->get('config_template');
		}
		
        $this->load->language('extension/d_blog_module/category');

        $this->load->model('extension/module/d_blog_module');
        $this->load->model('extension/d_blog_module/category');
        $this->load->model('extension/d_blog_module/post');
        $this->load->model('tool/image');

        $this->session->data['d_blog_module_debug'] = $this->config->get('d_blog_module_debug');
        $this->config_file = $this->model_extension_module_d_blog_module->getConfigFile($this->id, $this->sub_versions);
        $this->setting = $this->model_extension_module_d_blog_module->getConfigData($this->id, $this->id . '_setting', $this->config->get('config_store_id'), $this->config_file);
    }
	public function index() {
		$this->document->setTitle($this->config->get('config_meta_title'));
		$this->document->setDescription($this->config->get('config_meta_description'));
		$this->document->setKeywords($this->config->get('config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->config->get('config_url'), 'canonical');
		}
        
        // Menu
		$this->load->model('catalog/category');

		$this->load->model('catalog/product');
        
        $data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			if ($category['top']) {
				// Level 2
				$children_data = array();

				$children = $this->model_catalog_category->getCategories($category['category_id']);

				foreach ($children as $child) {
					$filter_data = array(
						'filter_category_id'  => $child['category_id'],
						'filter_sub_category' => true
					);

					$children_data[] = array(
						'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
						'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}

				// Level 1
				$data['categories'][] = array(
					'name'     => $category['name'],
					'children' => $children_data,
					'column'   => $category['column'] ? $category['column'] : 1,
					'href'     => $this->url->link('product/category', 'path=' . $category['category_id']),
                    'image'    => $this->model_tool_image->resize($category['image'], 80, 80),
				);
			}
		}
        
        //slide
        $this->load->model('design/banner');
		$this->load->model('tool/image');
		
		$data['banners'] = array();

		$results = $this->model_design_banner->getBanner(7);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . $result['image'])) {
				$data['banners'][] = array(
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize($result['image'], 1920, 470)
				);
			}
		}
        
        //about
        $this->load->model('catalog/information');
        $information_info = $this->model_catalog_information->getInformation(4);
        $data['aboutus'] = $this->url->link('information/information&information_id=4', '', true);
        $data['title'] = $information_info['title'];
        $data['description_about'] = utf8_substr(trim(strip_tags(html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8'))), 0, 300 ) . '...';
        //product
        
//        $this->load->language('product/category');

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

//		$this->load->model('tool/image');

		if (isset($this->request->get['filter'])) {
			$filter = $this->request->get['filter'];
		} else {
			$filter = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['limit'])) {
			$limit = (int)$this->request->get['limit'];
		} else {
			$limit = $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');
		}
        
//        $path = 20;
//        $category_info = $this->model_catalog_category->getCategory(20);
//
//		if ($category_info) {
			$data['products'] = array();
            $category_id = 0;
			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', '&product_id=' . $result['product_id'] )
				);
			}

//		}
        
        $path = 60;
        $category_info = $this->model_catalog_category->get_category(60);

		if ($category_info) {
			$data['products_rand'] = array();
            $category_id = $category_info['category_id'];
			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_filter'      => $filter,
				'sort'               => $sort,
				'order'              => $order,
				'start'              => ($page - 1) * 10,
				'limit'              => 10
			);


			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products_rand'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $result['rating'],
					'href'        => $this->url->link('product/product', 'path=' . $path . '&product_id=' . $result['product_id'] )
				);
			}
		}
        
        //blog
        
        $page = 1;

        $category_id = $this->setting['category']['main_category_id'];

        $data['category_id'] = $category_id;

//        $data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
//        $data['text_categories'] = $this->language->get('text_categories');
//        $data['text_tags'] = $this->language->get('text_tags');
//        $data['text_empty'] = $this->language->get('text_empty');
//        $data['text_views'] = $this->language->get('text_views');
//        $data['text_review'] = $this->language->get('text_review');
//        $data['text_read_more'] = $this->language->get('text_read_more');
//        $data['button_continue'] = $this->language->get('button_continue');
//        $data['continue'] = $this->url->link('common/home', '', 'SSL');
        
        //posts
        $limit = 5;
        $data['posts'] = array();
        if ($category_id == $this->setting['category']['main_category_id']) {
            $filter_data = array('limit' => $limit, 'start' => ($page - 1) * $limit,);
        } else {
            $filter_data = array('filter_category_id' => $category_id, 'limit' => $limit, 'start' => ($page - 1) * $limit,);
        }
        $post_total = $this->model_extension_d_blog_module_post->getTotalPosts($filter_data);
        $posts = $this->model_extension_d_blog_module_post->getPosts($filter_data);

        $new_row = false;
        if ($posts) {
            $post_thumb = $this->setting['post_thumb'];
            $data['post_thumb'] = $post_thumb;


            foreach ($posts as $post) {

                $data['posts'][] = array(
                    'post'      => $this->load->controller('extension/d_blog_module/post/thumb', $post['post_id']),
                    'animate'   => $this->setting['post_thumb']['animate'],
                );
            }
        }
        $data['limit_post'] = count($posts);
        
        $data['news'] = $this->url->link('extension/d_blog_module/category', '', true);
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
//		$data['content_top'] = $this->load->controller('common/content_top');
//		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}
}
