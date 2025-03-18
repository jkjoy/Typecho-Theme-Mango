<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('站点 LOGO 地址'),
        _t('在这里填入一个图片 URL 地址, 以在网站标题前加上一个 LOGO')
    );
    $form->addInput($logoUrl);
    $faviconUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'faviconUrl',
        null,
        null,
        _t('站点 favicon 地址'),
        _t('在这里填入一个图片 URL 地址, 以在浏览器标签页的网站标题前加上一个 favicon')
    );
    $form->addInput($faviconUrl);    

    $cnavatar = new Typecho_Widget_Helper_Form_Element_Text('cnavatar', NULL, 'https://cravatar.cn/avatar/', _t('Gravatar镜像'), _t('默认https://cravatar.cn/avatar/,建议保持默认'));
    $form->addInput($cnavatar);
    $icpbeian = new Typecho_Widget_Helper_Form_Element_Text('icpbeian', NULL, 'ICP备', _t('备案号码'), _t('不填写则不显示'));
    $form->addInput($icpbeian);
    $showlinks = new Typecho_Widget_Helper_Form_Element_Radio('showlinks', ['0' => _t('不显示'), '1' => _t('显示')], '0', _t('友情链接'), _t('是否显示首页友情链接'));
    $form->addInput($showlinks);
    $tongji = new Typecho_Widget_Helper_Form_Element_Textarea('tongji', NULL, NULL, _t('Footer代码'), _t('在footer中插入代码支持HTML'));
    $form->addInput($tongji);

    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        [
            'ShowRecentPosts'    => _t('显示最新文章'),
            'ShowRecentComments' => _t('显示最近回复'),
            'ShowHotPosts' => _t('显示热门文章'),
            'ShowCategory'       => _t('显示分类'),
            'ShowArchive'        => _t('显示归档'),
            'ShowOther'          => _t('显示其它杂项')
        ],
        ['ShowRecentPosts', 'ShowRecentComments', 'ShowHotPosts', 'ShowCategory', 'ShowArchive', 'ShowOther'],
        _t('侧边栏显示')
    );

    $form->addInput($sidebarBlock->multiMode());
}

/**
 * 将时间戳转换为“多久之前”的格式
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
/**
* Gravatar镜像
*/
// 获取Typecho的选项
$options = Typecho_Widget::widget('Widget_Options');
// 检查cnavatar是否已设置，如果未设置或为空，则使用默认的Gravatar前缀
$gravatarPrefix = empty($options->cnavatar) ? 'https://cravatar.cn/avatar/' : $options->cnavatar;
// 定义全局常量__TYPECHO_GRAVATAR_PREFIX__，用于存储Gravatar前缀
define('__TYPECHO_GRAVATAR_PREFIX__', $gravatarPrefix);

/**
* 页面加载时间
*/
function timer_start() {
    global $timestart;
    $mtime = explode( ' ', microtime() );
    $timestart = $mtime[1] + $mtime[0];
    return true;
    }
    timer_start();
    function timer_stop( $display = 0, $precision = 3 ) {
    global $timestart, $timeend;
    $mtime = explode( ' ', microtime() );
    $timeend = $mtime[1] + $mtime[0];
    $timetotal = number_format( $timeend - $timestart, $precision );
    $r = $timetotal < 1 ? $timetotal * 1000 . " ms" : $timetotal . " s";
    if ( $display ) {
    echo $r;
    }
    return $r;
    }

/*
* 文章浏览数统计
*/
function get_post_view($archive) {
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
}

/**
 * 处理文章点赞
 */
if (isset($_POST['action']) && $_POST['action'] == 'specs_zan') {
    handlePostLike();
}

function handlePostLike() {
    if (isset($_POST['cid'])) {
        $db = Typecho_Db::get();
        $cid = $_POST['cid'];
        
        // 获取当前点赞数
        $row = $db->fetchRow($db->select('str_value')
            ->from('table.fields')
            ->where('cid = ?', $cid)
            ->where('name = ?', 'likes'));
            
        $likes = isset($row['str_value']) ? intval($row['str_value']) : 0;
        $likes = $likes + 1;
        
        // 更新点赞数
        if (isset($row['str_value'])) {
            $db->query($db->update('table.fields')
                ->rows(array('str_value' => $likes))
                ->where('cid = ?', $cid)
                ->where('name = ?', 'likes'));
        } else {
            $db->query($db->insert('table.fields')
                ->rows(array(
                    'cid' => $cid,
                    'name' => 'likes',
                    'type' => 'str',
                    'str_value' => '1'
                )));
        }
        
        echo $likes;
        exit;
    }
}

/**
 * 注册AJAX处理函数 - 加载更多文章
 */
if (isset($_POST['action']) && $_POST['action'] == 'load_more') {
    loadMorePosts();
}

/**
 * 加载更多文章的处理函数
 */
function loadMorePosts() {
    if (isset($_POST['page'])) {
        $db = Typecho_Db::get();
        $pageSize = 5; // 每页文章数
        $currentPage = intval($_POST['page']); // 当前页码
        
        // 查询下一页文章
        $posts = $db->fetchAll($db->select()->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', Typecho_Db::SORT_DESC)
            ->page($currentPage, $pageSize));
            
        $result = array();
        foreach ($posts as $post) {
            // 组装文章数据
            $result[] = array(
                'cid' => $post['cid'],
                'title' => $post['title'],
                'permalink' => get_permalink($post['cid']),
                'date' => date('Y-m-d', $post['created']),
                'excerpt' => Typecho_Common::subStr(strip_tags($post['text']), 0, 200, '...') 
            );
        }
        
        echo json_encode($result);
        exit;
    }
}

/**
 * 获取文章总数
 */
function getPostsCount() {
    $db = Typecho_Db::get();
    return $db->fetchObject($db->select(array('COUNT(cid)' => 'num'))
        ->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish'))->num;
}

/**
 * 获取文章缩略图
 * 
 * @param object|array $post 文章对象或数组
 * @return string 缩略图URL
 */
function get_post_thumbnail($post) {
    // 将数组转换为对象
    if (is_array($post)) {
        $post = (object)$post;
    }
    
    // 获取默认缩略图
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg';
    
    // 调试信息
    error_log('Post object: ' . print_r($post, true));
    
    if (!$post) {
        error_log('No post object provided');
        return $default_thumbnail;
    }
    
    // 1. 尝试获取文章内容
    $content = '';
    
    // 获取完整的文章内容
    if (isset($post->text) && !empty($post->text)) {
        $content = $post->text;
    } else if (isset($post->content) && !empty($post->content)) {
        $content = $post->content;
    } else if (method_exists($post, 'content') && is_callable([$post, 'content'])) {
        $content = $post->content();
    }
    
    error_log('Article content length: ' . strlen($content));
    
    if (!empty($content)) {
        // 2. 尝试匹配第一张图片
        // 匹配 HTML img 标签
        if (preg_match('/<img[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches)) {
            $img_url = $matches[1];
            error_log('Found HTML image: ' . $img_url);
            
            // 处理相对路径
            if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
            }
            return $img_url;
        }
        
        // 匹配 Markdown 格式图片
        if (preg_match('/!\[([^\]]*)\]\(([^\)]+)\)/i', $content, $matches)) {
            $img_url = $matches[2];
            error_log('Found Markdown image: ' . $img_url);
            
            // 处理相对路径
            if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
            }
            return $img_url;
        }
        
        // 尝试匹配任何图片URL
        if (preg_match('/(https?:\/\/[^\s<>"\']*?\.(?:jpg|jpeg|png|gif|webp))(\?[^\s<>"\']*)?/i', $content, $matches)) {
            error_log('Found URL image: ' . $matches[1]);
            return $matches[1];
        }
    }
    
    error_log('No image found, using default: ' . $default_thumbnail);
    return $default_thumbnail;
}

/**
 * 处理Typecho特有的内容格式
 * 
 * @param string $content 文章内容
 * @return string 处理后的内容
 */
function parse_typecho_content($content) {
    // 处理[attach]标签
    if (preg_match_all('/\[attach\](\d+)\[\/attach\]/i', $content, $matches)) {
        foreach ($matches[1] as $index => $cid) {
            try {
                $db = Typecho_Db::get();
                $attachment = $db->fetchRow($db->select()->from('table.contents')
                    ->where('cid = ? AND type = ?', $cid, 'attachment'));
                
                if ($attachment) {
                    // 获取附件URL
                    $attachUrl = isset($attachment['text']) ? $attachment['text'] : '';
                    if (!empty($attachUrl)) {
                        // 替换[attach]标签为实际的图片标签
                        $content = str_replace(
                            $matches[0][$index],
                            '<img src="' . $attachUrl . '" alt="附件图片" />',
                            $content
                        );
                    }
                }
            } catch (Exception $e) {
                // 忽略错误，保持原标签不变
            }
        }
    }
    
    return $content;
}

/**
 * 获取上一篇文章
 * 
 * @param Widget_Archive $archive 当前文章归档对象
 * @return object|null 上一篇文章对象，如果没有则返回null
 */
function get_previous_post($archive) {
    if (!$archive->is('single')) {
        return null;
    }
    
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    // 获取上一篇文章（按创建时间排序）
    $post = $db->fetchRow($db->select()
        ->from('table.contents')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created < ?', $archive->created)
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.created', Typecho_Db::SORT_DESC)
        ->limit(1));
    
    if (!$post) {
        return null;
    }
    
    // 构建标准化的文章对象
    $result = new stdClass();
    $result->cid = $post['cid'];
    $result->title = $post['title'];
    $result->slug = $post['slug'];
    $result->created = $post['created'];
    $result->content = isset($post['text']) ? $post['text'] : '';
    $result->text = isset($post['text']) ? $post['text'] : '';
    $result->permalink = get_permalink($post['cid']);
    
    // 获取文章自定义字段
    $fields = $db->fetchAll($db->select()->from('table.fields')
        ->where('cid = ?', $post['cid']));
    
    // 添加自定义字段到文章对象
    if ($fields) {
        $result->fields = new stdClass();
        foreach ($fields as $field) {
            $result->fields->{$field['name']} = $field['str_value'] ? $field['str_value'] : $field['int_value'];
        }
    }
    
    return $result;
}

/**
 * 获取下一篇文章
 * 
 * @param Widget_Archive $archive 当前文章归档对象
 * @return object|null 下一篇文章对象，如果没有则返回null
 */
function get_next_post($archive) {
    if (!$archive->is('single')) {
        return null;
    }
    
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    // 获取下一篇文章（按创建时间排序）
    $post = $db->fetchRow($db->select()
        ->from('table.contents')
        ->where('table.contents.status = ?', 'publish')
        ->where('table.contents.created > ?', $archive->created)
        ->where('table.contents.type = ?', 'post')
        ->order('table.contents.created', Typecho_Db::SORT_ASC)
        ->limit(1));
    
    if (!$post) {
        return null;
    }
    
    // 构建标准化的文章对象
    $result = new stdClass();
    $result->cid = $post['cid'];
    $result->title = $post['title'];
    $result->slug = $post['slug'];
    $result->created = $post['created'];
    $result->content = isset($post['text']) ? $post['text'] : '';
    $result->text = isset($post['text']) ? $post['text'] : '';
    $result->permalink = get_permalink($post['cid']);
    
    // 获取文章自定义字段
    $fields = $db->fetchAll($db->select()->from('table.fields')
        ->where('cid = ?', $post['cid']));
    
    // 添加自定义字段到文章对象
    if ($fields) {
        $result->fields = new stdClass();
        foreach ($fields as $field) {
            $result->fields->{$field['name']} = $field['str_value'] ? $field['str_value'] : $field['int_value'];
        }
    }
    
    return $result;
}

/**
 * 获取文章永久链接
 * 
 * @param int $cid 文章ID
 * @return string 文章链接
 */
function get_permalink($cid) {
    try {
        // 获取系统选项
        $options = Helper::options();
        
        // 获取文章数据
        $db = Typecho_Db::get();
        $post = $db->fetchRow($db->select()
            ->from('table.contents')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'publish'));
            
        if (!$post) {
            return '';
        }
        
        // 使用Typecho内置的方法构建链接
        if ($options->rewrite) {
            // 伪静态已启用
            return rtrim($options->siteUrl, '/') . '/archives/' . $cid . '/';
        } else {
            // 伪静态未启用
            return rtrim($options->siteUrl, '/') . '/index.php/archives/' . $cid . '/';
        }
    } catch (Exception $e) {
        // 出现异常时使用最简单的方式
        $options = Helper::options();
        return $options->siteUrl . '?cid=' . $cid;
    }
}

 