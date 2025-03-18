<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/comments.css'); ?>">
<?php 
// 评论者等级判断函数
function commentApprove($comment, $email) {
    // 博主
    if ($comment->authorId == 1) {
        return array(
            'bgColor' => '#FF6C6C',
            'userDesc' => '博主',
            'userLevel' => '博主'
        );
    }
    
    // 访客
    return array(
        'bgColor' => '#888888',
        'userDesc' => '访客',
        'userLevel' => '访客'
    );
}

// 获取父评论链接
function getPermalinkFromCoid($coid) {
    $db = Typecho_Db::get();
    $row = $db->fetchRow($db->select('author')->from('table.comments')->where('coid = ?', $coid));
    if (empty($row)) return '';
    return '<a href="#comment-' . $coid . '">@' . $row['author'] . '</a> ';
}
?>

<div class="post_comment" id="post_comment_anchor">
    <div id="comments" class="comments-area">
        <div class="layoutSingleColumn">
            <?php $this->comments()->to($comments); ?>
            <?php 
            $language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
            if($this->allow('comment') && stripos($language, 'zh') > -1): 
            ?>
                <h3 class="comments-title">
                    <i class="bi bi-filter me-2"></i>评论<small>(<?php $this->commentsNum(_t('0'), _t('1'), _t('%d')); ?>)</small>
                </h3>
                
                <?php if ($comments->have()): ?>
                    <?php 
// 重写评论显示函数
function threadedComments($comments, $options) {
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    $commentApprove = commentApprove($comments, $comments->mail);
?>
    <li id="<?php $comments->theId(); ?>" class="comment even thread-even depth-1 <?php 
        if ($comments->levels == 0) {
            echo 'comment parent';
        } else {
            echo 'comment child';
        }
        echo $commentClass; 
    ?>">
        <article class="comment-body" id="div-<?php $comments->theId(); ?>">
            <footer class="comment-meta">
                <div class="comment-author vcard">
                    <?php echo $comments->gravatar('40', ''); ?>
                    <b class="fn">
                    <?php if ($comments->url): ?>
                        <a href="<?php echo $comments->url ?>" target="_blank" rel="external nofollow" title="<?php echo $commentApprove['userDesc']; ?>">
                            <?php echo $comments->author; ?>
                        </a>
                    <?php else: ?>
                        <?php echo $comments->author; ?>  
                    <?php endif; ?>
                    </b><span class="says">说道：</span>
                </div>
                <div class="comment-metadata">
                    <a href="<?php $comments->permalink(); ?>">
                        <time datetime="<?php $comments->date('Y-m-d H:i'); ?>">
                            <?php $comments->date('Y-m-d H:i'); ?>
                        </time>
                    </a>
                </div><!-- .comment-metadata -->
            </footer><!-- .comment-meta -->
            <div class="comment-content">
                <?php if ($comments->parent) {echo getPermalinkFromCoid($comments->parent);} $comments->content(); ?>
            </div><!-- .comment-content -->
            <div class="reply">
                <?php $comments->reply(); ?>
            </div>
        </article><!-- .comment-body -->
        <?php if ($comments->children) { ?>
            <ol class="children">
                <?php $comments->threadedComments($options); ?>
            </ol><!-- .children -->
        <?php } ?>
    </li><!-- #comment-## -->
<?php } ?>

<?php if ($comments->have()): ?>
    <ol class="comment-list">
        <?php 
        $parameter = array(
            'before'        => '',
            'after'         => '',
            'beforeAuthor'  => '',
            'afterAuthor'   => '',
            'beforeDate'    => '',
            'afterDate'     => '',
            'dateFormat'    => 'Y-m-d H:i'
        );
        $comments->listComments($parameter); 
        ?>
    </ol><!-- .comment-list -->
<?php endif; ?>
                <!-- 评论分页 -->
                <nav class="navigation comments-pagination" aria-label="评论分页">
               
                    <?php 
                    $comments->pageNav(
                        ' ',
                        ' ',
                        1,
                    '...',
                    array(
                        'wrapTag' => 'div',
                        'wrapClass' => 'nav-links',
                        'itemTag' => '',
                        'textTag' => 'page-numbers current"',
                        'itemClass' => 'page-numbers',
                        'currentClass' => 'page-numbers current',
                        'prevClass' => 'hidden',
                        'nextClass' => 'hidden'
                    )
                );
                ?>
              
                </nav>
                <?php endif; ?>

                <!-- 评论表单 -->
                <div id="<?php $this->respondId(); ?>" class="comment-respond">
                <h3 id="reply-title" class="comment-reply-title">
                    <i class="bi bi-keyboard me-1"></i>发布评论 
                    <small>
                        <?php $comments->cancelReply(); ?>
                    </small>
                </h3>
                    <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="comment-form">
                        <?php if($this->user->hasLogin()): ?>
                        <p>
                            登录身份: <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>.
                            <a href="<?php $this->options->logoutUrl(); ?>" title="Logout">退出 &raquo;</a>
                        </p>
                        <?php else: ?>
                        <p class="comment-form-author">
                            <input placeholder="称呼 *" type="text" name="author" id="author" class="text" value="<?php echo $previousAuthor; ?>" required />
                        </p>
                        <p class="comment-notes">
                            <input placeholder="邮箱<?php if ($this->options->commentsRequireMail): ?> *<?php endif; ?>" type="email" name="mail" id="mail" class="text" value="<?php echo $previousEmail; ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?> />
                        </p>
                        <p class="comment-form-url">
                            <input type="url" name="url" id="url" class="text" placeholder="http(s)://<?php if ($this->options->commentsRequireURL): ?> *<?php endif; ?>" value="<?php echo $previousUrl; ?>"<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?> />
                        </p>
                        <p class="comment-form-remember">
                            <label>
                            <input type="checkbox" name="remember" id="remember" <?php if($this->remember('author',true)): ?> checked="checked"<?php endif; ?> />
                             记住我的个人信息 (保存一个月)
                            </label>
                        </p>
                        <?php endif; ?>
                        
                        <p class="comment-form-comment">
                            <textarea rows="8" cols="50" name="text" id="textarea" class="textarea" 
                                    onkeydown="if(event.ctrlKey&&event.keyCode==13){document.getElementById('misubmit').click();return false};" 
                                    placeholder="雁过留声,人过留名" 
                                    required><?php $this->remember('text'); ?></textarea>
                        </p>

                        <p class="form-submit">
                            <button type="submit" class="submit" id="misubmit">提交评论</button>
                        </p>
                    </form>
                </div><!-- #respond -->
            <?php endif; ?>
        </div>
    </div>
</div> 
<script>
// 评论表单提交处理
document.getElementById('comment-form').addEventListener('submit', function(event) {
    var author = document.getElementById('author');
    var mail = document.getElementById('mail');
    var url = document.getElementById('url');
    var textarea = document.getElementById('textarea');
    var remember = document.getElementById('remember');
    
    // 检查评论内容是否为空
    if (textarea && textarea.value.trim() === '') {
        alert('必须填写评论内容');
        event.preventDefault();
        return false;
    }

    // 只有在选中"记住我"时才保存评论者信息
    if (remember && remember.checked) {
        if (author && mail && url) {
            setCookie('__typecho_remember_author', author.value, 30);
            setCookie('__typecho_remember_mail', mail.value, 30);
            setCookie('__typecho_remember_url', url.value, 30);
            setCookie('__typecho_remember_checkbox', 'true', 30);
        }
    } else {
        // 如果未选中，则清除已存储的cookie
        deleteCookie('__typecho_remember_author');
        deleteCookie('__typecho_remember_mail');
        deleteCookie('__typecho_remember_url');
        deleteCookie('__typecho_remember_checkbox');
    }
    
    // 禁用提交按钮防止重复提交
    var submitBtn = document.getElementById('misubmit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '提交中...';
    }
});

// Cookie设置函数
function setCookie(name, value, days) {
    var expires = '';
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
}

// Cookie删除函数
function deleteCookie(name) {
    setCookie(name, '', -1);
}

// Cookie获取函数
function getCookie(name) {
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) == 0) {
            return decodeURIComponent(c.substring(nameEQ.length, c.length));
        }
    }
    return null;
}

// 页面加载时自动填充已保存的信息
document.addEventListener('DOMContentLoaded', function() {
    var author = document.getElementById('author');
    var mail = document.getElementById('mail');
    var url = document.getElementById('url');
    var remember = document.getElementById('remember');
    
    // 如果之前选择了记住信息，则自动填充表单
    if (getCookie('__typecho_remember_checkbox') === 'true') {
        if (remember) remember.checked = true;
        if (author) author.value = getCookie('__typecho_remember_author') || '';
        if (mail) mail.value = getCookie('__typecho_remember_mail') || '';
        if (url) url.value = getCookie('__typecho_remember_url') || '';
    }
    
    // 为评论添加过渡效果
    var comments = document.querySelectorAll('.comment-body');
    comments.forEach(function(comment) {
        comment.style.opacity = '0';
        comment.style.transform = 'translateY(10px)';
        setTimeout(function() {
            comment.style.opacity = '1';
            comment.style.transform = 'translateY(0)';
        }, 100);
    });
});
</script>