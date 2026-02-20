<?php

class VLSMCalculator {

    private $baseNetworkLong;
    private $basePrefix;
    private $baseBroadcast;
    private $currentPointer; // where next subnet starts
    private $subnets = [];

    // Setup base network info
    public function __construct($cidr) {

        // Split network and prefix
        list($network, $prefix) = explode("/", $cidr);

        $this->basePrefix = (int)$prefix;

        // Normalize base network (just in case user typed weird host IP)
        $this->baseNetworkLong = IPUtils::networkBoundary($network, $prefix);

        // Calculate max allowed broadcast of base block
        $this->baseBroadcast = IPUtils::broadcastAddress(
            $this->baseNetworkLong,
            $this->basePrefix
        );

        // First subnet starts at base network
        $this->currentPointer = $this->baseNetworkLong;
    }

    // Determine how many host bits we need for N hosts
    private function requiredHostBits($hosts) {

        $h = 0;

        // Keep increasing until we can fit requested hosts
        while ((2 ** $h - 2) < $hosts) {
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
            $prefix = 32 - $h;
            $blockSize = 2 ** $h;

            // Prevent subnet bigger than base network
            if ($prefix < $this->basePrefix) {
                throw new Exception("Subnet larger than base network.");
            }

            $networkLong = $this->currentPointer;
            $broadcastLong = $networkLong + $blockSize - 1;

            // Make sure we don't overflow base network
            if ($broadcastLong > $this->baseBroadcast) {
                throw new Exception("Subnets exceed base network range.");
            }

            // Create subnet object and store it
            $this->subnets[] = new Subnet($hosts, $networkLong, $prefix);

            // Move pointer to next free IP
            $this->currentPointer = $broadcastLong + 1;
        }

        return $this->subnets;
    }
}