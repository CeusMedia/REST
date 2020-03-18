<?php
require_once '../../../vendor/autoload.php';

//  try to detect installation
$serverPath	= dirname( dirname( getEnv( 'REQUEST_URI' ) ) ).'/Server/';
$serverUrl	= 'http://127.0.0.1:1080/'.$serverPath;

//  OR set your own specific path
//$serverUrl	= 'http://127.0.0.1:1080/libs/REST/demo/Server/';

$app		= new JsApp( $serverUrl );

class JsApp
{
	public static $cdn	= 'https://cdn.ceusmedia.de/';

	public function __construct( $serverUrl )
	{
		$this->serverUrl	= $serverUrl;
		$this->page	= new UI_HTML_PageFrame();
		$this->main();
	}

	private function main()
	{
		$head	= '<script>
jQuery(document).ready(function(){
	App.serverUrl = "'.$this->serverUrl.'";
	App.run();
});
</script>';
		$body	= '
<div class="container">
	<h2>REST JS Client</h2>
	<div class="row-fluid">
		<div class="span12">
			Hello!
			<div id="result"></div>
		</div>
	</div>
</div>';
		$this->page->addHead( $head );
		$this->page->addBody( $body );
		print( $this->render() );
	}

	private function render()
	{
		$this->page->addStylesheet( self::$cdn.'css/bootstrap.min.css' );
		$this->page->addStylesheet( 'style.css' );
		$this->page->addJavaScript( self::$cdn.'js/jquery/1.10.2.min.js' );
		$this->page->addJavaScript( self::$cdn.'js/bootstrap.min.js' );
		$this->page->addJavaScript( 'script.js' );
		return $this->page->build();
	}
}
