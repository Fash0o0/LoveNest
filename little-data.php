<?php
declare(strict_types=1);

/** Read one bounded batch. No schema writes on public requests. */
function little_fetch($connection, array $cursor = [], int $singleId = 0): array
{
    $limit = 12;
    if ($singleId > 0) {
        $stmt = $connection->prepare('SELECT * FROM article WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $singleId);
        $stmt->execute();
        $result = $stmt->get_result();
    } elseif ($cursor) {
        if (count($cursor) !== 2 || !is_string($cursor[0]) || !is_int($cursor[1]) || $cursor[1] < 1 || strlen($cursor[0]) > 100) throw new InvalidArgumentException('无效的时间轴位置。');
        $stmt = $connection->prepare('SELECT * FROM article WHERE articletime < ? OR (articletime = ? AND id < ?) ORDER BY articletime DESC, id DESC LIMIT 13');
        $stmt->bind_param('ssi', $cursor[0], $cursor[0], $cursor[1]);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = mysqli_query($connection, 'SELECT * FROM article ORDER BY articletime DESC, id DESC LIMIT 13');
    }
    $rows = [];
    if ($result) while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    if (isset($stmt)) $stmt->close();
    $hasMore = count($rows) > $limit;
    $rows = array_slice($rows, 0, $limit);
    $last = $rows ? $rows[count($rows)-1] : null;
    return ['rows'=>$rows, 'hasMore'=>$hasMore, 'cursor'=>$last ? [(string) $last['articletime'], (int) $last['id']] : null];
}
