<?php

use \CeusMedia\REST\Client as Client;

class Demo{

	public function __construct(){

		$pathApp	= dirname( getEnv( 'SCRIPT_NAME' ) );
		$pathServer	= dirname( dirname( $pathApp ) ).'/Server/';
		$baseUri	= 'http://'.getEnv( 'SERVER_NAME' ).':'.getEnv( 'SERVER_PORT' ).$pathServer;
		$this->client	= new Client( $baseUri );
		$this->client->expectFormat( 'JSON' );
		$this->client->expectFormat( 'PHP' );
		$this->request	= new Net_HTTP_Request_Receiver();
		$this->response	= new Net_HTTP_Response();

		try{
			$result			= $this->dispatch();
		}
		catch( Exception $e ){
			$result	= UI_HTML_Exception_View::render( $e );
		}

		$this->response->setBody( $this->render( $result ) );
		Net_HTTP_Response_Sender::sendResponse( $this->response );
	}

	protected function dispatch(){
		$action	= $this->request->get( 'action' );
		switch( $action ){
			case 'add':
				$view	= new View_Add( $this->client, $this->request );
				$result	= $view->render();
				break;
			case 'edit':
				$view	= new View_Edit( $this->client, $this->request );
				$result	= $view->render();
				break;
			case 'index':
			case '':
				$view	= new View_Index( $this->client, $this->request );
				$result	= $view->render();
				break;
			default:
				throw new RangeException( 'Invalid action: '.$action );
		}
		return $result;
	}

	protected function render( $result ){
		$body	= '
<div class="container">
	<h1><span class="muted">Ceus Media</span> REST Client Demo</h2>
	'.$result.'
</div>';

		$page	= new UI_HTML_PageFrame();
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.10.2.min.js' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
		$page->addBody( $body );
		return $page->build();
	}
}
?>
