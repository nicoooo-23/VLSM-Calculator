<?php

require_once "classes/IPUtils.php";
require_once "classes/Subnet.php";
require_once "classes/VLSMCalculator.php";

header("Content-Type: application/json");

try {

    // Get query parameters
    $base = $_GET['network'] ?? null;
    $hosts = $_GET['hosts'] ?? null;

    if (!$base || !$hosts) {
        throw new Exception("Missing parameters.");
    }

    if (!IPUtils::validateCIDR($base)) {
        throw new Exception("Invalid CIDR format.");
    }

    $hostArray = explode(",", $hosts);

    // Run calculation
    $calculator = new VLSMCalculator($base);
    $subnets = $calculator->calculate($hostArray);

    // Return JSON result
    echo json_encode($subnets, JSON_PRETTY_PRINT);

} catch (Exception $e) {

    // Return error in JSON format
    echo json_encode(["error" => $e->getMessage()]);
}