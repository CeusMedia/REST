<?php
class Controller_Test_Remove extends Controller{

	public function handle( ADT_List_Dictionary $arguments ){
		$id	= $arguments->get( 'id' );
		if( !$id )
			throw new RangeException( 'No valid ID given' );
		if( $this->request->getMethod() === "POST" ){
			$this->client->delete( 'test/'.$id );
			$this->redirect( 'Test' );
		}
		$view	= new View_Test_Remove( $this->client, $this->request );
		$view->add( 'id', $id );
		$view->add( 'item', $this->client->get( 'test/'.$id ) );
		return $view->render();
	}
}
?>
