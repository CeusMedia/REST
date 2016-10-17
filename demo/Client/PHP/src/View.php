<?php
abstract class View{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;
		$this->data		= new ADT_List_Dictionary();
	}

	public function add( $key, $value ){
		$this->data->set( $key, $value );
	}

	protected function get( $key ){
		return $this->data->get( $key );
	}

	abstract public function render();
}
?>
