<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="col-lg-4">
    <div class="sidebar_sticky">
    <div class="author_show_box">
<!-- 作者信息 -->
<?php
// 获取数据库实例
$db = Typecho_Db::get();
// 获取当前用户
$user = Typecho_Widget::widget('Widget_User');
// 确定要显示的用户
if ($user->hasLogin()) {
    // 用户已登录，显示当前用户信息
    $targetUser = $user;
    $userId = $user->uid;
} else {
    // 用户未登录，获取管理员信息
    try {
        // 查询管理员用户（通常 group='administrator' 或 uid=1）
        $adminUser = $db->fetchRow($db->select()
            ->from('table.users')
            ->where('group = ?', 'administrator')
            ->limit(1));
        
        if ($adminUser) {
            // 使用管理员信息创建临时用户对象
            $targetUser = new stdClass();
            $targetUser->uid = $adminUser['uid'];
            $targetUser->mail = $adminUser['mail'];
            $targetUser->screenName = $adminUser['screenName'];
            $userId = $adminUser['uid'];
        } else {
            // 如果找不到管理员，返回空
            echo "";
            return;
        }
    } catch (Exception $e) {
        error_log('获取管理员信息失败: ' . $e->getMessage());
        echo "";
        return;
    }
}
// 查询用户的文章数量
$postCount = $db->fetchRow($db->select('COUNT(*) AS count')
    ->from('table.contents')
    ->where('authorId = ?', $userId)
    ->where('type = ?', 'post')
    ->where('status = ?', 'publish')
)['count'];

// 查询用户的评论数量
$commentCount = $db->fetchRow($db->select('COUNT(*) AS count')
    ->from('table.comments')
    ->where('authorId = ?', $userId)
    ->where('status = ?', 'approved')
)['count'];
// 生成 Gravatar 头像 URL
$email = $targetUser->mail;
$options = Typecho_Widget::widget('Widget_Options');
$gravatarPrefix = empty($options->cnavatar) ? 'https://cravatar.cn/avatar/' : $options->cnavatar;
$gravatarUrl = $gravatarPrefix . md5(strtolower(trim($email))) . '?s=80&d=mm&r=g';
$gravatarUrl2x = $gravatarPrefix . md5(strtolower(trim($email))) . '?s=160&d=mm&r=g';
?>
<div class="author_show_head">
    <img alt='<?php echo $targetUser->screenName; ?>' 
         src='<?php echo $gravatarUrl; ?>' 
         srcset='<?php echo $gravatarUrl2x; ?> 2x' 
         class='avatar avatar-80 photo' 
         height='80' width='80' 
         loading='lazy' 
         decoding='async'/>
    <h3><?php echo $targetUser->screenName; ?></h3>
    <p></p>
</div>
<div class="author_show_info">
    <span><i class="bi bi-book"></i><b>文章</b><?php echo $postCount; ?></span>
    <span><i class="bi bi-chat-square-dots"></i><b>评论</b><?php echo $commentCount; ?></span>
</div>
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowRecentPosts', $this->options->sidebarBlock)): ?>
<ul class="author_post">
<?php
    // 获取指定用户的最近文章
    $recentPosts = Typecho_Widget::widget('Widget_Contents_Post_Recent', 'pageSize=3&uid=' . $userId);
    while ($recentPosts->next()):
        $result = get_post_thumbnail($recentPosts);
        $thumbnail = !empty($result['images']) ? $result['images'][0] : $result['thumbnail'];
        $commentsNum = $recentPosts->commentsNum;
    ?>
        <li>
            <div class="thumbnail-container">
                <img width="400" height="280" 
                     src="<?php echo htmlspecialchars($thumbnail); ?>" 
                     class="thumbnail" 
                     alt="<?php echo $recentPosts->title; ?>" 
                     decoding="async" loading="lazy" />
            </div>
            <div class="author_title">
                <a href="<?php echo $recentPosts->permalink; ?>" 
                   class="stretched-link"><?php echo $recentPosts->title; ?></a>
                <p><?php echo $commentsNum . ' 条留言'; ?></p>
            </div>
        </li>
    <?php endwhile; ?>
</ul>
<?php endif; ?>
 
<!-- 热门文章 -->
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowHotPosts', $this->options->sidebarBlock)): ?>
    <?php
    $db = Typecho_Db::get();
    try {
        $result = $db->fetchAll($db->select()
            ->from('table.contents')
            ->where('type = ? AND status = ?', 'post', 'publish')
            ->order('commentsNum', Typecho_Db::SORT_DESC)
            ->limit(5)
        );

        if (!empty($result)):
    ?>
            <aside id="hot_posts-2" class="widget widget_hot_posts">
                <h3 class="widget-title">热门文章</h3>
                <ul class="widget_hot_post">
                    <?php 
                    foreach ($result as $post): 
                        try {
                            // 使用 Widget_Abstract_Contents 处理文章数据
                            $temp_post = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($post);
                            $post_images = get_post_thumbnail($post);
                            // 获取缩略图URL，如果没有图片则使用默认图片
                            $thumbnail = !empty($post_images['images']) ? $post_images['images'][0] : $post_images['thumbnail'];
                    ?>
                            <li class="widget_hot_li">
                                <img width="400" 
                                     height="280" 
                                     src="<?php echo htmlspecialchars($thumbnail); ?>" 
                                     class="thumbnail" 
                                     alt="<?php echo htmlspecialchars($temp_post['title']); ?>" 
                                     decoding="async" 
                                     loading="lazy">
                                <div class="hot_post_info">
                                    <h4>
                                        <a class="stretched-link" 
                                           href="<?php echo htmlspecialchars($temp_post['permalink']); ?>">
                                            <?php echo htmlspecialchars($temp_post['title']); ?>
                                        </a>
                                    </h4>
                                    <p><?php echo intval($temp_post['commentsNum']); ?> 条留言</p>
                                </div>
                            </li> 
                    <?php 
                        } catch (Exception $e) {
                            // 捕获并处理异常
                            echo "处理文章时出错: " . $e->getMessage();
                        }
                    endforeach; 
                    ?>
                </ul>
            </aside>
        <?php else: ?>
            <p>无热门文章</p>
        <?php endif; ?>
    <?php 
    } catch (Exception $e) {
        // 捕获并处理异常
        echo "获取热门文章时出错: " . $e->getMessage();
    }
    ?>
<?php endif; ?>
<!-- 最近回复 -->
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowRecentComments', $this->options->sidebarBlock)): ?>
    <aside id="comments-3" class="widget widget_comments">
        <h3 class="widget-title"><?php _e('最近回复'); ?></h3>
        <ul class="widget_comment_ul">
        <?php $comments = \Widget\Comments\Recent::alloc(); ?>
            <?php while ($comments->next()): ?>
                <li>
                <?php echo $comments->gravatar('40', ''); ?>
                <div class="widget_comment_info">
                <a rel="nofollow" href="<?php $comments->permalink(); ?>"><?php $comments->excerpt(35, '...'); ?></a>
                <span>
                    <em><?php $comments->author(false); ?></em>
                    <em><?php $comments->date('Y-m-d H:i'); ?></em>
                </span>
            </div>
            <?php endwhile; ?>
            </li>
            </ul>
        </aside>
<?php endif; ?> 
 
<!-- 热门标签 -->
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowTags', $this->options->sidebarBlock)): ?>
    <?php
    // 获取热门标签
    $tags = \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=20');
    if ($tags->have()):
    ?>
        <aside id="hot_tags-2" class="widget widget_hot_tags">
            <h3 class="widget-title">热门标签</h3>
            <div class="tagcloud">
                <?php while ($tags->next()): ?>
                    <a href="<?php $tags->permalink(); ?>" 
                       title="<?php $tags->name(); ?> (<?php $tags->count(); ?> 篇文章)" 
                       class="tag-item">
                        <?php $tags->name(); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </aside>
    <?php else: ?>
        <p>无热门标签</p>
    <?php endif; ?>
<?php endif; ?>

 <!-- 其它 -->
  
<?php if (!empty($this->options->sidebarBlock) && in_array('ShowOther', $this->options->sidebarBlock)): ?>
        <aside id="misc-2" class="widget widget_misc">
            <h3 class="widget-title"><?php _e('其它'); ?></h3>
            <ul class="widget_misc_ul">
                <?php if ($this->user->hasLogin()): ?>
                    <p>
                        <a href="<?php $this->options->adminUrl(); ?>"><?php _e('<i class="bi bi-box-arrow-in-right me-1"></i> 进入后台'); ?>
                            (<?php $this->user->screenName(); ?>)
                        </a>
                    </p>
                    <p>
                        <a href="<?php $this->options->logoutUrl(); ?>"><?php _e('<i class="bi bi-box-arrow-right me-1"></i> 退出'); ?></a>
                    </p>
                <?php else: ?>
                    <p>
                        <a href="<?php $this->options->adminUrl('login.php'); ?>"><?php _e('<i class="bi bi-box-arrow-in-right me-1"></i> 登录'); ?></a>
                    </p>
                <?php endif; ?>
                <p>
                    <a href="<?php $this->options->feedUrl(); ?>"><?php _e('<i class="bi bi-rss me-1"></i> 文章 '); ?></a> 
                </p>
                <p>
                <a href="<?php $this->options->commentsFeedUrl(); ?>"><?php _e('<i class="bi bi-rss-fill me-1"></i> 评论 '); ?></a>
                </p>
            </ul>
        </aside>
    <?php endif; ?>
</div>