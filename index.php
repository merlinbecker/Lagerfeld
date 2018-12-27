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
require_once "classes/Output.class.php";
/***********************************************/
/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

define("VERSION",0.2);
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

$output=new Output();
$output->setHeader($args);

switch($args['commands'][0]){
	case "User":
		if($args['commands'][1]=="Status"){
			$output->setPayload($user->logIn());
			$output->sendOutput();
		}
		else if($args['commands'][1]=="LogOut"){
			$output->setPayload($user->logout());
			$output->sendOutput();
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

?>

