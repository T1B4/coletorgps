<?php
namespace Src\Controllers;

use Src\Controllers\crc16Calculate;
use Src\Controllers\DataTranslate\Magneti_Marelli_DataTranslate;
use Src\Controllers\SendData\Magneti_Marelli_SendData;
use Src\Models\Alertas;
use Src\Models\Comandos;
use Src\Models\Trackers;

class Magneti_Marelli_Sockets
{

    protected $socket;
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function runSocket($socket, $ip, $port, $tracker)
    {
        $result = '';
        $socket = $this->createSocket($socket);
        $this->bindSocket($socket, $ip, $port);
        $this->getSocketData($socket, $result, $ip, $port, $tracker);
        $this->closeSocket($socket);
    }

    private function createSocket($socket)
    {
        try {
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            return $socket;
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    private function bindSocket($socket, $ip, $port)
    {
        try {
            (socket_bind($socket, $ip, $port));
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    private function getSocketData($socket, $result, $ip, $port, $tracker)
    {
        while (1) {
            echo "\n\n###### Aguardando novos dados dos rastreadores... ###### \n\n";
            socket_recvfrom($socket, $data, 1024, 0, $ip, $port);
            if (!empty($data)) {
                $data = bin2hex($data);
                var_dump($data);
                $msgid     = substr($data, 10, 2);
                $trackerId = substr($data, 4, 6);

                $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

                try {
                    file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                    file_put_contents($file, "\n==========================================================================================================\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida - " . $data . "\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

                $tracker = new Trackers();
                $device  = $tracker->localizarTracker(strtoupper($trackerId));

                // VERIFICA SE EXISTE UMA TABELA PARA O TRACKER NO BANCO DE DADOS
                if ($device !== false) {

                    try {
                        file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Tabela {$trackerId} encontrada, continuando com o processamento dos dados...\n", FILE_APPEND);
                    } catch (Exception $e) {
                        echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                    }

                    $sendData = new Magneti_Marelli_SendData();
                    $comandos = new Comandos();

                    global $MagnetiMarelliConfig;

                    try {
                        $exec = $comandos->getComands($trackerId);

                        if ($exec !== false && $exec !== null) {
                            foreach ($MagnetiMarelliConfig as $key => $value) {
                                if ($exec['cmd'] === $key) {
                                    $cmd = $value;
                                }
                            }
                        }
                    } catch (\Throwable $th) {
                        $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
                    }

                    if ($msgid === '00' || $msgid === '01' || $msgid === '02' || $msgid === '03' || $msgid === '04' || $msgid === '05' || $msgid === '66' || $msgid === '08') {

                        $dados = array(
                            'prodId'       => substr($data, 0, 2),
                            'firmVersion'  => substr($data, 2, 2),
                            'trackerId'    => substr($data, 4, 6),
                            'msgid'        => substr($data, 10, 2),
                            'seq'          => substr($data, 12, 2),
                            'sentDateTime' => substr($data, 14, 8),
                            'gpsDateTime'  => substr($data, 22, 8),
                            'lat'          => substr($data, 30, 8),
                            'lon'          => substr($data, 38, 8),
                            'speed'        => substr($data, 46, 2),
                            'direction'    => substr($data, 48, 2),
                            'alt'          => substr($data, 50, 4),
                            'gpsStatus'    => substr($data, 54, 2),
                            'nSats'        => substr($data, 56, 2),
                            'sensors'      => substr($data, 58, 2),
                            'actuators'    => substr($data, 60, 2),
                            'odom'         => substr($data, 62, 8),
                            'mensCrc'      => substr($data, -4, 4),
                        );

                        if ($msgid === '66') {
                            $dados['warning_code']  = substr($data, 70, 2);
                            $dados['warning_state'] = substr($data, 72, 2);
                            if ($dados['warning_state'] === '01') {
                                switch ($dados['warning_code']) {
                                    case '01':
                                        $this->processWarnings('Botão de Pânico ativado', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '02':
                                        $this->processWarnings('Bateria Violada', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '03':
                                        $this->processWarnings('Motor bloqueado', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '04':
                                        $this->processWarnings('Bateria interna com voltagem baixa', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '05':
                                        $this->processWarnings('Bateria principal com voltagem baixa', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '06':
                                        $this->processWarnings('Bateria interna vencida', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '07':
                                        $this->processWarnings('Bateria falhando', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '08':
                                        $this->processWarnings('Rastreador superaquecendo', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    case '09':
                                        $this->processWarnings('Limite de velocidade excedido', $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                                        break;

                                    default:
                                        break;
                                }
                            }
                        }

                        $seq     = $dados['seq'];
                        $buffer  = $dados['prodId'] . $dados['firmVersion'] . $dados['trackerId'] . '85' . $seq;
                        $crc16   = new crc16Calculate();
                        $crc     = $crc16->ComputeCrc($buffer);
                        $message = $buffer . $crc;
                        $len     = strlen($message);

                        socket_sendto($socket, hex2bin($message), $len, 0, $ip, $port);

                        if (!empty($exec)) {
                            $this->execCommand($exec, $cmd, $ip, $port, $dados['trackerId'], $socket, $dados['seq'], $dados['prodId'], $dados['firmVersion']);
                        }

                        $this->forwardData($dados, $tracker, $ip, $port);
                        $this->processActuators($dados['actuators'], $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);
                        $this->processStatus($dados['sensors'], $dados['trackerId'], $dados['sentDateTime'], $dados['lat'], $dados['lon'], $dados['speed']);

                    }

                    if ($msgid === '86' || $msgid === '06') {
                        echo "Erro no recebimento de mensagem ou comando pelo rastreador";
                    }
                } else {
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - tabela referente ao rastreador não encontrada na base de dados, consulte o cadastro.\n", FILE_APPEND);
                }
            }
        }
    }

    private function closeSocket($socket)
    {
        try {
            socket_close($socket);
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    private function forwardData($data, $tracker = "Magneti Marelli", $ip, $port)
    {
        $auth = new Magneti_Marelli_DataTranslate($this->logger);
        $auth->fullStrProcess($data, $ip, $port);
    }

    private function processActuators($actuators, $trackerId, $timestamp, $lat, $lon, $speed)
    {
        $mensagem = base_convert($actuators, 16, 2);
        $mensagem = str_pad($mensagem, 8, 0, STR_PAD_LEFT);
        if ($mensagem === '10000000') {
            $alerta = "Jaming Detectado";
        }
        if ($mensagem === '01000000') {
            $alerta = "Comunicação de GPS ruim";
        }
        if ($mensagem === '00000001') {
            $alerta = "Bloqueio de motor ativado";
        }
        if (isset($alerta)) {
            $send = new Alertas();
            $send->saveAlert($alerta, $trackerId, $timestamp, $lat, $lon, $speed);
        }
    }

    private function processStatus($status, $trackerId, $timestamp, $lat, $lon, $speed)
    {
        $mensagem = base_convert($status, 16, 2);
        $mensagem = str_pad($mensagem, 8, 0, STR_PAD_LEFT);
        if ($mensagem === '10000000') {
            $alerta = "Coordenada enviada da memória";
        }
        if ($mensagem === '01000000') {
            $alerta = "Ultima coordenada gps válida";
        }
        if ($mensagem === '00100000') {
            $alerta = "Modo Sleep habilitado";
        }
        if ($mensagem === '00010000') {
            $alerta = "Bateria violada";
        }
        if ($mensagem === '00000010') {
            $alerta = "Botão do pânico pressionado";
        }
        if ($mensagem === '00000001') {
            $alerta = "Ignição do veículo";
        }

        if (!isset($_SESSION['mensagem'][$trackerId]) || empty($_SESSION['mensagem'][$trackerId]) && !empty($alerta) && isset($alerta)) {
            $_SESSION['mensagem'][$trackerId] = $alerta;
            if (isset($alerta)) {
                $send = new Alertas();
                $send->saveAlert($alerta, $trackerId, $timestamp, $lat, $lon, $speed);
            }
        }

        if (!empty($_SESSION['mensagem'][$trackerId]) && !empty($alerta) && $_SESSION['mensagem'][$trackerId] !== $alerta) {
            $_SESSION['mensagem'][$trackerId] = $alerta;
            if (isset($alerta)) {
                $send = new Alertas();
                $send->saveAlert($alerta, $trackerId, $timestamp, $lat, $lon, $speed);
            }
        }
    }

    private function processWarnings($alerta, $trackerId, $timestamp, $lat, $lon, $speed)
    {
        $send = new Alertas();
        $send->saveAlert($alerta, $trackerId, $timestamp, $lat, $lon, $speed);
    }

    private function execCommand($exec, $cmd, $ip, $port, $trackerId, $socket, $seq, $prodId, $firmware)
    {
        $sendData = new Magneti_Marelli_SendData();
        $sendData->sendCommands($exec, $cmd, $ip, $port, $trackerId, $socket, $seq, $prodId, $firmware);
    }

}
