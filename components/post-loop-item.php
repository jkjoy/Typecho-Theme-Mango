<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
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
            $result = get_post_thumbnail($this);
            $images = $result['images'];
            $cropped_images = $result['cropped_images'];
            $total_count = $result['total_count'];
            if (!empty($images)):
                $imageCount = count($images);
                ?>
        <div class="post_images post_img_<?php echo $imageCount; ?>">
        <?php foreach ($images as $i => $image): ?>
        <a data-fancybox="post-<?php $this->cid(); ?>" href="<?php echo htmlspecialchars($image); ?>">
            <?php
                $fallbackThumb = Helper::options()->themeUrl . '/assets/img/nopic.svg';
                $customThumb = Helper::options()->thumbUrl ?? '';
                if (!empty($customThumb)) {
                    $fallbackThumb = $customThumb;
                }
            ?>
            <img class="post-thumbnail" src="<?php echo htmlspecialchars($cropped_images[$i]); ?>" alt="<?php $this->title(); ?>" loading="lazy" decoding="async" data-fallback="<?php echo htmlspecialchars($fallbackThumb); ?>">
            <?php if ($i == 8 && $total_count > 9): ?>
            <b>+<?php echo $total_count - 9; ?></b>
            <?php endif; ?>
        </a>
        <?php if ($i == 8 && $total_count > 9) break; ?>
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
            <span class=""><i class="bi bi-chat-square-text-fill"></i><a href="<?php $this->permalink() ?>#comments"><?php $this->commentsNum('0', '1', '%d'); ?></a></span>
            <span class=""><i class="bi bi-eye-fill"></i><?php get_post_view($this) ?></span>
            <span>
            <a href="javascript:;" data-action="ding" data-id="<?php $this->cid(); ?>" class="specsZan ">
                <i class="bi bi-heart-fill"></i>
                <em class="count"><?php if (function_exists('get_post_like')) { get_post_like($this); } else { echo 0; } ?></em>
            </a>
            </span>
        </div>
    </div>
</div>
