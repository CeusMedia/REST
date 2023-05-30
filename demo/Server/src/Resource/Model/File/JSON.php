<?php
class Resource_Model_File_JSON
{
	protected string $fileName;
	protected array $items		= [];

	public function __construct( string $fileName )
	{
		$this->fileName	= $fileName;
		if( !file_exists( $this->fileName ) )
			$this->save();
		$this->items	= json_decode( \CeusMedia\Common\FS\File\Reader::load( $this->fileName ), TRUE );
	}

	public function has( $id )
	{
		return array_key_exists( (string) $id, $this->items );
	}

	public function count()
	{
		return count( $this->items );
	}

	public function create( array $data = [] )
	{
		$id		= time();//microtime( TRUE );
		$data	= array_merge( $data, [
			'id'			=> $id,
			'views'			=> 0,
			'createdAt'		=> time(),
			'modifiedAt'	=> NULL,
		] );
		$this->items[$id]	= $data;
		$this->save();
		return $this->read( $id );
	}

	public function delete( $id )
	{
		$this->check( $id, TRUE, FALSE );
		unset( $this->items[$id] );
		$this->save();
		return TRUE;
	}

	public function flush()
	{
		$this->items	= [];
		$this->save();
	}

	public function index( $limit = NULL, $page = 1 )
	{
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

	public function read( $id )
	{
		$this->check( $id, TRUE, FALSE );
		return $this->items[$id];
	}

	public function update( $id, array $data = [] )
	{
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

	protected function check( $id, bool $strict = TRUE, bool $returnItem = TRUE )
	{
		if( !$this->has( $id ) ){
			if( $strict )
				throw new \OutOfRangeException( 'Invalid ID', 400 );
			return $returnItem ? NULL : FALSE;
		}
		return $returnItem ? $this->read( $id ) : TRUE;
	}

	protected function save()
	{
		$json	= json_encode( $this->items, JSON_PRETTY_PRINT );
		\CeusMedia\Common\FS\File\Writer::save( $this->fileName, $json );
	}
}
