<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="description" content="<?php $this->options->description(); ?>" />
    <meta name="keywords" content="<?php $this->options->keywords(); ?>" />
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ' - '); ?><?php $this->options->title(); ?> | <?php $this->options->description(); ?></title>
    <?php if ($this->options->faviconUrl): ?>
    <link rel='icon' href='<?php $this->options->faviconUrl(); ?>' type='image/x-icon' />
    <?php else: ?>
    <link rel='icon' href='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CgogIDwhLS0g6IqS5p6c5Li75L2TIC0tPgogIDxwYXRoIGQ9Ik0xNTAgMzAKICAgICAgIEM4MCAzMCwgNDAgOTAsIDQwIDE1MAogICAgICAgQzQwIDIyMCwgMTEwIDI2MCwgMTcwIDI1MAogICAgICAgQzIzMCAyNDAsIDI2MCAxOTAsIDI1MCAxNDAKICAgICAgIEMyNDAgOTAsIDIwMCAzMCwgMTUwIDMwIFoiIGZpbGw9IiNGRkM5MzMiIHN0cm9rZT0iI0U2QTgwMCIgc3Ryb2tlLXdpZHRoPSI0Ij48L3BhdGg+CgogIDwhLS0g6auY5YWJIC0tPgogIDxwYXRoIGQ9Ik0xMTAgNzAKICAgICAgIEM4MCAxMDAsIDcwIDE0MCwgODAgMTgwCiAgICAgICBDODUgMjAwLCAxMDUgMjE1LCAxMTUgMjIwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC41KSIgc3Ryb2tlLXdpZHRoPSI2IiBzdHJva2UtbGluZWNhcD0icm91bmQiPjwvcGF0aD4KCiAgPCEtLSDlj7blrZAgLS0+CiAgPHBhdGggZD0iTTE3MCAyMAogICAgICAgQzIwMCAtMTAsIDI1MCAtNSwgMjYwIDIwCiAgICAgICBDMjMwIDMwLCAyMDAgNDAsIDE3MCAyMCBaIiBmaWxsPSIjMkU4QjU3Ij48L3BhdGg+CgogIDwhLS0g5Y+26ISJIC0tPgogIDxwYXRoIGQ9Ik0xNzUgMjAgQzIxMCAxNSwgMjM1IDIwLCAyNTUgMjIiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzFGNkY0MyIgc3Ryb2tlLXdpZHRoPSIyIj48L3BhdGg+Cjwvc3ZnPgo=' type='image/x-icon' />
    <?php endif; ?>
    <!-- 使用url函数转换相关路径 -->
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/bootstrap-icons.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/fancybox.css'); ?>">
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>" id="jquery-min-js"></script>
    <script>
(function() {
  // 检查当前是否在日间时段（6:00-18:00）
  function isDaytime() {
    const now = new Date();
    const hour = now.getHours();
    return hour >= 6 && hour < 18;
  }
  // 后台设置
  var themeMode = '<?php echo isset($this->options->darkMode) && $this->options->darkMode ? $this->options->darkMode : "light"; ?>' || 'light'; 
  // 统一使用 isDarkMode 作为存储键，值用 "1"/"0"
  function setDark() {
    localStorage.setItem("isDarkMode", "1");
    document.documentElement.classList.add("dark");
  }
  function removeDark() {
    localStorage.setItem("isDarkMode", "0");
    document.documentElement.classList.remove("dark");
  }
  // 初始化
  var savedMode = localStorage.getItem("isDarkMode");
  if (savedMode === "1" || savedMode === "0") {
    // 用户手动设置过
    if (savedMode === "1") {
      setDark();
    } else {
      removeDark();
    }
  } else if (themeMode === 'auto') {
    // 自动模式：根据时间判断
    if (!isDaytime()) {
      setDark();
    } else {
      removeDark();
    }
  } else {
    // 固定模式：跟随后台设置
    if (themeMode === 'dark') {
      setDark();
    } else {
      removeDark();
    }
  }
  // 在自动模式下设置定时器
  if (themeMode === 'auto' && !savedMode) {
    function getNextChangeTime() {
      const now = new Date();
      const next = new Date();
      
      if (isDaytime()) {
        next.setHours(18, 0, 0, 0);
      } else {
        next.setHours(6, 0, 0, 0);
        if (next <= now) {
          next.setDate(next.getDate() + 1);
        }
      } 
      return next.getTime() - now.getTime();
    }
    function scheduleNextChange() {
      const delay = getNextChangeTime();
      setTimeout(() => {
        if (!localStorage.getItem("isDarkMode")) {
          if (isDaytime()) {
            removeDark();
          } else {
            setDark();
          }
          scheduleNextChange();
        }
      }, delay);
    }
    scheduleNextChange();
  }
  // 切换按钮函数
  window.switchDarkMode = function() {
    let isDark = localStorage.getItem("isDarkMode");
    if (isDark === "1") {
      removeDark();
    } else {
      setDark();
    }
  };
  // 重置为自动模式
  window.resetDarkMode = function() {
    localStorage.removeItem("isDarkMode");
    if (themeMode === 'auto') {
      if (isDaytime()) {
        removeDark();
      } else {
        setDark();
      }
      scheduleNextChange();
    } else {
      if (themeMode === 'dark') {
        setDark();
      } else {
        removeDark();
      }
    }
  };
})();
</script>
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/style.css'); ?>?v=<?php echo time(); ?>">
<?php $this->header(); ?>
</head>
<body class="home blog">
<header class="header sticky-top">
<div class="container">
		<div class="top">
			<button class="mobile_an" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile_right_nav" aria-controls="mobile_right_nav"><i class="bi bi-list"></i></button>
			<div class="top_l">
        <h1 class="logo">
          <a href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->description() ?>"><?php if ($this->options->logoUrl): ?><img src="<?php $this->options->logoUrl() ?>"><?php else: ?><?php endif; ?><b><?php $this->options->title() ?></b></a>
        </h1>
        		<nav class="header-menu">
                    <ul id="menu-menu-1" class="header-menu-ul">
                    <li id="menu-item-13" class="menu-item menu-item-type-custom menu-item-object-custom current-menu-item current_page_item menu-item-home menu-item-13">
                        <a<?php if ($this->is('index')): ?> class="current"<?php endif; ?> href="<?php $this->options->siteUrl(); ?>"><i class="bi bi-house-door-fill me-1"></i>首页</a>
                    </li>
                    <li class="menu-item-has-children">
                        <a title="分类">
                        <i class="bi bi-folder-fill me-1"></i>分类
                        </a>
                        <ul class="sub-menu" style="display: none;">
                        <?php $categories = Typecho_Widget::widget('Widget_Metas_Category_List'); while($categories->next()): ?>
                        <li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-28">
                            <a href="<?php $categories->permalink(); ?>">
                            <?php echo categoryIcon($categories); ?>
                            </a>
                        </li>
                        <?php endwhile; ?>
                        </ul>
                    </li>
                    <?php $this->widget('Widget_Contents_Page_List')->to($pages);while($pages->next()): ?>
                        <li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-28">
                             <a href="<?php $pages->permalink(); ?>">
                             <?php echo pageIcon($pages->slug, $pages->title); ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                    </ul>
                </nav>        	
            </div>
        	<div class="top_r">
        		<div class="top_r_an theme-switch me-4" onclick="switchDarkMode()">
                    <i class="bi bi-lightbulb-fill"></i>
                </div>
				<button class="top_r_an" type="button" data-bs-toggle="offcanvas" data-bs-target="#c_sousuo">
                    <i class="bi bi-search"></i>
                </button>
        	</div>
        </div>
	</div>
</header>

<div class="offcanvas offcanvas-top" tabindex="-1" id="c_sousuo" aria-labelledby="c_sousuoLabel">
    <div class="container">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        <div class="row justify-content-center">
            <div class="col-10 col-lg-6 search_box">
                <form action="<?php $this->options->siteUrl(); ?>" class="ss_a clearfix" method="get">
                    <input name="s" aria-label="Search" type="text" onblur="if(this.value=='')this.value='搜索'" onfocus="if(this.value=='搜索')this.value=''" value="搜索">
                    <button type="submit" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobile_right_nav" aria-labelledby="mobile_right_navLabel">
	<div class="mobile_head">
		<div class="mobile_head_logo">
                <?php if ($this->options->logoUrl): ?>
                    <a href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->description() ?>"><img src="<?php $this->options->logoUrl() ?>"><b><?php $this->options->title() ?></b></a>
                <?php else: ?>
                    <a href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->description() ?>"><b><?php $this->options->title() ?></b></a>
	        	<?php endif; ?>
        </div>
		<div class="theme-switch" onclick="switchDarkMode()"><i class="bi bi-lightbulb-fill"></i></div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
    <div id="sjcldnav" class="menu-zk">
        <ul id="menu-mobile">
            <li class="menu-item<?php if ($this->is('index')): ?> current-menu-item<?php endif; ?>">
                <a href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a>
            </li>
            <?php \Widget\Contents\Page\Rows::alloc()->to($pages); while ($pages->next()): ?>
                <li class="menu-item<?php if ($this->is('page', $pages->slug)): ?> current-menu-item<?php endif; ?>">
                    <a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a>
                </li>
            <?php endwhile; ?>
            
            <?php $this->widget('Widget_Metas_Category_List')->to($categories);if ($categories->have()): ?>
            <li class="menu-item menu-item-has-children">
                <a href="javascript:void(0);"><?php _e('分类'); ?></a>
                <ul class="sub-menu">
                    <?php while ($categories->next()): ?>
                    <li class="menu-item">
                        <a href="<?php $categories->permalink(); ?>" title="<?php $categories->name(); ?>"><?php $categories->name(); ?> (<?php $categories->count(); ?>)</a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php if ($this->is('index')):$this->need('slidesposts.php');endif; ?> 

<section class="index_area">
    <div class="container">
        <div class="row g-4">
