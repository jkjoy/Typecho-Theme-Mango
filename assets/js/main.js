 // 监听按钮点击
  jQuery(document).ready(function($){
  //table预设calss
  $('.wznrys table').addClass("table");
  });
  $(document).ready(function(){
      //子菜单点击展开
  });
  //列表ajax加载
  jQuery(document).ready(function($) {
  });
  //导航菜单
  function ds_mainmenu(ulclass){
      $(document).ready(function(){
          $(ulclass+' li').hover(function(){
              $(this).children("ul").show();
          },function(){
              $(this).children("ul").hide();
          });
      });
  }
  ds_mainmenu('.header-menu-ul');
  
  //返回顶部
  const scrollToTopBtn = document.querySelector(".scrollToTopBtn")
  const rootElement = document.documentElement
  function handleScroll() {
    const scrollTotal = rootElement.scrollHeight - rootElement.clientHeight
    if ((rootElement.scrollTop / scrollTotal ) > 0.80 ) {
      scrollToTopBtn.classList.add("showBtn")
    } else {
      scrollToTopBtn.classList.remove("showBtn")
    }
  }
  function scrollToTop() {
    rootElement.scrollTo({
      top: 0,
      behavior: "smooth"
    })
  }
  scrollToTopBtn.addEventListener("click", scrollToTop)
  document.addEventListener("scroll", handleScroll)

  // 轻量提示条（无需 Bootstrap JS）
  function mangoToast(message, type) {
    type = type || 'info';
    if (!message) return;

    if (!document.getElementById('mango-toast-style')) {
      var style = document.createElement('style');
      style.id = 'mango-toast-style';
      style.textContent =
        '.mango-toast-wrap{position:fixed;left:0;right:0;bottom:18px;z-index:99999;display:flex;justify-content:center;pointer-events:none;padding:0 12px}' +
        '.mango-toast{pointer-events:auto;max-width:520px;width:max-content;min-width:160px;padding:10px 14px;border-radius:12px;' +
        'border:1px solid rgba(0,0,0,.10);background:#fff;color:#111827;box-shadow:0 10px 28px rgba(17,24,39,.12);' +
        'font-size:14px;line-height:1.6;opacity:0;transform:translateY(6px);transition:opacity .18s ease,transform .18s ease}' +
        '.mango-toast.show{opacity:1;transform:translateY(0)}' +
        '.mango-toast.success{border-color:rgba(16,185,129,.25)}' +
        '.mango-toast.warning{border-color:rgba(245,158,11,.25)}' +
        '.mango-toast.error{border-color:rgba(239,68,68,.25)}' +
        '.dark .mango-toast{background:#111827;color:rgba(255,255,255,.9);border-color:rgba(255,255,255,.12);box-shadow:none}';
      document.head.appendChild(style);
    }

    var wrap = document.querySelector('.mango-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'mango-toast-wrap';
      document.body.appendChild(wrap);
    }

    var toast = document.createElement('div');
    toast.className = 'mango-toast ' + type;
    toast.textContent = message;
    wrap.appendChild(toast);

    requestAnimationFrame(function () {
      toast.classList.add('show');
    });

    setTimeout(function () {
      toast.classList.remove('show');
      setTimeout(function () {
        if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
      }, 220);
    }, 2200);
  }

  function mangoGetCookie(name) {
    var cookies = (document.cookie || '').split(';');
    for (var i = 0; i < cookies.length; i++) {
      var part = cookies[i].trim();
      if (!part) continue;
      if (part.indexOf(name + '=') === 0) {
        return decodeURIComponent(part.substring(name.length + 1));
      }
    }
    return null;
  }

  function mangoGetTypechoLikedCids() {
    // Typecho Cookie 会带 md5 前缀，这里按后缀匹配即可
    var cookies = (document.cookie || '').split(';');
    for (var i = 0; i < cookies.length; i++) {
      var part = cookies[i].trim();
      if (!part) continue;
      var eq = part.indexOf('=');
      if (eq === -1) continue;
      var key = part.substring(0, eq);
      if (key && key.slice(-('extend_contents_likes'.length)) === 'extend_contents_likes') {
        try {
          var val = decodeURIComponent(part.substring(eq + 1));
          return (val || '').split(',').filter(Boolean);
        } catch (e) {
          return [];
        }
      }
    }
    return [];
  }

  function mangoHasLiked(cid) {
    if (cid === undefined || cid === null) return false;
    var id = String(cid);
    if (mangoGetCookie('post_like_' + id) !== null) return true;
    var list = mangoGetTypechoLikedCids();
    return list.indexOf(id) !== -1;
  }

  function mangoSetLikeCookie(cid) {
    var id = String(cid);
    var expires = new Date();
    expires.setHours(23, 59, 59, 999);
    document.cookie = 'post_like_' + id + '=1; expires=' + expires.toUTCString() + '; path=/';
  }

  function mangoApplyLikeState($root) {
    $root = $root || $(document);
    $root.find('.specsZan[data-id]').each(function () {
      var $a = $(this);
      var cid = $a.data('id');
      if (mangoHasLiked(cid)) {
        $a.addClass('done');
      }
    });
  }
   
  // 点赞功能
  $(document).ready(function(){
  // 使用事件委托处理点赞
  $(document).on('click', '.specsZan', function(e){
      e.preventDefault();
      var $this = $(this);
      var cid = $this.data('id');
      
      // 检查是否已经点赞
      if(mangoHasLiked(cid)) {
          mangoToast('请勿重复点赞', 'warning');
          return false;
      }
      
      // 检查是否正在加载
      if($this.hasClass('loading')) return false;
      
      // 添加加载状态
      $this.addClass('loading');
      
      // 发送点赞请求
      $.ajax({
          url: window.location.href,
          type: 'POST',
          data: {
              action: 'specs_zan',
              cid: cid
          },
          success: function(data){
              data = (data || '').toString().trim();
              if (data === 'already_liked') {
                  $('.specsZan[data-id="' + cid + '"]').addClass('done');
                  mangoSetLikeCookie(cid);
                  mangoToast('请勿重复点赞', 'warning');
                  return;
              }

              var num = parseInt(data, 10);
              if (!isNaN(num) && num > 0) {
                  // 更新同一文章的所有点赞数
                  $('.specsZan[data-id="' + cid + '"] .count').text(num);
                  $('.specsZan[data-id="' + cid + '"]').addClass('done');
                  mangoSetLikeCookie(cid);
                  mangoToast('点赞成功', 'success');
              } else {
                  mangoToast('点赞失败，请稍后重试', 'error');
              }
          },
          error: function(){
              mangoToast('点赞失败，请检查网络后重试', 'error');
          },
          complete: function(){
              // 只移除 loading 状态，保持 done 状态
              $this.removeClass('loading');
          }
      });
      
      return false;
  });

  // 页面加载时同步点赞状态
  mangoApplyLikeState($(document));
});

// 列表缩略图加载失败兜底（避免第三方图失效导致大片破图）
function mangoBindPostThumbnailFallback($root) {
  $root = $root || $(document);
  $root.find('img.post-thumbnail').each(function () {
    var img = this;
    if (img.dataset && img.dataset.fallbackBound === '1') return;
    if (img.dataset) img.dataset.fallbackBound = '1';

    $(img).on('error', function () {
      var fallback = img.getAttribute('data-fallback');
      if (fallback && img.src !== fallback) {
        img.src = fallback;
      }
    });
  });
}
$(document).ready(function () {
  mangoBindPostThumbnailFallback($(document));
});

   
    // 加载更多文章
    $(document).on('click', '.post-read-more a', function(e){
        e.preventDefault();
        var $btn = $(this);
        var nextPage = $btn.attr('href');

        function addQueryParam(url, key, value) {
            if (!url) return url;
            var parts = url.split('#');
            var base = parts[0];
            var hash = parts.length > 1 ? '#' + parts.slice(1).join('#') : '';

            var re = new RegExp('([?&])' + key + '=([^&]*)', 'i');
            if (re.test(base)) return url;

            var sep = base.indexOf('?') === -1 ? '?' : '&';
            return base + sep + encodeURIComponent(key) + '=' + encodeURIComponent(value) + hash;
        }
        
        if($btn.hasClass('loading')) return false;
        
        $btn.addClass('loading').text('加载中...');
        
        $.ajax({
            url: addQueryParam(nextPage, 'mango_json', '1'),
            type: 'GET',
            dataType: 'json',
            success: function(resp){
                if (!resp || resp.success !== true) {
                    $btn.removeClass('loading').text('加载失败，点击重试');
                    return;
                }

                var $newPosts = $(resp.postsHtml || '');
                var $box = $btn.closest('.col-lg-8').find('.post_box').first();

                if ($newPosts.length > 0 && $box.length > 0) {
                    $box.append($newPosts);
                    $newPosts.hide().fadeIn(500);
                    mangoBindPostThumbnailFallback($newPosts);
                    mangoApplyLikeState($newPosts);
                }

                if (resp.nextHref) {
                    $btn.attr('href', resp.nextHref)
                        .removeClass('loading')
                        .text('加载更多');
                } else {
                    $btn.closest('.post-read-more').remove();
                }
            },
            error: function(xhr, status, error){
                console.error("AJAX Error:", status, error);
                $btn.removeClass('loading').text('加载失败，点击重试');
            }
        });
        
        return false;
    });
 
// 确保Bootstrap组件正确初始化
document.addEventListener('DOMContentLoaded', function() {
  // 手动处理菜单按钮点击事件
  var menuButton = document.querySelector('.mobile_an');
  var mobileMenu = document.getElementById('mobile_right_nav');
  
  if (menuButton && mobileMenu) {
    menuButton.addEventListener('click', function() {
      // 如果bootstrap对象存在，使用Bootstrap的API
      if (typeof bootstrap !== 'undefined') {
        var offcanvasInstance = bootstrap.Offcanvas.getInstance(mobileMenu);
        if (offcanvasInstance) {
          offcanvasInstance.show();
        } else {
          var bsOffcanvas = new bootstrap.Offcanvas(mobileMenu);
          bsOffcanvas.show();
        }
      } else {
        // 如果bootstrap对象不存在，使用简单的类切换
        mobileMenu.classList.add('show');
      }
    });
    
    // 处理关闭按钮点击事件
    var closeButton = mobileMenu.querySelector('.btn-close');
    if (closeButton) {
      closeButton.addEventListener('click', function() {
        if (typeof bootstrap !== 'undefined') {
          var offcanvasInstance = bootstrap.Offcanvas.getInstance(mobileMenu);
          if (offcanvasInstance) {
            offcanvasInstance.hide();
          }
        } else {
          mobileMenu.classList.remove('show');
        }
      });
    }
  }
  
  // 处理搜索按钮点击事件
  var searchButton = document.querySelector('.top_r_an[data-bs-target="#c_sousuo"]');
  var searchMenu = document.getElementById('c_sousuo');
  
  if (searchButton && searchMenu) {
    // 创建背景遮罩 (不改变DOM结构，只添加事件监听)
    searchButton.addEventListener('click', function() {
      if (typeof bootstrap !== 'undefined') {
        var offcanvasInstance = bootstrap.Offcanvas.getInstance(searchMenu);
        if (offcanvasInstance) {
          offcanvasInstance.show();
        } else {
          var bsOffcanvas = new bootstrap.Offcanvas(searchMenu);
          bsOffcanvas.show();
        }
      } else {
        // 手动显示搜索框
        searchMenu.classList.add('show');
      }
    });
    
    // 添加关闭搜索框的按钮事件处理
    var closeSearchButtons = searchMenu.querySelectorAll('[data-bs-dismiss="offcanvas"]');
    closeSearchButtons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (typeof bootstrap !== 'undefined') {
          var offcanvasInstance = bootstrap.Offcanvas.getInstance(searchMenu);
          if (offcanvasInstance) {
            offcanvasInstance.hide();
          }
        } else {
          searchMenu.classList.remove('show');
        }
      });
    });
    
    // 添加点击搜索框外部区域关闭搜索框的功能
    document.addEventListener('click', function(event) {
      // 如果搜索框已显示，且点击的不是搜索框内部元素，也不是搜索按钮
      if (searchMenu.classList.contains('show') && 
          !searchMenu.contains(event.target) && 
          event.target !== searchButton && 
          !searchButton.contains(event.target)) {
        if (typeof bootstrap !== 'undefined') {
          var offcanvasInstance = bootstrap.Offcanvas.getInstance(searchMenu);
          if (offcanvasInstance) {
            offcanvasInstance.hide();
          }
        } else {
          searchMenu.classList.remove('show');
        }
      }
    });
    
    // 监听ESC键关闭搜索框
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && searchMenu.classList.contains('show')) {
        if (typeof bootstrap !== 'undefined') {
          var offcanvasInstance = bootstrap.Offcanvas.getInstance(searchMenu);
          if (offcanvasInstance) {
            offcanvasInstance.hide();
          }
        } else {
          searchMenu.classList.remove('show');
        }
      }
    });
  }
  
  // 确保移动菜单的子菜单展开/收起功能正常工作
  $('.menu-zk .menu-item-has-children').prepend('<span class="czxjcdbs"></span>');
  $('.menu-zk li.menu-item-has-children .czxjcdbs').click(function(){
    $(this).toggleClass("kai");
    $(this).nextAll('.sub-menu').slideToggle("slow");
  });

  // 为代码块添加复制按钮和语言标签
  $('.wznrys pre').each(function() {
    var $pre = $(this);
    var $code = $pre.find('code');

    // 提取语言类型
    var codeClass = $code.attr('class') || '';
    var lang = '';

    // 从 class 中提取语言类型
    // 支持格式: lang-html, language-html, 或单独的语言名
    if (codeClass) {
      var classes = codeClass.split(/\s+/);
      for (var i = 0; i < classes.length; i++) {
        var cls = classes[i];
        // 匹配 lang-xxx 格式
        if (cls.indexOf('lang-') === 0) {
          lang = cls.substring(5);
          break;
        }
        // 匹配 language-xxx 格式
        else if (cls.indexOf('language-') === 0) {
          lang = cls.substring(9);
          break;
        }
      }

      // 如果没有找到 lang- 或 language- 前缀，尝试使用第一个非特殊类名
      if (!lang) {
        var nonLangClasses = ['hljs', 'line-numbers', 'match-braces', 'code', 'pre'];
        for (var i = 0; i < classes.length; i++) {
          if (classes[i] && nonLangClasses.indexOf(classes[i]) === -1) {
            lang = classes[i];
            break;
          }
        }
      }
    }

    // 添加语言标签（使用真实 DOM 元素而不是伪元素）
    if (lang && $pre.find('.code-lang-label').length === 0) {
      var $langLabel = $('<span class="code-lang-label">' + lang.toUpperCase() + '</span>');
      $pre.prepend($langLabel);
    }

    // 检查是否已经有复制按钮，避免重复添加
    if ($pre.find('.copy-code-btn').length === 0) {
      // 创建复制按钮
      var $copyBtn = $('<button class="copy-code-btn">Copy</button>');

      // 将按钮插入到 pre 标签中
      $pre.prepend($copyBtn);

      // 绑定点击事件
      $copyBtn.on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var codeText = $code.text();

        // 使用现代 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(codeText).then(function() {
            // 复制成功
            $btn.addClass('copied').text('Copied!');

            // 2秒后恢复按钮状态
            setTimeout(function() {
              $btn.removeClass('copied').text('Copy');
            }, 2000);
          }).catch(function(err) {
            console.error('复制失败:', err);
            $btn.text('Failed');
            setTimeout(function() {
              $btn.text('Copy');
            }, 2000);
          });
        } else {
          // 降级方案：使用传统的 document.execCommand
          var textArea = document.createElement('textarea');
          textArea.value = codeText;
          textArea.style.position = 'fixed';
          textArea.style.left = '-999999px';
          textArea.style.top = '-999999px';
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();

          try {
            var successful = document.execCommand('copy');
            if (successful) {
              $btn.addClass('copied').text('Copied!');
              setTimeout(function() {
                $btn.removeClass('copied').text('Copy');
              }, 2000);
            } else {
              $btn.text('Failed');
              setTimeout(function() {
                $btn.text('Copy');
              }, 2000);
            }
          } catch (err) {
            console.error('复制失败:', err);
            $btn.text('Failed');
            setTimeout(function() {
              $btn.text('Copy');
            }, 2000);
          }

          document.body.removeChild(textArea);
        }
      });
    }
  });
});
