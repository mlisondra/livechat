<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use LiveChat\Api\Client as LiveChat;

$LiveChatAPI = new LiveChat('web@jewsforjesus.org', '5e191e7817a1186db337627593cab804');

$agents = $LiveChatAPI->agents->get();
foreach($agents as $agent){
	if($agent->status != 'offline'){
		$online_agents[] = $agent->login;
	}
}

foreach($online_agents as $current_agent){
	$agent = $LiveChatAPI->agents->get($current_agent->login); 
	$job_title = "";
	if(isset($agent->job_title)){
		$job_title = $agent->job_title;
	}
	$individual_agents[] = $agent;
}
print(count($individual_agents));
extract($individual_agents);
//print '<pre>';
print_r($individual_agents);
//print '</pre>';
?>
<html>
<head>
<style type="text/css">
table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
}
th, td {
    padding: 15px;
}

.agent_name, .agent_login,.agent_last_logout,.agent_status,div.col_title {
	float:left;
	width:250px;
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
	width: 1400px;
	border:none;
}
</style>
</head>
<body>
<div class="container">
	<div class="col_title">Name</div>
	<div class="col_title">Email/Login</div>
	<div class="col_title">Status</div>
	<div class="col_title">Last Logout</div>
	
<?php foreach($individual_agents as $curr_agent){ ?>
	<div id="<?php print $curr_agent->login; ?>">
		<div class="agent_name"><?php print $curr_agent->name; ?></div>
		<div class="agent_login"><?php print $curr_agent->login; ?></div>
		<div class="agent_status" title="Click to log agent off"><?php print $curr_agent->status; ?></div>
		<div class="agent_last_logout"><?php print date("m/d/Y g:i A (e)",$curr_agent->last_logout); ?></div>
	</div>	
<?php } ?>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script>
	$(document).ready(function(){
		$(".agent_login_status").click(function(){
			//console.log($(this).parent().attr("id"));
			var user_login = $(this).parent().attr("id");
			var login_status = $(this).html();
			if(login_status == "not accepting chats"){
				login_status = "accepting chats";
			}else{
				login_status = "not accepting chats";
			}
			
				$.post("update.php",{"action":"update","user_login":user_login,"login_status":login_status},function(data){
					//console.log(data);
					location.reload();
				});
				
			//}
		});
	});
</script>
</body>
</html>