<?php
(@include '../../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Client as Client;

error_reporting( E_ALL );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;

$baseUri	= 'http://'.getEnv( 'SERVER_NAME' ).dirname( getEnv( 'REQUEST_URI' ) ).'/';

new Demo();

