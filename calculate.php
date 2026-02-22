<?php

require_once "classes/IPUtils.php";
require_once "classes/Subnet.php";
require_once "classes/VLSMCalculator.php";
require_once "classes/IPv6Utils.php";
require_once "classes/IPv6Subnet.php";
require_once "classes/IPv6VLSMCalculator.php";

try {

    // Get form inputs
    $mode = $_POST['mode'] ?? 'ipv4';
    $hosts = explode(",", $_POST['hosts']);
    
    // Trim whitespace from hosts
    $hosts = array_map('trim', $hosts);

    if ($mode === 'ipv6') {
        // IPv6 Mode
        $base = $_POST['base_network'];
        
        // Validate base network format
        if (!IPv6Utils::validateCIDR($base)) {
            throw new Exception("Invalid IPv6 CIDR format. Use format like: 2001:db8::/32");
        }

        // Create calculator and run it
        $calculator = new IPv6VLSMCalculator($base);
        $subnets = $calculator->calculate($hosts);
        
        $isIPv6 = true;
    } else {
        // IPv4 Mode (default)
        $base = $_POST['base_network'];
        
        // Validate base network format
        if (!IPUtils::validateCIDR($base)) {
            throw new Exception("Invalid IPv4 CIDR format. Use format like: 192.168.1.0/24");
        }

        // Create calculator and run it
        $calculator = new VLSMCalculator($base);
        $subnets = $calculator->calculate($hosts);
        
        $isIPv6 = false;
    }

    echo "<!DOCTYPE html>";
    echo "<html><head><title>VLSM Results</title>";
    echo "<link rel='stylesheet' type='text/css' href='assets/style.css'>";
    echo "</head><body><div class='container'>";

    $modeLabel = $isIPv6 ? "IPv6" : "IPv4";
    echo "<h2>VLSM Results - $modeLabel</h2>";
    echo "<table border='1'>";

    if ($isIPv6) {
        echo "<tr>
            <th>Number of Hosts</th>
            <th>Subnet Address</th>
            <th>Prefix Length</th>
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
                <td>{$subnet->firstUsable}</td>
                <td>{$subnet->lastUsable}</td>
                <td>{$subnet->broadcast}</td>
            </tr>";
        }
    } else {
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
    }

    echo "</table>";

    echo "<br>";
    
    echo "<button onclick='window.location.href=\"index.php\"' style='display:block; margin:20px auto;'>Calculate Again</button>";

    echo "</div></body></html>";
} catch (Exception $e) {

    // Show error page
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Error</title>";
    echo "<link rel='stylesheet' type='text/css' href='assets/style.css'>";
    echo "</head><body><div class='container'>";
    echo "<h2>Error</h2>";
    echo "<p style='color:red; font-size: 16px;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<button onclick='window.location.href=\"index.php\"' style='display:block; margin:20px auto;'>Go Back</button>";
    echo "</div></body></html>";
}