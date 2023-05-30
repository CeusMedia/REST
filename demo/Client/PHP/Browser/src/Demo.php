<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\ADT\Collection\Dictionary;
use CeusMedia\Common\Alg\Obj\Factory as ObjectFactory;
use CeusMedia\Common\FS\Folder\RecursiveLister as RecursiveFolderLister;
use CeusMedia\Common\Net\HTTP\Request\Receiver as Request;
use CeusMedia\Common\Net\HTTP\Response as Response;
use CeusMedia\Common\Net\HTTP\Response\Sender as ResponseSender;
use CeusMedia\Common\UI\HTML\Exception\View as ExceptionView;
use CeusMedia\Common\UI\HTML\PageFrame as HtmlPage;
use CeusMedia\Common\UI\HTML\Tag as HtmlTag;
use CeusMedia\REST\Client as Client;
use CeusMedia\Router\Router as Router;

class Demo
{
	protected Client $client;

	protected string $pathApp;

	protected Request $request;

	protected Response $response;

	protected Router $router;

	public function __construct()
	{
		$pathApp		= dirname( getenv( 'SCRIPT_NAME' ) );
		$pathServer		= dirname( $pathApp, 3 ).'/Server/';
		$hostServer		= getenv( 'SERVER_NAME' );
		$portServer		= getenv( 'SERVER_PORT' );
		$baseUri		= 'http://'.$hostServer.':'.$portServer.$pathServer;
		$this->request	= new Request();
		$this->response	= new Response();
		$this->router	= new Router();
		$this->client	= new Client( $baseUri );
		$this->client->expectFormat( 'JSON' );
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

	protected function dispatch(): string
	{
		$path	= $this->request->getPath();
		$method	= $this->request->getMethod();
		try{
			$route		= $this->router->resolve( $path );
			$parameters	= [$this->client, $this->request];
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
		$index		= RecursiveFolderLister::getFolderList( $classPath );
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
	<h1 class="project">Ceus Media REST</h1>
	<h2 class="project">PHP Browser Client Demo</h2>
	<div class="row-fluid">
		<div class="span3">
			<h3>Entities</h3>
			'.$list.'
		</div>
		<div class="span9">
			'.$result.'
		</div>
	</div>
</div>
<style>
h1.project {
	margin: 0.75em 0 -0.25em 0;
	padding: 0;
	line-height: 1.0em;
	font-weight: normal;
	font-size: 1.2em;
	color: #999;
	letter-spacing: 0.2px;
	}
h2.project {
	color: #444;
	font-weight: normal;
	border-bottom: 1px solid #ddd;
	}
</style>';

		$page	= new HtmlPage();
		$page->setBaseHref( 'http://'.getenv( 'SERVER_NAME' ).':'.getenv( 'SERVER_PORT' ).dirname( getenv( 'SCRIPT_NAME' ) ).'/' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
		$page->addStylesheet( 'https://cdn.ceusmedia.de/fonts/FontAwesome/font-awesome.min.css' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.11.1.min.js' );
//		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/jquery/1.11.1.min.map' );
		$page->addJavaScript( 'https://cdn.ceusmedia.de/js/bootstrap.min.js' );
		$page->addBody( $body );
		return $page->build();
	}
}
