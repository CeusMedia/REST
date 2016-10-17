<?php
class Controller_Index extends Controller{

	public function handle( ADT_List_Dictionary $arguments ){
		$view	= new View_Index( $this->client, $this->request );
		return $view->render();
	}
} ?>
