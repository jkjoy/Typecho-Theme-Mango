<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
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
    // 获取默认缩略图URL（用于图片加载失败时）
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg';
    $custom_thumbnail = Helper::options()->thumbUrl ?? '';
    if (!empty($custom_thumbnail)) {
        $default_thumbnail = $custom_thumbnail;
    }

    $imgUrl = (string)$imgUrl;
    if ($imgUrl === '') {
        return $default_thumbnail;
    }

    // JSON翻页时不走 TimThumb（减少服务端额外处理）
    if (function_exists('mango_is_json_pagination_request') && mango_is_json_pagination_request()) {
        return $imgUrl ?: $default_thumbnail;
    }

    // 非本站图片不走 TimThumb（TimThumb 默认也应禁用外链）
    if (function_exists('mango_is_external_url') && mango_is_external_url($imgUrl)) {
        return $imgUrl ?: $default_thumbnail;
    }

    $scheme = strtolower((string)parse_url((string)$imgUrl, PHP_URL_SCHEME));
    if ($scheme !== '' && $scheme !== 'http' && $scheme !== 'https') {
        return $imgUrl ?: $default_thumbnail;
    }

    // 尽量把 URL 映射成站内相对路径（相对 __TYPECHO_ROOT_DIR__），供 TimThumb 读取本地文件
    $relativePath = null;
    if (function_exists('mango_local_path_from_url')) {
        $localPath = mango_local_path_from_url($imgUrl);
        if ($localPath && is_file($localPath)) {
            $root = rtrim(str_replace('\\', '/', __TYPECHO_ROOT_DIR__), '/');
            $localPathNorm = str_replace('\\', '/', $localPath);
            if (stripos($localPathNorm, $root . '/') === 0) {
                $relativePath = substr($localPathNorm, strlen($root));
            }
        }
    }

    if ($relativePath === null) {
        $path = (string)parse_url($imgUrl, PHP_URL_PATH);
        if ($path !== '') {
            // 兼容站点装在子目录：去掉 siteUrl 的 base path
            $siteBasePath = (string)parse_url((string)Helper::options()->siteUrl, PHP_URL_PATH);
            $siteBasePath = rtrim($siteBasePath, '/');
            if ($siteBasePath !== '' && strncmp($path, $siteBasePath, strlen($siteBasePath)) === 0) {
                $path = substr($path, strlen($siteBasePath));
            }
            $relativePath = '/' . ltrim(rawurldecode($path), '/');
        }
    }

    if ($relativePath === null || $relativePath === '/') {
        return $imgUrl ?: $default_thumbnail;
    }

    $width = 400;
    $height = 400;
    $zc = 1;
    $q = 85;

    $timthumb = rtrim((string)Helper::options()->themeUrl, '/') . '/timthumb.php';
    $srcParam = '/' . ltrim((string)$relativePath, '/');
    $encodedSrc = mango_timthumb_encode_src($srcParam);

    return $timthumb . '?src=' . rawurlencode($encodedSrc) . '&w=' . (int)$width . '&h=' . (int)$height . '&zc=' . (int)$zc . '&q=' . (int)$q;
}

/**
 * 生成 TimThumb 缩略图 URL（可供模板直接使用）
 *
 * @param string $imgUrl 原始图片URL（建议站内）
 * @param int $width 宽
 * @param int $height 高
 * @param int $zc 裁切模式（TimThumb zc）
 * @param int $q 质量（TimThumb q）
 * @return string
 */
function mango_timthumb_url($imgUrl, $width = 400, $height = 400, $zc = 1, $q = 85)
{
    $default_thumbnail = Helper::options()->themeUrl . '/assets/img/nopic.svg';
    $custom_thumbnail = Helper::options()->thumbUrl ?? '';
    if (!empty($custom_thumbnail)) {
        $default_thumbnail = $custom_thumbnail;
    }

    $imgUrl = (string)$imgUrl;
    if ($imgUrl === '') {
        return $default_thumbnail;
    }
    if (function_exists('mango_is_external_url') && mango_is_external_url($imgUrl)) {
        return $imgUrl;
    }

    $path = (string)parse_url($imgUrl, PHP_URL_PATH);
    if ($path === '') {
        return $imgUrl;
    }

    $siteBasePath = (string)parse_url((string)Helper::options()->siteUrl, PHP_URL_PATH);
    $siteBasePath = rtrim($siteBasePath, '/');
    if ($siteBasePath !== '' && strncmp($path, $siteBasePath, strlen($siteBasePath)) === 0) {
        $path = substr($path, strlen($siteBasePath));
    }

    $timthumb = rtrim((string)Helper::options()->themeUrl, '/') . '/timthumb.php';
    $srcParam = '/' . ltrim(rawurldecode($path), '/');
    $encodedSrc = mango_timthumb_encode_src($srcParam);

    return $timthumb . '?src=' . rawurlencode($encodedSrc) . '&w=' . (int)$width . '&h=' . (int)$height . '&zc=' . (int)$zc . '&q=' . (int)$q;
}

/**
 * TimThumb(本仓库版本)的 src 参数需要 base64url 编码（仅对本地文件路径）。
 *
 * @param string $srcPath 形如 /usr/uploads/xxx.jpg
 * @return string
 */
function mango_timthumb_encode_src($srcPath)
{
    $srcPath = (string)$srcPath;
    $b64 = base64_encode($srcPath);
    return str_replace(array('+', '/', '='), array('-', '_', ''), $b64);
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
