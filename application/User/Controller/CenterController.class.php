<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
use EMall\Service\ApiService;
use EMall\Service\ChromePhp;

class CenterController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
		$messageData['messageNum']=$this->messageCount;
    	$this->assign('messageData',$messageData);
	}
	
    // 会员中心首页
	public function index() {
		$userData=$this->user;
		$where['shopper_id']=$userData['id'];
		$where['status']=array('gt',0);
        $orderModel=M('order_relationships');

        //查询所有订单商品各个状态的数量
        $orderGoodsStatus=ApiService::getOrderGoodsStatusCount($orderModel,$where['shopper_id']);
        $orderData['orderGoodsStatus']=$orderGoodsStatus;
        $orderData['nopay_order_num']=$userData['nopay_order_num'];
        unset($orderGoodsStatus);

        //15天内订单详细数据
        $where_15['DATE_SUB(CURDATE(), INTERVAL 30 DAY)']=array('exp','<=date(B.pay_time)');
        $where_15['A.status']=array('gt',0);

        $count=$orderModel
        ->alias('A')
        ->join('__ORDER__ B ON A.order_gid = B.order_gid')
        ->where($where_15)
        ->count();

        $page=$this->page($count,10);

        $lastShoppingData=$orderModel
        ->alias('A')
        ->field('A.goods_price,A.goods_data,A.goods_id,A.sku_idx,B.consignee,A.status,B.pay_time')
        ->join('__ORDER__ B ON A.order_gid = B.order_gid')
        ->where($where_15)
        ->limit($page->firstRow,$page->listRows)
        ->select();

        //dump($lastShoppingData);

        $this->assign('orderData',$orderData);
        $this->assign('shoppingData',$lastShoppingData);
        $this->assign('page',$page->show('default'));
        $this->assign($userData);
    	$this->display(':center');
    }

    public function doDeposit(){
    	$userData=$this->user;
    	//取充值记录
    	$orderModel=M('service_order');
    	$where['shopper_id']=$userData['id'];
    	$where['order_type']=1;
    	$count=$orderModel->where($where)->count();
    	$page=$this->page($count,10);

    	$depositData=$orderModel->where($where)->limit($page->firstRow,$page->listRows)->order('order_time desc')->select();
    	$this->assign('depositData',$depositData);
    	$this->assign($userData);
    	$this->assign('page',$page->show('default'));
    	$this->display(':doDeposit');
    }

    //我的积分
    public function myPoints(){
    	$user=$this->user;
    	$where['A.shopper_id']=$user['id'];
    	//积分是确认收货后才会计算的
    	$where['A.status']=array('gt',3);
    	$orderModel=M('order_relationships');

    	$count=$orderModel
    	->alias('A')
    	->join('__ORDER__ B ON A.order_gid = B.order_gid')
    	->where($where)
    	->group('A.order_gid')
    	->count();
    	$page=$this->page($count,10);

    	$pointsData=$orderModel
    	->alias('A')
    	->join('__ORDER__ B ON A.order_gid = B.order_gid')
    	->where($where)
    	->field('sum(A.points) as points_plus,B.order_serial,B.goods_data,B.points_deduction,B.pay_time')
    	->order('B.pay_time desc')
    	->group('A.order_gid')
    	->limit($page->firstRow,$page->listRows)
    	->select();

    	if($pointsData===false){
    		$this->error('暂时无法获取积分数据，请稍候尝试！');
    	}

    	$this->assign('users',$user);
    	$this->assign('pointsData',$pointsData);
    	$this->assign('page',$page->show('default'));
    	$this->display(':myPoints');
    }

    //查看消息
    public function viewMessage(){
    	$message_type=I('get.message_type',0,'intval');
    	$messageData=$this->getMessageData($message_type);
    	if($messageData!==false){
            $messageData['messageNum']=$messageData['messageNum'];
    		$this->assign('messageData',$messageData);
    	}

    	$this->assign($this->user['message_handle']);
    	$this->display(':viewMessage');
    }

    
    //收藏商品详情页
    public function myFavorGoods(){
    	$favorGoods=$this->getFavorGoods(0,'favorView');
    	$this->assign('favorGoods',$favorGoods);
    	$this->display(':myFavorGoods');
    }

    //收藏商品（ajax）
    //以其的商品与购物车商品一样，在用户退出登录或者会话结束时才会写入到用户数据库中
    public function setToFavorGoods(){
    	if(IS_POST){
	    	$id=I('post.id',0,'intval');

	    	if(!empty($id)){
	    		$userSession=session('user');
	    		//取商城配置，对比是否超出配置的收藏最大数
	    		$configData=ApiService::getEMallConfig();
	    		if($configData['FAVORGOODS_MAXNUM']<=$userSession['favorGoodsNum']){
	    			$this->ajaxReturn(array('status'=>0,'error'=>'超出最大收藏商品数'.$configData['FAVORGOODS_MAXNUM'].'，请先清理一些不需要或者过期的收藏商品！'));
	    		}
	    		$userSession['favorGoodsNum']++;
	    		//记录收藏时间与商品ID
	    		$userSession['newFavorGoods'][$id]=date('Y-m-d');
	    		unset($userSession['cancelFavorGoods'][$id]);
	    		session('user',$userSession);
	    		$favorData=$userSession['newFavorGoods'];
	    		unset($userSession);

	    		$favorGoods=$this->getFavorGoods($id);
	    		$this->ajaxReturn(array('status'=>1,'data'=>$favorGoods['content'],'favorData'=>$favorData));
	    	}
    	}
    }

    //获取收藏商品,当内部要获取单个收藏商品数据时将商品id作为$singleId传入即可,获取数据后将不会直接返回
    //$viewType为barView时数据显示在侧栏，favorView为收藏详情页
    public function getFavorGoods($singleId=0,$viewType='barView'){
    	$userSession=session('user');
    	
    	if(!empty($userSession['newFavorGoods']) && !empty($userSession['favor_goods'])){
    		$allFavorData=$userSession['newFavorGoods']+$userSession['favor_goods'];
    		//dump($allFavorData);
    	}else if(!empty($userSession['favor_goods']) && empty($userSession['newFavorGoods'])){
            $allFavorData=$userSession['favor_goods'];
        }else if(empty($userSession['favor_goods']) && !empty($userSession['newFavorGoods'])){
            $allFavorData=$userSession['newFavorGoods'];
        }
    	unset($userSession);

    	if(empty($singleId)){
    		if($viewType=='barView'){
    			$allFavorData=array_slice($allFavorData,0,10,true);
		    	//生成商品ID字串，限制侧栏输出数为前10件商品
		    	foreach ($allFavorData as $key => $value) {
		    		$gid.=empty($gid)?$key:','.$key;
		    	}
		    	if(!empty($gid)){
		    		$where['goods_id']=array('in',$gid);
		    	}
		    //收藏详情页显示所有收藏商品
	    	}else if ($viewType=='favorView'){
	    		$curPage=I('get.p',0,'intval');
	    		if(empty($curPage) || $curPage==1){
	    			$start=0;
	    		}else{
	    			$start=($curPage-1)*10;
	    		}

    			$favorGoods['favorGoodsNum']=count($allFavorData);
	    		$allFavorData=array_slice($allFavorData,$start,10,true);
	    		//
		    	foreach ($allFavorData as $key => $value) {
		    		$gid.=empty($gid)?$key:','.$key;
		    	}
		    	if(!empty($gid)){
		    		$where['goods_id']=array('in',$gid);
		    	}else{
		    		$favorGoods['favorGoodsNum']=0;
		    	}
	    	}
    	}else{
    		$singleId=intval($singleId);
    		$where['goods_id']=$singleId;
    	}

    	if(!empty($where)){
    		$goodsModel=M('goods');
    		if ($viewType=='favorView'){
    			$page=$this->page($favorGoods['favorGoodsNum'],10);
    			$favorGoods['page']=$page->show('default');
    		}

    		$favorGoods['content']=$goodsModel
    		->where($where)
    		->field('goods_id,term_id,goods_title,goods_img,goods_price,filter_id')
    		->select();
    	}
    	if(empty($singleId) && $viewType=='barView'){
    		$this->ajaxReturn(array('status'=>1,'data'=>$favorGoods['content'],'favorData'=>$allFavorData));
    	}else{
    		$favorGoods['favorData']=$allFavorData;
    		unset($allFavorData);
    		//生成排序索引（将对应商品结果记录中的数组索引号赋值到已经完成排序的favorData键对应的值）
    		foreach ($favorGoods['content'] as $key => $value) {
    			$favorGoods['favorData'][$value['goods_id']]=$key;
    		}
    		return $favorGoods;
    	}
    }

    //取消收藏商品
    public function cancelFavorGoods(){
    	if(IS_POST){
	    	$id=I('post.id',0,'intval');
	    	$ids=I('post.ids');
	    	if(!empty($id)){
	    		$userSession=session('user');
                //$this->ajaxReturn(array('status'=>0,'data'=>$userSession));
	    		//移除收藏商品的id（检查移除的商品id是否存在）
	    		if(!empty($userSession['favor_goods'][$id]) || !empty($userSession['newFavorGoods'][$id])){
                    if($userSession['favor_goods'][$id]){
                        unset($userSession['favor_goods'][$id]);
                    }else{
		    		    unset($userSession['newFavorGoods'][$id]);
                    }
		    		//记录取消收藏的商品id
		    		$userSession['cancelFavorGoods'][$id]=$id;
                    $userSession['favorGoodsNum']=$userSession['favorGoodsNum']-1;
		    		session('user',$userSession);
		    		$this->ajaxReturn(array('status'=>1,'data'=>$userSession['favor_goods']));
	    		}
	    	}
	    	//批量删除
	    	if(!empty($ids)){
	    		$ids=explode(',',$ids);
	    		$userSession=session('user');
                $i=0;
	    		foreach ($ids as $key => $gid) {
                    $i++;
					//移除收藏商品的id（检查移除的商品id是否存在）
					if(!empty($userSession['favor_goods'][$gid]) || !empty($userSession['newFavorGoods'][$gid])){
                        if($userSession['favor_goods'][$gid]){
                            unset($userSession['favor_goods'][$gid]);
                        }else{
                            unset($userSession['newFavorGoods'][$gid]);
                        }
			    		//记录取消收藏的商品id
			    		$userSession['cancelFavorGoods'][$gid]=$gid;
		    		}
	    		}
                $userSession['favorGoodsNum']=$userSession['favorGoodsNum']-$i;
	    		session('user',$userSession);
	    		$this->ajaxReturn(array('status'=>1,'data'=>$userSession['favor_goods']));
	    	}
    	}
    }

}
