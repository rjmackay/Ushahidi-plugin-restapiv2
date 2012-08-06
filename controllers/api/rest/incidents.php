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

require(Kohana::find_file('controllers/api', 'rest', TRUE));

class Incidents_Controller extends Rest_Controller {
		
	protected $allowed_order_fields = array('id','incident_title','incident_date','incident_verified');
	
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
				echo json_encode($this->get_incidents_array());
			break;

			case "POST":
				// Code to add new incident
				if (! $this->admin)
					$this->rest_error(401);
				
				// Make sure we don't have an incident id
				$this->data->id = null;
				$this->data->location_id = null;
				
				echo json_encode($this->save_incident($this->data));
				
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
				
				$incident = ORM::factory('incident')->find($id);
				if (! $incident->loaded) {
					$this->rest_error(404);
				}
				
				// Only admin's allowed to approve/verify
				if (! $this->admin)
					$this->rest_error(401);

				switch ($this->data->action)
				{
					case 'activate':
						$incident->incident_active = 1;
						break;
					case 'deactivate':
						$incident->incident_active = 0;
						break;
					case 'verify':
						$incident->incident_verified = 1;
						break;
					case 'unverify':
						$incident->incident_verified = 0;
						break;
				}
				$incident->save();
				reports::verify_approve($incident);
				
				echo json_encode($this->get_incidents_array($id));
			break;
			
			case "PUT":
				// Code to replace / update incident
				if (! $this->admin)
					$this->rest_error(401);
				
				// If no id posted (ie. from backbone.offline)
				// Use ID form URL
				if (!isset($this->data->id))
				{
					$this->data->id = $id;
				}
				
				// check id == url id
				if ($this->data->id != $id)
				{
					$errors = array(Kohana::lang('restapi_error.incident_id_mismatch'));
					$this->rest_error(400, $errors);
				}
				
				echo json_encode($this->save_incident($this->data));
				
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
			if (! $incident->loaded) {
				$this->rest_error(404);
			}
			
			if (! $this->admin AND $incident->incident_active != 1)
			{
				$this->rest_error(401);
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
			// Only include visible categories unless we're an admin
			if ($this->admin OR $category->category_visible)
			{
				$category_data = $category->as_array();
				$category_data['category_image'] = $category_data['category_image'] ? url::convert_uploaded_to_abs($category_data['category_image']) : $category_data['category_image'];
				$category_data['category_image_thumb'] = $category_data['category_image_thumb'] ? url::convert_uploaded_to_abs($category_data['category_image_thumb']) : $category_data['category_image_thumb'];
				
				$category_data['api_url'] = url::site(rest_controller::$api_base_url.'/categories/'.$category_data['id']);
				$incident_array['category'][] = $category_data;
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
			unset($incident_array['user']['password']);
			unset($incident_array['user']['code']);
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
		$incident_array['media'] = array();
		foreach ($incident->media as $media)
		{
			// Only include visible categories unless we're an admin
			if ($this->admin OR $media->media_active)
			{
				$media_data = $media->as_array();
				if ($media->media_link AND ! valid::url($media->media_link))
				{
					$media_data['media_link'] = url::convert_uploaded_to_abs($media_data['media_link']);
					$media_data['media_medium'] = url::convert_uploaded_to_abs($media_data['media_medium']);
					$media_data['media_thumb'] = url::convert_uploaded_to_abs($media_data['media_thumb']);
				}
				$incident_array['media'][] = $media_data;
			}
		}
		
		
		// Initialize custom field array - only supporting default form
		$incident_array['custom_field'] = customforms::get_custom_form_fields($incident_array['id'], 1, true);
		
		$incident_array['api_url'] = url::site(rest_controller::$api_base_url.'/incidents/'.$incident_array['id']);
		
		$incident_array['updated_at'] = $incident->incident_datemodify == null ? $incident->incident_dateadd : $incident->incident_datemodify;
		$incident_array['updated_at'] = date('c',strtotime($incident_array['updated_at']));
		
		return $incident_array;
	}

/**
	 * The actual reporting
	 *
	 * @return int
	 */
	private function save_incident($data)
	{
		// Convert time once, so we use the same time() call for all fields
		$time = isset($data->incident_date) ? strtotime($data->incident_date) : time();
		
		// Mash data into format expected by reports helper
		$post = array(
			'location_id' => isset($data->location_id) ? $data->location_id : null,
			'incident_id' => isset($data->id) ? $data->id : (isset($data->sid) ? $data->sid : null),
			'incident_title' => isset($data->incident_title) ? $data->incident_title : null,
			'incident_description' => isset($data->incident_description) ? $data->incident_description : null,
			'incident_date' => date('m/d/Y', $time),
			'incident_hour' => date('h', $time),
			'incident_minute' => date('i', $time),
			'incident_ampm' => date('a', $time),
			'latitude' => isset($data->location->latitude) ? $data->location->latitude : null,
			'longitude' => isset($data->location->longitude) ? $data->location->longitude : null,
			'location_name' => isset($data->location->location_name) ? $data->location->location_name : null,
			'country_id' => isset($data->location->country_id) ? $data->location->country_id : null,
			'incident_category' => array(),
			'incident_news' => array(),
			'incident_video' => array(),
			'incident_photo' => array(),
			'person_first' => isset($data->incident_person->person_first) ? $data->incident_person->person_first : '',
			'person_last' => isset($data->incident_person->person_last) ? $data->incident_person->person_last : '',
			'person_email' => isset($data->incident_person->person_email) ? $data->incident_person->person_email : '',
			'person_phone' => isset($data->incident_person->person_phone) ? $data->incident_person->person_phone : '',
			'incident_active' => isset($data->incident_active) ? $data->incident_active : null,
			'incident_verified' => isset($data->incident_verified) ? $data->incident_verified : null,
			'incident_zoom' => isset($data->incident_zoom) ? $data->incident_zoom : null,
			// message id? user id?
		);

		if (isset($data->category))
		{
			foreach ($data->category as $cat)
			{
				$post['incident_category'][] = $cat->id;
			}
		}

		if (isset($data->media))
		{
			foreach($data->media as $media)
			{
				if ($media->media_type == 2) // Video
				{
					$post['incident_video'][] = $media->media_link;
				}
				elseif ($media->media_type == 4) // News
				{
					$post['incident_news'][] = $media->media_link;
				}
			}
		}

		// Action::report_submit_admin - Report Posted
		Event::run('ushahidi_action.report_submit_admin', $post);

		// Test to see if things passed the rule checks
		if (reports::validate($post, TRUE))
		{
			// Yes! everything is valid
			$location_id = $post->location_id;

			// STEP 1: SAVE LOCATION
			$location = new Location_Model($location_id);
			reports::save_location($post, $location);

			// STEP 2: SAVE INCIDENT
			$incident_id = $post->incident_id;
			$incident = new Incident_Model($incident_id);
			reports::save_report($post, $incident, $location->id);

			// STEP 2b: Record Approval/Verification Action
			reports::verify_approve($incident);

			// STEP 2c: SAVE INCIDENT GEOMETRIES
			reports::save_report_geometry($post, $incident);

			// STEP 3: SAVE CATEGORIES
			reports::save_category($post, $incident);

			// STEP 4: SAVE MEDIA
			reports::save_media($post, $incident);

			// STEP 5: SAVE PERSONAL INFORMATION
			reports::save_personal_info($post, $incident);

			// Action::report_edit - Edited a Report
			Event::run('ushahidi_action.report_edit', $incident);

			// Success
			return $this->get_incidents_array($incident->id);
		}
		else
		{
			// populate the error fields, if any
			$errors = $post->errors('report');

			$this->rest_error(400, $errors);
		}
	}

}
	