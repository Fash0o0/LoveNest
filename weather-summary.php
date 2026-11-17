<?php
require_once __DIR__ . '/weather-service.php';
require_once __DIR__ . '/weather-view.php';
$homeWeather = weather_get_all(['now', 'daily', 'warning']);
?>
<section class="home-weather" aria-label="两个人的天气">
    <?php foreach ($homeWeather as $key => $person):
        $profile = weather_person($text, $key);
        $now = $person['now']['now'] ?? [];
        $today = $person['daily']['daily'][0] ?? [];
        $warnings = $person['warning']['warning'] ?? [];
    ?>
        <a class="home-weather__card weather-glass" href="weather.php?person=<?php echo weather_escape($key); ?>" aria-label="<?php echo weather_escape($profile['name'] . '所在地的天气，查看两个人的详细天气'); ?>">
            <div class="weather-identity">
                <img class="weather-avatar" src="<?php echo weather_escape($profile['avatar']); ?>" alt="<?php echo weather_escape($profile['name'] . '的头像'); ?>" width="44" height="44" loading="lazy">
                <div><h2><?php echo weather_escape($profile['name']); ?></h2><p><?php echo weather_escape($person['location']['display']); ?></p></div>
                <svg class="home-weather__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
            </div>
            <?php if ($now): ?>
                <div class="home-weather__now">
                    <div class="home-weather__temperature"><?php echo weather_value($now, 'temp', '°'); ?></div>
                    <div class="home-weather__condition"><strong><?php echo weather_value($now, 'text'); ?></strong><span>最高 <?php echo weather_value($today, 'tempMax', '°'); ?> / 最低 <?php echo weather_value($today, 'tempMin', '°'); ?></span></div>
                    <img class="weather-icon" src="<?php echo weather_icon_url($now['icon'] ?? '999'); ?>" alt="" width="56" height="56" loading="lazy">
                </div>
                <div class="home-weather__footer"><span>体感 <?php echo weather_value($now, 'feelsLike', '°'); ?> · <?php echo weather_value($now, 'windDir'); ?> <?php echo weather_value($now, 'windScale', '级'); ?></span><time datetime="<?php echo weather_escape($person['now']['updateTime'] ?? ''); ?>"><?php echo weather_time($person['now']['updateTime'] ?? null); ?> 更新</time></div>
            <?php else: ?>
                <p class="weather-empty">暂未获取天气，点击查看详情。</p>
            <?php endif; ?>
            <?php if ($warnings): ?><p class="weather-alert-note"><?php echo weather_escape($warnings[0]['title'] ?? '有天气预警'); ?><?php if (count($warnings) > 1): ?> · 共 <?php echo count($warnings); ?> 条<?php endif; ?></p><?php endif; ?>
        </a>
    <?php endforeach; ?>
</section>
