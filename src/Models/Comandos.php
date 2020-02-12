<?php
namespace Src\Models;

use App\DB\Database;
use PDO;

class Comandos
{
    protected $db;
    protected $logger;

    public function __construct($logger)
    {
        $this->db = Database::getInstance($logger);
        $this->logger = $logger;
    }

    public function getComands($trackerId)
    {
        $data;
        $sql = $this->db->prepare("SELECT * FROM c_comandos WHERE s_number = :trackerId AND status = 0 ORDER BY id ASC LIMIT 1");
        $sql->setFetchMode(PDO::FETCH_ASSOC);
        $sql->bindValue(':trackerId', $trackerId);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

        if ($sql->rowCount() >= 1) {
            $data = $sql->fetch();
            return $data;
        } else {
            return false;
        }
    }

    public function setComandStatus($id, $tempo, $status)
    {
        $sql = $this->db->prepare("UPDATE c_comandos SET status = :status, etime = :tempo WHERE id = :id");
        $sql->bindValue(':id', $id);
        $sql->bindValue(':tempo', $tempo);
        $sql->bindValue(':status', $status);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    public function setComandSeq($id, $seq)
    {
        $sql = $this->db->prepare("UPDATE c_comandos SET cmd_seq = :seq WHERE id = :id");
        $sql->bindValue(':id', $id);
        $sql->bindValue(':seq', $seq);
        try {
            $sql->execute();
        } catch (PDOException $th) {
            $this->logger->debug($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }
}
