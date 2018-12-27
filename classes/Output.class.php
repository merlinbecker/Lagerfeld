<?php
class Output{
	var $header;
	var $payload;
	function __construct(){
		$this->header=array();
		$this->payload=array();
	}
	public function setHeader($header){
		if(is_array($header)||is_object($header)){
			$this->header=$header;
		}else throw new Execption("wrong output header format");
	}
	public function setPayload($payload){
		if(is_array($payload)||is_object($payload)){
			$this->payload=$payload;
		}else throw new Exception("wrong output payload format");
	}
	function sendOutput(){
		header('Content-Type: application/json');
		$output=array();
		$output['header']=$this->header;
		$output['payload']=$this->payload;
		echo json_encode($output);
	}
}
?>
