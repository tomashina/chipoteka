<?php

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once('config.php');
require_once('env.php');

require_once(DIR_STORAGE . 'vendor/autoload.php');
require_once(DIR_SYSTEM . 'config/eloquent.php');


if ( ! function_exists('cron_range')) {
    /**
     *
     * @param bool $slug
     *
     * @return string
     */
    function cron_range(string $key, int $offset = null, int $limit = null)
    {
        $set = \Agmedia\Models\Setting::query()->where('code', 'ag_cron_range')->where('key', $key)->first();

        if ( ! $set && ! isset($set->value)) {
            \Agmedia\Models\Setting::query()->insert([
                'store_id' => 0,
                'code' => 'ag_cron_range',
                'key' => $key,
                'value' => json_encode(['offset' => 0, 'limit' => 20]),
                'serialized' => 0
            ]);

            $set = \Agmedia\Models\Setting::query()->where('code', 'ag_cron_range')->where('key', $key)->first();

            if ($set) {
                return json_decode($set->value, true);
            }
        }

        if ( ! $offset && ! $limit) {
            return json_decode($set->value, true);
        }

        \Agmedia\Models\Setting::query()->where('setting_id', $set->setting_id)->update([
            'store_id' => 0,
            'code' => 'ag_cron_range',
            'key' => $key,
            'value' => json_encode(['offset' => $offset, 'limit' => $limit]),
            'serialized' => 0
        ]);

        $set = \Agmedia\Models\Setting::query()->where('code', 'ag_cron_range')->where('key', $key)->first();

        if ($set) {
            return json_decode($set->value, true);
        }

        return null;
    }
}