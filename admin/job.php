<?php
ini_set('display_errors',1);
error_reporting(E_ERROR);
date_default_timezone_set ("America/Los_Angeles");

$all_agents = ""; // Array of agent objects
$online_agents = array(); // Array of agent logins that are currently logged in
$agents = "";
$ended_timestamp_array = "";
$agents_chat_ts_array = "";
$headers = "";
$message_body = "";

require_once '../vendor/autoload.php';

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
		
		
		if(!is_null($ended_timestamp_array)){
			arsort($ended_timestamp_array); // Sort in descending order the chat end timestamps
			$agents_chat_ts_array[array_shift($ended_timestamp_array)] = $agent->login; //Place current agent last chat timestamp into array; timestamp is used as key
			$current_agent->recent_chat_ended = date("m/d/Y g:i A",array_shift($ended_timestamp_array));
			$current_agent->recent_chat_ended_ts = array_shift($ended_timestamp_array);			
		}else{
			$current_agent->recent_chat_ended = "";
			$current_agent->recent_chat_ended_ts = "";		
		}			
		$agents[] = $current_agent;
		unset($ended_timestamp_array);
	}
}

$current_datetime = new Datetime(date("m/d/Y g:i A",time()));

// Iterate through logged in agents and determine if they need to be logged out
foreach($agents as $agent){
	if($agent->status == "not accepting chats" && $agent->recent_chat_ended == ""){
		$scenario1_logout[] = $agent; // Not accepting chats AND never chatted before
	}elseif($agent->status == "not accepting chats" && $agent->recent_chat_ended != ""){
		// check to see the difference between current time and last chat ended
		$date1 = new DateTime($agent->recent_chat_ended);
		$interval = $current_datetime->diff($date1);
		if($interval->d > 0 || $interval->h > 0){ // if diffrence between current timestamp and last chat ended time is more than 1 hour or 1 day store in array
			$scenario2_logout[] = $agent;
		}	
		
	}
}

if(count($scenario1_logout) > 0){
	$message_body .= "<p>The following agents were logged out for having status set to Not Accepting Chats and agent has never chatted.</p>";
	foreach($scenario1_logout as $agent_logged_out){	
		$message_body .= $agent_logged_out->name . " " . $agent_logged_out->login . "<br/><br/>"; // Add agent information to notification text
		$user_updates = array("status"=>"offline");
		notify($agent_logged_out,1);
		//$result = $LiveChatAPI->agents->update($agent_logged_out->login, $user_updates); // Log agent out of Livechat system
	}
}
if(count($scenario2_logout) > 0){
	$message_body .= "<p>The following agents were logged out for having status set to Not Accepting Chats and agent's last chat ended over an hour ago.</p>";
	foreach($scenario2_logout as $agent_logged_out){	
		$message_body .= $agent_logged_out->name . " " . $agent_logged_out->login . "<br/><br/>"; // Add agent information to notification text
		$user_updates = array("status"=>"offline");
		notify($agent_logged_out,2);
		//$result = $LiveChatAPI->agents->update($agent_logged_out->login, $user_updates); // Log agent out of Livechat system
	}
}
print $message_body;

$number_online_agents = count($online_agents);
$notification_recipient = "milder.lisondra@jewsforjesus.org";
$headers .= 'To: Milder Lisondra <milder.lisondra@yahoo.com>' . "\r\n";
$headers .= 'From: Livechat Monitor <milder.lisondra@jewsforjesus.org>' . "\r\n";
$headers .= 'Cc: web@jewsforjesus.org' . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

$subject = "Number of currently logged in Livechat agents";
//$message_body .= "Number of agents logged in: " . $number_online_agents . "\r\n\r\n";
//foreach($agents as $agent){
	//$message_body .= $agent->name . " Last chat ended: " . $agent->recent_chat_ended  . "\r\n\r\n";	
//}
// Milder: uncomment once logic has been approved.
//if($number_online_agents < 5){
	//$message_body .= "\r\n" . "No agents need to be logged out at this time." . "\r\n";
//}

//ksort($agents_chat_ts_array);
//$user_updates = array("status"=>$status);
// Milder: uncomment line below to actually allow system to log off designated user
//$result = $LiveChatAPI->agents->update(array_shift($agents_chat_ts_array), $user_updates); 


//$message_body .= "Agent: " . array_shift($agents_chat_ts_array) . " would have been logged off automatically by the system.";
if(count($scenario1_logout) > 0){
	//mail($notification_recipient,$subject,$message_body, $headers);
}

/*
* notify
* Sends email notification to agent upon being logged out
* @param object $agent
*/
function notify($agent,$scenario = 1){
	$headers = 'From: Livechat Monitor <chatadmin@jewsforjesus.org>' . "\r\n";
	$headers .= 'Reply-To: web@jewsforjesus.org' . "\r\n";
	$headers .= 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";	
	$headers .= 'BCc: milder.lisondra@jewsforjesus.org' . "\r\n";
	$subject = "You have been logged out of Livechat";
	$message_body = "Hi " . $agent->name . ",<br/></br>";
	$message_body .= "Thanks for your help with LiveChat. ";
	if($scenario == 1){
		$message_body .= 'You were logged out automatically because your status was set to "Not Accepting Chats" and you have never chatted.';
	}else{
		$message_body .= 'You were logged out automatically because your status was set to "Not Accepting Chats" and your last chat ended over an hour ago.';
	}
	$message_body .= '<p>We hope LiveChat has been a good experience for you and if you feel this process can be improved, please write me at <a href="mailto:web@jewsforjesus.org">web@jewsforjesus.org</a>.</p>';
	$message_body .= "<p>NOTE: We try to keep our limited seats open for other missionaries and volunteers to be able to login and chat. ";
	$message_body .= "If you have the LiveChat app on your phone or computer set to start automatically when it boots or re-boots, please turn off that option if you don't plan to chat.";
	$message_body .= ' <a href="http://jforj.org/net/online-evangelism/livechat-tips-and-training/#logout">See tips on how to do this here.</a></p>';

	mail("web@jewsforjesus.org",$subject,$message_body, $headers);
}

function print_nicely($arg){
	print '<pre>';
	print_r($arg);
	print '</pre>';
	
}
?>