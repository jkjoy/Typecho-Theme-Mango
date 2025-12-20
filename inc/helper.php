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

function mango_reset_current_toc()
{
    $GLOBALS['mango_current_toc_html'] = '';
    $GLOBALS['mango_current_toc_items'] = [];
    $GLOBALS['mango_current_toc_cid'] = null;
}

function mango_get_current_toc_html()
{
    return isset($GLOBALS['mango_current_toc_html']) ? (string)$GLOBALS['mango_current_toc_html'] : '';
}

function mango_toc_should_generate($widget)
{
    if (!is_object($widget) || !method_exists($widget, 'is')) {
        return false;
    }
    return $widget->is('single') && $widget->is('post');
}

function mango_toc_slugify($text)
{
    $text = (string)$text;
    if ($text === '') return '';

    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\\s+/u', '-', trim($text));
    $text = preg_replace('/[^\\p{L}\\p{N}\\-_:.]/u', '', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * 给文章正文的标题(h1~h6)自动补齐 id，并生成 TOC（用于侧边栏锚点跳转）。
 * 返回：['content' => string, 'tocHtml' => string, 'items' => array]
 */
function mango_build_toc_and_inject_ids($content, $widget = null)
{
    if (!is_string($content) || trim($content) === '') {
        if (mango_toc_should_generate($widget)) {
            mango_reset_current_toc();
        }
        return ['content' => $content, 'tocHtml' => '', 'items' => []];
    }

    if (!mango_toc_should_generate($widget)) {
        return ['content' => $content, 'tocHtml' => '', 'items' => []];
    }

    $items = [];
    $usedIds = [];

    if (class_exists('DOMDocument')) {
        $internalErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');

        $flags = 0;
        if (defined('LIBXML_HTML_NOIMPLIED')) $flags |= LIBXML_HTML_NOIMPLIED;
        if (defined('LIBXML_HTML_NODEFDTD')) $flags |= LIBXML_HTML_NODEFDTD;

        $wrapperHtml = '<div id="mango-toc-root">' . $content . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapperHtml, $flags);
        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="mango-toc-root"]')->item(0);

        if ($root) {
            $headingQuery = './/*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]';
            $headings = $xpath->query($headingQuery, $root);
            $index = 0;

            foreach ($headings as $h) {
                $index++;
                $level = (int)substr($h->nodeName, 1);
                if ($level < 1 || $level > 6) continue;

                $text = trim(preg_replace('/\\s+/u', ' ', (string)$h->textContent));
                if ($text === '') continue;

                $id = trim((string)$h->getAttribute('id'));
                if ($id === '') {
                    $id = mango_toc_slugify($text);
                }
                if ($id === '') {
                    $id = 'toc-' . $index;
                }

                $baseId = $id;
                $suffix = 2;
                while (isset($usedIds[$id])) {
                    $id = $baseId . '-' . $suffix;
                    $suffix++;
                }
                $usedIds[$id] = true;

                $h->setAttribute('id', $id);

                $items[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                ];
            }

            $newContent = '';
            foreach ($root->childNodes as $child) {
                $newContent .= $dom->saveHTML($child);
            }
            $content = $newContent;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    } else {
        $pattern = '/<h([1-6])([^>]*)>(.*?)<\\/h\\1>/is';
        $index = 0;
        $content = preg_replace_callback($pattern, function ($m) use (&$items, &$usedIds, &$index) {
            $index++;
            $level = (int)$m[1];
            $attrs = $m[2];
            $inner = $m[3];

            $text = trim(preg_replace('/\\s+/u', ' ', strip_tags($inner)));
            if ($text === '') return $m[0];

            $id = '';
            if (preg_match('/\\sid\\s*=\\s*([\\\"\\\'])(.*?)\\1/i', $attrs, $mm)) {
                $id = trim((string)$mm[2]);
            }
            if ($id === '') {
                $id = mango_toc_slugify($text);
            }
            if ($id === '') {
                $id = 'toc-' . $index;
            }

            $baseId = $id;
            $suffix = 2;
            while (isset($usedIds[$id])) {
                $id = $baseId . '-' . $suffix;
                $suffix++;
            }
            $usedIds[$id] = true;

            if (!preg_match('/\\sid\\s*=\\s*/i', $attrs)) {
                $attrs .= ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
            }

            $items[] = [
                'id' => $id,
                'text' => $text,
                'level' => $level,
            ];

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        }, $content);
    }

    $tocHtml = '';
    if (!empty($items)) {
        $baseLevel = 6;
        foreach ($items as $it) {
            $lvl = isset($it['level']) ? (int)$it['level'] : 6;
            if ($lvl >= 1 && $lvl <= 6 && $lvl < $baseLevel) $baseLevel = $lvl;
        }
        if ($baseLevel < 1 || $baseLevel > 6) $baseLevel = 1;

        $stack = [];
        $tocHtml .= '<ul class="toc-list">';
        foreach ($items as &$item) {
            $id = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8');
            $level = (int)$item['level'];

            $depth = $level - $baseLevel + 1;
            if ($depth < 1) $depth = 1;
            if ($depth > 6) $depth = 6;

            if (count($stack) > $depth) {
                $stack = array_slice($stack, 0, $depth);
            }
            if (count($stack) < $depth) {
                while (count($stack) < $depth) {
                    $stack[] = (count($stack) < $depth - 1) ? 1 : 0;
                }
            }
            $stack[$depth - 1] = (int)$stack[$depth - 1] + 1;

            $number = implode('-', $stack);
            $item['number'] = $number;
            $numEsc = htmlspecialchars($number, ENT_QUOTES, 'UTF-8');

            $tocHtml .= '<li class="toc-item toc-level-' . $level . ' toc-depth-' . $depth . '"><a href="#' . $id . '"><span class="toc-number">' . $numEsc . '</span> ' . $text . '</a></li>';
        }
        unset($item);
        $tocHtml .= '</ul>';
    }

    $cid = null;
    if (is_object($widget) && isset($widget->cid)) {
        $cid = (int)$widget->cid;
    }

    $GLOBALS['mango_current_toc_html'] = $tocHtml;
    $GLOBALS['mango_current_toc_items'] = $items;
    $GLOBALS['mango_current_toc_cid'] = $cid;

    return ['content' => $content, 'tocHtml' => $tocHtml, 'items' => $items];
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
