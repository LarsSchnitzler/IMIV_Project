<?php
// Database connection variables
$servername = "localhost";
$username = "512430_4_1";
$password = "PETQsVWrx@J0";
$dbname = "512430_4_1";

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT * FROM weather_states");
    $stmt->execute();
    $weather_states = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT * FROM units");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = array(
        "weather_states" => $weather_states,
        "units" => $units
    );

    //------------------Output------------------
    header('Content-Type: application/json');

    echo json_encode($output);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$conn = null;
?>