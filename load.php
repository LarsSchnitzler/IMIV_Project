<?php
//---------------check wether file has been executed in the last 23h---------------
$lastExecFile = '../private/last_execution.txt';

if (file_exists($lastExecFile)) {
    $lastExec = strtotime(file_get_contents($lastExecFile));
    
    if (!isset($lastExec) || empty($lastExec)) {
        $lastExec = time();
    }

    $elapsed = time() - $lastExec;

    if ($elapsed < 23 * 60 * 60) {
        die("The Load-Script has already run within the last 23 hours.\n");
    }
}

file_put_contents($lastExecFile, time());

//-------------include config, include transform.php, and get log file-------------
require_once 'config.php';

$data = include('transform.php');
if ($data === false) {
    die();
}
$dataHourly = $data['dataHourly'];
$sunrise = $data['sunrise'];

$logFile = '../private/api_to_db_logfile.log';

//-------------------------------Database Connection-------------------------------
try {
    $conn = new PDO($dsn, $username, $password, $options);
    file_put_contents($logFile, "Database connection successful\n", FILE_APPEND);        
} catch (Exception $e) {
    file_put_contents($logFile, "Database connection failed: " . $e->getMessage() . "\n", FILE_APPEND);
}

//-----------Insert hourly Weather States of current day into Database-------------
try {
    // Start the transaction
    $conn->beginTransaction();

    foreach ($dataHourly as $hour) {
        //Extract hourly Weather Variables of current day
        $datetimeEpoch = ($hour['datetimeEpoch']);
        $temp = $hour['temp'];
        $humidity = $hour['humidity'];
        $precip = $hour['precip'];
        $pressure = $hour['pressure'];
        $windspeed = $hour['windspeed'];
        $visibility = $hour['visibility'];
        $cloudcover = $hour['cloudcover'];
        $solarenergy = $hour['solarenergy'];

        //Insert hourly Weather Variables of current day into one new entry in Database
        $stmt = $conn->prepare("INSERT INTO weather_states (datetimeEpoch, temp, humidity, precip, pressure, windspeed, visibility, cloudcover, solarenergy, sunrise) VALUES (:datetimeEpoch, :temp, :humidity, :precip, :pressure, :windspeed, :visibility, :cloudcover, :solarenergy, :sunrise)");
        $stmt->bindParam(':datetimeEpoch', $datetimeEpoch);
        $stmt->bindParam(':temp', $temp);
        $stmt->bindParam(':humidity', $humidity);
        $stmt->bindParam(':precip', $precip);
        $stmt->bindParam(':pressure', $pressure);
        $stmt->bindParam(':windspeed', $windspeed);
        $stmt->bindParam(':visibility', $visibility);
        $stmt->bindParam(':cloudcover', $cloudcover);
        $stmt->bindParam(':solarenergy', $solarenergy);

        $stmt->bindParam(':sunrise', $sunrise);

        $stmt->execute();
    }
    // Commit the transaction
    $conn->commit();
} catch(PDOException $e) {
    // Roll back the transaction if something failed
    $conn->rollBack();
    // logging the error message
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

//-------------------Delete all weather states older than a month-------------------
try {
    $stmt = $conn->prepare("DELETE FROM weather_states WHERE FROM_UNIXTIME(datetimeEpoch) < DATE_SUB(NOW(), INTERVAL 1 MONTH)");
    $stmt->execute();
    file_put_contents($logFile, "Older weather states deleted\n", FILE_APPEND);
} catch(PDOException $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

//-------------------------Database Connection Close-------------------------------
$conn = null;

file_put_contents($logFile, "Database connection closed\n", FILE_APPEND);

//--------------------------------end log message----------------------------------
file_put_contents($logFile, "-----------End of script-----------\n", FILE_APPEND);

?>