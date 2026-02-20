<?php

require_once "classes/IPUtils.php";
require_once "classes/Subnet.php";
require_once "classes/VLSMCalculator.php";

try {

    // Get form inputs
    $base = $_POST['base_network'];
    $hosts = explode(",", $_POST['hosts']);

    // Validate base network format
    if (!IPUtils::validateCIDR($base)) {
        throw new Exception("Invalid CIDR format.");
    }

    // Create calculator and run it
    $calculator = new VLSMCalculator($base);
    $subnets = $calculator->calculate($hosts);

    echo "<!DOCTYPE html>";
    echo "<html><head><title>VLSM Results</title>";
    echo "<link rel='stylesheet' type='text/css' href='assets/style.css'>";
    echo "</head><body><div class='container'>";

    echo "<h2>Results</h2>";
    echo "<table border='1'>";

    echo "<tr>
        <th>Number of Hosts</th>
        <th>Subnet Address</th>
        <th>Prefix Length</th>
        <th>Subnet Mask</th>
        <th>First Usable Address</th>
        <th>Last Usable Address</th>
        <th>Broadcast Address</th>
    </tr>";

    // Print each calculated subnet
    foreach ($subnets as $subnet) {
        echo "<tr>
            <td>{$subnet->requiredHosts}</td>
            <td>{$subnet->network}</td>
            <td>/{$subnet->prefix}</td>
            <td>{$subnet->mask}</td>
            <td>{$subnet->firstUsable}</td>
            <td>{$subnet->lastUsable}</td>
            <td>{$subnet->broadcast}</td>
        </tr>";
    }

    echo "</table>";

    echo "<br>";
    
    echo "<button onclick='window.location.href=\"index.php\"' style='display:block; margin:20px auto;'>Calculate Again</button>";

    echo "</div></body></html>";
} catch (Exception $e) {

    // Show error nicely
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}