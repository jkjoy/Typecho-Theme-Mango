<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
/**
 * 处理文章点赞
 */
if (isset($_POST['action']) && $_POST['action'] == 'specs_zan') {
    handlePostLike();
}

function mango_is_json_pagination_request()
{
    return isset($_GET['mango_json']) && $_GET['mango_json'] === '1';
}

function mango_send_json($data)
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function mango_extract_first_href($html)
{
    if (preg_match('/href\\s*=\\s*(["\'])(.*?)\\1/i', (string)$html, $m)) {
        return $m[2];
    }
    return null;
}

function mango_is_external_url($url)
{
    $url = (string)$url;
    $siteUrl = (string)Helper::options()->siteUrl;

    $siteHost = parse_url($siteUrl, PHP_URL_HOST);
    $urlHost = parse_url($url, PHP_URL_HOST);

    if (empty($urlHost) || empty($siteHost)) {
        return false;
    }

    return strcasecmp($urlHost, $siteHost) !== 0;
}

function mango_local_path_from_url($url)
{
    $url = (string)$url;
    $siteUrl = (string)Helper::options()->siteUrl;

    $urlPath = parse_url($url, PHP_URL_PATH);
    if (empty($urlPath)) {
        return null;
    }

    $siteHost = parse_url($siteUrl, PHP_URL_HOST);
    $urlHost = parse_url($url, PHP_URL_HOST);
    if (!empty($urlHost) && !empty($siteHost) && strcasecmp($urlHost, $siteHost) !== 0) {
        return null;
    }

    $siteBasePath = (string)parse_url($siteUrl, PHP_URL_PATH);
    $siteBasePath = rtrim($siteBasePath, '/');
    if ($siteBasePath !== '' && strncmp($urlPath, $siteBasePath, strlen($siteBasePath)) === 0) {
        $urlPath = substr($urlPath, strlen($siteBasePath));
    }

    $relativePath = ltrim(rawurldecode($urlPath), '/');
    if ($relativePath === '') {
        return null;
    }

    return rtrim(__TYPECHO_ROOT_DIR__, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
}

function mango_get_next_page_href($archiveWidget)
{
    $totalPages = ceil($archiveWidget->getTotal() / $archiveWidget->parameter->pageSize);
    if ($archiveWidget->_currentPage >= $totalPages) {
        return null;
    }

    ob_start();
    $archiveWidget->pageLink('加载更多', 'next');
    $nextLinkHtml = ob_get_clean();
    return mango_extract_first_href($nextLinkHtml);
}

function handlePostLike() {
    try {
        if (isset($_POST['cid'])) {
            $db = Typecho_Db::get();
            $cid = (int)$_POST['cid'];

            $liked = Typecho_Cookie::get('extend_contents_likes');
            $liked = empty($liked) ? array() : explode(',', $liked);
            if (in_array((string)$cid, $liked, true) || in_array($cid, $liked, true)) {
                echo 'already_liked';
                exit;
            }

            if (!function_exists('mango_increment_post_like')) {
                echo 0;
                exit;
            }

            $likes = mango_increment_post_like($cid);
            if ($likes === null) {
                echo 0;
                exit;
            }

            $liked[] = (string)$cid;
            if (count($liked) > 200) {
                $liked = array_slice($liked, -200);
            }
            Typecho_Cookie::set('extend_contents_likes', implode(',', $liked));

            echo (int)$likes;
            exit;
        }
    } catch (Exception $e) {
        error_log('Error in handlePostLike: ' . $e->getMessage());
        echo 0;
        exit;
    }
}