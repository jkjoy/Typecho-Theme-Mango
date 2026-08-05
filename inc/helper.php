<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 关闭评论反垃圾保护
 * 评论层级突破999
 * 关闭检查评论来源URL与文章链接是否一致判断
 * 最新评论显示在前
 */
function themeInit($archive)
{
    Helper::options()->commentsAntiSpam = false;
    Helper::options()->commentsMaxNestingLevels = 999;
    Helper::options()->commentsOrder = 'DESC';
    Helper::options()->commentsCheckReferer = false;
}

/**
 * Gravatar镜像
 */
try {
    $options = Typecho_Widget::widget('Widget_Options');
    $gravatarPrefix = empty($options->cnavatar) ? 'https://cravatar.cn/avatar/' : $options->cnavatar;
    define('__TYPECHO_GRAVATAR_PREFIX__', $gravatarPrefix);
} catch (Exception $e) {
    error_log('Error in Gravatar settings: ' . $e->getMessage());
    if (!defined('__TYPECHO_GRAVATAR_PREFIX__')) {
        define('__TYPECHO_GRAVATAR_PREFIX__', 'https://cravatar.cn/avatar/');
    }
}
