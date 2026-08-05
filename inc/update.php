<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function mango_set_theme_update_error($message)
{
    $GLOBALS['mango_theme_update_error'] = trim((string)$message);
}

function mango_get_theme_update_error()
{
    return (string)($GLOBALS['mango_theme_update_error'] ?? '');
}

function mango_normalize_version($version)
{
    $version = trim((string)$version);
    $version = ltrim($version, "vV \t\n\r\0\x0B");

    if (preg_match('/^([0-9]+(?:\\.[0-9]+){1,3})/', $version, $matches)) {
        return $matches[1];
    }

    return $version;
}

function mango_get_theme_version_from_file($indexFile)
{
    if (!is_file($indexFile)) {
        return null;
    }

    $content = @file_get_contents($indexFile);
    if ($content === false) {
        return null;
    }

    if (preg_match('/@version\\s+([^\\s\\*]+)/i', $content, $matches)) {
        $version = mango_normalize_version($matches[1]);
        return $version !== '' ? $version : null;
    }

    return null;
}

function mango_get_theme_version_from_index()
{
    $indexFile = dirname(__DIR__) . '/index.php';
    if (is_file($indexFile)) {
        return mango_get_theme_version_from_file($indexFile);
    }

    $theme = (string)Helper::options()->theme;
    if ($theme === '') {
        return null;
    }

    $fallback = rtrim(__TYPECHO_ROOT_DIR__, '/\\')
        . rtrim(__TYPECHO_THEME_DIR__, '/\\')
        . '/' . $theme . '/index.php';

    return mango_get_theme_version_from_file($fallback);
}

function mango_theme_update_cache_file()
{
    return rtrim(__TYPECHO_ROOT_DIR__, '/\\') . '/usr/cache/mango-theme-release.json';
}

function mango_validate_release_data($release)
{
    if (!is_array($release)) {
        return null;
    }

    $tag = trim((string)($release['tag_name'] ?? ''));
    if (!preg_match('/^v?[0-9]+(?:\\.[0-9]+){1,3}$/i', $tag)) {
        return null;
    }

    $version = mango_normalize_version($tag);
    if ($version === '') {
        return null;
    }

    $htmlUrl = (string)($release['html_url'] ?? '');
    $htmlHost = strtolower((string)parse_url($htmlUrl, PHP_URL_HOST));
    if ($htmlHost !== 'github.com') {
        $htmlUrl = 'https://github.com/jkjoy/Typecho-Theme-Mango/releases/tag/' . rawurlencode($tag);
    }

    return [
        'tag_name' => $tag,
        'version' => $version,
        'html_url' => $htmlUrl,
        'published_at' => (string)($release['published_at'] ?? ''),
        'zip_url' => 'https://github.com/jkjoy/Typecho-Theme-Mango/archive/refs/tags/' . rawurlencode($tag) . '.zip',
        'cached_at' => (int)($release['cached_at'] ?? time()),
    ];
}

function mango_http_get($url, $timeout = 15)
{
    if (strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https') {
        return null;
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => (int)$timeout,
            CURLOPT_USERAGENT => 'Typecho-Theme-Mango-Updater',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }

        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($status >= 200 && $status < 300 && is_string($body)) {
            mango_set_theme_update_error('');
            return $body;
        }

        mango_set_theme_update_error(
            $curlError !== '' ? $curlError : 'GitHub API 返回 HTTP ' . $status
        );
    }

    if (!ini_get('allow_url_fopen')) {
        return null;
    }

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Typecho-Theme-Mango-Updater\r\nAccept: application/vnd.github+json",
            'timeout' => (int)$timeout,
            'follow_location' => 1,
            'max_redirects' => 5,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    error_clear_last();
    $body = @file_get_contents($url, false, $context);
    if (is_string($body) && $body !== '') {
        mango_set_theme_update_error('');
        return $body;
    }

    $lastError = error_get_last();
    if (is_array($lastError) && !empty($lastError['message'])) {
        mango_set_theme_update_error($lastError['message']);
    }
    return null;
}

function mango_read_cached_release($allowExpired = false)
{
    $cacheFile = mango_theme_update_cache_file();
    if (!is_file($cacheFile)) {
        return null;
    }

    $decoded = json_decode((string)@file_get_contents($cacheFile), true);
    $release = mango_validate_release_data($decoded);
    if (!$release) {
        return null;
    }

    $cachedAt = (int)($decoded['cached_at'] ?? 0);
    if (!$allowExpired && ($cachedAt <= 0 || time() - $cachedAt >= 12 * 3600)) {
        return null;
    }

    return $release;
}

function mango_write_release_cache($release)
{
    $cacheFile = mango_theme_update_cache_file();
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true)) {
        return false;
    }

    $release['cached_at'] = time();
    $json = json_encode($release, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        return false;
    }

    $tempFile = $cacheFile . '.tmp-' . getmypid();
    if (@file_put_contents($tempFile, $json, LOCK_EX) === false) {
        return false;
    }

    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
    if (!@rename($tempFile, $cacheFile)) {
        @unlink($tempFile);
        return false;
    }

    return true;
}

function mango_fetch_latest_release($force = false)
{
    if (!$force) {
        $cached = mango_read_cached_release(false);
        if ($cached) {
            return $cached;
        }
    }

    $apiUrl = 'https://api.github.com/repos/jkjoy/Typecho-Theme-Mango/releases/latest';
    $body = mango_http_get($apiUrl, 15);
    if ($body !== null) {
        $decoded = json_decode($body, true);
        $release = mango_validate_release_data($decoded);
        if ($release) {
            mango_write_release_cache($release);
            return $release;
        }
    }

    error_log('Failed to fetch Mango release data from ' . $apiUrl);
    return mango_read_cached_release(true);
}

function mango_get_latest_theme_update($force = false)
{
    $currentVersion = mango_get_theme_version_from_index();
    $release = mango_fetch_latest_release($force);
    if (!$currentVersion || !$release) {
        return null;
    }

    return [
        'current_version' => mango_normalize_version($currentVersion),
        'latest_version' => mango_normalize_version($release['version']),
        'tag_name' => $release['tag_name'],
        'html_url' => $release['html_url'],
        'zip_url' => $release['zip_url'],
        'has_update' => version_compare(
            mango_normalize_version($currentVersion),
            mango_normalize_version($release['version']),
            '<'
        ),
    ];
}

function mango_theme_update_admin_url($action)
{
    $security = Typecho_Widget::widget('Widget_Security');
    return $security->getAdminUrl('options-theme.php?mango_theme_update=' . rawurlencode($action));
}

function themeAutoUpgradeNotice()
{
    $currentVersion = mango_get_theme_version_from_index();
    if (!$currentVersion) {
        return;
    }

    $release = mango_fetch_latest_release(false);
    $update = $release ? mango_get_latest_theme_update(false) : null;
    if ($release && (!$update || !$update['has_update'])) {
        return;
    }

    $currentSafe = htmlspecialchars((string)$currentVersion, ENT_QUOTES, 'UTF-8');
    $checkUrl = mango_theme_update_admin_url('check');
    $releaseUrl = $release
        ? (string)$release['html_url']
        : 'https://github.com/jkjoy/Typecho-Theme-Mango/releases/latest';

    echo '<span class="themeConfig"><h3>主题更新</h3></span>';
    echo '<div class="info mango-update-info">';

    if (!$release) {
        $errorSafe = htmlspecialchars(mango_get_theme_update_error(), ENT_QUOTES, 'UTF-8');
        echo '当前版本 ' . $currentSafe . '，暂时无法连接 GitHub 检查新版本。';
        if ($errorSafe !== '') {
            echo '<br><small>' . $errorSafe . '</small>';
        }
    } else {
        $latestSafe = htmlspecialchars((string)$update['latest_version'], ENT_QUOTES, 'UTF-8');
        echo '发现新版本 <strong>' . $latestSafe . '</strong>，当前版本为 ' . $currentSafe . '。';
    }

    echo ' <a href="' . htmlspecialchars($releaseUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">查看发布记录</a>';
    if ($update && $update['has_update']) {
        echo ' <button type="button" class="btn primary mango-theme-update-action" data-action="'
            . htmlspecialchars(mango_theme_update_admin_url('upgrade'), ENT_QUOTES, 'UTF-8')
            . '" data-confirm="在线更新会先备份当前主题文件，再安装 GitHub 最新正式版。自定义文件修改将被覆盖，确认继续？">在线更新</button>';
    }
    echo ' <button type="button" class="btn mango-theme-update-action" data-action="'
        . htmlspecialchars($checkUrl, ENT_QUOTES, 'UTF-8')
        . '">重新检查</button>';
    echo '</div>';
    echo '<script>
    (function(){
        document.querySelectorAll(".mango-theme-update-action").forEach(function(button){
            button.addEventListener("click", function(){
                var message = button.getAttribute("data-confirm");
                if (message && !window.confirm(message)) return;
                var action = button.getAttribute("data-action");
                if (!action) return;
                document.querySelectorAll(".mango-theme-update-action").forEach(function(item){ item.disabled = true; });
                button.textContent = message ? "更新中..." : "检查中...";
                var form = document.createElement("form");
                form.method = "post";
                form.action = action;
                form.style.display = "none";
                document.body.appendChild(form);
                form.submit();
            });
        });
    })();
    </script>';
}

function mango_theme_update_workspace()
{
    $rootHash = substr(sha1((string)__TYPECHO_ROOT_DIR__), 0, 12);
    $candidates = [
        dirname(rtrim(__TYPECHO_ROOT_DIR__, '/\\')) . '/.mango-theme-updates-' . $rootHash,
        rtrim(sys_get_temp_dir(), '/\\') . '/mango-theme-updates-' . $rootHash,
    ];

    foreach ($candidates as $directory) {
        if (!is_dir($directory) && !@mkdir($directory, 0700, true)) {
            continue;
        }
        @chmod($directory, 0700);
        if (is_writable($directory)) {
            return $directory;
        }
    }

    throw new RuntimeException('无法创建主题更新工作目录');
}

function mango_update_random_suffix()
{
    try {
        return bin2hex(random_bytes(6));
    } catch (Throwable $error) {
        return str_replace('.', '', uniqid('', true));
    }
}

function mango_path_is_within($path, $base)
{
    $pathReal = realpath($path);
    $baseReal = realpath($base);
    if ($pathReal === false || $baseReal === false || $pathReal === $baseReal) {
        return false;
    }

    $prefix = rtrim($baseReal, '/\\') . DIRECTORY_SEPARATOR;
    return strncmp($pathReal, $prefix, strlen($prefix)) === 0;
}

function mango_remove_directory($directory, $allowedBase)
{
    if (!is_dir($directory)) {
        return true;
    }
    if (!mango_path_is_within($directory, $allowedBase)) {
        return false;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $path = $item->getPathname();
        if ($item->isLink() || $item->isFile()) {
            if (!@unlink($path)) {
                return false;
            }
        } elseif (!@rmdir($path)) {
            return false;
        }
    }

    return @rmdir($directory);
}

function mango_download_update_package($url, $destination, $maxBytes = 52428800)
{
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    if (strtolower((string)parse_url($url, PHP_URL_SCHEME)) !== 'https' || $host !== 'github.com') {
        throw new RuntimeException('更新包地址不是受信任的 GitHub HTTPS 地址');
    }

    $output = @fopen($destination, 'wb');
    if (!$output) {
        throw new RuntimeException('无法写入下载的更新包');
    }

    $downloaded = 0;
    $tooLarge = false;
    try {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('无法初始化 cURL');
            }

            $options = [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_USERAGENT => 'Typecho-Theme-Mango-Updater',
                CURLOPT_HTTPHEADER => ['Accept: application/octet-stream'],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_WRITEFUNCTION => function ($curlHandle, $chunk) use ($output, $maxBytes, &$downloaded, &$tooLarge) {
                    $length = strlen($chunk);
                    $downloaded += $length;
                    if ($downloaded > $maxBytes) {
                        $tooLarge = true;
                        return 0;
                    }
                    $written = fwrite($output, $chunk);
                    return $written === false ? 0 : $written;
                },
            ];
            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
            }

            curl_setopt_array($curl, $options);
            $success = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($tooLarge) {
                throw new RuntimeException('更新包超过 50 MB 限制');
            }
            if ($success === false || $status < 200 || $status >= 300) {
                throw new RuntimeException('下载更新包失败' . ($error !== '' ? '：' . $error : '（HTTP ' . $status . '）'));
            }
        } else {
            if (!ini_get('allow_url_fopen')) {
                throw new RuntimeException('服务器未启用 cURL 或 allow_url_fopen');
            }
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Typecho-Theme-Mango-Updater\r\nAccept: application/octet-stream",
                    'timeout' => 120,
                    'follow_location' => 1,
                    'max_redirects' => 5,
                ],
            ]);
            $input = @fopen($url, 'rb', false, $context);
            if (!$input) {
                throw new RuntimeException('下载 GitHub 更新包失败');
            }
            while (!feof($input)) {
                $chunk = fread($input, 8192);
                if ($chunk === false) {
                    fclose($input);
                    throw new RuntimeException('读取 GitHub 更新包失败');
                }
                $downloaded += strlen($chunk);
                if ($downloaded > $maxBytes) {
                    fclose($input);
                    throw new RuntimeException('更新包超过 50 MB 限制');
                }
                if ($chunk !== '' && fwrite($output, $chunk) === false) {
                    fclose($input);
                    throw new RuntimeException('写入更新包失败');
                }
            }
            fclose($input);
        }
    } catch (Throwable $error) {
        fclose($output);
        @unlink($destination);
        throw $error;
    }

    fclose($output);
    clearstatcache(true, $destination);
    $signature = @file_get_contents($destination, false, null, 0, 4);
    if (!is_file($destination) || filesize($destination) < 100 || substr((string)$signature, 0, 2) !== 'PK') {
        @unlink($destination);
        throw new RuntimeException('下载内容不是有效的 ZIP 更新包');
    }
}

function mango_open_validated_update_zip($zipFile)
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('服务器未启用 ZipArchive，无法在线更新');
    }

    $zip = new ZipArchive();
    $opened = $zip->open($zipFile, ZipArchive::CHECKCONS);
    if ($opened !== true) {
        throw new RuntimeException('更新包 ZIP 校验失败');
    }

    if ($zip->numFiles <= 0 || $zip->numFiles > 5000) {
        $zip->close();
        throw new RuntimeException('更新包文件数量异常');
    }

    $totalSize = 0;
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $name = (string)$zip->getNameIndex($index);
        $normalized = str_replace('\\', '/', $name);
        $segments = explode('/', $normalized);
        if (
            $normalized === ''
            || strpos($normalized, "\0") !== false
            || $normalized[0] === '/'
            || preg_match('/^[A-Za-z]:/', $normalized)
            || in_array('..', $segments, true)
        ) {
            $zip->close();
            throw new RuntimeException('更新包包含不安全路径：' . $name);
        }

        $operatingSystem = 0;
        $attributes = 0;
        if ($zip->getExternalAttributesIndex($index, $operatingSystem, $attributes)) {
            $fileType = ($attributes >> 16) & 0170000;
            if ($fileType === 0120000) {
                $zip->close();
                throw new RuntimeException('更新包不允许包含符号链接：' . $name);
            }
        }

        $stat = $zip->statIndex($index);
        $totalSize += is_array($stat) ? (int)($stat['size'] ?? 0) : 0;
        if ($totalSize > 104857600) {
            $zip->close();
            throw new RuntimeException('更新包解压后超过 100 MB 限制');
        }
    }

    return $zip;
}

function mango_find_extracted_theme_root($extractDirectory)
{
    if (is_file($extractDirectory . '/index.php')) {
        return $extractDirectory;
    }

    $matches = [];
    $items = @scandir($extractDirectory);
    if (!is_array($items)) {
        return null;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $extractDirectory . '/' . $item;
        if (is_dir($path) && is_file($path . '/index.php')) {
            $matches[] = $path;
        }
    }

    return count($matches) === 1 ? $matches[0] : null;
}

function mango_validate_extracted_theme($themeDirectory, $expectedVersion)
{
    $required = [
        'index.php',
        'functions.php',
        'header.php',
        'footer.php',
        'inc/update.php',
        'assets/css/style.css',
        'assets/js/main.js',
    ];

    foreach ($required as $relative) {
        if (!is_file($themeDirectory . '/' . $relative)) {
            throw new RuntimeException('更新包缺少主题文件：' . $relative);
        }
    }

    $packageVersion = mango_get_theme_version_from_file($themeDirectory . '/index.php');
    if (!$packageVersion || version_compare($packageVersion, mango_normalize_version($expectedVersion), '!=')) {
        throw new RuntimeException('更新包版本与 GitHub Release 不一致');
    }

    $indexContent = (string)@file_get_contents($themeDirectory . '/index.php');
    if (!preg_match('/@package\\s+Mango\\b/i', $indexContent)) {
        throw new RuntimeException('更新包不是 Mango 主题');
    }
}

function mango_copy_directory($source, $target)
{
    if (!is_dir($target) && !@mkdir($target, 0755, true)) {
        throw new RuntimeException('无法创建主题暂存目录');
    }

    $sourceLength = strlen(rtrim($source, '/\\')) + 1;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isLink()) {
            throw new RuntimeException('更新包中不允许包含符号链接');
        }

        $relative = substr($item->getPathname(), $sourceLength);
        $destination = $target . DIRECTORY_SEPARATOR . $relative;
        if ($item->isDir()) {
            if (!is_dir($destination) && !@mkdir($destination, 0755, true)) {
                throw new RuntimeException('无法创建目录：' . $relative);
            }
            continue;
        }

        $parent = dirname($destination);
        if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
            throw new RuntimeException('无法创建目录：' . dirname($relative));
        }
        if (!@copy($item->getPathname(), $destination)) {
            throw new RuntimeException('无法复制文件：' . $relative);
        }
        @chmod($destination, $item->getPerms() & 0777);
    }
}

function mango_create_theme_file_backup($themeDirectory, $workspace, $currentVersion)
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('服务器未启用 ZipArchive，无法创建更新前备份');
    }

    $backupDirectory = $workspace . '/backups';
    if (!is_dir($backupDirectory) && !@mkdir($backupDirectory, 0700, true)) {
        throw new RuntimeException('无法创建主题备份目录');
    }
    @chmod($backupDirectory, 0700);

    $backupFile = $backupDirectory . '/Mango-' . mango_normalize_version($currentVersion)
        . '-' . date('Ymd-His') . '-' . mango_update_random_suffix() . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('无法创建主题文件备份');
    }

    $baseLength = strlen(rtrim($themeDirectory, '/\\')) + 1;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($themeDirectory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = str_replace('\\', '/', substr($item->getPathname(), $baseLength));
        if ($relative === '.git' || strpos($relative, '.git/') === 0) {
            continue;
        }
        if ($item->isLink()) {
            $zip->close();
            @unlink($backupFile);
            throw new RuntimeException('当前主题包含符号链接，无法创建完整备份');
        }

        $added = $item->isDir()
            ? $zip->addEmptyDir($relative)
            : $zip->addFile($item->getPathname(), $relative);
        if (!$added) {
            $zip->close();
            @unlink($backupFile);
            throw new RuntimeException('备份主题文件失败：' . $relative);
        }
    }

    if (!$zip->close() || !is_file($backupFile)) {
        @unlink($backupFile);
        throw new RuntimeException('主题文件备份写入失败');
    }

    $backups = glob($backupDirectory . '/Mango-*.zip') ?: [];
    usort($backups, function ($left, $right) {
        return (int)@filemtime($right) <=> (int)@filemtime($left);
    });
    foreach (array_slice($backups, 5) as $oldBackup) {
        @unlink($oldBackup);
    }

    return $backupFile;
}

function mango_install_extracted_theme($source, $themeDirectory, $expectedVersion)
{
    $themeDirectory = realpath($themeDirectory);
    if ($themeDirectory === false || !is_dir($themeDirectory)) {
        throw new RuntimeException('当前主题目录不存在');
    }

    $parent = realpath(dirname($themeDirectory));
    if ($parent === false || !is_writable($parent) || !is_writable($themeDirectory)) {
        throw new RuntimeException('主题目录或其上级目录不可写');
    }
    if (is_dir($themeDirectory . '/.git')) {
        throw new RuntimeException('检测到 Git 工作区，请使用 git pull 更新，在线更新不会覆盖 Git 仓库');
    }

    $name = basename($themeDirectory);
    $suffix = mango_update_random_suffix();
    $staging = $parent . DIRECTORY_SEPARATOR . '.' . $name . '-update-' . $suffix;
    $rollback = $parent . DIRECTORY_SEPARATOR . '.' . $name . '-rollback-' . $suffix;

    try {
        mango_copy_directory($source, $staging);
        mango_validate_extracted_theme($staging, $expectedVersion);

        if (!@rename($themeDirectory, $rollback)) {
            throw new RuntimeException('无法移动当前主题目录，更新未执行');
        }

        if (!@rename($staging, $themeDirectory)) {
            if (!@rename($rollback, $themeDirectory)) {
                throw new RuntimeException('安装新版本失败，且自动回滚失败；旧主题位于 ' . $rollback);
            }
            throw new RuntimeException('安装新版本失败，已自动恢复旧主题');
        }

        clearstatcache(true);
        try {
            mango_validate_extracted_theme($themeDirectory, $expectedVersion);
        } catch (Throwable $error) {
            $failedInstall = $parent . DIRECTORY_SEPARATOR . '.' . $name . '-failed-' . $suffix;
            if (@rename($themeDirectory, $failedInstall) && @rename($rollback, $themeDirectory)) {
                mango_remove_directory($failedInstall, $parent);
                throw new RuntimeException('新版本安装校验失败，已自动恢复旧主题：' . $error->getMessage());
            }
            throw new RuntimeException('新版本安装校验失败，且自动回滚失败；旧主题位于 ' . $rollback);
        }

        if (!mango_remove_directory($rollback, $parent)) {
            error_log('Mango update completed but rollback directory could not be removed: ' . $rollback);
        }
    } finally {
        if (is_dir($staging)) {
            mango_remove_directory($staging, $parent);
        }
    }
}

function mango_upgrade_theme_from_github()
{
    if (function_exists('set_time_limit')) {
        @set_time_limit(180);
    }

    $update = mango_get_latest_theme_update(true);
    if (!$update || !$update['has_update']) {
        return '当前已是最新正式版';
    }

    $themeDirectory = realpath(dirname(__DIR__));
    if ($themeDirectory === false) {
        throw new RuntimeException('无法定位当前主题目录');
    }
    if (is_dir($themeDirectory . '/.git')) {
        throw new RuntimeException('检测到 Git 工作区，请使用 git pull 更新，在线更新不会覆盖 Git 仓库');
    }

    $workspace = mango_theme_update_workspace();
    $lock = @fopen($workspace . '/update.lock', 'c');
    if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        throw new RuntimeException('已有主题更新任务正在执行，请稍后再试');
    }

    $jobDirectory = $workspace . '/job-' . date('Ymd-His') . '-' . mango_update_random_suffix();
    $extractDirectory = $jobDirectory . '/extract';
    if (!@mkdir($extractDirectory, 0700, true)) {
        @flock($lock, LOCK_UN);
        fclose($lock);
        throw new RuntimeException('无法创建更新临时目录');
    }

    $zipFile = $jobDirectory . '/release.zip';
    $backupFile = null;
    try {
        mango_download_update_package($update['zip_url'], $zipFile);
        $zip = mango_open_validated_update_zip($zipFile);
        try {
            if (!$zip->extractTo($extractDirectory)) {
                throw new RuntimeException('解压主题更新包失败');
            }
        } finally {
            $zip->close();
        }

        $source = mango_find_extracted_theme_root($extractDirectory);
        if (!$source) {
            throw new RuntimeException('更新包中未找到唯一的主题根目录');
        }
        mango_validate_extracted_theme($source, $update['latest_version']);

        $backupFile = mango_create_theme_file_backup(
            $themeDirectory,
            $workspace,
            $update['current_version']
        );
        mango_install_extracted_theme($source, $themeDirectory, $update['latest_version']);

        @unlink(mango_theme_update_cache_file());
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        return '主题已更新到 ' . $update['latest_version'] . '。更新前文件备份：' . $backupFile;
    } finally {
        if (is_dir($jobDirectory)) {
            mango_remove_directory($jobDirectory, $workspace);
        }
        @flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function mango_theme_update_redirect()
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    $url = Typecho_Common::url('options-theme.php', Helper::options()->adminUrl);
    header('Location: ' . $url);
    exit;
}

function mango_handle_theme_update_request()
{
    if (!defined('__TYPECHO_ADMIN__') || empty($_GET['mango_theme_update'])) {
        return;
    }

    $action = (string)$_GET['mango_theme_update'];
    if (!in_array($action, ['check', 'upgrade'], true)) {
        http_response_code(400);
        exit;
    }
    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        http_response_code(405);
        exit;
    }

    $user = Typecho_Widget::widget('Widget_User');
    if (!$user->pass('administrator', true)) {
        http_response_code(403);
        exit;
    }
    Typecho_Widget::widget('Widget_Security')->protect();
    $notice = Typecho_Widget::widget('Widget_Notice');

    if ($action === 'check') {
        @unlink(mango_theme_update_cache_file());
        $release = mango_fetch_latest_release(true);
        if ($release) {
            $notice->set(_t('更新检查完成，GitHub 最新正式版：%s', $release['version']), 'success');
        } else {
            $error = mango_get_theme_update_error();
            $message = '无法连接 GitHub 检查主题更新' . ($error !== '' ? '：' . $error : '');
            $notice->set(_t($message), 'error');
        }
        mango_theme_update_redirect();
    }

    try {
        $message = mango_upgrade_theme_from_github();
        $notice->set(_t($message), 'success');
    } catch (Throwable $error) {
        $notice->set(_t('主题在线更新失败：%s', $error->getMessage()), 'error');
    }
    mango_theme_update_redirect();
}

mango_handle_theme_update_request();
