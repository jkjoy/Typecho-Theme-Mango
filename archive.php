<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php
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
    <?php $content = $this->content; $result = get_post_thumbnail($this); $images = $result['images'];if (!empty($images)): $imageCount = count($images);if($imageCount > 9) { $imageCount = 9;}?>
    <div class="post_images post_img_<?php echo $imageCount; ?>">
        <?php foreach ($images as $image): ?>
            <a data-fancybox="post-<?php $this->cid(); ?>" href="<?php echo htmlspecialchars($image); ?>">
                <img class="post-thumbnail" src="<?php echo htmlspecialchars($image); ?>" alt="<?php $this->title(); ?>">
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
if ($this->_currentPage < $totalPages): ?>
    <div class="post-read-more">
    <?php $this->pageLink('加载更多', 'next'); ?>
    </div>
<?php endif; ?>    
</div>
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
