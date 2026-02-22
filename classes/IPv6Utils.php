<?php

class IPv6Utils {
    
    // Validate IPv6 CIDR format (e.g. 2001:db8::/32)
    public static function validateCIDR($cidr) {
        if (!preg_match('/^([0-9a-fA-F:]+)\/(\d{1,3})$/', $cidr, $matches)) {
            return false;
        }
        
        $ip = $matches[1];
        $prefix = (int)$matches[2];
        
        // Validate prefix range (0-128 for IPv6)
        if ($prefix < 0 || $prefix > 128) {
            return false;
        }
        
        // Validate IPv6 address
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }
        
        return true;
    }
    
    // Convert IPv6 string to binary (128-bit)
    public static function ipv6ToBinary($ip) {
        $parts = explode(':', $ip);
        $binary = '';
        
        foreach ($parts as $part) {
            if ($part === '') continue;
            $hex = str_pad($part, 4, '0', STR_PAD_LEFT);
            $binary .= str_pad(base_convert($hex, 16, 2), 16, '0', STR_PAD_LEFT);
        }
        
        return str_pad($binary, 128, '0', STR_PAD_RIGHT);
    }
    
    // Convert binary string back to IPv6
    public static function binaryToIPv6($binary) {
        $binary = str_pad($binary, 128, '0', STR_PAD_RIGHT);
        $parts = [];
        
        for ($i = 0; $i < 8; $i++) {
            $chunk = substr($binary, $i * 16, 16);
            $hex = base_convert($chunk, 2, 16);
            $parts[] = str_pad($hex, 4, '0', STR_PAD_LEFT);
        }
        
        $ipv6 = implode(':', $parts);
        return self::compressIPv6($ipv6);
    }
    
    // Compress IPv6 address (remove leading zeros and consolidate ::)
    public static function compressIPv6($ip) {
        // Remove leading zeros from each segment
        $ip = preg_replace('/:0+([0-9a-f])/i', ':$1', $ip);
        
        // Replace longest run of zeros with ::
        if (preg_match('/(:0+){2,}/i', $ip)) {
            $ip = preg_replace_callback('/(^|:)0(:0)+(?=:|$)/i', function() {
                return '::';
            }, $ip);
        }
        
        // Clean up multiple colons
        $ip = str_replace(':::', '::', $ip);
        
        return $ip;
    }
    
    // Expand compressed IPv6 address
    public static function expandIPv6($ip) {
        $ip = strtolower($ip);
        
        if (strpos($ip, '::') !== false) {
            $e = explode('::', $ip);
            $skip = 8 - (count(explode(':', $e[0])) + count(explode(':', $e[1])) - 2);
            $replacement = implode(':', array_fill(0, $skip, '0000'));
            $ip = $e[0] . ':' . $replacement . ':' . $e[1];
            $ip = trim(str_replace('::', ':', $ip), ':');
        }
        
        $parts = explode(':', $ip);
        foreach ($parts as &$part) {
            $part = str_pad($part, 4, '0', STR_PAD_LEFT);
        }
        
        return implode(':', $parts);
    }
    
    // Get network boundary for IPv6 CIDR
    public static function networkBoundary($network, $prefix) {
        $binary = self::ipv6ToBinary($network);
        // Keep prefix bits, zero out the rest
        $binary = substr($binary, 0, $prefix) . str_repeat('0', 128 - $prefix);
        return $binary;
    }
    
    // Get broadcast address for IPv6
    public static function broadcastAddress($networkBinary, $prefix) {
        // In IPv6, broadcast is network with all host bits set to 1
        return substr($networkBinary, 0, $prefix) . str_repeat('1', 128 - $prefix);
    }
    
    // Calculate the next IPv6 address
    public static function incrementBinary($binary, $amount = 1) {
        $binary = str_pad($binary, 128, '0', STR_PAD_LEFT);
        
        // Convert binary to hex string
        $hex = '';
        for ($i = 0; $i < 128; $i += 4) {
            $chunk = substr($binary, $i, 4);
            $hex .= base_convert($chunk, 2, 16);
        }
        
        // Convert hex to decimal for arithmetic
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);
        
        if (function_exists('bcadd')) {
            // Use BC Math: convert hex to decimal first
            $decimal = '';
            for ($i = 0; $i < strlen($hex); $i++) {
                $decimal = bcadd(bcmul($decimal, 16), base_convert($hex[$i], 16, 10));
            }
            $decimal = bcadd($decimal, $amount);
            
            // Convert back to hex
            $newHex = '';
            while (bccomp($decimal, 0) > 0) {
                $newHex = base_convert(bcmod($decimal, 16), 10, 16) . $newHex;
                $decimal = bcdiv($decimal, 16);
            }
            $hex = str_pad($newHex, 32, '0', STR_PAD_LEFT);
        } else {
            // Fallback: manual increment
            $hex = strtolower($hex);
            $carry = $amount;
            for ($i = strlen($hex) - 1; $i >= 0 && $carry > 0; $i--) {
                $digit = hexdec($hex[$i]) + $carry;
                $hex[$i] = dechex($digit % 16);
                $carry = intdiv($digit, 16);
            }
            if ($carry > 0) {
                throw new Exception("IPv6 address out of range");
            }
        }
        
        // Ensure we don't exceed 128 bits
        if (strlen($hex) > 32) {
            throw new Exception("IPv6 address out of range");
        }
        
        // Convert hex back to binary
        $newBinary = '';
        for ($i = 0; $i < strlen($hex); $i++) {
            $newBinary .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        
        return str_pad($newBinary, 128, '0', STR_PAD_LEFT);
    }
}
