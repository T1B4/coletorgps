<?php
namespace Src\Controllers\DataTranslate;

use Src\Models\Trackers;

class Magneti_Marelli_DataTranslate
{
    private $data;
    private $trackers;
    private $logger;
    private $cmd;
    private $trackerId;
    private $timestamp;
    private $lat;
    private $long;
    private $alt;
    private $vel;
    private $QSat;
    private $gpsStatus;
    private $Odom;
    private $Ign;
    private $Pan;
    private $Horom;
    private $ip;
    private $port;

    public function __construct($logger)
    {
        $this->setLogger($logger);
    }

    public function fullStrProcess($data, $ip, $port)
    {
        $this->setData($data);
        $this->setTrackers();
        $this->setCmd($data);
        $this->setTrackerId($data);
        $this->setIp($ip);
        $this->setPort($port);
        $this->register_coordinates();
    }

    private function setLogger($logger)
    {
        $this->logger = $logger;
    }

    private function setTrackers()
    {
        $this->trackers = new Trackers;
    }

    public function getTrackers()
    {
        return $this->trackers;
    }

    // RECEBE OS DADOS BRUTOS VINDOS DO TRACKER SEM QUALQUER TIPO DE CONVERSÃO OU TRATAMENTO PARA POSTERIOR UTILIZAÇÃO
    private function setData($data)
    {
        $this->data = $data;
    }

    // RETORNA OS DADOS BRUTOS VINDOS DO TRACKER SEM QUALQUER TIPO DE CONVERSÃO OU TRATAMENTO
    public function getData()
    {
        return $this->data;
    }

    // ### MÉTODOS RESPONSÁVEIS POR QUEBRAR A STRING DE DADOS ORIGINAL EM PARTES PARA USO ### //

    // RECEBE O COMANDO ENVIADO PELO TRACKER
    private function setCmd($data)
    {
        $this->cmd = hexdec($data['msgid']);
    }

    // RETORNA O COMANDO ENVIADO PELO TRACKER
    public function getCmd()
    {
        return $this->cmd;
    }

    // RECEBE O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    private function setTrackerId($data)
    {
        $this->trackerId = strtoupper($data['trackerId']);
    }

    // RETORNA O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    public function getTrackerId()
    {
        return $this->trackerId;
    }

    private function setTimestamp($data)
    {
        $this->timestamp = hexdec($data['sentDateTime']);
    }

    // RETORNA A DATA INFORMADA PELO TRACKER NA MENSAGEM
    public function getDate()
    {
        return $this->timestamp;
    }

    // RETORNA A LATITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLat($data)
    {
        $this->lat = $this->convInt($data['lat']) / 360000;
    }

    // RETORNA A LONGITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLong($data)
    {
        $this->long = $this->convInt($data['lon']) / 360000;
    }

    // RECEBE A ALTITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setAlt($data)
    {
        $this->alt = hexdec($data['alt']);
    }

    // RECEBE A VELOCIDADE INFORMADA PELO TRACKER
    public function setVel($data)
    {
        $this->vel = hexdec($data['speed']);
    }

    // RECEBE A QUANTIDADE DE SATELITES INFORMADA PELO TRACKER
    public function setQSat($data)
    {
        $this->QSat = hexdec($data['nSats']);
    }

    // RETORNA OS DADOS DO ODOMETRO INFORMADO PELO TRACKER
    public function setOdom($data)
    {
        $this->Odom = hexdec($data['odom']);
    }

    // RETORNA OS DADOS DA IGNIÇÃO INFORMADO PELO TRACKER
    public function setIgn($data)
    {
        $this->Ign = hexdec(substr($data['sensors'], 7, 1));
    }

    // RETORNA OS DADOS DA IGNIÇÃO INFORMADO PELO TRACKER
    public function setPan($data)
    {
        $this->Pan = hexdec(substr($data['sensors'], 6, 1));
    }

    // RETORNA OS DADOS DO HORIMETRO INFORMADO PELO TRACKER
    public function setHorom($data)
    {
        $this->Horom = ($this->convInt($data)) / 60;
    }

    /**
     * RETORNA O VALOR DE timestamp ENVIADO PELO TRACKER
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * RETORNA O VALOR DE lat ENVIADO PELO TRACKER
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * RETORNA O VALOR DE long ENVIADO PELO TRACKER
     */
    public function getLong()
    {
        return $this->long;
    }

    /**
     * RETORNA O VALOR DE alt ENVIADO PELO TRACKER
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * RETORNA O VALOR DE vel ENVIADO PELO TRACKER
     */
    public function getVel()
    {
        return $this->vel;
    }

    /**
     * RETORNA O VALOR DE QSat ENVIADO PELO TRACKER
     */
    public function getQSat()
    {
        return $this->QSat;
    }

    /**
     * RETORNA O VALOR DE status ENVIADO PELO TRACKER
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * RETORNA O VALOR DE Odom ENVIADO PELO TRACKER
     */
    public function getOdom()
    {
        return $this->Odom;
    }

    /**
     * RETORNA O VALOR DE Odom ENVIADO PELO TRACKER
     */
    public function getPan()
    {
        return $this->Pan;
    }

    /**
     * RETORNA O VALOR DE Odom ENVIADO PELO TRACKER
     */
    public function getIgn()
    {
        return $this->Ign;
    }

    // RETORNA O NUMEROS DE POSIÇÕES ARMAZENADAS EM UMA MULTI STRING
    public function getQtyPosStts()
    {
        return $this->QtyPosStts;
    }

    // RETORNA TODAS AS POSIÇÕES DE CORDENADAS ARMAZENADAS NA MULTI STRING
    public function getPosStts()
    {
        return $this->PosStts;
    }

    // RETORNA O CRC PARA CHECAGEM DE AUTENTICIDADE DA MENSAGEM
    public function getCRC()
    {
        $crc = $this->binData[count($this->binData) - 2] . $this->binData[count($this->binData) - 1];
        return $crc;
    }

    /**
     * Get the value of ip
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set the value of ip
     *
     * @return  self
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get the value of port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the value of port
     *
     * @return  self
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get the value of gpsStatus
     */
    public function getGpsStatus()
    {
        return $this->gpsStatus;
    }

    /**
     * Set the value of gpsStatus
     *
     * @return  self
     */
    public function setGpsStatus($data)
    {
        $this->gpsStatus = $data['gpsStatus'];
    }

    // MÉTODO DE SEGURANÇA QUE CHECA SE OS DADOS SE ORIGINAM DE UM TRACKER REGISTRADO
    public function checkId($id)
    {
        if ($this->getTrackers()->localizarTracker($id) !== false) {
            $this->checkTypeOffMsg();
        }
    }

    public function sendData($lat, $lon, $vel, $trackerId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => 'http://idtracker.com.br/eventos_gatilho.php',
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => [
                'latitude'   => $lat,
                'longitude'  => $lon,
                'velocidade' => $vel,
                's_number'   => $trackerId,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

    }

    // MÉTODO PARA IDENTIFICAÇÃO E DIRECIONAMENTO DOS DADOS DE ACORDO COM O TIPO DA MENSAGEM
    private function checkTypeOffMsg()
    {
        switch ($this->getCmd()) {
            case '00':
                $this->register_coordinates();
                break;

            case '02':
                $this->register_coordinates();
                break;

            case '03':
                $this->register_coordinates();
                break;

            case '04':
                $this->register_coordinates();
                break;

            case '07':
                $this->register_coordinates();
                break;

            default:
                break;
        }
    }

    private function register_coordinates()
    {
        $this->setTimestamp($this->getData());
        $this->setTrackerId($this->getData());
        $this->setLat($this->getData());
        $this->setLong($this->getData());
        $this->setAlt($this->getData());
        $this->setVel($this->getData());
        $this->setQSat($this->getData());
        $this->setOdom($this->getData());
        $this->setIgn($this->getData());
        $this->setPan($this->getData());
        $this->setGpsStatus($this->getData());

        $timestamp  = $this->getDate();
        $trackerId  = $this->getTrackerId();
        $lat        = $this->getLat();
        $lon        = $this->getLong();
        $alt        = $this->getAlt();
        $vel        = $this->getVel();
        $nsats      = $this->getQSat();
        $gps_status = $this->getGpsStatus();
        $ip         = $this->getIp();
        $odom       = $this->getOdom();
        $ign        = $this->getIgn();
        $pan        = $this->getPan();
        $horim      = 0;
        $string     = implode(',', $this->getData());

        $this->sendData($lat, $lon, $vel, $trackerId);

        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

        $now = time();

        try {
            if ($nsats > 0 && $now > $timestamp) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
                $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $string);
            }
            if ($nsats < 1) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
            }
            if (strlen(implode(',', $this->getData())) < 74) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com tamanho inferior ao correto, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    private function convInt($data)
    {
        $env = $data;
        $env = unpack('l', pack('l', hexdec($env)))[1];
        return $env;
    }

}
