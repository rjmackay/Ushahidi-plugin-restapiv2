<?php defined('SYSPATH') or die('No direct script access.');
/**
 * RestApi Hook - Load All Events
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class rest {
	
	/**
	 * Registers the main event add method
	 */
	public function __construct()
	{
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));
		Event::add('ushahidi_action.config_routes', array($this, '_routes'));
	}
	
	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
	}
	
	/*
	 * Modify custom routes
	 */
	public function _routes()
	{
		// Add custom routing for appcache file
		Event::$data['api/rest/messages/([0-9]+)'] = 'api/rest/messages/single/$1';
		Event::$data['api/rest/incidents/([0-9]+)'] = 'api/rest/incidents/single/$1';
	}

}
new rest;
