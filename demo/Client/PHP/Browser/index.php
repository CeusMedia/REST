<?php
(@include '../../../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use \CeusMedia\REST\Client;
use \CeusMedia\Router\Log;

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
\Loader::registerNew( 'php', NULL, './src/' );
new UI_DevOutput;
Log::$level		= Log::LEVEL_ALL;
Log::$file		= __DIR__.'/client.log';
new Demo();
