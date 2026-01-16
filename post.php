<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title">
		<h1><?php $this->title() ?></h1>
		    <p>
		        <span><i class="bi bi-clock"></i><?php $this->date(); ?></span>
                <span><i class="bi bi-card-list"></i>
                    <?php $this->category(','); ?>
                </span>
		        <span><i class="bi bi-eye"></i><?php get_post_view($this) ?></span>
		    	<span><i class="bi bi-chat-square-text"></i>
				    <a href="<?php $this->permalink() ?>#post_comment_anchor">
                    <?php echo max(0, (int) $this->commentsNum); ?>
                    </a>
			    </span>
                    <?php if($this->user->hasLogin() && $this->user->pass('editor', true)): ?>    
                    <a href="<?php $this->options->adminUrl('write-post.php?cid=' . $this->cid); ?>" target="_blank" title="编辑文章"> 
                        <i class="bi bi-pen"></i>  <span>编辑</span>
                    </a> 
                    <?php endif; ?>
		    </p>
	</div>
    <div class="post_container">
		<article class="wznrys">
			<?php echo CustomContentFilter::parseImage($this->content, $this); ?>
		</article>
            <?php if ($this->modified > $this->created): ?>
        <p>最后更新于 <?php echo date('Y-m-d H:i:s', $this->modified); ?></p>
            <?php endif; ?>			
		<div class="post_loop_tag">
            <?php if ($this->tags):foreach ($this->tags as $tag): ?>
            <em> 
                <a href="<?php echo $tag['permalink']; ?>"><i class="bi bi-hash"></i><?php echo $tag['name']; ?></a> 
            </em>
            <?php endforeach;else:endif; ?>
        </div>
	</div>
	<div class="post_author">
		<div class="post_author_l">
            <?php $this->author->gravatar('60', ''); ?>
            <span><?php $this->author(); ?></span>
		</div>
		<div class="post_author_r">
			<div class="post_author_icon">
				<a href="#comments" title="评论">
                    <i class="bi bi-chat-square-dots-fill"></i>
                    <?php echo max(0, (int) $this->commentsNum); ?>
                </a>
				<a href="javascript:;" data-action="ding" data-id="<?php $this->cid(); ?>" class="specsZan ">
                    <i class="bi bi-hand-thumbs-up-fill"></i>
                    <small class="count"><?php if (function_exists('get_post_like')) { get_post_like($this); } else { echo 0; } ?></small>
				</a>
			</div>
		</div>
	</div>
        <?php
            $db = Typecho_Db::get();
            $prev = $db->fetchRow($db->select('cid', 'title', 'slug', 'created','text')
                ->from('table.contents')
                ->where('created < ?', $this->created)
                ->where('type = ?', 'post')
                ->where('status = ?', 'publish')
                ->order('created', Typecho_Db::SORT_DESC)
                ->limit(1));
            $result = get_post_thumbnail($prev);
            $prevThumbnailUrl = !empty($result['cropped_images']) ? $result['cropped_images'][0] : $result['thumbnail'];
            $next = $db->fetchRow($db->select('cid', 'title', 'slug', 'created','text')
                ->from('table.contents')
                ->where('created > ?', $this->created)
                ->where('type = ?', 'post')
                ->where('status = ?', 'publish')
                ->order('created', Typecho_Db::SORT_ASC)
                ->limit(1));
            $result = get_post_thumbnail($next);
            $nextThumbnailUrl = !empty($result['cropped_images']) ? $result['cropped_images'][0] : $result['thumbnail'];
        ?>
        <div class="next_prev_posts">
            <div class="prev_next_box nav_previous"<?php if (!$next) echo ' style="width:100%"'; ?>>
            <?php if ($prev):
                $prevUrl = Typecho_Router::url('post', $prev, $this->options->index);
            ?>
                <a href="<?php echo $prevUrl; ?>" title="<?php echo $prev['title']; ?>" rel="prev" style="background-image: url(<?php echo $prevThumbnailUrl; ?>);">
                    <div class="prev_next_info">
                        <small>上一篇</small>
                        <p><?php echo $prev['title']; ?></p>
                    </div>
                </a>
            <?php else: endif; ?>
            </div>
            <div class="prev_next_box nav_next"<?php if (!$prev) echo ' style="width:100%"'; ?>>
            <?php if ($next):
                $nextUrl = Typecho_Router::url('post', $next, $this->options->index);
            ?>
                <a href="<?php echo $nextUrl; ?>" title="<?php echo $next['title']; ?>" rel="next" style="background-image: url(<?php echo $nextThumbnailUrl; ?>);">
                    <div class="prev_next_info">
                        <small>下一篇</small>
                        <p><?php echo $next['title']; ?></p>
                    </div>
                </a>
            <?php else:endif; ?>
            </div>
        </div>
    <?php $this->related(5)->to($relatedPosts); if ($relatedPosts->have()):?>
    <div class="post_related mb-3">    
        <h3 class="widget-title">相关文章</h3>
        <?php while ($relatedPosts->next()): ?> 
            <div class="post_related_list">
                <a href="<?php $relatedPosts->permalink(); ?>" class="" title="<?php $relatedPosts->title(); ?>">
                    <?php $relatedPosts->title(); ?>
                </a>
            </div>	
        <?php endwhile; ?>	
    </div>	 
    <?php endif;$this->need('comments.php'); ?>
</div><!-- #main-->
<?php $this->need('sidebar.php');$this->need('footer.php'); ?>