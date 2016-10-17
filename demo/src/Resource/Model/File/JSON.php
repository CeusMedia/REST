<?php
class Resource_Model_File_JSON{

	protected $fileName;
	protected $items		= array();

	public function __construct( $fileName ){
		$this->fileName	= $fileName;
		if( !file_exists( $this->fileName ) )
			$this->save();
		$this->items	= json_decode( \FS_File_Reader::load( $this->fileName ), TRUE );
	}

	public function has( $id ){
		return array_key_exists( $id, $this->items );
	}

	public function count(){
		return count( $this->items );
	}

	public function create( $data = array() ){
		$id		= time( TRUE );
		$data	= array_merge( $data, array(
			'id'			=> $id,
			'views'			=> 0,
			'createdAt'		=> time(),
			'modifiedAt'	=> NULL,
		) );
		$this->items[$id]	= $data;
		$this->save();
		return $this->read( $id );
	}

	public function delete( $id ){
		$this->check( $id, TRUE, FALSE );
		unset( $this->items[$id] );
		$this->save();
	}

	public function flush(){
		$this->items	= array();
		$this->save();
	}

	public function index( $limit = NULL, $page = 1 ){
		$data	= $this->items;
		$total	= count( $this->items );
		$page	= max( $page, 1 );
		if( $limit ){
			$lastPage	= ceil( $total / $limit );
			if( $page && $page > $lastPage )
				throw new \RangeException( "Invalid page number" );
			if( $limit < $total )
				$data	= array_slice( $data, ( $page - 1 ) * $limit, $limit, TRUE );
		}
		return $data;
	}

	public function read( $id ){
		$this->check( $id, TRUE, FALSE );
		return $this->items[$id];
	}

	public function update( $id, $data = array() ){
		$this->check( $id, TRUE, FALSE );
		$original	= $this->items[$id];
		$data		= array_merge( $original, $data );
		if( $data !== $original ){
			$data['modifiedAt']		= time();
			$this->items[$id]	= $data;
			$this->save();
		}
		return $this->read( $id );
	}

	protected function check( $id, $strict = TRUE, $returnItem = TRUE ){
		if( !$this->has( $id ) ){
			if( $strict )
				throw new \OutOfRangeException( 'Invalid ID', 400 );
			return $returnItem ? NULL : FALSE;
		}
		return $returnItem ? $this->read( $id ) : TRUE;
	}

	protected function save(){
		$json	= json_encode( $this->items, JSON_PRETTY_PRINT );
		\FS_File_Writer::save( $this->fileName, $json );
	}
}
