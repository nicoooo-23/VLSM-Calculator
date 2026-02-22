<?php

class IPv6VLSMCalculator {

    private $baseNetworkBinary;
    private $basePrefix;
    private $baseBroadcast;
    private $currentPointer;
    private $subnets = [];

    public function __construct($cidr) {
        // Split network and prefix
        list($network, $prefix) = explode("/", $cidr);
        
        $this->basePrefix = (int)$prefix;
        
        // Normalize base network
        $this->baseNetworkBinary = IPv6Utils::networkBoundary($network, $prefix);
        
        // Calculate broadcast of base block
        $this->baseBroadcast = IPv6Utils::broadcastAddress(
            $this->baseNetworkBinary,
            $this->basePrefix
        );
        
        // First subnet starts at base network
        $this->currentPointer = $this->baseNetworkBinary;
    }

    // Determine how many host bits we need for N hosts
    private function requiredHostBits($hosts) {
        $h = 0;
        
        // Keep increasing until we can fit requested hosts
        // IPv6 uses 2^h addresses, with 2 reserved for network and broadcast
        while ((pow(2, $h) - 2) < $hosts) {
            $h++;
        }
        
        return $h;
    }

    public function calculate($hostArray) {
        // VLSM rule: biggest subnet first
        rsort($hostArray);
        
        foreach ($hostArray as $hosts) {
            $hosts = (int)$hosts;
            
            if ($hosts <= 0) {
                throw new Exception("Invalid host requirement: $hosts");
            }
            
            // Calculate required prefix
            $h = $this->requiredHostBits($hosts);
            $prefix = 128 - $h;
            
            // Prevent subnet bigger than base network
            if ($prefix < $this->basePrefix) {
                throw new Exception("Subnet larger than base network.");
            }
            
            $networkBinary = $this->currentPointer;
            $broadcastBinary = IPv6Utils::broadcastAddress($networkBinary, $prefix);
            
            // Make sure we don't overflow base network
            if ($this->binaryGreaterThan($broadcastBinary, $this->baseBroadcast)) {
                throw new Exception("Subnets exceed base network range.");
            }
            
            // Create subnet object
            $this->subnets[] = new IPv6Subnet($hosts, $networkBinary, $prefix);
            
            // Move pointer to next subnet
            $this->currentPointer = IPv6Utils::incrementBinary($broadcastBinary, 1);
        }
        
        return $this->subnets;
    }

    private function binaryGreaterThan($binary1, $binary2) {
        for ($i = 0; $i < 128; $i++) {
            if ($binary1[$i] > $binary2[$i]) {
                return true;
            } elseif ($binary1[$i] < $binary2[$i]) {
                return false;
            }
        }
        return false;
    }
}
