<?php
// date_default_timezone_set('America/Sao_Paulo');
// ini_set('date.timezone' , 'America/Sao_Paulo');

session_start();

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Src\Controllers\OrbisatSockets;

require 'vendor/autoload.php';
require 'App/Config/Config.php';
require 'App/Config/Orbisat_Config.php';

$logger = new Logger('Orbisat_Log');
$logger->pushHandler(new StreamHandler('Logs/debug_orbisat.log', Logger::DEBUG));

$tracker = basename(__FILE__);
$substring = explode('.', $tracker);
$tracker = ucwords($substring[0]);

$socket = new OrbisatSockets($logger);
$socket->runSocket($socket, $config['ip'], $config['port']['port_orbisat'], $tracker);
