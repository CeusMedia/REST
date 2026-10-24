# CeusMedia/REST

![Branch](https://img.shields.io/badge/Branch-0.5.x-blue?style=flat-square)
![Release](https://img.shields.io/badge/Release-0.4.0-blue?style=flat-square)
![PHP version](https://img.shields.io/badge/PHP-%5E8.3-blue?style=flat-square&color=777BB4)
![PHPStan level](https://img.shields.io/badge/PHPStan_level-max+strict-darkgreen?style=flat-square)
![License](https://img.shields.io/badge/License-GPL--3.0+-lightgrey?style=flat-square)

**Lightweight RESTful HTTP server and client for PHP.**

Define your routes in JSON, write plain controller classes that return data, and let the server
take care of routing, content negotiation, access control and safe error responses.
The client talks to any REST API via cURL and decodes the answer for you.

---

## Contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Server](#server)
  - [Routes](#routes)
  - [Controllers](#controllers)
  - [Response formats](#response-formats)
  - [Options](#options)
  - [Access checks](#access-checks)
  - [Error handling](#error-handling)
- [Client](#client)
- [Development](#development)
- [License](#license)

---

## Features

| | |
|---|---|
| 🧭 **Routing** | Routes from JSON files or folders via [CeusMedia/Router](https://github.com/CeusMedia/Router), with path arguments, roles and meta information |
| 🔁 **Content negotiation** | Answers in JSON, HTML, PHP (serialized) or XML, chosen by the `Accept` header – or forced |
| 🔐 **Access checks** | Pluggable checks: route roles, rate limits, brute-force protection, CSRF/origin check, IP filter, tokens, Basic auth |
| 🛡️ **Safe errors** | No stack traces or internals sent to clients; errors are logged and answered in the negotiated format |
| 🧩 **Configurable error bodies** | Own error structure or translated messages via an error formatter |
| 📄 **Pagination** | `Link` headers (`FIRST`, `PREV`, `NEXT`, `LAST`) from controllers with one call |
| 🌐 **CORS & security headers** | CORS headers out of the box, recommended security headers as a constant |
| 🔌 **Framework ready** | Runs standalone or with an environment of the Hydrogen framework as controller context |
| 📡 **Client** | cURL based client with `GET`, `POST`, `PUT`, `DELETE`, Basic auth, custom headers and logging |

---

## Installation

```bash
composer require ceus-media/rest
```

**Requirements:** PHP 8.3+, extensions `curl` and `ctype`.
Dependencies: `ceus-media/common`, `ceus-media/router`, `psr/simple-cache`.

---

## Quick start

**`routes.json`**

```json
[
	{"pattern": "/users",         "method": "GET",  "controller": "Controller_User", "action": "index"},
	{"pattern": "/users/:userId", "method": "GET",  "controller": "Controller_User", "action": "read"},
	{"pattern": "/users",         "method": "POST", "controller": "Controller_User", "action": "create"}
]
```

**`Controller_User.php`**

```php
use CeusMedia\REST\Server\Controller;

class Controller_User extends Controller
{
	public function index(): array
	{
		return [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']];
	}

	public function read( string $userId ): array
	{
		if( '1' !== $userId )
			throw new OutOfRangeException( 'User not found' );		//  → 404
		return ['id' => 1, 'name' => 'Alice'];
	}

	public function create(): array
	{
		$data	= $this->resources->getRequest()->getAllFromSource( 'POST' );
		return ['id' => 3] + $data;
	}
}
```

**`index.php`**

```php
use CeusMedia\REST\Server;
use CeusMedia\Router\Registry\Source\JsonFile;

require 'vendor/autoload.php';

$server	= new Server( ['forceMimeType' => 'application/json'] );
$server->addRouterRegistrySource( new JsonFile( 'routes.json' ) );
$server->handleRequest();
```

```bash
$ curl https://api.example.org/users/1
{"id":1,"name":"Alice"}
```

---

## Server

### Routes

Routes are managed by [CeusMedia/Router](https://github.com/CeusMedia/Router). Add them as a source …

```php
$server->addRouterRegistrySource( new Router\Registry\Source\JsonFile( 'routes.json' ) );
$server->addRouterRegistrySource( new Router\Registry\Source\JsonFolder( 'routes/' ) );
```

… or by option: `['routesFile' => 'routes.json']` / `['routesFolder' => 'routes/']`.

Each route maps a `pattern` and HTTP `method` to a `controller` class and `action` method.
Path arguments like `:userId` are passed to the action as parameters.
Routes may also declare `roles` and `meta` information, used by the [access checks](#access-checks).

### Controllers

Controllers receive the server context (request, response, router, resources) in their constructor.
Extending `CeusMedia\REST\Server\Controller` gives access to it via `$this->resources`.

| Return value of an action | Response |
|---|---|
| array, object, scalar | `200 OK`, body in negotiated format |
| `NULL` | `204 No Content` |
| throws `OutOfRangeException` | `404 Not Found` with its message |
| throws exception with code `4xx` | that status with its message |
| throws anything else | `500 Internal Server Error`, generic message, details logged |

Shared objects like database connections can be registered on the server and read in controllers:

```php
$server->setResource( 'database', $pdo );
//  in a controller:
$pdo	= $this->resources->get( 'database' );
```

**Pagination** – let clients discover other pages via `Link` headers:

```php
$this->decoratePagination( $total, $limit, $page, $request->getAllFromSource( 'GET' ) );
```

### Response formats

The format is negotiated from the request's `Accept` header against the enabled formats,
respecting qualities (`;q=0.5`) and wildcards (`application/*`, `*/*`):

| Format | MIME type | Default |
|---|---|:---:|
| JSON | `application/json` | ✅ |
| HTML | `text/html` | ✅ |
| PHP  | `application/x-php` (serialized) | ✅ |
| XML  | `application/xml` | ❌ |

```php
$server	= new Server( [
	'formats'			=> ['XML' => TRUE, 'PHP' => FALSE],	//  enable / disable formats
	'defaultMimeType'	=> 'application/json',				//  for */* and requests without Accept (default)
	'forceMimeType'		=> 'application/json',				//  ignore Accept, always answer JSON
] );
```

If no enabled format is acceptable, the server answers `406 Not Acceptable` – before the controller is called.

A format can also be requested by extension: `/users.json`, `/users.html`, `/users.php` resolve the route `/users`
and answer in this format, regardless of `Accept` header and `forceMimeType`.
Paths matching a route by themselves (e.g. a route `/files/report.json`) are not affected.

Own formats implement `Server\Format\FormatInterface` and are enabled with their fully qualified class name (starting with `\`).

### Options

| Option | Default | Description |
|---|---|---|
| `forceMimeType` | `NULL` | Always answer in this MIME type |
| `defaultMimeType` | `application/json` | Format for `Accept: */*` and requests without `Accept` header |
| `formats` | HTML, JSON, PHP | Enabled response formats |
| `routesFile` / `routesFolder` | `NULL` | Load routes from JSON file / folder |
| `accessControl` | `allowOrigin: *`, … | CORS headers `Access-Control-Allow-Origin/-Methods/-Headers` |
| `responseHeaders` | `[]` | Headers for every response, e.g. `Server::SECURE_RESPONSE_HEADERS` |
| `exposeErrors` | `FALSE` | Show messages of internal errors (5xx) – **development only** |
| `classContext` | `Server\Context` | Context class handed to controllers, e.g. a Hydrogen environment |

`Server::SECURE_RESPONSE_HEADERS` contains recommended values for APIs with personal data:
`Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `X-Frame-Options: DENY`.

### Access checks

Access checks run in order of registration after the route is resolved and before the controller is called.
The first denial ends the request.

```php
use CeusMedia\REST\Server;
use CeusMedia\REST\Server\AccessCheck;
use CeusMedia\Router;

$server	= new Server( [
	'forceMimeType'		=> 'application/json',
	'responseHeaders'	=> Server::SECURE_RESPONSE_HEADERS,
] );
$server->addRouterRegistrySource( new Router\Registry\Source\JsonFile( 'routes.json' ) );

//  CSRF protection for writing requests (Sec-Fetch-Site / Origin)
$server->addAccessCheck( new AccessCheck\Origin() );

//  10 requests per minute on "auth" routes, 600 per minute on all others
$server->addAccessCheck( new AccessCheck\RateLimit( $psr16Cache, ['auth' => [10, 60], 'api' => [600, 60]], 'api' ) );

//  max. 20 failed requests (401, 403) per IP within 15 minutes on routes with meta "failureLimit": "auth"
$server->addAccessCheck( new AccessCheck\FailureLimit( $psr16Cache, ['auth' => [20, 900]] ) );

//  roles of the route against roles of the client; routes without own roles need "user"
$server->addAccessCheck( new AccessCheck\RouteAccess( $authenticator, ['user'] ) );

$server->handleRequest();
```

Routes declare roles and meta information:

```json
[
	{"pattern": "/login", "method": "POST", "controller": "Controller_Auth", "action": "login",
	 "meta": {"public": true, "rateLimit": "auth", "failureLimit": "auth"}},
	{"pattern": "/users", "method": "GET", "controller": "Controller_User", "action": "index",
	 "roles": ["admin"]}
]
```

| Check | Purpose | Denies with |
|---|---|---|
| `Origin` | Blocks cross-site writing requests (CSRF); optional list of allowed origins | `403` |
| `RateLimit` | Requests per client IP and group in fixed windows, counters in any PSR-16 cache | `429` + `Retry-After` |
| `FailureLimit` | Failed requests (default `401`, `403`) per client IP and group – slows down brute force | `429` + `Retry-After` |
| `RouteAccess` | Roles of the route against roles from your `AuthenticatorInterface`; meta `public` opens a route | `401` / `403` |

`$authenticator` is your implementation of `Server\AuthenticatorInterface`:

```php
class TokenAuthenticator implements Server\AuthenticatorInterface
{
	public function getRoles( HttpRequest $request ): array
	{
		//  return the roles of the client, an empty list if not authenticated
	}
}
```

**Own checks** implement `AccessCheck\AccessCheckInterface` and return `AccessResult::allow()` or
`AccessResult::deny( $status, $message, $headers )`. To also see the status of every response
(like `FailureLimit` does), implement `AccessCheck\ResponseObserverInterface`.

<details>
<summary>Older checks: <code>Auth</code>, <code>User</code>, <code>IP</code></summary>

These are registered by class and method (deprecated way of registration, still supported):

```php
//  Bearer token
$server->registerAccessCheck( AccessCheck\Auth::class, 'perform', ['tokens' => ['secret-token']] );

//  HTTP Basic auth against a JSON file: {"name": {"password": "<password_hash()>", "enabled": true}}
$server->registerAccessCheck( AccessCheck\User::class, 'perform', ['filePath' => 'users.json'] );

//  IPv4 / IPv6 addresses and networks (CIDR)
$server->registerAccessCheck( AccessCheck\IP::class, 'perform', ['whitelist' => ['10.0.0.0/8'], 'blacklist' => []] );
```

</details>

### Error handling

Errors and denied requests are answered in the negotiated format with the same structure:

```json
{"error": {"status": 429, "message": "Too Many Requests"}}
```

- Internal errors (5xx) show a generic message; details go to the log. Use `exposeErrors` during development.
- Notices, warnings and deprecations are logged and do not stop the request.
- Fatal errors are caught on shutdown and answered with `500`.

To change the structure or translate the texts, set an error formatter:

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

> **Note:** The message given to the formatter is safe to show (generic for 5xx).
> The exception is for inspection only – its message may contain internal details.

---

## Client

```php
use CeusMedia\REST\Client;

$client	= new Client( 'https://api.example.org/' );
$client->expectFormat( 'JSON' );
$client->setBasicAuth( 'username', 'password' );
$client->addRequestHeader( 'X-Request-Id', 'abc123' );

try{
	$users	= $client->get( 'users', ['limit' => 10] );
	$user	= $client->post( 'users', ['name' => 'Carol'] );
	$client->put( 'users/3', ['name' => 'Caroline'] );
	$client->delete( 'users/3' );
}
catch( Client\RequestException | Client\ResponseException $e ){
	echo $e->getMessage();
}
```

| Method | Description |
|---|---|
| `get( $path, $parameters )` | `GET` with query parameters |
| `post( $path, $data )` | `POST` with form data (array/object) or raw body (string) |
| `put( $path, $data )` | `PUT` with form data |
| `delete( $path )` | `DELETE` |
| `expectFormat( $format )` | `JSON`, `HTML` or `PHP` – sets `Accept` and decodes the response |
| `setBasicAuth( $user, $password )` | HTTP Basic authentication |
| `addRequestHeader( $key, $value )` | Additional request header |
| `setLogRequests( $file )` / `setLogErrors( $file )` | Log requests / errors to a file |

cURL options can be passed as second constructor argument: `new Client( $baseUri, [CURLOPT_TIMEOUT => 10] )`.

---

## Development

```bash
composer dev:all            # syntax check, PHPStan and PHPUnit
composer dev:test-syntax    # PHP linter
composer dev:phpstan        # static analysis (level max + strict rules)
composer dev:phpunit        # unit tests
composer dev:doc            # API documentation
```

Demos for server and clients (PHP CLI, PHP browser, JavaScript) can be found in [`demo/`](demo/).
To try the demo server with PHP's built-in web server:

```bash
cd demo/Server && php -S localhost:8080 index.php
curl -u hans.testmann:myPassword http://localhost:8080/test
```

Changes are listed in the [CHANGELOG](CHANGELOG.md).

---

## License

[GPL-3.0](LICENSE) © Christian Würker, [ceusmedia.de](https://ceusmedia.de/)
