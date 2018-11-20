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

$accepts=explode(", ",$_SERVER['HTTP_ACCEPT']);
$method=$_SERVER['REQUEST_METHOD'];
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
$params[]=$tempparams[1];
$params[]=$tempparams[2];
$params[]=$tempparams[3];
$url_query=array();

parse_str($query_params,$url_query);


if($_SERVER['argc']>0){	
	echo "hier her!!";
	parse_str($_SERVER['argv'][0],$url_query);
}


echo "index!";

echo "<pre>";
print_r($params);
print_r($url_query);
echo "</pre>";

?>

