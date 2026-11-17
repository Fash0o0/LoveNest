<?php
include_once 'head.php';
require_once __DIR__ . '/weather-service.php';
require_once __DIR__ . '/weather-view.php';
$weather = weather_get_all();
$selectedPerson = ($_GET['person'] ?? '') === 'girl' ? 'girl' : 'boy';
?>
<head>
    <meta charset="utf-8" />
    <title><?php echo weather_escape($text['title']); ?> — 两个人的天气</title>
</head>
<body>
<main id="pjax-container">
    <section class="weather-page" aria-labelledby="weather-title">
        <header class="weather-hero">
            <h1 id="weather-title">两个人的天气</h1>
            <p>晴雨各一方，冷暖都放在心上。</p>
        </header>
        <div class="weather-switch-slot">
            <div class="weather-switch">
                <?php $currentProfile = weather_person($text, $selectedPerson); $nextProfile = weather_person($text, $selectedPerson === 'boy' ? 'girl' : 'boy'); ?>
                <button type="button" class="weather-switch__item" data-weather-person="<?php echo weather_escape($selectedPerson); ?>" aria-label="<?php echo weather_escape('当前为' . $currentProfile['name'] . '的天气，点击切换为' . $nextProfile['name']); ?>">
                    <?php foreach ($weather as $key => $person): $profile = weather_person($text, $key); ?>
                        <span class="weather-switch__label" data-weather-person="<?php echo weather_escape($key); ?>"<?php if ($selectedPerson !== $key): ?> hidden<?php endif; ?>>
                            <img src="<?php echo weather_escape($profile['avatar']); ?>" alt="" width="20" height="20">
                            <span><?php echo weather_escape($profile['name']); ?></span>
                        </span>
                    <?php endforeach; ?>
                </button>
            </div>
        </div>
        <div class="weather-grid">
        <?php foreach ($weather as $key => $person):
            $profile = weather_person($text, $key);
            $now = $person['now']['now'] ?? [];
            $daily = $person['daily']['daily'] ?? [];
            $hours = $person['hourly']['hourly'] ?? [];
            $rain = $person['minutely']['minutely'] ?? [];
            $airIndexes = $person['air']['indexes'] ?? [];
            $warnings = $person['warning']['warning'] ?? [];
            $indices = $person['indices']['daily'] ?? [];
        ?>
            <section class="weather-person<?php echo $selectedPerson === $key ? ' is-selected' : ''; ?>" data-weather-person="<?php echo weather_escape($key); ?>" aria-labelledby="weather-<?php echo $key; ?>">
                <header class="weather-identity weather-person__heading">
                    <img class="weather-avatar" src="<?php echo weather_escape($profile['avatar']); ?>" alt="<?php echo weather_escape($profile['name'] . '的头像'); ?>" width="52" height="52">
                    <div><h2 id="weather-<?php echo $key; ?>"><?php echo weather_escape($profile['name']); ?></h2><p><?php echo weather_escape($person['location']['display']); ?></p></div>
                    <button type="button" class="weather-person__switch" aria-label="<?php echo weather_escape('切换为' . weather_person($text, $key === 'boy' ? 'girl' : 'boy')['name'] . '的天气'); ?>"></button>
                </header>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>此刻天气</h3><?php echo weather_stamp($person['now']); ?></header>
                    <?php if ($now): ?>
                        <div class="weather-current">
                            <div class="weather-temperature"><?php echo weather_value($now, 'temp', '°'); ?></div>
                            <div class="weather-current__text"><strong><?php echo weather_value($now, 'text'); ?></strong><span>体感 <?php echo weather_value($now, 'feelsLike', '°'); ?></span><span><?php echo weather_value($daily[0] ?? [], 'tempMax', '°'); ?> / <?php echo weather_value($daily[0] ?? [], 'tempMin', '°'); ?></span></div>
                            <img class="weather-icon" src="<?php echo weather_icon_url($now['icon'] ?? '999'); ?>" alt="" width="72" height="72">
                        </div>
                        <?php echo weather_metrics($now, ['windDir' => ['风向', ''], 'windScale' => ['风力', '级'], 'windSpeed' => ['风速', ' km/h'], 'humidity' => ['湿度', '%'], 'precip' => ['近一小时降水', ' mm'], 'vis' => ['能见度', ' km'], 'pressure' => ['气压', ' hPa'], 'cloud' => ['云量', '%'], 'dew' => ['露点', '°'], 'wind360' => ['风向角', '°']]); ?>
                        <p class="weather-caption">观测时间 <?php echo weather_time($now['obsTime'] ?? null); ?></p>
                    <?php else: ?><p class="weather-empty">暂未获取当前天气，请稍后再来看看。</p><?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>天气预警</h3><?php echo weather_stamp($person['warning']); ?></header>
                    <?php if ($warnings): foreach ($warnings as $warning): ?>
                        <div class="weather-warning">
                            <h4><?php echo weather_escape($warning['title'] ?? '天气预警'); ?></h4>
                            <p><?php echo weather_escape($warning['text'] ?? ''); ?></p>
                            <?php echo weather_metrics($warning, ['sender' => ['发布单位', ''], 'typeName' => ['预警类型', ''], 'severity' => ['严重程度', ''], 'severityColor' => ['预警颜色', ''], 'status' => ['状态', '']]); ?>
                            <p class="weather-caption">发布 <?php echo weather_time($warning['pubTime'] ?? null); ?> · 生效 <?php echo weather_time($warning['startTime'] ?? null); ?> · 结束 <?php echo weather_time($warning['endTime'] ?? null); ?></p>
                        </div>
                    <?php endforeach; else: ?>
                        <p class="weather-quiet"><?php echo isset($person['warning']['warning']) ? '暂无天气预警' : '暂未获取预警信息，请留意当地气象台消息。'; ?></p>
                    <?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>未来三天</h3><?php echo weather_stamp($person['daily']); ?></header>
                    <?php if ($daily): foreach ($daily as $day): ?>
                        <details class="weather-day">
                            <summary><time datetime="<?php echo weather_escape($day['fxDate'] ?? ''); ?>"><?php echo weather_time($day['fxDate'] ?? null, 'm月d日'); ?></time><img class="weather-small-icon" src="<?php echo weather_icon_url($day['iconDay'] ?? '999'); ?>" alt="" width="28" height="28" loading="lazy"><span><?php echo weather_value($day, 'textDay'); ?><small>夜间 <?php echo weather_value($day, 'textNight'); ?></small></span><b><?php echo weather_value($day, 'tempMax', '°'); ?> / <?php echo weather_value($day, 'tempMin', '°'); ?></b></summary>
                            <div class="weather-day__details">
                                <div class="weather-day__night"><img class="weather-small-icon" src="<?php echo weather_icon_url($day['iconNight'] ?? '999'); ?>" alt="" width="28" height="28" loading="lazy"><span>夜间 <?php echo weather_value($day, 'textNight'); ?></span></div>
                                <?php echo weather_metrics($day, ['windDirDay' => ['白天风向', ''], 'windScaleDay' => ['白天风力', '级'], 'windSpeedDay' => ['白天风速', ' km/h'], 'wind360Day' => ['白天风向角', '°'], 'windDirNight' => ['夜间风向', ''], 'windScaleNight' => ['夜间风力', '级'], 'windSpeedNight' => ['夜间风速', ' km/h'], 'wind360Night' => ['夜间风向角', '°'], 'humidity' => ['湿度', '%'], 'precip' => ['降水量', ' mm'], 'pressure' => ['气压', ' hPa'], 'uvIndex' => ['紫外线指数', ''], 'vis' => ['能见度', ' km'], 'cloud' => ['云量', '%'], 'sunrise' => ['日出', ''], 'sunset' => ['日落', ''], 'moonrise' => ['月升', ''], 'moonset' => ['月落', '']]); ?>
                                <p class="weather-moon"><img class="weather-small-icon" src="<?php echo weather_icon_url($day['moonPhaseIcon'] ?? '999'); ?>" alt="" width="28" height="28" loading="lazy">月相 <?php echo weather_value($day, 'moonPhase'); ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?><p class="weather-caption">点击日期，查看昼夜风况与日月升落等详情。</p><?php else: ?><p class="weather-empty">暂未获取未来三天天气。</p><?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>未来 24 小时</h3><?php echo weather_stamp($person['hourly']); ?></header>
                    <?php if ($hours): ?>
                        <div class="weather-hourly" role="region" aria-label="逐小时天气，可左右滚动" tabindex="0">
                        <?php foreach ($hours as $hour): ?>
                            <div class="weather-hour"><time datetime="<?php echo weather_escape($hour['fxTime'] ?? ''); ?>"><?php echo weather_time($hour['fxTime'] ?? null, 'H:i'); ?></time><img class="weather-small-icon" src="<?php echo weather_icon_url($hour['icon'] ?? '999'); ?>" alt="" width="30" height="30" loading="lazy"><b><?php echo weather_value($hour, 'temp', '°'); ?></b><span><?php echo weather_value($hour, 'text'); ?></span><small>降水 <?php echo weather_value($hour, 'pop', '%'); ?></small></div>
                        <?php endforeach; ?>
                        </div>
                        <details class="weather-more"><summary>查看每小时风况、湿度与降水明细</summary>
                            <div class="weather-table-wrap" role="region" aria-label="24 小时天气明细" tabindex="0"><table class="weather-table"><caption>逐小时天气明细（北京时间）</caption><thead><tr><th scope="col">时间</th><th scope="col">天气</th><th scope="col">气温</th><th scope="col">降水概率</th><th scope="col">降水量</th><th scope="col">风向</th><th scope="col">风力</th><th scope="col">风速</th><th scope="col">风向角</th><th scope="col">湿度</th><th scope="col">气压</th><th scope="col">云量</th><th scope="col">露点</th></tr></thead><tbody>
                            <?php foreach ($hours as $hour): ?><tr><th scope="row"><?php echo weather_time($hour['fxTime'] ?? null); ?></th><?php foreach (['text' => '', 'temp' => '°', 'pop' => '%', 'precip' => ' mm', 'windDir' => '', 'windScale' => '级', 'windSpeed' => ' km/h', 'wind360' => '°', 'humidity' => '%', 'pressure' => ' hPa', 'cloud' => '%', 'dew' => '°'] as $field => $unit): ?><td><?php echo weather_value($hour, $field, $unit); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                            </tbody></table></div>
                        </details>
                    <?php else: ?><p class="weather-empty">暂未获取逐小时天气。</p><?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>未来两小时降水</h3><?php echo weather_stamp($person['minutely']); ?></header>
                    <?php if (!empty($person['minutely']['summary'])): ?><p class="weather-rain-summary"><?php echo weather_escape($person['minutely']['summary']); ?></p><?php endif; ?>
                    <?php if ($rain):
                        $maxPrecip = max(0.1, ...array_map(static fn (array $point): float => (float) ($point['precip'] ?? 0), $rain)); ?>
                        <div class="weather-rain-chart" role="img" aria-label="每五分钟降水变化，精确数值见下方降水明细">
                            <?php foreach ($rain as $point): $height = max(2, min(100, round((float) ($point['precip'] ?? 0) / $maxPrecip * 100))); ?><div class="weather-rain-chart__column"><span style="height: <?php echo $height; ?>%" title="<?php echo weather_time($point['fxTime'] ?? null, 'H:i') . ' ' . weather_value($point, 'precip', ' mm'); ?>"></span></div><?php endforeach; ?>
                        </div>
                        <div class="weather-rain-axis"><span><?php echo weather_time($rain[0]['fxTime'] ?? null, 'H:i'); ?></span><span>每 5 分钟 · 毫米</span><span><?php echo weather_time($rain[count($rain) - 1]['fxTime'] ?? null, 'H:i'); ?></span></div>
                        <details class="weather-more"><summary>查看每 5 分钟降水明细</summary><div class="weather-table-wrap" role="region" aria-label="分钟降水明细" tabindex="0"><table class="weather-table weather-table--rain"><caption>分钟降水（北京时间）</caption><thead><tr><th scope="col">时间</th><th scope="col">降水量</th><th scope="col">降水类型</th></tr></thead><tbody><?php foreach ($rain as $point): ?><tr><th scope="row"><?php echo weather_time($point['fxTime'] ?? null); ?></th><td><?php echo weather_value($point, 'precip', ' mm'); ?></td><td><?php echo weather_escape(['rain' => '雨', 'snow' => '雪'][$point['type'] ?? ''] ?? ($point['type'] ?? '—')); ?></td></tr><?php endforeach; ?></tbody></table></div></details>
                    <?php elseif (empty($person['minutely']['summary'])): ?><p class="weather-empty">暂未获取分钟降水信息。</p><?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>空气质量</h3><?php echo weather_stamp($person['air']); ?></header>
                    <?php if ($airIndexes): foreach ($airIndexes as $air): ?>
                        <div class="weather-air"><div class="weather-air__score"><strong><?php echo weather_value($air, isset($air['aqiDisplay']) ? 'aqiDisplay' : 'aqi'); ?></strong><div><b><?php echo weather_value($air, 'category'); ?></b><span><?php echo weather_value($air, 'name'); ?></span></div></div>
                            <p><?php echo weather_escape($air['health']['effect'] ?? ''); ?></p>
                            <?php if (!empty($air['primaryPollutant'])): ?><p>首要污染物：<?php echo weather_escape($air['primaryPollutant']['name'] ?? $air['primaryPollutant']['code'] ?? '—'); ?></p><?php endif; ?>
                            <?php if (!empty($air['health']['advice']['generalPopulation'])): ?><p class="weather-advice"><b>一般人群</b><?php echo weather_escape($air['health']['advice']['generalPopulation']); ?></p><?php endif; ?>
                            <?php if (!empty($air['health']['advice']['sensitivePopulation'])): ?><p class="weather-advice"><b>敏感人群</b><?php echo weather_escape($air['health']['advice']['sensitivePopulation']); ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; else: ?><p class="weather-empty">暂未获取空气质量。</p><?php endif; ?>
                    <?php if (!empty($person['air']['pollutants'])): ?>
                        <dl class="weather-pollutants"><?php foreach ($person['air']['pollutants'] as $pollutant): ?><div><dt title="<?php echo weather_escape($pollutant['fullName'] ?? ''); ?>"><?php echo weather_escape($pollutant['name'] ?? ''); ?></dt><dd><?php echo weather_value($pollutant['concentration'] ?? [], 'value'); ?><small><?php echo weather_escape($pollutant['concentration']['unit'] ?? ''); ?></small></dd></div><?php endforeach; ?></dl>
                        <details class="weather-more"><summary>污染物分项指数与参考监测站</summary>
                            <?php foreach ($person['air']['pollutants'] as $pollutant): ?><p class="weather-caption"><?php echo weather_escape($pollutant['fullName'] ?? $pollutant['name'] ?? ''); ?><?php foreach ($pollutant['subIndexes'] ?? [] as $subIndex): ?> · <?php echo weather_value($subIndex, 'code'); ?> 分项指数 <?php echo weather_value($subIndex, isset($subIndex['aqiDisplay']) ? 'aqiDisplay' : 'aqi'); ?><?php endforeach; ?></p><?php endforeach; ?>
                            <?php if (!empty($person['air']['stations'])): ?><p class="weather-caption">参考监测站：<?php echo weather_escape(implode('、', array_column($person['air']['stations'], 'name'))); ?></p><?php endif; ?>
                        </details>
                    <?php endif; ?>
                </article>

                <article class="weather-panel weather-glass">
                    <header class="weather-panel__heading"><h3>今日生活指数</h3><?php echo weather_stamp($person['indices']); ?></header>
                    <?php if ($indices): foreach ($indices as $index): ?>
                        <div class="weather-lifestyle"><div><h4><?php echo weather_escape($index['name'] ?? '生活指数'); ?></h4><span><?php echo weather_value($index, 'category'); ?></span></div><p><?php echo weather_escape($index['text'] ?? ''); ?></p><p class="weather-caption"><?php echo weather_value($index, 'date'); ?> · 等级 <?php echo weather_value($index, 'level'); ?></p></div>
                    <?php endforeach; else: ?><p class="weather-empty">暂未获取生活指数。</p><?php endif; ?>
                </article>
                <?php if (weather_sources($person) !== ''): ?><p class="weather-person__sources">数据来源：<?php echo weather_sources($person); ?></p><?php endif; ?>
            </section>
        <?php endforeach; ?>
        </div>
        <footer class="weather-attribution"><p>天气数据由 <a href="https://www.qweather.com/" target="_blank" rel="noopener">和风天气</a> 提供</p><p>天气时间均为北京时间。</p></footer>
    </section>
</main>
<?php include_once 'footer.php'; ?>
</body>
</html>
