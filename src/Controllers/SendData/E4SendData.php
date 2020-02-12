<?php
namespace Src\Controllers\SendData;

use Src\Controllers\crc16x25;
use Src\Models\Comandos;

/* CLASSE RESPONSÁVEL PELO TRATAMENTO, CONFIGURAÇÃO E ENVIO DE COMANDOS AOS APARELHOS E4 e E4 Mini.
BASICAMENTE A ÚNICA INFORMAÇÃO NECESSÁRIA NO PROCESSO É O ID DO APARELHO PARA CONSULTAR NO BANCO
SE EXISTEM COMANDOS SETADOS PARA EXECUÇÃO DE ACORDO COM A COLUNA STATUS ONDE STATUS = 0 INDICA
COMANDO AINDA NÃO EXECUTADO */

class E4SendData
{
    public function sendCommands($exec, $cmd, $ip, $port, $trackerId, $conn)
    {

        switch ($cmd) {
            // Rastreamento sob demanda, solicita coordenadas atualizadas ao tracker
            case '4101':
                $param = '';
                break;

            // Ajustar o valor para velocidade máxima do veículo
            case '4105':
                $param = dechex($exec['parametros_cmd']);
                break;

            // Ajustar o valor do odometro
            case '4145':
                if (!empty($exec['parametros_cmd'])) {
                    $param = str_pad(dechex($exec['parametros_cmd'] * 1000), 8, 0, STR_PAD_LEFT);
                } else {
                    $param = '00000000';
                }
                break;

            // Ajustar o tempo de espera entre o envio das coordenadas
            case '4102':
                $val = floor($exec['parametros_cmd'] / 10);
                $val < 3 ? 3 : $val;
                $param = str_pad(dechex($val * 10), 4, 0, STR_PAD_LEFT);
                break;

            // Ajustar o tempo de espera para envio de coordenadas com o veiculo parado
            case '4126':
                $val = floor($exec['parametros_cmd'] / 10);
                $val < 3 ? 3 : $val;
                $param = str_pad(dechex($val * 10), 4, 0, STR_PAD_LEFT);
                break;

            // Ajustar o tempo de espera para envio de coordenadas com o veiculo desligado
            case '5119':
                $val = $exec['parametros_cmd'];
                $val < 5 ? 5 : $val;
                $param = $val;
                break;

            // Ajustar o modo de economia de energia do tracker
            case '4113':
                $param = $exec['parametros_cmd'];
                break;

            // Ajustar o timezone do horário do tracker
            case '4132':
                $param = '';
                break;

            // Reinicia o aparelho
            case '4902':
                $param = '';
                break;

            // Ajusta TAGs Rfid no tracker para consulta de autorização
            case '4170':
                $arg   = explode(';', $exec['parametros_cmd']);
                $pos   = str_pad(hexdec($arg[0]), 2, 0, STR_PAD_LEFT);
                $tag   = str_pad(hexdec($arg[1]), 6, 0, STR_PAD_LEFT);
                $param = $pos . $tag;
                break;

            default:
                # code...
                break;
        }

        $crc      = new crc16x25();
        $strSize  = strlen('40400000' . $trackerId . $cmd . $param);
        $strSize  = str_pad($strSize, 4, 0, STR_PAD_LEFT);
        $checksun = $crc->computeCrc("4040" . $strSize . $trackerId . "400001");
        $msg      = "4040" . $strSize . $trackerId . $cmd . $param . $checksun . "0d0a";
        socket_write($conn, hex2bin($msg), strlen($msg));

        $comandos = new Comandos;
        $comandos->setComandStatus($exec['id'], time(), 1);
        $comandos->setComandSeq($exec['id'], 3);

    }

}
