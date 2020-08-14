<?php
require_once '../../../vendor/autoload.php';

//  try to detect installation
$serverPath	= dirname( dirname( getEnv( 'REQUEST_URI' ) ) ).'/Server/';
$serverUrl	= 'http://localhost:1080/'.$serverPath;
//$serverUrl	= 'https://localhost:10443/'.$serverPath;
//$serverUrl	= 'http://127.0.0.1:1080/'.$serverPath;
//$serverUrl	= 'https://127.0.0.1:10443/'.$serverPath;

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
	var app = new App({
		serverUrl: "'.$this->serverUrl.'",
		serverUsername: "hans.testmann",
		serverPassword: "myPassword",
		verbose: true,
	});
	app.run();
	jQuery("#btnTestAdd").on("click", function(){
		var resource	= new ResourceTest(app);
		resource.add(jQuery("#input_id").val(), function(){
			resource.index();
		});
	});
});
</script>';
		$body	= '
<div class="container">
	<br/>
	<div class="hero-unit">
		<h2>REST JS Client</h2>
		<div>JavaScript Demo Client for REST Server</div>
	</div>
	<div class="row-fluid">
		<div class="span6">
			<div class="panel">
				<h3>Test Items</h3>
				<div id="result"></div>
			</div>
		</div>
		<div class="span5">
			<div class="panel">
				<h3>Add Test Item</h3>
				<div class="row-fluid">
					<div class="span12">
						<label for="input_id">ID</label>
						<input type="text" name="id" id="input_id" value="'.time().'"/>
					</div>
				</div>
				<div class="buttonbar btn-toolbar">
					<button type="button" id="btnTestAdd" class="btn btn-success">save</button>
				</div>
			</div>
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
		$this->page->addStylesheet( 'src/css/style.css' );
		$this->page->addJavaScript( self::$cdn.'js/jquery/1.10.2.min.js' );
		$this->page->addJavaScript( self::$cdn.'js/bootstrap.min.js' );
		$this->page->addJavaScript( 'src/js/App.js' );
		$this->page->addJavaScript( 'src/js/RestClient.js' );
		$this->page->addJavaScript( 'script.js' );
		return $this->page->build();
	}
}
