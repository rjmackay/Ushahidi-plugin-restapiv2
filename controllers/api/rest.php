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
	
	protected $allowed_order_fields = array('id');
	protected $max_record_limit = 100;

	public function __construct()
	{
		$this->db = Database::instance();
		$this->auth = Auth::instance();
		
		// Reset the session - auth must be passed everytime
		$_SESSION = array();
		Session::instance()->set(Kohana::config('auth.session_key'), null);
		$this->_login();
		
		header("Cache-Control: no-cache, must-revalidate");
		// HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		// Date in the pass
		// currently always json
		header("Content-type: application/json; charset=utf-8");
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
				$message = $message ? $message : Kohana::lang('restapi_error.error_401', $page);
				break;
			case 501 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented');
				$message = $message ? $message : Kohana::lang('restapi_error.error_501', $page);
				break;
			case 405 :
				header($_SERVER['SERVER_PROTOCOL'] . ' 405 Not Allowed');
				$message = $message ? $message : Kohana::lang('restapi_error.error_405', $page);
				break;
			default :
				header($_SERVER['SERVER_PROTOCOL'] . ' ' . $error);
				break;
		}
		echo json_encode(array('error' => $error, 'message' => $message));
		//throw new Exception($message);
		exit();
	}

	protected function _login()
	{
		// Is user previously authenticated?
		if ($this->auth->logged_in())
		{
			return $this->auth->get_user()->id;
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
					return $this->auth->get_user()->id;
				}
			}
			catch (Exception $e)
			{
			}
			
			// Return access denied
			$this->rest_error(401);
		}

		// No auth details passed - return FALSE (not logged in)
		return FALSE;
	}
	
	/*
	 * Check if user is admin
	 **/
	protected function _login_admin()
	{
		if ( ! $this->auth->logged_in('login') OR $this->auth->logged_in('member'))
		{
			return FALSE;
		}
		return TRUE;
	}

	protected function _get_query_parameters()
	{
		if (isset($_REQUEST['limit']) 
				AND intval($_REQUEST['limit']) > 0
				AND $_REQUEST['limit'] <= $this->max_record_limit
			)
		{
			$this->limit = intval($_REQUEST['limit']);
		}
		
		if (isset($_REQUEST['orderfield']) AND in_array($_REQUEST['orderfield'], $this->allowed_order_fields))
		{
			$this->order_field = $_REQUEST['orderfield'];
		}
		
		if (isset($_REQUEST['sort']))
		{
			if ($_REQUEST['sort'] == 'ASC') $this->sort = 'ASC';
			elseif ($_REQUEST['sort'] == 'DESC') $this->sort = 'DESC';
			elseif ($_REQUEST['sort'] == 0) $this->sort = 'ASC';
			elseif ($_REQUEST['sort'] == 1) $this->sort = 'DESC';
		}
	}

}
