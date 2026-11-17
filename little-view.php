<?php
declare(strict_types=1);
require_once __DIR__ . '/little-service.php';

function little_card(array $post): void
{
    $id = (int) $post['id'];
    $visible = array_slice($post['images'], 0, 9);
    $overflow = max(0, count($post['images']) - 9);
    ?>
    <article class="love-timeline__item" data-year="<?= little_escape($post['year']) ?>" id="moment-<?= $id ?>">
        <div class="love-timeline__year"><strong><?= little_escape($post['year']) ?></strong><span>OUR STORY</span></div>
        <span class="love-timeline__marker" aria-hidden="true"></span>
        <div class="love-post" data-record="<?= $id ?>">
            <header class="love-post__header">
                <div class="love-post__author"><img src="<?= little_escape($post['avatar']) ?>" alt="" loading="lazy" width="44" height="44"><div><strong><?= little_escape($post['author']) ?></strong><time datetime="<?= little_escape($post['iso']) ?>"><?= little_escape($post['date']) ?></time></div></div>
                <div class="love-post__date" aria-hidden="true"><strong><?= little_escape($post['day']) ?></strong><span><?= little_escape($post['month']) ?>月</span></div>
            </header>
            <h2><a href="page.php?id=<?= $id ?>" data-open-record="<?= $id ?>"><?= little_escape($post['title']) ?></a></h2>
            <?php if ($post['excerpt'] !== ''): ?><p class="love-post__excerpt"><?= little_escape($post['excerpt']) ?></p><?php endif; ?>
            <?php if ($visible): ?>
                <div class="love-gallery" data-count="<?= count($visible) ?>">
                    <?php foreach ($visible as $index => $url): ?>
                        <button type="button" class="love-gallery__cell" data-open-record="<?= $id ?>" data-photo-index="<?= $index ?>" aria-label="查看<?= little_escape($post['title']) ?>，第<?= $index + 1 ?>张照片<?= ($overflow && $index === 8) ? '，共'.count($post['images']).'张' : '' ?>">
                            <img src="<?= little_escape($url) ?>" alt="<?= little_escape($post['title']) ?> · 照片<?= $index + 1 ?>" loading="lazy" decoding="async" width="600" height="600">
                            <?php if ($overflow && $index === 8): ?><span class="love-gallery__more" aria-hidden="true">+<?= $overflow ?></span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <script type="application/json" class="love-post__data"><?= json_encode($post, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
        </div>
    </article>
    <?php
}

function little_dialog(): void
{
    ?>
    <dialog class="love-detail" id="love-detail" aria-labelledby="love-detail-title">
        <div class="love-detail__panel">
            <button type="button" class="love-detail__close" data-detail-close aria-label="关闭记录详情">×</button>
            <section class="love-detail__viewer" aria-label="记录照片">
                <div class="love-detail__stage"><div class="love-detail__viewport"><div class="love-detail__track"><img class="love-detail__image" alt=""></div></div><button type="button" class="love-detail__prev" aria-label="上一张照片">‹</button><button type="button" class="love-detail__next" aria-label="下一张照片">›</button><span class="love-detail__counter" aria-live="polite"></span><span class="love-detail__image-error" role="status" hidden>照片暂时无法加载，请切换或稍后重试</span></div>
                <div class="love-detail__thumbs" aria-label="选择照片"></div>
            </section>
            <section class="love-detail__content" tabindex="0" aria-label="完整正文"><header class="love-post__author"><img class="love-detail__avatar" alt="" width="44" height="44"><div><strong class="love-detail__author"></strong><time class="love-detail__date"></time></div></header><h2 id="love-detail-title"></h2><div class="love-detail__body"></div></section>
        </div>
    </dialog>
    <?php
}
