<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;

class Controller_Test_Index extends Controller
{
	public function handle( Dictionary $arguments ): string
	{
		$data	= $this->client->get( 'test' );
		$view	= new View_Test_Index( $this->client, $this->request );
		$view->add( 'items', $data->data->items );
		$view->add( 'range', $data->data->range );
		return $view->render();
	}
}
