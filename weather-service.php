<?php
declare(strict_types=1);

/**
 * Server-side QWeather client. Credentials stay outside the repository; set
 * QWEATHER_PRIVATE_KEY_PATH to the Ed25519 private-key file on the server.
 */
function weather_config(): array
{
    $config = array(
        'host' => '',
        'developer_id' => '',
        'project_id' => '',
        'credential_id' => '',
        'private_key_path' => getenv('QWEATHER_PRIVATE_KEY_PATH') ?: __DIR__ . '/weather-private.pem',
        'cache_dir' => getenv('QWEATHER_CACHE_DIR') ?: __DIR__ . '/weather-cache',
        'usage_path' => getenv('QWEATHER_USAGE_PATH') ?: __DIR__ . '/weather-usage.json',
        'quota_limit' => 48000,
        'locations' => array(
            'boy' => array(
                'name' => '未设置',
                'display' => '未设置',
                'id' => '',
                'lat' => '',
                'lon' => '',
            ),
            'girl' => array(
                'name' => '未设置',
                'display' => '未设置',
                'id' => '',
                'lat' => '',
                'lon' => '',
            ),
        ),
    );
    $localConfig = __DIR__ . '/weather-config.local.php';
    if (is_file($localConfig)) {
        $config = array_replace_recursive($config, require $localConfig);
    }

    return $config;
}

function weather_cache_ttl(string $kind): int
{
    return array(
        'now' => 300,
        'hourly' => 1800,
        'daily' => 3600,
        'warning' => 600,
        'air' => 1800,
        'minutely' => 600,
        'indices' => 21600,
    )[$kind] ?? 600;
}

function weather_effective_ttl(string $kind): int
{
    $ttl = weather_cache_ttl($kind);
    if ($kind !== 'daily' && $kind !== 'indices') {
        return $ttl;
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai'));
    $untilMidnight = $now->modify('tomorrow')->setTime(0, 0)->getTimestamp() - $now->getTimestamp();
    return max(1, min($ttl, $untilMidnight));
}

function weather_base64url(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function weather_jwt(array $config): string
{
    if ($config['host'] === '' || $config['project_id'] === '' || $config['credential_id'] === '') {
        throw new RuntimeException('QWeather credentials are not configured.');
    }

    $privateKey = @file_get_contents($config['private_key_path']);
    if ($privateKey === false || trim($privateKey) === '') {
        throw new RuntimeException('QWeather private key is not configured.');
    }
    $key = openssl_pkey_get_private($privateKey);
    if ($key === false) {
        throw new RuntimeException('QWeather private key is invalid.');
    }

    $issuedAt = time() - 30;
    $header = weather_base64url((string) json_encode(array(
        'alg' => 'EdDSA',
        'kid' => $config['credential_id'],
    ), JSON_UNESCAPED_SLASHES));
    $payload = weather_base64url((string) json_encode(array(
        'iss' => $config['developer_id'],
        'sub' => $config['project_id'],
        'iat' => $issuedAt,
        'exp' => $issuedAt + 900,
    ), JSON_UNESCAPED_SLASHES));
    $signingInput = $header . '.' . $payload;
    $signature = '';
    if (!openssl_sign($signingInput, $signature, $key, 0)) {
        throw new RuntimeException('QWeather JWT signing failed.');
    }
    return $signingInput . '.' . weather_base64url($signature);
}

function weather_build_request(string $kind, array $location): array
{
    $config = weather_config();
    $jwt = weather_jwt($config);
    $paths = array(
        'now' => '/v7/weather/now?location=' . rawurlencode($location['id']),
        'hourly' => '/v7/weather/24h?location=' . rawurlencode($location['id']),
        'daily' => '/v7/weather/3d?location=' . rawurlencode($location['id']),
        'warning' => '/v7/warning/now?location=' . rawurlencode($location['id']),
        'air' => '/airquality/v1/current/' . rawurlencode($location['lat']) . '/' . rawurlencode($location['lon']),
        'minutely' => '/v7/minutely/5m?location=' . rawurlencode(round((float) $location['lon'], 2) . ',' . round((float) $location['lat'], 2)),
        'indices' => '/v7/indices/1d?type=3,9,13&location=' . rawurlencode($location['id']),
    );
    if (!isset($paths[$kind])) {
        throw new InvalidArgumentException('Unknown weather endpoint.');
    }
    return array(
        'url' => $config['host'] . $paths[$kind],
        'headers' => array('Authorization: Bearer ' . $jwt, 'Accept-Encoding: gzip'),
    );
}

function weather_http(array $request): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required for QWeather.');
    }
    $handle = curl_init($request['url']);
    curl_setopt_array($handle, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $request['headers'],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ));
    $caInfo = getenv('QWEATHER_CAINFO');
    if ($caInfo !== false && is_file($caInfo)) {
        curl_setopt($handle, CURLOPT_CAINFO, $caInfo);
    }
    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);
    if ($body === false) {
        throw new RuntimeException('QWeather request failed: ' . $error);
    }
    return array('status' => $status, 'body' => $body);
}

function weather_json_response(array $response): array
{
    if ($response['status'] < 200 || $response['status'] >= 300) {
        throw new RuntimeException('QWeather returned HTTP ' . $response['status'] . '.');
    }
    $data = json_decode($response['body'], true);
    if (!is_array($data) || (isset($data['code']) && $data['code'] !== '200')) {
        throw new RuntimeException('QWeather returned invalid data.');
    }
    return $data;
}

function weather_cache_file(string $cacheKey, string $cacheDir): string
{
    return rtrim($cacheDir, '\\/') . DIRECTORY_SEPARATOR . hash('sha256', $cacheKey) . '.json';
}

function weather_cached_request(string $cacheKey, int $ttl, callable $fetch, string $cacheDir): array
{
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0700, true) && !is_dir($cacheDir)) {
        throw new RuntimeException('Weather cache directory is not writable.');
    }
    $cacheFile = weather_cache_file($cacheKey, $cacheDir);
    $lock = fopen($cacheFile . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Weather cache lock could not be acquired.');
    }
    $read = static function () use ($cacheFile): ?array {
        if (!is_file($cacheFile)) {
            return null;
        }
        $cached = json_decode((string) file_get_contents($cacheFile), true);
        return is_array($cached) && isset($cached['fetched_at'], $cached['data']) ? $cached : null;
    };
    $cached = $read();
    if ($cached !== null && (time() - (int) $cached['fetched_at']) < $ttl) {
        flock($lock, LOCK_UN);
        fclose($lock);
        return $cached['data'];
    }
    try {
        $response = $fetch();
        $data = weather_json_response($response);
        file_put_contents($cacheFile, json_encode(array('fetched_at' => time(), 'data' => $data), JSON_UNESCAPED_UNICODE), LOCK_EX);
        flock($lock, LOCK_UN);
        fclose($lock);
        return $data;
    } catch (Throwable $exception) {
        $stale = $read();
        flock($lock, LOCK_UN);
        fclose($lock);
        if ($stale !== null) {
            return $stale['data'];
        }
        throw $exception;
    }
}

function weather_increment_usage(array $config): void
{
    $month = weather_month();
    $usage = is_file($config['usage_path']) ? json_decode((string) file_get_contents($config['usage_path']), true) : array();
    if (!is_array($usage) || ($usage['month'] ?? '') !== $month) {
        $usage = array('month' => $month, 'count' => 0);
    }
    $usage['count'] = (int) $usage['count'] + 1;
    file_put_contents($config['usage_path'], json_encode($usage), LOCK_EX);
}

function weather_month(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m');
}

function weather_record_usage(): void
{
    weather_increment_usage(weather_config());
}

function weather_quota_available(): bool
{
    $config = weather_config();
    if (!is_file($config['usage_path'])) {
        return true;
    }
    $usage = json_decode((string) file_get_contents($config['usage_path']), true);
    if (!is_array($usage) || ($usage['month'] ?? '') !== weather_month()) {
        return true;
    }
    return (int) ($usage['count'] ?? 0) < (int) $config['quota_limit'];
}

function weather_with_quota(callable $fetch, ?array $config = null): array
{
    $config ??= weather_config();
    $lock = fopen($config['usage_path'] . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('QWeather usage lock is not writable.');
    }
    try {
        $month = weather_month();
        $usage = is_file($config['usage_path']) ? json_decode((string) file_get_contents($config['usage_path']), true) : array();
        if (is_array($usage) && ($usage['month'] ?? '') === $month && (int) ($usage['count'] ?? 0) >= (int) $config['quota_limit']) {
            throw new RuntimeException('Monthly QWeather safety limit reached.');
        }
        $response = $fetch();
        if (($response['status'] ?? 0) >= 200 && ($response['status'] ?? 0) < 300) {
            weather_increment_usage($config);
        }
        return $response;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// Select only the data a page needs, while sharing the same cache keys.
function weather_get_all(array $kinds = array('now', 'hourly', 'daily', 'warning', 'air', 'minutely', 'indices')): array
{
    $config = weather_config();
    $result = array();
    foreach ($config['locations'] as $key => $location) {
        $result[$key] = array('location' => $location);
        foreach ($kinds as $kind) {
            try {
                $cacheKey = 'weather:' . $key . ':' . $kind;
                $result[$key][$kind] = weather_cached_request($cacheKey, weather_effective_ttl($kind), static function () use ($kind, $location): array {
                    return weather_with_quota(static fn (): array => weather_http(weather_build_request($kind, $location)));
                }, $config['cache_dir']);
            } catch (Throwable $exception) {
                $result[$key][$kind] = null;
            }
        }
    }
    return $result;
}
