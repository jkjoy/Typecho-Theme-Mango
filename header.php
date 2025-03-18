<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search'   => _t('包含关键字 %s 的文章'),
            'tag'      => _t('标签 %s 下的文章'),
            'author'   => _t('%s 发布的文章')
        ], '', ' - '); ?><?php $this->options->title(); ?> | <?php $this->options->description(); ?></title>
    <link rel='icon' href='<?php $this->options->faviconUrl(); ?>' type='image/x-icon' />
    <!-- 使用url函数转换相关路径 -->
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/bifont/bootstrap-icons.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/fancybox.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/style.css'); ?>">
    <script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>" id="jquery-min-js"></script>
    <!-- 通过自有函数输出HTML头部信息 -->
    <?php $this->header(); ?>
    <link rel="stylesheet" href="https://cdn.imsun.org/lxgw-wenkai-screen-webfont/style.css" /> 
<style>
    * {font-family:"LXGW WenKai Screen",sans-serif !important;} img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }
</style>
<script>
  const isDark= localStorage.getItem("isDarkMode");
  if(isDark==="1"){
    document.documentElement.classList.add('dark');
  }else{
    document.documentElement.classList.remove('dark');
  }
</script>     
<style>img:is([sizes="auto" i], [sizes^="auto," i]) { contain-intrinsic-size: 3000px 1500px }</style> 
</head>
<body class="home blog">
<header class="header sticky-top">
<div class="container">
		<div class="top">
			<button class="mobile_an" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile_right_nav" aria-controls="mobile_right_nav"><i class="bi bi-list"></i></button>
			<div class="top_l">
            	<h1 class="logo">
                <?php if ($this->options->logoUrl): ?>
                    <a href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->description() ?>"><img src="<?php $this->options->logoUrl() ?>"><b><?php $this->options->title() ?></b></a>
                <?php else: ?>
                    <a href="<?php $this->options->siteUrl(); ?>" title="<?php $this->options->description() ?>"><b><?php $this->options->title() ?></b></a>
	        	<?php endif; ?>
                </h1>
        		<nav class="header-menu">
                    <ul id="menu-menu-1" class="header-menu-ul">
                    <li id="menu-item-13" class="menu-item menu-item-type-custom menu-item-object-custom current-menu-item current_page_item menu-item-home menu-item-13"><a<?php if ($this->is('index')): ?> class="current"<?php endif; ?> href="<?php $this->options->siteUrl(); ?>"><?php _e('首页'); ?></a></li>
                    <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
                    <?php while ($pages->next()): ?>
                        <li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-28">
                               <a<?php if ($this->is('page', $pages->slug)): ?><?php endif; ?> href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>">
                            <?php $pages->title(); ?></a>
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
            <?php \Widget\Contents\Page\Rows::alloc()->to($pages); ?>
            <?php while ($pages->next()): ?>
                <li class="menu-item<?php if ($this->is('page', $pages->slug)): ?> current-menu-item<?php endif; ?>">
                    <a href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>"><?php $pages->title(); ?></a>
                </li>
            <?php endwhile; ?>
            
            <?php $this->widget('Widget_Metas_Category_List')->to($categories); ?>
            <?php if ($categories->have()): ?>
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


<section class="index_area">
    <div class="container">
        <div class="row g-4">