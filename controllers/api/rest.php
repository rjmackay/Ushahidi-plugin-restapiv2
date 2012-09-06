<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * REST Controller - Base REST API Controller
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

class Rest_Controller extends Controller {

	protected static $api_base_url = 'api/rest';
	protected static $api_version = 'restapi-0.1';
	
	protected $limit = 20;
	protected $order_field = 'id';
	protected $sort = 'DESC';
	protected $since_id = FALSE;
	
	protected $allowed_order_fields = array('id');
	protected $max_record_limit = 300;
	
	protected $data = array();

	public function __construct()
	{
		$this->db = Database::instance();
		$this->auth = Auth::instance();
		
		$this->_login();
		
		header("Cache-Control: no-cache, must-revalidate");
		// HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		// Date in the pass
		// currently always json
		header("Content-type: application/json; charset=utf-8");
		
		// Parse HTTP put data
		if (strtoupper(request::method()) == 'PUT' OR strtoupper(request::method()) == 'POST')
		{
			$this->data = json_decode( file_get_contents("php://input") );
		}
	}

	public function index()
	{
		$resources = array(
			'messages' => url::site(self::$api_base_url.'/messages'),
			'incidents' => url::site(self::$api_base_url.'/incidents'),
		);

		echo json_encode(array(
			'resources' => $resources,
			'ushahidi_version' => Kohana::config('settings.ushahidi_version'),
			'api_version' => self::$api_base_url,
			'db_version' => Kohana::config('settings.db_version'),
		));
	}

	protected function rest_error($error, $message = FALSE, $page = FALSE)
	{
		header("Content-type: application/json; charset=utf-8");

		if ($page === FALSE)
		{
			// Construct the page URI using Router properties
			$page = Router::$current_uri . Router::$url_suffix . Router::$query_string;
		}

		switch ($error)
		{
			case 404 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 File Not Found');
				$message = $message ? $message : Kohana::lang('restapi_error.error_404', $page);
				break;
			case 401 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
				header('WWW-Authenticate: Basic realm="Ushahidi API"');
				$message = $message ? $message : Kohana::lang('restapi_error.error_401', $page);
				break;
			case 501 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented');
				$message = $message ? $message : Kohana::lang('restapi_error.error_501', $page);
				break;
			case 400 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
				$message = $message ? $message : Kohana::lang('restapi_error.error_400', $page);
				break;
			case 405 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 405 Not Allowed');
				$message = $message ? $message : Kohana::lang('restapi_error.error_405', $page);
				break;
			case 409 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict');
				$message = $message ? $message : Kohana::lang('restapi_error.error_409', $page);
			default :
				header($_SERVER['SERVER_PROTOCOL'] . ' ' . $error);
				break;
		}
		echo json_encode(array('error' => $error, 'message' => $message));
		//throw new Exception($message);
		exit();
	}

	/**
	 * Check if user is logged in
	 * Special handling for API since we don't share sessions with the site
	 * and errors are returned as HTTP code + JSON
	 **/
	protected function _login()
	{
		// Is user previously authenticated?
		if ($this->auth->logged_in())
		{
			return TRUE;
		}
		//Get username and password
		elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
		{
			$username = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
			$password = filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);

			try
			{
				if ($this->auth->login($username, $password))
				{
					return TRUE;
				}
			}
			catch (Exception $e)
			{
			}
			
			// Return access denied
			$this->rest_error(401);
		}

		// Fail with a 401 if we admin=1
		// Bit of a hack but I haven't got a better way to handle it
		if (!empty($_GET['admin'])) $this->rest_error(401);
		
		// No auth details passed - return FALSE (not logged in)
		return FALSE;
	}
	
	/*
	 * Check if user is admin
	 **/
	protected function _login_admin()
	{
		if ( $this->auth->logged_in('login') AND $this->auth->get_user()->has_permission('admin_ui'))
		{
			return TRUE;
		}
		return FALSE;
	}

	protected function _get_query_parameters()
	{
		if (isset($_GET['limit']) 
				AND intval($_GET['limit']) > 0
				AND $_GET['limit'] <= $this->max_record_limit
			)
		{
			$this->limit = intval($_GET['limit']);
		}
		
		if (isset($_GET['orderfield']) AND in_array($_GET['orderfield'], $this->allowed_order_fields))
		{
			$this->order_field = $_GET['orderfield'];
		}
		
		if (isset($_GET['sort']))
		{
			if ($_GET['sort'] == 'ASC') $this->sort = 'ASC';
			elseif ($_GET['sort'] == 'DESC') $this->sort = 'DESC';
			elseif ($_GET['sort'] == 0) $this->sort = 'ASC';
			elseif ($_GET['sort'] == 1) $this->sort = 'DESC';
		}

		if (isset($_GET['since_id']))
		{
			$this->since_id = intval($_GET['since_id']);
		}
	}

}
