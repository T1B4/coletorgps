<?php
namespace Src\Controllers\SendData;

use Src\Controllers\crc16Calculate;
use Src\Models\Comandos;

// CLASSE RESPONSÁVEL PELO TRATAMENTO, CONFIGURAÇÃO E ENVIO DE COMANDOS AOS APARELHOS MAGNETI MARELLI.
// BASICAMENTE A ÚNICA INFORMAÇÃO NECESSÁRIA NO PROCESSO É O ID DO APARELHO PARA CONSULTAR NO BANCO
// SE EXISTEM COMANDOS SETADOS PARA EXECUÇÃO DE ACORDO COM A COLUNA STATUS ONDE STATUS = 0 INDICA
// COMANDO AINDA NÃO EXECUTADO

class Magneti_Marelli_SendData
{
    public function sendCommands($exec, $cmd, $ip, $port, $trackerId, $socket, $seq, $prodId, $firmware)
    {
        // LIGAR E DESLIGAR MENSAGENS DE ALERTA ENVIADOS PELO RASTREADOR
        if ($cmd === '77') {
            $param = str_pad(dechex($exec['parametros_cmd']), 2, 0, STR_PAD_LEFT);
        }

        // CONFIGURAR MODO SLEEP, HABILITA O RASTREADOR A ENTRAR NO MODO STAND BY OU NÃO APÓS UM PERIODO COM A IGNIÇÃO DESLIGADA
        if ($cmd === '76') {
            $param = str_pad(dechex($exec['parametros_cmd']), 2, 0, STR_PAD_LEFT);
        }

        // CONFIGURAR TEMPO DE ENVIO DE MENSAGEM DE CONEXÃO, CONFIGURADO EM MINUTOS SERVER PARA MANTER A CONEXÃO ENTRE O RASTREADOR E O COLETOR
        if ($cmd === 78) {
            $param = str_pad(dechex($exec['parametros_cmd']), 2, 0, STR_PAD_LEFT);
        }

        // CONFIGURAR APN DE ACESSO A REDE GPRS
        if ($cmd === "80") {
            $param = explode(';', $exec['parametros_cmd']);
            foreach ($param as $value) {
                $size = strlen($value);
                (($size % 2) !== 0) ? $size += 1 : $size;
                $var[] = str_pad(dechex($value), 2, 0, STR_PAD_LEFT);
                $var[] = str_pad(dechex($value), $size, 0, STR_PAD_LEFT);
            }
            $param = implode('', $var);
        }

        // CONFIGURAR IP E PORTA DE ACESSO DO RASTREADOR AO COLETOR
        if ($cmd === "81") {
            $param = explode(';', $exec['parametros_cmd']);
            foreach ($param as $value) {
                if (strlen($value) === 8) {
                    $var[] = str_pad(dechex($value), 8, 0, STR_PAD_LEFT);
                } else {
                    $var[] = str_pad(dechex($value), 4, 0, STR_PAD_LEFT);
                }
            }
            $param = implode('', $var);
        }

        // SETAR TEMPOS DE INTERVALOS DE COMUNICAÇÃO DO RASTREADOR COM O COLETOR, NESSE AJUSTE DEVE SER ENVIADO VALORES PARA
        // MENSAGENS DE RASTREAMENTO COM A IGNIÇÃO LIGADA EM SEGUNDOS (2 BYTES)
        // TEMPO DE ESPERA ANTES DE ENTRAR NO MODO ECONOMIDO APÓS A IGNIÇÃO SER DESLIGADA EM SEGUNDO (2 BYTES)
        // MENSAGENS DE RASREAMENTO COM A IGNIÇÃO DESLIGADA EM MINUTOS (2 BYTES)
        // TEMPO MÁXIMO DE ESPERA DA MENSAGEM ACK VINDA DO COLETOR EM SEGUNDOS (1 BYTE)
        if ($cmd === "82") {
            $param = explode(';', $exec['parametros_cmd']);
            for ($i = 0; $i < 4; $i++) {
                if ($i < 2) {
                    $var[] = str_pad(dechex($param[$i]), 4, 0, STR_PAD_LEFT);
                } elseif ($i > 2) {
                    $var[] = str_pad(dechex($param[$i]), 2, 0, STR_PAD_LEFT);
                }
            }
            $param = implode('', $var);
        }

        // ACIONAR RASTREAMENTO DE EMERGENCIA, CONFIGURAÇÃO QUE ENVIA MAIS MENSAGENS DE LOCALIZAÇÃO DO QUE O NORMAL POR UM TEMPO DEFINIDO EM MINUTOS
        // DEVE SER INFORMADO NO BANCO DE DADOS O TEMPO DE ENVIO ENTRE CADA LOCALIZAÇÃO E O TEMPO EM MINUTOS QUE ESSE COMPORTAMENTO SERÁ MANTIDO
        if ($cmd === "71") {
            $param = explode(';', $exec['parametros_cmd']);
            foreach ($param as $value) {
                $var[] = str_pad(dechex($value), 4, 0, STR_PAD_LEFT);
            }
            $param = implode('', $var);
        }

        // REQUISIÇÃO DE POSIÇÃO IMEDIATO
        if ($cmd === "75") {
            $param = '';
        }

        // BLOQUEAR OU DESBLOQUEAR MOTOR
        if ($cmd === "83") {
            $param = str_pad(dechex($exec['parametros_cmd']), 2, 0, STR_PAD_LEFT);
        }

        // ZERAR CONTAGEM DO ODOMETRO
        if ($cmd === "84") {
            $param = '';
        }

        $seq = intval(hexdec($seq)) + 1;
        $seq = dechex($seq);
        $seq = str_pad($seq, 2, 0, STR_PAD_LEFT);

        $buffer = $prodId . $firmware . $trackerId . $cmd . $seq . $param;
        $crc16 = new crc16Calculate();
        $crc = $crc16->ComputeCrc($buffer);
        $message = $buffer . $crc;
        socket_sendto($socket, hex2bin($message), strlen($message), 0, $ip, $port);

        $comandos = new Comandos;
        $comandos->setComandStatus($exec['id'], time(), 1);
        $comandos->setComandSeq($exec['id'], 3);
    }

}
