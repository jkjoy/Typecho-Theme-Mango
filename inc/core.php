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
    $theme_dir = basename(dirname(__FILE__));
    $upload_dir = __TYPECHO_ROOT_DIR__ . '/usr/cache/thumbnails/';
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
    $thumbnail_url = Helper::options()->siteUrl . 'usr/cache/thumbnails/' . $hash . '.webp';
    $fail_marker_path = $upload_dir . $hash . '.fail';
    // 如果缩略图已存在，直接返回
    if (file_exists($thumbnail_path)) {
        return $thumbnail_url;
    }
    // 失败缓存：避免对失效第三方链接反复阻塞请求
    if (file_exists($fail_marker_path) && (time() - filemtime($fail_marker_path)) < 7 * 86400) {
        return $default_thumbnail;
    }
    // JSON翻页时不做远程缩略图生成，优先快速返回（避免被第三方图片超时拖慢）
    if (function_exists('mango_is_json_pagination_request') && mango_is_json_pagination_request()) {
        return $imgUrl ?: $default_thumbnail;
    }
    // 非本站图片不生成缩略图，直接让浏览器加载原图（失败也不会阻塞后端）
    if (function_exists('mango_is_external_url') && mango_is_external_url($imgUrl)) {
        return $imgUrl ?: $default_thumbnail;
    }

    $scheme = strtolower((string)parse_url((string)$imgUrl, PHP_URL_SCHEME));
    if ($scheme !== '' && $scheme !== 'http' && $scheme !== 'https') {
        return $imgUrl ?: $default_thumbnail;
    }

    // 获取原始图片
    $img_data = false;
    if (function_exists('mango_local_path_from_url')) {
        $localPath = mango_local_path_from_url($imgUrl);
        if ($localPath && is_file($localPath)) {
            $img_data = @file_get_contents($localPath);
        }
    }
    if ($img_data === false) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3,
                'header' => "User-Agent: MangoThumb/1.0\r\nAccept: image/*\r\n",
            ],
        ]);
        $img_data = @file_get_contents($imgUrl, false, $ctx);
    }
    if ($img_data === false) {
        @touch($fail_marker_path);
        return $default_thumbnail; // 图片404或无法获取时，返回默认图片
    }
    // 创建图片资源
    $src = @imagecreatefromstring($img_data);
    if (!$src) {
        @touch($fail_marker_path);
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
        if (!@imagewebp($thumb, $thumbnail_path, 85)) {
            @touch($fail_marker_path);
            return $default_thumbnail;
        }
        return $thumbnail_url;
    } catch (Exception $e) {
        // 发生异常时返回默认图片
        @touch($fail_marker_path);
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