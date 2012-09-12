<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Categories Controller - API Controller for Categories
 *
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

require(Kohana::find_file('controllers/api', 'rest', TRUE));

class Categories_Controller extends Rest_Controller {
		
	protected $allowed_order_fields = array('id','category_title','category_position','parent_id');
	
	public function __construct()
	{
		parent::__construct();
		
		// Check auth here
		if( ! $this->_login_admin() )
		{
			$this->admin = FALSE;
		}
		else
		{
			$this->admin = TRUE;
		}
	}
	
	public function index()
	{
		
		// Process search strings
		// ie by type
		
		switch (strtoupper(request::method()))
		{
			case "GET":
				echo json_encode($this->get_categories_array());
			break;

			case "POST":
				if (! $this->admin)
					$this->rest_error(401);
				
				$this->rest_error(501);
				
			break;
			
			case "PUT":
				// Overwriting all categories not permitted
				$this->rest_error(405);
				
			break;
			
			case "DELETE":
				$this->rest_error(501);
				
			break;
		}
	}
	
	/** 
	 * Hack to return category tree via API
	 * Avoids building the tree on the client side
	 */
	public function tree()
	{
		echo json_encode(category::get_category_tree_data(FALSE, $this->admin));
	}
	
	public function single()
	{
		// Check auth here
		
		if (intval(Router::$arguments[0]))
		{
			$id = intval(Router::$arguments[0]);
		}
		else
		{
			throw new Kohana_Exception();
		}

		switch (strtoupper(request::method()))
		{
			case "GET":
				echo json_encode($this->get_categories_array($id));
			break;

			case "POST":
				// Not sure what this would do
				$this->rest_error(405);
				
			break;
			
			case "PUT":
				if (! $this->admin)
					$this->rest_error(401);
				
				$this->rest_error(501);
				
			break;
			
			case "DELETE":
				if (! $this->admin)
					$this->rest_error(401);
				
				$category = ORM::factory('category',$id);
				if ($category->loaded)
				{
					$category->delete();
				}
				else
				{
					$this->rest_error(404);
				}

			break;
		}
	}
	
	private function get_categories_array($id = FALSE)
	{
		if ($id)
		{
			$category = ORM::factory('category',$id);
			
			if (! $category->loaded) {
				$this->rest_error(404);
			}
			
			
			if (! $this->admin AND $category->category_visible != 1)
			{
				$this->rest_error(401);
			}
			
			//var_dump($category);
			return $this->add_data_to_category($category->as_array());
		}
		else
		{
			$this->_get_query_parameters();
			
			$categories = ORM::factory('category')->limit($this->limit)->orderby($this->order_field, $this->sort);
			
			// Only return approved reports for non admins
			if (! $this->admin)
			{
				$categories->where('category_visible', 1);
			}
			
			$categories = $categories->find_all();
			
			$categories_array = array();
			foreach ($categories as $category)
			{
				$category_array = $category->as_array();
				$category_array = $this->add_data_to_category($category_array);
				
				$categories_array[] = $category_array;
			}
			
			return $categories_array;
		}
	}
	
	private function add_data_to_category($category_array)
	{
		if ($category_array['parent_id'])
		{
			$category_array['parent_id'] = array($category_array['parent_id'] => array(
				'api_url' => url::site(rest_controller::$api_base_url.'/categories/'.$category_array['parent_id'])
			));
		}
		
		$category_array['api_url'] = url::site(rest_controller::$api_base_url.'/categories/'.$category_array['id']);
		
		$category_array['category_image'] = $category_array['category_image'] ? url::convert_uploaded_to_abs($category_array['category_image']) : $category_array['category_image'];
		$category_array['category_image_thumb'] = $category_array['category_image_thumb'] ? url::convert_uploaded_to_abs($category_array['category_image_thumb']) : $category_array['category_image_thumb'];
		
		// No date attached to categories so always now
		$category_array['updated_at'] = date_create()->format(DateTime::W3C);
		
		return $category_array;
	}
}
