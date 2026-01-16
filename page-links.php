<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$rawText = isset($this->text) ? (string)$this->text : '';
$hasLinksTag = stripos($rawText, '<links') !== false;

$options = Typecho_Widget::widget('Widget_Options');
$linksActivated = isset($options->plugins['activated']['Links']);

$linksBySort = [];
$linkQueryError = null;
if (!$hasLinksTag && $linksActivated) {
    try {
        $db = Typecho_Db::get();
        $links = $db->fetchAll(
            $db->select('lid', 'name', 'url', 'sort', 'email', 'image', 'description', 'user', 'state', 'order')
                ->from('table.links')
                ->where('state = ?', 1)
                ->order('order', Typecho_Db::SORT_ASC)
        );

        foreach ($links as $link) {
            $sortKey = isset($link['sort']) ? trim((string)$link['sort']) : '';
            $sortLabel = $sortKey !== '' ? $sortKey : _t('未分类');
            if (!isset($linksBySort[$sortLabel])) {
                $linksBySort[$sortLabel] = [];
            }
            $linksBySort[$sortLabel][] = $link;
        }
    } catch (Exception $e) {
        $linkQueryError = $e->getMessage();
        $linksBySort = [];
    }
}

if (!function_exists('mango_links_avatar_url')) {
    function mango_links_avatar_url($email, $size = 56) {
        $email = trim((string)$email);
        if ($email === '') return '';
        $gravatarPrefix = empty(Helper::options()->cnavatar) ? 'https://cravatar.cn/avatar/' : Helper::options()->cnavatar;
        $hash = md5(strtolower($email));
        return $gravatarPrefix . $hash . '?s=' . (int)$size . '&d=mm&r=g';
    }
}
$this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title">
        <h1><?php $this->title(); ?></h1>
    </div>
    <div class="post_container">
        <article class="wznrys">
            <?php $this->content(); ?>
        </article>

        <?php if (!$hasLinksTag): ?>
            <div class="mango-links">
                <?php if (!$linksActivated): ?>
                    <div class="mango-empty">未启用友情链接插件（Links），请先在后台启用</div>
                <?php elseif (!empty($linkQueryError)): ?>
                    <div class="mango-empty">友情链接加载失败：<?php echo htmlspecialchars((string)$linkQueryError); ?></div>
                <?php elseif (empty($linksBySort)): ?>
                    <div class="mango-empty">暂无友情链接</div>
                <?php else:foreach ($linksBySort as $sortLabel => $links): ?>
                        <section class="mango-links-group">
                            <h2 class="mango-links-title">
                                <i class="bi bi-folder-fill me-2"></i><?php echo htmlspecialchars((string)$sortLabel); ?>
                                <small><?php echo count($links); ?> 个</small>
                            </h2>
                            <div class="row g-3 mango-links-grid">
                                <?php foreach ($links as $link):
                                    $name = trim((string)($link['name'] ?? ''));
                                    $url = trim((string)($link['url'] ?? ''));
                                    $desc = trim((string)($link['description'] ?? ''));
                                    $image = trim((string)($link['image'] ?? ''));
                                    $email = trim((string)($link['email'] ?? ''));
                                    if ($url === '') {
                                        $url = '#';
                                    }

                                    if ($image === '') {
                                        $image = mango_links_avatar_url($email, 56);
                                    }
                                    if ($image === '') {
                                        $image = rtrim((string)$options->siteUrl, '/') . '/usr/plugins/Links/nopic.png';
                                    }

                                    $tooltipText = $desc !== '' ? $desc : $url;
                                    ?>
                                    <div class="col-6 col-md-6 col-xl-4">
                                        <div class="mango-link-card">
                                            <span class="mango-link-avatar">
                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($name); ?>" width="64" height="64" loading="lazy" decoding="async">
                                            </span>
                                            <span class="mango-link-main">
                                                <a class="mango-link-name-link"
                                                   href="<?php echo htmlspecialchars($url); ?>"
                                                   target="_blank"
                                                   rel="noopener"
                                                   <?php echo $url === '#' ? 'aria-disabled="true"' : ''; ?>
                                                >
                                                    <?php echo htmlspecialchars($name); ?>
                                                </a>
                                            </span>
                                            <?php if ($tooltipText !== ''): ?>
                                                <span class="mango-link-tooltip"><?php echo htmlspecialchars($tooltipText); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php $this->need('comments.php'); ?>
</div>
<?php $this->need('sidebar.php');$this->need('footer.php'); ?>