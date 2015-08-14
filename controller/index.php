<?php


require_once '../../livechat/vendor/autoload.php';

extract($_POST);
print $action;

if($action == "update"){
	use LiveChat\Api\Client as LiveChat;

	//$LiveChatAPI = new LiveChat('web@jewsforjesus.org', '5e191e7817a1186db337627593cab804');
	//$user_login = "milder.lisondra@jewsforjesus.org";
	//$user_updates = array("name"=>"Milder Hernando Lisondra","login_status"=>"accepting chats");
	//$result = $LiveChatAPI->agents->update($user_login, $user_updates);
	//print_r($result);
}