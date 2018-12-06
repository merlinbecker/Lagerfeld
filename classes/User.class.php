<?php
/**
class User.
uses git from https://github.com/thephpleague/oauth2-client
**/

class User{
	var $userdata;
	var $provider;
	function __construct($conf,$database){
		$this->provider= new \League\OAuth2\Client\Provider\GenericProvider((array)$conf['oauth_credentials']);
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
		$output['userdata']=$this->userdata;
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
			//try to connect, if not successful, try to get a refresh token
			//check database for access token!
			/*
			
			
			$existingAccessToken = new \League\OAuth2\Client\Token(array("access_token"=>$_POST['access_token']));
			
			if ($existingAccessToken->hasExpired()) {
				$newAccessToken = $provider->getAccessToken('refresh_token', [
					'refresh_token' => $existingAccessToken->getRefreshToken()
				]);
				// Purge old access token and store new access token to your data store.
			}
			
			*/
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
						//@todo: store userdata to database
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