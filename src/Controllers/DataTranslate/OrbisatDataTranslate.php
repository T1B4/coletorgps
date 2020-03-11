<?php
namespace Src\Controllers\DataTranslate;

use Src\Models\Trackers;

class OrbisatDataTranslate
{
    private $binmap;
    private $binData;
    private $data;
    private $trackers;
    private $logger;
    private $cmd;
    private $seqMensagem;
    private $ProdCod;
    private $trackerId;
    private $mensagem;
    private $timestamp;
    private $lat;
    private $long;
    private $alt;
    private $vel;
    private $KHdop;
    private $DInD;
    private $QSat;
    private $InOut;
    private $status;
    private $Odom;
    private $Horom;
    private $QtyPosStts;
    private $PosStts;
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
        $this->setBinMap();
        $this->setCmd();
        $this->setTrackerId($this->getData(), 6, 10);
        $this->setIp($ip);
        $this->setPort($port);
        $this->checkId($this->getTrackerId());
        // $this->checkTypeOffMsg();
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function setTrackers()
    {
        $this->trackers = new Trackers;
    }

    public function getTrackers()
    {
        return $this->trackers;
    }

    // RECEBE OS DADOS BRUTOS VINDOS DO TRACKER SEM QUALQUER TIPO DE CONVERSÃO OU TRATAMENTO PARA POSTERIOR UTILIZAÇÃO
    public function setData($data)
    {
        $this->data = $data;
    }

    // RETORNA OS DADOS BRUTOS VINDOS DO TRACKER SEM QUALQUER TIPO DE CONVERSÃO OU TRATAMENTO
    public function getData()
    {
        return $this->data;
    }

    // TABELA TRADUTORA DE HEXADECIMAL PARA BINÁRIO
    public function setBinMap()
    {
        $this->binmap = array('0' => '0000', '1' => '0001', '2' => '0010', '3' => '0011', '4' => '0100', '5' => '0101', '6' => '0110', '7' => '0111', '8' => '1000', '9' => '1001', 'a' => '1010', 'b' => '1011', 'c' => '1100', 'd' => '1101', 'e' => '1110', 'f' => '1111');
    }

    public function getBinMap()
    {
        return $this->binmap;
    }

    // MÉTODO QUE RECEBE OS DADOS BRUTOS E OS CONVERTE EM BINÁRIO PARA UTILIZAÇÃO
    public function translateData($data)
    {

        $length = strlen($data);
        $result = '';
        $binmap = $this->getBinMap();

        for ($i = 0; $i < $length; $i++) {
            $key = substr($data, $i, 1);
            if (array_key_exists($key, $binmap)) {
                $result .= $binmap[$key];
            }
        }
        $result = str_split($result, 8);

        return $result;
    }

    public function getBinData()
    {
        return $this->binData;
    }

    // ### MÉTODOS RESPONSÁVEIS POR QUEBRAR A STRING DE DADOS ORIGINAL EM PARTES PARA USO ### //

    // RECEBE O COMANDO ENVIADO PELO TRACKER
    public function setCmd()
    {
        $this->cmd = substr($this->getData(), 2, 2);
    }

    // RETORNA O COMANDO ENVIADO PELO TRACKER
    public function getCmd()
    {
        return $this->cmd;
    }

    // RECEBE O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    public function setTrackerId($data, $start, $count)
    {
        $this->trackerId = strtoupper(substr($data, $start, $count));
    }

    // RETORNA O NUMERO DE IDENTIFICAÇÃO DO TRACKER
    public function getTrackerId()
    {
        return $this->trackerId;
    }

    public function setTimestamp($data, $start, $count)
    {
        $this->timestamp = substr($data, $start, $count);

    }

    // RETORNA A DATA INFORMADA PELO TRACKER NA MENSAGEM
    public function getDate()
    {
        return $this->timestamp;
    }

    // RETORNA A LATITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLat($data, $start, $count)
    {
        $this->lat = round(($this->convInt($data, $start, $count)) / 3600000, 6);

    }

    // RETORNA A LONGITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setLong($data, $start, $count)
    {
        $this->long = round(($this->convInt($data, $start, $count)) / 3600000, 6);

    }

    // RECEBE A ALTITUDE INFORMADA PELO TRACKER NA MENSAGEM
    public function setAlt($data, $start, $count)
    {
        $this->alt = $this->convInt($data, $start, $count);
    }

    // RECEBE A VELOCIDADE INFORMADA PELO TRACKER
    public function setVel($data, $start, $count)
    {
        $this->vel = $this->convInt($data, $start, $count);
    }

    // RECEBE A QUANTIDADE DE SATELITES INFORMADA PELO TRACKER
    public function setQSat($data, $start, $count)
    {
        $this->QSat = hexdec(substr($data, $start, $count));
    }

    // RETORNA OS DADOS DO ODOMETRO INFORMADO PELO TRACKER
    public function setOdom($data, $start, $count)
    {
        $this->Odom = ($this->convInt($data, $start, $count)) / 10;
    }

    // RETORNA OS DADOS DO HORIMETRO INFORMADO PELO TRACKER
    public function setHorom($data, $start, $count)
    {
        $this->Horom = ($this->convInt($data, $start, $count)) / 60;
    }

    // RETORNA A QUANTIDADE DE POSIÇÕES ARMAZENADAS EM MEMÓRIA PARA MULTI STRINGS
    public function setQtyPosStts($data, $start, $count)
    {
        $this->QtyPosStts = hexdec(substr($data, $start, $count));
    }

    // RETORNA TODAS AS POSIÇÕES ARMAZENADAS EM MEMÓRIA NO TRACKER

    public function setPosStts($data, $start, $count)
    {
        $this->PosStts = substr($data, $start, $count);
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
     * RETORNA O VALOR DE KHdop ENVIADO PELO TRACKER
     */
    public function getKHdop()
    {
        return $this->KHdop;
    }

    /**
     * RETORNA O VALOR DE DInD ENVIADO PELO TRACKER
     */
    public function getDInD()
    {
        return $this->DInD;
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
     * RETORNA O VALOR DE Horom ENVIADO PELO TRACKER
     */
    public function getHorom()
    {
        return $this->Horom;
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
     * Get the value of InOut
     *
     * @return InOut
     */
    public function getInOut()
    {
        return $this->InOut;
    }

    /**
     * Set the value of InOut
     *
     * @return  self
     */
    public function setInOut($data, $start, $count)
    {
        $env         = substr($data, $start, $count);
        $env         = array_reverse(str_split($env, 2));
        $env         = implode($env);
        $this->InOut = $this->translateData($env);
    }

    // MÉTODO QUE CONVERTE EM TIMESTAMP E DATA PARTE DOS DADOS RECEBIDOS PELO TRACKER
    public function DataToDate()
    {
        $data  = $this->getDate();
        $data  = $this->translateData($data);
        $year  = 1990 + bindec(substr($data[0], 0, 6));
        $month = bindec(substr($data[1], 0, 2) . substr($data[0], 6, 2));
        $day   = (bindec(substr($data[1], 2, 5)));
        // $hour = bindec(substr($data[2], 0, 4) . substr($data[1], 7, 1)) - 3;
        $hour      = bindec(substr($data[2], 0, 4) . substr($data[1], 7, 1));
        $min       = bindec(substr($data[3], 0, 2) . substr($data[2], 4, 4));
        $seconds   = bindec(substr($data[3], 2));
        $month     = ($month < 10) ? '0' . $month : $month;
        $day       = ($day < 10) ? '0' . $day : $day;
        $hour      = ($hour < 10) ? '0' . $hour : $hour;
        $min       = ($min < 10) ? '0' . $min : $min;
        $seconds   = ($seconds < 10) ? '0' . $seconds : $seconds;
        $timestamp = strtotime($month . '/' . $day . '/' . $year . ' ' . $hour . ':' . $min . ':' . $seconds);
        $date      = $day . '/' . $month . '/' . $year . ' ' . $hour . ':' . $min . ':' . $seconds;

        return $timestamp;
    }

    // MÉTODO DE SEGURANÇA QUE CHECA SE OS DADOS SE ORIGINAM DE UM TRACKER REGISTRADO
    public function checkId($id)
    {
        if ($this->getTrackers()->localizarTracker(strtoupper($id)) !== false) {
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
    public function checkTypeOffMsg()
    {
        switch ($this->getCmd()) {
            case '00':
                $this->registerNormal_String2();
                break;

            case '01':
                $this->registerShort_String();
                break;

            case '02':
                $this->registerNormal_String2();
                break;

            case '04':
                $this->registerMulti_String2();
                break;

            case '03':
                $this->registerExtended_String2();
                break;

            default:
                break;
        }
    }

    public function registerNormal_String2()
    {
        $body = substr($this->getData(), 18, -6);

        $this->setTimestamp($body, 0, 8);
        $timestamp = $this->DataToDate();
        $this->setTrackerId($this->getData(), 6, 10);
        $this->setLat($body, 8, 8);
        $this->setLong($body, 16, 8);
        $this->setAlt($body, 24, 4);
        $this->setVel($body, 28, 4);
        $this->setQSat($body, 36, 2);
        $this->setInOut($body, 38, 2);

        if (strlen($body) > 42) {
            $this->setOdom($body, 42, 6);
        }
        if (strlen($body) > 48) {
            $this->setHorom($body, 48, 6);
        }

        // Requisita a ultima coordenada inserida no banco para efeito de comparação de distorção de dados recebidos.
        $trackerId    = $this->getTrackerId();
        $tracker_data = $this->getTrackers()->getLastCoordinates($trackerId);

        $lat   = $this->getLat();
        $lon   = $this->getLong();
        $alt   = $this->getAlt();
        $vel   = $this->getVel();
        $nsats = $this->getQSat();
        $ign   = substr(implode('', $this->getInOut()), -1);
        $pan   = substr(implode('', $this->getInOut()), -2, 1);
        $ip    = $this->getIp();
        $odom  = 0;
        $horim = 0;

        // Checa se a string recebida continha o registro de odometro para inserir no banco de dados
        if ($this->getOdom()) {
            $odomRecebido = $this->getOdom();
            $odomFinal    = round($odomRecebido) - $tracker_data['odometro'];
            $odom = $this->getOdom();
        }

        // Checa se a string recebida continha o registro de horimetro para inserir no banco de dados
        if ($this->getHorom()) {
            $horimRecebido = $this->getHorom();
            $horimFinal    = round($horimRecebido) - $tracker_data['horimetro'];
            $horim = $this->getHorom();
        }

        // Envia coordenadas, velocidade e sn do tracker para o front-end
        $this->sendData($lat, $lon, $vel, $trackerId);

        // Define o arquivo de log para salvar informações sobre os dados recebidos
        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

        $now = time();

        try {
            // Realiza uma checagem minima de satelites e distorção nos parametros odometro e horimetro para então poder gravar os dados no banco de dados
            if ($nsats >= 3 && $now > $timestamp) {
                // if ($nsats >= 1) {
                // Persiste os dados recebidos no arquivo de log do rastreador
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

                // Persiste os dados recebidos no banco de dados
                $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $this->getData());
            }
            // Checa se o numero minimo de satelites está dentro do especificado, se não, grava no arquivo de log essa informação e não grava os dados no banco de dados
            if ($nsats < 3) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites insuficiente, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function registerShort_String()
    {
        $this->setTimestamp($this->getData(), 18, 8);
        $timestamp = $this->DataToDate();
        $this->setTrackerId($this->getData(), 6, 10);

        $trackerId = strtoupper($this->getTrackerId($this->getData(), 6, 10)); // Linha utilizada em produção para captar o Tracker ID

        // Captando o ultimo registro de posição do veiculo para inserir no banco de dados a cada chamada do modo de economia, utilizado quando
        // o veículo está parado com a ignição desligada.
        $tracker_data = $this->getTrackers()->getLastCoordinates($trackerId);

        $lat   = 0;
        $lon   = 0;
        $odom  = 0;
        $horim = 0;
        $nsats = 3;

        if ($tracker_data !== false) {
            $lat   = $tracker_data['lat'];
            $lon   = $tracker_data['lon'];
            $odom  = $tracker_data['odometro'];
            $horim = $tracker_data['horimetro'];
            $nsats = $tracker_data['nsats'];
        }

        $vel = '0';
        $ign = '0';
        $pan = '0';
        $ip  = $this->getIp();

        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

        $now = time();

        try {
            if ($nsats >= 3 && $now > $timestamp) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
                $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $this->getData());
            }
            if ($nsats < 3) {

                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function registerMulti_String2()
    {
        $string = '';
        $this->setQtyPosStts($this->getData(), 18, 2);
        $this->setPosStts($this->getData(), 20, -6);
        $this->setTrackerId($this->getData(), 6, 10);
        $positions = str_split($this->getPosStts(), 54);

        foreach ($positions as $value) {
            if ($string !== $value) {
                $this->setTimestamp($value, 0, 8);
                $timestamp = $this->DataToDate();
                $this->DataToDate();
                $this->setLat($value, 8, 8);
                $this->setLong($value, 16, 8);
                $this->setAlt($value, 24, 4);
                $this->setVel($value, 28, 4);
                $this->setQSat($value, 36, 2);
                $this->setInOut($value, 38, 2);
                if (strlen($value) > 42) {
                    $this->setOdom($value, 42, 6);
                }
                if (strlen($value) > 48) {
                    $this->setHorom($value, 48, 6);
                }

                $trackerId = strtoupper($this->getTrackerId($this->getData(), 6, 10));
                $lat       = $this->getLat();
                $lon       = $this->getLong();
                $alt       = $this->getAlt();
                $vel       = $this->getVel();
                $nsats     = $this->getQSat();
                $ign       = substr(implode('', $this->getInOut()), 0, 1);
                $pan       = substr(implode('', $this->getInOut()), 1, 1);
                $ip        = $this->getIp();
                $odom      = 0;
                $horim     = 0;

                if ($this->getOdom()) {
                    $odom = $this->getOdom();
                }
                if ($this->getHorom()) {
                    $horim = $this->getHorom();
                }

                $this->sendData($lat, $lon, $vel, $trackerId);

                $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

                $now = time();

                try {
                    if ($nsats >= 3 && $now > $timestamp) {
                        try {
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                        } catch (Exception $e) {
                            echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                        }
                        $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $this->getData());
                        $string = $value;
                    }

                    if ($nsats < 3) {

                        try {
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                        } catch (Exception $e) {
                            echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                        }

                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
            $string = '';
        }

    }

    public function registerExtended_String2()
    {
        $this->setTimestamp($this->getData(), 18, 8);
        $timestamp = $this->DataToDate();
        $this->setTrackerId($this->getData(), 6, 10);
        $this->setLat($this->getData(), 26, 8);
        $this->setLong($this->getData(), 34, 8);
        $this->setAlt($this->getData(), 42, 4);
        $this->setVel($this->getData(), 46, 4);
        $this->setQSat($this->getData(), 54, 2);
        $this->setInOut($this->getData(), 56, 2);
        if (strlen($this->getData()) > 60) {
            $this->setOdom($this->getData(), 60, 6);
        }
        if (strlen($this->getData()) > 66) {
            $this->setHorom($this->getData(), 66, 6);
        }
        // $this->setOdom($this->getData(), 60, 6);
        // $this->setHorom($this->getData(), 66, 6);

        $trackerId = strtoupper($this->getTrackerId($this->getData(), 6, 10));
        $lat       = $this->getLat();
        $lon       = $this->getLong();
        $alt       = $this->getAlt();
        $vel       = $this->getVel();
        $nsats     = $this->getQSat();
        $ign       = substr(implode('', $this->getInOut()), -1);
        $pan       = substr(implode('', $this->getInOut()), -2, 1);
        $ip        = $this->getIp();
        $odom      = 0;
        $horim     = 0;
        if ($this->getOdom()) {
            $odom = $this->getOdom();
        }
        if ($this->getHorom()) {
            $horim = $this->getHorom();
        }
        // $odom      = $this->getOdom();
        // $horim     = $this->getHorom();

        $this->sendData($lat, $lon, $vel, $trackerId);

        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

        $now = time();

        try {
            if ($nsats >= 3 && $now > $timestamp) {
                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Coordenadas válidas recebidas, Latitude: $lat, Longitude: $lon, Velocidade: $vel, NSats: $nsats, Ignição: $ign, Pânico: $pan, Odometro: $odom e Horimetro: $horim.\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Dados armazenados no banco de dados com sucesso.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }
                $this->getTrackers()->insertCoordinates($timestamp, $trackerId, $lat, $lon, $alt, $vel, $nsats, $ign, $pan, $ip, $odom, $horim, $this->getData());
            }
            if ($nsats < 3) {

                try {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com sinal de satélites = 0, não registrando dados no banco de dados.\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

            }
            // if (strlen($this->getData()) < 104) {

            //     try {
            //         file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida mas com tamanho inferior ao correto, não registrando dados no banco de dados.\n", FILE_APPEND);
            //     } catch (Exception $e) {
            //         echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
            //     }

            // }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function convInt($data, $start, $end)
    {
        $env = substr($data, $start, $end);
        $env = array_reverse(str_split($env, 2));
        $env = implode($env);
        $env = unpack('l', pack('l', hexdec($env)))[1];
        return $env;
    }
}
