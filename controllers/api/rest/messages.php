<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Messages Controller - API Controller for Messages
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

require(Kohana::find_file('controllers/api', 'rest'));

class Messages_Controller extends Rest_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		// Check auth here
		if ( ! $this->_login())
		{
			// @todo better error message
			return FALSE;
		}
	}
	
	public function index()
	{
		
		// Process search strings
		// ie by type
		
		switch (strtoupper(request::method()))
		{
			case "GET":
				echo json_encode($this->get_messages_array());
			break;

			case "POST":
				// Messages are read-only
				$this->rest_error(405);
				
			break;
			
			case "PUT":
				// Messages are read-only
				$this->rest_error(405);
				
			break;
			
			case "DELETE":
				// Messages are read-only
				$this->rest_error(405);
				
			break;
		}
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
				echo json_encode($this->get_messages_array($id));
			break;

			case "POST":
				$this->rest_error(501);
				
			break;
			
			case "PUT":
				// Messages are read-only
				$this->rest_error(405);
				
			break;
			
			case "DELETE":
				$message = ORM::factory('message',$id);
				if ($message->loaded)
				{
					$message->delete();
				}
				else
				{
					$this->rest_error(404);
				}

			break;
		}
	}
	
	private function get_messages_array($id = FALSE)
	{
		if ($id)
		{
			$message = ORM::factory('message',$id);
			
			if (! $message->loaded) {
				$this->rest_error(404);
			}
			
			//var_dump($message);
			return $this->add_data_to_message($message->as_array());
		}
		else
		{
			$this->_get_query_parameters();
			
			$messages = ORM::factory('message')->limit($this->limit)->orderby($this->order_field,$this->sort)->find_all();
			
			$messages_array = array();
			foreach ($messages as $message)
			{
				$message_array = $message->as_array();
				$message_array = $this->add_data_to_message($message_array);
				
				$messages_array[] = $message_array;
			}
			
			return $messages_array;
		}
	}
	
	private function add_data_to_message($message_array)
	{
		static $message_type;
		if (!$message_type)
		{
			$message_type = ORM::factory('service')->select_list('id','service_name');
		}
		
		$message_array['message_type'] = $message_type[$message_array['message_type']];
		if ($message_array['incident_id'])
		{
			$message_array['incident_id'] = array($message_array['incident_id'] => array(
				'api_url' => url::site(rest_controller::$api_base_url.'/incidents/'.$message_array['incident_id']),
				'url' => url::site('/reports/view/'.$message_array['incident_id'])
			));
		}
		
		$message_array['api_url'] = url::site(rest_controller::$api_base_url.'/messages/'.$message_array['id']);
		
		return $message_array;
	}
}
	