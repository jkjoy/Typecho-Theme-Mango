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
 * 获取文章缩略图和所有图片
 * 
 * @param object|array $post 文章对象或数组
 * @return array 包含缩略图URL、所有图片数组(最多9张)和实际图片总数
 */
function get_post_thumbnail($post) {
    // 将数组转换为对象
    if (is_array($post)) {
        $post = (object)$post;
    }
    
    // 获取默认缩略图
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg';
    
    // 初始化返回数组
    $result = array(
        'thumbnail' => $default_thumbnail,
        'images' => array(),
        'count' => 0,
        'total_count' => 0  // 新增：记录实际总数
    );
    
    // 调试信息
    error_log('Post object: ' . print_r($post, true));
    
    if (!$post) {
        error_log('No post object provided');
        return $result;
    }
    
    // 1. 获取文章内容
    $content = '';
    
    if (isset($post->text) && !empty($post->text)) {
        $content = $post->text;
    } else if (isset($post->content) && !empty($post->content)) {
        $content = $post->content;
    } else if (method_exists($post, 'content') && is_callable([$post, 'content'])) {
        $content = $post->content();
    }
    
    error_log('Article content length: ' . strlen($content));
    
    $images = array();
    
    if (!empty($content)) {
        // 2. 匹配所有HTML图片
        preg_match_all('/<img[^>]*src=[\\\'"]([^\\\'"]+)[\\\'"][^>]*>/i', $content, $html_matches);
        if (!empty($html_matches[1])) {
            foreach ($html_matches[1] as $img_url) {
                // 处理相对路径
                if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                    $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
                }
                $images[] = $img_url;
            }
        }
        
        // 3. 匹配所有Markdown格式图片
        preg_match_all('/!\[([^\]]*)\]\(([^\)]+)\)/i', $content, $md_matches);
        if (!empty($md_matches[2])) {
            foreach ($md_matches[2] as $img_url) {
                // 处理相对路径
                if (strpos($img_url, 'http') !== 0 && strpos($img_url, '//') !== 0) {
                    $img_url = Helper::options()->siteUrl . ltrim($img_url, '/');
                }
                $images[] = $img_url;
            }
        }
        
        // 4. 匹配所有直接的图片URL
        preg_match_all('/(https?:\/\/[^\s<>\"\']*?\.(?:jpg|jpeg|png|gif|webp))(\?[^\s<>\"\']*)?/i', $content, $url_matches);
        if (!empty($url_matches[1])) {
            $images = array_merge($images, $url_matches[1]);
        }
        
        // 去重
        $images = array_unique($images);
        $images = array_values($images); // 重置数组索引
        
        // 保存实际的总图片数
        $total_count = count($images);
        
        // 如果图片数量超过9张，随机选择9张
        if (count($images) > 9) {
            // 保存第一张图片作为缩略图
            $thumbnail = $images[0];
            
            // 打乱数组顺序
            shuffle($images);
            
            // 只保留9张图片
            $images = array_slice($images, 0, 9);
            
            // 确保缩略图在选中的图片中
            if (!in_array($thumbnail, $images)) {
                // 替换最后一张图片为缩略图
                $images[8] = $thumbnail;
            }
        }
        
        // 更新结果数组
        $result['images'] = $images;
        $result['count'] = count($images);
        $result['total_count'] = $total_count;  // 保存实际总数
        
        // 设置缩略图（使用第一张图片）
        if (!empty($images)) {
            $result['thumbnail'] = $images[0];
        }
    }
    
    error_log('Selected images count: ' . $result['count']);
    error_log('Total images found: ' . $result['total_count']);
    error_log('Thumbnail URL: ' . $result['thumbnail']);
    
    return $result;
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
 