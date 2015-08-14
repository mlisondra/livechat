<?php
ini_set('display_errors',1);
error_reporting('E_ALL');
//date_default_timezone_set ("America/Los_Angeles");

require_once '../vendor/autoload.php';

use LiveChat\Api\Client as LiveChat;

$LiveChatAPI = new LiveChat('web@jewsforjesus.org', '5e191e7817a1186db337627593cab804'); // New api object

$chats = $LiveChatAPI->chats->get();
print '<pre>';
foreach($chats as $key=>$chat){
	explode($chat);
	foreach($chat as $current_chat){
		print $current_chat->agents[0]->display_name; print "<br/>";
		print $current_chat->visitor->name; print "<br/>";
		print_r($current_chat);
	}
	//print_r($chat[$key]->type);
}

print '<pre>';