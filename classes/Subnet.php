<?php

class Subnet {

    // These will hold final subnet details
    public $requiredHosts;
    public $network;
    public $prefix;
    public $mask;
    public $firstUsable;
    public $lastUsable;
    public $broadcast;

    // When we create a subnet, calculate everything immediately
    public function __construct($requiredHosts, $networkLong, $prefix) {

        $this->requiredHosts = $requiredHosts;

        // Convert numeric IP back to dotted format
        $this->network = long2ip($networkLong);

        $this->prefix = $prefix;

        // Convert prefix to human-readable mask
        $this->mask = IPUtils::cidrToMask($prefix);

        $hostBits = 32 - $prefix;
        $blockSize = 2 ** $hostBits;

        // Broadcast = last IP in block
        $broadcastLong = $networkLong + $blockSize - 1;

        // /31 and /32 have no usable host range
        if ($prefix >= 31) {
            $this->firstUsable = "N/A";
            $this->lastUsable = "N/A";
        } else {
            $this->firstUsable = long2ip($networkLong + 1);
            $this->lastUsable = long2ip($broadcastLong - 1);
        }

        $this->broadcast = long2ip($broadcastLong);
    }
}