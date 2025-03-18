<?php 
/**
 * 说说
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div class="col-lg-8">
    <div class="post_container_title">
	    <h1><?php $this->title() ?></h1>
	</div>
        <div class="post_container">
            <?php $this->content(); ?>
        <div id="memos" class="memos"></div>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
        <!-- 再加载lightbox -->
        <link href="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/css/lightbox.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/lightbox2@2.11.3/dist/js/lightbox.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js"></script>
        <script src="https://blog.0tz.top/MemosTimeline/js/api.js"></script>
        <script src="https://blog.0tz.top/MemosTimeline/js/main.js"></script> 
</div></div>
<style>
.memo {
    display: flex;
    flex-direction: column;
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.dark .memo {
    background: #333;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.memo-header {
    display: flex;
    gap: 15px;
    margin-bottom: 12px;
}
.avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}
.memo-content-wrapper {
    flex: 1;
}
.memo-content {
    white-space: pre-wrap;
    line-height: 1.6;
}
.memo-meta {
    color: #666;
    font-size: 0.9em;
    margin-top: 10px;
}
.tag {
    display: inline-block;
    background: #e1f5fe;
    padding: 2px 8px;
    border-radius: 4px;
    color: #0288d1;
    margin-right: 5px;
}
.code {
    background: #f5f5f5;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
.user-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.user-name {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 4px;
}
.memo-time {
    color: #666;
    font-size: 14px;
}
.tags-container {
    margin: 8px 0;
}
.load-more-btn {
    margin: 20px 0;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    background-color: #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}
.dark .load-more-btn {
    background-color: #333;
}
.load-more-btn:hover {
    background-color: #e0e0e0;
}
.load-more-btn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}
.markdown-body {
    line-height: 1.6;
}
.markdown-body p {
    margin: 1em 0;
}
.markdown-body code {
    background: #f6f8fa;
    padding: 0.2em 0.4em;
    border-radius: 3px;
    font-family: monospace;
}
.markdown-body pre {
    background: #f6f8fa;
    padding: 16px;
    border-radius: 6px;
    overflow-x: auto;
}
.markdown-body a {
    color: #0366d6;
    text-decoration: none;
}
.markdown-body a:hover {
    text-decoration: underline;
}
.image-gallery {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin: 10px 0;
}
.image-container {
    position: relative;
    padding-bottom: 100%;
    overflow: hidden;
    border-radius: 8px;
}
.image-container img {
    position: absolute;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.image-container:hover img {
    transform: scale(1.05);
}
.memos-wrapper {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}
/* 超链接自动换行 */
.memo-content a {
    word-wrap: break-word;
    word-break: break-all;
    white-space: normal;
}
/* 代码块样式 */
.memo-content pre {
    margin: 1em 0;
    padding: 1em;
    border-radius: 4px;
    background: #f6f8fa;
    overflow-x: auto;
    max-width: 100%;
}
.memo-content pre code {
    white-space: pre;
    display: block;
    word-wrap: break-word;
    word-break: break-all;
    white-space: normal;
}
.memo-content code {
    font-family: Consolas, Monaco, 'Andale Mono', monospace;
    font-size: 0.9em;
    padding: 0.2em 0.4em;
    border-radius: 3px;
    background: #f6f8fa;
}
/* 行内代码样式 */
.memo-content :not(pre) > code {
    word-wrap: break-word;
    white-space: normal;
}
@media (max-width: 768px) {
    .image-gallery {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
