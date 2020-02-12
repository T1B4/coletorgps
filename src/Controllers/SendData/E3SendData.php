<?php
namespace Src\Controllers\SendData;

use Src\Models\Comandos;

// CLASSE RESPONSÁVEL PELO TRATAMENTO, CONFIGURAÇÃO E ENVIO DE COMANDOS AOS APARELHOS E3.
// BASICAMENTE A ÚNICA INFORMAÇÃO NECESSÁRIA NO PROCESSO É O ID DO APARELHO PARA CONSULTAR NO BANCO
// SE EXISTEM COMANDOS SETADOS PARA EXECUÇÃO DE ACORDO COM A COLUNA STATUS ONDE STATUS = 0 INDICA
// COMANDO AINDA NÃO EXECUTADO

class E3SendData
{
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function sendCommands($exec, $cmd, $ip, $port, $trackerId, $socket)
    {
        // DEFINIR VELOCIDADE MÁXIMA PERMITIDA PARA O VEICULO
        if ($cmd === "OD") {
            $param = str_pad(dechex($exec['parametros_cmd']), 4, 0, STR_PAD_LEFT);
        }

        // DESPERTAR RASTREADOR
        if ($cmd === "SP") {
            $param = str_pad(dechex($exec['parametros_cmd']), 4, 0, STR_PAD_LEFT);
        }

        // TROCAR O IP E A PORTA COM A QUAL O RASTREADOR SE COMUNICA PARA O ENVIO DE INFORMAÇÕES
        if ($cmd === "IP") {
            $data    = [];
            $tmp     = '';
            $data    = explode(';', $exec['parametros_cmd']);
            $index   = $data[0];
            $address = array_reverse(explode('.', $data[1]));
            $door    = str_pad(dechex($data[2]), 4, 0, STR_PAD_LEFT);
            for ($i = 0; $i < count($address); $i++) {
                $tmp .= str_pad(dechex($address[$i]), 2, 0, STR_PAD_LEFT);
            }
            $param = strtoupper($index) . ',' . strtoupper($tmp) . ',' . strtoupper($door);
        }

        // AJUSTAR O VALOR DO ODOMETRO NO APARELHO
        if ($cmd === "LC") {
            if (!empty($exec['parametros_cmd'])) {
                $param = str_pad(dechex($exec['parametros_cmd'] * 10), 6, 0, STR_PAD_LEFT);
            } else {
                $param = '000000';
            }
        }

        // SOLICITAR STATUS DO APARELHO
        if ($cmd === "CX") {
            $param = '';
        }

        // AJUSTAR TEMPO DE COMUNICAÇÃO COM O COLETOR QUANTO A IGNIÇÃO ESTA LIGADA
        if ($cmd === "HT") {
            $param = explode(';', $exec['parametros_cmd']);
            foreach ($param as $value) {
                $var[] = str_pad(dechex($value), 4, 0, STR_PAD_LEFT);
            }
            $param = implode(',', $var);
        }

        // LIGAR ALARME ANTI FURTO DO VEICULO
        if ($cmd === "FD1") {
            $cmd   = "FD";
            $param = 'F1';
        }

        // DESLIGAR ALARME ANTI FURTO DO VEICULO
        if ($cmd === "FD2") {
            $cmd   = "FD";
            $param = 'F2';
        }

        // BLOQUEIO DO VEICULO
        if ($cmd === "FD3") {
            $cmd   = "FD";
            $param = 'Y1';
        }

        // DESBLOQUEIO DO VEICULO
        if ($cmd === "FD4") {
            $cmd   = "FD";
            $param = 'Y2';
        }

        // ATIVA/DESATIVA MODO DE ECONOMIA DE ENERGIA
        if ($cmd === "PM") {
            if ($exec['parametros_cmd'] === "ATIVAR") {
                $param = "01";
            }
            if ($exec['parametros_cmd'] === "DESATIVAR") {
                $param = "00";
            }
        }

        // REINICIAR O RASTREADOR
        if ($cmd === "CQ") {
            $param = '';
        }

        // COMANDO PARA LOCALIZAR O RASTREADOR
        if ($cmd === "GZ") {
            $param = explode(";", $exec['parametros_cmd']);
            foreach ($param as $value) {
                if (strlen($value <= 4)) {
                    $var[] = str_pad(dechex($value), 4, 0, STR_PAD_LEFT);
                } else {
                    $var[] = $value;
                }
            }
            $param = implode(",", $param);
        }

        // CONFIGURAR CELULAR PARA MONITORAMENTO
        if ($cmd === "ZH") {
            $param = $exec['parametros_cmd'];
        }

        // CONFIGURAR CELULAR PARA MONITORAMENTO
        if ($cmd === "FH") {
            $param = $exec['parametros_cmd'];
        }

        // ATIVAR OU DESATIVAR ENVIO DE SMS
        if ($cmd === "MG") {
            if ($exec['parametros_cmd'] === "ATIVAR") {
                $param = "01";
            } else {
                $param = "00";
            }
        }

        $message = "*ET," . $trackerId . "," . $cmd . "," . $param . "#";
        socket_sendto($socket, $message, strlen($message), 0, $ip, $port);

        $comandos = new Comandos($this->logger);
        try {
            $comandos->setComandStatus($exec['id'], time(), 1);
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

        try {
            $comandos->setComandSeq($exec['id'], 3);
        } catch (\Throwable $th) {
            $this->logger->error($th->getMessage() . ' - on file ' . $th->getFile() . ' - on line ' . $th->getLine());
        }

    }

}