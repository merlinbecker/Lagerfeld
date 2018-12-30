<?php
/**
* parseServerArguments: übernimmt das Extrahieren der Kommandoparameter aus den Servervariablen
* @return Command and Query Params als Array
* @todo refactor doDbChecks: auslagern in Datei
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

function doDBChecks($db){
	//do some db setup, if not existing
	if(!isset($_SESSION['db_version'])||($_SESSION['db_version']!=VERSION)){
		
		//check the settings table
		
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
			else return $e->getMessage();
		}
		
		//check the users table
		try{
			$dbcheck = $db->run("SELECT 1 FROM users");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE users ( "
							."`id` BIGINT NOT NULL AUTO_INCREMENT,"
							."`email` VARCHAR(255) NOT NULL,"
							."PRIMARY KEY (`id`)) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM user_history");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE user_history ( "
							."`uid` BIGINT NOT NULL ,"
							."`task` VARCHAR(255) NOT NULL ,"
							."`value` BIGINT NOT NULL ,"
							."`timestamp` BIGINT NOT NULL ) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM item");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE item ( "
						."`id` BIGINT NOT NULL AUTO_INCREMENT ,"
						."`name` VARCHAR(255) NOT NULL ,"
						."`picture` TEXT ,"
						."`best_before` BIGINT,"
						."`url` VARCHAR(255) ,"
						."`parent` BIGINT DEFAULT 0 ,"
						."`uid` BIGINT NOT NULL ,"
						."`location_desc` TEXT ,"
						."`lat` DOUBLE ,"
						."`lng` DOUBLE ,"
						."`level` TINYINT,"
						."`deleted` TINYINT(1) DEFAULT 0,"
						."`container` TINYINT(1) DEFAULT 0,"
						."PRIMARY KEY (`id`)) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM item_history");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE item_history ( "
						."`iid` BIGINT NOT NULL ,"
						."`task` VARCHAR(255) NOT NULL ,"
						."`value` BIGINT NULL ,"
						."`timestamp` BIGINT NOT NULL ) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM item_future");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE item_future ( "
						."`iid` BIGINT NOT NULL ,"
						."`task` VARCHAR(255) NOT NULL ,"
						."`value` BIGINT NOT NULL) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM categories");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE categories ( "
						."`id` BIGINT NOT NULL AUTO_INCREMENT ,"
						."`name` VARCHAR(255) NOT NULL ,"
						."`uid` BIGINT NOT NULL,"
						."`color` varchar(7),"
						."`icon` varchar (32),"
						."PRIMARY KEY (`id`)) ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		
		try{
			$dbcheck = $db->run("SELECT 1 FROM item_comments");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE item_comments ( "
						."`iid` BIGINT NOT NULL, "
						."`comment` VARCHAR(255) NOT NULL ,"
						."`time` BIGINT NOT NULL)"
						."ENGINE = InnoDB;");
			}
			else return $e->getMessage();
		}
		

		try{
			$dbcheck = $db->run("SELECT 1 FROM item_categories");
		}catch(Exception $e){
			if($e->getCode()=="42S02"){
				$db->run("CREATE TABLE item_categories ( "
						."`iid` BIGINT NOT NULL ,"
						."`cid` VARCHAR(255) NOT NULL ) ENGINE = InnoDB;");		
			}
			else return $e->getMessage();
		}
		
		$_SESSION['db_version']=VERSION;
		$db->update('settings',['value' => VERSION],['key' => 'version']);
	}
}
?>
