<?php if ( ! defined('BASEPATH')) exit('No direct access allowed');

class Design_model extends TI_Model {

    private $layouts = array();

	public function getLayouts() {
		$this->db->from('layouts');

		$query = $this->db->get();

		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

    /**
     * @return array
     */
    public function getBanners() {
		$this->db->from('banners');

		$query = $this->db->get();

		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

	public function getRoutes() {
		$this->db->from('uri_routes');

		$this->db->order_by('priority', 'ASC');

		$query = $this->db->get();
		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

	public function getLayout($layout_id) {
		$this->db->from('layouts');

		$this->db->where('layout_id', $layout_id);

		$query = $this->db->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
		}
	}

	public function getLayoutModules($layout_id) {
		$this->db->from('layout_modules');

		$this->db->where('layout_id', $layout_id);

		$query = $this->db->get();

        $result = array();

		if ($query->num_rows() > 0) {
            $result = $query->result_array();
		}

        return $result;
	}

    public function getLayoutRoutes($layout_id) {
		$this->db->from('layout_routes');

		$this->db->where('layout_id', $layout_id);

		$query = $this->db->get();

		$result = array();

		if ($query->num_rows() > 0) {
			$result = $query->result_array();
		}

		return $result;
	}

    public function getBanner($banner_id) {
        $this->db->from('banners');

        $this->db->where('banner_id', $banner_id);

        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            return $query->row_array();
        }
    }

    public function getRouteLayoutId($uri_route = '') {
        $layout_id = NULL;

        if ($uri_route !== '') {
            $this->db->from('layout_routes');
            $this->db->where('uri_route', $uri_route);

            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $row = $query->row_array('first');
                $layout_id = $row['layout_id'];
            }
        }

        return $layout_id;
    }

    public function getPageLayoutId($page_id = '') {
        $layout_id = NULL;

        if ($page_id !== '') {
            $this->db->from('pages');
            $this->db->where('page_id', $page_id);

            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $row = $query->row_array('first');
                $layout_id = $row['layout_id'];
            }
        }

        return $layout_id;
    }

    public function updateRoutes($routes = array()) {
		if (!empty($routes)) {
			$write_routes = array();

			$this->db->truncate('uri_routes');
			$priority = 1;
			foreach ($routes as $key => $value) {
				if (!empty($value['uri_route']) AND !empty($value['controller'])) {
					$write_routes[$priority] = $value;

					$this->db->set('uri_route', $value['uri_route']);
					$this->db->set('controller', $value['controller']);
					$this->db->set('priority', $priority);

					$this->db->insert('uri_routes');
					$priority++;
				}
			}

//			$this->writeRoutesFile($write_routes);

			if ($this->db->affected_rows() > 0) {
				return TRUE;
			}
		}
	}

	public function writeRoutesFile($write_routes = array()) {

		$filepath = IGNITEPATH . 'config/routes.php';
		$line = '';

		if ($fp = @fopen($filepath, FOPEN_READ_WRITE_CREATE_DESTRUCTIVE)) {

            $line .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct access allowed');\n\n";

            $line .= "$"."route['default_controller'] = 'home';\n";
            $line .= "$"."route[\"^(\".implode('|', array('home', 'menus', 'reserve', 'contact', 'checkout', 'maintenance', 'local', 'pages')).\")?$\"] = '$1';\n";
            $line .= "$"."route[\"^(\".implode('|', array('home', 'menus', 'reserve', 'contact', 'checkout', 'maintenance', 'local', 'pages')).\")?/(:any)$\"] = '$1';\n";
            $line .= "$"."route[\"^(\".implode('|', array('home', 'menus', 'reserve', 'contact', 'checkout', 'maintenance', 'local', 'pages')).\")?/(:any)$\"] = '$1/$2';\n";


            if (!empty($write_routes) AND is_array($write_routes)) {
				foreach ($write_routes as $key => $value) {
					$line .= "$"."route['". $value['uri_route'] ."'] = '". $value['controller'] ."';\n";
				}
        	}

			//$line .= "$"."route['(:any)'] = 'slug';\n\n";
			$line .= "$"."route['404_override'] = '';\n\n";

			$line .= "/* End of file routes.php */\n";
			$line .= "/* Location: ./system/tastyigniter/config/routes.php */";

			flock($fp, LOCK_EX);
			fwrite($fp, $line);
			flock($fp, LOCK_UN);
			fclose($fp);

			@chmod($filepath, FILE_WRITE_MODE);
		}
	}

	public function updateLayout($update = array()) {
		$query = FALSE;

		if (!empty($update['name'])) {
			$this->db->set('name', $update['name']);
		}

		if (!empty($update['layout_id'])) {
			$this->db->where('layout_id', $update['layout_id']);
			$query = $this->db->update('layouts');

            if (!empty($update['routes'])) {
                $query = $this->addLayoutRoutes($update['layout_id'], $update['routes']);
            }

            if (!empty($update['modules'])) {
                $query = $this->addLayoutModules($update['layout_id'], $update['modules']);
            }
        }

		return $query;
	}

	public function updateBanner($update = array()) {
		$query = FALSE;

		if (!empty($update['name'])) {
			$this->db->set('name', $update['name']);
		}

		if (!empty($update['type'])) {
			$this->db->set('type', $update['type']);
		}

		if (!empty($update['click_url'])) {
			$this->db->set('click_url', $update['click_url']);
		}

		if (!empty($update['language_id'])) {
			$this->db->set('language_id', $update['language_id']);
		}

		if (!empty($update['alt_text'])) {
			$this->db->set('alt_text', $update['alt_text']);
		}

		if (!empty($update['custom_code'])) {
			$this->db->set('custom_code', $update['custom_code']);
		}

		if (!empty($update['image_code'])) {
			$this->db->set('image_code', serialize($update['image_code']));
		}

		if (!empty($update['status'])) {
			$this->db->set('status', $update['status']);
		}

		if (!empty($update['banner_id'])) {
			$this->db->where('banner_id', $update['banner_id']);
			$query = $this->db->update('banners');
		}

		return $query;
	}

    public function addLayout($add = array()) {
		$query = FALSE;

		if (!empty($add['name'])) {
			$this->db->set('name', $add['name']);
		}

		if ($this->db->insert('layouts')) {
			$layout_id = $this->db->insert_id();

            if (!empty($add['routes'])) {
                $this->addLayoutRoutes($layout_id, $add['routes']);
            }

            if (!empty($add['modules'])) {
                $this->addLayoutModules($layout_id, $add['modules']);
            }

			$query = $layout_id;
		}

		return $query;
	}

    private function addLayoutRoutes($layout_id, $routes = array()) {
        $query = FALSE;
        $this->db->where('layout_id', $layout_id);
        $this->db->delete('layout_routes');

        if (is_array($routes)) {
            foreach ($routes as $route) {
                $this->db->set('layout_id', $layout_id);
                $this->db->set('uri_route', $route['uri_route']);
                $query = $this->db->insert('layout_routes');
            }

            return $query;
        }

        return $query;
    }

    private function addLayoutModules($layout_id, $modules = array()) {
        $query = FALSE;
        $this->db->where('layout_id', $layout_id);
        $this->db->delete('layout_modules');

        if (is_array($modules)) {
            foreach ($modules as $priority => $module) {
                if (!empty($module) AND is_array($module)) {
                    $this->db->set('layout_id', $layout_id);
                    $this->db->set('module_code', $module['module_code']);
                    $this->db->set('position', $module['position']);
                    $this->db->set('priority', !empty($module['priority']) ? $module['priority'] : $priority);
                    $this->db->set('status', $module['status']);
                    $query = $this->db->insert('layout_modules');
                }
            }

            return $query;
        }

        return $query;
    }

    public function addBanner($add = array()) {
		$query = FALSE;

		if (!empty($add['name'])) {
			$this->db->set('name', $add['name']);
		}

		if (!empty($add['type'])) {
			$this->db->set('type', $add['type']);
		}

		if (!empty($add['click_url'])) {
			$this->db->set('click_url', $add['click_url']);
		}

		if (!empty($add['language_id'])) {
			$this->db->set('language_id', $add['language_id']);
		}

		if (!empty($add['alt_text'])) {
			$this->db->set('alt_text', $add['alt_text']);
		}

		if (!empty($add['custom_code'])) {
			$this->db->set('custom_code', $add['custom_code']);
		}

		if (!empty($add['image_code'])) {
			$this->db->set('image_code', serialize($add['image_code']));
		}

		if (!empty($add['status'])) {
			$this->db->set('status', $add['status']);
		}
		if ($this->db->insert('banners')) {
			$banner_id = $this->db->insert_id();

			$query = $banner_id;
		}

		return $query;
	}

    public function deleteLayout($layout_id) {
		$this->db->where('layout_id', $layout_id);
		$this->db->delete('layouts');

		$this->db->where('layout_id', $layout_id);
		$this->db->delete('layout_routes');

		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}

    public function deleteBanner($banner_id) {
		$this->db->where('banner_id', $banner_id);
		$this->db->delete('banners');

		if ($this->db->affected_rows() > 0) {
			return TRUE;
		}
	}
}

/* End of file design_model.php */
/* Location: ./system/tastyigniter/models/design_model.php */