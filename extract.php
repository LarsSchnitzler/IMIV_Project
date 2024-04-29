<?php
function fetchData() {
    //-------------start log file - append-------------
    $logFile = '../private/api_to_db_logfile.log';
    $message = "-----------Start of script-----------\n";
    $message .= "Today is: " . date("Y-m-d H:i:s") . "\n";
    file_put_contents($logFile, $message, FILE_APPEND);

    //----------------API Request----------------------
    $url = 'https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/Chur%2C%20Switzerland/today?unitGroup=metric&elements=datetimeEpoch%2Ctemp%2Chumidity%2Cprecip%2Cwindspeed%2Cpressure%2Ccloudcover%2Cvisibility%2Csolarenergy%2Csunrise&include=hours%2Cdays&key=XCSJ35ABYDTZUGVYUJTZ23HDP&contentType=json';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    // logging the error message if there is one. Then return false which exits the function
    if (curl_errno($ch)) {
        file_put_contents($logFile, 'Error making API request' . curl_error($ch) . "\n", FILE_APPEND);
        return false;
    }
    else {
        file_put_contents($logFile, 'API request successful' . "\n", FILE_APPEND);
    }
    
    curl_close($ch);
    
    return json_decode($response, true);
}

return fetchData();
?>