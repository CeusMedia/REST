<?php

use CeusMedia\REST\Client as Client;
use CeusMedia\Router\Router as Router;
use Net_HTTP_Request_Receiver as Request;
use Net_HTTP_Response as Response;
use Net_HTTP_Response_Sender as ResponseSender;
use UI_HTML_PageFrame as HtmlPage;
use UI_HTML_Exception_View as ExceptionView;
use UI_HTML_Tag as HtmlTag;
use Alg_Object_Factory as ObjectFactory;
use ADT_List_Dictionary as Dictionary;

class Demo
{
	public function __construct()
	{
		$pathApp		= dirname( getEnv( 'SCRIPT_NAME' ) );
		$pathServer		= dirname( dirname( dirname( $pathApp ) ) ).'/Server/';
		$hostServer		= getEnv( 'SERVER_NAME' );
		$portServer		= getEnv( 'SERVER_PORT' );
		$baseUri		= 'http://'.$hostServer.':'.$portServer.$pathServer;
		$this->client	= new Client( $baseUri );
		$this->client->expectFormat( 'JSON' );
		$this->client->expectFormat( 'PHP' );
		$this->request	= new Request();
		$this->response	= new Response();
		$this->router	= new Router();
		$this->router->setMethod( $this->request->getMethod()->get() );
		$this->router->loadRoutesFromJsonFile( 'routes.json' );

		try{
			$result			= $this->dispatch();
		}
		catch( \Exception $e ){
			$result	= ExceptionView::render( $e );
		}

		$this->response->setBody( $this->render( $result ) );
		ResponseSender::sendResponse( $this->response );
	}

	protected function dispatch()
	{
		$path	= $this->request->getPath();
		$method	= $this->request->getMethod();
		try{
			$route		= $this->router->resolve( $path );
			$parameters	= array( $this->client, $this->request );
			$arguments	= new Dictionary( $route->getArguments() );
			$controller	= ObjectFactory::createObject( $route->getController(), $parameters );
			$result		= $controller->handle( $arguments );
		}
		catch( \Exception $e ){
			$result	= ExceptionView::render( $e );
		}
		return $result;
	}

	protected function render( $result )
	{
		$path		= $this->request->getPath();
		$method		= $this->request->getMethod();
		$route		= $this->router->resolve( $path, FALSE );
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
					$list[]	= HtmlTag::create( 'li', array(
						HtmlTag::create( 'a', str_replace( "/", " ", $topic ), array(
							'href'	=> substr( $indexRoutes[0]->getPattern(), 1 ),
						) )
					), $attributes );
				}
			}
		}
		$list	= HtmlTag::create( 'ul', $list, array( 'class' => 'nav nav-stacked nav-pills' ) );

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

		$page	= new HtmlPage();
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
