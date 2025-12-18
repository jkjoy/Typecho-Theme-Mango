<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
function mango_render_goto_page($target)
{
    $target = trim((string)$target);
    if ($target === '') {
        http_response_code(400);
        echo 'Missing goto url.';
        exit;
    }

    $target = str_replace(["\r", "\n"], '', $target);
    $scheme = strtolower((string)parse_url($target, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        http_response_code(400);
        echo 'Invalid goto url.';
        exit;
    }

    $host = (string)parse_url($target, PHP_URL_HOST);
    $siteTitle = (string)Helper::options()->title;
    $delaySeconds = 3;

    $safeHost = htmlspecialchars($host !== '' ? $host : $target, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8');
    $safeTargetAttr = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
    $targetJs = json_encode($target, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Referrer-Policy: no-referrer');
    }

    echo '<!doctype html><html><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>跳转提示 - ' . $safeTitle . '</title>';
    echo '<style>
        :root{color-scheme:light dark;}
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,"PingFang SC","Microsoft YaHei",sans-serif;background:#f6f7fb;color:#111827}
        .wrap{max-width:560px;margin:0 auto;padding:32px 18px}
        .card{background:#fff;border:1px solid rgba(0,0,0,.06);border-radius:14px;padding:18px 18px 16px;box-shadow:0 10px 28px rgba(17,24,39,.06)}
        .title{font-size:18px;font-weight:700;margin:0 0 10px}
        .desc{margin:0 0 14px;line-height:1.8;color:#4b5563}
        .url{word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;background:#f3f4f6;padding:10px 12px;border-radius:10px;border:1px solid rgba(0,0,0,.06)}
        .actions{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;border:1px solid rgba(0,0,0,.12);text-decoration:none;font-weight:600}
        .btn.primary{background:#4270f5;color:#fff;border-color:rgba(66,112,245,.9)}
        .btn.ghost{background:transparent;color:#111827}
        .hint{margin-top:10px;font-size:13px;color:#6b7280;line-height:1.7}
        @media (prefers-color-scheme: dark){
            body{background:#0b0f19;color:rgba(255,255,255,.9)}
            .card{background:#111827;border-color:rgba(255,255,255,.08);box-shadow:none}
            .desc{color:rgba(255,255,255,.75)}
            .url{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.08);color:rgba(255,255,255,.88)}
            .btn.ghost{color:rgba(255,255,255,.9);border-color:rgba(255,255,255,.16)}
            .hint{color:rgba(255,255,255,.65)}
        }
    </style>';
    echo '</head><body><div class="wrap"><div class="card">';
    echo '<h1 class="title">即将离开本站</h1>';
    echo '<p class="desc">你将前往第三方网站，请注意甄别内容与安全风险。</p>';
    echo '<div class="url">' . $safeHost . '</div>';
    echo '<div class="actions">';
    echo '<a class="btn primary" href="' . $safeTargetAttr . '" rel="noopener noreferrer" target="_self">继续访问</a>';
    echo '<a class="btn ghost" href="javascript:history.back()">返回上一页</a>';
    echo '</div>';
    echo '<div class="hint"><span id="mango-goto-count">' . (int)$delaySeconds . '</span> 秒后将自动跳转；如未跳转请点击“继续访问”。</div>';
    echo '</div></div>';
    echo '<script>
        (function(){
            var target = ' . ($targetJs ?: '""') . ';
            var sec = ' . (int)$delaySeconds . ';
            var el = document.getElementById("mango-goto-count");
            var timer = setInterval(function(){
                sec--;
                if (el) el.textContent = sec;
                if (sec <= 0) {
                    clearInterval(timer);
                    window.location.href = target;
                }
            }, 1000);
        })();
    </script>';
    echo '</body></html>';
    exit;
}

if (isset($_GET['goto'])) {
    mango_render_goto_page($_GET['goto']);
}