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
require_once "classes/Item.class.php";
/***********************************************/
/**
 * headers: allow cross origin access
 **/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
header("Access-Control-Allow-Origin: *");

define("VERSION","0.4.3 alpha");
session_start();

//ob_start();


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
	case "Items":
		if($args['method']=="POST"||$args['method']=="PUT"){
			$outdata=array();
			$outdata['user']=$user->logIn();
			if($user->isLoggedIn()){
				$data=json_decode(file_get_contents('php://input'));
				$item=new Item($db,$user);
				if(isset($data->id)){
					$outdata['item']=$item->update($data);
				}
				else{
					$outdata['item']=$item->create($data);
				}
				$output->setPayload($outdata);
				$output->sendOutput();
			}
		}
		else if($args['method']=="GET"){
			$outdata=array();
			$outdata['user']=$user->logIn();
			if($user->isLoggedIn()){
				$item=new Item($db,$user);
				$outdata['items']=$item->getItem();			
				$outdata['categories']=$item->getCategories();
			}
			$output->setPayload($outdata);
			$output->sendOutput();
		}
	break;
	case "User":
		if($args['commands'][1]=="Status"){
			$output->setPayload(array("user"=>$user->logIn()));
			$output->sendOutput();
		}
		else if($args['commands'][1]=="LogOut"){
			$output->setPayload(array("user"=>$user->logout()));
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
