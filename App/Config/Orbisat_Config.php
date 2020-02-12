<?php
namespace App\Config;

global $orbisatConfig;
global $orbisatCommandsLabel;
global $osTrackerKey;

$osTrackerKey = "070a1350";

$orbisatConfig = [
    "configCommands"  => [
        "ENQ"             => "05",
        "EXT"             => "3f",
        "CONFIG_ENABLE"   => "40",
        "CONFIG_DISABLE"  => "7f",
        "CONFIG_APN"      => "41",
        "CONFIG_HOST"     => "42",
        "TOFF"            => "43",
        "TON"             => "44",
        "TEMERG"          => "46",
        "TECON"           => "47",
        "RETRY"           => "49",
        "KEY"             => "4a",
        "SET_DEFAULT_APN" => "4b",
        "PROTOCOL_TYPE"   => "4f",
        "ODOMETER"        => "50",
        "HOROMETER"       => "51",
    ],
    "serviceCommands" => [
        "SERVICE_ENABLE"  => "80",
        "SERVICE_DISABLE" => "cf",
        "RESET"           => "81",
        "SLEEP"           => "82",
        "WDTON"           => "83",
        "SIMUL_INPUT"     => "87",
        "OUTPUT"          => "88",
        "DUMP_LOG"        => "89",
        "OUT_DUMP_LOG"    => "8a",
        "DUMP_CONF"       => "8b",
        "OUT_DUMP_CONF"   => "8c",
        "CLEAR"           => "8e",
        "EMERG"           => "8f",
    ],
];

$orbisatCommandsLabel = [
    "CONFIG_APN"      => "CONFIGURAR_OPERADORA",
    "CONFIG_HOST"     => "TROCAR_IP",
    "SET_DEFAULT_APN" => "OPERADORA_PADRAO",
    "ODOMETER"        => "AJUSTAR_ODOMETRO",
    "HOROMETER"       => "AJUSTAR_HORIMETRO",
    "DUMP_LOG"        => "LOGS",
    "DUMP_CONF"       => "CONFIGURACOES",
    "EMERG"           => "MODO_EMERGENCIA",
    "PROTOCOL_TYPE"   => "CONFIGURAR_PROTOCOLO",
    "TOFF"            => "ATUALIZACAO_IGNICAO_DESLIGADA",
    "TON"             => "ATUALIZACAO_IGNICAO_LIGADA",
    "TEMERG"          => "ATUALIZACAO_EMERGENCIA_LIGADA",
    "TECON"           => "ATUALIZACAO_MODO_ECONOMIA",
    "RETRY"           => "TENTATIVAS_ATUALIZACAO",
    "RESET"           => "RESET",
    "ENQ"             => "SOLICITAR_COORDENADAS",
    "OUTPUT"          => "BLOQUEAR_VEICULO"
];
