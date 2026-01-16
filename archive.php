<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (function_exists('mango_is_json_pagination_request') && mango_is_json_pagination_request()) {
    ob_start();
    while ($this->next()) {
        $this->need('components/post-loop-item.php');
    }
    $postsHtml = ob_get_clean();
    $nextHref = function_exists('mango_get_next_page_href') ? mango_get_next_page_href($this) : null;

    mango_send_json([
        'success' => true,
        'postsHtml' => $postsHtml,
        'nextHref' => $nextHref,
        'hasMore' => !empty($nextHref),
    ]);
}
 $this->need('header.php');
    $categoryImage = '';
    if ($this->categories) {
        $category = $this->categories[0];
        $categoryId = $category['mid'];
        $categoryName = $category['name'];
        $categoryDescription = $category['description']; 
        $themeUrl = Helper::options()->themeUrl . '/assets/img/';
        $categoryImage = $themeUrl . $categoryId . '.png';
    }
?>
<div class="col-lg-8">
    <div class="catbox">
        <div class="cat_head">
        <?php if ($this->is('category')): ?>
        <img width="180" height="180" 
         src="<?php echo $categoryImage; ?>" 
         class="attachment-180x180x1 size-180x180x1" 
         alt="<?php echo htmlspecialchars($categoryName); ?>" 
         decoding="async"
         loading="lazy">
        <?php endif; ?>
            <div class="cat_head_r">
            <h2><?php $this->archiveTitle([
            'category' => _t('<i class="bi bi-hash me-1"></i>%s'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('<i class="bi bi-hash me-1"></i>%s'),
            'author'   => _t('%s 发布的文章')
        ], '', ''); ?></h2>
        <?php if ($this->is('category')): ?>
        <p><?php echo $this->getDescription(); ?></p>
        <?php endif; ?>
            </div>
        </div>
        <div class="post_box">
    <?php while ($this->next()):$this->need('components/post-loop-item.php');endwhile; ?>
    </div>
</div> 
<?php if ($this->options->loadmore == 0):$this->pageNav('上页','下页',1,'...',array(
                            'wrapTag' => 'div',
                            'wrapClass' => 'posts-nav',
                            'itemTag' => 'span',
                            'textTag' => 'span',
                            'itemClass' => 'post-page-numbers',
                            'currentClass' => 'post-page-numbers current',
                            'prevClass' => 'hidden',
                            'nextClass' => 'hidden'
                        )); else:
$nextPage = $this->_currentPage + 1;
$totalPages = ceil($this->getTotal() / $this->parameter->pageSize);
if ($this->_currentPage < $totalPages): ?>
    <div class="post-read-more">
    <?php $this->pageLink('加载更多', 'next'); ?>
    </div>
<?php endif; endif; ?>    
</div>
<?php $this->need('sidebar.php');$this->need('footer.php'); ?>