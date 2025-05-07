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
    $slidePosts = new Typecho_Widget_Helper_Form_Element_Text(
        'slidePosts',
        NULL,
        NULL,
        _t('幻灯片文章'),
        _t('输入文章的 CID，多个请用英文逗号或空格分隔，如：1,2,3 或 1 2 3')
    );
    $form->addInput($slidePosts);   
    $cnavatar = new Typecho_Widget_Helper_Form_Element_Text('cnavatar', NULL, NULL, _t('Gravatar镜像'), _t('默认https://cravatar.cn/avatar/'));
    $form->addInput($cnavatar);
    $icpbeian = new Typecho_Widget_Helper_Form_Element_Text('icpbeian', NULL, NULL, _t('备案号码'), _t('不填写则不显示'));
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
            'ShowHotPosts'       => _t('显示热门文章'),
            'ShowTags'           => _t('显示标签'),
            'ShowOther'          => _t('显示其它杂项')
        ],
        ['ShowRecentPosts', 'ShowRecentComments', 'ShowHotPosts', 'ShowTags', 'ShowOther'],
        _t('侧边栏显示')
    );
    $darkMode = new Typecho_Widget_Helper_Form_Element_Radio(
        'darkMode',
        array(
            'auto' => '自动跟随系统',
            'light' => '始终浅色',
            'dark' => '始终深色'
        ),
        'auto',
        '显示模式',
        '选择站点外观模式。'
    );
    $form->addInput($darkMode);
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
$options = Typecho_Widget::widget('Widget_Options');
$gravatarPrefix = empty($options->cnavatar) ? 'https://cravatar.cn/avatar/' : $options->cnavatar;
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
 * 获取用户文章总数
 */
function getPostsCount() {
    $db = Typecho_Db::get();
    return $db->fetchObject($db->select(array('COUNT(cid)' => 'num'))
        ->from('table.contents')
        ->where('type = ?', 'post')
        ->where('status = ?', 'publish'))->num;
}

/**
 * 获取文章缩略图和所有图片
 * 
 * @param object|array $post 文章对象或数组
 * @return array 包含缩略图URL、所有图片数组(最多9张)和实际图片总数
 */
function get_post_thumbnail($post) {
    if (is_array($post)) $post = (object)$post;
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg'; 
    $result = array(
        'thumbnail' => $default_thumbnail,
        'images' => array(),
        'cropped_images' => array(), // 新增
        'count' => 0,
        'total_count' => 0 
    );  
    if (!$post) return $result;
    $theme_dir = basename(dirname(__FILE__));
    $content = '';
    if (!empty($post->text)) $content = $post->text;
    else if (!empty($post->content)) $content = $post->content;
    else if (method_exists($post, 'content') && is_callable([$post, 'content'])) $content = $post->content();
    $images = array();
    if (!empty($content)) {
        preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $html_matches);
        if (!empty($html_matches[1])) {
            foreach ($html_matches[1] as $img_url) {
                if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                    $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
                }
                $images[] = $img_url;
            }
        }
        // Markdown
        preg_match_all('/!\[([^\]]*)\]\(([^\)]+)\)/i', $content, $md_matches);
        if (!empty($md_matches[2])) {
            foreach ($md_matches[2] as $img_url) {
                if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                    $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
                }
                $images[] = $img_url;
            }
        }
        // URL直链
        preg_match_all('/(https?:\/\/[^\s<>\"\']*?\.(?:jpg|jpeg|png|gif|webp))(\?[^\s<>\"\']*)?/i', $content, $url_matches);
        if (!empty($url_matches[1])) {
            $images = array_merge($images, $url_matches[1]);
        }
        // 去重
        $images = array_unique($images);
        $images = array_values($images);
        $total_count = count($images);
        if (count($images) > 9) {
            $thumbnail = $images[0];
            $images = array_slice($images, 0, 9);
            if (!in_array($thumbnail, $images)) {
                $images[8] = $thumbnail;
            }
        }
        $cropped_images = array();
        foreach ($images as $img) {
            $cropped_images[] = get_thumb($img, $theme_dir);
        }
        $result['images'] = $images;
        $result['cropped_images'] = $cropped_images;
        $result['count'] = count($images);
        $result['total_count'] = $total_count;
        if (!empty($images)) {
            $result['thumbnail'] = $images[0];
        }
    }
    return $result;
}

// -- 裁剪缩略图函数 --
function get_thumb($imgUrl, $theme_dir) {
    $upload_dir = __DIR__ . '/thumbnails/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $hash = md5($imgUrl);
    $thumbnail_path = $upload_dir . $hash . '.jpg';
    $thumbnail_url = Helper::options()->themeUrl . '/thumbnails/' . $hash . '.jpg';
    if (file_exists($thumbnail_path)) {
        return $thumbnail_url;
    }
    $img_data = @file_get_contents($imgUrl);
    if ($img_data === false) return $imgUrl;
    $src = @imagecreatefromstring($img_data);
    if (!$src) return $imgUrl;
    $width = imagesx($src);
    $height = imagesy($src);
    $min = max(300, min($width, $height));
    $size = $min;
    $src_x = ($width - $size) / 2;
    $src_y = ($height - $size) / 2;
    $thumb = imagecreatetruecolor($size, $size);
    imagecopyresampled($thumb, $src, 0, 0, $src_x, $src_y, $size, $size, $size, $size);
    imagejpeg($thumb, $thumbnail_path, 90);
    imagedestroy($src);
    imagedestroy($thumb);
    return $thumbnail_url;
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
        // 获取文章对象
        $db = Typecho_Db::get();
        $post = $db->fetchRow($db->select()
            ->from('table.contents')
            ->where('cid = ?', $cid)
            ->where('status = ?', 'publish'));   
        if (!$post) {
            return '';
        }
        // 构造文章对象
        $post['type'] = 'post'; // 确保类型为文章
        $post = Typecho_Widget::widget('Widget_Abstract_Contents')->filter($post);   
        // 使用文章对象的 permalink 方法生成链接
        return $post['permalink'];
    } catch (Exception $e) {
        // 出现异常时使用最简单的方式
        $options = Helper::options();
        return $options->siteUrl . '?cid=' . $cid;
    }
}

/**    
 * 评论者认证等级 + 身份    
 *    
 * @author Chrison    
 * @access public    
 * @param str $email 评论者邮址    
 * @return result     
 */     
function commentApprove($widget, $email = NULL)      
{   
    $result = array(
        "state" => -1,//状态
        "isAuthor" => 0,//是否是博主
        "userLevel" => '',//用户身份或等级名称
        "userDesc" => '',//用户title描述
        "bgColor" => '',//用户身份或等级背景色
        "commentNum" => 0//评论数量
    );
    if (empty($email)) return $result;       
    $result['state'] = 1;
    $master = array(      
        '基友邮箱1@qq.com',
        '基友邮箱1@qq.com'
    );      
    if ($widget->authorId == $widget->ownerId) {      
        $result['isAuthor'] = 1;//」
        $result['userLevel'] = '「博主」<i class="bi bi-award-fill"></i>';
        $result['userDesc'] = '本站站长';
        $result['bgColor'] = '#FFD67A';
        $result['commentNum'] = 999;
    } else if (in_array($email, $master)) {      
        $result['userLevel'] = '「基友」';
        $result['userDesc'] = '好基友';
        $result['bgColor'] = '#65C186';
        $result['commentNum'] = 888;
    } else {
        //数据库获取
        $db = Typecho_Db::get();
        //获取评论条数
        $commentNumSql = $db->fetchAll($db->select(array('COUNT(cid)'=>'commentNum'))
            ->from('table.comments')
            ->where('mail = ?', $email));
        $commentNum = $commentNumSql[0]['commentNum'];    
        //获取友情链接
        $linkSql = $db->fetchAll($db->select()->from('table.links')
            ->where('user = ?',$email));       
        //等级判定
        if($commentNum==1){
            $result['userLevel'] = '「初见」<i class="bi bi-0-circle"></i>';
            $result['bgColor'] = '#999999';
            $userDesc = '人生一大步！';
        } else {
            if ($commentNum<10 && $commentNum>1) {
                $result['userLevel'] = '「初识」<i class="bi bi-1-circle"></i>';
                $result['bgColor'] = '#999999';
            }elseif ($commentNum<20 && $commentNum>=10) {
                $result['userLevel'] = '「相识」<i class="bi bi-2-circle"></i>';
                $result['bgColor'] = '#A0DAD0';
            }elseif ($commentNum<40 && $commentNum>=20) {
                $result['userLevel'] = '「熟识」<i class="bi bi-3-circle"></i>';
                $result['bgColor'] = '#A0DAD0';
            }elseif ($commentNum<80 && $commentNum>=40) {
                $result['userLevel'] = '「好友」<i class="bi bi-4-circle"></i>';
                $result['bgColor'] = '#A0DAD0';
            }elseif ($commentNum<160 && $commentNum>=80) {
                $result['userLevel'] = '「知己」<i class="bi bi-5-circle"></i>';
                $result['bgColor'] = '#A0DAD0';
            }elseif ($commentNum>=160) {
                $result['userLevel'] = '「挚友」<i class="bi bi-6-circle"></i>';
                $result['bgColor'] = '#A0DAD0';
            }
             $userDesc = '您在本站有'.$commentNum.'条留言！'; 
        }
        if($linkSql){
            $result['userLevel'] = '「博友」';
            $result['bgColor'] = '#21b9bb';
            $userDesc = '🔗'.$linkSql[0]['description'].'&#10;✌️'.$userDesc;
        }
        
        $result['userDesc'] = $userDesc;
        $result['commentNum'] = $commentNum;
    } 
    return $result;
}

/***
 * 在线状态
 */
function get_last_login($user){
    $user   = '1'; 
    $now    = time();
    $db     = Typecho_Db::get();
    $prefix = $db->getPrefix();
    $row = $db->fetchRow($db->select('activated')->from('table.users')->where('uid = ?', $user));
    if ($row) {
        echo Typecho_I18n::dateWord($row['activated'], $now);
    } else {
        echo '博主一直在这里';
    }
}

/**
 * 生成页面图标的函数
 */
function pageIcon($slug, $title) {
    $icon = '';
    if ($slug == 'memos') {
        $icon = '<i class="bi bi-chat-fill me-1"></i>';
    } elseif ($slug == 'links') {
        $icon = '<i class="bi bi-folder-symlink-fill me-1"></i>';
    } elseif ($slug == 'tags') {
        $icon = '<i class="bi bi-tags-fill me-1"></i>';
    } elseif ($slug == 'categories') {
        $icon = '<i class="bi bi-folder-fill me-1"></i>';
    } elseif ($slug == 'comments') {
        $icon = '<i class="bi bi-chat-dots-fill me-1"></i>';
    } elseif ($slug == 'gbook') {
        $icon = '<i class="bi bi-cloud-arrow-up-fill me-1"></i>';
    } elseif ($slug == 'search') {
        $icon = '<i class="bi bi-search me-1"></i>';
    } elseif ($slug == 'archives') {
        $icon = '<i class="bi bi-calendar-heart-fill me-1"></i>';
    } elseif ($slug == 'tools') {
        $icon = '<i class="bi bi-tools me-1"></i>';
    } elseif ($slug == 'help') {
        $icon = '<i class="bi bi-question-circle-fill me-1"></i>';
    } elseif ($slug == 'about') {
        $icon = '<i class="bi bi-info-circle-fill me-1"></i>';
    } 
    return $icon . $title;
}

/**
 * 获取幻灯片文章
 */
function getSlidesPosts() {
    $slides = Helper::options()->slidePosts;
    if (empty($slides)) {
        return array();
    }
    $cids = preg_split('/[,\s]+/', $slides);
    $cids = array_map('intval', $cids);
    $cids = array_filter($cids);
    if (empty($cids)) {
        return array();
    }
    // 查询文章
    $db = Typecho_Db::get();
    try {
        // 构建查询
        $posts = $db->fetchAll($db->select()
            ->from('table.contents')
            ->where('cid IN ?', $cids)
            ->where('status = ?', 'publish')
            ->where('type = ?', 'post'));
        $postsMap = array();
        foreach ($posts as $post) {
            $postsMap[$post['cid']] = $post;
        }
        $sortedPosts = array();
        foreach ($cids as $cid) {
            if (isset($postsMap[$cid])) {
                $sortedPosts[] = $postsMap[$cid];
            }
        }
        return array_map(function($post) {
            return Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
        }, $sortedPosts);    
    } catch (Exception $e) {
        return array();
    }
}

/**
 * Typecho后台附件增强：图片预览、批量插入、保留官方删除按钮与逻辑
 * @author jkjoy
 * @date 2025-04-25
 */
Typecho_Plugin::factory('admin/write-post.php')->bottom = array('AttachmentHelper', 'addEnhancedFeatures');
Typecho_Plugin::factory('admin/write-page.php')->bottom = array('AttachmentHelper', 'addEnhancedFeatures');

class AttachmentHelper {
    public static function addEnhancedFeatures() {
        ?>
        <style>
        #file-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;padding:15px;list-style:none;margin:0;}
        #file-list li{position:relative;border:1px solid #e0e0e0;border-radius:4px;padding:10px;background:#fff;transition:all 0.3s ease;list-style:none;margin:0;}
        #file-list li:hover{box-shadow:0 2px 8px rgba(0,0,0,0.1);}
        #file-list li.loading{opacity:0.7;pointer-events:none;}
        .att-enhanced-thumb{position:relative;width:100%;height:150px;margin-bottom:8px;background:#f5f5f5;overflow:hidden;border-radius:3px;display:flex;align-items:center;justify-content:center;}
        .att-enhanced-thumb img{width:100%;height:100%;object-fit:contain;display:block;}
        .att-enhanced-thumb .file-icon{display:flex;align-items:center;justify-content:center;width:100%;height:100%;font-size:40px;color:#999;}
        .att-enhanced-finfo{padding:5px 0;}
        .att-enhanced-fname{font-size:13px;margin-bottom:5px;word-break:break-all;color:#333;}
        .att-enhanced-fsize{font-size:12px;color:#999;}
        .att-enhanced-factions{display:flex;justify-content:space-between;align-items:center;margin-top:8px;gap:8px;}
        .att-enhanced-factions button{flex:1;padding:4px 8px;border:none;border-radius:3px;background:#e0e0e0;color:#333;cursor:pointer;font-size:12px;transition:all 0.2s ease;}
        .att-enhanced-factions button:hover{background:#d0d0d0;}
        .att-enhanced-factions .btn-insert{background:#467B96;color:white;}
        .att-enhanced-factions .btn-insert:hover{background:#3c6a81;}
        .att-enhanced-checkbox{position:absolute;top:5px;right:5px;z-index:2;width:18px;height:18px;cursor:pointer;}
        .batch-actions{margin:15px;display:flex;gap:10px;align-items:center;}
        .btn-batch{padding:8px 15px;border-radius:4px;border:none;cursor:pointer;transition:all 0.3s ease;font-size:10px;display:inline-flex;align-items:center;justify-content:center;}
        .btn-batch.primary{background:#467B96;color:white;}
        .btn-batch.primary:hover{background:#3c6a81;}
        .btn-batch.secondary{background:#e0e0e0;color:#333;}
        .btn-batch.secondary:hover{background:#d0d0d0;}
        .upload-progress{position:absolute;bottom:0;left:0;width:100%;height:2px;background:#467B96;transition:width 0.3s ease;}
        </style>
        <script>
        $(document).ready(function() {
            // 批量操作UI按钮
            var $batchActions = $('<div class="batch-actions"></div>')
                .append('<button type="button" class="btn-batch primary" id="batch-insert">批量插入</button>')
                .append('<button type="button" class="btn-batch secondary" id="select-all">全选</button>')
                .append('<button type="button" class="btn-batch secondary" id="unselect-all">取消全选</button>');
            $('#file-list').before($batchActions);

            // 插入格式
            Typecho.insertFileToEditor = function(title, url, isImage) {
                var textarea = $('#text'), 
                    sel = textarea.getSelection(),
                    insertContent = isImage ? '![' + title + '](' + url + ')' : 
                                            '[' + title + '](' + url + ')';
                textarea.replaceSelection(insertContent + '\n');
                textarea.focus();
            };
            // 批量插入
            $('#batch-insert').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var content = '';
                $('#file-list li').each(function() {
                    if ($(this).find('.att-enhanced-checkbox').is(':checked')) {
                        var $li = $(this);
                        var title = $li.find('.att-enhanced-fname').text();
                        var url = $li.data('url');
                        var isImage = $li.data('image') == 1;
                        content += isImage ? '![' + title + '](' + url + ')\n' : '[' + title + '](' + url + ')\n';
                    }
                });
                if (content) {
                    var textarea = $('#text');
                    var pos = textarea.getSelection();
                    var newContent = textarea.val();
                    newContent = newContent.substring(0, pos.start) + content + newContent.substring(pos.end);
                    textarea.val(newContent);
                    textarea.focus();
                }
            });
            $('#select-all').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#file-list .att-enhanced-checkbox').prop('checked', true);
                return false;
            });
            $('#unselect-all').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#file-list .att-enhanced-checkbox').prop('checked', false);
                return false;
            });
            // 防止复选框冒泡
            $(document).on('click', '.att-enhanced-checkbox', function(e) {e.stopPropagation();});
            // 增强文件列表样式，但不破坏li原结构和官方按钮
            function enhanceFileList() {
                $('#file-list li').each(function() {
                    var $li = $(this);
                    if ($li.hasClass('att-enhanced')) return;
                    $li.addClass('att-enhanced');
                    // 只增强，不清空li
                    // 增加批量选择框
                    if ($li.find('.att-enhanced-checkbox').length === 0) {
                        $li.prepend('<input type="checkbox" class="att-enhanced-checkbox" />');
                    }
                    // 增加图片预览（如已有则不重复加）
                    if ($li.find('.att-enhanced-thumb').length === 0) {
                        var url = $li.data('url');
                        var isImage = $li.data('image') == 1;
                        var fileName = $li.find('.insert').text();
                        var $thumbContainer = $('<div class="att-enhanced-thumb"></div>');
                        if (isImage) {
                            var $img = $('<img src="' + url + '" alt="' + fileName + '" />');
                            $img.on('error', function() {
                                $(this).replaceWith('<div class="file-icon">🖼️</div>');
                            });
                            $thumbContainer.append($img);
                        } else {
                            $thumbContainer.append('<div class="file-icon">📄</div>');
                        }
                        // 插到插入按钮之前
                        $li.find('.insert').before($thumbContainer);
                    }
                });
            }
            // 插入按钮事件
            $(document).on('click', '.btn-insert', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $li = $(this).closest('li');
                var title = $li.find('.att-enhanced-fname').text();
                Typecho.insertFileToEditor(title, $li.data('url'), $li.data('image') == 1);
            });
            // 上传完成后增强新项
            var originalUploadComplete = Typecho.uploadComplete;
            Typecho.uploadComplete = function(attachment) {
                setTimeout(function() {
                    enhanceFileList();
                }, 200);
                if (typeof originalUploadComplete === 'function') {
                    originalUploadComplete(attachment);
                }
            };
            // 首次增强
            enhanceFileList();
        });
        </script>
        <?php
    }
}
?>