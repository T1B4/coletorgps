<?php

$coletores = array('orbisat', 'magneti_marelli', 'e3');

foreach ($coletores as $coletor) {
    $service = exec("ps aux | grep -v grep | grep -v checkServices | grep " . $coletor, $line, $result);

    if (strlen($service) < 5) {
        exec("nohup /opt/phps/7.0.5/bin/php /var/sites/coletorgps/html/" . $coletor . ".php > /var/sites/coletorgps/html/" . $coletor . ".log &");
        echo "Reiniciado o coletor : " . $coletor . "\n";
    }
}
