<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
/**
 * 将时间戳转换为"多久之前"的格式
 *
 * @param int $timestamp 时间戳
 * @return string
 */
function time_ago($timestamp) {
    $current_time = time();
    $time_diff = $current_time - $timestamp;

    if ($time_diff < 60) {
        return $time_diff . ' 秒前';
    } elseif ($time_diff < 3600) {
        return floor($time_diff / 60) . ' 分钟前';
    } elseif ($time_diff < 86400) {
        return floor($time_diff / 3600) . ' 小时前';
    } elseif ($time_diff < 2592000) {
        return floor($time_diff / 86400) . ' 天前';
    } elseif ($time_diff < 31536000) {
        return floor($time_diff / 2592000) . ' 个月前';
    } else {
        return floor($time_diff / 31536000) . ' 年前';
    }
}
/*
* 文章浏览数统计
*/
function get_post_view($archive) {
    try {
        $cid = $archive->cid;
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
            $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0;');
            echo 0;
            return;
        }
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
        if ($archive->is('single')) {
            $views = Typecho_Cookie::get('extend_contents_views');
            if (empty($views)) {
                $views = array();
            } else {
                $views = explode(',', $views);
            }
            if (!in_array($cid, $views)) {
                $db->query($db->update('table.contents')->rows(array('views' => (int)$row['views'] + 1))->where('cid = ?', $cid));
                array_push($views, $cid);
                $views = implode(',', $views);
                Typecho_Cookie::set('extend_contents_views', $views); //记录查看cookie
            }
        }
        echo $row['views'];
    } catch (Exception $e) {
        error_log('Error in get_post_view: ' . $e->getMessage());
        echo 0;
    }
}

/*
* 文章点赞数（存储到 contents.likes，避免使用自定义字段）
*/
function mango_ensure_contents_likes_column()
{
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();

    try {
        $db->fetchRow($db->select('likes')->from('table.contents')->limit(1));
    } catch (Exception $e) {
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `likes` INT(10) DEFAULT 0;');
    }
}

function mango_migrate_likes_from_fields_if_needed($cid, $currentLikes)
{
    $currentLikes = (int)$currentLikes;
    if ($currentLikes > 0) {
        return $currentLikes;
    }

    try {
        $db = Typecho_Db::get();
        $row = $db->fetchRow($db->select('str_value')
            ->from('table.fields')
            ->where('cid = ?', $cid)
            ->where('name = ?', 'likes')
            ->limit(1));
        if ($row && isset($row['str_value'])) {
            $likes = (int)$row['str_value'];
            if ($likes > 0) {
                $db->query($db->update('table.contents')->rows(array('likes' => $likes))->where('cid = ?', $cid));
                return $likes;
            }
        }
    } catch (Exception $e) {
    }

    return $currentLikes;
}

function get_post_like($archive)
{
    try {
        $cid = $archive->cid;
        $db = Typecho_Db::get();

        mango_ensure_contents_likes_column();

        $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
        $likes = $row && isset($row['likes']) ? (int)$row['likes'] : 0;
        $likes = mango_migrate_likes_from_fields_if_needed($cid, $likes);

        echo $likes;
    } catch (Exception $e) {
        error_log('Error in get_post_like: ' . $e->getMessage());
        echo 0;
    }
}

function mango_increment_post_like($cid)
{
    $cid = (int)$cid;
    if ($cid <= 0) {
        return null;
    }

    $db = Typecho_Db::get();
    mango_ensure_contents_likes_column();

    $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
    $likes = $row && isset($row['likes']) ? (int)$row['likes'] : 0;
    $likes = mango_migrate_likes_from_fields_if_needed($cid, $likes);
    $likes = $likes + 1;

    $db->query($db->update('table.contents')->rows(array('likes' => $likes))->where('cid = ?', $cid));
    return $likes;
}

/**
 * 自定义图片解析
 * 
 * @param string $content 文章内容
 * @param Typecho_Widget $widget 文章对象
 * @return string 解析后的文章内容
 */
Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('CustomContentFilter', 'parseImage');

class CustomContentFilter
{
    public static function parseImage($content, $widget)
    {
        // 确保$content是字符串
        if (is_array($content)) {
            return $content;
        }
        
        // 匹配图片标签的正则表达式
        $pattern = '/<img.*?src="([^"]*)"[^>]*alt="([^"]*)"[^>]*>/i';
        
        // 替换为指定格式
        $replacement = '<figure class="size-full"><a href="$1" data-fancybox="gallery"><img decoding="async" src="$1" alt="$2" class="wp-image"/></a></figure>';
        
        // 执行替换
        return preg_replace($pattern, $replacement, $content);
    }
}
