<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
/**    
 * 评论者认证等级 + 身份    
 *    
 * @author Chrison    
 * @access public    
 * @param str $email 评论者邮址    
 * @return result     
 */     
function commentApprove($widget, $email = NULL)      
{   
    $result = array(
        "state" => -1,//状态
        "isAuthor" => 0,//是否是博主
        "userLevel" => '',//用户身份或等级名称
        "userDesc" => '',//用户title描述
        "bgColor" => '',//用户身份或等级背景色
        "commentNum" => 0//评论数量
    );
    if (empty($email)) return $result;       
    $result['state'] = 1;
    $emailLower = strtolower(trim((string)$email));
    $friendRaw = (string)(Helper::options()->friend ?? '');
    $friendList = preg_split('/[,\s]+/u', strtolower(trim($friendRaw)), -1, PREG_SPLIT_NO_EMPTY);
    $isFriend = ($emailLower !== '' && !empty($friendList) && in_array($emailLower, $friendList, true)); 
    if ($widget->authorId == $widget->ownerId) {      
        $result['isAuthor'] = 1;//」
        $result['userLevel'] = '「博主」<i class="bi bi-award-fill"></i>';
        $result['userDesc'] = '本站站长';
        $result['bgColor'] = '#ef6762ff';
        $result['commentNum'] = 999;
    } else {
        try {
            //数据库获取
            $db = Typecho_Db::get();
            //获取评论条数
            $commentNumSql = $db->fetchAll($db->select(array('COUNT(cid)'=>'commentNum'))
                ->from('table.comments')
                ->where('mail = ?', $email));
            $commentNum = $commentNumSql[0]['commentNum'];    
            //获取友情链接
            $linkSql = $db->fetchAll($db->select()->from('table.links')
                ->where('user = ?',$email));       
            //等级判定
            if($commentNum==1){
                $result['userLevel'] = '「初见」<i class="bi bi-0-circle"></i>';
                $result['bgColor'] = '#999999';
                $userDesc = '人生一大步！';
            } else {
                if ($commentNum<10 && $commentNum>1) {
                    $result['userLevel'] = '「初识」<i class="bi bi-1-circle"></i>';
                    $result['bgColor'] = '#999999';
                }elseif ($commentNum<20 && $commentNum>=10) {
                    $result['userLevel'] = '「相识」<i class="bi bi-2-circle"></i>';
                    $result['bgColor'] = '#8dc7beff';
                }elseif ($commentNum<40 && $commentNum>=20) {
                    $result['userLevel'] = '「熟识」<i class="bi bi-3-circle"></i>';
                    $result['bgColor'] = '#3ceacdff';
                }elseif ($commentNum<80 && $commentNum>=40) {
                    $result['userLevel'] = '「好友」<i class="bi bi-4-circle"></i>';
                    $result['bgColor'] = '#27ee15ff';
                }elseif ($commentNum<160 && $commentNum>=80) {
                    $result['userLevel'] = '「知己」<i class="bi bi-5-circle"></i>';
                    $result['bgColor'] = '#e7e42dff';
                }elseif ($commentNum>=160) {
                    $result['userLevel'] = '「挚友」<i class="bi bi-6-circle"></i>';
                    $result['bgColor'] = '#fdf000ff';
                }
                 $userDesc = '您在本站有'.$commentNum.'条留言！'; 
            }
            if($linkSql){
                $result['userLevel'] = '「博友」';
                $result['bgColor'] = '#00fd15ff';
                $userDesc = '🔗'.$linkSql[0]['description'].'&#10;✌️'.$userDesc;
            }
            
            if ($isFriend) {
                $result['userLevel'] = '「好友」<i class="bi bi-heart-fill"></i>';
                $result['bgColor'] = '#880097ff';
                $userDesc = '好基友认证&#10;' . $userDesc;
            }
            $result['userDesc'] = $userDesc;
            $result['commentNum'] = $commentNum;
        } catch (Exception $e) {
            error_log('Error in commentApprove function: ' . $e->getMessage());
            // 设置默认值
            $result['userLevel'] = '「访客」';
            $result['bgColor'] = '#999999';
            $result['userDesc'] = '欢迎留言';
            $result['commentNum'] = 0;
        }
    } 
    return $result;
}
