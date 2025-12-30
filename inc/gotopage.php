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

    // 获取目标URL的域名
    $targetHost = (string)parse_url($target, PHP_URL_HOST);
    
    // 获取当前网站的域名 - 使用HTTP_HOST（可能包含端口）
    $currentHost = (string)$_SERVER['HTTP_HOST'];
    
    // 移除端口号（如果存在）
    $currentHost = preg_replace('/:\d+$/', '', $currentHost);
    
    // 标准化域名：移除www前缀进行比较
    $normalizedTargetHost = preg_replace('/^www\./i', '', $targetHost);
    $normalizedCurrentHost = preg_replace('/^www\./i', '', $currentHost);
    
    // 调试信息（生产环境应移除）
    error_log("GOTO DEBUG: target='$target' | targetHost='$targetHost' | currentHost='$currentHost' | normTarget='$normalizedTargetHost' | normCurrent='$normalizedCurrentHost' | match=" . ($normalizedTargetHost === $normalizedCurrentHost ? 'YES' : 'NO'));
    
    // 如果域名相同（忽略www前缀），直接跳转
    if ($normalizedTargetHost === $normalizedCurrentHost) {
        if (!headers_sent()) {
            header('Location: ' . $target, true, 302);
        } else {
            echo '<script>window.location.href = ' . json_encode($target) . ';</script>';
        }
        exit;
    }
    
    $siteTitle = (string)Helper::options()->title;
    $delaySeconds = 0;

    $safeHost = htmlspecialchars($targetHost !== '' ? $targetHost : $target, ENT_QUOTES, 'UTF-8');
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
        body{
            margin:0;
            font-family:PingFang SC,Microsoft YaHei,Helvetica Neue,Helvetica,Arial,sans-serif;
            background:#f7f7f7;
            color:#333;
            font-size:14px;
        }
        .wrap{
            max-width:560px;
            margin:0 auto;
            padding:40px 20px;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .card{
            background:#fff;
            border-radius:10px;
            padding:25px;
            box-shadow:0 2px 20px rgba(0,0,0,.08);
            width:100%;
            box-sizing:border-box;
        }
        .title{
            font-size:18px;
            font-weight:600;
            margin:0 0 12px;
            color:#111827;
        }
        .desc{
            margin:0 0 18px;
            line-height:1.8;
            color:#555;
            font-size:14px;
        }
        .url{
            word-break:break-all;
            font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
            background:#f5f5f5;
            padding:12px 14px;
            border-radius:6px;
            border:1px solid #e5e5e5;
            font-size:13px;
            color:#333;
            margin-bottom:15px;
        }
        .actions{
            display:flex;
            gap:10px;
            margin-top:20px;
            flex-wrap:wrap;
        }
        .btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:10px 18px;
            border-radius:6px;
            text-decoration:none;
            font-weight:500;
            font-size:14px;
            transition:all .2s ease;
            border:1px solid transparent;
            cursor:pointer;
            box-sizing:border-box;
        }
        .btn.primary{
            background:#4270f5;
            color:#fff;
            border-color:#4270f5;
            flex:1;
            min-width:120px;
        }
        .btn.primary:hover{
            background:#3556f3;
            border-color:#3556f3;
            transform:translateY(-1px);
            box-shadow:0 4px 12px rgba(66,112,245,.25);
        }
        .btn.ghost{
            background:#fff;
            color:#333;
            border-color:#ddd;
            flex:1;
            min-width:120px;
        }
        .btn.ghost:hover{
            background:#f8f9fa;
            border-color:#ccc;
            transform:translateY(-1px);
        }
        .hint{
            margin-top:15px;
            font-size:12px;
            color:#666;
            line-height:1.6;
            text-align:center;
        }
        
        /* 深色模式支持 - 匹配主题样式 */
        body.dark{
            background:#191919;
            color:#c9c9c9;
        }
        body.dark .card{
            background:#212121;
            box-shadow:0 2px 20px rgba(0,0,0,.3);
        }
        body.dark .title{
            color:#fff;
        }
        body.dark .desc{
            color:#c9c9c9;
        }
        body.dark .url{
            background:#2a2a2b;
            border-color:#3a3a3a;
            color:#f0f0f0;
        }
        body.dark .btn.ghost{
            background:#2a2a2b;
            color:#f0f0f0;
            border-color:#3a3a3a;
        }
        body.dark .btn.ghost:hover{
            background:#343435;
            border-color:#4a4a4a;
        }
        body.dark .hint{
            color:#b2b2b2;
        }
        
        /* 响应式适配 */
        @media (max-width:768px){
            .wrap{padding:20px 15px;}
            .card{padding:20px;}
            .btn{padding:10px 15px;font-size:13px;}
            .actions{flex-direction:column;}
            .btn.primary,.btn.ghost{min-width:100%;}
        }
    </style>';
    echo '</head><body><div class="wrap"><div class="card">';
    echo '<script>
        // 检测系统深色模式并应用
        (function(){
            var prefersDark = window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
            if (prefersDark) {
                document.body.classList.add("dark");
            }
            // 监听系统主题变化
            if (window.matchMedia) {
                window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function(e) {
                    if (e.matches) {
                        document.body.classList.add("dark");
                    } else {
                        document.body.classList.remove("dark");
                    }
                });
            }
        })();
    </script>';
    echo '<h1 class="title">即将离开本站</h1>';
    echo '<p class="desc">你将前往第三方网站，请注意甄别内容与安全风险。</p>';
    echo '<div class="url">' . $safeTargetAttr . '</div>';
    echo '<div class="actions">';
    echo '<a class="btn primary" href="' . $safeTargetAttr . '" rel="noopener noreferrer" target="_self">继续访问</a>';
    echo '<a class="btn ghost" href="javascript:history.back()">返回上一页</a>';
    echo '</div>';
    echo '<div class="hint">请点击"继续访问"按钮手动跳转</div>';
    echo '</div></div>';
    echo '</body></html>';
    exit;
}

if (isset($_GET['goto'])) {
    mango_render_goto_page($_GET['goto']);
}