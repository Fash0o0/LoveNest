<?php
declare(strict_types=1);

// Shared presentation helpers; both pages read the same server-side cache.
function weather_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function weather_icon_url($icon): string
{
    $code = preg_match('/^\d{3,4}$/', (string) $icon) ? (string) $icon : '999';
    return 'https://icons.qweather.com/assets/icons/' . $code . '.svg';
}

function weather_value(array $data, string $field, string $unit = ''): string
{
    return isset($data[$field]) && is_scalar($data[$field]) && (string) $data[$field] !== ''
        ? weather_escape($data[$field]) . weather_escape($unit) : '—';
}

function weather_person(array $settings, string $key): array
{
    return [
        'name' => trim((string) ($settings[$key] ?? '')) ?: ($key === 'boy' ? '先生' : '小姐'),
        'avatar' => 'https://q1.qlogo.cn/g?b=qq&nk=' . rawurlencode((string) ($settings[$key . 'img'] ?? '')) . '&s=640',
    ];
}

function weather_time($value, string $format = 'm-d H:i'): string
{
    if (!$value || !is_string($value)) return '—';
    try {
        return weather_escape((new DateTimeImmutable($value, new DateTimeZone('Asia/Shanghai')))
            ->setTimezone(new DateTimeZone('Asia/Shanghai'))->format($format));
    } catch (Exception $exception) {
        return '—';
    }
}

function weather_stamp(?array $payload): string
{
    return !empty($payload['updateTime'])
        ? '<time datetime="' . weather_escape($payload['updateTime']) . '">更新于 ' . weather_time($payload['updateTime']) . '</time>' : '';
}

/** @param array<string, array{0: string, 1: string}> $fields */
function weather_metrics(array $data, array $fields): string
{
    $html = '<dl class="weather-metrics">';
    foreach ($fields as $field => [$label, $unit]) {
        $html .= '<div><dt>' . weather_escape($label) . '</dt><dd>' . weather_value($data, $field, $unit) . '</dd></div>';
    }
    return $html . '</dl>';
}

function weather_sources(array $person): string
{
    $sources = [];
    foreach (['now', 'daily', 'hourly', 'warning', 'indices', 'minutely'] as $kind) {
        foreach ($person[$kind]['refer']['sources'] ?? [] as $source) {
            if (is_string($source)) $sources[] = $source;
        }
    }
    return weather_escape(implode('、', array_unique($sources)));
}
