<?php
namespace Src\Controllers\DataTranslate;

use Src\Models\Trackers;

class E3DataTranslate
{
    private $data;
    private $trackers;
    private $logger;
    private $trackerId;
    private $cmd;
    private $av;
    private $timestamp;
    private $lat;
    private $long;
    private $vel;
    private $status;
    private $signal;
    private $power;
    private $Odom;
    private $ip;
    private $port;

    public function __construct($logger)
    {
        $this->setLogger($logger);
        $this->setTrackers();
    }

    public function fullStrProcess($data, $ip, $port)
    {
        $this->setData($data);
        $this->setTrackers();
        $this->setCmd($this->getData(), 2);
        $this->setTrackerId($this->getData(), 1);
        $this->setAv($this->getData(), 3);
        $this->setTimestamp($this->getData(), 4);
        $this->setLat($this->getData(), 6);
        $this->setLong($this->getData(), 7);
        $this->setVel($this->getData(), 8);
        $this->setStatus($this->getData(), 10);
        $this->setSignal($this->getData(), 11);
        $this->setPower($this->getData(), 12);
        $this->setOdom($this->getData(), 14);
        $this->setIp($ip);
        $this->setPort($port);
        $this->checkTypeOffMsg();
    }

    private function setLogger($logger)
    {
        $this->logger = $logger;
    }

    private function setTrackers()
    {
        $this->trackers = new Trackers($this->logger);
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
    private function setCmd($data, $pos)
    {
        $this->cmd = $data[$pos];
    }

    // RETORNA O COMANDO ENVIADO PELO TRACKER
    public function getCmd()
    {
        return $this->cmd;
    }

    // RECEBE O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    private function setTrackerId($data, $pos)
    {
        $this->trackerId = $data[$pos];
    }

    // RETORNA O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    public function getTrackerId()
    {
        return $this->trackerId;
    }

    public function setAv($data, $pos)
    {
        $this->av = $data[$pos];
    }

    public function getAv()
    {
        return $this->av;
    }

    private function setTimestamp($data, $pos)
    {
        $tm    = $data[4] . $data[5];
        $tm    = str_split($tm, 2);
        $array = [];
        foreach ($tm as $time) {
            $array[] = str_pad(hexdec($time), 2, 0, STR_PAD_LEFT);
        }
        $tmst            = strtotime('20' . implode('', $array));
        $this->timestamp = $tmst;
    }

    // RETORNA A DATA INFORMADA PELO TRACKER NA MENSAGEM
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    // RETORNA A LATITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLat($data, $pos)
    {
        $signal                               = '';
        $lt                                   = $data[$pos];
        (substr($lt, 0, 1) === "8") ? $signal = '-' : $signal = '+';
        $this->lat                            = $signal . (hexdec(substr($lt, 1))) / 600000;
    }

    // RETORNA A LONGITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLong($data, $pos)
    {
        $signal                               = '';
        $lt                                   = $data[$pos];
        (substr($lt, 0, 1) === '8') ? $signal = '-' : $signal = '+';
        $this->long                           = $signal . (hexdec(substr($lt, 1))) / 600000;
    }

    // RECEBE A VELOCIDADE INFORMADA PELO TRACKER
    public function setVel($data, $pos)
    {
        $this->vel = (hexdec($data[$pos]) / 100);
    }

    // RETORNA OS DADOS DO ODOMETRO INFORMADO PELO TRACKER
    public function setOdom($data, $pos)
    {
        $this->Odom = (hexdec($data[$pos]) / 10);
    }

    public function setStatus($data, $pos)
    {
        $this->status = $data[$pos];
    }

    public function setSignal($data, $pos)
    {
        $this->signal = hexdec($data[$pos]);
    }

    public function setPower($data, $pos)
    {
        $this->power = $data[$pos];
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
     * RETORNA O VALOR DE vel ENVIADO PELO TRACKER
     */
    public function getVel()
    {
        return $this->vel;
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

    public function getSignal()
    {
        return $this->signal;
    }

    public function getPower()
    {
        return $this->power;
    }

    // MÉTODO DE SEGURANÇA QUE CHECA SE OS DADOS SE ORIGINAM DE UM TRACKER REGISTRADO
    public function checkId($id)
    {
        if ($this->getTrackers()->localizarTracker($id) !== false) {
            $this->checkTypeOffMsg();
        } else {
            return false;
        }
    }

    // MÉTODO PARA IDENTIFICAÇÃO E DIRECIONAMENTO DOS DADOS DE ACORDO COM O TIPO DA MENSAGEM
    private function checkTypeOffMsg()
    {
        switch ($this->getCmd()) {
            case 'MQ':
                $this->registerMq();
                break;

            case 'HB':
                $this->registerHb();
                break;

            default:
                // $this->registerHb();
                break;
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

    private function registerHb()
    {
        $trackerId = $this->getTrackerId();
        $timestamp = $this->getTimestamp();
        $lat       = $this->getLat();
        $lon       = $this->getLong();
        $vel       = $this->getVel();
        $signal    = $this->getSignal();
        $gps       = $this->getAv();
        $ip        = $this->getIp();
        $odom      = $this->getOdom();
        $horim     = 0;
        $ign       = substr(str_pad(base_convert($this->getStatus(), 16, 2), 32, 0, STR_PAD_LEFT), 8, 1);
        $pan       = substr(str_pad(base_convert($this->getStatus(), 16, 2), 32, 0, STR_PAD_LEFT), 7, 1);
        $string    = implode(',', $this->getData());

        // $this->sendData($lat, $lon, $vel, $trackerId);

        global $config;

        $file = $config['logDir'] . $trackerId;

        // try {
        if ($signal > 8 && time() > $timestamp && $gps == 'A') {
            try {
                file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $signal, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
            } catch (Exception $e) {
                echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
            }

            $this->sendData($lat, $lon, $vel, $trackerId);

            $this->getTrackers()->insertCoordinates($timestamp, strtoupper($trackerId), $lat, $lon, $vel, $signal, $ign, $pan, $ip, $odom, $horim, $string);
        }
        if ($signal < 1 || $gps != 'A') {
            try {
                file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
            } catch (Exception $e) {
                echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
            }
        }
        if (count($this->getData()) < 16) {
            try {
                file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com tamanho inferior ao correto, não registrando dados no banco de dados.\n", FILE_APPEND);
            } catch (Exception $e) {
                echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
            }
        }
        // } catch (Exception $e) {
        // echo $e->getMessage();
        // }

    }

    private function registerMq()
    {
        global $config;
        $string    = '';
        $positions = str_split($this->getPosStts(), 54);
        foreach ($positions as $value) {
            if ($string !== $value) {
                $trackerId = $this->getTrackerId();
                $timestamp = $this->getTimestamp();
                $lat       = $this->getLat();
                $lon       = $this->getLong();
                $vel       = $this->getVel();
                $nsats     = $this->getQSat();
                $gps       = $this->getAv();
                $ip        = $this->getIp();
                $odom      = $this->getOdom();
                $horim     = 0;
                $ign       = substr(str_pad(base_convert($this->getStatus(), 16, 2), 32, 0, STR_PAD_LEFT), 8, 1);
                $pan       = substr(str_pad(base_convert($this->getStatus(), 16, 2), 32, 0, STR_PAD_LEFT), 7, 1);
                $string    = $value;

                // $this->sendData($lat, $lon, $vel, $trackerId);

                $file = $config['logDir'] . $trackerId;

                // try {
                if ($nsats > 8 && time() > $timestamp && $gps == 'A') {
                    try {
                        file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    } catch (Exception $e) {
                        echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                    }
                    $this->sendData($lat, $lon, $vel, $trackerId);

                    $this->getTrackers()->insertCoordinates($timestamp, strtoupper($trackerId), $lat, $lon, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $string);

                }
                if ($nsats < 1 || $gps != 'A') {
                    try {
                        file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                    } catch (Exception $e) {
                        echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                    }
                }
                // } catch (Exception $e) {
                // echo $e->getMessage();
                // }

            }
        }
    }
}
