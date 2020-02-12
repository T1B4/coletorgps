<?php
namespace Src\Controllers;

use Src\Controllers\DataTranslate\E3DataTranslate;
use Src\Controllers\SendData\E3SendData;
use Src\Models\Alertas;
use Src\Models\Comandos;
use Src\Models\Trackers;

class E3Sockets
{
    protected $socket;
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function runSocket($socket, $ip, $port, $tracker)
    {
        $buffer = '';
        $socket = $this->createSocket($socket);
        $this->bindSocket($socket, $ip, $port);
        $this->getSocketData($socket, $buffer, $ip, $port, $tracker);
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

    private function getSocketData($socket, $buffer, $ip, $port, $tracker)
    {
        global $E3Config;
        global $config;
        while (1) {
            echo "\n\n###### ...Aguardando novos dados dos rastreadores... ###### \n\n";
            socket_recvfrom($socket, $buffer, 1024, 0, $ip, $port);
            if (!empty($buffer)) {
                global $E3Config;
                $data    = trim(str_replace(['*', '#'], "", $buffer));
                $rcv_str = $data;
                $data    = explode(',', $data);
                var_dump($rcv_str);
                $msgid     = $data[2];
                $trackerId = $data[1];

                $file = $config['logDir'] . $trackerId ;

                try {
                    file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                    file_put_contents($file, "\n==========================================================================================================\n", FILE_APPEND);
                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida - " . $rcv_str . "\n", FILE_APPEND);
                } catch (Exception $e) {
                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                }

                $tracker = new Trackers($this->logger);
                $device  = $tracker->localizarTracker(strtoupper($trackerId));

                // VERIFICA SE EXISTE UMA TABELA PARA O TRACKER NO BANCO DE DADOS
                if ($device !== false) {

                    try {
                        file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Tabela {$trackerId} encontrada, continuando com o processamento dos dados...\n", FILE_APPEND);
                    } catch (Exception $e) {
                        echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                    }

                    $comandos = new Comandos($this->logger);

                    try {
                        $exec = $comandos->getComands($trackerId);
                        if ($exec !== false && $exec !== null) {
                            foreach ($E3Config as $key => $value) {
                                if ($exec['cmd'] === $key) {
                                    $cmd = $value;
                                }
                            }
                        }
                    } catch (\Throwable $th) {
                        $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
                    }

                    if (!empty($exec)) {
                        $this->execCommand($exec, $cmd, $ip, $port, $trackerId, $socket);
                    }

                    if ($msgid == "TX" || $msgid == "UP" || $msgid === 'MQ') {
                        socket_sendto($socket, $buffer, strlen($buffer), 0, $ip, $port);
                    }

                    if ($msgid === 'HB' || $msgid === 'AM' || $msgid === 'DW' || $msgid === 'CC' && count($data) >= 15) {
                        $this->forwardData($data, $tracker, $ip, $port);
                        if (!empty($data[10]) && strlen($data[10]) > 7) {
                            $this->processStatus($data);
                        }
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

    private function forwardData($data, $tracker = "E3", $ip, $port)
    {
        $auth = new E3DataTranslate($this->logger);
        $auth->fullStrProcess($data, $ip, $port);
    }

    private function processStatus($data)
    {
        $mensagem = '';
        $alerts = str_split($data[10], 1);
        for ($i = 0; $i < 8; $i++) {
            $x = $alerts[$i];
            if ($x !== '0') {
                foreach ($this->statusTable() as $key => $value) {
                    if ($x === substr($key, $i, 1)) {
                        (!empty($mensagem)) ? $mensagem .= ' ' . $value : $mensagem = $value;
                        // break;
                    }
                }
            }
        }

        // CONVERTER DADOS PARA TIMESTAMP
        $tm    = $data[4] . $data[5];
        $tm    = str_split($tm, 2);
        $array = [];
        foreach ($tm as $time) {
            $array[] = str_pad(hexdec($time), 2, 0, STR_PAD_LEFT);
        }
        $tmst = strtotime('20' . implode('', $array));

        // CONVERTER DADOS PARA LATITUDE
        $signal                               = '';
        $lt                                   = $data[6];
        (substr($lt, 0, 1) === "8") ? $signal = '-' : $signal = '+';
        $lat                                  = $signal . (hexdec(substr($lt, 1))) / 600000;

        // CONVERTER DADOS PARA LONGITUDE
        $signal                               = '';
        $lt                                   = $data[7];
        (substr($lt, 0, 1) === '8') ? $signal = '-' : $signal = '+';
        $lon                                  = $signal . (hexdec(substr($lt, 1))) / 600000;

        // CONVERTER DADOS PARA VELOCIDADE
        $speed = (hexdec($data[8]) / 100);

        $mensagem = trim($mensagem);

        if (!isset($_SESSION['mensagem'][$data[1]]) || empty($_SESSION['mensagem'][$data[1]])) {
            $_SESSION['mensagem'][$data[1]] = $mensagem;
            $msg                            = new Alertas($this->logger);
            $msg->saveAlert($mensagem, $data[1], $tmst, $lat, $lon, $speed);
        }

        if ($_SESSION['mensagem'][$data[1]] !== $mensagem) {
            $_SESSION['mensagem'][$data[1]] = $mensagem;
            $msg                            = new Alertas($this->logger);
            $msg->saveAlert($mensagem, $data[1], $tmst, $lat, $lon, $speed);
        }

    }

    private function execCommand($exec, $cmd, $ip, $port, $trackerId, $socket)
    {
        $sendData = new E3SendData($this->logger);
        $sendData->sendCommands($exec, $cmd, $ip, $port, $trackerId, $socket);
    }

    private function statusTable()
    {
        $st = array(
            "08000000" => "Bateria interna com pouca voltagem ",
            "10000000" => "Mal funcionamento do GPS ",
            "40000000" => "Veiculo em movimento ",
            "00010000" => "Alarme SOS ",
            "00020000" => "Alarme de movimento ",
            "00040000" => "Energia externa cortada ",
            "00080000" => "Energia do motor cortada ",
            "00400000" => "Modo de economia de energia ativo ",
            "00800000" => "Ignição ligada ",
            // "20000000" => "Alarme de vibração ",
            // "80000000" => "Limite de velocidade excedido ",
            // "00100000" => "Porta do veiculo aberta ",
            // "01000000" => "Alarme ativo ",
        );

        return $st;
    }

}
