<?php
include('../config/config.php');
include('../config/utilities.php');
include('../phpmailer/class.phpmailer.php');
include('../ecommerce/lphp.php');

$messages_obj = new Messages();
$accounts_obj = new Accounts();
$flags_obj = new Flags();
$skills_obj = new Skills();
$mail_obj = new PHPMailer();
$sso = new Discourse_SSO("ee847916ff0dd2a2d34231a99427ac6451794621d7f0bd4f5d0d3aca8a531bda");
$mail_obj->IsHTML(true);
$action = $_REQUEST['action'];
$nonce = $_REQUEST['nonce'];
$templates = "../" . TEMPLATES . "/";
$logged_in_account = $_SESSION['user']['account_id'];

$fd_obj = new lphp();

switch($action){
	case "register":
		$result = $accounts_obj->check_email_exists($_POST['email']);
		if($result){
			$response['status'] =  "failed";
			$response['message'] = "email exists";
		}else{
			if(BETA_MODE == "true"){
				if($_POST['account_type'] == "business basic"){
					$_POST['account_type'] = "business pro";
				}else{
					$_POST['account_type'] = "pro";
				}
			}
			$account_created = $accounts_obj->_create($_POST);

			if($account_created['status'] != 'failed'){
				$response['status'] =  "success";
				$response['message'] =  "account created";
				$response['account_id'] = $account_created['account_id'];

				$email_args['recipient_email'] = $_POST['email'];
				$email_args['recipient_name'] = $_POST['first_name'] . ' ' . $_POST['last_name'];
				$email_args['sender_email'] = "registrations@talentearth.com";
				$email_args['sender_name'] = "TalentEarth Notification";
				$email_args['subject'] = "We have received your TalentEarth registration";
				if($_POST['account_type'] == "basic"){
					$template = file_get_contents($templates."registration_confirmation_email.tpl");
					$confirm_reg_page = "http://". $host_name ."/confirm_registration.php?id=";
					$welcome_message_text = TALENT_WELCOME_MESSAGE;
				}elseif($_POST['account_type'] == "business basic" || $_POST['account_type'] == "business pro"){
					$template = file_get_contents($templates."registration_confirmation_email_business.tpl");
					$confirm_reg_page = "http://". $host_name . "/confirm_registration_business.php?id=";
					$welcome_message_text = BUSINESS_WELCOME_MESSAGE;
				}else{
					$template = file_get_contents($templates."registration_confirmation_email.tpl");
					$confirm_reg_page = "http://" . $host_name . "/confirm_registration.php?id=";
					$welcome_message_text = TALENT_WELCOME_MESSAGE;
				}

				$template_data['confirm_registration_link'] = $confirm_reg_page . $account_created['account_id'] . "&token=" . $account_created['reg_token'] . "&account_type=" . urlencode($_POST['account_type']);
				$template_data['images_full_path'] = "http://" . $host_name . "/images/";

				$email_args['message_body'] = insert_content($template_data,$template);
				$email_args['bcc_group'] = array("registration@talentearth.com"); //optional
				send_notification($email_args); //config/utilities.php
				
				$thread_id = $messages_obj->create_thread(); //create the thread record and return it's id
		
				if($thread_id > 0){ // if the thread was created
					//insert the message record into the db. returns success or failed
					$message_insert = $messages_obj->_insert(0, "Status Update", $welcome_message_text, "work status", $thread_id);
					if($message_insert == "success"){
						
						$sender_relationship = $messages_obj->create_thread_relationship($thread_id, 0);
						$recieveer_relationship = $messages_obj->create_thread_relationship($thread_id, $account_created['account_id']);
					}
				}
				
				// create record for new user in privacy settings table
				$account_id = $account_created['account_id'];
				$accounts_obj->create_privacy_settings($account_id, $_POST['account_type']);
			}else{
				$response['status'] =  "failed";
				$response['message'] = "account could not be created";
			}
		}
		break;
	case "validate_registration": //inbound user has provided account id and system-generated token
		//ensure that account id and system-generated token combo is legitimate
		$args = array("account_id"=>$_POST['account_id'],"reg_token"=>$_POST['token']);
		$result = $accounts_obj->validate_registration($args);
    $response['status'] = $result;
    $response['is_affiliate'] = false;
    if ($result == 'valid')
    {
      $profile = $accounts_obj->_get((int)$_POST['account_id']);
      $response['is_affiliate'] = ($profile['affiliate_id'] > 0);
    }
		break;
	case "create_username_password";
		$_POST['username'] = $_POST['username2'];
		$_POST['password'] = $_POST['password_create'];
		$username_check = $accounts_obj->check_username_exists($_POST['username']);
		if($username_check){
			$response['status'] = 'failed';
			$response['message'] = 'username exists';
		}else{
			$response['status'] = 'success';
			if($accounts_obj->create_username_password($_POST)){
				$login_args = array("username"=>$_POST['username2'],"password"=>$_POST['password']);
				$auth_result = $accounts_obj->auth_user($login_args); //log user in
				if($auth_result['status'] == "success"){
					$user_info = $accounts_obj->_get($auth_result['account_id']);
					$_SESSION['user']['auth'] = $auth_result['status'];
					$_SESSION['user']['account_id'] = $user_info['id'];
					$_SESSION['user']['account_type'] = $user_info['account_type'];
					$_SESSION['user']['username'] = $user_info['username'];
					$_SESSION['user']['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
					$_SESSION['user']['first_name'] = $user_info['first_name'];
					$_SESSION['user']['last_name'] = $user_info['last_name'];
					$_SESSION['user']['email'] = $user_info['email'];
				}
				$response['status'] =  "success";
				$response['message'] = "account updated";
			}else{
				$response['status'] =  "failed";
				$response['message'] = "account could not be updated";
			}
		}

		break;
	case "resend_confirmation": //resend confirmation email to registrant
		//check to see that provided email address is in system
		//check to make sure that the status of associated account is indeed inactive
		$account_info = $accounts_obj->_get($_POST['account_id']);
		if($account_info != 0){
			if($account_info['status'] == 0){
				$email_args['recipient_email'] = $account_info['email'];
				$email_args['recipient_name'] = $account_info['first_name'] . ' ' . $account_info['last_name'];
				$email_args['sender_email'] = "registrations@talentearth.com";
				$email_args['sender_name'] = "TalentEarth Notification";
				$email_args['subject'] = "We have received your Talent Earth registration";
				$template = file_get_contents($templates."registration_confirmation_email.tpl");
				$template_data['confirm_registration_link'] = "http://talentearth1.dystrick.com/confirm_registration.php?id=" . $account_info['id'] . "&token=" . $account_info['reg_token'];
				$message_body = insert_content($template_data,$template);
				$email_args['message_body'] = $message_body;
				//$email_args['bcc_group'] = array("milder.lisondra@yahoo.com","mmartinez@dystrick.com"); //optional
				send_notification($email_args); //config/utilities.php
				$response['status'] = "success";
				$response['email'] = $account_info['email'];
			}else{
				$response['status'] = "failed";
			}
		}
		break;
	case "login":
		$auth_result = $accounts_obj->auth_user($_POST);
		$response['status'] = $auth_result['status'];
		if($auth_result['status'] == "success"){
			//set session variable
			//get user info using given account id
			$user_info = $accounts_obj->_get($auth_result['account_id']);
			$_SESSION['user']['auth'] = $auth_result['status'];
			$_SESSION['user']['account_id'] = $user_info['id'];
			$_SESSION['user']['username'] = $user_info['username'];
			$_SESSION['user']['account_type'] = $user_info['account_type'];
			$_SESSION['user']['first_name'] = $user_info['first_name'];
			$_SESSION['user']['last_name'] = $user_info['last_name'];
			$_SESSION['user']['email'] = $user_info['email'];
			if(($user_info['account_type'] == "business basic") || ($user_info['account_type'] == "business pro")){
				$_SESSION['user']['company'] = $user_info['company'];
			}
			$_SESSION['user']['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];

                        if($_POST['remember'] == "on"){ setcookie('kli',$user_info['loggedin_token'],time()+60*60*24*30,'/'); }

			$response['username'] = $user_info['username'];
		}
		break;
	case "sso_login":
		$auth_result = $accounts_obj->auth_user($_POST);
		$response['status'] = $auth_result['status'];
		if($auth_result['status'] == "success"){
			//set session variable
			//get user info using given account id
			$user_info = $accounts_obj->_get($auth_result['account_id']);
			$_SESSION['user']['auth'] = $auth_result['status'];
			$_SESSION['user']['account_id'] = $user_info['id'];
			$_SESSION['user']['username'] = $user_info['username'];
			$_SESSION['user']['account_type'] = $user_info['account_type'];
			$_SESSION['user']['first_name'] = $user_info['first_name'];
			$_SESSION['user']['last_name'] = $user_info['last_name'];
			$_SESSION['user']['email'] = $user_info['email'];
			if(($user_info['account_type'] == "business basic") || ($user_info['account_type'] == "business pro")){
				$_SESSION['user']['company'] = $user_info['company'];
			}
			$_SESSION['user']['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];

                        if($_POST['remember'] == "on"){ setcookie('kli',$user_info['loggedin_token'],time()+60*60*24*30,'/'); }

			$response['username'] = $user_info['username'];
			$response['email'] = $user_info['email'];
			$response['nonce'] = $nonce;
			$sso_payload['external_id'] = $user_info['username'];
			$sso_payload['email'] = $user_info['email'];
			$sso_payload['nonce'] = $nonce;
			$response['redirect_url'] = $sso->buildLoginString($sso_payload);
		}
		break;
	case "logout";
		unset($_SESSION['user']);
		unset($_SESSION['messages_offset']);
                //$_COOKIE['kli'] = '';

                unset($_COOKIE['fbsr_'.FACEBOOK_APP_ID]);
                unset($_COOKIE['fbs_'.FACEBOOK_APP_ID]);
                unset($_COOKIE['fbm_'.FACEBOOK_APP_ID]);

		$response['status'] = "success";
		break;
	case "reset_password";
		$_POST['password'] = $_POST['password1'];
		$response = $accounts_obj->reset_password($_POST);
		if($response['status'] == "success"){
			//send new password to user
			$email_args['recipient_email'] = $response['user_info']['email'];
			$email_args['recipient_name'] = $response['user_info']['first_name'] . ' ' . $response['user_info']['last_name'];
			$email_args['sender_email'] = "accounts@talentearth.com";
			$email_args['sender_name'] = "TalentEarth Notification";
			$email_args['subject'] = "Your password has been reset";
			$email_args['message_body'] = "Your password has be successfully reset";
			//$email_args['bcc_group'] = array("milder.lisondra@yahoo.com"); //optional
			send_notification($email_args); //config/utilities.php
		}
		break;
	case "reset_pwd_token":
		//Create a token for reset password request
		$_POST['token'] = get_random_string(); //from config/utilities.php
		$response = $accounts_obj->reset_pwd_token($_POST);
		if($response['status'] == "success"){
			//send new password to user
			$email_args['recipient_email'] = $response['user_info']['email'];
			$email_args['recipient_name'] = $response['user_info']['first_name'] . ' ' . $response['user_info']['last_name'];
			$email_args['sender_email'] = "accounts@talentearth.com";
			$email_args['sender_name'] = "TalentEarth Notification";
			$email_args['subject'] = "Password Reset Request";
			//$email_args['message_body'] = "http://" . $host_name . "/confirm_pwd_reset.php?id=" . $response['user_info']['id'] . '&token=' . $response['token'];

			$template = file_get_contents($templates."password_reset_request.tpl");
			$template_data['password_reset_link'] = "http://" . $host_name . "/confirm_pwd_reset.php?id=" . $response['user_info']['id'] . '&token=' . $response['token'];
			$template_data['images_full_path'] = "http://" . $host_name . "/images/";
			$email_args['message_body'] = insert_content($template_data,$template);

			//$email_args['bcc_group'] = array("milder.lisondra@yahoo.com"); //optional
			send_notification($email_args); //config/utilities.php
		}
		break;
	case "validate_pwd_request":
		//ensure that account id and system-generated token combo is legitimate
		$args = array("account_id"=>$_POST['account_id'],"reset_pwd_token"=>$_POST['token']);
		$result = $accounts_obj->validate_pwd_reset($args);
		$response['status'] = $result;
		break;
		case "update":
		$result = $accounts_obj->_update($_POST);
		if($result){
			$response['status'] = "success";
		}else{
			$response['status'] = "failed";
		}
		break;

    case "add_friend_request":
        // sends friend request
        $account_id = $_SESSION['user']['account_id'];
        $friend_id = $_POST['friend_id'];
		$message_text = $_POST['message_text'];

        if(!$account_id || !$friend_id){ return 0; }

        $account_info = $accounts_obj->_get($account_id);
        $friend_info = $accounts_obj->_get($friend_id);

		if($message_text == ""){
			$content = $account_info->user_name." would like to be your Colleague.";
		}else{
			$content = $message_text;
		}

        $team = "0";
        $title = "Colleague Request";
        $type = "colleague request";

        //$status = $accounts_obj->_add_friend_request($account_id, $friend_id);
        $status = $messages_obj->_friend_request_exists($account_id, $friend_id, 1);

        if($status == 0){
   	    $result = $messages_obj->_insert_request($account_id, $friend_id, $team, $title, $content, $type);
            $response['status'] = "success";
        }
        else if($status == 1){
            $response['status'] = "request exists";
        }
        else if($status == 2){
            $response['status'] = 'friend exists';
        }
        else{ $response['status'] = "failed"; }

        break;

    case "authorize_friend":
        $my_account_id = $_SESSION['user']['account_id'];
        //$account_id = $_REQUEST['account_id'];
        $friend_id = $_REQUEST['friend_id'];

        if(!$my_account_id || !$friend_id){ return 0; }

        $result = $accounts_obj->_authorize_friend($my_account_id, $friend_id);
        if($result == 1){ $response['status'] = "success"; }
        else if($result == -1){ $response['status'] = 'exists'; }
        else{ $response['status'] = "failed"; }

        //print $result;
        break;

    case "decline_friend":
        $my_account_id = $_SESSION['user']['account_id'];
        $friend_id = $_REQUEST['friend_id'];

        if(!$my_account_id || !$friend_id){ return 0; }

        $result = $accounts_obj->_decline_friend($my_account_id, $friend_id);
        if($result == 1){ $response['status'] = "success"; }
        else if($result == -1){ $response['status'] = 'not found'; }
        else{ $response['status'] = 'failed'; }

        break;

	case "get_logged_in_id":
		if(isset($_SESSION['user']['account_id'])){
			$user_info = $accounts_obj->_get($_SESSION['user']['account_id']);
			$response['status'] = "success";
			$response['account_info'] = $user_info;
		}else{
			$response['status'] = "success";
		}
		break;
	case "update_profile":
		$_POST['account_id'] = $_SESSION['user']['account_id'];
		//clean up the default values
		if($_POST['title'] == "Title"){
			$_POST['title'] = "";
		}
		if($_POST['address1'] == "Street Address"){
			$_POST['address1'] = "";
		}
		if($_POST['address2'] == "Street Address Two"){
			$_POST['address2'] = "";
		}
		if($_POST['city'] == "City"){
			$_POST['city'] = "";
		}
		if($_POST['postal_code'] == "Zip Code"){
			$_POST['postal_code'] = "";
		}
		if($_POST['phone'] == "Contact Phone"){
			$_POST['phone'] = "";
		}
		if($_POST['bio'] == "Profile Summary"){
			$_POST['bio'] = "";
		}
		if($_POST['website'] == "Website"){
			$_POST['website'] = "";
		}
		//these are the seeking checkboxes and the newsletter checkbox
		//iterate through the array to basically check values
		$seeking_array = array("contract","part_time","full_time","internship","newsletter");
		foreach($seeking_array as $value){
			if($_POST[$value] == "on"){
				$_POST[$value] = "1";
			}else{
				$_POST[$value] = "0";
			}
		}

		$result = $accounts_obj->_update($_POST);
		if($result){
			$response['status'] = "success";
		}else{
			$response['status'] = "failed";
		}
		break;
	case "get_user_settings":
		extract($_POST);
		if(get_account_settings($to_account) == "all"){
			$response['status'] = "success";
			$response[] = "all";
		}
		break;

        case "get_web_path":
                if($_REQUEST['account_id']){
                    $web_path = $accounts_obj->_get_web_storage_path($_REQUEST['account_id']);
                    if($web_path){
                        $response['status'] = 'success';
                        $response['content'] = $web_path;
                    }
                    else{
                        $response['status'] = 'failed';
                        $response['content'] = 'Unable to get storage path';
                    }
                }
                else{
                    $response['status'] = 'failed';
                    $response['content'] = 'No Account ID';
                }
            break;
	case "change_password":
		$result = $accounts_obj->change_password($_POST);
		if($result == 1){
			$response['status'] = 'success';
		}else{
			$response['status'] = 'failure';
		}

		break;
	case "change_settings":
		$status_array = "";
		$num_erorrs = 0;
		//if the username does not equal to current username, check to see that the requested username is available
		if(trim($_POST['username']) != $_SESSION['user']['username']){
			$username_check = $accounts_obj->check_username_exists(trim($_POST['username']));
			if($username_check){
				$status_array[] = "Username Exists";
				$num_erorrs++;
			}else{
				if($accounts_obj->update_username($_POST)){
					$status_array[] = "Username Updated";
					$_SESSION['user']['username'] = trim($_POST['username']);
				}else{
					$status_array[] = "Username Could Not Be Updated";
				}
			}
		}else{
			$status_array[] = "No Change in Username";
		}

		//if the email does not equal to current email, check to see that the requested email is available
		$user_info = $accounts_obj->_get($logged_in_account);
		$email_check = $accounts_obj->check_email_exists(trim($_POST['email']));
		if(trim($_POST['email']) != $user_info['email']){
			$email_check = $accounts_obj->check_email_exists(trim($_POST['email']));
			if($email_check){
				$num_erorrs++;
				$status_array[] = "Email Already In Use";
			}else{
				if($accounts_obj->update_email($_POST)){
					$status_array[] = "Email Updated";
					$_SESSION['user']['email'] = trim($_POST['email']);
				}else{
					$status_array[] = "Email Could Not Be Updated";
				}
			}
		}else{
			$status_array[] = "No Change In Email";
		}
		$result = $accounts_obj->update_account_settings($_POST);
		if($result > 0){
			$status_array[] = "Email Notifications Updated";
		}else{
			$status_array[] = "No Change In Email Notifications";
		}
		$response['status'] = $status_array;
		break;

	case "report_bug":
		// connect to the DB that stores the Bug List. It is on the Bluehost server.
		$bug_DBserverName = "box584.bluehost.com";
		$bug_DBusername = "pacifit7";
		$bug_DBpassword =  "Duffy*01";
		$bug_dbToUse = "pacifit7_db";
		$bug_dbLink = mysql_connect($bug_DBserverName,$bug_DBusername,$bug_DBpassword) or die(mysql_error($bug_dbLink));
		$bug_dbSelected = mysql_select_db($bug_dbToUse,$bug_dbLink);
		$user_message = $_POST['message'];
		$_POST['message'] = $_POST['message'] . ' <br/>User Agent: ' . $_SERVER['HTTP_USER_AGENT']; //Added 05/30/2012 Milder Lisondra
		$cdt = date("m/d/Y",time());
		$sql = "INSERT INTO `talentearthbugslist` (`first_name`, `last_name`, `email`, `page_reported`, `message`,`submit_date`) VALUES ('".$_POST['first_name']."', '".$_POST['last_name']."', '".$_POST['email']."', '".$_POST['page']."', '".addslashes($_POST['message'])."','".$cdt."')";
		$result = mysql_query($sql, $bug_dbLink);
		if($result){
			mysql_close($bug_dbLink);
			$response['status'] = "success";
		}else{
			$response['status'] = "failed";
		}

		//Send bug submission via email
		unset($email_args);
		$email_args['recipient_email'] = "dev@talentearth.com";
		$email_args['recipient_name'] = "TalentEarth DEV Team";
		$email_args['sender_email'] = "bugs@talentearth.com";
		$email_args['sender_name'] = "TalentEarth";
		$email_args['subject'] = "A Bug has been submitted from TalentEarth.com";
		$template = file_get_contents($templates."bug_submission.html");
		$_POST['username'] = $_SESSION['user']['username'];
		$_POST['cdt'] = date("m/d/Y g:i A",time());
		$_POST['message'] = $user_message;
		$_POST['user_agent'] =  $_SERVER['HTTP_USER_AGENT'];
		$email_args['message_body'] = insert_content($_POST,$template);
		send_notification($email_args); //config/utilities.php
		
		break;
                
        case "validate_external":
            $account_id = $_REQUEST['account_id'];
            $uid = $_REQUEST['uid'];
            
            if($accounts_obj->validate_external($account_id, $uid)){
                die('asd');
                $response['status'] = 'success';
                $response['content'] = 1;
            }
            else{
                die('ewq');
                $response['status'] = 'failed';
                $response['content'] = 'Error matching ID and UID';
            }
            break;

        case "create_username_external":
            $account_id = $_REQUEST['account_id'];
            $username = $_REQUEST['username2'];
            $uid = $_REQUEST['uid'];
            $account_id = $_REQUEST['account_id'];
            
            
            $username_check = $accounts_obj->check_username_exists($username);
            if($username_check){
                $response['status'] = 'failed';
                $response['message'] = 'username exists';
            }else{
                    
                if($accounts_obj->create_username_external($account_id, $username, $uid)){
                    $response['status'] = 'success';
                    $response['content'] = $username;
                }
                else{
                    $response['status'] = 'failed';
                    $response['content'] = 0;
                }
            }
            break;
   
	case "delete_account":
		extract($_POST);
		$email_notification = "";
		$current_date = time();
		
		if($account_id == $logged_in_account){
			$user_info = $accounts_obj->_get($account_id);
			$account_type = $_SESSION['user']['account_type'];
				//Delete account
				 //TODO: The sql query here needs to be moved into the Accounts class        
				$sql = "UPDATE `".TABLES_PREFIX.ACCOUNTS."` SET `status`='0' WHERE `id`= '".$account_id."'";
				$result = mysql_query($sql);
				if($result){
					if(mysql_affected_rows() == 1){
						//TODO : Move sql query to accounts model
						$sql = "SELECT `transaction_id` FROM `".TABLES_PREFIX.TRANSACTIONS."` WHERE `cancelled_date`='0' AND `transaction_status`='APPROVED' AND `account_id` = '".$account_id."' AND `cancelled_date` = 0";
						$result = mysql_query($sql);
						if($result && mysql_num_rows($result) == 1){
							$transaction_info = mysql_fetch_array($result);
							extract($transaction_info);
							$_POST['oid'] = $transaction_id;
							$cancel_result = process_cancel_payment($_POST); //from config/utilities.php

							if($cancel_result['r_approved'] == "APPROVED"){
								//TODO: Move query to accounts model
								$sql = "UPDATE `".TABLES_PREFIX.TRANSACTIONS."` SET `cancelled_date`='".$current_date."' WHERE `transaction_id`= '".$transaction_id."'";
								mysql_query($sql);
								
								$email_notification['recipient_email'] = "Welenofsky";
								$email_notification['recipient_name'] = "Justin Welenofsky";
								$email_notification['subject'] = "User Deleted Account";
								$email_notification['message_body'] = "User Deleted Account. Please check the database";
								$email_notification['sender_name'] = "TalentEarth Customer Service";
								$email_notification['sender_email'] = "info@talentearth.com";
								send_notification($email_notification);
							}else{
								//Should send email to TalentEarth administrators so they can void manually from First Data Virtual Terminal
								$email_notification['recipient_email'] = "welenofsky@gmail.com";
								$email_notification['recipient_name'] = "Justin Welenofsky";
								$email_notification['subject'] = "User Deleted Account";
								$email_notification['message_body'] = "User Deleted Account but could not cancel in First Data. Use Virtual Terminal to cancel billing." . $transaction_id;
								$email_notification['sender_name'] = "TalentEarth Customer Service";
								$email_notification['sender_email'] = "info@talentearth.com";
								send_notification($email_notification);								
							}							
						}else{
							//Should send email to TalentEarth administrators so they can void manually from First Data Virtual Terminal
								$email_notification['recipient_email'] = "welenofsky@gmail.com";
								$email_notification['recipient_name'] = "Justin Welenofsky";
								$email_notification['subject'] = "User Deleted Account";
								$email_notification['message_body'] = "User Deleted Account but could not cancel in First Data. Use Virtual Terminal to cancel billing." . $transaction_id;
								$email_notification['sender_name'] = "TalentEarth Customer Service";
								$email_notification['sender_email'] = "info@talentearth.com";
								send_notification($email_notification);								
						}
						
						unset($email_notification);
						$email_notification['recipient_email'] = $user_info['email'];
						$email_notification['recipient_name'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
						$email_notification['subject'] = "Your TalentEarth Account has been deleted";
						//$email_notification['message_body'] = "User Deleted Account but could not cancel in First Data. Use Virtual Terminal to cancel billing." . $transaction_id;
						$template = file_get_contents($templates."delete_account_confirmation.html");
						$template_data['username'] = $user_info['username'];
						$template_data['email'] = $user_info['email']; 
						$template_data['cdt'] = date("m/d/Y",$current_date);
						$template_data['transaction_id'] = $transaction_id;
						$email_notification['message_body'] = insert_content($template_data,$template);
						$email_notification['sender_name'] = "TalentEarth Customer Service";
						$email_notification['sender_email'] = "info@talentearth.com";
						// $email_notification['bcc_group'] = array("milder.lisondra@yahoo.com");
						
						send_notification($email_notification);							
						unset($_SESSION['user']); //unset all user session variables
						$response['status'] = 'success';
					}
				}
		}else{
			$response['status'] = 'failed';
		}
		break;
		
	case "update_profile_privacy":
		if($_POST['account_id'] != ""){
			$result = $accounts_obj->save_privacy_settings($_POST);
			if($result == 0){
				$response['status'] = "failed";
			}else{
				$response['status'] = "success";
			}
		}
		break;
	case "get_featured_profiles":
		$sql = "SELECT * FROM featured_profiles";
		$result = mysql_query($sql);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$response['status'] = "success";
				while($row = mysql_fetch_assoc($result)) {
					$response[$row['type']]['username'][] = $row['username'];
					$response[$row['type']]['username'][$row['username']]['profile_image'] = $row['profileimage'];
					$response[$row['type']]['username'][$row['username']]['full_name'] = $row['full_name'];
					$response[$row['type']]['username'][$row['username']]['talent'] = $row['talent'];
				}
			}
		} else {
			$response['status'] = "failed";
			$response['error'] = mysql_error();
		}
		break;
	/*
	case "add_featured_profile":
		$response['status'] = "success";
		$response['message'] = "add_featured_profile";
		$type = mysql_escape_string($_POST['type']);
		$profile_image = '';
		$username = $_POST['username'];
		if(!empty($username)) {
			$profile_info = $accounts_obj->_get_by_username($username);
			if(!empty($profile_image = $profile_info['profile_image'])) {
				$profile_image = $profile_info['profile_image']);
				$sql = "INSERT INTO featured_profiles (username,profileimage,type) VALUES ('$username','$profile_image','$type')";
			}
		}
		break;
	*/
	case "add_featured_profile":
		$needed_postdata = array('type','username','profileimage');
		foreach ($needed_postdata as $value) {
			if(empty($_POST[$value])) {
				$response['status'] = "failed";
				$response['message'] .= "Missing $value, ";
			}
		}
		$username = $_POST['username'];
		$type = $_POST['type'];
		$profileimage = $_POST['profileimage'];
		$sql = "INSERT INTO featured_profiles (username,profileimage,type) VALUES ('$username','$profileimage','$type')";
		$response['status'] = 'success';
		$response['message'] = $sql;
		break;
	case "get_by_username":
		$username = $_POST['username'];
		if(!empty($username)) {
			$profile_info = $accounts_obj->_get_by_username($username);
			if(!empty($profile_info['profile_image'])) {
				$response['status'] = "success";
				$response['profile_image'] = $profile_info['profile_image'];
			} else {
				$response['status'] = "failed";
			}
		}
		break;
}


print json_encode($response);

?>
