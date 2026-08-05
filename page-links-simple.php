<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 友情链接 · 简约版
 *
 * @package custom
 */
$rawText = isset($this->text) ? (string)$this->text : '';
$hasLinksTag = stripos($rawText, '<links') !== false;
$linksBySort = [];
$linksTableAvailable = true;
$totalLinks = 0;

try {
    $db = Typecho_Db::get();
    $links = $db->fetchAll(
        $db->select('lid', 'name', 'url', 'sort', 'email', 'image', 'description', 'state', 'order')
            ->from('table.links')
            ->where('state = ?', 1)
            ->order('order', Typecho_Db::SORT_ASC)
    );

    foreach ($links as $link) {
        $name = trim((string)($link['name'] ?? ''));
        $url = trim((string)($link['url'] ?? ''));
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($name === '' || $url === '' || !in_array($scheme, ['http', 'https'], true)) {
            continue;
        }

        $sort = trim((string)($link['sort'] ?? ''));
        $sortLabel = $sort !== '' ? $sort : _t('未分类');
        if (!isset($linksBySort[$sortLabel])) {
            $linksBySort[$sortLabel] = [];
        }
        $linksBySort[$sortLabel][] = $link;
        $totalLinks++;
    }
} catch (Exception $error) {
    $linksTableAvailable = false;
    $linksBySort = [];
    error_log('Mango simple links table query failed: ' . $error->getMessage());
}

if (!function_exists('mango_simple_links_avatar_url')) {
    function mango_simple_links_avatar_url($email, $size = 48)
    {
        $email = trim((string)$email);
        if ($email === '') {
            return '';
        }

        $gravatarPrefix = empty(Helper::options()->cnavatar)
            ? 'https://cravatar.cn/avatar/'
            : Helper::options()->cnavatar;

        return $gravatarPrefix . md5(strtolower($email)) . '?s=' . (int)$size . '&d=mm&r=g';
    }
}

if (!function_exists('mango_simple_links_initial')) {
    function mango_simple_links_initial($name)
    {
        $name = trim((string)$name);
        if ($name === '') {
            return '?';
        }

        if (function_exists('mb_substr')) {
            $initial = mb_substr($name, 0, 1, 'UTF-8');
        } elseif (preg_match('/^./u', $name, $matches)) {
            $initial = $matches[0];
        } else {
            $initial = substr($name, 0, 1);
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($initial, 'UTF-8')
            : strtoupper($initial);
    }
}

$this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title mango-links-simple-heading">
        <h1><?php $this->title(); ?></h1>
        <?php if ($linksTableAvailable && $totalLinks > 0): ?>
            <p>
                <span><i class="bi bi-people-fill"></i><?php echo $totalLinks; ?> 个站点</span>
                <span><i class="bi bi-folder-fill"></i><?php echo count($linksBySort); ?> 个分组</span>
            </p>
        <?php endif; ?>
    </div>
    <div class="post_container mango-links-simple-shell">
        <?php if (!$hasLinksTag): ?>
            <article class="wznrys mango-links-simple-intro">
                <?php $this->content(); ?>
            </article>
        <?php endif; ?>

        <div class="mango-links-simple">
            <?php if (!$linksTableAvailable): ?>
                <div class="mango-links-simple-empty">
                    <i class="bi bi-plug" aria-hidden="true"></i>
                    <span>未检测到 links 数据表，请先安装并启用 Links 插件完成初始化</span>
                </div>
            <?php elseif (empty($linksBySort)): ?>
                <div class="mango-links-simple-empty">
                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                    <span>暂无友情链接</span>
                </div>
            <?php else: ?>
                <?php foreach ($linksBySort as $sortLabel => $groupLinks):
                    $groupId = 'mango-links-simple-' . md5((string)$sortLabel);
                    ?>
                    <section class="mango-links-simple-group" aria-labelledby="<?php echo $groupId; ?>">
                        <header class="mango-links-simple-group-heading">
                            <h2 id="<?php echo $groupId; ?>"><?php echo htmlspecialchars((string)$sortLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                            <span><?php echo count($groupLinks); ?></span>
                        </header>
                        <div class="mango-links-simple-grid">
                            <?php foreach ($groupLinks as $link):
                                $name = trim((string)$link['name']);
                                $url = trim((string)$link['url']);
                                $description = trim((string)($link['description'] ?? ''));
                                $image = trim((string)($link['image'] ?? ''));
                                if ($image === '') {
                                    $image = mango_simple_links_avatar_url($link['email'] ?? '', 48);
                                }
                                ?>
                                <a class="mango-links-simple-item"
                                   href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   title="<?php echo htmlspecialchars($description !== '' ? $description : $name, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <span class="mango-links-simple-avatar" aria-hidden="true">
                                        <span><?php echo htmlspecialchars(mango_simple_links_initial($name), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($image !== ''): ?>
                                            <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>"
                                                 alt=""
                                                 width="48"
                                                 height="48"
                                                 loading="lazy"
                                                 decoding="async"
                                                 onerror="this.remove()">
                                        <?php endif; ?>
                                    </span>
                                    <span class="mango-links-simple-copy">
                                        <strong><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo htmlspecialchars($description !== '' ? $description : $url, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php $this->need('comments.php'); ?>
</div>
<?php $this->need('sidebar.php');$this->need('footer.php'); ?>
