<?php
require_once 'config.php';

//-------------------------------Database Connection-------------------------------
try {
    $conn = new PDO($dsn, $username, $password, $options);   
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

//----------------Select all Weather States and Units from Database-----------------
try {
    // Prepare and execute the weatehr-SQL statement
    $stmt = $conn->prepare("SELECT * FROM weather_states");
    $stmt->execute();
    $weather_states = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare and execute the units-SQL statement
    $stmt = $conn->prepare("SELECT * FROM units");
    $stmt->execute();
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //make new assoc. array with the physical quantity-value as key and the unit-value as value.-Out of $units
    $units = array_column($units, 'unit', 'physical_quantity');

    $output = array(
        "weather_states" => $weather_states,
        "units" => $units
    );

    //------------------Output------------------
    header('Content-Type: application/json');

    echo json_encode($output);
} catch(PDOException $e) {
    echo json_encode(["error: " . $e->getMessage()]);
}

// Close the database connection
$conn = null;
?>