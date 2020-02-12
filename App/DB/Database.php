<?php
namespace App\DB;

use PDO;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/*
 * Constantes de parâmetros para configuração da conexão
 */
global $config;
define('HOST', $config['host']);
define('DBNAME', $config['dbname']);
define('CHARSET', 'utf8');
define('USER', $config['dbuser']);
define('PASSWORD', $config['dbpass']);

class Database
{

    /*
     * Atributo estático para instância do PDO
     */
    private static $pdo;
    private static $logger;

    /*
     * Escondendo o construtor da classe
     */
    protected function __construct($logger)
    {
        self::$logger = new Logger('Database');
        self::$logger->pushHandler(new StreamHandler('Logs/database.log', Logger::DEBUG));
    }

    /*
     * Método estático para retornar uma conexão válida
     * Verifica se já existe uma instância da conexão, caso não, configura uma nova conexão
     */
    public static function getInstance()
    {
        if (!isset(self::$pdo)) {
            try {
                $opcoes = array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
                    PDO::ATTR_PERSISTENT         => true,
                );
                self::$pdo = new PDO("mysql:host=" . HOST . "; dbname=" . DBNAME . "; charset=" . CHARSET . ";", USER, PASSWORD, $opcoes);
                self::$logger->debug("Conexão bem sucedida com o banco de dados...");
            } catch (PDOException $e) {
                print "Erro: " . $e->getMessage();
                self::$logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
            }
        }
        self::$logger->debug("Utilizando conexão já existente com o banco de dados...");
        return self::$pdo;
    }
}
