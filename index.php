<?php
header("Location: http://jewsforjesus.org/live-chat/");
ini_set('display_errors',1);
error_reporting('E_ERRORS');
date_default_timezone_set ("America/Los_Angeles");

$all_agents = ""; // Array of agent objects
$online_agents = array(); // Array of agent logins that are currently logged in
$agents = "";
$ended_timestamp_array = "";

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
			arsort($ended_timestamp_array); // Sort in descending order the chat end timestamps
		$current_agent->recent_chat_ended = date("m/d/Y h:i A", array_shift($ended_timestamp_array));
		$agents[] = $current_agent;
		unset($ended_timestamp_array);	
	}
}

?>
<html>
<head>
<style type="text/css">
body {
	font-size:14px;
}
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}
th, td {
    padding: 15px;
}

.agent_name, .agent_login,.agent_last_logout,.agent_status,.agent_last_chat_ended, div.col_title {
	float:left;
	width:200px;
	border: solid 1px;
	padding: 20px;
	
	
}
div.col_title {
	font-weight: bold;
}
.agent_status:hover {background-color: #E3E3E3; cursor: pointer;}

div.clear {
	clear:both;
}
div.container {
	width: 1600px;
	border:none;
}
</style>
</head>
<body>
<div><h2>Chat Agents currently logged in</h2></div>
<div class="container">
	<div class="col_title">Name/Title</div>
	<div class="col_title">Email/Login</div>
	<div class="col_title">Status</div>
	<div class="col_title">Last Chat Ended</div>
	<div class="col_title">Last Logout</div>
	<div class="clear"></div>
<?php foreach($agents as $agent){ ?>
	<div id="<?php print $agent->login; ?>">
		<div class="agent_name"><?php print $agent->name . ' / ' . $agent->job_title; ?></div>
		<div class="agent_login"><?php print $agent->login; ?></div>
		<div class="agent_status" title="Click to log agent off"><?php print $agent->status; ?> <a href="###">Log agent off</a></div>
		<div class="agent_last_chat_ended"><?php print $agent->recent_chat_ended; ?></div>
		<div class="agent_last_logout"><?php print date("m/d/Y h:i A",$agent->last_logout); ?></div>
		<div class="clear"></div>
	</div>	
<?php } ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script>
	$(document).ready(function(){
		$(".agent_status").click(function(){
			var user_login = $(this).parent().attr("id");
			var status = $(this).html();
			/*if(login_status == "not accepting chats"){
				login_status = "accepting chats";
			}else{
				login_status = "offline";
			}*/
			if(confirm('Are you sure?')){
				$.post("update.php",{"action":"update","user_login":user_login,"status":"offline"},function(data){					
					location.reload(); //reload page
				});
			}
				
		});
	});
</script>
</body>
</html>