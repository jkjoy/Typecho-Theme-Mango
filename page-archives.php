<?php 
/**
 * 文章归档
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
        <?php
        // 使用数据库直接查询文章，避免与侧边栏冲突
        $db = \Typecho\Db::get();
        $posts = $db->fetchAll($db->select('cid', 'title', 'slug', 'created', 'commentsNum')
            ->from('table.contents')
            ->where('type = ?', 'post')
            ->where('status = ?', 'publish')
            ->order('created', \Typecho\Db::SORT_DESC));
        
        $output = ''; // 初始化输出变量
        $year = ''; // 当前年份
        $month = ''; // 当前月份
        
        foreach ($posts as $post) {
            // 处理文章数据
            $permalink = \Typecho\Router::url('post', $post, $this->options->index);
            $title = htmlspecialchars($post['title']);
            $created = $post['created'];
            $commentsNum = $post['commentsNum'];
            
            // 获取年月日
            $postYear = date('Y', $created);
            $postMonth = date('m', $created);
            $postDay = date('d', $created);
            
            // 如果年份变化，添加年份标题
            if ($year != $postYear) {
                // 如果不是第一个年份，关闭上一个月份的列表
                if ($year != '') {
                    $output .= '</ul></div>';
                }
                
                $year = $postYear;
                $month = '';
                $output .= '<div class="archive-year"><h3>' . $year . '年</h3>';
            }
            
            // 如果月份变化，添加月份标题
            if ($month != $postMonth) {
                // 如果不是第一个月份，关闭上一个月份的列表
                if ($month != '') {
                    $output .= '</ul>';
                }
                
                $month = $postMonth;
                $output .= '<h4 class="archive-month">' . $month . '月</h4><ul class="blog-posts">';
            }
            
            // 输出文章项
            $output .= '<li><span>';
            $output .= '<i><time datetime="' . date('Y-m-d', $created) .'">' . $postDay . '日</time></i>';
            $output .= '</span>';
            $output .= '<a href="' . $permalink . '">' . $title . '</a>';
            
            // 添加评论数
            if ($commentsNum > 0) {
                $output .= '<span class="comments-count">(' . $commentsNum . '条评论)</span>';
            }
            
            $output .= '</li>';
        }
        
        // 关闭最后一个列表
        if ($year != '') {
            $output .= '</ul></div>';
        }
        
        echo $output;
        ?>
	</div>
</div>
<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>

<style>
.archive-year {
    margin-bottom: 20px;
}

.archive-year h3 {
    font-size: 1.5rem;
    color: #333;
    margin-bottom: 10px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.dark .archive-year h3 {
    color: #fff;
    border-bottom-color: #444;
}

.archive-month {
    font-size: 1.2rem;
    margin-top: 15px;
    margin-bottom: 10px;
    color: #555;
}

.dark .archive-month {
    color: #ccc;
}

.blog-posts {
    list-style: none;
    padding-left: 20px;
    margin-bottom: 15px;
}

.blog-posts li {
    position: relative;
    padding: 5px 0;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.blog-posts span {
    display: inline-block;
    vertical-align: middle;
    margin-right: 10px;
}

.blog-posts span i {
    font-style: normal;
    color: #999;
}

.blog-posts a {
    color: #333;
    text-decoration: none;
    white-space: nowrap;
}

.dark .blog-posts a {
    color: #fff;
}

.blog-posts a:hover {
    color: #007bff;
}

.comments-count {
    margin-left: 8px;
    font-size: 0.85em;
    color: #888;
}

.dark .comments-count {
    color: #aaa;
}
</style>
