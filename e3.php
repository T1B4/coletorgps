<?php
// date_default_timezone_set('America/Sao_Paulo');
// ini_set('date.timezone' , 'America/Sao_Paulo');

session_start();

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Src\Controllers\E3Sockets;

require 'vendor/autoload.php';
require 'App/Config/Config.php';
require 'App/Config/E3Config.php';

$logger = new Logger('E3_Log');
$logger->pushHandler(new StreamHandler('Logs/debug_E3.log', Logger::DEBUG));

$tracker   = basename(__FILE__);
$substring = explode('.', $tracker);
$tracker   = ucwords($substring[0]);

$socket = new E3Sockets($logger);
$socket->runSocket($socket, $config['ip'], $config['port']['port_e_3'], $tracker);
