<?php
namespace Src\Controllers\DataTranslate;

use Src\Models\Trackers;

class E4DataTranslate
{
    public $data;
    public $trackers;
    public $logger;
    public $trackerId;
    public $ip;
    public $port;
    public $time;
    public $gpsStatus;
    public $lat;
    public $sLat;
    public $lon;
    public $sLon;
    public $vel;
    public $direction;
    public $date;
    public $hDop;
    public $alt;
    public $inputOutput;
    public $ad1_ad2;
    public $odom;
    public $rFid;

    public function __construct($logger)
    {
        $this->setLogger($logger);
    }

    public function fullStrProcess($data, $ip, $port, $tracker, $trackerId)
    {
        $this->setData($data);
        $this->setIp($ip);
        $this->setPort($port);
        $this->setTrackers();
        $this->setTrackerId($trackerId);
        $this->setTime();
        $this->setGpsStatus();
        $this->setLat();
        $this->setSLat();
        $this->setLon();
        $this->setSLon();
        $this->setVel();
        $this->setDirection();
        $this->setDate();
        $this->setHDop();
        $this->setAlt();
        $this->setInputOutput();
        $this->setAd1_ad2();
        $this->setOdom();
        $this->setRFid();
        $this->registerCoordinate();
    }

    /**
     * Get the value of data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the value of data
     *
     * @return  self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get the value of trackers
     */
    public function getTrackers()
    {
        return $this->trackers;
    }

    /**
     * Set the value of trackers
     *
     * @return  self
     */
    public function setTrackers()
    {
        $this->trackers = new Trackers;

        return $this;
    }

    /**
     * Get the value of logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Set the value of logger
     *
     * @return  self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the value of trackerId
     */
    public function getTrackerId()
    {
        return $this->trackerId;
    }

    /**
     * Set the value of trackerId
     *
     * @return  self
     */
    public function setTrackerId($trackerId)
    {
        $this->trackerId = $trackerId;

        return $this;
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
     * Get the value of time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set the value of time
     *
     * @return  self
     */
    public function setTime()
    {
        $arg        = $this->getData();
        $this->time = substr($arg[0], 0, 2) . ':' . substr($arg[0], 2, 2) . ':' . substr($arg[0], 4, 2);

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
    public function setGpsStatus()
    {
        $arg             = $this->getData();
        $this->gpsStatus = $arg[1];
        return $this;
    }

    /**
     * Get the value of lat
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set the value of lat
     *
     * @return  self
     */
    public function setLat()
    {
        $arg = $this->getData();

        $this->lat = (substr($arg[2], 0, 2)) + ((substr($arg[2], 2) / 60));

        return $this;
    }

    /**
     * Get the value of sLat
     */
    public function getSLat()
    {
        return $this->sLat;
    }

    /**
     * Set the value of sLat
     *
     * @return  self
     */
    public function setSLat()
    {
        $arg        = $this->getData();
        $this->sLat = $arg[3] == 'S' ? '-' : '';

        return $this;
    }

    /**
     * Get the value of lon
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Set the value of lon
     *
     * @return  self
     */
    public function setLon()
    {
        $arg = $this->getData();

        $this->lon = (substr($arg[4], 0, 3)) + ((substr($arg[4], 3) / 60));

        return $this;
    }

    /**
     * Get the value of sLon
     */
    public function getSLon()
    {
        return $this->sLon;
    }

    /**
     * Set the value of sLon
     *
     * @return  self
     */
    public function setSLon()
    {
        $arg = $this->getData();

        $this->sLon = $arg[5] == 'W' ? '-' : '';

        return $this;
    }

    /**
     * Get the value of vel
     */
    public function getVel()
    {
        return $this->vel;
    }

    /**
     * Set the value of vel
     *
     * @return  self
     */
    public function setVel()
    {
        $arg = $this->getData();

        $this->vel = ($arg[6] * 1.852);

        return $this;
    }

    /**
     * Get the value of direction
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set the value of direction
     *
     * @return  self
     */
    public function setDirection()
    {
        $arg = $this->getData();

        $this->direction = $arg[7];

        return $this;
    }

    /**
     * Get the value of date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the value of date
     *
     * @return  self
     */
    public function setDate()
    {
        $arg = $this->getData();

        $this->date = (substr($arg[8], 4, 2)) . '-' . (substr($arg[8], 2, 2)) . '-' . (substr($arg[8], 0, 2));

        return $this;
    }

    /**
     * Get the value of hDop
     */
    public function getHDop()
    {
        return $this->hDop;
    }

    /**
     * Set the value of hDop
     *
     * @return  self
     */
    public function setHDop()
    {
        $arg = $this->getData();

        $this->hDop = $arg[11];

        return $this;
    }

    /**
     * Get the value of alt
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * Set the value of alt
     *
     * @return  self
     */
    public function setAlt()
    {
        $arg = $this->getData();

        $this->alt = $arg[12];

        return $this;
    }

    /**
     * Get the value of inputOutput
     */
    public function getInputOutput()
    {
        return $this->inputOutput;
    }

    /**
     * Set the value of inputOutput
     *
     * @return  self
     */
    public function setInputOutput()
    {
        $arg = $this->getData();

        $this->inputOutput = $arg[13];

        return $this;
    }

    /**
     * Get the value of ad1_ad2
     */
    public function getAd1_ad2()
    {
        return $this->ad1_ad2;
    }

    /**
     * Set the value of ad1_ad2
     *
     * @return  self
     */
    public function setAd1_ad2()
    {
        $arg = $this->getData();

        $this->ad1_ad2 = $arg[14] . $arg[15];

        return $this;
    }

    /**
     * Get the value of odom
     */
    public function getOdom()
    {
        return $this->odom;
    }

    /**
     * Set the value of odom
     *
     * @return  self
     */
    public function setOdom()
    {
        $arg = $this->getData();

        $this->odom = $arg[16] / 1000;

        return $this;
    }

    /**
     * Get the value of rFid
     */
    public function getRFid()
    {
        return $this->rFid;
    }

    /**
     * Set the value of rFid
     *
     * @return  self
     */
    public function setRFid()
    {
        $arg = $this->getData();

        $this->rFid = $arg[17];

        return $this;
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

    public function registerCoordinate()
    {
        $trackerId = $this->getTrackerId();
        $timestamp = strtotime($this->getDate() . ' ' . $this->getTime());
        $lat       = $this->getSLat() . $this->getLat();
        $lon       = $this->getSLon() . $this->getLon();
        $vel       = $this->getVel();
        $signal    = $this->getGpsStatus();
        $ip        = $this->getIp();
        $odom      = $this->getOdom();
        $horim     = 0;
        $ign       = 1;
        $pan       = 0;
        $string    = implode(',', $this->getData());

        $this->sendData($lat, $lon, $vel, $trackerId);

        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

        $now = time();

        try {
            if ($signal == 'A' && $now > $timestamp) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $signal, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

                $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $signal, $ign, $pan, $ip, $odom, $horim, $string);
            }
            if ($signal == 'S') {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
            }
            if (count($this->getData()) < 17) {
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
}