<?php
// Shared visitor navigation. Article details belong to the memories section.
$navigationPage = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php');
if ($navigationPage === 'page.php') {
    $navigationPage = 'little.php';
}
$navigationItems = array(
    array('index.php', '首页', '首页', '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>'),
    array('little.php', '点滴', '点点滴滴', '<path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5zM2 5h2v14H2z"/><path d="M9 8h8M9 12h8M9 16h5" fill="none" stroke="var(--nav-icon-line, white)" stroke-width="1.5" stroke-linecap="round"/>'),
    array('leaving.php', '留言', '留言祝福', '<path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.5-1.2A9 9 0 1 0 12 3z"/><g fill="var(--nav-icon-line, white)"><circle cx="8" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="16" cy="12" r="1"/></g>'),
    array('loveImg.php', '相册', '恋爱相册', '<path fill-rule="evenodd" d="M8 4h8l2 3h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3zm4 5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/>'),
    array('list.php', '清单', '恋爱列表', '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m6 8 1.5 1.5L10 7m-4 8 1.5 1.5L10 14m3-6h5m-5 7h5" fill="none" stroke="var(--nav-icon-line, white)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'),
    array('weather.php', '天气', '两个人的天气', '<path d="M7.2 18.5h10.3a3.5 3.5 0 0 0 .5-7 5.8 5.8 0 0 0-11.1-1.7A4.5 4.5 0 0 0 7.2 18.5Z"/><path d="M8 4.2v1.5m-3 0 1 1m7-2.5v1.5m-8.4 2H3.1" fill="none" stroke="var(--nav-icon-line, white)" stroke-width="1.5" stroke-linecap="round"/>'),
    array('about.php', '关于', '关于我们', '<path d="M2 4h7a3 3 0 0 1 3 3 3 3 0 0 1 3-3h7v15h-7a3 3 0 0 0-3 2 3 3 0 0 0-3-2H2z"/><path d="M12 7v11m4-10h3m-3 4h3" fill="none" stroke="var(--nav-icon-line, white)" stroke-width="1.5" stroke-linecap="round"/>'),
);
?>
<nav class="site-nav" aria-label="主导航">
    <button class="site-nav__toggle" type="button" aria-label="展开主导航" aria-expanded="true" aria-controls="site-nav-links" tabindex="-1" hidden></button>
    <div class="site-nav__links" id="site-nav-links">
    <?php foreach ($navigationItems as $navigationItem):
        $navigationActive = $navigationPage === $navigationItem[0]; ?>
        <a href="<?php echo $navigationItem[0]; ?>"
            class="site-nav__item<?php echo $navigationActive ? ' is-active' : ''; ?>"
            aria-label="<?php echo $navigationItem[2]; ?>"<?php if ($navigationActive): ?> aria-current="page"<?php endif; ?>>
            <svg class="site-nav__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?php echo $navigationItem[3]; ?></svg>
            <span class="site-nav__label"><?php echo $navigationItem[1]; ?></span>
        </a>
    <?php endforeach; ?>
    </div>
</nav>
