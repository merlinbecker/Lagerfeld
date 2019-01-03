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

define("VERSION","0.4.1 alpha");
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
	case "Items":
		if($args['method']=="POST"||$args['method']=="PUT"){
			$outdata=array();
			$outdata['user']=$user->logIn();
			if($user->isLoggedIn()){
			//@todo refactor in class
			//@todo how test a working refactoring?
			$data=json_decode(file_get_contents('php://input'));
			if(isset($data->id)){
				//update 
				//@todo check if permission is right to change
			}
			else{
			/**@todo auslagern*/
				$insertArray=array();
				$insertArray['uid']=$user->getUserId();
				if(isset($data->parent)){
					if($data->parent>0)
						$insertArray['parent']=$data->parent;
				}
				$insertArray['name']=$data->itemname;
				if(isset($data->picture)){
					if($data->picture!=""){	
					if(!is_dir("data")){
						mkdir("data");	
					}
					if (preg_match('/^data:image\/(\w+);base64,/', $data->picture, $type)) {
    						$picdata = substr($data->picture, strpos($data->picture, ',') + 1);
    						$type = strtolower($type[1]); // jpg, png, gif

   	 					if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
        						throw new \Exception('invalid image type');
   					 	}	

    						$picdata = base64_decode($picdata);
    						if ($picdata === false) {
        						throw new \Exception('base64_decode failed');
   					 	}
					} else {
    						throw new \Exception('did not match data URI with image data');
					}
					$insertArray['picture']=$outdata['filename']=uniqid("lf").".".$type;
					file_put_contents("data/".$outdata['filename'], $picdata);
					}
				}
				if(isset($data->best_before))$insertArray['best_before']=strtotime($data->best_before);
				if(isset($data->url))$insertArray['url']=$data->url;
				if(isset($data->location_desc))$insertArray["location_desc"]=$data->location_desc;
				if(isset($data->isContainer))$insertArray['container']=1;
				/**
				@todo lon lat level fehlen noch
				**/
				$categories=array();
				if(isset($data->categories)){
					$cats=explode(",",$data->categories);
					foreach($cats as &$cat){
						$cat=trim($cat);
						if($cat=="")continue;
						$catnr=$db->single("SELECT COUNT(`id`) FROM categories WHERE name=?",array($cat));
						if($catnr==0){
							$catnr=$db->insertGet('categories',array("name"=>$cat,"uid"=>$user->getUserId()),"id");
						}$categories[]=$catnr;
					}
				}	

				for($i=0;$i<$data->number;$i++){
					$ident=$db->insertGet('item',$insertArray,"id");
					$outdata['created'][]=$ident;
					foreach($categories as $cat){
						$db->insert("item_categories",array("iid"=>$ident,"cid"=>$cat));
					}
				
					if(isset($data->comments)){
						$db->insert("item_comments",array("iid"=>$ident,"comment"=>$data->comments,"time"=>time()));
					}
					$db->insert("item_history",array("iid"=>$ident,"task"=>"created","timestamp"=>time()));
					if(isset($insertArray['parent'])){
						$db->insert("item_history",array("iid"=>$ident,"task"=>"to parent","value"=>$insertArray['parent'],"timestamp"=>time()));
					}
					
				}

							
			}}


			$outdata['item'] = $db->run("SELECT id,parent as parent_id,name,picture,container,COUNT(id) as anzahl FROM item WHERE deleted=0 AND (uid=? or uid=0) AND id IN (".implode(",",$outdata['created']).") GROUP BY name,parent,container,picture ORDER BY parent ASC",$user->getUserId());
			$output->setPayload($outdata);
			$output->sendOutput();
		}
		else if($args['method']=="GET"){
			$outdata=array();
			$outdata['user']=$user->logIn();
			if($user->isLoggedIn()){
				/**
				@todo: freigegebene Items für andere auch beachten! zum beispiel für den marktplatz (spätere version)
				**/
				$rows = $db->run("SELECT id,parent as parent_id,name,picture,container,parent,COUNT(id) as anzahl FROM item WHERE deleted=0 AND uid=? or uid=0 GROUP BY name,parent,container,picture ORDER BY parent ASC",$user->getUserId());
				foreach($rows as $row){
					$row['categories']=$db->single("SELECT GROUP_CONCAT(cid) FROM item_categories WHERE iid=?",array($row['id']));;

					$outdata['items'][]=$row;
				}
				$outdata['categories']=$db->run("SELECT DISTINCT categories.name,categories.id,COUNT(categories.id) as items FROM categories, item_categories,item WHERE cid=categories.id AND item.uid=? AND deleted=0 AND item.id=iid GROUP BY categories.id;",$user->getUserId());
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

