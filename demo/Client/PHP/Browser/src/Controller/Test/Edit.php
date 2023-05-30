<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;

class Controller_Test_Edit extends Controller
{
	public function handle( Dictionary $arguments ): string
	{
		$id	= $arguments->get( 'id' );
		if( !$id )
			throw new RangeException( 'No valid ID given' );
		if( $this->request->getMethod()->isPost() ){
			$data	= $this->request->getAllFromSource( 'POST' );
			$this->client->put( 'test/'.$id, $data );
			$this->redirect( 'Test/'.$id.'/edit' );
		}
		$view	= new View_Test_Edit( $this->client, $this->request );
		$view->add( 'id', $id );
		$view->add( 'item', $this->client->get( 'test/'.$id ) );
		return $view->render();
	}
}
