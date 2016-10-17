<?php
class Controller_Test_Index extends Controller{

	public function handle( ADT_List_Dictionary $arguments ){
		$data	= $this->client->get( 'test' );
		$view	= new View_Test_Index( $this->client, $this->request );
		$view->add( 'items', $data['data']['items'] );
		$view->add( 'range', $data['data']['range'] );
		return $view->render();
	}
}
?>
