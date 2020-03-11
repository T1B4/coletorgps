<?php
namespace Src\Controllers;

use Src\Controllers\crc16x25;
use Src\Controllers\DataTranslate\E4DataTranslate;
use Src\Controllers\SendData\E4SendData;
use Src\Models\Alertas;
use Src\Models\Comandos;
use Src\Models\Trackers;

class E4Sockets
{
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function runSocket($socket, $ip, $port, $tracker)
    {
        $data   = [];
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($socket, $ip, 2008);
        socket_listen($socket, 20);
        while (true) {
            $linger = array('l_linger' => 0, 'l_onoff' => 1);
            if (($conn = socket_accept($socket)) !== false) {
                socket_set_option($conn, SOL_SOCKET, SO_LINGER, $linger);

                echo "\n\n------------------   Aguardando novos dados   ------------------\n\n";

                while (true) {
                    $string = socket_read($conn, 65535, PHP_BINARY_READ);

                    if ($string == '') {
                        echo "\n\n-----------------   Final de conexão recebido   -----------------\n\n";
                        socket_close($conn);
                        break;
                    }

                    if (strlen($string) > 0) {

                        global $E4Config;
                        socket_getpeername($conn, $address, $door); //Requisita ao Peer o Ip e a Porta usada durante a conexão e armazena nas variáveis address e door

                        $hexString = bin2hex($string);
                        $trackerId = substr($hexString, 8, 14); //Extrai o id do aparelho da string recebida
                        $command   = substr($hexString, 22, 4); // Extrai o tipo de mensagem recebida atravéz do campo command conforme protocolo de comunicação.

                        if ($command == '9955') {
                            $msgSize = strlen($string);
                            $dados   = substr($string, 13, ($msgSize - 17));
                            $dados   = str_replace('|', ',', $dados);
                            $data    = explode(',', $dados);
                            var_dump(implode(',', $data));
                        }

                        if ($command == '9999') {
                            $msgSize = strlen($string);
                            $dados   = substr($string, 15, ($msgSize - 17));
                            $dados   = str_replace('|', ',', $dados);
                            $data    = explode(',', $dados);
                            var_dump(implode(',', $data));
                        }

                        $file = "/var/sites/coletorgps/html/Logs/" . $trackerId;

                        // VERIFICA SE EXISTE TRACKER COM O ID DA MENSAGEM CADASTRADO NO SISTEMA E DA SEQUÊNCIA
                        $tracker = new Trackers();
                        $device  = $tracker->localizarTracker(strtoupper($trackerId));

                        // VERIFICA SE EXISTE UMA TABELA PARA O TRACKER NO BANCO DE DADOS
                        if ($device && $device != false) {

                            try {
                                file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - Tabela {$trackerId} encontrada, continuando com o processamento dos dados...\n", FILE_APPEND);
                            } catch (Exception $e) {
                                echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                            }

                            /* Verifica se a mensagem recebida é uma solicitação de login, verifica se
                            existe aparelho cadastrado com esse id e responde conforme consulta ao banco de dados. */
                            if ($command == '5000') {

                                try {
                                    file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, "\n==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - solicitação de Login recebida, enviando ACK - " . $dados . "\n", FILE_APPEND);
                                } catch (Exception $e) {
                                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                                }

                                $crc      = new crc16x25();
                                $checksun = $crc->computeCrc("40400012" . $trackerId . "400001");
                                $msg      = "40400012" . $trackerId . "400001" . $checksun . "0d0a";
                                socket_write($conn, hex2bin($msg), strlen($msg));
                            }
                            // Final da verificação se a mensagem é de login

                            /*   VERIFICA SE É UMA MENSAGEM COM COORDENADAS, CHECA SE É DE UM TRACKER VÁLIDO
                            ENVIA OS DADOS PARA O CONTROLER TRATAR AS INFORMAÇÕES E PERSISTIR NO BD */
                            if ($command == '9955') {

                                try {
                                    file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, "\n==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida - " . $dados . "\n", FILE_APPEND);
                                } catch (Exception $e) {
                                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                                }

                                $trackerMsg = new E4DataTranslate($this->logger);
                                $trackerMsg->fullStrProcess($data, $address, $door, $tracker = 'E4', $trackerId);
                                $this->verificarComandos($address, $door, $trackerId, $conn);
                                socket_close($conn);
                                break;
                            }

                            if ($command == '9999') {

                                try {
                                    file_put_contents($file, "==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, "\n==========================================================================================================\n", FILE_APPEND);
                                    file_put_contents($file, date("d/m/Y H:i:s", strtotime('-3 hours')) . " - string recebida com alarme - " . $dados . "\n", FILE_APPEND);
                                } catch (Exception $e) {
                                    echo "\n\n Não foi possível gravar o arquivo" . $e->getMessage() . "\n\n";
                                }

                                $alarm      = substr($hexString, 26, 2);
                                $trackerMsg = new E4DataTranslate($this->logger);
                                $trackerMsg->fullStrProcess($data, $address, $door, $tracker = 'E4', $trackerId);
                                $this->processAlarm($alarm, $trackerId, strtotime($trackerMsg->getDate() . ' ' . $trackerMsg->getTime()), $trackerMsg->getLat(), $trackerMsg->getLon());
                                $this->verificarComandos($address, $door, $trackerId, $conn);
                                socket_close($conn);
                                break;
                            }

                            socket_close($conn);
                            break;

                        } else {
                            socket_close($conn);
                            break;
                        }
                    }
                }
            }
        }
        socket_close($socket);
    }

    public function verificarComandos($address, $door, $trackerId, $conn)
    {

        $comandos = new Comandos();

        try {
            $exec = $comandos->getComands($trackerId);
            if ($exec !== false && $exec !== null) {
                foreach ($E4Config as $key => $value) {
                    if ($exec['cmd'] === $key) {
                        $cmd = $value;
                    }
                }
            }
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

        if (!empty($exec)) {
            $this->execCommand($exec, $cmd, $address, $door, $trackerId, $conn);
        }

    }

    public function execCommand($exec, $cmd, $ip, $port, $trackerId, $conn)
    {
        $sendData = new E4SendData();
        $sendData->sendCommands($exec, $cmd, $ip, $port, $trackerId, $conn);
    }

    public function processAlarm($alarm, $trackerId, $tmst, $lat, $lon, $speed)
    {

        switch ($alarm) {
            case '01':
                $mensagem = "SOS ativado.";
                $msg      = new Alertas();
                $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
                break;

            case '04':
                $mensagem = "Ignição ativada.";
                $msg      = new Alertas();
                $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
                break;

            case '10':
                $mensagem = "Bateria com pouca carga.";
                $msg      = new Alertas();
                $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
                break;

            case '50':
                $mensagem = "Conexão com a bateria externa cortada.";
                $msg      = new Alertas();
                $msg->saveAlert($mensagem, $trackerId, $tmst, $lat, $lon, $speed);
                break;

            default:
                # code...
                break;
        }

    }
}
