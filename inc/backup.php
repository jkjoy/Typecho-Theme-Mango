<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use Typecho\Common;
use Typecho\Db;
use Widget\Notice;
use Widget\Security;
use Widget\User;

function mango_get_current_theme_name()
{
    $theme = '';
    try {
        $theme = (string)Helper::options()->theme;
    } catch (Exception $e) {
        $theme = '';
    }

    if ($theme !== '') {
        return $theme;
    }

    $str1 = explode('/themes/', Helper::options()->themeUrl);
    $str2 = explode('/', $str1[1] ?? '');
    return (string)($str2[0] ?? '');
}

function mango_get_theme_option_name()
{
    $theme = mango_get_current_theme_name();
    return $theme !== '' ? ('theme:' . $theme) : null;
}

function mango_get_theme_settings_from_db()
{
    $name = mango_get_theme_option_name();
    if (!$name) {
        return [];
    }

    $db = Db::get();
    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name)->limit(1));
    if (!$row || !isset($row['value'])) {
        return [];
    }

    $value = @unserialize($row['value']);
    return is_array($value) ? $value : [];
}

function mango_get_backup_option_prefix()
{
    $theme = mango_get_current_theme_name();
    return $theme !== '' ? ('mango_theme_backup:' . $theme . ':') : null;
}

function mango_create_db_backup_entry(array $settings)
{
    $prefix = mango_get_backup_option_prefix();
    if (!$prefix) {
        throw new Exception('Unknown theme');
    }

    $db = Db::get();
    $stamp = date('YmdHis');
    $name = $prefix . $stamp;

    $payload = [
        'theme' => mango_get_current_theme_name(),
        'exportedAt' => date('c'),
        'settings' => $settings,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new Exception('JSON encode failed');
    }

    $db->query(
        $db->insert('table.options')->rows([
            'name' => $name,
            'value' => $json,
            'user' => 0,
        ])
    );

    return $name;
}

function mango_list_db_backups($limit = 20)
{
    $prefix = mango_get_backup_option_prefix();
    if (!$prefix) {
        return [];
    }

    $db = Db::get();
    $rows = $db->fetchAll(
        $db->select('name', 'value')
            ->from('table.options')
            ->where('name LIKE ?', $prefix . '%')
            ->order('name', Db::SORT_DESC)
            ->limit((int)$limit)
    );

    $items = [];
    foreach ($rows as $row) {
        $name = (string)($row['name'] ?? '');
        $value = (string)($row['value'] ?? '');
        $decoded = json_decode($value, true);

        $exportedAt = null;
        if (is_array($decoded) && isset($decoded['exportedAt'])) {
            $exportedAt = (string)$decoded['exportedAt'];
        }

        if (!$exportedAt && preg_match('/:(\\d{14})$/', $name, $m)) {
            $exportedAt = $m[1];
        }

        $items[] = [
            'name' => $name,
            'exportedAt' => $exportedAt,
        ];
    }

    return $items;
}

function mango_get_db_backup_json($name)
{
    $prefix = mango_get_backup_option_prefix();
    $name = (string)$name;
    if (!$prefix || strpos($name, $prefix) !== 0) {
        return null;
    }

    $db = Db::get();
    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name)->limit(1));
    if (!$row || !isset($row['value'])) {
        return null;
    }

    return (string)$row['value'];
}

function mango_delete_db_backup($name)
{
    $prefix = mango_get_backup_option_prefix();
    $name = (string)$name;
    if (!$prefix || strpos($name, $prefix) !== 0) {
        return false;
    }

    $db = Db::get();
    $db->query($db->delete('table.options')->where('name = ?', $name));
    return true;
}

function mango_export_theme_settings_json()
{
    $theme = mango_get_current_theme_name();
    $settings = mango_get_theme_settings_from_db();

    $payload = [
        'theme' => $theme,
        'exportedAt' => date('c'),
        'settings' => $settings,
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function mango_clear_output_buffers()
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
}

function mango_sanitize_imported_settings(array $settings)
{
    unset($settings['_'], $settings['mango_backup_data'], $settings['mango_backup_file']);

    foreach (array_keys($settings) as $k) {
        if (is_string($k) && str_starts_with($k, 'mango_backup_')) {
            unset($settings[$k]);
        }
    }

    return $settings;
}

function mango_save_theme_settings_to_db(array $settings)
{
    $name = mango_get_theme_option_name();
    if (!$name) {
        throw new Exception('Unknown theme');
    }

    $db = Db::get();
    $exists = $db->fetchRow($db->select('name')->from('table.options')->where('name = ?', $name)->limit(1));

    if ($exists) {
        $db->query(
            $db->update('table.options')
                ->rows(['value' => serialize($settings)])
                ->where('name = ?', $name)
        );
    } else {
        $db->query(
            $db->insert('table.options')
                ->rows([
                    'name' => $name,
                    'value' => serialize($settings),
                    'user' => 0,
                ])
        );
    }
}

function mango_handle_theme_backup_request()
{
    if (!defined('__TYPECHO_ADMIN__')) {
        return;
    }

    if (empty($_GET['mango_backup'])) {
        return;
    }

    $action = (string)$_GET['mango_backup'];
    $user = User::alloc();
    if (!$user->pass('administrator', true)) {
        http_response_code(403);
        exit;
    }

    $security = Security::alloc();
    $security->protect();

    if (in_array($action, ['save_db', 'restore_db', 'delete_db', 'import'], true)) {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }
    }

    if ($action === 'save_db') {
        mango_clear_output_buffers();
        try {
            $settings = mango_get_theme_settings_from_db();
            $key = mango_create_db_backup_entry($settings);
            Notice::alloc()->set(_t('已保存备份：%s', $key), 'success');
        } catch (Exception $e) {
            Notice::alloc()->set(_t('保存备份失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'restore_db') {
        mango_clear_output_buffers();
        $key = (string)($_GET['key'] ?? '');
        $json = mango_get_db_backup_json($key);
        if (!$json) {
            Notice::alloc()->set(_t('备份不存在'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $decoded = json_decode($json, true);
        $settings = is_array($decoded) ? ($decoded['settings'] ?? $decoded) : null;
        if (!is_array($settings)) {
            Notice::alloc()->set(_t('备份数据损坏或格式不正确'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        try {
            $settings = mango_sanitize_imported_settings($settings);
            mango_save_theme_settings_to_db($settings);
            Notice::alloc()->set(_t('已从服务器备份恢复主题设置'), 'success');
        } catch (Exception $e) {
            Notice::alloc()->set(_t('恢复失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'delete_db') {
        mango_clear_output_buffers();
        $key = (string)($_GET['key'] ?? '');
        if (mango_delete_db_backup($key)) {
            Notice::alloc()->set(_t('备份已删除'), 'success');
        } else {
            Notice::alloc()->set(_t('删除失败：备份不存在'), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'export') {
        mango_clear_output_buffers();
        $json = mango_export_theme_settings_json();
        $file = 'theme-' . mango_get_current_theme_name() . '-backup-' . date('Ymd-His') . '.json';

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        echo $json ?: '{}';
        exit;
    }

    if ($action === 'import') {
        mango_clear_output_buffers();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }

        $raw = (string)($_POST['mango_backup_data'] ?? '');
        $raw = trim($raw);
        if ($raw === '') {
            Notice::alloc()->set(_t('备份数据为空'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Notice::alloc()->set(_t('备份数据不是有效的 JSON'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $settings = $decoded['settings'] ?? $decoded;
        if (!is_array($settings)) {
            Notice::alloc()->set(_t('备份数据格式不正确'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        try {
            $settings = mango_sanitize_imported_settings($settings);
            mango_save_theme_settings_to_db($settings);
            Notice::alloc()->set(_t('主题设置已从备份恢复'), 'success');
        } catch (Exception $e) {
            Notice::alloc()->set(_t('恢复失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }
}

function mango_render_theme_backup_section()
{
    if (!defined('__TYPECHO_ADMIN__')) {
        return;
    }

    $security = Security::alloc();
    $exportUrl = $security->getAdminUrl('options-theme.php?mango_backup=export');
    $importUrl = $security->getAdminUrl('options-theme.php?mango_backup=import');
    $saveDbUrl = $security->getAdminUrl('options-theme.php?mango_backup=save_db');
    $json = mango_export_theme_settings_json() ?: '{}';
    $safeJson = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');
    $backups = mango_list_db_backups(20);

    echo '<span class="themeConfig"><h3>备份与恢复</h3></span>';
    echo '<div class="info">可将主题设置备份到服务器（数据库）恢复仅影响主题设置，不影响文章/评论数据。</div>';
    echo '<p style="margin: 0 0 10px 0;">
        <button type="button" class="btn primary" id="mango-backup-save-db">保存主题配置</button>
    </p>';

    echo '<p style="margin: 18px 0 10px 0;font-weight:600;">备份列表（最近 20 条）</p>';
    if (empty($backups)) {
        echo '<div class="info">暂无备份，点击“保存主题配置”创建一份。</div>';
    } else {
        echo '<div style="border:1px solid #d9d9d6;border-radius:4px;overflow:hidden;">';
        echo '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
        echo '<thead><tr style="background:#f7f7f7;"><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">时间</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">标识</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">操作</th></tr></thead>';
        echo '<tbody>';
        foreach ($backups as $item) {
            $name = (string)$item['name'];
            $exportedAt = (string)($item['exportedAt'] ?? '');
            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

            $restoreUrl = $security->getAdminUrl('options-theme.php?mango_backup=restore_db&key=' . urlencode($name));
            $deleteUrl = $security->getAdminUrl('options-theme.php?mango_backup=delete_db&key=' . urlencode($name));

            echo '<tr>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;white-space:nowrap;">' . htmlspecialchars($exportedAt, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;word-break:break-all;">' . $safeName . '</td>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;white-space:nowrap;">';
            echo '<button type="button" class="btn primary mango-backup-restore" data-url="' . htmlspecialchars($restoreUrl, ENT_QUOTES, 'UTF-8') . '">恢复</button> ';
            echo '<button type="button" class="btn mango-backup-delete" data-url="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '">删除</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }



    echo '<style>
        .btn{display:inline-block;padding:6px 10px;border-radius:4px;border:1px solid #d9d9d6;background:#fff;cursor:pointer;font-size:12px}
        .btn.primary{background:#467B96;color:#fff;border-color:#467B96}
        .btn:disabled{opacity:.6;cursor:not-allowed}
    </style>';

    echo '<script>
    (function(){
        var saveDbBtn = document.getElementById("mango-backup-save-db");
        var copyBtn = document.getElementById("mango-backup-copy");
        var exportEl = document.getElementById("mango-backup-export");
        var restoreBtn = document.getElementById("mango-backup-restore");
        var importEl = document.getElementById("mango-backup-import");
        var importUrl = ' . json_encode($importUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';
        var saveDbUrl = ' . json_encode($saveDbUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';

        async function mangoPost(url, formData) {
            var resp = await fetch(url, { method: "POST", body: formData || new FormData(), credentials: "same-origin" });
            if (resp.redirected) {
                window.location.href = resp.url;
                return;
            }
            window.location.reload();
        }

        if (saveDbBtn) {
            saveDbBtn.addEventListener("click", async function(){
                if (!confirm("确认将当前主题设置保存到服务器备份？")) return;
                saveDbBtn.disabled = true;
                saveDbBtn.textContent = "保存中...";
                try {
                    await mangoPost(saveDbUrl);
                } catch (e) {
                    alert("保存失败，请检查网络后重试");
                } finally {
                    saveDbBtn.disabled = false;
                    saveDbBtn.textContent = "保存到服务器";
                }
            });
        }

        document.querySelectorAll(".mango-backup-restore").forEach(function(btn){
            btn.addEventListener("click", async function(){
                var url = btn.getAttribute("data-url");
                if (!url) return;
                if (!confirm("确认从该服务器备份恢复？将覆盖当前主题设置。")) return;
                btn.disabled = true;
                btn.textContent = "恢复中...";
                try {
                    await mangoPost(url);
                } catch (e) {
                    alert("恢复失败，请检查网络后重试");
                } finally {
                    btn.disabled = false;
                    btn.textContent = "恢复";
                }
            });
        });

        document.querySelectorAll(".mango-backup-delete").forEach(function(btn){
            btn.addEventListener("click", async function(){
                var url = btn.getAttribute("data-url");
                if (!url) return;
                if (!confirm("确认删除该服务器备份？此操作不可撤销。")) return;
                btn.disabled = true;
                btn.textContent = "删除中...";
                try {
                    await mangoPost(url);
                } catch (e) {
                    alert("删除失败，请检查网络后重试");
                } finally {
                    btn.disabled = false;
                    btn.textContent = "删除";
                }
            });
        });

        if (restoreBtn && importEl) {
            restoreBtn.addEventListener("click", async function(){
                var raw = (importEl.value || "").trim();
                if (!raw) { alert("请粘贴备份 JSON"); return; }
                if (!confirm("确认恢复？将覆盖当前主题设置。")) return;

                restoreBtn.disabled = true;
                restoreBtn.textContent = "恢复中...";
                try {
                    var form = new FormData();
                    form.append("mango_backup_data", raw);
                    var resp = await fetch(importUrl, { method: "POST", body: form, credentials: "same-origin" });
                    if (resp.redirected) {
                        window.location.href = resp.url;
                        return;
                    }
                    window.location.reload();
                } catch (e) {
                    alert("恢复失败，请检查网络后重试");
                } finally {
                    restoreBtn.disabled = false;
                    restoreBtn.textContent = "恢复并覆盖当前设置";
                }
            });
        }
    })();
    </script>';
}

mango_handle_theme_backup_request();
