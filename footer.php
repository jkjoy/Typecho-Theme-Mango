<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
    </div>
</div>
</section>
<?php
$footerLinks = [];
if ($this->options->showlinks) {
    try {
        $db = Typecho_Db::get();
        $links = $db->fetchAll(
            $db->select('name', 'url', 'description')
                ->from('table.links')
                ->where('state = ?', 1)
                ->where('sort = ?', '推荐')
                ->order('order', Typecho_Db::SORT_ASC)
        );

        foreach ($links as $link) {
            if (trim((string)($link['name'] ?? '')) !== '' && trim((string)($link['url'] ?? '')) !== '') {
                $footerLinks[] = $link;
            }
        }
    } catch (Exception $error) {
        error_log('Mango footer links table query failed: ' . $error->getMessage());
    }
}
?>
<?php if (!empty($footerLinks)): ?>
<section class="links mobile_none">
    <div class="container">
        <span>友情链接：</span>
        <?php foreach ($footerLinks as $link):
            $name = trim((string)$link['name']);
            $url = trim((string)$link['url']);
            $description = trim((string)($link['description'] ?? ''));
            ?>
            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
               target="_blank"
               rel="me noopener noreferrer"
               title="<?php echo htmlspecialchars($description !== '' ? $description : $name, ENT_QUOTES, 'UTF-8'); ?>"
            ><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<footer class="footbox">
    <div class="container">
    	<div class="copyright">&copy; <?php echo date('Y'); ?> 
        <?php $this->options->title(); ?></a>.
        <a href="https://typecho.org" rel="external nofollow" target="_blank" style="color: #fff;">Typecho</a>强力驱动.
        <p class="hidden"> Theme by <a href="https://www.imsun.org" target="_blank" style="color: #fff;">老孙</a> & <a href="https://huitheme.com" rel="external nofollow" target="_blank">HUiTHEME</a>绘主题. </p>  
        <?php if($this->options->icpbeian): ?>
        <a class="beian" href="https://beian.miit.gov.cn/" rel="external nofollow" target="_blank" title="备案号"><i class="bi bi-shield-check me-1"></i><?php $this->options->icpbeian() ?></a>
        <?php endif; ?>
        </div>
	</div>
</footer>
<?php if ($this->options->tongji): echo $this->options->tongji(); endif; ?>
<!-- end #footer -->
<button class="scrollToTopBtn" title="返回顶部"><i class="bi bi-chevron-up"></i></button>
<?php $this->footer(); ?>
<script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/main.js'); ?>"></script>
<script type="text/javascript" src="<?php $this->options->themeUrl('assets/js/fancybox.js'); ?>"></script>
</body>
</html>
