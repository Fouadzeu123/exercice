<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SettingsService
{
    protected static $defaultSettings = [
        'vip_salaries' => [
            0 => 0.00,
            1 => 100.00,
            2 => 250.00,
            3 => 500.00,
            4 => 1000.00,
            5 => 2000.00,
        ],
        'min_deposit' => 500.00,
        'min_withdrawal' => 1500.00,
        'support_telegram' => 'https://t.me/armholding',
        'support_whatsapp' => 'https://wa.me/237000000000',
        'lucky_draw_cost' => 1,
        'generation_duration' => 0, // Instantaneous (0s)
        'block_generation_global' => false,
    ];

    /**
     * Get a setting by key or return a default value if not set.
     */
    public static function get($key = null, $default = null)
    {
        $settings = self::all();

        if (is_null($key)) {
            return $settings;
        }

        return data_get($settings, $key, $default);
    }

    /**
     * Get all settings from the JSON store.
     */
    public static function all()
    {
        if (!Storage::exists('settings.json')) {
            return self::$defaultSettings;
        }

        try {
            $content = Storage::get('settings.json');
            $data = json_decode($content, true);
            return array_merge(self::$defaultSettings, $data ?: []);
        } catch (\Exception $e) {
            return self::$defaultSettings;
        }
    }

    /**
     * Set a single setting.
     */
    public static function set($key, $value)
    {
        $settings = self::all();
        data_set($settings, $key, $value);

        Storage::put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        return $settings;
    }

    /**
     * Merge multiple settings at once.
     */
    public static function setMultiple(array $newSettings)
    {
        $settings = self::all();

        // Handle conversion of vip_salaries array if it has string keys from requests
        if (isset($newSettings['vip_salaries']) && is_array($newSettings['vip_salaries'])) {
            $vipSalaries = [];
            foreach ($newSettings['vip_salaries'] as $k => $val) {
                $vipSalaries[(int)$k] = (float)$val;
            }
            $newSettings['vip_salaries'] = $vipSalaries;
        }

        $settings = array_merge($settings, $newSettings);

        Storage::put('settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        return $settings;
    }
}
