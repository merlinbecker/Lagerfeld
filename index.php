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

//do some db setup, if not existing
//todo: refactor this
if(!isset($_SESSION['db_version'])||($_SESSION['db_version']!=VERSION)){
	echo "------------------<br/>"
	."doing db_checks <br/>"
	."---------------------<br/>";
	
	try{
		$dbcheck = $db->run("SELECT 1 FROM settings");
	}catch(Exception $e){
		if($e->getCode()=="42S02"){
			$db->run("CREATE TABLE settings ( "
						."`key` VARCHAR(128) NOT NULL ,"
						." `value` VARCHAR(128) NOT NULL,"
						."PRIMARY KEY (`key`)) ENGINE = InnoDB;");
			$db->insert('settings',[
				'key'=>'settings',
				'value'=>VERSION
			]);
		}
		else echo $e->getMessage();
	}


	
	$_SESSION['db_version']=VERSION;
	$db->update('settings',['value' => VERSION],['key' => 'version']);
}


	
	//$userData = $db->row("SELECT value FROM settings WHERE key=\"version\"");
/*try{
	$userData = $db->row("SELECT value FROM settings WHERE key='version'");
}	
catch(Exception $e){
	echo "EXPEPTION!";
	echo $e->getMessage();
}*/
/*
$result=

$rows = $db->run('SELECT * FROM comments WHERE blogpostid = ? ORDER BY created ASC', $_GET['blogpostid']);
foreach ($rows as $row) {
    $template_engine->render('comment', $row);
}
*/

$user=new User($conf,$db);


echo "index!";
echo "<pre>";
print_r(parseServerArguments());
print_r($user->logIn());
echo "</pre>";

?>

