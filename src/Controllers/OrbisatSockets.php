<?php
namespace Src\Controllers;

use Src\Controllers\crc16_Kermit;
use Src\Controllers\DataTranslate\OrbisatDataTranslate;
use Src\Controllers\SendData\OrbisatSendData;
use Src\Models\Alertas;
use Src\Models\Comandos;
use Src\Models\Trackers;

class OrbisatSockets
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
        try {
            while (1) {
                echo "\n\n---------- Aguardando novos dados dos rastreadores... ---------- \n\n";
                socket_recvfrom($socket, $data, 1024, 0, $ip, $port);

                // VERIFICA SE O COLETOR RECEBEU ALGUMA STRING DO TRACKER
                if (!empty($data)) {
                    $data = bin2hex($data);
                    var_dump($data);
                    $msgid = substr($data, 2, 2);
                    $trackerId = strtoupper(substr($data, 6, 10));
                    $cmdType = '';

                    $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

                    if ($data !== '2b2b2b') {
                        try {
                            file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                            file_put_contents($file, "\n\n==========================================================================================================\n", FILE_APPEND);
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida - " . $data . "\n", FILE_APPEND);
                        } catch (Exception $e) {
                            echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                        }
                    }

                    $tracker = new Trackers($this->logger);
                    // VERIFICA SE EXISTE UMA TABELA PARA O TRACKER NO BANCO DE DADOS E SE O TRACKER É UM TRACKER VÁLIDO
                    $device = $tracker->localizarTracker($trackerId);

                    if ($device && $device != false) {

                        // INICIA O PROCESSAMENTO DOS DADOS RECEBIDOS PELO COLETOR
                        try {
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Tabela {$trackerId} encontrada, continuando com o processamento dos dados...\n", FILE_APPEND);
                        } catch (Exception $e) {
                            echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                        }

                        $sendData = new OrbisatSendData($this->logger);
                        $comandos = new Comandos($this->logger);

                        global $orbisatConfig;
                        global $orbisatCommandsLabel;
                        global $osTrackerKey;

                        $seq = $this->translateData(substr($data, 4, 2));
                        $finsyn = substr($seq, 0, 2);
                        $rx = substr($seq, 2, 3);
                        $tx = substr($seq, 5, 3);
                        $seq = $this->unTranslateData($finsyn . $tx . $rx);

                        if ($tx === '111') {
                            $seq = base_convert('01110000', 2, 16);
                        }
                        $buffer = '06' . $seq . $trackerId . '0b00';

                        $crcGen = new crc16_Kermit();
                        $crc = $crcGen->ComputeCrc($buffer);
                        $crc = substr($crc, 2, 2) . substr($crc, 0, 2);

                        $message = '02' . $buffer . $crc . '0d';
                        $len = strlen($message);

                        // ENVIA A MENSAGEM DE ACK PARA O TRACKER
                        if ($msgid !== '06' && $msgid !== '15') {
                            socket_sendto($socket, hex2bin($message), $len, 0, $ip, $port);
                        }

                        try {
                            // CHECA SE EXISTEM COMANDOS A SEREM EXECUTADOS PARA O APARELHO
                            $exec = $comandos->getComands(strtoupper($trackerId));

                            if ($exec !== false || $exec !== null) {
                                $command = array_search($exec['cmd'], $orbisatCommandsLabel);
                                foreach ($orbisatConfig as $key => $value) {
                                    foreach ($value as $op => $cod) {
                                        if ($command === $op) {
                                            $cmdType = $key;
                                        }
                                    }
                                }
                            }
                        } catch (\Throwable $th) {
                            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
                        }

                        // CHECA SE A STRING É DE POSICIONAMENTO
                        if ($msgid === '01' || $msgid === '02' || $msgid === '03' || $msgid === '04') {

                            // ENVIA A STRING PARA PROCESSAMENTO, SEGMENTAÇÃO DE DADOS E PERSISTÊNCIA NO BANCO DE DADOS
                            $this->forwardData($data, $tracker, $ip, $port);

                            // CHECA SE A MENSAGEM É NORMAL STRING PARA PROCESSAR O STATUS
                            if ($msgid === "02") {
                                $this->processStatus($data);
                            }
                        }

                        // CHECA SE CADA ETAPA DO ENVIO DE COMANDO FOI ACEITA E FAZ O ROLLBACK CASO NÃO TENHA SIDO ACEITA PELO RASTREADOR
                        if ($msgid == '15' && !empty($exec)) {
                            echo "\n\n\n Rollback nos comandos... \n\n\n\n";
                            $comandos->setComandStatus($exec['id'], null, 0);
                            $comandos->setComandSeq($exec['id'], 1);
                        }

                        // INICIO DA SEQUÊNCIA LÓGICA DE ENVIO DE COMANDOS
                        if (!empty($exec) && $exec['cmd_seq'] === '0') {
                            ($cmdType === "configCommands") ? $cmdCode = "40" : $cmdCode = "80";
                            echo "Habilitando modo de configuração";
                            $sendData->enableConfigMode($trackerId, $ip, $port, $data, $socket, $exec, $cmdCode, $osTrackerKey, 1);
                        }

                        if (!empty($exec) && $exec['cmd_seq'] === '1' && $tx !== '110' && $msgid !== '15') {
                            echo "Executando comando...";
                            $sendData->executeComands($trackerId, $ip, $port, $socket, $exec, $orbisatConfig, $data, 1);
                        }

                        if (!empty($exec) && $exec['cmd_seq'] === '2' && $tx !== '110' && $msgid !== '15') {
                            ($cmdType === "configCommands") ? $cmdCode = "7f" : $cmdCode = "cf";
                            echo "Desabilitando modo de configuração";
                            $sendData->disableConfigMode($trackerId, $ip, $port, $data, $socket, $exec, $cmdCode, $osTrackerKey, 1);
                        }
                        // FIM DA SEQUÊNCIA LÓGICA DE ENVIO DE COMANDOS

                    } else {
                        if ($data !== '2b2b2b') {
                            file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - tabela referente ao rastreador não encontrada na base de dados, consulte o cadastro.\n", FILE_APPEND);
                        }
                    }
                }
            }
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    private function closeSocket($socket)
    {
        try {
            socket_close($socket);
            echo "\n\n Socket encerrado... \n\n";
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }
    }

    private function forwardData($data, $tracker = "Orbisat", $ip, $port)
    {
        $auth = new OrbisatDataTranslate($this->logger);
        $auth->fullStrProcess($data, $ip, $port);
    }

    private function processStatus($data)
    {
        $status_string = substr($data, 58, 2);
        $status = str_pad(base_convert($status_string, 16, 2), 8, 0, STR_PAD_LEFT);
        $status = array_reverse(str_split($status, 1));
        $mensagem = '';
        $trackerId = substr($data, 6, 10);

        if ($status[0] === "0") {
            $mensagem .= "GPS desligado ";
        }

        if ($status[1] === "0") {
            $mensagem .= "GPS não funcionando ";
        }

        if ($status[2] === "0") {
            $mensagem .= "GPRS desligado ";
        }

        if ($status[3] === "0") {
            $mensagem .= "GPRS não funcionando ";
        }

        if ($status[4] === "1") {
            $mensagem .= "JAMMING detectado ";
        }

        // if ($status[5] === "1") {
        //     $mensagem .= "Rastreador operando em modo normal ";
        // } else {
        //     $mensagem .= "Rastreador operando em modo de emergência ";
        // }

        // if ($status[6] === "0") {
        //     $mensagem .= "Rastreador trabalhando de forma incorreta ";
        // }

        // if ($status[7] === "0") {
        //     $mensagem .= "Aplicação do rastreador apresentou instabilidade ";
        // }

        $mensagem = trim($mensagem);

        $dt = new OrbisatDataTranslate($this->logger);
        $dt->setBinMap();
        $tmst = $dt->setTimestamp($data, 18, 8);
        $lat = $dt->setLat($data, 26, 8);
        $lon = $dt->setLong($data, 34, 8);
        $speed = $dt->setVel($data, 46, 4);

        $tmst = $dt->DataToDate();
        $lat = $dt->getLat();
        $lon = $dt->getLong();
        $speed = $dt->getVel();

        if (!isset($_SESSION['mensagem'][$trackerId]) || empty($_SESSION['mensagem'][$trackerId])) {
            $_SESSION['mensagem'][$trackerId] = $mensagem;
            $msg = new Alertas($this->logger);
            $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
        }

        if (strcasecmp($_SESSION['mensagem'][$trackerId], $mensagem) < 0) {
            $_SESSION['mensagem'][$trackerId] = $mensagem;
            $msg = new Alertas($this->logger);
            $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
        }

    }

    private function setBinMap()
    {
        return array('0' => '0000', '1' => '0001', '2' => '0010', '3' => '0011', '4' => '0100', '5' => '0101', '6' => '0110', '7' => '0111', '8' => '1000', '9' => '1001', 'a' => '1010', 'b' => '1011', 'c' => '1100', 'd' => '1101', 'e' => '1110', 'f' => '1111');
    }

    public function translateData($data)
    {
        $length = strlen($data);
        $result = '';
        $string = $data;
        $binmap = $this->setBinMap();

        for ($i = 0; $i < $length; $i++) {
            $key = substr($string, $i, 1);
            if (array_key_exists($key, $binmap)) {
                $result .= $binmap[$key];
            }
        }

        return $result;
    }

    public function unTranslateData($data)
    {
        $binmap = $this->setBinMap();
        $string = str_split($data, 4);
        $hexvalue = '';

        foreach ($string as $key => $value) {
            foreach ($binmap as $binkey => $binvalue) {
                if ($value === $binvalue) {
                    $hexvalue .= $binkey;
                }
            }
        }
        return $hexvalue;
    }

}
