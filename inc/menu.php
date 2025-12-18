<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
/**
 * 生成页面图标的函数
 */
function pageIcon($slug, $title) {
    $icon = '';
    if ($slug == 'memos') {
        $icon = '<i class="bi bi-chat-fill me-1"></i>';
    } elseif ($slug == 'links') {
        $icon = '<i class="bi bi-folder-symlink-fill me-1"></i>';
    } elseif ($slug == 'tags') {
        $icon = '<i class="bi bi-tags-fill me-1"></i>';
    } elseif ($slug == 'categories') {
        $icon = '<i class="bi bi-folder-fill me-1"></i>';
    } elseif ($slug == 'comments') {
        $icon = '<i class="bi bi-chat-dots-fill me-1"></i>';
    } elseif ($slug == 'gbook') {
        $icon = '<i class="bi bi-cloud-arrow-up-fill me-1"></i>';
    } elseif ($slug == 'search') {
        $icon = '<i class="bi bi-search me-1"></i>';
    } elseif ($slug == 'archives') {
        $icon = '<i class="bi bi-calendar-heart-fill me-1"></i>';
    } elseif ($slug == 'tools') {
        $icon = '<i class="bi bi-tools me-1"></i>';
    } elseif ($slug == 'help') {
        $icon = '<i class="bi bi-question-circle-fill me-1"></i>';
    } elseif ($slug == 'about') {
        $icon = '<i class="bi bi-info-circle-fill me-1"></i>';
    } 
    return $icon . $title;
}

/**
 * 生成分类icon的函数
 */
function categoryIcon($categories) {
    $icon = '';
    if ($categories->slug == 'images') {
        $icon = '<i class="bi bi-images me-1"></i>';
    } elseif ($categories->slug == 'share') {
        $icon = '<i class="bi bi-share-fill me-1"></i>';
    } elseif ($categories->slug == 'NULL') {
        $icon = '<i class="bi bi-speaker-fill me-1"></i>';
    } elseif ($categories->slug == 'memos') {
        $icon = '<i class="bi bi-chat me-1"></i>';
    } elseif ($categories->slug == 'codes') {
        $icon = '<i class="bi bi-code me-1"></i>';
    } elseif ($categories->slug == 'diary') {
        $icon = '<i class="bi bi-journal-text me-1"></i>';
    } elseif ($categories->slug == 'logs') {
        $icon = '<i class="bi bi-person-fill me-1"></i>';
    } elseif ($categories->slug == 'test') {
        $icon = '<i class="bi bi-calendar-fill me-1"></i>';
    } elseif ($categories->slug == 'tools') {
        $icon = '<i class="bi bi-tools me-1"></i>';
    } elseif ($categories->slug == 'music') {
        $icon = '<i class="bi bi-music-note me-1"></i>';
    } elseif ($categories->slug == 'links') {
        $icon = '<i class="bi bi-link me-1"></i>';
    } elseif ($categories->slug == 'video') {
        $icon = '<i class="bi bi-camera-video me-1"></i>';
    } elseif ($categories->slug == 'books') {
        $icon = '<i class="bi bi-book me-1"></i>';
    } elseif ($categories->slug == 'games') {
        $icon = '<i class="bi bi-gamepad me-1"></i>';
    } elseif ($categories->slug == 'themes') {
        $icon = '<i class="bi bi-palette me-1"></i>';
    } elseif ($categories->slug == 'plugins') {
        $icon = '<i class="bi bi-gear-fill me-1"></i>';
    } elseif ($categories->slug == 'photo') {
        $icon = '<i class="bi bi-images me-1"></i>';
    } else {
        $icon = '<i class="bi bi-folder-fill me-1"></i>';
    }

    return $icon . $categories->name;
}