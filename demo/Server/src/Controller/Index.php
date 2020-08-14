<?php
class Controller_Index{

	public function __construct( $resources ){
		$this->resources	= $resources;
	}

	public function index(){
		return 'HTTP REST Server Index | Try path: test';
	}
}?>
