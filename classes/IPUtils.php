<?php

class IPUtils {

    // Check if input looks like a valid CIDR (e.g. 192.168.1.0/24)
    public static function validateCIDR($cidr) {

        // Make sure it matches "IP/prefix" format
        if (!preg_match('/^([0-9\.]+)\/(\d{1,2})$/', $cidr, $matches)) {
            return false;
        }

        // Validate the IP part
        if (!filter_var($matches[1], FILTER_VALIDATE_IP)) {
            return false;
        }

        // Make sure prefix is between 0 and 32
        $prefix = (int)$matches[2];
        return ($prefix >= 0 && $prefix <= 32);
    }

    // Convert prefix (e.g. 24) to subnet mask (255.255.255.0)
    public static function cidrToMask($prefix) {
        return long2ip(-1 << (32 - $prefix));
    }

    // Normalize network to its proper boundary
    // Example: 192.168.1.5/24 → 192.168.1.0
    public static function networkBoundary($network, $prefix) {
        $ipLong = ip2long($network);
        $mask = -1 << (32 - $prefix);
        return $ipLong & $mask; // bitwise AND trims host bits
    }

    // Calculate broadcast address for a network
    public static function broadcastAddress($networkLong, $prefix) {
        $hostBits = 32 - $prefix;
        return $networkLong + (2 ** $hostBits) - 1;
    }
}