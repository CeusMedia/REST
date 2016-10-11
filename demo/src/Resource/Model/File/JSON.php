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

	public function index(){
		return $this->items;
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

