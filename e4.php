<?php
// date_default_timezone_set('America/Sao_Paulo');
// ini_set('date.timezone' , 'America/Sao_Paulo');

session_start();

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Src\Controllers\E4Sockets;

require 'vendor/autoload.php';
require 'App/Config/Config.php';
require 'App/Config/E4Config.php';

$logger = new Logger('E4_Log');
$logger->pushHandler(new StreamHandler('Logs/debug_E4.log', Logger::DEBUG));

$tracker = basename(__FILE__);
$substring = explode('.', $tracker);
$tracker = ucwords($substring[0]);

$socket = new E4Sockets($logger);
$socket->runSocket($socket, $config['ip'], $config['port']['port_e_4'], $tracker);