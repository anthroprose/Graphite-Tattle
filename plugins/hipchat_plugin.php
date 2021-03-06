<?php

plugin_listener('plugin_settings', 'hipchat_settings');
plugin_listener('send_methods', 'hipchat_send_methods');

function hipchat_settings() {
    return array(
		'hipchat_apikey' => array('friendly_name' => 'HipChat v2 API Key',
                                     'default' => '',
                                     'type' => 'string'),
		'hipchat_room' => array('friendly_name' => 'HipChat Room to Notify',
                                     'default' => '',
                                     'type' => 'integer'),
		'hipchat_warning_color' => array('friendly_name' => 'Hipchat Color for Warnings',
                                    'default' => 'yellow',
                                    'type' => 'string'),
		'hipchat_error_color' => array('friendly_name' => 'Hipchat Color for Errors',
                                    'default' => 'red',
                                    'type' => 'string'),
		'hipchat_ok_color' => array('friendly_name' => 'Hipchat Color for OK',
                                    'default' => 'green',
                                    'type' => 'string'),
		'hipchat_notify' => array('friendly_name' => 'Should this trigger Notifications?',
                                    'default' => true,
                                    'type' => 'bool')
	);
}

function hipchat_send_methods() {
      return array('hipchat_notify' => 'HipChat');
}

function hipchat_notify($check, $check_result) {
    	
    global $status_array;
    global $debug;
	
    $state = $status_array[$check_result->getStatus()];
	
    if (strtolower($state) == 'ok') {
    	
        $color = sys_var('hipchat_ok_color');
		
    } elseif (strtolower($state) == 'warning') {
    	
        $color = sys_var('hipchat_warning_color');
		
    } elseif (strtolower($state) == 'error') {
    	
        $color = sys_var('hipchat_error_color');
		
    }

	$url = $GLOBALS['TATTLE_DOMAIN'] . '/' . CheckResult::makeURL('list',$check_result);

    $data = array(
        'color' => $color,
        'notify' =>  sys_var('hipchat_notify'),
        'message_format' => 'html',
        'message' => "<b>" . $check->prepareName() . "</b><br />The check returned {$check_result->prepareValue()}<br />View Alert Details : <a href=\"" . $url . "\">" . $url . "</a>"
	);
	
	if ($debug) {
		
		$url = 'https://api.hipchat.com/v2/room?auth_token=' . sys_var('hipchat_apikey');
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		
		echo "\n\nRooms: " . curl_exec($c) . "\n\n";
		echo "\n\nURL: " . 'https://api.hipchat.com/v2/room/' . strtolower(sys_var('hipchat_room')) . '/notification?auth_token=' . sys_var('hipchat_apikey') . "\n\n";
		echo "\n\nData: " . print_r($data, true) . "\n\n";
		
	}

	$url = 'https://api.hipchat.com/v2/room/' . strtolower(sys_var('hipchat_room')) . '/notification?auth_token=' . sys_var('hipchat_apikey');
	$c = curl_init();
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HTTPHEADER, array(                                                                          
    	'Content-Type: application/json',                                                                                
    	'Content-Length: ' . strlen(json_encode($data)))
	);
	curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($data));

	$response = curl_exec($c);
	
	if ($response === false) {
		
    	echo "\n\nCurl error: " . curl_error($c) . "\n\n";
		
	}
	
	if ($debug) {
		
		echo "\n\nResponse: " . curl_getinfo($c, CURLINFO_HTTP_CODE)  . ' - ' . $response . "\n\n";
		
	}
	
}
