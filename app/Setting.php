<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'value', 'description',
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key
     *
     * @param string $key
     * @param mixed $value
     * @param string $description
     * @return bool
     */
    public static function set($key, $value, $description = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }

    /**
     * Get all network settings
     *
     * @return array
     */
    public static function getNetworkSettings()
    {
        return [
            'ipType' => static::get('network.ipType', 'dynamic'),
            'ipAddress' => static::get('network.ipAddress', ''),
            'subnetMask' => static::get('network.subnetMask', ''),
            'gateway' => static::get('network.gateway', ''),
            'dnsServer' => static::get('network.dnsServer', '8.8.8.8'),
        ];
    }

    /**
     * Update network settings
     *
     * @param array $data
     * @return void
     */
    public static function updateNetworkSettings($data)
    {
        static::set('network.ipType', $data['ipType'] ?? 'dynamic', 'IP Configuration Type');
        static::set('network.ipAddress', $data['ipAddress'] ?? '', 'Static IP Address');
        static::set('network.subnetMask', $data['subnetMask'] ?? '', 'Subnet Mask');
        static::set('network.gateway', $data['gateway'] ?? '', 'Gateway Address');
        static::set('network.dnsServer', $data['dnsServer'] ?? '8.8.8.8', 'DNS Server');
    }

    /**
     * Get license settings
     *
     * @return array
     */
    public static function getLicenseSettings()
    {
        return [
            'key' => static::get('license.key', ''),
            'expiry' => static::get('license.expiry', ''),
            'status' => static::get('license.status', 'unlicensed'),
        ];
    }

    /**
     * Update license settings
     *
     * @param array $data
     * @return void
     */
    public static function updateLicenseSettings($data)
    {
        static::set('license.key', $data['key'] ?? '', 'License Key');
        static::set('license.expiry', $data['expiry'] ?? '', 'License Expiry Date');
        static::set('license.status', $data['status'] ?? 'unlicensed', 'License Status');
    }

    /**
     * Check if system license is active
     *
     * @return bool
     */
    public static function isLicenseActive()
    {
        $status = static::get('license.status', 'unlicensed');
        return $status === 'active';
    }
}
