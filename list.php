<?php
include_once 'head.php';
$reslist = mysqli_query($connect, "select * from lovelist order by id desc");
$row_count = mysqli_num_rows($reslist);
$list_rows = array();
$completed_count = 0;
while ($list = mysqli_fetch_array($reslist)) {
    $list_rows[] = $list;
    if ((string) ($list['icon'] ?? '') === '1') {
        $completed_count++;
    }
}
$progress_percent = $row_count > 0 ? (int) round($completed_count / $row_count * 100) : 0;
?>

<head>
    <meta charset="utf-8" />
    <title><?php echo $text['title'] ?> — 恋爱事件</title>
</head>
<body>
    <div id="pjax-container">
        <div class="central">
            <div class="title"><h1>总有些惊奇的际遇 比方说当我遇见你</h1></div>
            <div class="row central central-800">
                <div class="card col-lg-12 col-md-12 col-sm-12 col-sm-x-12">
                    <section class="list_texts list-card <?php if ($text['Animation'] == "1") { ?>animated fadeInUp delay-03s<?php } ?>" data-list-card aria-labelledby="list-card-title">
                        <header class="list-card__header">
                            <div class="list-card__intro">
                                <span class="list-card__eyebrow">恋爱清单</span>
                                <h2 id="list-card-title">把想做的事，一件件完成</h2>
                                <div class="list-card__progress" data-list-progress
                                    data-completed="<?php echo $completed_count; ?>"
                                    data-total="<?php echo $row_count; ?>"
                                    role="progressbar" aria-label="清单完成进度"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $progress_percent; ?>"
                                    aria-valuetext="已完成 <?php echo $completed_count; ?> 项，共 <?php echo $row_count; ?> 项"
                                    style="--list-progress: <?php echo $progress_percent; ?>%;">
                                    <div class="list-card__progress-meta">
                                        <span>完成进度</span>
                                        <strong><?php echo $completed_count; ?> / <?php echo $row_count; ?> 已完成</strong>
                                    </div>
                                    <div class="list-card__progress-track" aria-hidden="true"><span></span></div>
                                </div>
                            </div>
                            <div class="list-filters" role="radiogroup" aria-label="筛选清单事项">
                                <button type="button" class="list-filter" data-list-filter="all" role="radio" aria-checked="true" tabindex="0">全部</button>
                                <button type="button" class="list-filter" data-list-filter="completed" role="radio" aria-checked="false" tabindex="-1">已完成</button>
                                <button type="button" class="list-filter" data-list-filter="pending" role="radio" aria-checked="false" tabindex="-1">未完成</button>
                            </div>
                        </header>
                        <ul class="list-items">
                            <?php foreach ($list_rows as $list):
                                $status = ((string) $list['icon'] === '1') ? 'completed' : 'pending';
                                $eventName = htmlspecialchars((string) $list['eventname'], ENT_QUOTES, 'UTF-8', false);
                                $imageUrl = trim((string) ($list['imgurl'] ?? ''));
                                $hasImage = $imageUrl !== '' && $imageUrl !== '0';
                                $attachmentId = 'list-attachment-' . (int) $list['id'];
                                ?>
                                <li class="list-item" data-list-item data-status="<?php echo $status; ?>">
                                    <?php if ($hasImage): ?>
                                    <button type="button" class="list-item__summary" data-list-expand
                                        aria-label="展开“<?php echo $eventName; ?>”的附件"
                                        aria-expanded="false" aria-controls="<?php echo $attachmentId; ?>">
                                    <?php else: ?>
                                    <div class="list-item__summary">
                                    <?php endif; ?>
                                        <span class="list-item__status list-item__status--<?php echo $status; ?>" role="img"
                                            aria-label="<?php echo $status === 'completed' ? '已完成' : '未完成'; ?>">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <?php if ($status === 'completed'): ?>
                                                    <path d="m6 12 4 4 8-8" />
                                                <?php else: ?>
                                                    <circle cx="12" cy="12" r="7" />
                                                <?php endif; ?>
                                            </svg>
                                        </span>
                                        <span class="list-item__title"><?php echo $eventName; ?></span>
                                        <?php if ($hasImage): ?>
                                            <svg class="list-item__chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" /></svg>
                                        <?php endif; ?>
                                        <?php if ($hasImage): ?>
                                    </button>
                                        <?php else: ?>
                                    </div>
                                        <?php endif; ?>
                                    <?php if ($hasImage): ?>
                                    <div class="list-item__attachment" id="<?php echo $attachmentId; ?>" hidden>
                                        <div class="list-item__attachment-content">
                                        <img class="spotlight" data-list-image="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8', false); ?>"
                                            alt="<?php echo $eventName; ?>" loading="lazy" decoding="async">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="list-empty" data-list-empty role="status"<?php if ($row_count > 0): ?> hidden<?php endif; ?>><?php echo $row_count > 0 ? '没有符合条件的事项' : '还没有清单事项，先写下想和对方一起完成的事吧。'; ?></p>
                    </section>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var card = document.querySelector('[data-list-card]');
                if (!card) return;
                var filters = Array.prototype.slice.call(card.querySelectorAll('[data-list-filter]'));
                var filterGroup = card.querySelector('.list-filters');
                var items = Array.prototype.slice.call(card.querySelectorAll('[data-list-item]'));
                var emptyState = card.querySelector('[data-list-empty]');
                var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
                var currentFilter = 'all';
                var emptyMessages = { all: '还没有清单事项，先写下想和对方一起完成的事吧。', completed: '还没有已完成的事项，期待我们一起点亮。', pending: '所有事项都已完成，一起写下新的心愿吧。' };

                function setAttachmentExpanded(item, expanded, instant) {
                    var toggle = item.querySelector('[data-list-expand]');
                    if (!toggle) return;
                    var attachment = item.querySelector('.list-item__attachment');
                    window.clearTimeout(attachment.closeTimer);
                    toggle.setAttribute('aria-expanded', String(expanded));
                    toggle.setAttribute('aria-label', toggle.getAttribute('aria-label').replace(/^(展开|收起)/, expanded ? '收起' : '展开'));
                    attachment.setAttribute('aria-hidden', String(!expanded));
                    if (expanded) {
                        attachment.removeAttribute('inert');
                        var image = attachment.querySelector('[data-list-image]');
                        if (!image.getAttribute('src')) image.src = image.dataset.listImage;
                        if (attachment.hidden) {
                            attachment.hidden = false;
                            // Establish the collapsed grid before transitioning to its natural height.
                            void attachment.offsetHeight;
                        }
                        attachment.classList.add('is-expanded');
                    } else {
                        attachment.setAttribute('inert', '');
                        attachment.classList.remove('is-expanded');
                        if (instant || reducedMotion.matches || attachment.hidden) attachment.hidden = true;
                        else attachment.closeTimer = window.setTimeout(function () {
                            attachment.hidden = true;
                        }, 300);
                    }
                }

                function closeAttachment(item, instant) {
                    setAttachmentExpanded(item, false, instant);
                }

                function applyFilter(filter) {
                    var visibleCount = 0;
                    var selectedButton;
                    var changed = currentFilter !== filter;
                    currentFilter = filter;
                    items.forEach(function (item) {
                        window.clearTimeout(item.filterTimer);
                        item.classList.remove('is-filter-enter');
                        var visible = filter === 'all' || item.dataset.status === filter;
                        item.hidden = !visible;
                        if (visible) {
                            visibleCount += 1;
                            if (changed && !reducedMotion.matches) {
                                item.style.setProperty('--list-filter-delay', Math.min(visibleCount - 1, 4) * 25 + 'ms');
                                void item.offsetWidth;
                                item.classList.add('is-filter-enter');
                                item.filterTimer = window.setTimeout(function () {
                                    item.classList.remove('is-filter-enter');
                                }, 380);
                            }
                        }
                        else closeAttachment(item, true);
                    });
                    filters.forEach(function (button) {
                        var selected = button.dataset.listFilter === filter;
                        button.setAttribute('aria-checked', String(selected));
                        button.tabIndex = selected ? 0 : -1;
                        if (selected) selectedButton = button;
                    });
                    if (selectedButton) moveFilterIndicator(selectedButton);
                    emptyState.textContent = emptyMessages[filter];
                    emptyState.hidden = visibleCount !== 0;
                }

                function moveFilterIndicator(button) {
                    if (!filterGroup || !button) return;
                    var groupRect = filterGroup.getBoundingClientRect();
                    var buttonRect = button.getBoundingClientRect();
                    filterGroup.style.setProperty('--filter-indicator-x', (buttonRect.left - groupRect.left) + 'px');
                    filterGroup.style.setProperty('--filter-indicator-width', buttonRect.width + 'px');
                }

                filters.forEach(function (button, index) {
                    button.addEventListener('click', function () { applyFilter(button.dataset.listFilter); });
                    button.addEventListener('keydown', function (event) {
                        var next;
                        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (index + 1) % filters.length;
                        else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (index + filters.length - 1) % filters.length;
                        else if (event.key === 'Home') next = 0;
                        else if (event.key === 'End') next = filters.length - 1;
                        else return;
                        event.preventDefault();
                        filters[next].focus();
                        applyFilter(filters[next].dataset.listFilter);
                    });
                });

                card.querySelectorAll('[data-list-expand]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var expanded = button.getAttribute('aria-expanded') === 'true';
                        var selectedItem = button.closest('[data-list-item]');
                        items.forEach(function (item) {
                            if (item !== selectedItem) closeAttachment(item, false);
                        });
                        setAttachmentExpanded(selectedItem, !expanded, false);
                    });
                });
                window.addEventListener('resize', function () {
                    var active = card.querySelector('[data-list-filter][aria-checked="true"]');
                    moveFilterIndicator(active);
                });
                applyFilter('all');
            }());
        </script>
    </div>
    <?php include_once 'footer.php'; ?>
</body>
</html>
