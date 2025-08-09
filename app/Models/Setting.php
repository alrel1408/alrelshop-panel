<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description'
    ];

    public $timestamps = false;

    protected $dates = ['updated_at'];

    // Helper methods
    public static function get($key, $default = null)
    {
        $setting = static::where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public static function set($key, $value, $description = null)
    {
        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description,
                'updated_at' => now()
            ]
        );
    }

    public static function getPanelName()
    {
        return static::get('panel_name', config('panel.name'));
    }

    public static function getAccountPrice()
    {
        return (float) static::get('account_price', config('panel.price_per_account'));
    }

    public static function getMaxAccountsPerUser()
    {
        return (int) static::get('max_accounts_per_user', 10);
    }

    public static function getDefaultAccountDuration()
    {
        return (int) static::get('default_account_duration', 30);
    }

    public static function isMaintenanceMode()
    {
        return (bool) static::get('maintenance_mode', false);
    }

    public static function getTelegramBotToken()
    {
        return static::get('telegram_bot_token');
    }

    public static function getTelegramChatId()
    {
        return static::get('telegram_chat_id');
    }

    // Get all settings as key-value pairs
    public static function getAllSettings()
    {
        return static::pluck('setting_value', 'setting_key')->toArray();
    }

    // Bulk update settings
    public static function updateSettings(array $settings)
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value);
        }
    }
}
