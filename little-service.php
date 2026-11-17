<?php
declare(strict_types=1);

function little_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function little_safe_url(string $value): string
{
    $value = html_entity_decode(trim($value), ENT_QUOTES, 'UTF-8');
    if ($value === '' || preg_match('/[\x00-\x20\x7f<>"\x27\\\\]/u', $value) ||
        preg_match('~(?:^|/)\.\.(?:/|$)~', rawurldecode($value)) || strncmp($value, '//', 2) === 0) {
        return '';
    }
    if (preg_match('~^https?://~i', $value)) {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }
    return preg_match('~^(?:/?images/|/?Style/|/[^/])~', $value) ? $value : '';
}

/** Extract legacy images and sanitize rich text without executing stored HTML. */
function little_parts(string $html): array
{
    $images = [];
    $html = preg_replace_callback('~!\[[^\]]*\]\(([^\s)]+)\)~', static function (array $match): string {
        $url = little_safe_url($match[1]);
        return $url !== '' ? '<img src="' . little_escape($url) . '">' : '';
    }, $html);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $allowed = ['p','div','span','h1','h2','h3','h4','h5','h6','strong','b','em','i','s','del','u','br','hr','ul','ol','li','blockquote','quote','pre','code','a','center','video','source'];
    $clean = function (DOMNode $node) use (&$clean, &$images, $allowed): string {
        if ($node instanceof DOMText) return little_escape($node->nodeValue ?? '');
        if (!$node instanceof DOMElement) return '';
        $tag = strtolower($node->tagName);
        if ($tag === 'img') {
            $url = little_safe_url($node->getAttribute('src') ?: $node->getAttribute('data-funlazy'));
            if ($url !== '') $images[] = $url;
            return '';
        }
        if (in_array($tag, ['script','style','iframe','object','svg','math','template'], true)) return '';
        $content = '';
        foreach ($node->childNodes as $child) $content .= $clean($child);
        if (!in_array($tag, $allowed, true)) return $content;
        $attributes = '';
        if ($tag === 'a') {
            $url = little_safe_url($node->getAttribute('href'));
            if ($url !== '') $attributes = ' href="' . little_escape($url) . '" target="_blank" rel="noopener noreferrer"';
        }
        if ($tag === 'video' || $tag === 'source') {
            $url = little_safe_url($node->getAttribute('src'));
            if ($url !== '') $attributes = ' src="' . little_escape($url) . '"';
            if ($tag === 'video') $attributes .= ' controls preload="none"';
        }
        if (in_array($tag, ['br','hr','source'], true)) return '<' . $tag . $attributes . '>';
        return '<' . $tag . $attributes . '>' . $content . '</' . $tag . '>';
    };
    $body = '';
    $root = $doc->getElementsByTagName('body')->item(0);
    if ($root) foreach ($root->childNodes as $node) $body .= $clean($node);
    return ['body'=>$body, 'images'=>array_values(array_unique($images))];
}

/** A NULL image list means an old record; an explicit [] must stay empty. */
function little_record(array $row, array $settings): array
{
    $parts = little_parts((string) ($row['articletext'] ?? ''));
    $stored = isset($row['articleimages']) ? json_decode((string) $row['articleimages'], true) : null;
    $images = is_array($stored) ? $stored : $parts['images'];
    $images = array_values(array_filter(array_map(static fn($url): string => is_string($url) ? little_safe_url($url) : '', $images)));
    $name = html_entity_decode((string) ($row['articlename'] ?? ''), ENT_QUOTES, 'UTF-8');
    $avatar = 'Style/img/about.png';
    foreach (['boy','girl'] as $person) {
        if ($name === html_entity_decode((string) ($settings[$person] ?? ''), ENT_QUOTES, 'UTF-8') && !empty($settings[$person . 'img'])) {
            $avatar = 'https://q1.qlogo.cn/g?b=qq&nk=' . rawurlencode((string) $settings[$person . 'img']) . '&s=100';
        }
    }
    try {
        $rawDate = (string) ($row['articletime'] ?? '');
        $date = $rawDate !== '' ? new DateTimeImmutable($rawDate, new DateTimeZone('Asia/Shanghai')) : null;
    } catch (Exception $error) {
        $date = null;
    }
    $body = $parts['body'];
    return [
        'id'=>(int) $row['id'], 'title'=>html_entity_decode((string) ($row['articletitle'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'author'=>$name, 'avatar'=>$avatar, 'images'=>$images, 'body'=>$body,
        'excerpt'=>trim(html_entity_decode(strip_tags(preg_replace('~</(?:p|div|h[1-6]|li)>~', ' ', $body)), ENT_QUOTES, 'UTF-8')),
        'date'=>$date ? $date->format('Y年m月d日 H:i') : (string) ($row['articletime'] ?? ''),
        'iso'=>$date ? $date->format('Y-m-d\TH:i:s') : '', 'year'=>$date ? $date->format('Y') : '往日',
        'day'=>$date ? $date->format('d') : '—', 'month'=>$date ? $date->format('m') : '—',
        'cursor'=>[(string) ($row['articletime'] ?? ''), (int) $row['id']],
    ];
}

function little_validate(array $input, array $names): array
{
    foreach (['articletitle','articletext','articlename','articletime','articleimages'] as $key) {
        if (!isset($input[$key]) || !is_string($input[$key]) || !preg_match('//u', $input[$key])) throw new InvalidArgumentException('请完整填写点滴信息。');
    }
    $title = trim($input['articletitle']); $body = trim($input['articletext']); $author = trim($input['articlename']);
    if ($title === '' || preg_match_all('/./us', little_escape($title)) > 100) throw new InvalidArgumentException('标题不能为空，且不能超过100个字符。');
    if (preg_match_all('/./us', $body) > 2000) throw new InvalidArgumentException('正文不能超过2000个字符。');
    if (preg_match('~<\s*img\b|!\[[^\]]*\]\(~i', $body)) throw new InvalidArgumentException('请用正文下方的“选择上传图片”添加照片。');
    if (!in_array($author, $names, true)) throw new InvalidArgumentException('请选择有效发布者。');
    $dateString = str_replace('T', ' ', $input['articletime']);
    if (strlen($dateString) === 16) $dateString .= ':00';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dateString, new DateTimeZone('Asia/Shanghai'));
    if (!$date || $date->format('Y-m-d H:i:s') !== $dateString || $dateString < '1900-01-01') throw new InvalidArgumentException('请选择有效发布日期。');
    $images = json_decode($input['articleimages'], true);
    if (!is_array($images) || array_values($images) !== $images) throw new InvalidArgumentException('图片列表无效，请刷新后重试。');
    foreach ($images as $url) if (!is_string($url) || little_safe_url($url) === '') throw new InvalidArgumentException('图片地址无效，请重新上传。');
    if ($body === '' && count($images) === 0) throw new InvalidArgumentException('请填写正文或至少添加一张照片。');
    return ['title'=>little_escape($title), 'body'=>$body, 'author'=>$author, 'date'=>$dateString, 'images'=>array_values($images)];
}
