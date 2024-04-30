<?php
//---------------------get log file-----------------------
$logFile = '../private/api_to_db_logfile.log';

//--------------------include extract.php--------------------
$data = include('extract.php');
if ($data === false) {
    die();
}

try {
    $sunrise = $data['days'][0]['sunrise']; // gets sunrise time for that day
    $dataHourly = $data['days'][0]['hours']; // reduces response to array of hourly states
    file_put_contents($logFile, "Data transformed successfully\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logFile, "Error transforming data\n", FILE_APPEND);
    return false;
}

return array(
    "sunrise" => $sunrise,
    "dataHourly" => $dataHourly
);  
?>