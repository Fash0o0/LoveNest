<?php
include_once 'head.php';
?>

<head>
    <meta charset="utf-8" />
    <title><?php echo $text['title'] ?></title>
</head>

<body>
    <main id="pjax-container">
        <section class="home-clock" aria-label="在一起的时间" data-clock-format="split">
            <div class="home-clock__stamp">
                <div class="home-clock__face">
                    <div class="home-clock__story">
                        <h2 class="home-clock__intro" id="span_dt_dt">朝暮与年岁并往，<br>与你行至天光。</h2>
                        <div class="home-clock__since">
                            <span class="home-clock__heart" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20.4 5.6a5.2 5.2 0 0 0-7.4 0L12 6.7l-1.1-1.1a5.2 5.2 0 0 0-7.3 7.4L12 21l8.4-8a5.2 5.2 0 0 0 0-7.4Z"/></svg></span>
                            <div><span class="home-clock__since-label" lang="en">TOGETHER SINCE</span><time datetime="<?php echo htmlspecialchars($text['startTime'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo date('Y-m-d H:i', strtotime($text['startTime'])); ?></time></div>
                        </div>
                    </div>
                    <div class="home-clock__count" role="group" aria-label="相伴时长">
                        <div class="home-clock__days"><b id="tian">—</b><span class="home-clock__unit" lang="en" aria-label="天">DAYS</span></div>
                        <div class="home-clock__detail">
                            <div><b id="shi">—</b><span lang="en" aria-label="小时">HOURS</span></div>
                            <div><b id="fen">—</b><span lang="en" aria-label="分钟">MINUTES</span></div>
                            <div><b id="miao">—</b><span lang="en" aria-label="秒">SECONDS</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php include __DIR__ . '/weather-summary.php'; ?>
    </main>

    <?php include_once 'footer.php'; ?>
</body>

</html>
