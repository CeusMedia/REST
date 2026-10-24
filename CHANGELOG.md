
# Changelog of CeusMedia/REST

## Unreleased (0.5.x)

- Security
  - Errors never show details (message, file, line) to clients; they are logged. Option `exposeErrors` for development.
  - Notices, warnings and deprecations are logged and no longer stop the request; other PHP errors become exceptions.
  - All throwables (also `Error`, `TypeError`) are answered, not only exceptions.
  - Errors and denied access are sent in the negotiated format (e.g. `{"error":{"status":401,"message":"Access denied"}}`) instead of HTML, without `exit`.
  - AccessCheck\Auth: no hard-coded token anymore; accepted tokens via option `tokens` (no tokens: access denied), `Bearer` scheme, constant-time comparison.
  - AccessCheck\User: passwords from `password_hash()` (plain text still works, logged as deprecated); same message for every failure; access denied if the users file is missing (was granted).
  - AccessCheck\IP: IPv6 and networks (CIDR) for whitelist and blacklist; blacklist is applied now; client IP respects trusted proxies (CeusMedia/Common `Request::setTrustedProxies()`); IP no longer echoed in the message; empty whitelist means everybody (minus blacklist).

- Features
  - Access checks as objects: `Server::addAccessCheck( AccessCheckInterface )`, results via `AccessResult::allow()` / `deny( status, message, headers )`. `registerAccessCheck()` still works (deprecated).
  - `AccessCheck\RouteAccess`: roles of the route (Router) against roles of the client from an application `AuthenticatorInterface`; default roles for routes without own roles; route meta `public` opens a route. 401 if not authenticated, 403 if role missing.
  - `AccessCheck\RateLimit`: requests per client IP and group (route meta `rateLimit` or default group) in fixed windows, counters in any PSR-16 cache; 429 with `Retry-After`.
  - `AccessCheck\FailureLimit`: failed requests (default status 401, 403) per client IP and group (route meta `failureLimit`), denied with 429 and `Retry-After` until the period after the first failure is over; success does not reset.
  - `AccessCheck\ResponseObserverInterface`: access checks can observe the status of every response of a resolved route.
  - `AccessCheck\Origin`: CSRF protection for writing requests via `Sec-Fetch-Site` and `Origin`, optional allowed origins.
  - Option `responseHeaders` for every response incl. errors; `Server::SECURE_RESPONSE_HEADERS` with recommended values.
  - Error response body configurable: `Server::setErrorFormatter( ErrorFormatterInterface )`; default `ErrorFormatter` with optional texts by status.
  - `NULL` as result is answered with `204 No Content`.
  - Option `defaultMimeType` (default `application/json`): format for `Accept: */*` and requests without Accept header.
  - Client errors keep their status (exception code 4xx) and message; other exceptions become 500.

- Fixes
  - Format by extension works again: `/users.json` resolves the route `/users` and answers in JSON (also errors), before Accept header and `forceMimeType`. Paths matching a route as they are stay untouched.
  - Without option `forceMimeType` the format is negotiated by Accept header again (was: 500 on every request). With `forceMimeType`, no Accept header is needed.
  - Accept header: wildcards (`*/*`, `type/*`), qualities incl. `q=0` and parameters are respected; own parser, since the one of CeusMedia/Common drops types of the same length and quality. Without Accept header every format is acceptable.
  - No acceptable format: `406 Not Acceptable` instead of 500, negotiated before the controller is called. Forced MIME type of a disabled format: 500.
  - Runs on SAPIs without request variables in the environment, e.g. the built-in web server (`php -S`): missing ones are taken from `$_SERVER`.

- Quality
  - Tests for access checks and error handling.
  - Require psr/simple-cache directly.
  - Demo server uses the new access checks (Origin, RateLimit, FailureLimit, RouteAccess with a demo authenticator), hashed passwords and roles; JSON files are protected by `.htaccess`.


## 0.4

- System:
  - Upgrade to CeusMedia::Common 1.0.x.
  - Upgrade to CeusMedia::Router 0.5.x.
  - Upgrade to CeusMedia::Cache 0.6.x.
  - Migrate to PHP 8.1.
  - Revamp demos.

- Features
  - Allow raw data on post.
  - Add debug logging.
  - Allow environment of Hydrogen framework as context.

- Quality
  - Update code quality and reach PHPStan level max+strict.
  - Add first unit test.

- General
  - Moved config files for tools to own folder.
  - Update composer scripts.
  - Add examples in readme.
  - Update git ignore file.




## 0.3
- Code Quality
  - Update code style.
  - Add PHPStan.
  - Improve code strictness.
  - Improve code quality.
  - Improve support for PHPUnit.

- Server
  - Refactor server context setup.
  - Support access control headers.
  - Add CORS option on server.
  - Support raw post content.
  - Add access checks.
  - Prepare logging.
  - Improve error logging.
  - Use other source class for HTTP request handling.

- Demos
  - Update demos.
  - Update server demo.
  - Updated JS client demo.

- General
  - Update copyright year.
  - Update composer and make file.
  - Require PHP atleast 7.3.
  - Allow CeusMedia:Common to be dev-master.
  - Set stability to dev.


## 0.2.x
- 0.2.1: Add server context as resource pool.
- 0.2.2: Add register for external access checkers.
- 0.2.2: Add generell error handler.
- 0.2.2: Support additional request headers in client.

## 0.2
- Document link for server controller pagination discovery support.
- Set DocCreator documents path to project root.
- Add this changelog.
- Update PHP client demos README.
- Updated HTML header of PHP Browser client demo.
- Finish PHP CLI client demo.
- Add PHP client demo for CLI.
- Protected controller members.
- Improve header and exception handling in client.
- Improve HTML output on caucht HTTP error.
- Update PHP client demo.
- Fix bug in client.
- Update PHP client demo.
- Update server demo script.
- Update client.
- Moved server demo data.
- Add client demos of PHP.
- Add client.
- Handle exceptions and fatal error.
- Rename demo to Server demo.
- Updated demo.

## 0.1
- Add make file.
- Add PHPUnit file.
- Add DocCreator file.
- Extend .gitignore by doc folders.
- Add .gitignore.
- Finish simple demo.
- Extend formats and server by extension.
- Add demo.
- Set file permissions.
- Add server.
- Create composer.json.
- Initial commit.
