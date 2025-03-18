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
document.getElementById('comment-form').addEventListener('submit', function() {
    var author = document.getElementById('author');
    var mail = document.getElementById('mail');
    var url = document.getElementById('url');
    var textarea = document.getElementById('textarea');
    
    // 保存评论者信息
    if (author && mail && url) {
        setCookie('__typecho_remember_author', author.value, 30);
        setCookie('__typecho_remember_mail', mail.value, 30);
        setCookie('__typecho_remember_url', url.value, 30);
    }
    
    // 禁用提交按钮防止重复提交
    var submitBtn = document.getElementById('misubmit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '提交中...';
    }
    
    // 清空评论框
    if (textarea) {
        textarea.value = '';
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

// 评论回复功能
window.TypechoComment = {
    dom: function (id) {
        return document.getElementById(id);
    },
    
    create: function (tag, attr) {
        var el = document.createElement(tag);
        for (var key in attr) {
            el.setAttribute(key, attr[key]);
        }
        return el;
    },

    reply: function (cid, coid) {
        var comment = this.dom(cid),
            parent = comment.parentNode,
            response = this.dom('<?php echo $this->respondId(); ?>'),
            input = this.dom('comment-parent'),
            form = 'form' == response.tagName ? response : response.getElementsByTagName('form')[0],
            textarea = response.getElementsByTagName('textarea')[0];

        if (null == input) {
            input = this.create('input', {
                'type': 'hidden',
                'name': 'parent',
                'id': 'comment-parent'
            });
            form.appendChild(input);
        }
        
        input.setAttribute('value', coid);

        if (null == this.dom('comment-form-place-holder')) {
            var holder = this.create('div', {
                'id': 'comment-form-place-holder'
            });
            response.parentNode.insertBefore(holder, response);
        }

        comment.appendChild(response);
        this.dom('cancel-comment-reply-link').style.display = '';

        if (null != textarea && 'text' == textarea.name) {
            textarea.focus();
        }

        return false;
    },

    cancelReply: function () {
        var response = this.dom('<?php echo $this->respondId(); ?>'),
            holder = this.dom('comment-form-place-holder'),
            input = this.dom('comment-parent');

        if (null != input) {
            input.parentNode.removeChild(input);
        }

        if (null == holder) {
            return true;
        }

        this.dom('cancel-comment-reply-link').style.display = 'none';
        holder.parentNode.insertBefore(response, holder);
        return false;
    }
};

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
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