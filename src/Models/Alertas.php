<?php
namespace Src\Models;

use App\DB\Database;

class Alertas
{
    protected $db;
    protected $logger;

    public function __construct($logger)
    {
        $this->db     = Database::getInstance($logger);
        $this->logger = $logger;
    }

    public function saveAlert($alerta, $trackerId, $timestamp, $lat, $lon, $vel)
    {
        $sql = $this->db->prepare("INSERT INTO c_alertas (s_number, timestamp, lat, lon, vel, alerta) values (:trackerId, :timestamp, :lat, :lon, :vel, :alerta)");
        $sql->bindValue(':trackerId', strtoupper($trackerId));
        $sql->bindValue(':timestamp', $timestamp);
        $sql->bindValue(':lat', $lat);
        $sql->bindValue(':lon', $lon);
        $sql->bindValue(':vel', $vel);
        $sql->bindValue(':alerta', $alerta);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

}
