<?php
class Accounts{

	public $accounts;
	public $accounts_view;
	public $privacy_settings;

	public function __construct(){
	    	$this->accounts = TABLES_PREFIX.ACCOUNTS;
            $this->accounts_view = TABLES_PREFIX."view_accounts";
            $this->friend_requests = TABLES_PREFIX.FRIEND_REQUESTS;
            $this->friends = TABLES_PREFIX.FRIENDS;
            $this->storage_paths = TABLES_PREFIX.STORAGE_PATHS;
            $this->accounts_media = TABLES_PREFIX.MEDIA;
			$this->requests = TABLES_PREFIX.REQUESTS;
			$this->privacy_settings = TABLES_PREFIX.PRIVACY_SETTINGS;
			$this->privacy_settings_const = unserialize(PROFILE_PRIVACY_SETTINGS);
	}

	/**
	* Create new Account with given array of parameters
	* @param array $args
	* @return array $response (status,account_id,reg_token)
	*/
	public function _create($args){
		extract($args);
		$cdt = time();
		$response = "";
		$reg_token = get_random_string(); //from utilities.php
                
                $first_name = trim($first_name);
                $last_name = trim($last_name);
                $email = trim($email);

    $affiliate_id = 0;
    $affiliate_is_valid_dates = 0;

    if (isset($_SESSION['affiliate_id']))
    {
      $affiliate_id = $_SESSION['affiliate_id'];
      $affiliate_is_valid_dates = isset($_SESSION['affiliate_is_valid_dates']) ? (int)$_SESSION['affiliate_is_valid_dates'] : 0;
    }

		$sql = sprintf("INSERT INTO `".$this->accounts."` (`first_name`,`last_name`,`email`,`created`,`modified`,`reg_token`,`account_type`, `storage_path_id`, `loggedin_token`, `affiliate_id`, `affiliate_is_valid_dates`) VALUES ('%s','%s','%s','$cdt','$cdt','$reg_token','$account_type','".DEFAULT_STORAGE_PATH_ID."', MD5('%s'), '%d', '%d');",
			mysql_real_escape_string($first_name),
			mysql_real_escape_string($last_name),
			mysql_real_escape_string($email),
                        $reg_token,
      $affiliate_id,
      $affiliate_is_valid_dates
			);

		$result = mysql_query($sql);

		if($result){
			$response['status'] = "success";
			$response['account_id'] = mysql_insert_id();
			$response['reg_token'] = $reg_token;

      if (isset($_SESSION['affiliate_id']))
      {
        unset($_SESSION['affiliate_id']);
        unset($_SESSION['affiliate_is_valid_dates']);
      }
		}else{
			$response['status'] = "failed";
			$response['account_id'] = "";
			$response['reg_token'] = "";
		}
		return $response;
	}

	/**
	* Retrieves account information with given id
	* @param string $account_id Account ID
	*/
	public function _get($account_id){
		if(is_numeric($account_id)){
			$sql = "SELECT * FROM `".$this->accounts."` WHERE id = '".$account_id."'";
			$result = mysql_query($sql);
			if($result){
				if(mysql_num_rows($result) > 0){
					$account_info = mysql_fetch_array($result);
	                    $sql = "SELECT * FROM ".$this->accounts_media." WHERE `account_id` = '".$account_id."' AND `media_type` = 'profile' LIMIT 1";
						//echo $sql;	                   
					    $res = mysql_query($sql);
	                    if($res){
	                        if(mysql_num_rows($res) > 0){
	                            $profile_media = mysql_fetch_assoc($res);
	                            $account_info['web_path'] = $this->_get_web_storage_path($account_info['id']);
	                            $account_info['profile_image'] = $account_info['web_path'].$account_info['id']."/images/".$profile_media['media_thumb'];
	                        }
	                    }
	                    $sql = "SELECT * FROM ".$this->accounts_media." WHERE `account_id` = '".$account_id."' AND `media_type` = 'banner' LIMIT 1";
	                   
					    $res = mysql_query($sql);
	                    if($res){
	                        if(mysql_num_rows($res) > 0){
	                            $profile_media = mysql_fetch_assoc($res);
	                            $account_info['web_path'] = $this->_get_web_storage_path($account_info['id']);
	                            $account_info['banner_image'] = $account_info['web_path'].$account_info['id']."/images/".$profile_media['media_name'];
	                        }
	                    }

					return $account_info;
				}else{
					return 0;
				}
			}			
		}else{
			return 0;
		}

	}

    /**
     * Gets the storage path for the current user
     * @param int $account_id
     * @return storage path if found, 0 otherwise
     */
    public function _get_storage_path($account_id){
        if(!$account_id){ return 0; }
        $account_id = mysql_real_escape_string($account_id);
        $sql = "SELECT ".$this->storage_paths.".path
                  FROM ".$this->accounts.", ".$this->storage_paths."
                 WHERE ".$this->accounts.".id = '".$account_id."'
                   AND ".$this->accounts.".storage_path_id = ".$this->storage_paths.".id
                 LIMIT 1";
        $res = mysql_query($sql);
        if($res){
            if(mysql_num_rows($res) > 0){
                $row = mysql_fetch_assoc($res);
                return $row{'path'};
            }
            else{ return 0; }
        }
        else{
            return 0;
        }
    }



    /**
     * Gets the web accessible storage path for the current user
     * @param int $account_id
     * @return storage path if found, 0 otherwise
     */
    public function _get_web_storage_path($account_id){
        if(!$account_id){ return 0; }
        $account_id = mysql_real_escape_string($account_id);
        $sql = "SELECT ".$this->storage_paths.".web_path
                  FROM ".$this->accounts.", ".$this->storage_paths."
                 WHERE ".$this->accounts.".id = '".$account_id."'
                   AND ".$this->accounts.".storage_path_id = ".$this->storage_paths.".id
                 LIMIT 1";
        $res = mysql_query($sql);
        if($res){
            if(mysql_num_rows($res) > 0){
                $row = mysql_fetch_assoc($res);
                return "/".$row{'web_path'};
            }
            else{ return 0; }
        }
        else{
            return 0;
        }
    }


	/**
	* Retrieves account information with given email
	* @param string $email
	*/
	public function _get_by_email($email){
                $email = mysql_real_escape_string(trim($email));
                
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `email` = '".$email."' LIMIT 1";
		$result = mysql_query($sql);
		if($result){
			if(mysql_num_rows($result) == 1){
				$account_info = mysql_fetch_array($result);
				return $account_info;
			}else{
				return 0;
			}
		}
	}

	/**
	* Retrieves account information with given username
	* @param string $username
	*/
	public function _get_by_username($username){
                $username = mysql_real_escape_string(trim($username));
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `username` = '".$username."'";
		$result = mysql_query($sql);
		if($result){
			if(mysql_num_rows($result) == 1){
				$account_info = mysql_fetch_array($result);
                $account_info['web_path'] = $this->_get_web_storage_path($account_info['id']);
                $sql = "SELECT * FROM ".$this->accounts_media." WHERE account_id = '".$account_info['id']."' AND media_type = 'profile' LIMIT 1";
				$res = mysql_query($sql);
                if($res){
                    if(mysql_num_rows($res) > 0){
                        $profile_media = mysql_fetch_assoc($res);
                        $account_info['profile_image'] = $account_info['web_path'].$account_info['id']."/images/".$profile_media['media_thumb'];
                    }
                }
            	$sql = "SELECT * FROM ".$this->accounts_media." WHERE account_id = '".$account_info['id']."' AND media_type = 'banner' LIMIT 1";
				$res = mysql_query($sql);
                if($res){
                    if(mysql_num_rows($res) > 0){
                        $profile_media = mysql_fetch_assoc($res);
                        $account_info['banner_image'] = $account_info['web_path'].$account_info['id']."/images/".$profile_media['media_name'];
                    }
                }
				return $account_info;
			}else{
				return 0;
			}
		}
	}

	/**
	* Update acccount information using given array of parameters
	* @param array $args
	* TODO: The check for company variable should be handled at the Controller level
	*/
	public function _update($args){
		extract($args);
		$cdt = time();
		if(!isset($company)){
			$company = "";
		}

		$sql = sprintf("UPDATE `".$this->accounts."` SET `first_name`='%s', `last_name`='%s', `email`='%s', `title`='%s',`address1`='%s',`address2`='%s',`city`='%s',
		`state`='$state',
		`postal_code`='%s',
		`phone`='%s',
		`contract`='$contract',
		`part_time`='$part_time',
		`full_time`='$full_time',
		`internship`='$internship',
		`newsletter`='$newsletter',
		`bio`='%s',
		`modified`='$cdt',
		`company`='$company',
		`website`= '%s'
		WHERE `id`='".$account_id."';",
			mysql_real_escape_string(trim($first_name)),
			mysql_real_escape_string(trim($last_name)),
			mysql_real_escape_string(trim($email)),
			mysql_real_escape_string(trim($title)),
			mysql_real_escape_string(trim($address1)),
			mysql_real_escape_string(trim($address2)),
			mysql_real_escape_string(trim($city)),
			mysql_real_escape_string(trim($postal_code)),
			mysql_real_escape_string(trim($phone)),
			mysql_real_escape_string(trim($bio)),
			mysql_real_escape_string(trim($website))
		);
		$result = mysql_query($sql);
		if($result){
			if(mysql_affected_rows() == 1){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * Update single column for given account id
	 */
	public function _update_single_column($args){
		extract($args);
		$sql = "UPDATE `$this->accounts` SET `".$column."` = '".$column_value."' WHERE `id`='".$account_id."'";
		//echo $sql; die();
		$result = mysql_query($sql);
		if($result){
			if(mysql_affected_rows() == 1){
				return 1;
			}else{
				$error_args['function_used'] = __METHOD__;
				$error_args['error_message'] = mysql_error() . " " . $sql;
				record_error($error_args);
				return 0;
			}
		}else{
			$error_args['function_used'] = __METHOD__;
			$error_args['error_message'] = mysql_error() . " " . $sql;
			record_error($error_args);
			return 0;
		}

	}

	/**
	 * Check if given email address exists
	 * @param string $email
	 * @return bool true/false
	 */
	public function check_email_exists($email){
                $email = mysql_real_escape_string(trim($email));
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `email`='".$email."' AND status = '1'";
		$result = mysql_query($sql);
		if($result){
			if(mysql_num_rows($result) > 0){
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	 * Check if given username exists
	 * @param string $username
	 * @return bool true/false
	 */
	public function check_username_exists($username){
                $username = mysql_real_escape_string(trim($username));
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `username`='".$username."'";

		$result = mysql_query($sql);
		if($result){
			if(mysql_num_rows($result) > 0){
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	* Check to see that given account id and registration token is a valid combo
	* @param array $args
	*/
	public function validate_registration($args){
		extract($args);
                
	    $account_id = mysql_real_escape_string($account_id);
	    $reg_token = mysql_real_escape_string($reg_token);
                
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `id`='".$account_id."' AND `reg_token`='".$reg_token."' AND `status`='0'";
		$result = mysql_query($sql);
		if(is_resource($result)){
			if(mysql_num_rows($result) == 1){
				$user_info = mysql_fetch_array($result);
				extract($user_info);
				//Check to see if there is a recored with the same email address
				if($this->check_email_exists($email)){
					return 'record exists';
				}else{
					return 'valid';
				}
			}else{
				return "invalid";
			}
		}else{
			return "could not retrieve";
		}
	}

	/**
	* Create username and password for given account id
	* @param $array $args
	*/
	public function create_username_password($args){
		extract($args);
		$cdt = time();
		$hashed_pwd = md5($password);
		if(is_numeric($account_id)){
			$sql = sprintf("UPDATE `".$this->accounts."` SET `username` = '%s',`password` = '$hashed_pwd',`status`='1',`modified` = '$cdt' WHERE `id`='".$account_id."'",
				mysql_real_escape_string($username)
			);
			$result = mysql_query($sql);
			if($result){
				if(mysql_affected_rows() == 1){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
	}

	public function update_username($args){
		extract($args);
		$sql = sprintf("UPDATE `".$this->accounts."` SET `username` = '%s' WHERE `id` = '".$account_id."'",
			mysql_real_escape_string(trim($username))
		);
		$result = mysql_query($sql);
		if($result){
			if(mysql_affected_rows() == 1){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}

	public function update_email($args){
		extract($args);
		$sql = sprintf("UPDATE `".$this->accounts."` SET `email` = '%s' WHERE `id` = '".$account_id."'",
			mysql_real_escape_string(trim($email))
		);
		//echo $sql;
		$result = mysql_query($sql);
		if($result){
			if(mysql_affected_rows() == 1){
				return 1;
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}

	/**
	* Authenticate user with given credentials
	* @param array $args ($username,$password)
	* @return array $response
	*/
	public function auth_user($args){
	    extract($args);
            
            $username = mysql_real_escape_string($username);

            $user_exists = $this->check_email_exists($username); //check if user email exists
            $username_exists = $this->check_username_exists($username); //check if username exists
            
            if($user_exists || $username_exists){
                $sql = "SELECT * FROM `".$this->accounts."` WHERE `email`='$username' OR `username`='$username'";

                $result = mysql_query($sql);
                $user_info = mysql_fetch_array($result);

                if($user_info['status'] == 1){

                    if(!$user_info['password'] && $user_info['external'] == 'facebook'){
                        $response['status'] = 'facebook';
                    }
                    else if(!$user_info['password'] && $user_info['external'] = 'linkedin'){
                        $response['status'] = 'linkedin';
                    }
                    else if(md5($password) == $user_info['password']){
                		$response['status'] = "success";
                		$response['account_id'] = $user_info['id'];
                    }else{
                		$response['status'] = "bad password";
                    }
                }else{
            	    $response['status'] = "account inactive";
                }
            }else{
        	$response['status'] = "invalid user";
            }
	    return $response;
	}

        /**
         * Gets the loggedin_token for the given username
         * @param $username string
         * @return $loggedin_token string
         */
        function get_loggedin_token($username){
            if(!$username){ return; }
            
            $username = mysql_real_escape_string($username);

            $sql = "SELECT loggedin_token FROM ".$this->accounts." WHERE username = '".$username."' LIMIT 1";
            $res = mysql_query($sql);
            if($res){
                if(mysql_num_rows($res) > 0){
                    $row = mysql_fetch_assoc($res);
                    return $row['loggedin_token'];
                }
                return;
            }
            return;
        }

	/**
	* Reset Password
	* @param array $args (int $account_id, string $password)
	* @return array $response (string $status, string $message, array $user_info)
	*/
	public function reset_password($args){
	    extract($args);
                
            $account_id = mysql_real_escape_string($account_id);
                
            $hashed_pwd = md5($password);
	    //print_r($args);
            $sql = "UPDATE `".$this->accounts."` SET `password`='".$hashed_pwd."' WHERE `id`='$account_id' LIMIT 1";
	    //echo $sql;
            $result = mysql_query($sql);
            if($result){
        	//reset the password token
        	$token = get_random_string(); //from utilities.php
            	$sql = "UPDATE `".$this->accounts."` SET `reset_pwd_token`='".$token."' WHERE `id`='$account_id' LIMIT 1";

		mysql_query($sql);

            	$response['status'] = 'success';
		$response['message'] = 'Password reset.';
		$response['user_info'] = $this->_get($account_id);
            }else{
            	$response['status'] = 'failed';
		$response['message'] = 'Password could not be reset.';
		$response['user_info'] = 0;
            }

	    return $response;
	}

	/**
	 * Change password for given user id
	 * @param array $args (int $account_id,string password)
	 * @return int 1 on success; 0 on failure
	 */
	public function change_password($args){
		extract($args);
		if(is_numeric($account_id)){
			$hashed_pwd = md5($password);
                        $account_id = mysql_real_escape_string($account_id);
                        
			$sql = "UPDATE `".$this->accounts."` SET `password` = '".$hashed_pwd."' WHERE `id`='".$account_id."'"; 

			$result = mysql_query($sql);
			if($result){
				if(mysql_affected_rows() == 1){
					return 1;
				}
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}

	/**
	 * Reset password
	 * Method checks to see if the given $email address exists
	 * @param array $args
	 * @return array $response (string $status, string $message, string $token, array $user_info)
	 *
	 */
	public function reset_pwd_token($args){
		extract($args);
                $user_exists = $this->check_email_exists($email); //check if user email exists
                if($user_exists == 1){
        	        $token = get_random_string(); //from utilities.php
                        $sql = "UPDATE `".$this->accounts."` SET `reset_pwd_token`='".$token."' WHERE `email`='$email' AND `status` = '1'";

                        $result = mysql_query($sql);
                        if($result){
            	                if(mysql_affected_rows() == 1){
            		                $response['status'] = 'success';
					$response['message'] = 'token created';
					$response['token'] = $token;
					$response['user_info'] = $this->_get_by_email($email); //get user info
					if($response['user_info'] == 0)  {
	                	$response['status'] = 'failed';
						$response['message'] = 'Could not find user.';
						return $response;
					}
            	                }else{
            		                $response['status'] = 'failed';
					$response['message'] = 'token could not be created';
					$response['token'] = "";
					$response['user_info'] = "";
            	                }
                        }
                }else{
                        $response['status'] = 'failed';
			$response['message'] = '<span style="color:#E96E34">Email address not found.</span>';
                }

		return $response;
	}

	/**
	* Check to see that given account id and password reset token is a valid combo
	* @param array $args
	* @return string
	*/
	public function validate_pwd_reset($args){
		extract($args);
		$sql = "SELECT * FROM `".$this->accounts."` WHERE `id`='".$account_id."' AND `reset_pwd_token`='".$reset_pwd_token."'";
		$result = mysql_query($sql);
		if(is_resource($result)){
			if(mysql_num_rows($result) == 1){
				return "valid";
			}else{
				return "invalid";
			}
		}else{
			return "could not retrieve";
		}
	}

	/**
	 * If facebook UID exists in our database sets session vars to be logged in.
	 * If the UID doesn't exist, creates a local account for the user and logs in.
	 * Uses global function get_facebook_cookie, found in /config/utilities.php
	 * @param int $uid
	 * @return 1 if successfully logged in, 0 otherwise
	 */
    public function validate_facebook_auth($uid){
	    if(!$uid){ return 0; }

	    $cookie = get_facebook_cookie(FACEBOOK_APP_ID, FACEBOOK_APP_SECRET);
            $user = json_decode(file_get_contents('https://graph.facebook.com/me?access_token=' . $cookie['access_token']));
            
            $sql = "SELECT * FROM ".$this->accounts." WHERE external_id = '".$uid."' AND status = '1' LIMIT 1";
            $res = mysql_query($sql);
            
            if($res){
                if(mysql_num_rows($res) > 0){ // user has created account through FB
                    $row = mysql_fetch_assoc($res);
                    
                    if($row['username']){ // has already created a username, login, continue to homepage
                        $_SESSION['user']['auth'] = 'success';
		        $_SESSION['user']['account_id'] = $row['id'];
                        $_SESSION['user']['facebook'] = 1;
                        $_SESSION['user']['username'] = $row['username'];
                        $_SESSION['user']['external_id'] = $uid;
                        $_SESSION['user']['account_type'] = $row['account_type'];
                        $_SESSION['user']['fullname'] = $row['first_name'] . ' ' . $row['last_name'];
                        $_SESSION['user']['first_name'] = $row['first_name'];
                        $_SESSION['user']['last_name'] = $row['last_name'];
                        $_SESSION['user']['email'] = $row['email'];
		        return -1;
                    }
                    else{ // need to create a username here
                        return $row['id'];
                    }
                }
                else{ // hasnt logged in to TE through facebook before
                    if($_GET['fblogin']){ // logged in through FB, need to create TE account here, login, create username
                        $reg_token = get_random_string();
                            
                        if(BETA_MODE == "true"){
                            $new_account_type = 'pro';
                        }
                        else{
                            $new_account_type = 'basic';
                        }
                            
    		        $sql = "INSERT INTO ".$this->accounts." (external_id, first_name, last_name, email, external, status, storage_path_id, account_type, loggedin_token)
                                VALUES ('".$uid."','".$user->first_name."','".$user->last_name."','".$user->email."','facebook', '1', '".DEFAULT_STORAGE_PATH_ID."','".$new_account_type."', MD5('".$reg_token."'))";

    		        $res = mysql_query($sql) or die(mysql_error());
	                $account_id = mysql_insert_id();
                        
		        $_SESSION['user']['auth'] = 'success';
		        $_SESSION['user']['account_id'] = $account_id;
		        $_SESSION['user']['facebook'] = 1;
                        $_SESSION['user']['external_id'] = $uid;
                        $_SESSION['user']['account_type'] = "basic";
                        $_SESSION['user']['fullname'] = $user->first_name . ' ' . $user->last_name;
                        $_SESSION['user']['first_name'] = $user->first_name;
                        $_SESSION['user']['last_name'] = $user->last_name;
                        $_SESSION['user']['email'] = $user->email;
                        return $account_id;
                    }
                    else{ // FB cookie has been set but we should ignore it?
                        return -2;
                    }
                }
            }
            
            return 0;
            
            
            // old stuff here //
/*            

            $sql = "SELECT * FROM ".$this->accounts." WHERE username IS NOT NULL AND external_id = '".$uid."' LIMIT 1";
            $res = mysql_query($sql);
	    if($res){
    		if(mysql_num_rows($res) > 0){
		    $row = mysql_fetch_assoc($res);
                    $_SESSION['user']['auth'] = 'success';
		    $_SESSION['user']['account_id'] = $row['id'];
                    $_SESSION['user']['facebook'] = 1;
                    $_SESSION['user']['username'] = $row['username'];
                    $_SESSION['user']['external_id'] = $uid;
                    $_SESSION['user']['account_type'] = $row['account_type'];
                    $_SESSION['user']['fullname'] = $row['first_name'] . ' ' . $row['last_name'];
                    $_SESSION['user']['first_name'] = $row['first_name'];
                    $_SESSION['user']['last_name'] = $row['last_name'];
                    $_SESSION['user']['email'] = $row['email'];
		    return -1;
		}
                else if($_SESSION['user']['auth'] == 'success'){
                    return -2;
                }
		else if(!$_SESSION['user']['auth'] != 'success'){
                    // do email stuff here //
                }
                else if($_GET['fblogin']){ // check for referring get variable opr something?
                    $uid = mysql_real_escape_string($uid);

                    $sql = "SELECT * FROM ".$this->accounts." WHERE external_id = '".$uid."' LIMIT 1";
                    $res = mysql_query($sql);
                    if($res){
                        if(!mysql_num_rows($res)){
                            $reg_token = get_random_string();
                            
                            if(BETA_MODE == "true"){
                                $new_account_type = 'pro';
                            }
                            else{
                                $new_account_type = 'basic';
                            }
                            
    		            $sql = "INSERT INTO ".$this->accounts." (external_id, first_name, last_name, email, external, status, storage_path_id, account_type, loggedin_token)
                                    VALUES ('".$uid."','".$user->first_name."','".$user->last_name."','".$user->email."','facebook', '1', '".DEFAULT_STORAGE_PATH_ID."','".$new_account_type."', MD5('".$reg_token."'))";

    		            $res = mysql_query($sql) or die(mysql_error());
		            $account_id = mysql_insert_id();
                        }
                        else{
                            $row = mysql_fetch_assoc($res);
                            $account_id = $row['id'];    
                        }
                        
		        $_SESSION['user']['auth'] = 'success';
		        $_SESSION['user']['account_id'] = $account_id;
		        $_SESSION['user']['facebook'] = 1;
                        $_SESSION['user']['external_id'] = $uid;
                        $_SESSION['user']['account_type'] = "basic";
                        $_SESSION['user']['fullname'] = $user->first_name . ' ' . $user->last_name;
                        $_SESSION['user']['first_name'] = $user->first_name;
                        $_SESSION['user']['last_name'] = $user->last_name;
                        $_SESSION['user']['email'] = $user->email;
			return $account_id;
		    }
		}
                else{
                    return -3;
                }
	    }
	    return 0;
            // end old stuff here
            */
	}

    /**
     * Same as validate_facebook_auth()
     * @param int $uid
     * @return 1 if successfully logged in, 0 otherwise
     */
    public function validate_linkedin_auth($uid){
	    if(!$uid){ return 0; }
            
            $cookie = get_linkedin_cookie();

            $sql = "SELECT * FROM ".$this->accounts." WHERE external_id = '".$uid."' AND status = '1' LIMIT 1";
            $res = mysql_query($sql);
            
            if($res){
                if(mysql_num_rows($res) > 0){ // user has created account through LI
                    $row = mysql_fetch_assoc($res);
                    
                    if($row['username']){ // has already created a username, login, continue to homepage
                        $_SESSION['user']['auth'] = 'success';
		        $_SESSION['user']['account_id'] = $row['id'];
                        $_SESSION['user']['linkedin'] = 1;
                        $_SESSION['user']['username'] = $row['username'];
                        $_SESSION['user']['external_id'] = $uid;
                        $_SESSION['user']['account_type'] = $row['account_type'];
                        $_SESSION['user']['fullname'] = $row['first_name'] . ' ' . $row['last_name'];
                        $_SESSION['user']['first_name'] = $row['first_name'];
                        $_SESSION['user']['last_name'] = $row['last_name'];
                        $_SESSION['user']['email'] = $row['email'];
		        return -1;
                    }
                    else{ // need to create a username here
                        return $row['id'];
                    }
                }
                else{ // hasnt logged in to TE through facebook linkedin before
                    //if($_GET['lilogin']){ // logged in through LI, need to create TE account here, login, create username
                        $reg_token = get_random_string();
                            
                        if(BETA_MODE == "true"){
                            $new_account_type = 'pro';
                        }
                        else{
                            $new_account_type = 'basic';
                        }
                            
    		        $sql = "INSERT INTO ".$this->accounts." (external_id, first_name, last_name, email, external, status, storage_path_id, account_type, loggedin_token)
                                VALUES ('".$uid."','".$cookie['first_name']."','".$cookie['last_name']."','','linkedin', '1', '".DEFAULT_STORAGE_PATH_ID."','".$new_account_type."', MD5('".$reg_token."'))";

    		        $res = mysql_query($sql) or die(mysql_error());
	                $account_id = mysql_insert_id();
                        
		        $_SESSION['user']['auth'] = 'success';
		        $_SESSION['user']['account_id'] = $account_id;
		        $_SESSION['user']['linkedin'] = 1;
                        $_SESSION['user']['external_id'] = $uid;
                        $_SESSION['user']['account_type'] = "basic";
                        $_SESSION['user']['fullname'] = $cookie['first_name'] . ' ' . $cookie['last_name'];
                        $_SESSION['user']['first_name'] = $cookie['first_name'];
                        $_SESSION['user']['last_name'] = $cookie['last_name'];
                        return $account_id;
                    //}
                    //else{ // LI cookie has been set but we should ignore it?
                        //return -2;
                    //}
                }
            }
            
            return 0;
        
                        
        /* old way
        
        if(!$uid){ return 0; }

        $cookie = get_linkedin_cookie();

        $sql = "SELECT * FROM ".$this->accounts." WHERE username IS NOT NULL AND external_id = '".$cookie['uid']."' LIMIT 1";
	$res = mysql_query($sql);
	if($res){
            if(mysql_num_rows($res) > 0){
                $row = mysql_fetch_assoc($res);
                $_SESSION['user']['auth'] = 'success';
		$_SESSION['user']['account_id'] = $row['id'];
                $_SESSION['user']['linkedin'] = 1;
                $_SESSION['user']['username'] = $row['username'];
                $_SESSION['user']['external_id'] = $uid;
                $_SESSION['user']['account_type'] = $row['account_type'];
                $_SESSION['user']['fullname'] = $row['first_name'] . ' ' . $row['last_name'];
		$_SESSION['user']['first_name'] = $row['first_name'];
		$_SESSION['user']['last_name'] = $row['last_name'];
		$_SESSION['user']['email'] = $row['email'];

		return -1;
	    }
	    else{
                $uid = mysql_real_escape_string($uid);
                $sql = "SELECT * FROM ".$this->accounts." WHERE external_id = '".$uid."' LIMIT 1";
                $res = mysql_query($sql);
                if($res){
                    if(!mysql_num_rows($res)){
                        $reg_token = get_random_string();
                        
                        if(BETA_MODE == "true"){
                            $new_account_type = 'pro';
                        }
                        else{
                            $new_account_type = 'basic';
                        }                        
                        
    		        $sql = "INSERT INTO ".$this->accounts."
		                (external_id, first_name, last_name, email, external, status, storage_path_id, account_type,loggedin_token)
			        VALUES ('".$uid."','".$cookie['first_name']."','".$cookie['last_name']."','','linkedin','1','".DEFAULT_STORAGE_PATH_ID."','".$new_account_type."',MD5('".$reg_token."'))";

                        $res = mysql_query($sql) or die(mysql_error());
                        $account_id = mysql_insert_id();
                    }
                    else{
                        $row = mysql_fetch_assoc($res);
                        $account_id = $row['id'];
                    }
                    
		    $_SESSION['user']['auth'] = 'success';
	            $_SESSION['user']['account_id'] = $account_id;
		    $_SESSION['user']['linkedin'] = 1;
                    $_SESSION['user']['external_id'] = $uid;
                    $_SESSION['user']['account_type'] = 'basic';
                    $_SESSION['user']['first_name'] = $cookie['first_name'];
                    $_SESSION['user']['last_name'] = $cookie['last_name'];
                    $_SESSION['user']['fullname'] = $cookie['first_name'] . ' ' . $cookie['last_name'];
		    return $account_id;
		}
	    }
	}

	return 0;
        */
    }

    public function validate_external($account_id, $uid){
        if(!$account_id || !$uid){ return; }
        
        //die('QQQQQQQQQQ');
        $account_id = mysql_real_escape_string($account_id);
        $uid = mysql_real_escape_string($uid);
        
        $sql = "SELECT * FROM ".$this->accounts." WHERE id = '".$account_id."' AND external_id = '".$uid."' LIMIT 1";
        $res = mysql_query($sql);
        if($res){
            if(mysql_num_rows($res) > 0){
                return 1;
            }
            return 0;
        }
        
        return 0;
    }

    public function create_username_external($account_id, $username, $uid){
        if(!$account_id || !$username || !$uid){ return; }
        
        $account_id = mysql_real_escape_string($account_id);
        $username = mysql_real_escape_string(trim($username));
        $uid = mysql_real_escape_string($uid);
        
        $sql = "SELECT * FROM ".$this->accounts." WHERE username IS NULL and id = '".$account_id."' AND external_id = '".$uid."' LIMIT 1";
        $res = mysql_query($sql);
        
        if($res){
            if(mysql_num_rows($res) > 0){
                $sql = "UPDATE ".$this->accounts." SET username = '".$username."' WHERE id = '".$account_id."' LIMIT 1";
                $res = mysql_query($sql);
                if($res){ return 1; }
                
                return 0;
            }
            return 0;
        }
        return 0;
    }

    public function get_username_from_uid($uid){
        if(!$uid){ return; }
        
        $uid = mysql_real_escape_string($uid);
            
        $sql = "SELECT username FROM ".$this->accounts." WHERE external_id = '".$uid."' LIMIT 1";
        //print $sql."<BR>";
        $res = mysql_query($sql);
        if(!$res){ return; }
            
        if(mysql_num_rows($res) > 0){
            $row = mysql_fetch_assoc($res);
            return $row['username'];
        }
            
        return 0;
            
    }

    /**
     * Adds a friend request
     * @param int $account_id and $friend_id
     * @return 1 if added, 2 if already friends/requested, 0 otherwise
     */
    public function _add_friend_request($account_id, $friend_id){
        if(!$account_id || !$friend_id){ return 0; }

        $account_id = mysql_real_escape_string($account_id);
        $friend_id = mysql_real_escape_string($friend_id);

        if(!$this->_check_if_friends($account_id, $friend_id, 1)){
            $sql = "INSERT INTO ".$this->friend_requests."(account_id, friend_id, date_created) VALUES('".$account_id."','".$friend_id."',now())";
            $res = mysql_query($sql);
            if($res){ return 1; }

            return 0;
        }
        else{ return 2; }
    }

    /**
     * Determines if a friend exists or has been requested already
     * @param int $account_id
     * @param int $friend_id
     * @return 1 if already a friend or requested, 0 if not
     */
    public function _check_if_friends($account_id, $friend_id, $request = 0){
        if(!$account_id || !$friend_id){ return 0; }
        
        $account_id = mysql_real_escape_string($account_id);
        $friend_id = mysql_real_escape_string($friend_id);

        $sql = "SELECT id FROM ".$this->friends." WHERE (account_id = '".$account_id."' AND friend_id ='".$friend_id."') || (account_id = '".$friend_id."' AND friend_id ='".$account_id."') AND `type` = 'user' LIMIT 1";
        $res = mysql_query($sql);

        if($request){
	    $sql = "SELECT * FROM ".$this->requests." WHERE to_account = '".$friend_id."' AND from_account = '".$account_id."' AND type = 'colleague request' LIMIT 1";

            $res2 = mysql_query($sql);

            if(mysql_num_rows($res2) > 0){ return 1; }
        }

        if(mysql_num_rows($res) > 0){
            return 1;
        }
        else{
            return 0;
        }
    }

	/**
     * Determines if a relationship is established between a business account and a team
     * @param int $account_id
     * @param int $team_id
     * @return 1 if already a friend or requested, 0 if not
     */
    public function check_if_team_friends($account_id, $team_id){
        if(!$account_id || !$team_id){ return 0; }

        $account_id = mysql_real_escape_string($account_id);
        $team_id = mysql_real_escape_string($team_id);
        
        $sql = "SELECT id FROM ".$this->friends." WHERE account_id = '".$account_id."' AND friend_id ='".$team_id."' AND `type` = 'team' LIMIT 1";
        $res = mysql_query($sql);

        if(mysql_num_rows($res) > 0){
            return 1;
        }
        else{
            return 0;
        }
    }

    /**
     * Declines a friend request
     * @param int $account_id
     * @param int $friend_id
     * @return 1 if declined, 0 if failed, -1 if no request found
     */
    public function _decline_friend($account_id, $friend_id){
        if(!$account_id || !$friend_id){ return 0; }

	$account_id = mysql_real_escape_string($account_id);
	$friend_id = mysql_real_escape_string($friend_id);

	if($this->_check_if_friends($friend_id, $account_id, 1)){
	    $sql = "DELETE FROM ".$this->requests." WHERE from_account = '".$friend_id."' AND to_account = '".$account_id."' AND type = 'colleague request' LIMIT 1";
	    $res = mysql_query($sql);
	    if($res){
	    	return 1;
	    }
	    else{ return 0; }
	}
	else{
	    return -1;
	}
    }

    /**
     * Authorizes a friend request
     * @param int $account_id
     * @param int $friend_id
     * @return 1 if authorized, 0 if not
     */
    public function _authorize_friend($account_id, $friend_id, $type = "message"){
        if(!$account_id || !$friend_id){ return 0; }

        $account_id = mysql_real_escape_string($account_id);
        $friend_id = mysql_real_escape_string($friend_id);

        if($this->_check_if_friends($account_id, $friend_id, 0)){ return -1; }

		if($type == "message"){ // if this was triggered by clicking accept in a message
	        //$sql = "SELECT * FROM ".$this->friend_requests." WHERE account_id = '".$friend_id."' AND friend_id = '".$account_id."' LIMIT 1";
			$sql = "SELECT * FROM ".$this->requests." WHERE from_account = '".$friend_id."' AND to_account = '".$account_id."' AND type = 'colleague request' LIMIT 1";

	        $res = mysql_query($sql);
	        if($res){
	            if(mysql_num_rows($res) > 0){
	                $sql = "INSERT INTO ".$this->friends." (account_id, friend_id) VALUES('".$account_id."','".$friend_id."')";
	                $result = mysql_query($sql);
	                if(!$result){ return 0; }

	                //$sql = "DELETE FROM ".$this->friend_requests." WHERE account_id = '".$friend_id."' AND friend_id = '".$account_id."' LIMIT 1";
					$sql = "DELETE FROM ".$this->requests." WHERE from_account = '".$friend_id."' AND to_account = '".$account_id."' AND type = 'colleague request' LIMIT 1";
	                $result = mysql_query($sql);
	                if(!$result){ return 0; }

	                return 1;
	            }
	            else{ return 0; }
	        }
        }else{ // this was triggered by assigning someone to a job
        	$sql = "INSERT INTO ".$this->friends." (account_id, friend_id) VALUES('".$account_id."','".$friend_id."')";
            $result = mysql_query($sql);
            if(!$result){ return 0; }

			return 1;
        }

        return 0;
    }

	/**
	 * Retrieve account settings for given account id
	 * @param int $account_id
	 * @return array $result; on failure return 0
	 */
	public function get_account_settings($account_id){
		$sql = "SELECT `allowed_contact`,`email_job_requests`,`email_colleague_requests`,`email_team_requests`,`email_messages` FROM `".$this->accounts."` WHERE `id`='".$account_id."' LIMIT 1";
		$result = mysql_query($sql);
		if($result){
			$results = mysql_fetch_array($result);
			return $results;
		}else{
			return 0;
		}

	}

	/**
	 * Update settings for given account id
	 * @param $args (int $account_id, string $username, string $email, array $email_notifications)
	 */
	public function update_account_settings($args){
		extract($args);
		$num_affected = 0;
		
		$notifications_array = array("email_job_requests","email_colleague_requests","email_team_requests","email_messages");
		$to_set = array_intersect($notifications_array,$email_notifications); //create array of items chosen by user
		if(count($email_notifications) != 0){
			$to_unset = array_diff($notifications_array,$to_set); //create array of items not chosen by user
		}else{
			$to_unset = $notifications_array;
		}
		
		$account_id = mysql_real_escape_string($account_id);

		foreach($to_set as $v){
            $v = mysql_real_escape_string($v);
			if($v == "email_messages"){
				$sql = "UPDATE `".$this->accounts."` SET `allowed_contact`='".$allow_email_messages."' WHERE `id`='".$account_id."'";
				mysql_query($sql);	
				if(mysql_affected_rows() == 1){
					$num_affected++;
				}							
			}
			
			$sql = "UPDATE `".$this->accounts."` SET `".$v."`='1' WHERE `id`='".$account_id."'";	
			
			mysql_query($sql);
			if(mysql_affected_rows() == 1){
				$num_affected++;
			}
		}

		foreach($to_unset as $v){
                        $v = mysql_real_escape_string($v);
			$sql = "UPDATE `".$this->accounts."` SET `".$v."`='0' WHERE `id`='".$account_id."'";
			mysql_query($sql);
			if(mysql_affected_rows() == 1){
				$num_affected++;
			}
		}

		return $num_affected;
	}

	/**
	 * Retrieve transaction information for given account id
	 * @param int $account_id
	 */
	public function get_account_transaction($account_id){
		if(is_numeric($account_id)){
			$sql = "SELECT * FROM `talent_earth_dtools_transactions` WHERE `account_id`= '".$account_id."' AND `transaction_status`='APPROVED' LIMIT 1";
			$result = mysql_query($sql);
			if($result){
				if(mysql_num_rows($result) == 1){
					$record = mysql_fetch_array($result);
					return $record;
				}
			}
		}else{
			return 0;
		}
	}

        public function kli_auth($loggedin_token){
            if(!$loggedin_token){ return; }

            $sql = "SELECT * FROM ".$this->accounts." WHERE loggedin_token = '".mysql_real_escape_string($loggedin_token)."' LIMIT 1";
            $res = mysql_query($sql);
            if($res){
                if(mysql_num_rows($res) > 0){
                    $row = mysql_fetch_assoc($res);

                    // login here
                    $_SESSION['user']['auth'] = "success";
                    $_SESSION['user']['account_id'] = $row['id'];
                    $_SESSION['user']['username'] = $row['username'];
                    $_SESSION['user']['account_type'] = $row['account_type'];
                    $_SESSION['user']['first_name'] = $row['first_name'];
                    $_SESSION['user']['last_name'] = $row['last_name'];
                    $_SESSION['user']['email'] = $row['email'];
                    $_SESSION['user']['fullname'] = $row['first_name'].' '.$row['last_name'];

                    return 1;
                }

                return;
            }
            else{ return; }
        }
        
        public function add_share_karma($account_id){
            if(!$account_id){ return; }
            
                $account_id = mysql_real_escape_string($account_id);
                
                $sql = "UPDATE ".$this->accounts." SET karma = karma + ".SHARE_KARMA_POINTS." WHERE id = '".$account_id."' LIMIT 1";
                $res = mysql_query($sql);
                
                if($res){ return 1; }
                
                return 0;
        }
		
	/**
	 * 
	 */	
	public function get_subscription_info($account_id){
		$sql = "SELECT `transaction_date`,`subscription_renewal_date` FROM `".TABLES_PREFIX.TRANSACTIONS."` WHERE `account_id`='".$account_id."' AND `cancelled_date` = '0' AND `subscription_renewal_date` != '0' AND `transaction_status`='APPROVED'";
		$result = mysql_query($sql);
		if($result && mysql_num_rows($result) == 1){
			$rec = mysql_fetch_array($result);
			return $rec;
		}else{
			return 0;
		}
		
	}
	
	/**
	 * get_privacy_settings
	 * @param int $account_id
	 */	
	public function get_privacy_settings($account_id){
		if(is_numeric($account_id)){
			$query = "SELECT * FROM `".$this->privacy_settings."` WHERE `account_id` = '".$account_id."'";
			$result = mysql_query($query);
			if($result && mysql_num_rows($result) == 1){
				$rec = mysql_fetch_array($result);
				return $rec;
			}
		}else{
			return 0;
		}
	}
	
	/**
	 * save_privacy_settings
	 * @param array $args
	 */
	public function save_privacy_settings($args){
		extract($args);
		$num_updated = 0;
		$settings_off = array_diff($this->privacy_settings_const,$args['setting']);

		if(count($args['setting']) > 0){
			//Set settings that need to be turned on
			foreach($args['setting'] as $setting){
				$query = "UPDATE `".$this->privacy_settings."` SET `".$setting."` = '1' WHERE `account_id` = '".$account_id."'";
				if(mysql_query($query)){
					$num_updated++;
				}
			}			
		}else{ //settings are all turned off
			foreach($this->privacy_settings_const as $setting){
				$query = "UPDATE `".$this->privacy_settings."` SET `".$setting."` = '0' WHERE `account_id` = '".$account_id."'";
				if(mysql_query($query)){
					$num_updated++;
				}
			}			
		}
		
		//settings that need to be turned off
		if(count($settings_off) > 0){
			//Set settings that need to be turned off
			foreach($settings_off as $setting){
				$query = "UPDATE `".$this->privacy_settings."` SET `".$setting."` = '0' WHERE `account_id` = '".$account_id."'";
				if(mysql_query($query)){
					$num_updated++;
				}
			}			
		}
		if($num_updated > 0){
			return 1;
		}else{
			return 0;
		}

	}

  /**
   * @param int $account_id
   * @param null $account_type
   */
  public function create_privacy_settings($account_id, $account_type = null)
  {
    $query = "INSERT INTO `".$this->privacy_settings."` (account_id) VALUES (".$account_id.");";
    if ($account_type == 'business basic' || $account_type == 'business pro')
    {
      $query = "INSERT INTO `" . $this->privacy_settings . "` (account_id, photo, location, summary, availability)
        VALUES (" . $account_id . ", '1', '1', '1', '1');";
    }
    mysql_query($query);
  }

  /**
   * @param int $days
   * @return array
   */
  public function get_expired_in($days)
  {
    $results = array();
    $sql = sprintf("SELECT * FROM `%s` WHERE DATE_SUB(DATE(FROM_UNIXTIME(`expiration_date`)), INTERVAL %d DAY) = '%s'
      AND `status` = '1' AND (`account_type` = 'business pro' OR `account_type` = 'pro')
      AND `last_notification_day` != '%d'", $this->accounts, $days, date('Y-m-d'), $days);
    $result = mysql_query($sql);

    if ($result && mysql_num_rows($result))
    {
      while ($row = mysql_fetch_assoc($result))
      {
        $results[] = $row;
      }
    }

    return $results;
  }

  public function change_level_for_expired()
  {
    $accounts = $this->get_expired_in(0);

    foreach ($accounts as $account)
    {
      $new_account_type = $account['account_type'] == 'business pro' ? 'business basic' : 'basic';

      $sql = sprintf("UPDATE `%s` SET `last_notification_day` = '0', `account_type` = '%s', `expiration_date` = '%d'
        WHERE `id` = '%d'", $this->accounts, $new_account_type, strtotime('+1 year'), $account['id']);
      mysql_query($sql);
    }
  }

  /**
   * @param int $account_id
   */
  public function set_affiliate_initial_expiration($account_id)
  {
    $sql = sprintf("UPDATE `%s` SET `last_notification_day` = '-1', `expiration_date` = '%d' WHERE `id` = '%d'",
      $this->accounts, strtotime('+10 days'), $account_id);
    mysql_query($sql);
  }

  public function prolong_basic_accounts()
  {
    $sql = sprintf("UPDATE `%s` SET `expiration_date` = UNIX_TIMESTAMP(DATE_ADD(NOW(), INTERVAL 1 YEAR))
      WHERE (`account_type` = 'basic' OR `account_type` = 'business basic') AND
      DATE_SUB(DATE(FROM_UNIXTIME(`expiration_date`)), INTERVAL 10 DAY) = CURDATE()", $this->accounts);
    mysql_query($sql);

    print $sql;
  }

}
