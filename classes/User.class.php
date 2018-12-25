<?php
/**
class User.
uses git from https://github.com/thephpleague/oauth2-client
**/

class User{
	var $userdata;
	var $provider;
	var $db;
	var $userid;
	var $conf;
	function __construct($conf,$database){
		$this->userid=0;
		$this->conf=(array)$conf['oauth_credentials'];
		$this->provider= new \League\OAuth2\Client\Provider\GenericProvider($this->conf);
		$this->db=$database;
	}
	
	function getUserId(){
		if($this->userid<=0){
			$id=$db->row("SELECT id FROM users WHERE email=?",$this->userdata['email']);
			$this->userid=$id['id'];
		}
		return $this->userid;
	}
	
	function isLoggedIn(){
		return (isset($this->userdata)&&is_array($this->userdata))?true:false;
	}
	
	private function getNotLoggedInStatus($msg=""){
		$output=array();
		$output['status']='not logged in';
		$output['authURL']=$this->provider->getAuthorizationUrl();
		$output['message']=$msg;
		return $output;
	}
	
	
	private function getLoggedInStatus(){
		$output=array();
		$output['status']='logged in';
		$output['userdata']=array(
			"access_token"=>$this->userdata['access_token']
		);
		//insert login time
		$uid=$this->getUserId();
		
		return $output;
	}
	
	function logIn(){
		//if the user already has a session id
		if(isset($_SESSION['userdata'])&&(is_numeric($_SESSION['userdata']))){
			$this->userdata=$_SESSION['userdata'];
			return getLoggedInStatus();
		}
		//if the user sends a oauth access token
		elseif(isset($_POST['access_token'])){
			        $request = $provider->getAuthenticatedRequest(
            			'GET',
            			$this->conf['urlResourceOwnerDetails'],
            			$_POST['access_token']
        			);
				echo "<pre>";
				print_r($request);
				echo "</pre>";

			/*//try to connect, if not successful, try to get a refresh token
			$oauth=$db->row("SELECT oauth FROM users WHERE access_token=?",$_POST['access_token']);
			$oauth=unserialize($oauth['oauth']);
			
			
			$existingAccessToken = new \League\OAuth2\Client\Token(array(
				"access_token"=>$oauth['access_token'],
				"expires"=>$oauth['expires'],
				"refresh_token"=>$oauth['refresh_token']
			));
			
			if ($existingAccessToken->hasExpired()) {
				$newAccessToken = $provider->getAccessToken('refresh_token', [
					'refresh_token' => $existingAccessToken->getRefreshToken()
				]);
				
				$resourceOwner = $this->provider->getResourceOwner($newAccessToken);
				$owner=$resourceOwner->toArray();
						
				$this->userdata=array(
					"access_token"=>$newAccessToken->getToken(),
					"refresh_token"=>$newAccessToken->getRefreshToken(),
					"expires"=>$newAccessToken->getExpires(),
					"email"=>$owner["email"]
				);
						
						
				$this->db->update('users', [
					'oauth' => serialize($this->userdata),
					'access_token'=>$accessToken->getToken()
				], [
					'email' => $this->userdata['email']
				]);
	
						
				$this->db->insert('user_history', [
						'uid' => $uid,
						'task' => "login",
						'value'=>"refresh_token request",
						'timestamp'=>time()
				]);
				
				$_SESSION['userdata']=$this->userdata;
				return $this->getLoggedInStatus();				
			}*/
		}
		//if the user has got a grant token
		elseif(isset($_GET['code'])){
			if (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
				if (isset($_SESSION['oauth2state'])) {
					unset($_SESSION['oauth2state']);
				}
				return $this->getNotLoggedInStatus();
			}
			else{
				  try {
						// Try to get an access token using the authorization code grant.
						$accessToken = $this->provider->getAccessToken('authorization_code', [
							'code' => $_GET['code']
						]);
						
						$resourceOwner = $this->provider->getResourceOwner($accessToken);
						$owner=$resourceOwner->toArray();
						
						$this->userdata=array(
							"access_token"=>$accessToken->getToken(),
							"refresh_token"=>$accessToken->getRefreshToken(),
							"expires"=>$accessToken->getExpires(),
							"email"=>$owner["email"]
						);
						
						$mail=$this->db->row("SELECT * FROM users WHERE email = ?",$this->userdata['email']);
						//create user if not exists
						if(strlen($mail['email'])<3){
							$this->db->insert('users', [
								'email' => $this->userdata['email']//,
								//'access_token'=>$accessToken->getToken(),
								//'oauth' => serialize($this->userdata)
							]);
						}else{
							/*$this->db->update('users', [
								'oauth' => serialize($this->userdata),
								'access_token'=>$accessToken->getToken()
							], [
								'email' => $this->userdata['email']
							]);*/
						}
						
						$this->db->insert('user_history', [
								'uid' => $uid,
								'task' => "login",
								'timestamp'=>time()
							]);
						
						$_SESSION['userdata']=$this->userdata;
						return $this->getLoggedInStatus();
					} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
						// Failed to get the access token or user details.
						unset($_SESSION['userdata']);
						return $this->getNotLoggedInStatus($e->getMessage());
					}
			}
		}
		else{
			// Get the state generated for you and store it to the session.
			$_SESSION['oauth2state'] = $this->provider->getState();
			return $this->getNotLoggedInStatus();
		}
	}
	
	function logout(){
		unset ($_SESSION['userdata']);
		return $this->getNotLoggedInStatus();
	}
}
?>
