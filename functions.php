<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 自动检查主题更新
 */
function themeAutoUpgradeNotice()
{
    // 1. 定义当前主题版本 
    $current_version = '1.2.2';

    // 2. 定义 GitHub API 地址
    $api_url = 'https://api.github.com/repos/jkjoy/typecho-theme-mango/releases/latest';

    // 3. 设置缓存，避免每次请求都调用 API，减轻服务器压力
    // 使用主题目录下的缓存文件，确保有写入权限
    $cache_dir = __TYPECHO_ROOT_DIR__ . '/usr/cache';
    $cache_file = $cache_dir . '/version.json';
    $cache_time = 12 * 3600; // 缓存12小时

    // 确保缓存目录存在
    if (!file_exists($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    $latest_version = null;
    
    // 检查缓存文件是否存在且未过期
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if ($cache_data && isset($cache_data['tag_name'])) {
            $latest_version = $cache_data['tag_name'];
        }
    } else {
        // 缓存过期或不存在，重新请求 API
        $ctx = stream_context_create([
            'http' => [
                'header' => 'User-Agent: Typecho-Theme-Updater', // GitHub API 要求有 User-Agent
                'timeout' => 10 // 设置超时时间
            ]
        ]);
        
        $response = @file_get_contents($api_url, false, $ctx);

        if ($response) {
            $release_data = json_decode($response, true);
            if (isset($release_data['tag_name'])) {
                $latest_version = $release_data['tag_name'];
                // 更新缓存文件
                $result = file_put_contents($cache_file, json_encode(['tag_name' => $latest_version, 'time' => time()]));
                // 如果缓存写入失败，记录错误但不影响显示
                if (!$result) {
                    error_log('Failed to write upgrade cache to ' . $cache_file);
                }
            }
        } else {
            // API请求失败，记录错误
            error_log('Failed to fetch release data from ' . $api_url);
            // 如果有旧缓存，使用旧缓存数据
            if (file_exists($cache_file)) {
                $cache_data = json_decode(file_get_contents($cache_file), true);
                if ($cache_data && isset($cache_data['tag_name'])) {
                    $latest_version = $cache_data['tag_name'];
                }
            }
        }
    }
    // 4. 如果获取到了最新版本，则进行比较
    if ($latest_version && version_compare($current_version, $latest_version, '<')) {
        
        $notice_html = '
        <span class="themeConfig"><h3>主题更新</h3>
            <div class="info">发现新版本 ' . $latest_version . '，您当前使用的是 ' . $current_version . '。建议立即更新以获得最新功能和安全性修复。
                <a href="https://github.com/jkjoy/typecho-theme-mango/releases/latest" target="_blank">查看更新</a>
                <a href="https://github.com/jkjoy/typecho-theme-mango/releases" target="_blank">立即下载</a>
            </div>';
        echo $notice_html;
    }
}

/**
 * 主题配置项
 */

function themeConfig($form)
{
    echo '<style>.typecho-page-title h2 {font-weight: 600;color: #30ac9aff;}.typecho-page-title h2:before {content: "#";margin-right: 6px;color: #30ac9aff; font-size: 20px;font-weight: 600;}.themeConfig h3 {color: #30ac9aff;font-size: 20px;}.themeConfig h3:before {content: "[";margin-right: 5px;color: #cde51bff;font-size: 25px;}.themeConfig h3:after {content: "]";margin-left: 5px;color: #cde51bff;font-size: 25px;}.info{border: 1px solid #ffadad;padding: 20px;margin: -15px 10px 25px 0;background: #ffffff;border-radius: 5px;color: #e5ac1bff;}</style>';
    // 直接在主题设置页面调用更新检查
    themeAutoUpgradeNotice();
    echo '<span class="themeConfig"><h3>博客设置</h3></span>';
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CgogIDwhLS0g6IqS5p6c5Li75L2TIC0tPgogIDxwYXRoIGQ9Ik0xNTAgMzAKICAgICAgIEM4MCAzMCwgNDAgOTAsIDQwIDE1MAogICAgICAgQzQwIDIyMCwgMTEwIDI2MCwgMTcwIDI1MAogICAgICAgQzIzMCAyNDAsIDI2MCAxOTAsIDI1MCAxNDAKICAgICAgIEMyNDAgOTAsIDIwMCAzMCwgMTUwIDMwIFoiIGZpbGw9IiNGRkM5MzMiIHN0cm9rZT0iI0U2QTgwMCIgc3Ryb2tlLXdpZHRoPSI0Ij48L3BhdGg+CgogIDwhLS0g6auY5YWJIC0tPgogIDxwYXRoIGQ9Ik0xMTAgNzAKICAgICAgIEM4MCAxMDAsIDcwIDE0MCwgODAgMTgwCiAgICAgICBDODUgMjAwLCAxMDUgMjE1LCAxMTUgMjIwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC41KSIgc3Ryb2tlLXdpZHRoPSI2IiBzdHJva2UtbGluZWNhcD0icm91bmQiPjwvcGF0aD4KCiAgPCEtLSDlj7blrZAgLS0+CiAgPHBhdGggZD0iTTE3MCAyMAogICAgICAgQzIwMCAtMTAsIDI1MCAtNSwgMjYwIDIwCiAgICAgICBDMjMwIDMwLCAyMDAgNDAsIDE3MCAyMCBaIiBmaWxsPSIjMkU4QjU3Ij48L3BhdGg+CgogIDwhLS0g5Y+26ISJIC0tPgogIDxwYXRoIGQ9Ik0xNzUgMjAgQzIxMCAxNSwgMjM1IDIwLCAyNTUgMjIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzFGNkY0MyIgc3Ryb2tlLXdpZHRoPSIyIj48L3BhdGg+Cjwvc3ZnPgo=',
        _t('<div class="info">全局设置</div>站点 LOGO 地址'),
        _t('在这里填入一个图片 URL 地址, 以在网站标题前加上一个 LOGO')
    );
    $form->addInput($logoUrl);
    $faviconUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'faviconUrl',
        null,
        'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CgogIDwhLS0g6IqS5p6c5Li75L2TIC0tPgogIDxwYXRoIGQ9Ik0xNTAgMzAKICAgICAgIEM4MCAzMCwgNDAgOTAsIDQwIDE1MAogICAgICAgQzQwIDIyMCwgMTEwIDI2MCwgMTcwIDI1MAogICAgICAgQzIzMCAyNDAsIDI2MCAxOTAsIDI1MCAxNDAKICAgICAgIEMyNDAgOTAsIDIwMCAzMCwgMTUwIDMwIFoiIGZpbGw9IiNGRkM5MzMiIHN0cm9rZT0iI0U2QTgwMCIgc3Ryb2tlLXdpZHRoPSI0Ij48L3BhdGg+CgogIDwhLS0g6auY5YWJIC0tPgogIDxwYXRoIGQ9Ik0xMTAgNzAKICAgICAgIEM4MCAxMDAsIDcwIDE0MCwgODAgMTgwCiAgICAgICBDODUgMjAwLCAxMDUgMjE1LCAxMTUgMjIwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC41KSIgc3Ryb2tlLXdpZHRoPSI2IiBzdHJva2UtbGluZWNhcD0icm91bmQiPjwvcGF0aD4KCiAgPCEtLSDlj7blrZAgLS0+CiAgPHBhdGggZD0iTTE3MCAyMAogICAgICAgQzIwMCAtMTAsIDI1MCAtNSwgMjYwIDIwCiAgICAgICBDMjMwIDMwLCAyMDAgNDAsIDE3MCAyMCBaIiBmaWxsPSIjMkU4QjU3Ij48L3BhdGg+CgogIDwhLS0g5Y+26ISJIC0tPgogIDxwYXRoIGQ9Ik0xNzUgMjAgQzIxMCAxNSwgMjM1IDIwLCAyNTUgMjIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzFGNkY0MyIgc3Ryb2tlLXdpZHRoPSIyIj48L3BhdGg+Cjwvc3ZnPgo=',
        _t('站点 favicon 地址'),
        _t('在这里填入一个图片 URL 地址, 以在浏览器标签页的网站标题前加上一个 favicon')
    );
    $form->addInput($faviconUrl); 
    $thumbUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'thumbUrl',
        null,
        'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MDAgMzUwIiB3aWR0aD0iNjAwIiBoZWlnaHQ9IjM1MCI+CiAgPHJlY3Qgd2lkdGg9IjYwMCIgaGVpZ2h0PSIzNTAiIGZpbGw9IiNjY2NjY2MiPjwvcmVjdD4KICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9Im1vbm9zcGFjZSIgZm9udC1zaXplPSIyNnB4IiBmaWxsPSIjMzMzMzMzIj7mmoLml6Dlm77niYc8L3RleHQ+ICAgCjwvc3ZnPg==',
        _t('默认文章缩略图地址'),
        _t('默认的文章缩略图地址')
    );    
    $form->addInput($thumbUrl); 
    $cnavatar = new Typecho_Widget_Helper_Form_Element_Text('cnavatar', NULL, NULL, _t('Gravatar镜像'), _t('当头像显示异常时填写,默认使用https://cravatar.cn/avatar/'));
    $form->addInput($cnavatar);
    $darkMode = new Typecho_Widget_Helper_Form_Element_Radio(
        'darkMode',
        array(
            'auto' => '自动切换',
            'light' => '始终浅色',
            'dark' => '始终深色'
        ),
        'auto',
        '显示模式',
        '选择站点外观模式。'
    );
    $form->addInput($darkMode);
    $loadmore = new Typecho_Widget_Helper_Form_Element_Radio('loadmore', ['0' => _t('页码模式'), '1' => _t('加载更多')], '0', _t('文章列表加载模式'), _t(' '));
    $form->addInput($loadmore);
    $slidePosts = new Typecho_Widget_Helper_Form_Element_Text(
        'slidePosts',
        NULL,
        NULL,
        _t('<span class="themeConfig"><h3>推荐位设置</h3></span><div class="info">幻灯片展示</div>推荐位文章 CID'),
        _t('输入文章的 CID，多个请用英文逗号或空格分隔，如：1,2,3 或 1 2 3')
    );
    $form->addInput($slidePosts);   
    $icpbeian = new Typecho_Widget_Helper_Form_Element_Text('icpbeian', NULL, NULL, _t('<span class="themeConfig"><h3>底部设置</h3></span><div class="info">网站底部信息设置</div>备案号码'), _t('不填写则不显示'));
    $form->addInput($icpbeian);
    $showlinks = new Typecho_Widget_Helper_Form_Element_Radio('showlinks', ['0' => _t('不显示'), '1' => _t('显示')], '0', _t('首页底部链接'), _t('是否展示友情链接,需要启用links插件'));
    $form->addInput($showlinks);
    $tongji = new Typecho_Widget_Helper_Form_Element_Textarea('tongji', NULL, NULL, _t('自定义页脚内容'), _t('支持HTML语法，可用于添加第三方统计代码'));
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
        _t('<span class="themeConfig"><h3>侧边栏设置</h3></span><div class="info">侧边栏显示模块选择</div>侧边栏模块')
    );
    $form->addInput($sidebarBlock->multiMode());
}

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
 * 自定义字段
 */
function themeFields($layout) {
    $summary= new Typecho_Widget_Helper_Form_Element_Textarea('summary', NULL, NULL, _t('文章摘要'), _t('自定义摘要'));
    $layout->addItem($summary);
    $likes= new Typecho_Widget_Helper_Form_Element_Text('likes', NULL, NULL, _t('点赞数'), _t('文章点赞数'));
    $layout->addItem($likes);
}

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

/**
 * 处理文章点赞
*/
if (isset($_POST['action']) && $_POST['action'] == 'specs_zan') {
    handlePostLike();
}

function handlePostLike() {
    try {
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
    } catch (Exception $e) {
        error_log('Error in handlePostLike: ' . $e->getMessage());
        echo 0;
        exit;
    }
}

/**
 * 获取用户文章总数
 */
function getPostsCount() {
    try {
        $db = Typecho_Db::get();
        return $db->fetchObject($db->select(array('COUNT(cid)' => 'num'))
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish'))->num;
    } catch (Exception $e) {
        error_log('Error in getPostsCount: ' . $e->getMessage());
        return 0;
    }
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
    // 从主题设置中获取自定义缩略图（后台填写的默认地址）
    $custom_thumbnail = Helper::options()->thumbUrl ?? ''; 
    // 使用自定义缩略图（如果已设置）
    if (!empty($custom_thumbnail)) {
        $default_thumbnail = $custom_thumbnail;
    }
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

/**
 * 生成缩略图
 * 
 * @param string $imgUrl 原始图片URL
 * @param array $options 配置选项
 * @return string 缩略图URL
 */
function get_thumb($imgUrl, $options) {
    $theme_dir = basename(dirname(__FILE__));
    $upload_dir = __DIR__ . '/thumbnails/';
    // 获取默认缩略图URL（用于图片加载失败时）
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg';
    $custom_thumbnail = Helper::options()->thumbUrl ?? '';
    if (!empty($custom_thumbnail)) {
        $default_thumbnail = $custom_thumbnail;
    }
    // 确保缓存目录存在
    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) {
            return $default_thumbnail; // 如果无法创建目录，返回默认图片
        }
    }
    // 生成唯一文件名
    $hash = md5($imgUrl);
    $thumbnail_path = $upload_dir . $hash . '.webp';
    $thumbnail_url = Helper::options()->themeUrl . '/thumbnails/' . $hash . '.webp';
    // 如果缩略图已存在，直接返回
    if (file_exists($thumbnail_path)) {
        return $thumbnail_url;
    }
    // 获取原始图片
    $img_data = @file_get_contents($imgUrl);
    if ($img_data === false) {
        return $default_thumbnail; // 图片404或无法获取时，返回默认图片
    }
    // 创建图片资源
    $src = @imagecreatefromstring($img_data);
    if (!$src) {
        return $default_thumbnail; // 图片格式无效或无法创建资源时，返回默认图片
    }
    try {
        $width = imagesx($src);
        $height = imagesy($src);
        // 计算缩略图尺寸
        $target_ratio = 1 / 1;
        $src_ratio = $width / $height; 
        if ($src_ratio > $target_ratio) {
            $new_height = $height;
            $new_width = $height * $target_ratio;
            $src_x = ($width - $new_width) / 2;
            $src_y = 0;
        } else {
            $new_width = $width;
            $new_height = $width / $target_ratio;
            $src_x = 0;
            $src_y = ($height - $new_height) / 2;
        }
        // 计算最终尺寸
        $scale = min(400/$new_width, 400/$new_height);
        $dst_width = (int)($new_width * $scale);
        $dst_height = (int)($new_height * $scale);
        // 创建目标图像
        $thumb = imagecreatetruecolor($dst_width, $dst_height);
        // 处理透明背景
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        // 重采样
        imagecopyresampled(
            $thumb, $src,
            0, 0, $src_x, $src_y,
            $dst_width, $dst_height,
            $new_width, $new_height
        );
        // 保存缩略图
        imagewebp($thumb, $thumbnail_path, 85);
        return $thumbnail_url;
    } catch (Exception $e) {
        // 发生异常时返回默认图片
        return $default_thumbnail;
    } finally {
        // 释放资源
        if (isset($src)) {
            imagedestroy($src);
        }
        if (isset($thumb)) {
            imagedestroy($thumb);
        }
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
    //$master = array(      
    //    '基友邮箱1@qq.com',
    //    '基友邮箱1@qq.com'
    //);      
    if ($widget->authorId == $widget->ownerId) {      
        $result['isAuthor'] = 1;//」
        $result['userLevel'] = '「博主」<i class="bi bi-award-fill"></i>';
        $result['userDesc'] = '本站站长';
        $result['bgColor'] = '#FFD67A';
        $result['commentNum'] = 999;
    //} else if (in_array($email, $master)) {      
        //$result['userLevel'] = '「基友」';
        //$result['userDesc'] = '好基友';
        //$result['bgColor'] = '#65C186';
        //$result['commentNum'] = 888;
    } else {
        try {
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
        } catch (Exception $e) {
            error_log('Error in commentApprove function: ' . $e->getMessage());
            // 设置默认值
            $result['userLevel'] = '「访客」';
            $result['bgColor'] = '#999999';
            $result['userDesc'] = '欢迎留言';
            $result['commentNum'] = 0;
        }
    } 
    return $result;
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
 * 生成分类icon的函数
 */
function categoryIcon($categories) {
    $icon = '';
    if ($categories->slug == 'images') {
        $icon = '<i class="bi bi-images me-1"></i>';
    } elseif ($categories->slug == 'share') {
        $icon = '<i class="bi bi-share-fill me-1"></i>';
    } elseif ($categories->slug == 'NULL') {
        $icon = '<i class="bi bi-speaker-fill me-1"></i>';
    } elseif ($categories->slug == 'memos') {
        $icon = '<i class="bi bi-chat me-1"></i>';
    } elseif ($categories->slug == 'codes') {
        $icon = '<i class="bi bi-code me-1"></i>';
    } elseif ($categories->slug == 'diary') {
        $icon = '<i class="bi bi-journal-text me-1"></i>';
    } elseif ($categories->slug == 'logs') {
        $icon = '<i class="bi bi-person-fill me-1"></i>';
    } elseif ($categories->slug == 'test') {
        $icon = '<i class="bi bi-calendar-fill me-1"></i>';
    } elseif ($categories->slug == 'tools') {
        $icon = '<i class="bi bi-tools me-1"></i>';
    } elseif ($categories->slug == 'music') {
        $icon = '<i class="bi bi-music-note me-1"></i>';
    } elseif ($categories->slug == 'links') {
        $icon = '<i class="bi bi-link me-1"></i>';
    } elseif ($categories->slug == 'video') {
        $icon = '<i class="bi bi-camera-video me-1"></i>';
    } elseif ($categories->slug == 'books') {
        $icon = '<i class="bi bi-book me-1"></i>';
    } elseif ($categories->slug == 'games') {
        $icon = '<i class="bi bi-gamepad me-1"></i>';
    } elseif ($categories->slug == 'themes') {
        $icon = '<i class="bi bi-palette me-1"></i>';
    } elseif ($categories->slug == 'plugins') {
        $icon = '<i class="bi bi-gear-fill me-1"></i>';
    } elseif ($categories->slug == 'photo') {
        $icon = '<i class="bi bi-images me-1"></i>';
    } else {
        $icon = '<i class="bi bi-folder-fill me-1"></i>';
    }

    return $icon . $categories->name;
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
    try {
        $db = Typecho_Db::get();
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
        error_log('Error in getSlidesPosts: ' . $e->getMessage());
        return array();
    }
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