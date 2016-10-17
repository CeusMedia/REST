<?php
class Controller_Test_Edit extends Controller{

	public function handle( ADT_List_Dictionary $arguments ){
		$id	= $arguments->get( 'id' );
		if( !$id )
			throw new RangeException( 'No valid ID given' );
		if( $this->request->getMethod() === "POST" ){
			$data	= $this->request->getAllFromSource( 'POST' )->getAll();
			$this->client->put( 'test/'.$id, $data );
			$this->redirect( 'Test/'.$id.'/edit' );
		}
		$view	= new View_Test_Edit( $this->client, $this->request );
		$view->add( 'id', $id );
		$view->add( 'item', $this->client->get( 'test/'.$id ) );
		return $view->render();
	}
}
?>
