# REST

![Branch](https://img.shields.io/badge/Branch-0.4.x-blue?style=flat-square)
![Release](https://img.shields.io/badge/Release-0.4.0-blue?style=flat-square)
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

### Access checks

```php
use CeusMedia\REST\Server;
use CeusMedia\REST\Server\AccessCheck;

$server	= new Server( [
	'forceMimeType'		=> 'application/json',
	'responseHeaders'	=> Server::SECURE_RESPONSE_HEADERS,
] );
$server->addRouterRegistrySource( new Router\Registry\Source\JsonFile( 'routes.json' ) );
$server->addAccessCheck( new AccessCheck\Origin() );
$server->addAccessCheck( new AccessCheck\RateLimit( $psr16Cache, ['auth' => [10, 60], 'api' => [600, 60]], 'api' ) );
//  max. 20 failed requests (401, 403) per IP within 15 minutes on routes with meta "failureLimit": "auth"
$server->addAccessCheck( new AccessCheck\FailureLimit( $psr16Cache, ['auth' => [20, 900]] ) );
//  $authenticator implements Server\AuthenticatorInterface: returns the roles of the client
$server->addAccessCheck( new AccessCheck\RouteAccess( $authenticator, ['user'] ) );
$server->handleRequest();
```

Routes declare roles and meta information (CeusMedia/Router):

```json
[
	{"pattern": "/login", "method": "POST", "controller": "Controller_Auth", "action": "login",
	 "meta": {"public": true, "rateLimit": "auth", "failureLimit": "auth"}},
	{"pattern": "/users", "method": "GET", "controller": "Controller_User", "action": "index",
	 "roles": ["admin"]}
]
```

Denied requests are answered in the negotiated format, e.g. `{"error":{"status":429,"message":"Too Many Requests"}}` with `Retry-After`.

Errors and denied requests use the same structure. To change it or translate the texts, set an error formatter:

```php
//  texts by status
$server->setErrorFormatter( new Server\ErrorFormatter( [401 => 'Bitte anmelden.', 500 => 'Interner Fehler.'] ) );

//  own structure: implement Server\ErrorFormatterInterface
$server->setErrorFormatter( new class implements Server\ErrorFormatterInterface {
	public function format( int $status, string $message, ?Throwable $exception = NULL ): array
	{
		return ['error' => ['code' => 'http'.$status, 'status' => $status, 'message' => $message]];
	}
} );
```

The message given to the formatter is safe to show (generic for 5xx). The exception is for inspection only, its message may contain internal details.

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