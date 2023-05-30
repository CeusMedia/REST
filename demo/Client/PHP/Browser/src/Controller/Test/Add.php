<?php

use CeusMedia\Common\ADT\Collection\Dictionary;

class Controller_Test_Add extends Controller
{
	public function handle( Dictionary $arguments ): string
	{
		if( $this->request->getMethod()->isPost() ){
			$keys	= $this->request->get( 'key' );
			$values	= $this->request->get( 'value' );
			$data	= array();
			foreach( $keys as $nr => $key )
				if( strlen( trim( $key ) ) )
					$data[$key]	= (string) $values[$nr];
			$data	= $this->client->post( 'test', $data );
			$this->redirect( 'Test' );
		}
		$view	= new View_Test_Add( $this->client, $this->request );
		return $view->render();
	}
}
