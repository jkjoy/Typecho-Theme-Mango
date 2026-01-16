<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
    </div>
</div>
</section>
<?php if ($this->options->showlinks): ?>
<section class="links mobile_none">
    <div class="container">
        <span>友情链接：</span>
        <?php Links_Plugin::output('<a href="{url}" target="_blank" rel="me noopener" title="{title}">{name}</a>'); ?>        
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