<?php

namespace Agmedia\Luceed\Connection;

use Agmedia\Helpers\Log;
use Agmedia\Luceed\LuceedEndPoints;
use Agmedia\Models\Setting;

/**
 * Class LuceedService
 * @package Agmedia\Luceed\Connection
 */
class LuceedService
{
    /**
     * @var string
     */
    private const MONITOR_SETTING_CODE = 'ag_luceed_monitor';

    /**
     * @var string
     */
    private const MONITOR_SETTING_KEY = 'luceed_service_health';

    /**
     * @var string
     */
    private const MONITOR_ENDPOINT_KEY = 'warehouse_list';

    /**
     * @var int
     */
    private const MONITOR_MIN_DOWNTIME_SECONDS = 300;

    /**
     * @var array
     */
    private const NOTIFICATION_RECIPIENTS = [
        'webalarm@chipoteka.hr',
        'tomislav@agmedia.hr',
    ];

    /**
     * @var mixed|string
     */
    private $base_url;

    /**
     * @var mixed|string
     */
    private $username;

    /**
     * @var mixed|string
     */
    private $password;

    /**
     * @var string
     */
    public $env;


    /**
     * LuceedService constructor.
     */
    public function __construct(bool $b2b = false)
    {
        $this->base_url = agconf('service.base_url');
        $this->username = agconf('service.username');
        $this->password = agconf('service.password');
        $this->env      = agconf('env');

        if ($b2b) {
            $this->username = 'b2bweb';
            $this->password = 'DJi2L7CNqa';
        }
    }


    /**
     * @param string $url
     * @param string $option
     * @param int    $timeoutSeconds
     *
     * @return mixed
     */
    public function get(string $url, string $option = '', int $timeoutSeconds = 0)
    {
        $targetUrl = $url . $option;

        $this->logRequest('GET', $targetUrl);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return file_get_contents(DIR_UPLOAD . 'luceed_json/' . $url);
        }

        // Production or live enviroment.
        try {
            $result    = $this->performGetRequest($targetUrl, 1000, $timeoutSeconds);
            $response  = $result['response'];
            $curlError = $result['error'];
            $httpCode  = $result['http_code'];

            if ($response === false || $httpCode >= 500 || $httpCode === 0) {
                $message = $curlError ?: ('HTTP status ' . $httpCode);

                $this->logFailure('GET', $targetUrl, $message);
            }

            return $response;

        } catch (\Exception $exception) {
            $this->log('GET', $targetUrl, $exception);

            return false;
        }
    }


    /**
     * @param string $url
     * @param array  $body
     *
     * @return mixed
     */
    public function post(string $url, array $body)
    {
        $this->logRequest('POST', $url, $body);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return [];
        }

        // Production or live enviroment.
        try {
            $ch = curl_init($this->base_url . $url);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
            curl_setopt ($ch, CURLOPT_POST, true);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode >= 500 || $httpCode === 0) {
                $message = $curlError ?: ('HTTP status ' . $httpCode);

                $this->logFailure('POST', $url, $message);
            }

            $this->logResponse('POST', $url, [$body, is_string($response) ? json_decode($response) : null]);

            return $response;

        } catch (\Exception $exception) {
            $this->log('POST', $url, $exception);

            return false;
        }
    }


    /**
     * @param string $url
     * @param array  $body
     *
     * @return mixed
     */
    public function put(string $url, array $body)
    {
        $this->logRequest('PUT', $url, $body);

        // Local or testing enviroment.
        if ($this->env == 'local') {
            return [];
        }

        // Production or live enviroment.
        try {
            $ch = curl_init($this->base_url . $url);
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $httpCode >= 500 || $httpCode === 0) {
                $message = $curlError ?: ('HTTP status ' . $httpCode);

                $this->logFailure('PUT', $url, $message);
            }

            $this->logResponse('PUT', $url, [$body, is_string($response) ? json_decode($response) : null]);

            return $response;

        } catch (\Exception $exception) {
            $this->log('PUT', $url, $exception);

            return false;
        }
    }


    /**
     * Dedicated health probe for cron monitoring.
     *
     * @return array
     */
    public function probeAvailability(): array
    {
        $url = $this->resolveMonitorUrl();

        if ( ! $this->shouldMonitorAvailability()) {
            return [
                'status'       => 'up',
                'notification' => null,
                'checked_at'   => date('Y-m-d H:i:s'),
                'url'          => $url,
                'http_code'    => 200,
                'error'        => '',
                'monitored'    => false,
            ];
        }

        try {
            $result = $this->performGetRequest($url, 1000, 5);

            if ($result['response'] === false || $result['http_code'] >= 500 || $result['http_code'] === 0) {
                $message = $result['error'] ?: ('HTTP status ' . $result['http_code']);

                $this->logFailure('GET', $url, $message);
            }

            return $this->recordAvailabilityProbe('GET', $url, $result['response'], $result['http_code'], $result['error']);
        } catch (\Exception $exception) {
            $this->log('GET', $url, $exception);

            return $this->recordAvailabilityProbe('GET', $url, false, 0, $exception->getMessage());
        }
    }


    /**
     * @param string     $type
     * @param string     $url
     * @param \Exception $exception
     */
    private function log(string $type, string $url, \Exception $exception): void
    {
        $log_name = 'luceed_' . $type . '_error';

        Log::store($url, $log_name);
        Log::store($exception->getMessage(), $log_name);
    }


    /**
     * @param string $type
     * @param string $url
     * @param string $message
     */
    private function logFailure(string $type, string $url, string $message): void
    {
        $log_name = 'luceed_' . $type . '_error';

        Log::store($url, $log_name);
        Log::store($message, $log_name);
    }


    /**
     * @param string $type
     * @param string $url
     * @param array  $body
     */
    private function logRequest(string $type, string $url, array $body = []): void
    {
        $log_name = 'luceed_' . $type . '_request';

        Log::store($url, $log_name);

        if (! empty($body)) {
            Log::store($body, $log_name);
            Log::store(json_encode($body), $log_name);
        }
    }


    /**
     * @param string $type
     * @param string $url
     * @param array  $body
     */
    private function logResponse(string $type, string $url, array $body = []): void
    {
        $log_name = 'luceed_' . $type . '_response';

        Log::store($url, $log_name);

        if (! empty($body)) {
            Log::store($body, $log_name);
        }
    }


    /**
     * @return bool
     */
    private function shouldMonitorAvailability(): bool
    {
        return $this->env !== 'local';
    }


    /**
     * @param mixed  $response
     * @param int    $httpCode
     * @param string $error
     *
     * @return bool
     */
    private function isAvailableResponse($response, int $httpCode, string $error): bool
    {
        if ($response === false || $error) {
            return false;
        }

        return $httpCode >= 200 && $httpCode < 400;
    }


    /**
     * @return array|null
     */
    private function getAvailabilityState(): ?array
    {
        $setting = Setting::query()
                          ->where('store_id', 0)
                          ->where('code', self::MONITOR_SETTING_CODE)
                          ->where('key', self::MONITOR_SETTING_KEY)
                          ->first();

        if ( ! $setting || ! $setting->value) {
            return null;
        }

        $decoded = json_decode((string) $setting->value, true);

        return is_array($decoded) ? $decoded : null;
    }


    /**
     * @param array $payload
     */
    private function saveAvailabilityState(array $payload): void
    {
        $setting = Setting::query()
                          ->where('store_id', 0)
                          ->where('code', self::MONITOR_SETTING_CODE)
                          ->where('key', self::MONITOR_SETTING_KEY)
                          ->first();

        $value = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($setting) {
            $setting->value      = $value;
            $setting->serialized = 0;
            $setting->save();

            return;
        }

        Setting::query()->create([
            'store_id'   => 0,
            'code'       => self::MONITOR_SETTING_CODE,
            'key'        => self::MONITOR_SETTING_KEY,
            'value'      => $value,
            'serialized' => 0,
        ]);
    }


    /**
     * @param array $payload
     * @param array $previousState
     */
    private function sendAvailabilityNotification(array $payload, array $previousState = []): void
    {
        $config = $this->getMailConfig();

        if ( ! $config['from'] || ! $config['sender']) {
            Log::store('Mail settings are missing for Luceed availability notifications.', 'luceed_health_monitor_error');

            return;
        }

        $mail = new \Mail($config['engine']);
        $mail->parameter = $config['parameter'];
        $mail->smtp_hostname = $config['smtp_hostname'];
        $mail->smtp_username = $config['smtp_username'];
        $mail->smtp_password = html_entity_decode($config['smtp_password'], ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = (int) $config['smtp_port'];
        $mail->smtp_timeout = (int) $config['smtp_timeout'];
        $mail->setTo(self::NOTIFICATION_RECIPIENTS);
        $mail->setFrom($config['from']);
        $mail->setSender($config['sender']);
        $mail->setSubject($this->resolveNotificationSubject($payload['status']));
        $mail->setText($this->resolveNotificationBody($payload, $previousState));
        $mail->send();
    }


    /**
     * @param string $status
     *
     * @return string
     */
    private function resolveNotificationSubject(string $status): string
    {
        if ($status === 'down') {
            return '[Chipoteka] Luceed servis nije dostupan';
        }

        return '[Chipoteka] Luceed servis je ponovno dostupan';
    }


    /**
     * @param array $payload
     * @param array $previousState
     *
     * @return string
     */
    private function resolveNotificationBody(array $payload, array $previousState = []): string
    {
        $lines = [];

        if ($payload['status'] === 'down') {
            $lines[] = 'Luceed servis nije dostupan najmanje 5 minuta.';
            $lines[] = 'Prekid detektiran: ' . ($payload['down_since'] ?: $payload['checked_at']);
            $lines[] = 'Mail poslan: ' . $payload['checked_at'];
        } else {
            $lines[] = 'Luceed servis je ponovno dostupan.';
            $lines[] = 'Vrijeme oporavka: ' . $payload['checked_at'];

            $downSince = $previousState['down_since'] ?? ($previousState['last_down_since'] ?? null);
            $downNotifiedAt = $previousState['down_notified_at'] ?? ($previousState['last_down_notified_at'] ?? null);

            if ($downSince) {
                $lines[] = 'Prekid zapoceo: ' . $downSince;
            }

            if ($downNotifiedAt) {
                $lines[] = 'Alarm poslan: ' . $downNotifiedAt;
            }

            $duration = $this->resolveDowntimeDuration($downSince, $payload['checked_at']);

            if ($duration) {
                $lines[] = 'Trajanje prekida: ' . $duration;
            }
        }

        $lines[] = 'Metoda: ' . $payload['method'];
        $lines[] = 'Endpoint: ' . $payload['url'];
        $lines[] = 'HTTP status: ' . (int) $payload['http_code'];

        if (isset($payload['error']) && $payload['error']) {
            $lines[] = 'Greska: ' . $payload['error'];
        }

        if (isset($payload['checked_from']) && $payload['checked_from']) {
            $lines[] = 'Izvor provjere: ' . $payload['checked_from'];
        }

        return implode(PHP_EOL, $lines);
    }


    /**
     * @return array
     */
    private function getMailConfig(): array
    {
        return [
            'engine'        => $this->getStoreSetting('config_mail_engine', 'mail'),
            'parameter'     => $this->getStoreSetting('config_mail_parameter', ''),
            'smtp_hostname' => $this->getStoreSetting('config_mail_smtp_hostname', ''),
            'smtp_username' => $this->getStoreSetting('config_mail_smtp_username', ''),
            'smtp_password' => $this->getStoreSetting('config_mail_smtp_password', ''),
            'smtp_port'     => $this->getStoreSetting('config_mail_smtp_port', 25),
            'smtp_timeout'  => $this->getStoreSetting('config_mail_smtp_timeout', 5),
            'from'          => $this->getStoreSetting('config_email', ''),
            'sender'        => html_entity_decode($this->getStoreSetting('config_name', 'Chipoteka'), ENT_QUOTES, 'UTF-8'),
        ];
    }


    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getStoreSetting(string $key, $default = null)
    {
        $setting = Setting::query()
                          ->where('store_id', 0)
                          ->where('key', $key)
                          ->orderByDesc('setting_id')
                          ->first();

        if ($setting && $setting->value !== null && $setting->value !== '') {
            return $setting->value;
        }

        return $default;
    }


    /**
     * @return string
     */
    private function resolveApplicationSource(): string
    {
        if (defined('HTTP_CATALOG') && HTTP_CATALOG) {
            return HTTP_CATALOG;
        }

        if (defined('HTTP_SERVER') && HTTP_SERVER) {
            return HTTP_SERVER;
        }

        return 'CLI';
    }


    /**
     * @param string $url
     * @param int    $connectTimeoutMs
     * @param int    $timeoutSeconds
     *
     * @return array
     */
    private function performGetRequest(string $url, int $connectTimeoutMs = 1000, int $timeoutSeconds = 0): array
    {
        $ch = curl_init($this->base_url . $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeoutMs);

        if ($timeoutSeconds > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
        }

        $response = curl_exec($ch);
        $result   = [
            'response'  => $response,
            'error'     => curl_error($ch),
            'http_code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE),
        ];

        curl_close($ch);

        return $result;
    }


    /**
     * @return string
     */
    private function resolveMonitorUrl(): string
    {
        $endPoints = LuceedEndPoints::get($this->env);

        return $endPoints[self::MONITOR_ENDPOINT_KEY] ?? 'skladista/lista';
    }


    /**
     * @param string $method
     * @param string $url
     * @param mixed  $response
     * @param int    $httpCode
     * @param string $error
     *
     * @return array
     */
    private function recordAvailabilityProbe(string $method, string $url, $response, int $httpCode = 0, string $error = ''): array
    {
        $checkedAt    = date('Y-m-d H:i:s');
        $isAvailable  = $this->isAvailableResponse($response, $httpCode, $error);
        $previousState = $this->getAvailabilityState() ?: [];
        $previousStatus = $previousState['status'] ?? 'up';
        $notification = null;
        $sentNotification = null;

        $payload = [
            'status'           => $isAvailable ? 'up' : 'down',
            'checked_at'       => $checkedAt,
            'state_changed_at' => $checkedAt,
            'method'           => $method,
            'url'              => $url,
            'http_code'        => $httpCode,
            'error'            => $error,
            'checked_from'     => $this->resolveApplicationSource(),
            'down_since'       => null,
            'down_notified_at' => null,
            'last_success_at'  => $isAvailable ? $checkedAt : ($previousState['last_success_at'] ?? null),
            'recovery_notification_pending' => false,
            'last_down_since'              => $previousState['last_down_since'] ?? null,
            'last_down_notified_at'        => $previousState['last_down_notified_at'] ?? null,
        ];

        if ($isAvailable) {
            if ($previousStatus === 'down' && ! empty($previousState['down_notified_at'])) {
                $notification = 'up';
                $payload['recovery_notification_pending'] = true;
                $payload['last_down_since']               = $previousState['down_since'] ?? null;
                $payload['last_down_notified_at']         = $previousState['down_notified_at'] ?? null;
            } elseif ($previousStatus === 'up' && ! empty($previousState['recovery_notification_pending'])) {
                $notification = 'up';
                $payload['recovery_notification_pending'] = true;
            } else {
                $payload['state_changed_at'] = $previousState['state_changed_at'] ?? $checkedAt;
            }
        } else {
            $payload['down_since'] = ($previousStatus === 'down' && ! empty($previousState['down_since']))
                ? $previousState['down_since']
                : $checkedAt;

            $payload['state_changed_at'] = ($previousStatus === 'down')
                ? ($previousState['state_changed_at'] ?? $payload['down_since'])
                : $checkedAt;

            $payload['down_notified_at'] = ($previousStatus === 'down' && ! empty($previousState['down_notified_at']))
                ? $previousState['down_notified_at']
                : null;

            if ( ! $payload['down_notified_at'] && $this->hasUnavailableThresholdElapsed($payload['down_since'], $checkedAt)) {
                $notification = 'down';
            }
        }

        if ($notification) {
            try {
                $notificationPayload = $payload;

                if ($notification === 'down') {
                    $notificationPayload['down_notified_at'] = $checkedAt;
                }

                $this->sendAvailabilityNotification($notificationPayload, $previousState);

                if ($notification === 'down') {
                    $payload['down_notified_at'] = $checkedAt;
                }

                if ($notification === 'up') {
                    $payload['recovery_notification_pending'] = false;
                }

                $sentNotification = $notification;
            } catch (\Throwable $exception) {
                Log::store($exception->getMessage(), 'luceed_health_monitor_error');
            }
        }

        $this->saveAvailabilityState($payload);
        $payload['notification'] = $sentNotification;

        return $payload;
    }


    /**
     * @param string|null $downSince
     * @param string      $checkedAt
     *
     * @return bool
     */
    private function hasUnavailableThresholdElapsed(?string $downSince, string $checkedAt): bool
    {
        if ( ! $downSince) {
            return false;
        }

        return (strtotime($checkedAt) - strtotime($downSince)) >= self::MONITOR_MIN_DOWNTIME_SECONDS;
    }


    /**
     * @param string|null $downSince
     * @param string|null $upAt
     *
     * @return string|null
     */
    private function resolveDowntimeDuration(?string $downSince, ?string $upAt): ?string
    {
        if ( ! $downSince || ! $upAt) {
            return null;
        }

        $seconds = strtotime($upAt) - strtotime($downSince);

        if ($seconds < 0) {
            return null;
        }

        $minutes = floor($seconds / 60);
        $rest    = $seconds % 60;

        if ($minutes > 0) {
            return $minutes . ' min ' . $rest . ' s';
        }

        return $seconds . ' s';
    }
}
