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

	public function __construct()
	{
		header("Cache-Control: no-cache, must-revalidate");
		// HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		// Date in the pass
		// currently always json
		header("Content-type: application/json; charset=utf-8");
	}

	public function index()
	{
		$this->rest_error(404);
	}

	public function rest_error($error, $message = FALSE, $page = FALSE)
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

	public function _login()
	{
		$auth = Auth::instance();

		// Is user previously authenticated?
		if ($auth->logged_in())
		{
			return $auth->get_user()->id;
		}
		else
		{
			//Get username and password
			if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
			{
				$username = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
				$password = filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);

				try
				{
					if ($auth->login($username, $password))
					{
						return $auth->get_user()->id;
					}
					else
					{
						$this->_prompt_login();
						return FALSE;
					}
				}
				catch (Exception $e)
				{
					$this->_prompt_login();
					return FALSE;
				}
			}

			//prompt user to login
			$this->_prompt_login();
			return FALSE;
		}
	}

	/**
	 * Prompts user to login.
	 *
	 * @param int user_id - The currently logged in user id to be passed as the
	 *                      realm value.
	 * @return void
	 */
	private function _prompt_login($user_id = 0)
	{
		header('WWW-Authenticate: Basic realm="' . Kohana::config('settings.site_name') . '"');
		$this->rest_error(401);
	}

}
