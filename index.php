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
*
*/
/**
* Requirements
**/
/*************************************************/
require_once "vendor/autoload.php";
require_once "lf_config.php";
require_once "classes/User.class.php";
require_once "classes/Helper.functions.php";

/***********************************************/
/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

define("VERSION",0.1);
session_start();

//ob_start();

/**
 * @TODO make a helper class out of this (and the absolute url)
 ***/

if(file_exists($CONFIG_PATH)){
	$conf=(array)json_decode(urldecode(file_get_contents($CONFIG_PATH)));
}
else{
	$conf=array();
}
$db_conf=$conf['db_credentials'];
$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host='.$db_conf->host.';dbname='.$db_conf->database,
    $db_conf->user,
    $db_conf->password
);

doDBChecks($db);

$user=new User($conf,$db);
$args=parseServerArguments();

switch($args['commands'][0]){
	case "User":
		if($args['commands'][1]=="Status"){
			echo json_encode($user->logIn());
		}
		else if($args['commands'][1]=="Logout"){
			echo json_encode($user->logout());
		}
		else if($args['commands'][1]=="LogIn"){
			header("Location:".$user->getLoginUrl());
		}
	break;
	case "auth":
		$user->logIn();
		header("Location:".$BASE_URL);
	break;
	default:
		include("frontend/website.html");
	break;
}


echo "<pre>";
print_r($args);
echo "</pre>";
?>

