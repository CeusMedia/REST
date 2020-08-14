<?php
class Controller_Test extends \CeusMedia\REST\Server\Controller{

	public function __construct( $resources ){
		$this->resources	= $resources;
		$this->model		= new \Resource_Model_File_JSON( 'data/test.json' );
	}

	public function flush(){
		return $this->model->flush();
	}

	public function index(){
		$order	= $this->resources->request->get( 'order' );
		$limit	= max( (int) $this->resources->request->get( 'limit' ), 0 );
		$limit	= $limit ? $limit : 10;
		$page	= max( (int) $this->resources->request->get( 'page' ), 1 );
		$total	= $this->model->count();

		//  --  DISCOVERY  --  //
		$this->decoratePagination( $total, $limit, $page, $this->resources->request->getAllFromSource( 'GET' ) );

		$data	= array(
			'items'	=> $this->model->index( $limit, $page ),
			'range'	=> array(
				'limit'	=> $limit,
				'page'	=> $page,
				'total'	=> $total,
			)
		);
		return $data;
	}

	public function create(){
		$data	= $this->resources->request->getAllFromSource( 'POST' );
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
