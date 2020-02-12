<?php
namespace Src\Models;

use App\DB\Database;
use PDO;

class Trackers
{

    protected $db;
    protected $logger;

    public function __construct($logger)
    {
        $this->db     = Database::getInstance($logger);
        $this->logger = $logger;
    }

    public function localizarTracker($s_number)
    {
        $sql = $this->db->prepare("SELECT * FROM c_trackers WHERE s_number = :s_number AND excluido != 'S'");
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        $sql->bindValue(':s_number', $s_number);
        try {
            $sql->execute();
        } catch (\Throwable $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

        if ($sql->rowCount() > 0) {
            $data = $sql->fetch();
            return $data;
        } else {
            return false;
        }
    }

    public function insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $nsats, $ign = 1, $pan = 0, $ip, $odo, $horim = 0, $string)
    {
        $sql = $this->db->prepare("INSERT INTO t_" . strtoupper($trackerId) . " ( timestamp, lat, lon, nsats, vel, ign, pan, ip, odometro, horimetro, string) VALUES ( :timestamp, :lat, :lon, :nsats, :vel, :ign, :pan, :ip, :odo, :horim, :string)");
        $sql->bindValue(':timestamp', $timestamp);
        $sql->bindValue(':lat', $lat);
        $sql->bindValue(':lon', $lon);
        $sql->bindValue(':nsats', $nsats);
        $sql->bindValue(':vel', $vel);
        $sql->bindValue(':ign', $ign);
        $sql->bindValue(':pan', $pan);
        $sql->bindValue(':ip', $ip);
        $sql->bindValue(':odo', $odo);
        $sql->bindValue(':horim', $horim);
        $sql->bindValue(':string', $string);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

        $sql = $this->db->prepare("UPDATE c_veiculos SET `ign` = :ign, `pan` = :pan, `lat` = :lat, `lon` = :lon WHERE `s_number` = :trackerId");
        $sql->bindValue(':ign', $ign);
        $sql->bindValue(':pan', $pan);
        $sql->bindValue(':lat', $lat);
        $sql->bindValue(':lon', $lon);
        $sql->bindValue(':trackerId', $trackerId);
        $sql->execute();
    }

    public function getLastCoordinates($id)
    {
        $data;
        $sql = $this->db->prepare("SELECT * FROM t_" . $id . " ORDER BY id DESC LIMIT 1");
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
        if ($sql->rowCount() > 0) {
            $data = $sql->fetch();
            return $data;
        } else {
            return false;
        }
    }

    public function getTrackersData($id)
    {
        $data;
        $sql = $this->db->prepare("SELECT * FROM trackers WHERE s_number = :id");
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        $sql->bindValue(':id', $id);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
        if ($sql->rowCount() > 0) {
            $data = $sql->fetchAll();
            return $data;
        } else {
            return false;
        }
    }
}
