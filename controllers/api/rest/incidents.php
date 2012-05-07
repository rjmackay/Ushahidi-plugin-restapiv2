<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Incidents Controller - API Controller for Incidents
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

class Incidents_Controller extends Rest_Controller {
	
	public function __construct()
	{
		parent::__construct();
		
		// Check auth here
		if( ! $this->_login_admin())
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
				echo json_encode($this->get_incidents_array());
			break;

			case "POST":
				// Code to add new incident
				if (! $this->admin)
					$this->rest_error(401);
				
			break;
			
			case "PUT":
				// Don't want replacing all incidents
				$this->rest_error(501);
				
			break;
			
			case "DELETE":
				// Don't want deleting all incident
				$this->rest_error(501);
				
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
				echo json_encode($this->get_incidents_array($id));
			break;

			case "POST":
				// Not sure what this would do? (maybe chain this for add categories etc)
				$this->rest_error(501);
				
			break;
			
			case "PUT":
				// Code to replace / update incident
				if (! $this->admin)
					$this->rest_error(401);
				
			break;
			
			case "DELETE":
				if (! $this->admin)
					$this->rest_error(401);
			
				$incident = ORM::factory('incident',$id);
				if ($incident->loaded)
				{
					$incident->delete();
				}
				else
				{
					$this->rest_error(404);
				}

			break;
		}
	}
	
	private function get_incidents_array($id = FALSE)
	{
		if ($id)
		{
			$incident = ORM::factory('incident')->with('location')->with('incident_person')->with('user')->find($id);
			if (! $this->admin AND $incident->incident_active != 1)
			{
				$this->rest_error(401);
			}
			
			if (! $incident->loaded) {
				$this->rest_error(404);
			}
			
			//var_dump($incident);
			return $this->add_data_to_incident($incident->as_array(), $incident);
		}
		else
		{
			$this->_get_query_parameters();
			
			$incidents = ORM::factory('incident')->limit($this->limit)->orderby($this->order_field,$this->sort);
			
			// Only return approved reports for non admins
			if (! $this->admin)
			{
				$incidents->where('incident_active', 1);
			}
			
			$incidents = $incidents->find_all();
			
			
			$incidents_array = array();
			foreach ($incidents as $incident)
			{
				$incident_array = $incident->as_array();
				$incident_array = $this->add_data_to_incident($incident_array, $incident);
				
				$incidents_array[] = $incident_array;
			}
			
			return $incidents_array;
		}
	}
	
	private function add_data_to_incident($incident_array, $incident)
	{
		/*static $incident_type;
		if (!$incident_type)
		{
			$incident_type = ORM::factory('service')->select_list('id','service_name');
		}
		
		if ($incident_array['incident_id'])
		{
			$incident_array['incident_id'] = array($incident_array['incident_id'] => array(
				'api_url' => url::site(rest_controller::$api_base_url.'/incidents/'.$incident_array['incident_id']),
				'url' => url::site('/reports/view/'.$incident_array['incident_id'])
			));
		}*/
		
		// Add categories
		$incident_array['category'] = array();
		foreach ($incident->category as $category)
		{
			if ($category->category_visible AND ! $this->admin)
			{
				$incident_array['category'][] = $category->as_array();
			}
		}
		// Add location
		// @todo filter on location_visible
		$incident_array['location'] = $incident->location->as_array();
		
		// Add incident_person
		if ($this->admin)
		{
			$incident_array['incident_person'] = $incident->incident_person->as_array(); //@todo sanitize
		}
		else
		{
			// @todo check what should be public
			$incident_array['incident_person'] = array(
				'id' => $incident->incident_person->id,
				'person_first' => $incident->incident_person->person_first,
				'person_last' => $incident->incident_person->person_last
			);
		}
		
		// Add user?
		if ($this->admin)
		{
			$incident_array['user'] = $incident->user->as_array(); //@todo sanitize
		}
		else
		{
			// @todo check what should be public
			$incident_array['user'] = array(
				'id' => $incident->user->id,
				'name' => $incident->user->name,
				'username' => $incident->user->username
			);
		}
		
		// Add media?
		
		$incident_array['api_url'] = url::site(rest_controller::$api_base_url.'/incidents/'.$incident_array['id']);
		
		return $incident_array;
	}
}
	