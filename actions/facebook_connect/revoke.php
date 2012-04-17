<?php

//Remove facebook access for the currently logged in user.

// unregister user's access tokens
$plugin = facebook_connect_get_plugin();
$user_guid = elgg_get_logged_in_user_guid();

$plugin->unsetUserSetting('uid', $user_guid);
$plugin->unsetUserSetting('access_token', $user_guid);

system_message(elgg_echo('facebook_connect:revoke:success'));
forward('settings/plugins', 'facebook_connect');
