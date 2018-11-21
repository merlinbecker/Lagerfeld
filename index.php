<?php
/**
* Lagerfeld Smart Home Storage API
* @author Merlin Becker
* @version 0.1.0
* @created 20.11.2018
**/


/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

ob_start();


/**
 * @TODO make a helper class out of this (and the absolute url)
 * **/

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

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => 'demoapp',    // The client ID assigned to you by the provider
    'clientSecret'            => 'demopass',   // The client password assigned to you by the provider
    'redirectUri'             => 'http://example.com/your-redirect-url/',
    'urlAuthorize'            => 'http://brentertainment.com/oauth2/lockdin/authorize',
    'urlAccessToken'          => 'http://brentertainment.com/oauth2/lockdin/token',
    'urlResourceOwnerDetails' => 'http://brentertainment.com/oauth2/lockdin/resource'
]);


echo "index!";

echo "<pre>";
print_r(parseServerArguments());
echo "</pre>";

?>

