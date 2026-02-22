<?php

class IPv6Subnet {

    public $requiredHosts;
    public $network;
    public $prefix;
    public $firstUsable;
    public $lastUsable;
    public $broadcast;

    public function __construct($requiredHosts, $networkBinary, $prefix) {

        $this->requiredHosts = $requiredHosts;
        
        // Convert binary back to IPv6 address
        $this->network = IPv6Utils::binaryToIPv6($networkBinary);
        $this->prefix = $prefix;

        $hostBits = 128 - $prefix;
        
        // For IPv6, /127 and /128 are point-to-point links
        if ($prefix >= 127) {
            $this->firstUsable = "N/A";
            $this->lastUsable = "N/A";
        } else {
            // First usable is network + 1
            $firstBinary = IPv6Utils::incrementBinary($networkBinary, 1);
            $this->firstUsable = IPv6Utils::binaryToIPv6($firstBinary);
            
            // Last usable is broadcast - 1
            $broadcastBinary = IPv6Utils::broadcastAddress($networkBinary, $prefix);
            $lastBinary = self::decrementBinary($broadcastBinary, 1);
            $this->lastUsable = IPv6Utils::binaryToIPv6($lastBinary);
        }

        // Broadcast address is network with all host bits set to 1
        $broadcastBinary = IPv6Utils::broadcastAddress($networkBinary, $prefix);
        $this->broadcast = IPv6Utils::binaryToIPv6($broadcastBinary);
    }

    private static function decrementBinary($binary, $amount = 1) {
        $binary = str_pad($binary, 128, '0', STR_PAD_LEFT);
        
        // Convert binary to hex string
        $hex = '';
        for ($i = 0; $i < 128; $i += 4) {
            $chunk = substr($binary, $i, 4);
            $hex .= base_convert($chunk, 2, 16);
        }
        
        // Convert hex to decimal for arithmetic
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);
        
        if (function_exists('bcsub')) {
            // Use BC Math: convert hex to decimal first
            $decimal = '';
            for ($i = 0; $i < strlen($hex); $i++) {
                $decimal = bcadd(bcmul($decimal, 16), base_convert($hex[$i], 16, 10));
            }
            $decimal = bcsub($decimal, $amount);
            
            // Convert back to hex
            $newHex = '';
            while (bccomp($decimal, 0) > 0) {
                $newHex = base_convert(bcmod($decimal, 16), 10, 16) . $newHex;
                $decimal = bcdiv($decimal, 16);
            }
            $hex = str_pad($newHex, 32, '0', STR_PAD_LEFT);
        } else {
            // Fallback: manual decrement
            $hex = strtolower($hex);
            $borrow = $amount;
            for ($i = strlen($hex) - 1; $i >= 0 && $borrow > 0; $i--) {
                $digit = hexdec($hex[$i]) - $borrow;
                if ($digit < 0) {
                    $digit += 16;
                    $borrow = 1;
                } else {
                    $borrow = 0;
                }
                $hex[$i] = dechex($digit);
            }
            if ($borrow > 0) {
                throw new Exception("IPv6 address underflow");
            }
        }
        
        // Convert hex back to binary
        $newBinary = '';
        for ($i = 0; $i < strlen($hex); $i++) {
            $newBinary .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        
        return str_pad($newBinary, 128, '0', STR_PAD_LEFT);
    }
}
