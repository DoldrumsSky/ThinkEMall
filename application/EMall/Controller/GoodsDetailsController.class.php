<?php

namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\HomebaseController;

/**
 * ThinkEMall电子商城前端商品展示控制器
 * ============================================================================
 * 引用、修改及衍生本系统代码请保留以下信息
 * 版权所有 2016-2020 作者：阳华 ThinkEMall，并保留所有权利。 * 
 * ----------------------------------------------------------------------------
 * 项目地址：https://github.com/DoldrumsSky/ThinkEMall * 
 * 联系方式：
 * QQ:451343282  Email:451343282@qq.com
 * 技术交流群:1950562
 * ============================================================================
 * $Author: YangHua $
 * $Id: GoodsDetailsController.php 17217 2017-04-09 06:29:08Z YangHua $
*/

class GoodsDetailsController extends HomebaseController {
    
    //商品详情页
    public function index() {
    	$goods_id=I('get.id',0,'intval');
    	$term_id=I('get.cid',0,'intval');
    	
    	$goodsModel=M("goods");
        $prefix=C('DB_PREFIX');

        $where[$prefix.'goods.goods_id']=$goods_id;
        $where[$prefix.'goods.goods_status']=1;
    	
        $goodsData=$goodsModel
        ->join('__LOGISTICS__ ON __GOODS__.logistics_id = __LOGISTICS__.logistics_id')        
        ->where($where)->find();

        if($goodsData==false){
            $this->error('抱歉，您查询的商品已经不存在！');
        }

        //积分数据处理，当选择按配置进行计算时，不需要设置手动积分字段值
        if($goodsData['goods_points']==0){
            $price=$goodsData['goods_price'];
            $powNum=strlen(floor($price));
            $goodsData['goods_points']=floor($price/pow(10,$powNum)*pow(10,$powNum-1));
        }

        $termFilterData= ApiService::getTermFilter($goodsData['term_id']);
 
        //session数据手动提取测试
        /*$session=M('think_session')->where(array('session_id'=>'marf9epq5ge7tisqjcrha8du47'))->find();
        session_decode($session['session_data']);
        $data=$_SESSION;
        dump($data);
                   $matchNum= preg_match('/user\|.*}.*?\|/',$session['session_data'],$sessionUser);
        dump($session['session_data']);
        if($matchNum==0){
            $matchNum= preg_match('/user\|.*}/',$session['session_data'],$sessionUser);
            $sessionUser=$matchNum>0?explode('|',$session['session_data'])[1]:'';

        }else{

            $sessionUser=explode('|',$sessionUser[0])[1]; 
            //preg_match('/user\|.*}/',$sessionSplit,$sessionUser);
            $endPos=strrpos($sessionUser,'}')+1;
            dump(unserialize(mb_substr($sessionUser,0,$endPos)));
        }*/

        //dump($result);
        $this->assign('goods',$goodsData);
        $this->assign('termFilterData', $termFilterData);
        $this->display(':GoodsDetails');
    }

    //获取商品SKU单品数据
    public function getSKUData(){
        $goods_id=I('get.goods_id',0,'intval');
        $result=M('goods')->where(array('goods_id'=>$goods_id))->field('goods_sku')->find();
        if($result){
            $this->ajaxReturn(array('status'=>1,'data'=>$result));
        }else{
            $this->ajaxReturn(array('status'=>0,'error'=>'服务器无法正常获取商品数据！'));
        }
    }

    
    // 获取评价数据
    public function getAppraiseData(){
        if(IS_POST){
            $goods_id=I('post.gid');
            $filterType=I('post.filterType',0,'intval');
            
            $appraiseModel=M('appraise');
            $where['goods_id']=$goods_id;
            $where['appraise_status']=1;
            //全部
            if($filterType==1){
                $field='appraise_id,
                        sku_idx,
                        shopper_name,
                        appraise_content,
                        appraise_additional,
                        appraise_time,
                        appraise_addt_time,
                        appraise_score,
                        appraise_pic,
                        appraise_reply,
                        admin_name';

                $errorTxt='评价';
            //追评（显示数据不包括原评价内容）
            }else if($filterType==2){
                $where['appraise_additional']=array('NEQ','');
                $field='appraise_id,
                        sku_idx,
                        shopper_name,
                        appraise_additional,
                        appraise_addt_time,
                        appraise_score,
                        appraise_reply,
                        admin_name';

                $errorTxt='追评';
            //晒图评论(显示数据不包括追评)
            }else if($filterType==3){
                $where['appraise_pic']=array('NEQ','');
                $field='appraise_id,
                        sku_idx,
                        shopper_name,
                        appraise_content,
                        appraise_time,
                        appraise_score,
                        appraise_pic,
                        appraise_reply,
                        admin_name';

                $errorTxt='包含图片的评价';
            }
            //$where['appraise_content']=array('NEQ','');
            $appraiseModel->where($where);

            $count=$appraiseModel->count();            
            $page = $this->page($count, 20);
                
            $appraiseData['content']=$appraiseModel
            ->where($where)
            ->field($field)
            ->limit($page->firstRow , $page->listRows)
            ->order("appraise_time DESC")
            ->select();

            if(!empty($appraiseData['content'])){
                $appraiseData['page']=$page->show('default');
                //判断是否是登录的管理员，是则显示回复
                if(session('user.user_type')==1){
                    $appraiseData['admin']=1;
                }
                $this->ajaxReturn(array('status'=>1,'data'=>$appraiseData));
            }else{
                $this->ajaxReturn(array('status'=>0,'error'=>'此商品还没有任何'.$errorTxt.'内容！'));
            }
        }
    }

    public function postAppraiseReply(){
        if(IS_POST){
            $userInfo=session('user');
            //验证用户
            if($userInfo['user_type']==1){
                $appraise_id=I('post.appraise_id');
                $replyContent=I('post.replyContent');
                $postType=I('post.postType',0,intval);

                if(!empty($appraise_id) && !empty($replyContent)){
                    //非编辑时保存回复时间
                    if($postType==1){
                        $updateData['reply_time']=array('exp','CURRENT_TIMESTAMP');
                        $updateData['admin_name']=$userInfo['user_nicename'];
                        $updateData['admin_id']=$userInfo['id'];
                    }
                    $updateData['appraise_reply']=$replyContent;

                    $appraiseModel=M('appraise');
                    $result=$appraiseModel->where(array('appraise_id'=>$appraise_id))->save($updateData);
                    if($result===false){
                        $this->ajaxReturn(array('status'=>0,'error'=>'无法保存提交的评价回复，请稍候再试！'));
                    }

                    $this->ajaxReturn(array('status'=>1,'data'=>session('user.user_nicename')));
                }
            }
        }
    }

    //取与当前浏览商品类似的商品
    public function getSimilarGoods(){
        $fidGroup=I('post.filter_id');
        $goods_price=I('post.goods_price',0,'intval');
        $goods_id=I('post.goods_id',0,'intval');
        $term_id=I('post.term_id',0,'intval');

        if(empty($fidGroup) && empty($goods_price)){
            return false;
        }

        $highPrice=round($goods_price+$goods_price/5);
        $lowPrice=round($goods_price-$goods_price/5);

        $where['match(filter_id)']=array('exp','against(\''.$fidGroup.'\')');
        $where['goods_price']=array('between',$lowPrice.','.$highPrice);
        $where['goods_id']=array('neq',$goods_id);
        $where['term_id']=$term_id;

        $goodsModel=M('goods');
        $goodsData=$goodsModel->where($where)->field('goods_id,goods_title,goods_price,goods_img')->order('permonth_sales desc')->limit(0,8)->select();
        if($goodsData!==false){
            $this->ajaxReturn(array('status'=>1,'data'=>$goodsData));
        }else{
            $this->ajaxReturn(array('status'=>0,'error'=>'无法获取商品数据，请稍候尝试！'.$goodsModel->getError()));
        }
    }
    
    // 获取指定导航下的所有显示在顶层的推荐菜单数据（返回一个二级的菜单树数组）,CID为导航的分类id
    public function getAllTopShowNav($cid){
        $cid=intval($cid);
        //获取全部分类
        $navData=ApiService::getAllTopShowNav($cid);
        if(empty($navData)){
            $this->ajaxReturn(array('status'=>0,'error'=>'未获取到任何数据！'));
        }
        $this->ajaxReturn(array('status'=>1,'data'=>$navData));
    }
}
