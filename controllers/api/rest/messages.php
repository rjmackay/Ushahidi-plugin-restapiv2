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

require(Kohana::find_file('controllers/api', 'rest', TRUE));

class Messages_Controller extends Rest_Controller {
		
	protected $allowed_order_fields = array('id','message_from','message_type','message_date');
	
	public function __construct()
	{
		parent::__construct();
		
		// Only admin users
		if ( ! $this->_login() OR ! $this->_login_admin() )
		{
			$this->rest_error(401);
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
			return $this->add_data_to_message($message->as_array(), $message);
		}
		else
		{
			$this->_get_query_parameters();
			
			$messages = ORM::factory('message')->
			  where('message IS NOT NULL')
			  ->limit($this->limit)
			  ->orderby($this->order_field,$this->sort);

			if ($this->since_id)
			{
				$messages->where('message.id >', $this->since_id);
			}

			$messages = $messages->find_all();
			
			$messages_array = array();
			foreach ($messages as $message)
			{
				$message_array = $message->as_array();
				$message_array = $this->add_data_to_message($message_array, $message);
				
				$messages_array[] = $message_array;
			}
			
			return $messages_array;
		}
	}
	
	private function add_data_to_message($message_array, $message)
	{
		static $services;
		if (!$services)
		{
			$services = ORM::factory('service')->select_list('id','service_name');
		}
		
		$message_array['updated_at'] = $message_array['message_date'];
		
		$message_array['message_service'] = null;
		if ($message_array['reporter_id'])
		{
			$message_array['reporter'] = $message->reporter->as_array();
			$message_array['reporter']['service_name'] = $services[$message->reporter->service_id];
			$message_array['message_service'] = $services[$message->reporter->service_id];
			// if message doesn't have location, try swapping reporter location
			if ($message->latitude == NULL AND $message->longitude == NULL AND $message->reporter->location->loaded)
			{
				$message_array['latitude'] = $message->reporter->location->latitude;
				$message_array['longitude'] = $message->reporter->location->longitude;
				$message_array['location_name'] = $message->reporter->location->location_name;
			}
			// format date in ISO standard
			$message_array['reporter']['reporter_date'] = $message_array['reporter']['reporter_date'] != null ? date('c',strtotime($message_array['reporter']['reporter_date'])) : null;
		}
		
		if ($message_array['incident_id'])
		{
			$message_array['incident'] = array(
				'api_url' => url::site(rest_controller::$api_base_url.'/incidents/'.$message_array['incident_id']),
				'url' => url::site('/reports/view/'.$message_array['incident_id'])
			);
			// Use incident added time if message has an incident
			$message_array['updated_at'] = $message->incident->incident_dateadd;
		}
		
		$message_array['api_url'] = url::site(rest_controller::$api_base_url.'/messages/'.$message_array['id']);
		// Format updated_at value
		$message_array['updated_at'] = date('c',strtotime($message_array['updated_at']));
		// format all dates in ISO standard
		$message_array['message_date'] = $message->message_date != null ? date('c',strtotime($message_array['message_date'])) : null;
		
		return $message_array;
	}
}
	
