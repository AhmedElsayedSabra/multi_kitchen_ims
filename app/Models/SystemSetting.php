<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'options' => 'array',
    ];

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return $setting->getValue();
    }

    /**
     * Set a setting value by key.
     */
    public static function set(string $key, $value): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get the setting value with proper type casting.
     */
    public function getValue()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->value && $this->type === 'image') {
            return Storage::url($this->value);
        }

        return null;
    }

    /**
     * Get all settings as key-value array.
     */
    public static function getAllSettings(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }

    /**
     * Get settings by group.
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)->get()->pluck('value', 'key')->toArray();
    }

    /**
     * Get public settings.
     */
    public static function getPublicSettings(): array
    {
        return static::where('is_public', true)->get()->pluck('value', 'key')->toArray();
    }

    /**
     * Get formatted currency.
     */
    public static function formatCurrency(float $amount): string
    {
        $symbol = static::get('currency_symbol', '$');

        return $symbol.number_format($amount, 2);
    }

    /**
     * Get the singleton system settings instance with all settings as array.
     * Returns an array that can be used by Livewire components.
     */
    public static function getSettings(): array
    {
        $settings = static::all()->pluck('value', 'key')->toArray();

        $defaults = [
            'system_name' => 'Inventory Management System',
            'company_name' => 'My Restaurant Group',
            'logo' => null,
            'favicon' => null,
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'primary_color' => '#0ea5e9',
            'secondary_color' => '#059669',
            'print_header' => 'Multi-Kitchen Inventory Management System',
            'print_footer' => 'Confidential - For Internal Use Only',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'track_expiry_by_default' => true,
            'expiry_alert_days' => 7,
            'language' => 'en',
            'rtl_support' => true,
        ];

        $allSettings = array_merge($defaults, $settings);

        // Cast values to proper types
        $casts = [
            'track_expiry_by_default' => 'boolean',
            'expiry_alert_days' => 'integer',
            'rtl_support' => 'boolean',
        ];

        foreach ($casts as $key => $type) {
            if (isset($allSettings[$key])) {
                $allSettings[$key] = match ($type) {
                    'boolean' => filter_var($allSettings[$key], FILTER_VALIDATE_BOOLEAN),
                    'integer' => (int) $allSettings[$key],
                    default => $allSettings[$key],
                };
            }
        }

        // Add computed logo_url
        if (! empty($allSettings['logo'])) {
            $allSettings['logo_url'] = Storage::url($allSettings['logo']);
        } else {
            $allSettings['logo_url'] = null;
        }

        return $allSettings;
    }
}
