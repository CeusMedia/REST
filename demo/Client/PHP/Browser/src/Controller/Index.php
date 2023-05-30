<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;

class Controller_Index extends Controller
{
	public function handle( Dictionary $arguments ): string
	{
		$view	= new View_Index( $this->client, $this->request );
		return $view->render();
	}
}
