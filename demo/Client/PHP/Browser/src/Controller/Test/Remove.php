<?php

use CeusMedia\Common\ADT\Collection\Dictionary;

class Controller_Test_Remove extends Controller
{
	public function handle( Dictionary $arguments ): string
	{
		$id	= $arguments->get( 'id' );
		if( !$id )
			throw new RangeException( 'No valid ID given' );
		if( $this->request->getMethod()->isPost() ){
			$this->client->delete( 'test/'.$id );
			$this->redirect( 'Test' );
		}
		$view	= new View_Test_Remove( $this->client, $this->request );
		$view->add( 'id', $id );
		$view->add( 'item', $this->client->get( 'test/'.$id ) );
		return $view->render();
	}
}
