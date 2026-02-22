// IPv4 Utilities
class IPUtils {
    static validateCIDR(cidr) {
        const match = cidr.match(/^([0-9.]+)\/(\d{1,2})$/);
        if (!match) return false;

        const ip = match[1];
        const prefix = parseInt(match[2]);

        // Validate IP format
        const parts = ip.split('.');
        if (parts.length !== 4) return false;
        for (let part of parts) {
            const num = parseInt(part);
            if (isNaN(num) || num < 0 || num > 255) return false;
        }

        // Validate prefix range
        return prefix >= 0 && prefix <= 32;
    }

    static ipToLong(ip) {
        const parts = ip.split('.');
        return (parseInt(parts[0]) << 24) + (parseInt(parts[1]) << 16) + (parseInt(parts[2]) << 8) + parseInt(parts[3]);
    }

    static longToIp(long) {
        return ((long >>> 24) & 255) + '.' + ((long >>> 16) & 255) + '.' + ((long >>> 8) & 255) + (long & 255);
    }

    static cidrToMask(prefix) {
        const mask = -1 << (32 - prefix);
        return this.longToIp(mask >>> 0);
    }

    static networkBoundary(network, prefix) {
        const ipLong = this.ipToLong(network);
        const mask = -1 << (32 - prefix);
        return (ipLong & mask) >>> 0;
    }

    static broadcastAddress(networkLong, prefix) {
        const hostBits = 32 - prefix;
        return networkLong + (Math.pow(2, hostBits) - 1);
    }
}

// IPv6 Utilities
class IPv6Utils {
    static validateCIDR(cidr) {
        const match = cidr.match(/^([0-9a-fA-F:]+)\/(\d{1,3})$/);
        if (!match) return false;

        const ip = match[1];
        const prefix = parseInt(match[2]);

        if (prefix < 0 || prefix > 128) return false;

        // Basic IPv6 format check
        if (!this.isValidIPv6(ip)) return false;

        return true;
    }

    static isValidIPv6(ip) {
        // Simple check for IPv6 format
        return /^[0-9a-fA-F:]*$/.test(ip) && ip.split(':').length <= 8;
    }

    static ipv6ToBinary(ip) {
        const parts = this.expandIPv6(ip).split(':');
        let binary = '';

        for (let part of parts) {
            const hex = part.padStart(4, '0');
            const dec = parseInt(hex, 16);
            binary += dec.toString(2).padStart(16, '0');
        }

        return binary.padEnd(128, '0');
    }

    static binaryToIPv6(binary) {
        binary = binary.padEnd(128, '0');
        const parts = [];

        for (let i = 0; i < 8; i++) {
            const chunk = binary.substr(i * 16, 16);
            const hex = parseInt(chunk, 2).toString(16).padStart(4, '0');
            parts.push(hex);
        }

        return this.compressIPv6(parts.join(':'));
    }

    static expandIPv6(ip) {
        ip = ip.toLowerCase();

        if (ip.includes('::')) {
            const [left, right] = ip.split('::');
            const leftParts = left ? left.split(':') : [];
            const rightParts = right ? right.split(':') : [];
            const missing = 8 - (leftParts.length + rightParts.length);
            const zeroParts = Array(missing).fill('0000');
            const parts = [...leftParts, ...zeroParts, ...rightParts];
            ip = parts.join(':');
        }

        const parts = ip.split(':');
        return parts.map(p => p.padStart(4, '0')).join(':');
    }

    static compressIPv6(ip) {
        // Remove leading zeros from each segment
        ip = ip.replace(/:0+([0-9a-f])/g, ':$1');

        // Replace longest run of zeros with ::
        if (/:0(:0)+/.test(ip)) {
            ip = ip.replace(/(^|:)0(:0)+(?=:|$)/g, '::');
        }

        ip = ip.replace(/:::/g, '::');

        return ip;
    }

    static networkBoundary(network, prefix) {
        const binary = this.ipv6ToBinary(network);
        return binary.substr(0, prefix) + '0'.repeat(128 - prefix);
    }

    static broadcastAddress(networkBinary, prefix) {
        return networkBinary.substr(0, prefix) + '1'.repeat(128 - prefix);
    }

    static incrementBinary(binary, amount = 1) {
        binary = binary.padStart(128, '0');

        // Convert binary to decimal using BigInt
        let value = BigInt(0);
        for (let bit of binary) {
            value = (value << BigInt(1)) | BigInt(bit);
        }

        value += BigInt(amount);

        // Convert back to binary
        let result = value.toString(2).padStart(128, '0');
        return result;
    }

    static decrementBinary(binary, amount = 1) {
        binary = binary.padStart(128, '0');

        let value = BigInt(0);
        for (let bit of binary) {
            value = (value << BigInt(1)) | BigInt(bit);
        }

        value -= BigInt(amount);

        let result = value.toString(2).padStart(128, '0');
        return result;
    }

    static binaryGreaterThan(binary1, binary2) {
        for (let i = 0; i < 128; i++) {
            if (binary1[i] > binary2[i]) return true;
            if (binary1[i] < binary2[i]) return false;
        }
        return false;
    }
}
