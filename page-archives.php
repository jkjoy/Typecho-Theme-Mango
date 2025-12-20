<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
/**
 * 归档页面模板
 *
 * @package custom              
 */
$db = Typecho_Db::get();
$posts = [];
try {
    $posts = $db->fetchAll(
        $db->select('cid', 'title', 'slug', 'created', 'commentsNum')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
    );
} catch (Exception $e) {
    $posts = [];
}

$totalPosts = count($posts);
$oldestCreated = $totalPosts ? (int)$posts[$totalPosts - 1]['created'] : 0;
$latestCreated = $totalPosts ? (int)$posts[0]['created'] : 0;

$groupedByYear = [];
foreach ($posts as $post) {
    $created = isset($post['created']) ? (int)$post['created'] : 0;
    $year = $created ? date('Y', $created) : _t('未知');
    if (!isset($groupedByYear[$year])) {
        $groupedByYear[$year] = [];
    }
    $groupedByYear[$year][] = $post;
}
$this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title">
        <h1><?php $this->title(); ?></h1>
    </div>
    <div class="post_container">
        <div class="mango-archives">
            <?php if (empty($groupedByYear)): ?>
                <div class="mango-empty">暂无文章可归档</div>
            <?php else: ?>
                <?php foreach ($groupedByYear as $year => $yearPosts): ?>
                    <section class="mango-archive-year">
                        <h2 class="mango-archive-year-title">
                            <i class="bi bi-calendar-heart me-2"></i><?php echo htmlspecialchars((string)$year); ?>
                            <small><?php echo count($yearPosts); ?> 篇</small>
                        </h2>
                        <ul class="mango-archive-list">
                            <?php foreach ($yearPosts as $post): ?>
                                <?php
                                $created = isset($post['created']) ? (int)$post['created'] : 0;
                                $permalink = Typecho_Router::url('post', $post, $this->options->index);
                                $commentsNum = isset($post['commentsNum']) ? max(0, (int)$post['commentsNum']) : 0;
                                ?>
                                <li class="mango-archive-item">
                                    <span class="mango-archive-date"><?php echo $created ? date('m-d', $created) : '--'; ?></span>
                                    <a class="mango-archive-link" href="<?php echo htmlspecialchars((string)$permalink); ?>">
                                        <?php echo htmlspecialchars((string)($post['title'] ?? '')); ?>
                                    </a>
                                    <span class="mango-archive-count" title="评论数">
                                        <i class="bi bi-chat-square-text me-1"></i><?php echo $commentsNum; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
