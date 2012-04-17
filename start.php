<?php

elgg_register_event_handler('init', 'system', 'facebook_connect_init');

function facebook_connect_init()
{
	elgg_register_library('facebook', __DIR__ . "/vendors/facebookoauth/src/facebook.php");
	elgg_register_library('facebook_connect', __DIR__ . "/lib/facebook_connect.php");


	elgg_load_library('facebook_connect');

	elgg_extend_view('css/elgg', 'facebook_connect/css');

	// sign on with facebook
	if (facebook_connect_allow_sign_on_with_facebook()) {
		elgg_extend_view('login/extend', 'facebook_connect/login');
	}

	// register page handler
	elgg_register_page_handler('facebook_connect', 'facebook_connect_pagehandler');

	// register Walled Garden public pages
	elgg_register_plugin_hook_handler('public_pages', 'walled_garden', 'facebook_connect_public_pages');

	// @todo Move this to a separate plugin :/
    //require __DIR__ . '/lib/facebook_service.php';
}

/**
 * @return ElggPlugin
 */
function facebook_connect_get_plugin()
{
    static $plugin = null;
    if (! $plugin) {
        $plugin = elgg_get_plugin_from_id('facebook_connect');
    }
    return $plugin;
}

/**
 * @param array $page
 */
function facebook_connect_pagehandler($page)
{
	global $CONFIG;

	if (!isset($page[0])) {
		forward();
	}
	$_GET['session'] = $CONFIG->input['session'];
	switch ($page[0]) {
		case 'authorize':
			facebook_connect_authorize();
			break;
		case 'login':
			facebook_connect_login();
			break;
		default:
			forward();
			break;
	}
}

/**
 * Send password for new user who is registered using facebook connect
 *
 * @param $email
 * @param $name
 * @param $username
 * @param $password
 * @return bool
 */
function send_user_password_mail($email, $name, $username, $password)
{
	$site = elgg_get_site_entity();
	$email = trim($email);
	
	// send out other email addresses
	if (!is_email_address($email)) {
		return false;
	}

	$message = elgg_echo('facebook_connect:email:body', array(
        $name,
        $site->name,
        $site->url,
        $username,
        $email,
        $password,
        $site->name,
        $site->url
    ));

	$subject = elgg_echo('facebook_connect:email:subject', array($name));

	// create the from address
	$site = get_entity($site->guid);
	if (($site) && (isset($site->email))) {
		$from = $site->email;
	} else {
		$from = 'noreply@' . get_site_domain($site->guid);
	}

	elgg_send_email($from, $email, $subject, $message);	
}

/**
 * Register as public pages for walled garden.
 *
 * @param string $hook
 * @param string $type
 * @param array  $return_value
 * @param array  $params
 * @return array
 */
function facebook_connect_public_pages($hook, $type, $return_value, $params) {
	$return_value[] = 'facebook_connect/forward';
	$return_value[] = 'facebook_connect/login';

	return $return_value;
}
