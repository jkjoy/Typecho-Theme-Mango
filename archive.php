<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="catbox">
        <div class="cat_head">
            <div class="cat_head_r">
            <h2><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 <i class="bi bi-hash me-1"></i>%s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ''); ?></h2>
            </div>
        </div>
        <div class="post_box">
    <?php while ($this->next()): ?>
        <div class="post_loop">
    <div class="post_loop_head">
        <div class="post_loop_head_author">
            <a class="images_author" href="<?php $this->author->permalink(); ?>" title="<?php $this->title() ?>">
                <?php $this->author->gravatar('60', ''); ?>
            </a>
            <div class="images_author_name">
                <h3><?php $this->author(); ?></h3>
                <span><?php echo time_ago($this->created); ?></span>
            </div>
        </div>
        <a class="post_loop_more" href="<?php $this->permalink() ?>" title="<?php $this->title() ?>"><i class="bi bi-three-dots"></i></a>
    </div>
    <div class="post_loop_conter">
        <div class="post_loop_title_box">
            <h2 class="post_loop_title">
                <a class="stretched-link" href="<?php $this->permalink() ?>" title="<?php $this->title() ?>">
                <?php $this->title() ?>
                </a>
            </h2>
                <p>        
                    <?php if($this->fields->summary){echo $this->fields->summary;} else {$this->excerpt(180);}?>      
                </p>
        </div>
<?php
// 获取文章内容
$content = $this->content;
preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
$images = array_filter($matches[1], function($url) {
    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
    return $extension !== 'svg';
});
if (!empty($images)):
    $imageCount = count($images);
?>
    <div class="post_images post_img_<?php echo $imageCount; ?>">
        <?php foreach ($images as $image): ?>
            <a data-fancybox="post-1" href="<?php echo htmlspecialchars($image); ?>">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="文章图片">
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
        <div class="post_loop_tag">
            <?php if ($this->tags): ?>
            <?php foreach ($this->tags as $tag): ?>
            <em> 
                <a href="<?php echo $tag['permalink']; ?>"><i class="bi bi-hash"></i><?php echo $tag['name']; ?></a> 
            </em>
            <?php endforeach; ?>
            <?php else: ?>
            <?php endif; ?>
        </div>
        <div class="post_info_footer">
            <span class=""><i class="bi bi-chat-square-text-fill"></i><a href="<?php $this->permalink() ?>#respond"><?php $this->commentsNum('0', '1', '%d'); ?></a></span>
            <span class=""><i class="bi bi-eye-fill"></i><?php get_post_view($this) ?></span>
            <span>
            <?php $likes = $this->fields->likes ? $this->fields->likes : 0; ?>
            <a href="javascript:;" data-action="ding" data-id="<?php $this->cid(); ?>" class="specsZan ">
                <i class="bi bi-heart-fill"></i>
                <em class="count"><?php echo $likes; ?></em>
            </a>
            </span>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
</div>
<?php
$nextPage = $this->_currentPage + 1;
$totalPages = ceil($this->getTotal() / $this->parameter->pageSize);

// 检查是否还有下一页
if ($this->_currentPage < $totalPages): ?>
    <div class="post-read-more">
        <?php if ($this->is('index')): ?>
            <a href="<?php $this->options->siteUrl(); ?>page/<?php echo $nextPage; ?>">加载更多</a>
        <?php else: ?>
            <?php 
                // 获取当前请求的完整路径
                $requestUri = $this->request->getRequestUri();
                // 移除页码相关参数
                $baseUrl = preg_replace('/\/page\/\d+/', '', $requestUri);
                $baseUrl = preg_replace('/\/\d+$/', '', $baseUrl);
                // 确保路径末尾有斜杠
                $baseUrl = rtrim($baseUrl, '/') . '/';
                // 构建完整的下一页URL，直接添加页码
                echo '<a href="' . $baseUrl . $nextPage . '">加载更多</a>';
            ?>
        <?php endif; ?>
    </div>
<?php endif; ?>    
</div>
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
