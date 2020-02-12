<?php
// date_default_timezone_set('America/Sao_Paulo');
// ini_set('date.timezone' , 'America/Sao_Paulo');

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Src\Controllers\Magneti_Marelli_Sockets;

require 'vendor/autoload.php';
require 'App/Config/Config.php';
require 'App/Config/Magneti_Marelli_Config.php';

$logger = new Logger('Magneti_Log');
$logger->pushHandler(new StreamHandler('Logs/debug_magneti.log', Logger::DEBUG));

$tracker = basename(__FILE__);
$substring = explode('.', $tracker);
$tracker = ucwords($substring[0]);

$socket = new Magneti_Marelli_Sockets($logger);
$socket->runSocket($socket, $config['ip'], $config['port']['port_magneti_marelli'], $tracker);
