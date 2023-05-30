<?php
/** @noinspection PhpMultipleClassDeclarationsInspection */
(@include '../../../../vendor/autoload.php') or die('Please use composer to install required packages.' . PHP_EOL);

use CeusMedia\Common\Loader as CommonLoader;
use CeusMedia\Common\UI\DevOutput;
use \CeusMedia\REST\Client;
use \CeusMedia\Router\Log;

error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
CommonLoader::registerNew( 'php', NULL, './src/' );
new DevOutput;
Log::$level		= Log::LEVEL_ALL;
Log::$file		= __DIR__.'/client.log';
new Demo();
