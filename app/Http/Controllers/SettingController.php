<?php

namespace App\Http\Controllers;

use App\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all system settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $settings = [
            'network' => Setting::getNetworkSettings(),
            'license' => Setting::getLicenseSettings(),
        ];

        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }

    /**
     * Update system settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'network.ipType' => 'sometimes|in:static,dynamic',
            'network.ipAddress' => 'sometimes|nullable|ip',
            'network.subnetMask' => 'sometimes|nullable|ip',
            'network.gateway' => 'sometimes|nullable|ip',
            'network.dnsServer' => 'sometimes|nullable|ip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Update network settings if provided
        if ($request->has('network')) {
            $networkData = $request->network;

            // Validate that if ipType is static, required fields are present
            if (isset($networkData['ipType']) && $networkData['ipType'] === 'static') {
                if (empty($networkData['ipAddress']) || empty($networkData['subnetMask']) || empty($networkData['gateway'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'For static IP, you must provide ipAddress, subnetMask, and gateway'
                    ], 400);
                }
            }

            Setting::updateNetworkSettings($networkData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => [
                'network' => Setting::getNetworkSettings(),
                'license' => Setting::getLicenseSettings(),
            ]
        ]);
    }

    /**
     * Get current network status from the system.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function currentNetworkStatus()
    {
        $status = [
            'ipAddress' => 'Unknown',
            'subnetMask' => 'Unknown',
            'gateway' => 'Unknown',
            'dnsServer' => 'Unknown',
        ];

        try {
            // Try to get current IP address
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                $output = shell_exec('ipconfig');
                if ($output) {
                    // Parse IPv4 Address
                    if (preg_match('/IPv4 Address[.\s]+:\s+([0-9.]+)/', $output, $matches)) {
                        $status['ipAddress'] = $matches[1];
                    }
                    // Parse Subnet Mask
                    if (preg_match('/Subnet Mask[.\s]+:\s+([0-9.]+)/', $output, $matches)) {
                        $status['subnetMask'] = $matches[1];
                    }
                    // Parse Default Gateway
                    if (preg_match('/Default Gateway[.\s]+:\s+([0-9.]+)/', $output, $matches)) {
                        $status['gateway'] = $matches[1];
                    }
                }
            } else {
                // Linux/Unix
                $output = shell_exec('ip addr show');
                if ($output) {
                    // Parse IP address
                    if (preg_match('/inet\s+([0-9.]+)\/([0-9]+)/', $output, $matches)) {
                        $status['ipAddress'] = $matches[1];
                        // Convert CIDR to subnet mask
                        $cidr = (int)$matches[2];
                        $status['subnetMask'] = long2ip(-1 << (32 - $cidr));
                    }
                }

                // Get gateway
                $gatewayOutput = shell_exec('ip route | grep default');
                if ($gatewayOutput && preg_match('/default via ([0-9.]+)/', $gatewayOutput, $matches)) {
                    $status['gateway'] = $matches[1];
                }

                // Get DNS server
                if (file_exists('/etc/resolv.conf')) {
                    $resolvConf = file_get_contents('/etc/resolv.conf');
                    if (preg_match('/nameserver\s+([0-9.]+)/', $resolvConf, $matches)) {
                        $status['dnsServer'] = $matches[1];
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail and return Unknown values
        }

        return response()->json([
            'success' => true,
            'current' => $status
        ]);
    }

    /**
     * Apply network settings to the system (Linux only).
     * WARNING: This modifies system network configuration files.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyNetworkSettings(Request $request)
    {
        // Only superusers can apply network settings
        if (!auth()->user()->isSuperuser()) {
            return response()->json([
                'success' => false,
                'message' => 'Only superusers can apply network settings'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ipType' => 'required|in:static,dynamic',
            'ipAddress' => 'required_if:ipType,static|nullable|ip',
            'subnetMask' => 'required_if:ipType,static|nullable|ip',
            'gateway' => 'required_if:ipType,static|nullable|ip',
            'dnsServer' => 'sometimes|nullable|ip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Check if running on Windows (development)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return response()->json([
                'success' => false,
                'message' => 'Network configuration is only supported on Linux systems (Nano Pi deployment)'
            ], 400);
        }

        // Save settings to database first
        Setting::updateNetworkSettings($request->all());

        // Apply network configuration using NetworkManager (nmcli)
        try {
            $ipType = $request->input('ipType');
            $interface = 'eth0'; // Primary network interface on NanoPi

            // Get connection name for eth0
            $connectionName = trim(shell_exec("nmcli -t -f NAME,DEVICE connection show --active | grep '{$interface}' | cut -d: -f1"));

            if (empty($connectionName)) {
                throw new \Exception("No active connection found for interface {$interface}");
            }

            if ($ipType === 'dynamic') {
                // Configure DHCP
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.method auto 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.addresses '' 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.gateway '' 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.dns '' 2>&1");
            } else {
                // Configure Static IP
                $ipAddress = $request->input('ipAddress');
                $subnetMask = $request->input('subnetMask');
                $gateway = $request->input('gateway');
                $dnsServer = $request->input('dnsServer', '8.8.8.8');

                // Convert subnet mask to CIDR notation
                $cidr = $this->subnetMaskToCidr($subnetMask);
                $ipWithCidr = "{$ipAddress}/{$cidr}";

                shell_exec("nmcli connection modify '{$connectionName}' ipv4.method manual 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.addresses '{$ipWithCidr}' 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.gateway '{$gateway}' 2>&1");
                shell_exec("nmcli connection modify '{$connectionName}' ipv4.dns '{$dnsServer}' 2>&1");
            }

            // Apply changes by bringing connection down and up
            shell_exec("nmcli connection down '{$connectionName}' 2>&1");
            shell_exec("nmcli connection up '{$connectionName}' 2>&1");

            return response()->json([
                'success' => true,
                'message' => 'Network settings applied successfully. Connection will reconnect momentarily.',
                'info' => 'If you lose connection, the system will revert to previous settings automatically.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply network settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert subnet mask to CIDR notation
     * E.g., 255.255.255.0 => 24
     */
    private function subnetMaskToCidr($subnetMask)
    {
        $long = ip2long($subnetMask);
        $base = ip2long('255.255.255.255');
        return 32 - log(($long ^ $base) + 1, 2);
    }
}
