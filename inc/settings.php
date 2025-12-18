<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 主题配置项
 */
function themeConfig($form)
{
    echo '<style>.typecho-page-title h2 {font-weight: 600;color: #30ac9aff;}.typecho-page-title h2:before {content: "#";margin-right: 6px;color: #30ac9aff; font-size: 20px;font-weight: 600;}.themeConfig h3 {color: #30ac9aff;font-size: 20px;}.themeConfig h3:before {content: "[";margin-right: 5px;color: #cde51bff;font-size: 25px;}.themeConfig h3:after {content: "]";margin-left: 5px;color: #cde51bff;font-size: 25px;}.info{border: 1px solid #ffadad;padding: 20px;margin: -15px 10px 25px 0;background: #ffffff;border-radius: 5px;color: #e5ac1bff;}</style>';
    // 直接在主题设置页面调用更新检查
    themeAutoUpgradeNotice();
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CgogIDwhLS0g6IqS5p6c5Li75L2TIC0tPgogIDxwYXRoIGQ9Ik0xNTAgMzAKICAgICAgIEM4MCAzMCwgNDAgOTAsIDQwIDE1MAogICAgICAgQzQwIDIyMCwgMTEwIDI2MCwgMTcwIDI1MAogICAgICAgQzIzMCAyNDAsIDI2MCAxOTAsIDI1MCAxNDAKICAgICAgIEMyNDAgOTAsIDIwMCAzMCwgMTUwIDMwIFoiIGZpbGw9IiNGRkM5MzMiIHN0cm9rZT0iI0U2QTgwMCIgc3Ryb2tlLXdpZHRoPSI0Ij48L3BhdGg+CgogIDwhLS0g6auY5YWJIC0tPgogIDxwYXRoIGQ9Ik0xMTAgNzAKICAgICAgIEM4MCAxMDAsIDcwIDE0MCwgODAgMTgwCiAgICAgICBDODUgMjAwLCAxMDUgMjE1LCAxMTUgMjIwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC41KSIgc3Ryb2tlLXdpZHRoPSI2IiBzdHJva2UtbGluZWNhcD0icm91bmQiPjwvcGF0aD4KCiAgPCEtLSDlj7blrZAgLS0+CiAgPHBhdGggZD0iTTE3MCAyMAogICAgICAgQzIwMCAtMTAsIDI1MCAtNSwgMjYwIDIwCiAgICAgICBDMjMwIDMwLCAyMDAgNDAsIDE3MCAyMCBaIiBmaWxsPSIjMkU4QjU3Ij48L3BhdGg+CgogIDwhLS0g5Y+26ISJIC0tPgogIDxwYXRoIGQ9Ik0xNzUgMjAgQzIxMCAxNSwgMjM1IDIwLCAyNTUgMjIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzFGNkY0MyIgc3Ryb2tlLXdpZHRoPSIyIj48L3BhdGg+Cjwvc3ZnPgo=',
        _t('<span class="themeConfig"><h3>博客设置</h3></span><div class="info">全局设置</div>站点 LOGO 地址'),
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

    if (function_exists('mango_render_theme_backup_section')) {
        mango_render_theme_backup_section();
    }
}
