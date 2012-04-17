<?php

// allow other plugins to use service

// @todo Allow only services for which permission has been requested/granted

elgg_register_plugin_hook_handler('post', 'facebook_service', 'facebookservice_post');
elgg_register_plugin_hook_handler('viewnote', 'facebook_service', 'facebookservice_viewnote');
elgg_register_plugin_hook_handler('postnote', 'facebook_service', 'facebookservice_postnote');
elgg_register_plugin_hook_handler('viewwall', 'facebook_service', 'facebookservice_viewwall');
elgg_register_plugin_hook_handler('viewstatus', 'facebook_service', 'facebookservice_viewstatus');
elgg_register_plugin_hook_handler('viewfeed', 'facebook_service', 'facebookservice_viewfeed');
elgg_register_plugin_hook_handler('viewfeedgraph', 'facebook_service', 'facebookservice_viewfeedgraph');
elgg_register_plugin_hook_handler('viewcomment', 'facebook_service', 'facebookservice_viewcomment');
elgg_register_plugin_hook_handler('viewusername', 'facebook_service', 'facebookservice_viewusername');
elgg_register_plugin_hook_handler('viewlike', 'facebook_service', 'facebookservice_viewlike');
elgg_register_plugin_hook_handler('postcomment', 'facebook_service', 'facebookservice_postcomment');
elgg_register_plugin_hook_handler('postlike', 'facebook_service', 'facebookservice_postlike');
elgg_register_plugin_hook_handler('friendrequest','facebook_service','facebookservice_friendrequest');


/**
 * Post to a facebook users wall.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return bool
 */
function facebookservice_post($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');
    $site = elgg_get_site_entity();

    if(!$params['name']) {
	   $site_name = $site->name;
    }
    else {
	   $site_name = $params['name'];
    }

    if(!$params['logo']) {
	    $logo = elgg_get_site_url() .'_graphics/elgg_logo.png' ;
    }
    else {
	    $logo = $params['logo'] ;
    }

    if(!$params['link']) {
	   $link = elgg_get_site_url();
    }
    else {
	   $link = $params['link'];
    }

    $attachment =  array(
		'access_token' => $access_token,
		'message' => $params['message'],
		'name' => $site_name,
		'link' => $link,
		'description' => $params['description'],
		'picture' => $logo,
    );

    if (!($access_token && $target)) {
	return NULL;
    }

    $facebook = facebookservice_api();
    $facebook->api('/me/feed', 'POST', $attachment);

    return TRUE;
}


/**
 * Retrieve a facebook user's notes.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewnote($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    if(!$params['limit']) {
	   $limit = 10;
    }
    else {
	   $limit = $params['limit'];
    }

    $attachment =  array(
		'access_token' => $access_token,
		'limit' => $limit,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    $facebook = facebookservice_api();

    //you can also use the facebook uid($target) in the request in place of me

    $fbnotes = $facebook->api('/me/notes', 'GET', $attachment);
    return $fbnotes;
}

/**
 * Retrieve a facebook user's notes.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_postnote($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
		'access_token' => $access_token,
		'message' => $params['message'],
		'subject' => $params['subject'],
    );

    if (!($access_token && $target))
	{
		return NULL;
    }

    $facebook = facebookservice_api();

    //you can also use the facebook uid($target) in the request in place of me

    return $facebook->api('/me/notes', 'POST', $attachment);
}

/**
 * Retrieve a facebook user's wall.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewwall($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    if(!$params['limit']) {
	   $limit = 10;
    }
    else {
	   $limit = $params['limit'];
    }

    $attachment =  array(
		'access_token' => $access_token,
		'limit' => $limit,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    $facebook = facebookservice_api();

    //you can also use the facebook uid($target) in the request in place of me

    return $facebook->api('/me/feed', 'GET', $attachment);
}

/**
 * Retrieve a facebook user's statuses.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewstatus($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

	if(!$params['limit']) {
	   $limit = 10;
    }
    else {
	   $limit = $params['limit'];
    }

    //limit = 1 will give the latest status
    $attachment =  array(
		'access_token' => $access_token,
		'limit' => $limit,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    $facebook = facebookservice_api();

    //you can also use the facebook uid($target) in the request in place of me

    return $facebook->api('/me/statuses', 'GET', $attachment);
}

/**
 * Retrieve a facebook user's home feed.Uses fql(facebook query language) as it gives
 * a greater level of flexibility like filtering the feeds
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewfeed($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
		'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
		return NULL;
    }


    $facebook = facebookservice_api();

    //how would you like to filter your feed.Available options are by network,all or applications(Applications option returns feed from pages,applications,etc)
    $filter = $params['choice'];

    switch ($filter)
	{
		case "network":
			$fbhome = $facebook->api(array('method' => 'fql.query' , 'query'=> "SELECT post_id,viewer_id,source_id ,created_time,attachment,likes,comments,actor_id, target_id, message FROM stream WHERE filter_key in (SELECT filter_key FROM stream_filter WHERE uid = $target AND type = 'network')",'access_token' => $access_token,
			));
			break;
		case "application":
			$fbhome = $facebook->api(array('method'=>'fql.query','query'=> "SELECT post_id,viewer_id,source_id ,created_time,attachment,likes,comments,actor_id, target_id, message FROM stream WHERE filter_key in (SELECT filter_key FROM stream_filter WHERE uid = $target AND type = 'application')",'access_token' => $access_token,
			));
			break;
		case "newsfeed":
		default:
			$fbhome = $facebook->api(array('method'=>'fql.query','query'=> "SELECT post_id,viewer_id,source_id ,created_time,attachment,likes,comments,actor_id, target_id, message FROM stream WHERE filter_key in (SELECT filter_key FROM stream_filter WHERE uid = $target AND type = 'newsfeed')",'access_token' => $access_token,
			));
		break;
	}
	return $fbhome;
}

/**
 * Retrieve a facebook user's home feed using graph api.For more the powerful fql use the function above
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewfeedgraph($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');


    if(!$params['limit']) {
	   $limit = 10;
    }
    else {
	   $limit = $params['limit'];
    }

    $attachment =  array(
		'access_token' => $access_token,
		'limit' => $limit,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    //you can also use the facebook uid($target) in the request in place of me

    $facebook = facebookservice_api();
    return $facebook->api('/me/home', 'GET', $attachment);
}

/**
 * Retrieve facebook comments.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewcomment($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
		'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    //the post id.shouldn't be mistaken with the user's id
    $id = $params['id'];

    $facebook = facebookservice_api();
    return $facebook->api('/' .$id . '/comments', 'GET', $attachment);
}

/**
 * Post comment to facebook.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_postcomment($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
	'access_token' => $access_token,
	'message' => $params['message'],
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    //the post id.shouldn't be mistaken with the user's id
    $id = $params['id'];

    $facebook = facebookservice_api();
    return $facebook->api('/' .$id . '/comments', 'POST', $attachment);
}

/**
 * Retrieve a post likes on facebook.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewlike($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
		'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    //the post id or comment id.shouldn't be mistaken with the user's id
    $id = $params['id'];

    $facebook = facebookservice_api();
    return $facebook->api('/' .$id . '/likes', 'GET', $attachment);
}

/**
 * Like a post on facebook.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_postlike($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
		'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
		return NULL;
    }

    //the post id or comment id.shouldn't be mistaken with the user's id
    $id = $params['id'];

    $facebook = facebookservice_api();
    return $facebook->api('/' .$id . '/likes', 'POST', $attachment);
}

/**
 * Retrieve a facebook user's username.
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_viewusername($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
    	'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
	return NULL;
    }

    //the id of the facebook user's whose user name you want to retrieve
    $id = $params['id'];

    $facebook = facebookservice_api();
    return $facebook->api(array('method'=>'fql.query','query'=> "SELECT name FROM profile WHERE id = $id",'access_token' => $access_token,
    ));
}

/**
 * Retrieve a user's friendrequest on facebook.There isn't any graph api endpoint to retrieve a users friend request at the time of writing this code
 *
 * @param string $hook
 * @param string $entity_type
 * @param null $returnvalue
 * @param array $params
 * @return array
 */
function facebookservice_friendrequest($hook, $entity_type, $returnvalue, $params)
{
	$user_id = $params['userid'];
    $access_token = elgg_get_plugin_user_setting('access_token', $user_id, 'facebook_connect');
    $target = elgg_get_plugin_user_setting('uid', $user_id, 'facebook_connect');

    $attachment =  array(
	    'access_token' => $access_token,
    );

    if (!($access_token && $target)) {
	return NULL;
    }

    $facebook = facebookservice_api();
    return $facebook->api(array(	'method'=>'fql.query','query'=> "SELECT uid_from FROM friend_request WHERE uid_to=$target ",'access_token' => $access_token,
    ));
}
