<?php
namespace App\Config;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PDO;

$dir = realpath(dirname(__DIR__, 2));
require $dir . '/App/Environment.php';

global $config;

// $logger = new Logger('Log');
// $logger->pushHandler(new StreamHandler($logDir . '/Logs/debug.log', Logger::DEBUG));

if (ENVIRONMENT == 'development') {
    $config['dbname'] = 'gps_lab';
    $config['host']   = 'localhost';
    $config['dbuser'] = 'admingps';
    $config['dbpass'] = 'tiba';
    $config['port']   = [
        'port_orbisat'         => '5005',
        'port_e_3'             => '5006',
        'port_magneti_marelli' => '5007',
        'port_e_4'             => '5008',
    ];
    $config['logDir'] = '/var/sites/gpslab/html/Logs/';
} else {
    $config['dbname'] = 'gps_tracker';
    $config['host']   = 'localhost';
    $config['dbuser'] = 'coletorgps';
    $config['dbpass'] = 't1b40111';
    $config['port']   = [
        'port_orbisat'         => '2005',
        'port_e_3'             => '2006',
        'port_magneti_marelli' => '2007',
        'port_e_4'             => '2008',
    ];
    $config['logDir'] = '/var/sites/coletorgps/html/Logs/';
}

// o Ip de conexão para socket pode ser feito setando 0 que aceita conexões de qualquer rede, interna ou externa, 127.0.0.1 só aceita conexões internas e 0.0.0.0 que aceita conexões dentro do mesmo dominio
$config['ip'] = 0;
