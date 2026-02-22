// IPv4 Subnet Class
class Subnet {
    constructor(requiredHosts, networkLong, prefix) {
        this.requiredHosts = requiredHosts;
        this.network = IPUtils.longToIp(networkLong);
        this.prefix = prefix;
        this.mask = IPUtils.cidrToMask(prefix);

        const hostBits = 32 - prefix;
        const blockSize = Math.pow(2, hostBits);
        const broadcastLong = networkLong + blockSize - 1;

        if (prefix >= 31) {
            this.firstUsable = 'N/A';
            this.lastUsable = 'N/A';
        } else {
            this.firstUsable = IPUtils.longToIp(networkLong + 1);
            this.lastUsable = IPUtils.longToIp(broadcastLong - 1);
        }

        this.broadcast = IPUtils.longToIp(broadcastLong);
    }
}

// IPv4 VLSM Calculator
class VLSMCalculator {
    constructor(cidr) {
        const [network, prefix] = cidr.split('/');
        this.basePrefix = parseInt(prefix);
        this.baseNetworkLong = IPUtils.networkBoundary(network, this.basePrefix);
        this.baseBroadcast = IPUtils.broadcastAddress(
            this.baseNetworkLong,
            this.basePrefix
        );
        this.currentPointer = this.baseNetworkLong;
        this.subnets = [];
    }

    requiredHostBits(hosts) {
        let h = 0;
        while (Math.pow(2, h) - 2 < hosts) {
            h++;
        }
        return h;
    }

    calculate(hostArray) {
        // Sort in descending order (biggest first)
        hostArray = hostArray.map(h => parseInt(h)).sort((a, b) => b - a);

        for (let hosts of hostArray) {
            if (hosts <= 0) {
                throw new Error('Invalid host requirement: ' + hosts);
            }

            const h = this.requiredHostBits(hosts);
            const prefix = 32 - h;
            const blockSize = Math.pow(2, h);

            if (prefix < this.basePrefix) {
                throw new Error('Subnet larger than base network.');
            }

            const networkLong = this.currentPointer;
            const broadcastLong = networkLong + blockSize - 1;

            if (broadcastLong > this.baseBroadcast) {
                throw new Error('Subnets exceed base network range.');
            }

            this.subnets.push(new Subnet(hosts, networkLong, prefix));
            this.currentPointer = broadcastLong + 1;
        }

        return this.subnets;
    }
}

// IPv6 Subnet Class
class IPv6Subnet {
    constructor(requiredHosts, networkBinary, prefix) {
        this.requiredHosts = requiredHosts;
        this.network = IPv6Utils.binaryToIPv6(networkBinary);
        this.prefix = prefix;

        if (prefix >= 127) {
            this.firstUsable = 'N/A';
            this.lastUsable = 'N/A';
        } else {
            const firstBinary = IPv6Utils.incrementBinary(networkBinary, 1);
            this.firstUsable = IPv6Utils.binaryToIPv6(firstBinary);

            const broadcastBinary = IPv6Utils.broadcastAddress(networkBinary, prefix);
            const lastBinary = IPv6Utils.decrementBinary(broadcastBinary, 1);
            this.lastUsable = IPv6Utils.binaryToIPv6(lastBinary);
        }

        const broadcastBinary = IPv6Utils.broadcastAddress(networkBinary, prefix);
        this.broadcast = IPv6Utils.binaryToIPv6(broadcastBinary);
    }
}

// IPv6 VLSM Calculator
class IPv6VLSMCalculator {
    constructor(cidr) {
        const [network, prefix] = cidr.split('/');
        this.basePrefix = parseInt(prefix);
        this.baseNetworkBinary = IPv6Utils.networkBoundary(network, this.basePrefix);
        this.baseBroadcast = IPv6Utils.broadcastAddress(
            this.baseNetworkBinary,
            this.basePrefix
        );
        this.currentPointer = this.baseNetworkBinary;
        this.subnets = [];
    }

    requiredHostBits(hosts) {
        let h = 0;
        while (Math.pow(2, h) - 2 < hosts) {
            h++;
        }
        return h;
    }

    calculate(hostArray) {
        // Sort in descending order (biggest first)
        hostArray = hostArray.map(h => parseInt(h)).sort((a, b) => b - a);

        for (let hosts of hostArray) {
            if (hosts <= 0) {
                throw new Error('Invalid host requirement: ' + hosts);
            }

            const h = this.requiredHostBits(hosts);
            const prefix = 128 - h;

            if (prefix < this.basePrefix) {
                throw new Error('Subnet larger than base network.');
            }

            const networkBinary = this.currentPointer;
            const broadcastBinary = IPv6Utils.broadcastAddress(networkBinary, prefix);

            if (IPv6Utils.binaryGreaterThan(broadcastBinary, this.baseBroadcast)) {
                throw new Error('Subnets exceed base network range.');
            }

            this.subnets.push(new IPv6Subnet(hosts, networkBinary, prefix));
            this.currentPointer = IPv6Utils.incrementBinary(broadcastBinary, 1);
        }

        return this.subnets;
    }
}
