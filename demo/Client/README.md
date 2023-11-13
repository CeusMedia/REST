## Example

	require_once 'vendor/autoload.php';

	$baseUri	= 'https://mydomain.tld/path/to/server/';

	try{
		$client	= new \CeusMedia\REST\Client( $baseUri );
		$client->expectFormat( 'JSON' );

		$data	= $client->get( 'resource/path?argument=value' );
		print_r( $data );
	}
	catch( Exception $e ){
		print( \CeusMedia\Common\UI\HTML\\Exception\Page::display( $e ) );
	}
