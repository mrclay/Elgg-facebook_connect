<?php
/**
 * Common library of functions used by Facebook Services.
 *
 * @package facebook_connect
 */

/**
 * Tests if the system admin has enabled Sign-On-With-Facebook
 *
 * @return bool
 */
function facebook_connect_allow_sign_on_with_facebook()
{
	if (elgg_get_plugin_setting('consumer_key', 'facebook_connect')
            && elgg_get_plugin_setting('consumer_secret', 'facebook_connect')) {
        return (elgg_get_plugin_setting('sign_on', 'facebook_connect') === 'yes');
	}
    return false;
}

/**
 * Log in a user with facebook.
 */
function facebook_connect_login()
{
	elgg_load_library('facebook');
	// sanity check
	if (! facebook_connect_allow_sign_on_with_facebook()) {
		forward();
	}
	$facebook = facebookservice_api();
	if (!$session = $facebook->getSession()) {
		forward();
	}

	// attempt to find user and log them in.
	// else, create a new user.
	$options = array(
		'type' => 'user',
		'plugin_user_setting_name_value_pairs' => array(
			'uid' => $session['uid'],
			'access_token' => $session['access_token'],
		),
		'plugin_user_setting_name_value_pairs_operator' => 'OR',
		'limit' => 0
	);

	$users = elgg_get_entities_from_plugin_user_settings($options);

	if ($users) {
		if (count($users) == 1 && login($users[0])) {
			system_message(elgg_echo('facebook_connect:login:success'));
			elgg_set_plugin_user_setting('access_token', $session['access_token'], $users[0]->guid);
			if (empty($users[0]->email)) {
				$data = $facebook->api('/me');
				$email = $data['email'];
				$user = get_entity($users[0]->guid);
				$user->email = $email;
				$user->save();
			}
		} else {
			system_message(elgg_echo('facebook_connect:login:error'));
		}
		forward();
	} else {
		// need facebook account credentials
		$data = $facebook->api('/me');

		// backward compatibility for stalled-development FBConnect plugin
		$user = FALSE;
		$facebook_users = elgg_get_entities_from_metadata(array(
			'type' => 'user',
			'metadata_name_value_pairs' => array(
				'name' => 'facebook_uid',
				'value' => $session['uid'],
			),
		));

		if (is_array($facebook_users) && count($facebook_users) == 1) {
			// convert existing account
			$user = $facebook_users[0];
			login($user);

			elgg_delete_metadata(array(
                'guid' => $user->get('guid'),
                'metadata_name' => 'facebook_uid',
                'limit' => 0,
            ));
            elgg_delete_metadata(array(
                'guid' => $user->get('guid'),
                'metadata_name' => 'facebook_controlled_profile',
                'limit' => 0,
            ));
		}

		// create new user
		if (!$user) {
			// check new registration allowed
			if (!facebook_connect_allow_new_users_with_facebook()) {
				register_error(elgg_echo('registerdisabled'));
				forward();
			}
			$userSave = 0;
			$email = $data['email'];
			$users = get_user_by_email($email);
			if (!$users) {
				// Elgg-ify facebook credentials
				$username = str_replace(' ', '', strtolower($data['name']));
				while (get_user_by_username($username))
				{
					$username = str_replace(' ', '', strtolower($data['name'])) . '_' . rand(1000, 9999);
				}
				$password = generate_random_cleartext_password();
				$name = $data['name'];
				$user = new ElggUser();
				$user->username = $username;
				$user->name = $name;
				$user->email = $email;
				$user->access_id = ACCESS_PUBLIC;
				$user->salt = generate_random_cleartext_password();
				$user->password = generate_user_password($user, $password);
				$user->owner_guid = 0;
				$user->container_guid = 0;
				$userSave = 1;
			} else {
				$user = $users[0];
			}

			if ($userSave) {
				if (!$user->save()) {
					register_error(elgg_echo('registerbad'));
					forward();
				}
				send_user_password_mail($email, $name, $username, $password);
				$forward = "profile/{$user->username}";
			} else {
				$forward = '';
			}
		}

		// set facebook services tokens
		elgg_set_plugin_user_setting('uid', $session['uid'], $user->guid);
		elgg_set_plugin_user_setting('access_token', $session['access_token'], $user->guid);

		// pull in facebook icon
		$url = 'https://graph.facebook.com/' . $session['uid'] .'/picture?type=large';
		facebook_connect_update_user_avatar($user, $url);

		// login new user
		if (login($user)) {
			system_message(elgg_echo('facebook_connect:login:success'));
		} else {
			system_message(elgg_echo('facebook_connect:login:error'));
		}
		forward($forward, 'facebook_connect');
	}
	// register login error
	register_error(elgg_echo('facebook_connect:login:error'));
	forward();
}

/**
 * Pull in the latest avatar from facebook
 * @param ElggUser $user
 * @param string $file_location
 * @return bool
 */
function facebook_connect_update_user_avatar(ElggUser $user, $file_location)
{
	$tempfile = elgg_get_data_path() . $user->getGUID() . 'img.jpg';
	$imgContent = file_get_contents($file_location);
	$fp = fopen($tempfile, "w");
	fwrite($fp, $imgContent);
	fclose($fp);
	$sizes = array(
		'topbar' => array(16, 16, TRUE),
		'tiny' => array(25, 25, TRUE),
		'small' => array(40, 40, TRUE),
		'medium' => array(100, 100, TRUE),
		'large' => array(200, 200, FALSE),
		'master' => array(550, 550, FALSE),
	);

	$filehandler = new ElggFile();
	$filehandler->owner_guid = $user->getGUID();
	foreach ($sizes as $size => $dimensions) {
		$image = get_resized_image_from_existing_file(
			$tempfile,
			$dimensions[0],
			$dimensions[1],
			$dimensions[2]
		);

		$filehandler->setFilename("profile/$user->guid$size.jpg");
		$filehandler->open('write');
		$filehandler->write($image);
		$filehandler->close();
	}

	// update user's icontime
	$user->icontime = time();
	return TRUE;
}

/**
 * User-initiated facebook authorization
  *
  * Callback action from facebook registration. Registers a single Elgg user with
  * the authorization tokens. Will revoke access from previous users when a
  * conflict exists.
 */
function facebook_connect_authorize()
{
	$facebook = facebookservice_api();
	if (!$session = $facebook->getSession()) {
		register_error(elgg_echo('facebook_connect:authorize:error'));
		forward('settings/plugins', 'facebook_connect');
	}
	// make sure no other users are registered to this facebook account.
	$options = array(
		'type' => 'user',
		'plugin_user_setting_name_value_pairs' => array(
			'uid' => $session['uid'],
			'access_token' => $session['access_token'],
		),
		'plugin_user_setting_name_value_pairs_operator' => 'OR',
		'limit' => 0
	);

	$users = elgg_get_entities_from_plugin_user_settings($options);

	if ($users) {
		foreach ($users as $user) {
			// revoke access
			elgg_unset_plugin_user_setting('uid', $user->getGUID());
			elgg_unset_plugin_user_setting('access_token', $user->getGUID());
		}
	}

	// register user's access tokens
	elgg_set_plugin_user_setting('uid', $session['uid']);
	elgg_set_plugin_user_setting('access_token', $session['access_token']);

	system_message(elgg_echo('facebook_connect:authorize:success'));
	forward('settings/plugins', 'facebook_connect');
}

/**
 * Returns the url to authorize a user
 *
 * @param string $return_url
 * @return string
 */
function facebook_connect_get_authorize_url($return_url='')
{
	if (!$return_url) {
		// default to login page
		$return_url = elgg_get_site_url() . 'facebook_connect/login';
	}
	$facebook = facebookservice_api();
	return $facebook->getLoginUrl(array(
		'next' => $return_url,
        'req_perms' => 'email',
	));
}

/**
 * @return Facebook
 */
function facebookservice_api()
{
	elgg_load_library('facebook');
	return new Facebook(array(
		'appId' => elgg_get_plugin_setting('consumer_key', 'facebook_connect'),
		'secret' => elgg_get_plugin_setting('consumer_secret', 'facebook_connect'),
	));
}

/**
 * Checks if this site is accepting new users.
 * Admins can disable manual registration, but some might want to allow
 * facebook-only logins.
 *
 * @return bool
 */
function facebook_connect_allow_new_users_with_facebook()
{
	$site_reg = elgg_get_config('allow_registration');
	$facebook_reg = elgg_get_plugin_setting('new_users');
	return ($site_reg || (!$site_reg && $facebook_reg == 'yes'));
}
