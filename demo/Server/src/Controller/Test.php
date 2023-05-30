<?php

use CeusMedia\REST\Server\Context;
use CeusMedia\REST\Server\Controller as ServerController;

class Controller_Test extends ServerController
{

	public function __construct( Context $context )
	{
		$this->resources	= $context;
		$this->model		= new Resource_Model_File_JSON( 'data/test.json' );
	}

	public function flush()
	{
		return $this->model->flush();
	}

	public function index()
	{
		$request	= $this->resources->getRequest();

		$order	= $request->get( 'order' );
		$limit	= max( (int) $request->get( 'limit' ), 0 );
		$limit	= $limit ? $limit : 10;
		$page	= max( (int) $request->get( 'page' ), 1 );
		$total	= $this->model->count();

		//  --  DISCOVERY  --  //
		$this->decoratePagination( $total, $limit, $page, $request->getAllFromSource( 'GET' ) );

		$data	= [
			'items'	=> $this->model->index( $limit, $page ),
			'range'	=> [
				'limit'	=> $limit,
				'page'	=> $page,
				'total'	=> $total,
			]
		];
		return $data;
	}

	public function create()
	{
		$data	= $this->resources->getRequest()->getAllFromSource( 'POST' );
		return $this->model->create( $data );
	}

	public function read( $testId )
	{
		$item	= $this->model->read( $testId );
		$item['views']++;
		$this->model->update( $testId, $item );
		return $item;
	}

	public function update( $testId )
	{
		$data	= [];
		parse_str( $this->resources->getRequest()->getRawPostData(), $data );
		foreach( $data as $key => $value )
			if( in_array( $key, ['id', 'createdAt', 'modifiedAt'] ) )
				unset( $data[$key] );
		return $this->model->update( $testId, $data );
	}

	public function delete( $testId )
	{
		return $this->model->delete( $testId );
	}


	public function views( $testId )
	{
		$item	= $this->model->read( $testId );
		return $item['views'];
	}
}
