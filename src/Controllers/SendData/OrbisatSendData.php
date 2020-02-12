<?php
namespace Src\Controllers\SendData;

use Src\Controllers\crc16_Kermit;
use Src\Models\Comandos;

// CLASSE RESPONSÁVEL PELO TRATAMENTO, CONFIGURAÇÃO E ENVIO DE COMANDOS AOS APARELHOS ORBISAT.
// BASICAMENTE A ÚNICA INFORMAÇÃO NECESSÁRIA NO PROCESSO É O ID DO APARELHO PARA CONSULTAR NO BANCO
// SE EXISTEM COMANDOS SETADOS PARA EXECUÇÃO DE ACORDO COM A COLUNA STATUS ONDE STATUS = 0 INDICA
// COMANDO AINDA NÃO EXECUTADO, A CLASSE ENTÃO HABILITA O MODO DE CONFIGURAÇÃO, ENVIA UM OU MAIS COMANDOS
// SEMPRE UM POR VEZ POIS O ORBISAT NÃO ACEITA O ENVIO DE MAIS DE UM COMANDO POR VEZ E QUANDO FINALIZADO
// SETA OS STATUS DOS COMANDOS QUE RESPONDERAM COM ACK PARA O VALOR 1 FINALIZANDO O PROCESSO.

class OrbisatSendData
{
    public function enableConfigMode($trackerId, $ip, $port, $data, $socket, $exec, $config_options, $tracker_key, $tx_inc)
    {
        $seq    = $this->translateData(substr($data, 4, 2));
        $finsyn = substr($seq, 0, 2);
        $rx     = substr($seq, 2, 3);
        $tx     = substr($seq, 5, 3);
        $tx     = bindec($tx) + $tx_inc;
        $tx     = substr($this->translateData($tx), 1, 3);
        $bin    = $finsyn . $tx . $rx;
        $seq    = $this->unTranslateData($finsyn . $tx . $rx);
        if ($tx == '111') {
            $seq = $this->unTranslateData('01110000');
        }

        $buffer  = $config_options . $seq . $trackerId . '00' . $tracker_key . '0000';
        $buf_len = dechex(strlen($buffer) / 2);
        $buf_len = str_pad($buf_len, 2, 0, STR_PAD_LEFT);

        $buffer = $config_options . $seq . $trackerId . $buf_len . $tracker_key;

        $crcGen = new crc16_Kermit();
        $crc    = $crcGen->ComputeCrc($buffer);
        $crc    = substr($crc, 2, 2) . substr($crc, 0, 2);

        $message = '02' . $buffer . $crc . '0d';
        $msglen  = strlen($message);
        socket_sendto($socket, hex2bin($message), $msglen, 0, $ip, $port);

        $comandos = new Comandos;
        $comandos->setComandSeq($exec['id'], 1);
    }

    public function executeComands($trackerId, $ip, $port, $socket, $cmd, $orbisatConfig, $data, $tx_inc)
    {
        global $orbisatCommandsLabel;
        $command = array_search($cmd['cmd'], $orbisatCommandsLabel);

        foreach ($orbisatConfig as $value) {
            foreach ($value as $op => $cod) {
                if ($command == $op) {
                    $cmdCode = $cod;
                }
            }
        }
        $param = $cmd['parametros_cmd'];

        //  ################### CONFIGURAR APN ###################

        // COMANDO PARA SETAR O CONFIG_APN – Configuração do APN (Access Point Name)
        // ESTRUTURA DO COMANDO : (INDEX = UINT8, OPERATOR = CHAR[10], APN = CHAR[30], USER = CHAR[10], PASSWD = CHAR[10])
        // ADICIONAR OS PARAMETROS DE CONFIGURAÇÃO NO BANCO DE DADOS SEPARADOS POR ; (PONTO E VIRGULA) PARA O PADRÃO DO SISTEMA
        // E FACILITAR O TRATAMENTO DOS MESMOS SEM INCORRER EM DADOS INCOERENTES QUE PODEM OCASIONAR MAL FUNCIONAMENTO DO EQUIPAMENTO

        if ($cmdCode == '41') {
            $final  = '';
            $array  = explode(";", $param);
            $config = ["index" => $array[0], "operator" => $array[1], "apn" => $array[2], "user" => $array[3], "passwd" => $array[4]];
            foreach ($config as $key => $value) {
                if ($key == 'index') {
                    $num   = 2;
                    $value = dechex($value);
                } elseif ($key == 'apn') {
                    $num   = 60;
                    $value = $this->String2Hex($value);
                } else {
                    $num   = 20;
                    $value = $this->String2Hex($value);
                }
                $value = str_pad($value, $num, 0, STR_PAD_RIGHT);
                $final .= $value;
            }
            $param = $final;
        }

        // ###################  CONFIGURAR HOST ###################

        // COMANDO PARA SETAR O CONFIG_HOST – Configuração para Acesso a Servidor
        // ESTRUTURA DO COMANDO : (INDEX = UINT8, HOST = CHAR[64], PORT = UINT16) ONDE INDEX REPRESENTA O NUMERO DE 2 A 4 NA TABELA
        // DO RASTREADOR, HOST REPRESENTA UM NOME DE HOST VÁLIDO NA INTERNET OU SEU IP E PORT REPRESENTA O NUMERO DA PORTA UTILIZADA
        // PARA COMUNICAÇÃO

        if ($cmdCode == '42') {

            $array = explode(";", $param);
            $index = str_pad(dechex(2), 2, 0, STR_PAD_LEFT);

            $host = $this->String2Hex($array[0]);
            $host = str_pad($host, 128, 0, STR_PAD_RIGHT);

            $door = str_pad(dechex($array[1]), 4, 0, STR_PAD_LEFT);
            $door = $this->reverseHexadecimal($door);

            $param = $index . $host . $door;

        }

        // ###################  CONFIGURAR TOFF ###################

        // COMANDO PARA SETAR O TOFF – Período de Atualização com Ignição Desligada, estrutura do comando em UINT16 3 BYTES

        if ($cmdCode == '43') {
            $param = dechex($param);
            $param = str_pad($param, 6, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        // ###################  CONFIGURAR TON ###################

        // COMANDO PARA SETAR O TON - Período de Atualização de Posição com Ignição Ligada, estrutura do comando em UINT16 3 BYTES

        if ($cmdCode == '44') {
            $param = dechex($param);
            $param = str_pad($param, 6, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
            var_dump($param);
        }

        // COMANDO PARA SETAR O TEMERG – Período de Atualização no Estado de EMERGÊNCIA, estrutura do comando em UINT16 3 BYTES

        if ($cmdCode == '46') {
            $param = dechex($param);
            $param = str_pad($param, 6, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        // COMANDO PARA SETAR O TECON – Tempo para Entrar no Modo de Economia, estrutura do comando em UINT16 3 BYTES

        if ($cmdCode == '47') {
            $param = dechex($param);
            $param = str_pad($param, 6, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        // COMANDO PARA SETAR O RETRY – Quantidade de Retransmissão, estrutura do comando em UINT16 3 BYTES

        if ($cmdCode == '49') {
            $param = dechex($param);
            $param = str_pad($param, 6, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        // COMANDO PARA SETAR O SET_DEFAULT_APN - Configuração da APN Padrão, estrutura do comando em UINT8 2 bytes, deve ser setado um valor entre 1 e 10, o valor 0 é reservado para o apn padrão configurado de fabrica e não pode ser alterado, se for setado o valor zero a apn default será desabilitada

        if ($cmdCode == '4b') {
            $param = dechex($param);
            $param = str_pad($param, 4, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        // COMANDO PARA SETAR O PROTOCOL_TYPE – Tipo de Protocolo de Comunicação (TCP/UDP), estrutura do comando em CHAR, setar U para UDP ou T para TCP/IP, padrão do sistema é UDP

        if ($cmdCode == '4f') {
            $param = $this->String2Hex($param);
            $param = str_pad($param, 2, 0, STR_PAD_LEFT);
            // $param = $this->reverseHexadecimal($param);
        }

        // COMANDO PARA SETAR O ODOMETRO, ENVIO PRECISA SER NO FORMATO UINT8[3] (4 BYTES), BYTES INVERTIDOS APÓS CONVERTIDOS PARA HEXADECIMAL, O VALOR SETADO NO BANCO PARA O USUÁRIO OU CLIENTE É EM KM DEVE SER MULTIPLICADO POR 10 POIS O SISTEMA DO RASTREADOR CONVERTE ESSE VALOR PARA CENTENAS DE METROS

        if ($cmdCode == '50') {
            if (!empty($param)) {
                $param = dechex((intval($param) * 10));
                $param = str_pad($param, 8, 0, STR_PAD_LEFT);
                $param = $this->reverseHexadecimal($param);
            } else {
                $param = '00000000';
            }
        }

        // COMANDO PARA SETAR O HORIMETRO, ENVIO PRECISA SER NO FORMATO UINT8[3] (4 BYTES), BYTES INVERTIDOS APÓS CONVERTIDOS PARA HEXADECIMAL, O VALOR SETADO NO BANCO QUE PARA O USUÁRIO OU CLIENTE É EM HORAS E DEVE SER MULTIPLICADO POR 60 POIS O SISTEMA DO RASTREADOR CONVERTE ESSE VALOR PARA MINUTOS

        if ($cmdCode == '51') {
            $param = dechex((intval($param) * 60));
            $param = str_pad($param, 8, 0, STR_PAD_LEFT);
            $param = $this->reverseHexadecimal($param);
        }

        if ($cmdCode == '89') {
            $param = '';
        }

        if ($cmdCode == '81') {
            $param = '';
        }

        if ($cmdCode == '8b') {
            echo "\n Executando solicitação das configurações do rastreador... \n";
            $param = '';
        }

        $seq    = $this->translateData(substr($data, 4, 2));
        $finsyn = substr($seq, 0, 2);
        $rx     = substr($seq, 2, 3);
        $tx     = substr($seq, 5, 3);
        $tx     = bindec($tx) + $tx_inc;
        $tx     = substr($this->translateData($tx), 1, 3);
        $bin    = $finsyn . $tx . $rx;
        $seq    = $this->unTranslateData($finsyn . $tx . $rx);
        if ($tx == '111') {
            $seq = $this->unTranslateData('01110000');
        }
        $buffer = $cmdCode . $seq . $trackerId . '00' . $param . '0000';
        $len    = strlen($buffer) / 2;
        $len    = str_pad(dechex(intval($len)), 2, 0, STR_PAD_LEFT);
        $buffer = $cmdCode . $seq . $trackerId . $len . $param;

        $crcGen = new crc16_Kermit();
        $crc    = $crcGen->ComputeCrc($buffer);
        $crc    = substr($crc, 2, 2) . substr($crc, 0, 2);

        $message = '02' . $buffer . $crc . '0d';

        $msglen = strlen($message);
        socket_sendto($socket, hex2bin($message), $msglen, 0, $ip, $port);

        $comandos = new Comandos;
        $comandos->setComandSeq($cmd['id'], 2);

    }

    public function disableConfigMode($trackerId, $ip, $port, $data, $socket, $exec, $config_options, $tracker_key, $tx_inc)
    {
        $seq    = $this->translateData(substr($data, 4, 2));
        $finsyn = substr($seq, 0, 2);
        $rx     = substr($seq, 2, 3);
        $tx     = substr($seq, 5, 3);
        $tx     = bindec($tx) + $tx_inc;
        $tx     = substr($this->translateData($tx), 1, 3);
        $bin    = $finsyn . $tx . $rx;
        $seq    = $this->unTranslateData($finsyn . $tx . $rx);
        if ($tx == '111') {
            $seq = $this->unTranslateData('01110000');
        }

        $buffer  = $config_options . $seq . $trackerId . '00' . $tracker_key . '0000';
        $buf_len = dechex(strlen($buffer) / 2);
        $buf_len = str_pad($buf_len, 2, 0, STR_PAD_LEFT);

        $buffer = $config_options . $seq . $trackerId . $buf_len . $tracker_key;

        $crcGen = new crc16_Kermit();
        $crc    = $crcGen->ComputeCrc($buffer);
        $crc    = substr($crc, 2, 2) . substr($crc, 0, 2);

        $message = '02' . $buffer . $crc . '0d';
        $msglen  = strlen($message);
        socket_sendto($socket, hex2bin($message), $msglen, 0, $ip, $port);

        $comandos = new Comandos;
        $comandos->setComandSeq($exec['id'], 3);
        $comandos->setComandStatus($exec['id'], time(), 1);
    }

    // Método que verifica se existem comandos registrados no banco de dados para serem executados, os comandos são executados um por vez e sempre após o recebimento de mensagem de coordenadas.

    public function checkComands($trackerId)
    {
        $cmd = new Comandos();
        $var = $cmd->getComands($trackerId);
        return $var;
    }

    // Método que cria o CRC para a mensagem baseado em uma parte da string a ser enviada.

    private function CRC16Calculate($buffer)
    {
        $result = 0x0000;
        if (($length = strlen($buffer)) > 0) {
            for ($offset = 0; $offset < $length; $offset++) {
                $result ^= ord($buffer[$offset]);
                for ($bitwise = 0; $bitwise < 8; $bitwise++) {
                    $lowBit = $result & 0x0001;
                    $result >>= 1;
                    if ($lowBit) {
                        $result ^= 0x8408;
                    }
                }
            }
        }
        return $result;
    }

    private function setBinMap()
    {
        return array(
            '0' => '0000',
            '1' => '0001',
            '2' => '0010',
            '3' => '0011',
            '4' => '0100',
            '5' => '0101',
            '6' => '0110',
            '7' => '0111',
            '8' => '1000',
            '9' => '1001',
            'a' => '1010',
            'b' => '1011',
            'c' => '1100',
            'd' => '1101',
            'e' => '1110',
            'f' => '1111',
        );
    }

    /**
     * Método que traduz dados hexadecimais para binários
     */
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

    /**
     * Método que traduz dados em binários para hexadecimal
     */
    public function unTranslateData($data)
    {
        $binmap   = $this->setBinMap();
        $string   = str_split($data, 4);
        $hexvalue = '';

        foreach ($string as $key => $value) {
            foreach ($binmap as $binkey => $binvalue) {
                if ($value == $binvalue) {
                    $hexvalue .= $binkey;
                }
            }
        }
        return $hexvalue;
    }

    // Método que faz a inversão dos bytes hexadecimais, exemplo (0e2f ficaria 2f0e).

    private function reverseHexadecimal($data)
    {
        $var = str_split($data, 2);
        $var = implode("", array_reverse($var));
        return $var;
    }

    // Método que converte String em Hexadecimal, utilizado quando precisamos enviar dados do tipo CHAR ou strings que não contenham somente numeros.

    private function String2Hex($string)
    {
        $hex = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $hex .= dechex(ord($string[$i]));
        }
        return $hex;
    }

}
