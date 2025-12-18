<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; 

/**
 * 自动检查主题更新
 */
function mango_normalize_version($version)
{
    $version = trim((string)$version);
    $version = ltrim($version, "vV \t\n\r\0\x0B");

    if (preg_match('/^([0-9]+(?:\\.[0-9]+){1,3})/', $version, $m)) {
        return $m[1];
    }

    return $version;
}

function mango_get_theme_version_from_index()
{
    // 兼容 Windows/自定义目录：直接从当前主题目录读取 index.php 注释中的 @version
    $indexFile = dirname(__DIR__) . '/index.php';
    if (!is_file($indexFile)) {
        // 兜底：按 Typecho 目录规则拼接
        $theme = (string)Helper::options()->theme;
        if ($theme !== '') {
            $fallback = rtrim(__TYPECHO_ROOT_DIR__, '/\\') . rtrim(__TYPECHO_THEME_DIR__, '/\\') . '/' . $theme . '/index.php';
            if (is_file($fallback)) {
                $indexFile = $fallback;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    $content = @file_get_contents($indexFile);
    if ($content === false) {
        return null;
    }

    if (preg_match('/@version\\s+([^\\s\\*]+)/i', $content, $m)) {
        $version = mango_normalize_version($m[1]);
        return $version !== '' ? $version : null;
    }

    return null;
}

function themeAutoUpgradeNotice()
{
    // 1. 从 index.php 注释读取当前主题版本（@version）
    $current_version = mango_get_theme_version_from_index();
    if (empty($current_version)) {
        return;
    }

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
            $latest_version = mango_normalize_version($cache_data['tag_name']);
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
                $latest_version = mango_normalize_version($release_data['tag_name']);
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
                    $latest_version = mango_normalize_version($cache_data['tag_name']);
                }
            }
        }
    }
    // 4. 如果获取到了最新版本，则进行比较
    if ($latest_version && version_compare(mango_normalize_version($current_version), mango_normalize_version($latest_version), '<')) {
        
        $notice_html = '
        <span class="themeConfig"><h3>主题更新</h3>
            <div class="info">发现新版本 ' . $latest_version . '，您当前使用的是 ' . $current_version . '。建议立即更新以获得最新功能和安全性修复。
                <a href="https://github.com/jkjoy/typecho-theme-mango/releases/latest" target="_blank">查看更新</a>
                <a href="https://github.com/jkjoy/typecho-theme-mango/releases" target="_blank">立即下载</a>
            </div>';
        echo $notice_html;
    }
}
