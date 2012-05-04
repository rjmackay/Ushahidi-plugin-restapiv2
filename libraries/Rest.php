<?php
/**
 * Rest Exception classes
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 *
 * Api_Controller
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Controllers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

/**
 * Creates a Page Not Found exception.
 */
class Rest_404_Exception extends Kohana_404_Exception {
	
	protected $template = 'restapi_error_page';
	
	protected $code = 'Resource not found';

	/**
	 * Set internal properties.
	 *
	 * @param  string  URL of page
	 * @param  string  custom error template
	 */
	public function __construct($page = FALSE)
	{
		if ($page === FALSE)
		{
			// Construct the page URI using Router properties
			$page = Router::$current_uri.Router::$url_suffix.Router::$query_string;
		}

		Exception::__construct(Kohana::lang('restapi_error.resource_not_found', $page));
	}

	/**
	 * Sends "File Not Found" headers, to emulate server behavior.
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		// Send the 404 header
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 File Not Found');
		header("Content-type: application/json; charset=utf-8");
	}

} // End Kohana 404 Exception

/**
 * Creates a Access Denied exception.
 */
class Rest_401_Exception extends Kohana_Exception {
	
	protected $template = 'restapi_error_page';
	
	protected $code = 'Unauthorized';

	/**
	 * Set internal properties.
	 *
	 * @param  string  URL of page
	 * @param  string  custom error template
	 */
	public function __construct($page = FALSE)
	{
		if ($page === FALSE)
		{
			// Construct the page URI using Router properties
			$page = Router::$current_uri.Router::$url_suffix.Router::$query_string;
		}

		Exception::__construct(Kohana::lang('restapi_error.unauthorized', $page));
	}

	/**
	 * Sends "File Not Found" headers, to emulate server behavior.
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		// Send the 401 header
		header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized');
		header("Content-type: application/json; charset=utf-8");
	}

} // End Kohana 404 Exception

/**
 * Creates a Not Implemented exception.
 */
class Rest_501_Exception extends Kohana_Exception {
	
	protected $template = 'restapi_error_page';
	
	protected $code = 'Not implemented';

	/**
	 * Set internal properties.
	 *
	 * @param  string  URL of page
	 * @param  string  custom error template
	 */
	public function __construct($page = FALSE)
	{
		if ($page === FALSE)
		{
			// Construct the page URI using Router properties
			$page = Router::$current_uri.Router::$url_suffix.Router::$query_string;
		}

		Exception::__construct(Kohana::lang('restapi_error.not_implemented', $page));
	}

	/**
	 * Sends "File Not Found" headers, to emulate server behavior.
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		// Send the 404 header
		header($_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented');
		header("Content-type: application/json; charset=utf-8");
	}

} // End Kohana 404 Exception