# REST

![Branch](https://img.shields.io/badge/Branch-0.4.x-blue?style=flat-square)
![Release](https://img.shields.io/badge/Release----blue?style=flat-square)
![PHP version](https://img.shields.io/badge/PHP-%5E8.1-blue?style=flat-square&color=777BB4)
![PHPStan level](https://img.shields.io/badge/PHPStan_level-max+strict-darkgreen?style=flat-square)

HTTP RESTful Server and Client implemented in PHP.

## Examples

### Server

```
use CeusMedia\REST;
use CeusMedia\Router;

$server	= new REST\Server( [
	'forceMimeType' => 'application/json',
] );
$server->addRouterRegistrySource( new Router\Registry\Source\JsonFile( 'routes.json' ) );
$server->handleRequest();
```

### Client
```
$baseUri	= 'https://mydomain.tld/path/to/server/';

$client	= new \CeusMedia\REST\Client( $baseUri );
$client->expectFormat( 'JSON' );

try{
	$data = $client->get( 'resource/path?argument=value' );
	print_r( $data );
}
catch( Exception $e ){
	print \CeusMedia\Common\UI\HTML\Exception\Page::display( $e );
}
```