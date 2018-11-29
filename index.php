<?php
/**
* Lagerfeld Smart Home Storage API
* @author Merlin Becker
* @version 0.1.0
* @created 20.11.2018
*
* @todo always refactor!

*currently doing: OAUTH2
*/

/**
* Requirements
**/
/*************************************************/
require_once "vendor/autoload.php";
require_once "lf_config.php";

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
/***********************************************/
/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

session_start();

echo "<pre>";
	print_r($_SESSION);
echo "</pre>";

//ob_start();



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

//config session var. wenn 
if(isset($_POST['access_token'])) $_SESSION['access_token']=$_POST['access_token'];


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
        //$resourceOwner = $provider->getResourceOwner($accessToken);
		/*
		echo "<pre>";
			print_r($resourceOwner);
		echo "</pre>";
		
        $owner=$resourceOwner->toArray();
		
		echo "OWNER!";
		echo "<pre>";
			print_r($owner);
		echo "</pre>";
		*/
		$oauth=(array)$conf['oauth_credentials'];
		
		
		stream_context_set_default(
		 array(
		  'http' => array(
		   'proxy' => "tcp://".$oauth['proxy']
		   // Remove the 'header' option if proxy authentication is not required
		  )
		 )
		);
		
		$jwks_json = file_get_contents($oauth['urlResourceOwnerDetails']);
		$jwk = JWK::parseKeySet($jwks_json);

		$tks = explode('.', $accessToken->getToken());
		list($headb64, $bodyb64, $cryptob64) = $tks;
		$jwt_header = json_decode(base64_decode($headb64),true);
		$jwt_body = json_decode(base64_decode($bodyb64),true);
		$key=$jwk[$jwt_header["kid"]];

		try
		{
			$decoded = JWT::decode($accessToken->getToken(), $key, array($jwt_header["alg"]));
			$decoded_array = (array) $decoded;
			
			echo "<pre>";
				print_r($decoded);
			echo "</pre>";
			
			// GREAT SUCCESS!
		}
		catch (\Exception $e)
		{
			// TOKEN COULDN'T BE VALIDATED
		}
		
		
		
		
        // The provider provides a way to get an authenticated API request for
        // the service, using the access token; it returns an object conforming
        // to Psr\Http\Message\RequestInterface.
     /*   $request = $provider->getAuthenticatedRequest(
            'GET',$conf['oauth_credentials']['urlResourceOwnerDetails'],
            $accessToken
        );
	*/
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

