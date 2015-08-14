<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
date_default_timezone_set ("America/Los_Angeles");

$all_agents = ""; // Array of agent objects
$online_agents = array(); // Array of agent logins that are currently logged in
$agents = "";
$ended_timestamp_array = "";
$agents_chat_ts_array = "";
$headers = "";
$message_body = "";

require_once __DIR__ . '/vendor/autoload.php';

use LiveChat\Api\Client as LiveChat;

$LiveChatAPI = new LiveChat('web@jewsforjesus.org', '5e191e7817a1186db337627593cab804'); // New api object

$all_agents = $LiveChatAPI->agents->get(); // Retrieve all agents
foreach($all_agents as $agent){ //Store only agents that are currently online into new array
	
	if($agent->status != 'offline'){ 
		$online_agents[] = $agent->login;
		$current_agent = $LiveChatAPI->agents->get($agent->login); // Retrieve specific agent information
		$current_agent_chats = $LiveChatAPI->chats->get($params = array("agent"=>$agent->login)); // Retrieve chats for specific agent
		foreach($current_agent_chats->chats as $chat){ // Store chat endtimes for current agent
			$ended_timestamp_array[] = $chat->ended_timestamp;
		}
		//print count($ended_timestamp_array);
		if(count($ended_timestamp_array) > 0){
			arsort($ended_timestamp_array); // Sort in descending order the chat end timestamps
			if(!empty(array_shift($ended_timestamp_array))){
				$agents_chat_ts_array[array_shift($ended_timestamp_array)] = $agent->login; //Place current agent last chat timestamp into array; timestamp is used as key
			}
			
			$current_agent->recent_chat_ended = date("m/d/Y g:i A (T)",array_shift($ended_timestamp_array));	
		}
		
		
			
		$agents[] = $current_agent;
		unset($ended_timestamp_array);	
	}
}
$number_online_agents = count($online_agents);
$notification_recipient = "milder.lisondra@jewsforjesus.org";
$headers .= 'To: Milder Lisondra <milder.lisondra@yahoo.com>' . "\r\n";
$headers .= 'From: Livechat Monitor <milder.lisondra@jewsforjesus.org>' . "\r\n";
//$headers .= 'Cc: birthdayarchive@example.com' . "\r\n";
//$headers .= 'Bcc: birthdaycheck@example.com' . "\r\n";

$subject = "Number of currently logged in Livechat agents";
$message_body .= "Number of agents logged in: " . $number_online_agents . "\r\n";
foreach($agents as $agent){
	$message_body .= $agent->name . "\r\n";	
}
//if($number_online_agents < 5){
	//$message_body .= "\r\n" . "No agents need to be logged out at this time." . "\r\n";
//}

//print '<pre>'; print_r($agents); print '</pre>';
print '<pre>';
print_r($agents_chat_ts_array);
ksort($agents_chat_ts_array);
print_r($agents_chat_ts_array);
print array_shift($agents_chat_ts_array);
print '</pre>';

//$message_body .= "Agent: " . array_shift($agents_chat_ts_array) . " would have been deleted.";
mail($notification_recipient,$subject,$message_body, $headers);
?>
