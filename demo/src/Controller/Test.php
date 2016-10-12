<?php
class Controller_Test {

	public function __construct( $resources ){
		$this->resources	= $resources;
		$this->model		= new \Resource_Model_File_JSON( 'data/test.json' );
	}

	public function index(){
		return $this->model->index();
	}

	public function create(){
		$data	= $this->resources->request->getAllFromSource( 'POST' )->getAll();
		return $this->model->create( $data );
	}

	public function read( $testId ){
		$item	= $this->model->read( $testId );
		$item['views']++;
		$this->model->update( $testId, $item );
		return $item;
	}

	public function update( $testId ){
		$data	= array();
		parse_str( $this->resources->request->getRawPostData(), $data );
		foreach( $data as $key => $value )
			if( in_array( $key, array( 'id', 'createdAt', 'modifiedAt' ) ) )
				unset( $data[$key] );
		return $this->model->update( $testId, $data );
	}

	public function delete( $testId ){
		return $this->model->delete( $testId );
	}


	public function views( $testId ){
		$item	= $this->model->read( $testId );
		return $item['views'];
	}
}
