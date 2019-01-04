<?php
/**
class Item
@version 1.0.0
@since 0.4.1
@author Merlin Becker
**/
class Item{
	const HISTORY_CREATE_ITEM=1;
	const HISTORY_ADD_TO_PARENT=2;
	const HISTORY_CHANGE_ITEM=3;
	const HISTORY_DELETE_ITEM=4;

	var $db;
	var $user;
	function __construct($database,$user){
		$this->db=$database;
		$this->user=$user;
	}
	public function create($data){
		$insertArray=array();
		$insertArray['uid']=$this->user->getUserId();
		if(isset($data->parent)){
			if($data->parent>0)
				$insertArray['parent']=$data->parent;
		}
		$insertArray['name']=$data->itemname;
		if(isset($data->picture)){
			if($data->picture!=""){
				$insertArray['picture']=$this->processPictureData($data->picture);	
			}
		}
		if(isset($data->best_before))$insertArray['best_before']=strtotime($data->best_before);
		if(isset($data->url))$insertArray['url']=$data->url;
		if(isset($data->location_desc))$insertArray["location_desc"]=$data->location_desc;
		if(isset($data->isContainer))$insertArray['container']=1;
		/**
		@todo lon lat level fehlen noch
		@todo categories auslagern
		**/
		$categories=array();
		if(isset($data->categories)){
			$cats=explode(",",$data->categories);
			foreach($cats as &$cat){
				$cat=trim($cat);
				if($cat=="")continue;
				$catnr=$this->db->single("SELECT `id` FROM categories WHERE name=?",array($cat));
				if(!$catnr){
					$catnr=$this->db->insertGet('categories',array("name"=>$cat,"uid"=>$this->user->getUserId()),"id");
				} 
				$categories[]=$catnr;
			}
		}	

		for($i=0;$i<$data->number;$i++){
			$ident=$this->db->insertGet('item',$insertArray,"id");
			$outdata[]=$ident;
			foreach($categories as $cat){
				$this->db->insert("item_categories",array("iid"=>$ident,"cid"=>$cat));
			}
				
			if(isset($data->comments)){
				$this->db->insert("item_comments",array("iid"=>$ident,"comment"=>$data->comments,"time"=>time()));
			}
			$this->writeHistory(self::HISTORY_CREATE_ITEM,$ident);
			if(isset($insertArray['parent'])){
				$this->writeHistory(self::HISTORY_ADD_TO_PARENT,$ident,$insertArray['parent']);	
			}
		}

		return $this->getItem($outdata);
	}
	private function writeHistory($action,$ident,$value=0){
		$insertvals=array();
		if($value==0) $insertvals['value']=$value;
		$insertvals['iid']=$ident;
		$insertvals['timestamp']=time();
		switch($action){
			case self::HISTORY_CREATE_ITEM:
				$insertvals['task']="created";
			break;
			case self::HISTORY_ADD_TO_PARENT:
				$insertvals['task']="to parent";
			break;
			case self::HISTORY_CHANGE_ITEM:
				$insertvals['task']="changed";
			break;
			case self::HISTORY_DELETE_ITEM:
				$insertvals['task']="deleted";
			break;
		}
		$this->db->insert("item_history",$insertvals);
	}
	public function getItem($ids=0){
		$outdata=array();
		$cond=is_array($ids)?"AND id IN (".implode(",",$ids).") ":" ";
		$query="SELECT id,parent as parent_id,name,picture,container,COUNT(id) as anzahl "
			."FROM item "
			."WHERE deleted=0 "
			."AND (uid=? or uid=0) "
			.$cond
			."GROUP BY name,parent,container,picture "
			."ORDER BY container DESC";
		$rows=$this->db->run($query,$this->user->getUserId());
		foreach($rows as $row){
			$row['categories']=$this->db->single("SELECT GROUP_CONCAT(cid) FROM item_categories WHERE iid=?",array($row['id']));
			$outdata[]=$row;
		}
		return $outdata;
	}
	public function getCategories($ids=0){
		$cond=is_array($ids)?"AND item.id IN (".implode(",",$ids).") ":" ";
		$query="SELECT categories.name,categories.id,COUNT(categories.id) as items " 
		."FROM categories, item_categories,item "
		."WHERE cid=categories.id "
		."AND item.uid=? "
		."AND deleted=0 "
		."AND item.id=iid "
		.$cond 
		."GROUP BY categories.name;";
		$rows=$this->db->run($query,$this->user->getUserId());
		return $rows;
	}
	public function update($data){
		$this->db->update('item',(array)$data,
 		[
			'id'=>$data->id,
    			'uid' => $this->user->getUserId()
		]);
		if(isset($data->parent)){
			$this->writeHistory(self::HISTORY_ADD_TO_PARENT,$data->id,$data->parent);	
		}
		else if(isset($data->deleted)){
			$this->writeHistory(self::HISTORY_DELETE_ITEM,$data->id);
		}
		else $this->writeHistory(self::HISTORY_CHANGE_ITEM,$data->id);
		return $this->getItem(array($data->id));
	}
	
	private function processPictureData($base64pic){
		if(!is_dir("data")){
			if(!mkdir("data"))throw new \Exception("no permission to create data directory");	
		}
		if (preg_match('/^data:image\/(\w+);base64,/', $base64pic, $type)) {
    			$picdata = substr($base64pic, strpos($base64pic, ',') + 1);
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
		$filename=uniqid("lf").".".$type;
		if(!file_put_contents("data/".$filename, $picdata)){
			throw new \Exception("could not save image file");
		}
		return $filename;
	}
}
