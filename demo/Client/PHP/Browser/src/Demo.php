<?php

use \CeusMedia\REST\Client as Client;

class Demo{

	public function __construct(){

		$pathApp	= dirname( getEnv( 'SCRIPT_NAME' ) );
		$pathServer	= dirname( dirname( dirname( $pathApp ) ) ).'/Server/';
		$baseUri	= 'http://'.getEnv( 'SERVER_NAME' ).':'.getEnv( 'SERVER_PORT' ).$pathServer;
		$this->client	= new Client( $baseUri );
		$this->client->expectFormat( 'JSON' );
		$this->client->expectFormat( 'PHP' );
		$this->request	= new Net_HTTP_Request_Receiver();
		$this->response	= new Net_HTTP_Response();

		$this->router	= new \CeusMedia\Router\Router();
		$this->router->loadRoutesFromJsonFile( 'routes.json' );


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
		$path	= $this->request->getPath();
		$method	= $this->request->getMethod();
		try{
			$route		= $this->router->resolve( $path, $method );
			$parameters	= array( $this->client, $this->request );
			$arguments	= new ADT_List_Dictionary( $route->getArguments() );
			$controller	= Alg_Object_Factory::createObject( $route->getController(), $parameters );
			$result		= $controller->handle( $arguments );
		}
		catch( Exception $e ){
			$result	= UI_HTML_Exception_View::render( $e );
		}
		return $result;
	}

	protected function render( $result ){
		$path		= $this->request->getPath();
		$method		= $this->request->getMethod();
		$route		= $this->router->resolve( $path, $method, FALSE );
		$classPath	= 'src/Controller/';

		$list		= array();
		$index		= FS_Folder_RecursiveLister::getFolderList( $classPath );
		foreach( $index as $entry ){
			$topic	= substr( $entry->getPathname(), strlen( $classPath ) );
			if( file_exists( $entry->getPathname().'/Index.php' ) ){
				$className	= 'Controller_'.str_replace( "/", "_", $topic );

				$indexRoutes	= $this->router->getRoutesByController( $className.'_Index' );
				if( $indexRoutes ){
//					print_m( $indexRoutes );die;
					$attributes	= array();
					if( $route && preg_match( "/^".$className."_/", $route->getController() ) )
						$attributes['class']	= 'active';
					$list[]	= UI_HTML_Tag::create( 'li', array(
						UI_HTML_Tag::create( 'a', str_replace( "/", " ", $topic ), array(
							'href'	=> $indexRoutes[0]->getPattern(),
						) )
					), $attributes );
				}
			}
		}
		$list	= UI_HTML_Tag::create( 'ul', $list, array( 'class' => 'nav nav-stacked nav-pills' ) );

		$body	= '
<div class="container">
	<h1><span class="muted">Ceus Media REST</span> PHP Browser Client Demo</h2>
	<div class="row-fluid">
		<div class="span3">
			<h3>Entities</h3>
			'.$list.'
		</div>
		<div class="span9">
			'.$result.'
		</div>
	</div>
</div>';

		$page	= new UI_HTML_PageFrame();
		$page->setBaseHref( 'http://'.getEnv( 'SERVER_NAME' ).':'.getEnv( 'SERVER_PORT' ).dirname( getEnv( 'SCRIPT_NAME' ) ).'/' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/font-awesome.min.css' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.11.1.min.js' );
//		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.11.1.min.map' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
		$page->addBody( $body );
		return $page->build();
	}
}
?>
