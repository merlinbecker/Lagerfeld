<?php
/**
* Lagerfeld Smart Home Storage API
* @author Merlin Becker
* @version 0.1.0
* @created 20.11.2018
*
* @todo always refactor!

*currently doing as refactor: OAUTH
*currently doing: Database

* https://packagist.org/packages/slim/pdo
*/

/**
* Requirements
**/
/*************************************************/
require_once "vendor/autoload.php";
require_once "lf_config.php";

/***********************************************/
/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

session_start();

//ob_start();

class User{
	var $email;
	var $userdata;
	var $oauth_token;
	var $provider;
	function __construct($conf,$database){
		$this->provider= new \League\OAuth2\Client\Provider\GenericProvider((array)$conf['oauth_credentials']);
		$this->logIn();
	}
	
	function isLoggedIn(){
		return (isset($this->userdata)&&is_array($this->userdata))?true:false;
	}
	private function getNotLoggedInStatus(){
		$output=array();
		$output['status']='not logged in';
		$output['authURL']=$this->provider->getAuthorizationUrl();
		return $output;
	}
	
	function logIn(){
		//if the user already has a session id
		if(isset($_SESSION['userdata'])&&(is_numeric($_SESSION['userdata'])){
			$this->userdata=$_SESSION['userdata'];
			return $_SESSION['userdata'];
		}
		//if the user sends a oauth access token
		elseif(isset($_POST['access_token'])){
			//try to connect, if not successful, try to get a refresh token
			new \League\OAuth2\Client\Token();
			$existingAccessToken = getAccessTokenFromYourDataStore();

			if ($existingAccessToken->hasExpired()) {
				$newAccessToken = $provider->getAccessToken('refresh_token', [
					'refresh_token' => $existingAccessToken->getRefreshToken()
				]);

				// Purge old access token and store new access token to your data store.
			}
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
						$accessToken = $provider->getAccessToken('authorization_code', [
							'code' => $_GET['code']
						]);
						
						// We have an access token, which we may use in authenticated
						// requests against the service provider's API.
						echo 'Access Token: ' . $accessToken->getToken() . "<br>";
						echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
						echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
						echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";

						// Using the access token, we may look up details about the
						// resource owner.
						$resourceOwner = $provider->getResourceOwner($accessToken);
						echo "<pre>";
							print_r($resourceOwner);
						echo "</pre>";
						
						$owner=$resourceOwner->toArray();
						
						echo "OWNER!";
						echo "<pre>";
							print_r($owner);
						echo "</pre>";
						
						$oauth=(array)$conf['oauth_credentials'];
						echo "<pre>";
						print_r($oauth);
						echo "</pre>";
					} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
						// Failed to get the access token or user details.
						unset($_SESSION);
						session_destroy();
						exit($e->getMessage());
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
		unset $_SESSION['userdata'];
	}
}


/**
 * @TODO make a helper class out of this (and the absolute url)
 ***/

/**
* parseServerArguments: übernimmt das Extrahieren der Kommandoparameter aus den Servervariablen
* @return Command and Query Params als Array
**/
function parseServerArguments(){
	$output=array();
	$output['accept']=explode(", ",$_SERVER['HTTP_ACCEPT']);
	$output['method']=$_SERVER['REQUEST_METHOD'];
	
	$url_prefix=str_replace("index.php","",$_SERVER['SCRIPT_NAME']);
	$temp_url="/".str_replace($url_prefix, "", $_SERVER['REQUEST_URI']);


	//remove query from request uri
	$query_params=substr($temp_url,strpos($temp_url,"?")+1);
	if(!strpos($temp_url,"?")===false)
		$req_uri=substr($temp_url,0,strpos($temp_url,"?"));
	else 
		$req_uri=$temp_url;
	$tempparams=explode("/",$req_uri);
	$params=array();
	for($i=0;$i<count($tempparams);$i++){
		if($tempparams[$i]!="")
			$params[]=$tempparams[$i];
	}
	
	$output['commands']=$params;

	$url_query=array();
	parse_str($query_params,$url_query);

	if($_SERVER['argc']>0){	
		parse_str($_SERVER['argv'][0],$url_query);
	}
	$output['query']=$url_query;

	return $output;
}


if(file_exists($CONFIG_PATH)){
	$conf=(array)json_decode(urldecode(file_get_contents($CONFIG_PATH)));
}
else{
	$conf=array();
}

$provider = new \League\OAuth2\Client\Provider\GenericProvider((array)$conf['oauth_credentials']);


// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {
    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
	
    exit('Invalid state, piss off!');
} else {
    try {
		
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
		
        // We have an access token, which we may use in authenticated
        // requests against the service provider's API.
        echo 'Access Token: ' . $accessToken->getToken() . "<br>";
        echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
        echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
        echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";

        // Using the access token, we may look up details about the
        // resource owner.
        $resourceOwner = $provider->getResourceOwner($accessToken);
		
		echo "<pre>";
			print_r($resourceOwner);
		echo "</pre>";
		
        $owner=$resourceOwner->toArray();
		
		echo "OWNER!";
		echo "<pre>";
			print_r($owner);
		echo "</pre>";
		
		$oauth=(array)$conf['oauth_credentials'];
		echo "<pre>";
   
     print_r($oauth);
	echo "</pre>";
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        // Failed to get the access token or user details.
		unset($_SESSION);
		session_destroy();
		
        exit($e->getMessage());
    }

}

echo "index!";
echo "<pre>";
print_r(parseServerArguments());
echo "</pre>";

?>

