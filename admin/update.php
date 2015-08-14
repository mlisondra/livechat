<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';

use LiveChat\Api\Client as LiveChat;
extract($_POST);
if($action == "update"){
	
	$LiveChatAPI = new LiveChat('web@jewsforjesus.org', '5e191e7817a1186db337627593cab804');
	$user_login = $user_login;
	/*
if($login_status == "not accepting chats"){
	$status = "offline";
}else{
	$status = "accepting chats";
}*/
	$user_updates = array("login_status"=>"not accepting chats","status"=>$status);
	$result = $LiveChatAPI->agents->update($user_login, $user_updates);
	print_r($result);

}