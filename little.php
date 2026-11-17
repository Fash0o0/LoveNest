<?php
include_once 'head.php';
require_once __DIR__ . '/little-data.php';
require_once __DIR__ . '/little-view.php';
$singleId = isset($littleSingleId) ? (int) $littleSingleId : 0;
$batch = little_fetch($connect, [], $singleId);
?>
<head><meta charset="utf-8"><title><?= little_escape((string) $text['title']) ?> — <?= little_escape((string) $text['card1']) ?></title></head>
<body>
<div id="pjax-container">
    <main class="central love-page" <?= $singleId ? 'data-auto-open="' . $singleId . '"' : '' ?>>
        <header class="love-page__intro"><span>OUR LITTLE MOMENTS</span><h1>点点滴滴</h1><p><?= little_escape((string) $text['deci1']) ?></p></header>
        <?php if ($singleId): ?><a class="love-page__back" href="little.php">← 回到时间轴</a><?php endif; ?>
        <div class="love-timeline" data-feed-url="little-feed.php">
            <?php foreach ($batch['rows'] as $row) little_card(little_record($row, $text)); ?>
        </div>
        <?php if (!$batch['rows']): ?><p class="love-page__empty"><?= $singleId ? '这条记录暂时找不到了。' : '故事从这里开始，期待我们的第一条记录。' ?></p><?php endif; ?>
        <div class="love-page__loading" role="status" aria-live="polite"></div>
        <?php if ($batch['hasMore']): ?><button class="love-page__load" type="button" data-cursor="<?= little_escape(json_encode($batch['cursor'])) ?>">加载更早的回忆</button><?php elseif ($batch['rows'] && !$singleId): ?><p class="love-page__end">故事从这里开始</p><?php endif; ?>
    </main>
    <?php little_dialog(); ?>
</div>
<?php include_once 'footer.php'; ?>
</body></html>
