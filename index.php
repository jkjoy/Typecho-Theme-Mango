<?php
/**
 * Mango for Typecho
 * 双栏主题
 * 原作者 huitheme
 * 老孙博客移植
 * @package  Mango 
 * @author 老孙
 * @version 1.5.0
 * @link http://www.imsun.pw
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

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
?>
<div class="col-lg-8">
    <div class="post_box">
        <?php while ($this->next()):$this->need('components/post-loop-item.php');endwhile; ?>
    </div>
<?php if ($this->options->loadmore == 0): $this->pageNav('上页','下页',1,'...',array(
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