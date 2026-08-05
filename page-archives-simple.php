<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章归档 · 简约版
 *
 * @package custom
 */
$posts = [];
try {
    $db = Typecho_Db::get();
    $posts = $db->fetchAll(
        $db->select('cid', 'title', 'slug', 'created', 'commentsNum', 'text')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
    );
} catch (Exception $error) {
    error_log('Mango simple archives query failed: ' . $error->getMessage());
}

$groupedByYear = [];
$totalCharacters = 0;
$oldestCreated = 0;
$countArticleCharacters = function ($content) {
    $content = preg_replace('/^<!--markdown-->/i', '', (string)$content);
    $content = preg_replace('/\[(?:\/?)(?:hplayer|music|video|audio)[^\]]*\]/i', ' ', $content);

    try {
        $content = $this->markdown($content);
    } catch (Throwable $error) {
        error_log('Mango archive word count parsing failed: ' . $error->getMessage());
    }

    $content = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', (string)$content);
    $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (!preg_match_all('/[\p{L}\p{N}]/u', $content, $matches)) {
        return 0;
    }

    return count($matches[0]);
};

foreach ($posts as $post) {
    $created = isset($post['created']) ? (int)$post['created'] : 0;
    if ($created > 0 && ($oldestCreated === 0 || $created < $oldestCreated)) {
        $oldestCreated = $created;
    }
    $totalCharacters += $countArticleCharacters($post['text'] ?? '');

    $year = $created ? date('Y', $created) : _t('未知');
    if (!isset($groupedByYear[$year])) {
        $groupedByYear[$year] = [];
    }
    $groupedByYear[$year][] = $post;
}

$totalPosts = count($posts);
$siteDays = 0;
if ($oldestCreated > 0) {
    $today = new DateTimeImmutable('today');
    $firstPostDate = (new DateTimeImmutable())->setTimestamp($oldestCreated)->setTime(0, 0);
    if ($firstPostDate <= $today) {
        $siteDays = (int)$firstPostDate->diff($today)->format('%a') + 1;
    }
}

$this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title mango-archives-simple-heading">
        <h1><?php $this->title(); ?></h1>
        <?php if ($totalPosts > 0): ?>
            <p>
                <span><i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i><?php echo number_format($totalPosts); ?> 篇文章</span>
                <span><i class="bi bi-calendar-range-fill" aria-hidden="true"></i><?php echo number_format(count($groupedByYear)); ?> 个年份</span>
                <span><i class="bi bi-file-earmark-font-fill" aria-hidden="true"></i><?php echo number_format($totalCharacters); ?> 字</span>
                <span title="从 <?php echo date('Y-m-d', $oldestCreated); ?> 开始计算"><i class="bi bi-hourglass-split" aria-hidden="true"></i>建站 <?php echo number_format($siteDays); ?> 天</span>
            </p>
        <?php endif; ?>
    </div>
    <div class="post_container mango-archives-simple-shell">
        <div class="mango-archives-simple">
            <?php if (empty($groupedByYear)): ?>
                <div class="mango-archives-simple-empty">
                    <i class="bi bi-archive" aria-hidden="true"></i>
                    <span>暂无文章可归档</span>
                </div>
            <?php else: ?>
                <?php foreach ($groupedByYear as $year => $yearPosts):
                    $yearId = 'mango-archives-simple-year-' . md5((string)$year);
                    ?>
                    <section class="mango-archives-simple-group" aria-labelledby="<?php echo $yearId; ?>">
                        <header class="mango-archives-simple-group-heading">
                            <h2 id="<?php echo $yearId; ?>"><?php echo htmlspecialchars((string)$year, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <span><?php echo count($yearPosts); ?> 篇</span>
                        </header>
                        <ul class="mango-archives-simple-list">
                            <?php foreach ($yearPosts as $post):
                                $created = isset($post['created']) ? (int)$post['created'] : 0;
                                $permalink = Typecho_Router::url('post', $post, $this->options->index);
                                $commentsNum = isset($post['commentsNum']) ? max(0, (int)$post['commentsNum']) : 0;
                                ?>
                                <li>
                                    <a class="mango-archives-simple-item" href="<?php echo htmlspecialchars((string)$permalink, ENT_QUOTES, 'UTF-8'); ?>">
                                        <time class="mango-archives-simple-date"<?php if ($created): ?> datetime="<?php echo date('Y-m-d', $created); ?>"<?php endif; ?>>
                                            <?php echo $created ? date('m-d', $created) : '--'; ?>
                                        </time>
                                        <span class="mango-archives-simple-title"><?php echo htmlspecialchars((string)($post['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="mango-archives-simple-count" title="<?php echo $commentsNum; ?> 条评论">
                                            <i class="bi bi-chat-square-text" aria-hidden="true"></i><?php echo $commentsNum; ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->need('sidebar.php');$this->need('footer.php'); ?>
