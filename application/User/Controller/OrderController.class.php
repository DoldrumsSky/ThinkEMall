<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
use EMall\Service\ApiService;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城前端订单管理控制器
 * ============================================================================
 * 引用、修改及衍生本系统代码请保留以下信息
 * 版权所有 2016-2020 作者：阳华 ThinkEMall，并保留所有权利。 * 
 * ----------------------------------------------------------------------------
 * 项目地址：https://github.com/DoldrumsSky/ThinkEMall
 *           https://git.oschina.net/langweaver/thinkemall
 * 联系方式：
 * QQ:451343282  Email:451343282@qq.com
 * 技术交流群:1950562
 * ============================================================================
 * $Author: YangHua $
 * $Id: AdminOrderController.php 17217 2017-2-10 06:29:08Z YangHua $
*/

class OrderController extends MemberbaseController {
    //protected $checkReturnType='ajax';
    function _initialize() {
        parent::_initialize();
        $messageData['messageNum']=$this->messageCount;
        $this->assign('messageData',$messageData);
    }
    
    // 前台用户订单首页 
	public function index() {
        $diffNum=I('get.diff',0,'intval');
        $status=I('get.status',-1,'intval');
        $returnType=$_GET['returnType'];

        //分析搜索订单的关键词,搜索时不带时间比较
        $keywords=trim(I('post.keywords'));
        //订单号查询
        if(!empty($keywords) && preg_match("/^\d{16}$/",$keywords)){
            $where['order_serial']=$keywords;
        //商品关键词查询
        }else if(!empty($keywords)){
            $where['Match(goods_keywords)']=array('exp','Against (\''.$keywords.'\')');
        //非搜索订单时加入时间比较
        }else{

            $y=date("Y",time()); 
            $m=date("m",time()); 
            $d=date("d",time());

            //取筛选的订单起始时间
            switch ($diffNum) {
                case 0:          //三个月内的订单
                    $m-=3;
                    break;
                case 1:          //一年前年内的订单
                    $y-=1;
                    break;
                case 2:          //两年前年内的订单
                    $y-=2;
                    break;
                case 3:         //三年前年内的订单
                    $y-=3;
                    break;
                case -3:        //三年前所有的订单
                    $y-=3;
                    break; 
                default:        //其它数值都为今年年内订单
                    $m=1;
                    $d=1;
                    break;
            }

            $start_time = mktime(0, 0, 0, $m, $d ,$y);
            $end_time = $diffNum>0?mktime(0, 0, 0, 12, 31 ,$y):null;
            $compareExp=$diffNum>0||empty($diffNum)?'gt':'lt';
            $where['unix_timestamp(order_time)']=!$end_time?array($compareExp,$start_time):array(array('gt',$start_time),array('lt',$end_time),'and');
        }

        $where['shopper_id']=$this->userid;
        $orderModel=M('order_relationships');

        //查询所有订单商品各个状态的数量
        $orderGoodsStatus=ApiService::getOrderGoodsStatusCount($orderModel,$where['shopper_id']);
        //dump($orderGoodsStatus);
        if(!empty($status) && $status>0){
            $where['status']=$status;
        }else{
            if($status==0){
                $where['status']=0;
            }else{
                $where['status']=array('egt',0);
            }
        }

        $pagesize=10;
        $field = '*';
        $limit = '0,10';
        $order = 'order_gid DESC';

        if (!empty($pagesize)) {
                changeModelProperty($orderModel,'order');
                $pagetpl = empty($pagetpl) ? '{first}{prev}{liststart}{list}{listend}{next}{last}' : $pagetpl;
                $totalsize=M('order')
                    ->field($field)
                    ->where($where)
                    ->order($order)
                    ->limit($limit)
                    ->count();  

                $pagesize = intval($pagesize);
                $page_param = C("VAR_PAGE");
                $page = new \Page($totalsize,$pagesize);
                $page->setLinkWraper("li");
                $page->__set("PageParam", $page_param);
                if(sp_is_mobile()){
                    $pagesetting= array("listlong" => "2", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
                }else{
                    $pagesetting=array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
                }
                $page->SetPager('default', $pagetpl,$pagesetting);

                $orderData=$orderModel
                    ->field($field)
                    ->where($where)
                    ->order($order)
                    ->limit($page->firstRow, $page->listRows)
                    ->select();            
              
                $content['page']=$page->show('default');
                $content['total_pages']=$page->getTotalPages(); // 总页数
                $content['count']=$totalsize;
            }else{
                $orderData=M('order')
                    ->field($field)
                    ->where($where)
                    ->order($order)
                    ->limit($limit)
                    ->select();
            }


        $content['order']=$orderData;


        if($orderData!==false){
            $content['orderGoodsStatus']=$orderGoodsStatus;
            //取订单数信息
            //$userSession=$this->user;
            $countWhere['shopper_id']=$where['shopper_id'];
            $countWhere['status']=array('EGT',0);
            $orderCount=$orderModel->where($countWhere)->field('sum(status=0) nopay,count(*) total')->select();
            if($orderCount!==false){
                $content['nopay_order_num']=$orderCount[0]['nopay'];
                $content['all_order_num']=$orderCount[0]['total'];
            }
            unset($orderCount);
        }else{
            if($returnType=='ajax'){
                if($orderData===false){
                    $this->orderReturn(0,null,'无法获取用户订单数据，请稍候尝试！');
                }else{
                    $this->orderReturn(0,null,'没有查询到任何相关的订单数据！');
                }

            }else{
                if($orderData===false){
                    $this->error('无法获取用户订单数据，请稍候尝试！');
                }else{
                    $this->display(":order");
                    return true;             
                }
            }
        }

        if($returnType=='ajax'){
            //$content['sql']=$orderModel->getLastSql();
                $this->orderReturn(1,$content);
        }else{
            $this->assign('orderData',$content);
		    $this->display(":order");
        }

    }

    //根据订单商品状态查询付款商品信息
    public function queryOrderGoodsByStatus(){
        $status=I('request.status',0,'intval');
        $returnType=$_GET['returnType'];
        if(empty($status)){
            return false;
        }
        $orderModel=M('order_relationships');
        if($status<>6){
            $where['a.status']=$status;
        }else{
            $where['a.refund_status']=array('NEQ',0);
        }
        $where['a.shopper_id']=$this->userid;

        $count=$orderModel->alias('a')
        ->join('__ORDER__ b ON a.order_gid=b.order_gid')
        ->where($where)
        ->count();

        $page=$this->page($count,10);

        $content['order']=$orderModel
        ->alias('a')
        ->field('a.*,b.consignee,b.payment')
        ->join('__ORDER__ b ON a.order_gid=b.order_gid')
        ->where($where)
        ->limit($page->firstRow,$page->listRows)
        ->order('order_id desc')
        ->select();


        if($content['order']!==false){
            //查询所有订单商品各个状态的数量
            $orderGoodsStatus=ApiService::getOrderGoodsStatusCount($orderModel,$where['a.shopper_id']);

            $content['page']=$page->show('default');
            $content['total_pages']=$page->getTotalPages(); // 总页数
            $content['count']=$totalsize;
            $content['orderGoodsStatus']=$orderGoodsStatus;
            //取订单数信息
            $countWhere['shopper_id']=$where['a.shopper_id'];
            $countWhere['status']=array('EGT',0);
            //切换查询order
            changeModelProperty($orderModel,'order');
            $orderCount=$orderModel->where($countWhere)->field('sum(status=0) nopay,count(*) total')->select();
            if($orderCount!==false){
                $content['nopay_order_num']=$orderCount[0]['nopay'];
                $content['all_order_num']=$orderCount[0]['total'];
            }
            unset($orderCount);
            /*
            $userSession=$this->user;
            $content['all_order_num']=$userSession['all_order_num'];
            $content['nopay_order_num']=$userSession['nopay_order_num'];
            unset($userSession);*/
            
            if($returnType=='ajax'){
                $this->orderReturn(1,$content);
            }else{
                $this->assign('orderData',$content);
                $this->assign('queryType','orderGoods');
                $this->display(':order');
            }
        }
        $this->orderReturn(0,null,'无法获取用户订单数据，请稍候尝试！');
    }

    //查看订单详情
    public function orderView(){
        $order_gid=I('get.id',0,'intval');

        $orderModel=M('order');
        $where['order_gid']=$order_gid;
        $where['status']=array('gt',0);
        $where['shopper_id']=$this->userid;
        $orderData=$orderModel->where($where)->find();
        //dump($orderData);
        if(!$orderData){
            $this->error('查询不到任何关于此订单的数据！');
        }
        $this->assign('orderData',$orderData);
        $this->display(':orderView');
    }

    //确认收货
    public function setReceivedStatu(){
        if(IS_POST){
            $order_gid=I('post.order_id');
            $waybill_no=I('post.waybill_no');
            $errorState=0;

            if(!empty($order_gid) && !empty($waybill_no)){
                $orderModel=M('order');
                $where['order_gid']=$order_gid;
                $where['shopper_id']=$this->userid;

                $orderModel->startTrans();
                $orderData=$orderModel->where($where)->field('goods_data')->find();
                if($orderData!==false){
                    $orderData['goods_data']=json_decode($orderData['goods_data'],true);
                    //订单映射数据更新
                    foreach ($orderData['goods_data'] as $key => $value) {
                        if($value['waybill_no']==$waybill_no){
                            $orderData['goods_data'][$key]['status']=4;
                        }
                    }
                    $orderData['goods_data']=json_encode($orderData['goods_data']);
                    $update=$orderModel->where($where)->save($orderData);
                    if($update!==false){
                        //更新商品数据
                        $this->changeModelProperty($orderModel,'order_relationships');
                        $where['waybill_no']=$waybill_no;
                        $updateData['status']=4;
                        $updateData['finish_time']=array('exp','CURRENT_TIMESTAMP');
                        $update=$orderModel->where($where)->save($updateData);
                        if($update!==false){
                            $orderModel->commit();
                            $this->orderReturn(1,date('Y-m-d H:i:s'));
                        }
                        $orderData->rollback();
                        $this->orderReturn(0,null,'无法更新商品数据，请稍候尝试！');
                    }else{
                        $orderData->rollback();
                        $this->orderReturn(0,null,'无法更新商品数据，请稍候尝试！');
                    }

                }
            }
        }
    }

    //添加商品进购物车，包含购买商品的数量更新
    public function addToCart(){
        $oraginData= session('user.shopcart');
        $gid=explode('-',I('post.gid'));
        $updateField=I('post.updateField');
        $shopBuyNum=I('post.shopBuyNum',0,'intval');
        $data=I('post.cartData');
        $saveModel=I('post.saveModel');
        $oldGID=I('post.oldGID');

        if(in_array('', $gid) && empty($updateField)){
            $error='empty data';
            $this->ajaxReturn(array('status'=>0,'error'=>'未能识别商品数据，请刷新页面后尝试重新添加！'));
        }
        $gid=join('-',$gid);

        //如果提交的商品数据为空,表示更新购物车商品数据，否则直接替换购物车中所有的商品数据（目前未使用）
        if(!empty($data)){
            $oraginData[$gid]=$data;
            session('user.shopcart',$oraginData);         
        }else{
            //单件商品数据添加更新
            if($saveModel=='update' && empty($updateField)){
                //如果是添加到购物车则判断是否超出容量
                if(empty($oraginData[$gid])){
                    if(count($oraginData)>=$this->EMallConfig['SHOPCART_MAXNUM']){
                        //return false;
                        $this->ajaxReturn(array('status'=>0,'error'=>'抱歉，您的购物车太满，超过'.$this->EMallConfig['SHOPCART_MAXNUM'].'，先结算一部分商品吧！'));
                    }
                }
                $oraginData[$gid]=$shopBuyNum;
            //多件商品数据更新
            }else if($saveModel=='update' && !empty($updateField)){
                foreach ($updateField as $key => $value) {
                   $oraginData[$key]=$value;  
                }  
            }
            session('user.shopcart',$oraginData);                 
        }
        //ChromePhp::log( session('user.shopcart'));
               //session('user.shopcart',null);

        $this->ajaxReturn(array('status'=>1,'data'=>$oraginData));
        //$this->ajaxReturn(array('status'=>1,'data'=>$gid,'error'=>$error));
    }

    //查看购物车详情页
    public function viewAllOfCartGoods(){
        //记录获取购物车数据的方式返回给显示页面，再由页面的ajax按reqType执行数据加载
        $reqType=I('get.reqType');
        $gid=I('get.gid');
        $specParam=I('get.specParam');
        $this->assign('reqType',$reqType);
        $this->assign('gid',$gid);
        $this->assign('specParam',$specParam);
        $this->display(':shopcart');
    }

    //获取购物车商品数据
    public function getShopCartData(){
        //session('shopCart',null);
        $type=I('get.type');
        $shopCartData= ApiService::getShopCartData($type);

        if($shopCartData){
            $this->ajaxReturn(array('status'=>1,'data'=>$shopCartData));  
        }else{
            $this->ajaxReturn(array('status'=>0,'data'=>'pp'));               
        }
    }

    //删除购物车商品数据
    public function removeShopCartData(){
        $gid=I('gid');
        $type=I('type');
        //删除选中
        if($type=='all'){
            //session('shopCart',null);
            $shopCartData=session('user.shopcart');
            foreach ($gid as $key => $value) {
                unset( $shopCartData[$value]);
            }
        }else{
            //删除单个
            $shopCartData=session('user.shopcart');
            unset($shopCartData[$gid]);
        }
        session('user.shopcart',$shopCartData);
        $this->ajaxReturn(array('status'=>1,'data'=>session('user.shopcart')));
    }
    
    //购物车订单结算（含运费）
    public function shopAccounts(){
        $gid=I('get.gid');
        $type=I('get.type');
        $logistics_id=I('get.lid');
        $getAddress=I('get.getAddr',0,intval);

        if($type=='all'){
            $goods_info=M('goods')
            ->join('__LOGISTICS__ ON __LOGISTICS__.logistics_id = __GOODS__.logistics_id')
            ->where(array('goods_id'=>array('in',$gid)))->field('goods_id,logistics_borne,logistics_type,goods_weight,goods_volume,logistics_param,freInsurance')->select();

        }else if($type=='logistics'){
            $goods_info=M('logistics')
                ->where(array('logistics_id'=>array('in',$logistics_id)))->field('logistics_id,logistics_type,logistics_param')->select();
        }

        if(!$goods_info){
            $this->ajaxReturn(array('status'=>0,'error'=>'商品数据查询出错，请稍候尝试！'));
        }

        if($getAddress==1){
            $userAddress=M('users')->where(array('id'=>$this->userid))->field('address')->find();
            if(!$userAddress){
                $this->ajaxReturn(array('status'=>0,'error'=>'获取用户地址数据出错，请稍候尝试！'));
            }            
        }


        $this->ajaxReturn(array('status'=>1,'data'=>$goods_info,'user'=>$userAddress['address']));

    }

    //创建订单
    public function createOrder(){
        //判断当前未支付订单数是否超过配置数
        $orderModel=M('order');
        $countWhere['shopper_id']=$this->userid;
        $countWhere['status']=0;
        $nopay_order_num=$orderModel->where($countWhere)->count();
           
        if($nopay_order_num>=$this->EMallConfig['NOPAY_MAXNUM']){
            $this->orderReturn(0,null,'您未支付的订单数超过了'.$this->EMallConfig['NOPAY_MAXNUM'].'笔，请先到我的订单中结算支付一部分订单吧！');
        }

        $goodsData=$_POST['goodsData'];
        $provinceId=I('post.province');
        $cityId=I('post.city');
        $consignee=I('post.consignee');
        $telphone=I('post.telphone');
        $address=I('post.address');
        $goodsData=json_decode($goodsData,true);
        //初始化变量
        $totalLogiPrice=0;
        $totalPrice=0;
        //当提交的订单中有效购买商品为0时不生成订单
        $totalCreateOrderGoodsNum=0;

        //生成商品ID查询字串 
        foreach ($goodsData as $key => $value) {
            $gidArr=explode('-',$key);
            $gid[]=array($gidArr[0]=>intval($gidArr[1]));
            $idStr.=empty($idStr)?intval($gidArr[0]):','.intval($gidArr[0]);
        }
        //$gid['12']='4';$gid['10']='4';
        changeModelProperty($orderModel,'goods');
        $result= $orderModel
        ->join('__LOGISTICS__ ON __LOGISTICS__.logistics_id = __GOODS__.logistics_id')
        ->where(array('goods_id'=>array('in',$idStr)))
        ->field('
            goods_id,
            cmf_goods.user_id as saler_id,
            goods_stock,
            goods_sku,
            goods_title,
            goods_price,
            goods_discount,
            goods_points,
            goods_img,
            goods_weight,
            logistics_borne,
            logistics_type,
            logistics_param,
            freInsurance
            ')->select();
       
        if(!$result){
            $this->ajaxReturn(array('status'=>0,'error'=>'读取商品数据出错，请稍候尝试！'));
        }
        //取对应购物车商品数据准备删除
        $userSession=session('user');

        //开启事务
        $orderModel->startTrans();
        foreach ($result as $idx => $goods) {
            if(!empty($goods['goods_sku'])){ 
                $goods['goods_sku']=json_decode($goods['goods_sku'],true);
            }
            $goods['logistics_param']=json_decode($goods['logistics_param'],true);
            //同组商品需要合计运费（单用户商城）
            $mergeLogiPrice=false;
            //累计购买同一件商品的重量或者体积变量（同一件商品不同属性按单个计算运费，所以需要累计）
            $lastModNum=-1;
            foreach ($gid as $key => $value) {
                //商品ID
                $goods_id=array_keys($value)[0];
                //单品项数据行索引ID,没有SKU数据时为0
                $sku_idx=!empty($goods['goods_sku'])?$value[$goods_id]:0;

                if($goods['goods_id']==$goods_id){                    
                    $shopBuyNum= $goodsData[$goods_id.'-'.$sku_idx]['shopBuyNum'];
                    //查找对应sku数据,空数据将执行json_decode
                    if(!empty($goods['goods_sku'])){                        
                        $stock=$goods['goods_sku']['SKU_Form'][$sku_idx]['sku_stock'];
                        $price=$goods['goods_sku']['SKU_Form'][$sku_idx]['sku_price'];
                        //判断价格是否合法(如果sku中没有设定价格则以商品实际单价为准)
                        $price=empty($price)?$price=$goods['goods_price']:$price;
                        //当购买量大于库存时，可购买数限制为剩余库存量
                        if($shopBuyNum>$stock){
                            $shopBuyNum=$stock;
                        }
                        $goods['goods_stock']=$stock;
                       //没有SKU数据时按普通商品计算
                    }else{
                        $price=$goods['goods_price'];
                        $stock=$goods['goods_stock'];
                        //当购买量大于库存时，可购买数限制为剩余库存量
                        if($shopBuyNum>$stock){
                            $shopBuyNum=$stock;
                        }
                        //按购买数减掉实际库存量
                        $goods['goods_stock']=$stock-$shopBuyNum;
                    }

                    //对比库存，刷新总价
                    if($stock>0){

                        //保存购买后的SKU数据信息
                        if(!empty($goods['goods_sku'])){
                            //按购买数减掉实际库存量
                            $goods['goods_sku']['SKU_Form'][$sku_idx]['sku_stock']=$stock-$shopBuyNum;
                            //更新数据表
                            $skuUpdateData['sku_stock']=$stock-$shopBuyNum;
                            $map['goods_id']=$goods_id;
                            $map['sku_idx']=$sku_idx;

                            $skuUpdate=$this->changeModelProperty($orderModel,'sku')->where($map)->save($skuUpdateData);

                            if($skuUpdate===false){
                                $orderModel->rollback();
                                $this->orderReturn(0,null,'订单提交失败，请稍候再试！');
                            }
                            //array('goods_id'=>$goods_id,'sku_idx'=>$sku_idx,'table'=>'sku','sql'=>$orderModel->getLastSql())
                        }

                        $totalPrice+=$price*$shopBuyNum;               

                        //配送方式
                        $wayIdx=$goodsData[$goods_id.'-'.$sku_idx]['logistics'];   

                        //运费计算
                        //if($goods['logistics_borne']>0){
                            //$cityId=$goodsData['city'];
                            //检查收货地址是否支持当前配送方式
                            $isSurpportArea=false;
                            foreach ($goods['logistics_param'][$wayIdx] as $key => $value) {
                                foreach ($value['area']['provinceId'] as $key => $pid) {
                                    if($provinceId==$pid){
                                        //取运费计算方式参照的变量
                                        if($goods['logistics_type']==0) {                                           
                                            //按购买件数计算
                                            $logiCountOut=$shopBuyNum-1;
                                            $first=$value['firstPrice'];

                                         }else if ($goods['logistics_type']==1||$goods['logistics_type']==2) {
                                            //按重量或者体积计算首重或首体积差（两种算法一样）                                         
                                            $curLogiCount=$goods['logistics_type']==1?$shopBuyNum*$goods['goods_weight']:$shopBuyNum*$goods['goods_volume'];
                                            //超出的重量如果不到下一档超量则按超量进行计算
                                            //判断是否是第一次计算同件商品运费
                                            if($lastModNum<0){
                                                //如果当前购买商品重量未超出首重，取超首重剩余的重量，否则取超出首重后剩余的超次重数量         
                                                if($value['first']>$curLogiCount){
                                                    $outNum=0;
                                                    $logiCountOut=0;
                                                    $lastModNum=$value['first']-$curLogiCount;
                                                }else{
                                                    //超出首重后的重量和
                                                    $outNum=$curLogiCount-$value['first'];
                                                    //超出首重后,按倍数减去超次重数量，取余数后，保存剩余的超次重数量
                                                    $multipleMod=($curLogiCount-$value['first'])*100%($value['next']*100)/100;
                                                    $lastModNum=$multipleMod>0?$value['next']-$multipleMod:0;
                                                    //计算实际超首重后的超次重倍数
                                                    $logiCountOut=ceil($outNum/$value['next']);
                                                }
                                                //首重价格，第一次和未超出首重时等于首重价格，超出后为0，只计算超出后的超次重费用
                                                $first=$value['firstPrice'];
                                            }else{
                                                //第一次以后的同商品运费计算，取第一次的剩余超重数量合并计算
                                                if($value['first']>($curLogiCount+$lastModNum)){
                                                    //未超首重时不计超出重量
                                                    $outNum=0;
                                                    $logiCountOut=0;
                                                    //保存剩余超首重的数量
                                                    $lastModNum=$value['first']-$curLogiCount-$lastModNum;
                                                    $first=$value['firstPrice'];
                                                }else{
                                                    //超出首重后先减去剩余超出首重的数量，剩余为超次重数量
                                                    $outNum=$curLogiCount-$lastModNum;
                                                    //计算超次重数量的倍数，取余数再计算出剩余超次重数量
                                                    $multipleMod=($outNum*100)%($value['next']*100)/100;                                      
                                                    $lastModNum=$multipleMod>0?$value['next']-$multipleMod:0;
                                                    $logiCountOut=ceil($outNum/$value['next']);
                                                    $first=0;
                                                }
                                                               
                                            }                                               

                                        }

                                        //ChromePhp::log('firstWeight:'.$shopBuyNum*$goods['goods_weight'].','.$logiCountOut.','.$multipleMod.','.$outNum.','.$curLogiCount.',pp:'.$lastModNum);
                                        
                                        //判断同组商品是否已经计算过运费（单用户商城）
                                        if(!$mergeLogiPrice){
                                            //计算运费, $freInsurance用于记录运费险勾选的价格，订单入库时使用
                                            $freInsurance=$goodsData[$goods_id.'-'.$sku_idx]['freInsurance']==true?$goods['freinsurance']:0;
                                            $mergeInsurance=$freInsurance;

                                            $logistics_price=$shopBuyNum<=0?0
                                                            :$first+$value['nextPrice']*$logiCountOut;
                                            $totalLogiPrice+=$logistics_price+$freInsurance;
                                            //刷新总价
                                            $totalPrice+=$logistics_price+$freInsurance;
                                            //
                                            $mergeLogiPrice=true;
                                        //合并的商品运费险数据$mergeLogiPrice都显示为-1
                                        }else{       
                                            
                                           //合并运费按附加部分收取
                                            $logistics_price=$value['nextPrice']*$logiCountOut;
                                            $totalLogiPrice+=$logistics_price;
                                            //刷新总价
                                            $totalPrice+=$logistics_price;
                                            $mergeInsurance=-1;
                                        }
                                        
                                        //ChromePhp::log($logistics_price);
                                         $isSurpportArea=true;
                                        break;
                                    }
                                }

                                if($isSurpportArea){
                                    break;
                                }
                                
                            }

                        //不支持配送时重置关键数据(用于验证非法提交数据)
                        if(!$isSurpportArea){
                            $logistics_price=0;
                            $shopBuyNum=0;
                            $freInsurance=0;             
                        }else{
                            //有效订单商品数递增
                            $totalCreateOrderGoodsNum+=1;
                        }

                    //无库存时重置关键数据，返回订单页面时将以这些数据进行检测标识
                    }else{
                        $logistics_price=0;
                        $shopBuyNum=0;
                        $freInsurance=0;
                    }

                    //积分数据处理，当选择按默认配置进行计算时，不需要设置手动积分字段值
                    if($goods['goods_points']==0){
                        $powNum=strlen(floor($price));
                        $goods_points=floor($price/pow(10,$powNum)*pow(10,$powNum-1));
                    }else{
                        $goods_points=$goods['goods_points'];
                    }

                    //构造订单的goods_data数据
                    $orderGoodsData[$goods_id.'-'.$sku_idx]=array(
                        'goods_id'=>$goods['goods_id'],
                        'goods_title'=>$goods['goods_title'],
                        'goods_img'=>$goodsData[$goods_id.'-'.$sku_idx]['goods_img'],
                        'price'=>$price,
                        'points'=>$goods_points,
                        'shopBuyNum'=>$shopBuyNum,
                        'goods_stock'=>$stock,
                        'sku_spec'=>$goods['goods_sku']['SKU_Form'][$sku_idx]['sku_spec'],
                        'sku_style'=>$goods['goods_sku']['SKU_Form'][$sku_idx]['sku_style'],
                        'logistics'=>$wayIdx,
                        //'logistics'=>$this->changeToLogistaticsStr($wayIdx),
                        'logistics_price'=>$logistics_price,
                        'logiSupport'=>$isSurpportArea,
                        'buyFreInsurance'=>$goodsData[$goods_id.'-'.$sku_idx]['freInsurance'],
                        'freInsurance'=>$mergeInsurance,
                        'status'=>0
                        );

                    $saler_id=$goods['saler_id'];

                    //准备删除session中对应的购物车商品
                    foreach ($userSession['shopcart'] as $key => $cartGID) {
                        if($key==$goods_id.'-'.$sku_idx){
                            //ChromePhp::log($cartGoods.','.$key.','.$cartGID);
                            unset($userSession['shopcart'][$key]);
                            break;
                        }
                    }                   

                }               
            }

            //订单中没有任何有效商品时直接返回
            if($totalCreateOrderGoodsNum==0){
                $orderModel->rollback();
                $this->orderReturn(0,null,'订单中的商品已售罄或者无效！');
            }

            if(!empty($goods['goods_sku'])){
                $updateGoods['goods_sku']=json_encode($goods['goods_sku']);
            }
            $updateGoods['goods_stock']=$goods['goods_stock'];
      
            $updateResult=$this->changeModelProperty($orderModel,'goods')->where(array('goods_id'=>$goods['goods_id']))->save($updateGoods);

            if($updateResult===false){
                $orderModel->rollback();
                $this->orderReturn(0,array('goods_id'=>$goods['goods_id'],'table'=>'goods'),'订单提交失败，请稍候再试！');
            }
            $goodsSql=$orderModel->getLastSql();
        }
        //创建订单入库信息
        $order_serial=date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $orderData['order_serial']=$order_serial;
        $orderData['shopper_id']=$this->userid;
        //$orderData['saler_id']=$saler_id;
        $orderData['goods_data']=json_encode($orderGoodsData);
        $orderData['total_price']=$totalPrice;
        $orderData['freinsurance']=$freInsurance;
        $orderData['consignee']=$consignee;
        $orderData['telphone']=$telphone;
        $orderData['address']=$address;
        $orderData['logistics_price']=$totalLogiPrice;
        
        //ChromePhp::log($orderData);
        //保存订单组关系信息
        $orderGroup=$this->changeModelProperty($orderModel,'order')->add($orderData);
        //unset($orderData);
        if(!$orderGroup===false){  
            //将单件商品信息分别存入order表
            foreach ($orderGoodsData as $key => $value) {
                $singleOrderData['order_gid']=$orderGroup;
                $singleOrderData['order_serial']=$order_serial;
                $singleOrderData['saler_id']=$saler_id;
                $singleOrderData['shopper_id']=$this->userid;
                $singleOrderData['goods_id']=$value['goods_id'];
                $sku_idx=explode('-',$key);
                $singleOrderData['sku_idx']=$sku_idx[1];
                $singleOrderData['goods_price']=$value['price'];
                $singleOrderData['points']=$value['points'];
                $singleOrderData['buy_num']=$value['shopBuyNum'];
                $singleOrderData['logistics_wayid']=$value['logistics'];
                $singleOrderData['logistics_price']=$value['logistics_price'];
                unset($value['goods_id'],$value['price'],$value['shopBuyNum'],$value['logistics'],$value['logistics_price']);
                $singleOrderData['goods_data']=json_encode(array($key=>$value));     

                $orderGoods=$this->changeModelProperty($orderModel,'order_relationships')->add($singleOrderData);

                if($orderGoods===false){
                    $orderModel->rollback();
                    $this->orderReturn(0,null,'订单提交失败，请稍候再试！');
                }
            }
            unset($orderGoodsData);

            //刷新session中对应的购物车商品数据以及订单量数据
            //session('user.shopcart',$cartGoods);
            $userSession['all_order_num']+=1;
            $userSession['nopay_order_num']+=1;
            session('user',$userSession);

            //
            $orderModel->commit();

            $this->orderReturn(1,array('order'=>$orderData,'cartGoodsNum'=>count($cartGoods)));
        }else{
            $orderModel->rollback();
            $this->orderReturn(0,null,'订单提交失败，请稍候再试！');
        }


    }

    //提交退货申请
    public function postApplyRefund(){
        if(IS_POST){
            $order_gid=I('post.order_id',0,'intval');
            $gidGroup=I('post.gidGroup');
            $refundInfo=$_POST['refundInfo'];
            //退货详情页面中编辑时传递此参数可以保存并返回编辑内容，否则只返回gid字串
            $type=I('post.type',0,'intval');
 
            if(!empty($order_gid) && !empty($gidGroup)){

                $orderModel=M('order');
                $where['shopper_id']=$this->userid;
                $where['order_gid']=$order_gid;

                $gidGroup=explode(',',$gidGroup);
                //更新映射数据
                $result=$orderModel->where($where)->field('goods_data')->find();
                if($result!==false){
                    $refundInfo=json_decode($refundInfo,true);
                    $result['goods_data']=json_decode($result['goods_data'],true);
                    foreach ($gidGroup as $key => $value) {
                        if(!empty($result['goods_data'][$value])){
                            //检验申请退货详细信息的文本长度
                            if(strlen($result['goods_data'][$value]['detail']>200)){
                                $this->orderReturn(0,null,'您填写的退款详情说明内容过长！');
                            }

                            $status=$result['goods_data'][$value]['status'];
                            $refundStatus=$result['goods_data'][$value]['refund_status'];
                            if($status>0 && $refundStatus<>-1){
                                //判断是否为拒绝退货商品
                                if($refundStatus==-1){
                                    //删除拒绝退货商品的更新ID
                                    unset($gidGroup[$key]);
                                    continue;
                                }else{
                                    //退货详细状态标识（-1为拒绝退货，1为申请状态，2为接收申请，3为寄回退货商品，4为确认收回退货商品，5已退款）
                                    if($type<>1){
                                        //type为1时只改变退货基本信息，不改变退货的状态
                                        $result['goods_data'][$value]['refund_status']=1;
                                        //退货申请信息
                                        $result['goods_data'][$value]['refund_info']=$refundInfo[$value];
                                        $result['goods_data'][$value]['refund_info']['applyTime']=date('Y年m月d日 h:i:s');
                                    }else{
                                        $result['goods_data'][$value]['refund_info']['contact']=$refundInfo[$value]['contact'];
                                        $result['goods_data'][$value]['refund_info']['telNum']=$refundInfo[$value]['telNum'];
                                        $result['goods_data'][$value]['refund_info']['detail']=$refundInfo[$value]['detail'];
                                    }
                                }
                            }else if($refundStatus<>0){
                                //删除重复申请的商品更新ID
                                unset($gidGroup[$key]);
                                continue;
                            }
                        }
                    }
                    //如果没有需要更新的退货商品则直接返回
                    if(empty($gidGroup)){
                        $this->orderReturn(0,null,'勾选的商品已经处于申请退货或者被拒绝退货状态，请不要重复提交！');
                    }

                    $orderModel->startTrans();
                    $result['goods_data']=json_encode($result['goods_data']);
                    $update=$orderModel->where($where)->save($result);

                    //更新订单商品数据
                    if($update!==false){
                        if(!empty($refundInfo)){
                            $this->changeModelProperty($orderModel,'order_relationships');
                            foreach ($gidGroup as $key => $value) {
                                $value=explode('-',$value);
                                $where['goods_id']=intval($value[0]);
                                $where['sku_idx']=intval($value[1]);
                                if($type<>1){
                                    $updateData['refund_status']=1;
                                }
                                $updateData['refund_info']= json_encode($refundInfo[$value[0].'-'.$value[1]]);
                                $update=$orderModel->where($where)->save($updateData);
                                if($update!==false){
                                    $orderModel->commit();
                                    //非编辑模式返回申请成功的商品gid
                                    if(empty($type)){
                                        $this->orderReturn(1,$gidGroup);
                                    }else if($type==1){
                                        $this->orderReturn(1,$refundInfo[$value[0].'-'.$value[1]]);
                                    }
                                }else{
                                    $orderModel->rollback();
                                    $this->orderReturn(0,null,'保存退货申请数据出错，请稍候尝试！');
                                }
                            }
                        }
                    }
                    $orderModel->rollback();
                    $this->orderReturn(0,null,'保存退货申请数据出错，请稍候尝试！');

                }

            }
        }
    }

    //查看订单商品退货详情
    public function viewRefund(){
        $goods_id=I('get.gid');
        $order_gid=I('get.order_id',0,'intval');

        if(!empty($goods_id) && strlen($goods_id)<30 && !empty($order_gid)){
            $where['order_gid']=$order_gid;
            $where['shopper_id']=$this->userid;
            $refundData=M('order')->where($where)->field('order_gid,order_serial,goods_data')->find();

            if($refundData!==false){
                $refundData['goods_id']=$goods_id;
                $this->assign('refundData',$refundData);
            }else{
                $this->error('未查找到任何相关退款数据！');
            }
        }

        $this->display(':refundView');
    }

    //提交退货运单
    public function postRefundWayBillNo(){
        if(IS_POST){
            $order_gid=I('post.order_id',0,'intval');
            $goods_id=I('post.gid');
            $refundWayBillNo=I('post.refundWayBillNo');

            if(!empty($order_gid) && !empty($goods_id) && !empty($refundWayBillNo)){
                $orderModel=M('order');  
                $where['order_gid']=$order_gid;
                $where['shopper_id']=$this->userid;

                $refundData=$orderModel->where($where)->field('goods_data')->find();
                $refundData['goods_data']=json_decode($refundData['goods_data'],true);

                if(!empty($refundData['goods_data'][$goods_id]) && $refundData['goods_data'][$goods_id]['refund_info']['refundWayBillNo']!==$refundWayBillNo){
                    $refundData['goods_data'][$goods_id]['refund_info']['refundWayBillNo']=$refundWayBillNo;
                    $refundData['goods_data'][$goods_id]['refund_status']=3;
                    $refundData['goods_data'][$goods_id]['refund_info']['refund_waybill_sendtime']=date('Y年m月d日 H:i');
                    $returnRefundData=$refundData['goods_data'][$goods_id]['refund_info'];
                    $refundData['goods_data']=json_encode($refundData['goods_data']);

                    $orderModel->startTrans();
                    //更新映射数据
                    $update=$orderModel->where($where)->save($refundData);
                    if($update!==false){
                        unset($refundData);
                        $goods_id=explode('-',$goods_id);
                        $where['goods_id']=intval($goods_id[0]);
                        $where['sku_idx']=intval($goods_id[1]);
                        $updateData['refund_waybill_sendtime']=array('exp','CURRENT_TIMESTAMP');
                        $updateData['refund_status']=3;
                        $updateData['refund_waybill']=$refundWayBillNo;
                        //更新订单商品数据
                        $this->changeModelProperty($orderModel,'order_relationships');
                        $update=$orderModel->where($where)->save($updateData);
                        if($update!==false){
                            $orderModel->commit();
                            $this->orderReturn(1,$returnRefundData);
                        }
                    }

                    $orderModel->rollback();
                    $this->orderReturn(0,null,'无法保存退货运单数据，请稍候尝试！');
                }

            }

        }
    }

    //申请售后
    public function postApplyAfterSale(){
        if(IS_POST){
            $order_gid=I('post.order_id',0,'intval');
            $afterSaleInfo=$_POST['afterSaleInfo'];
            
            if(!empty($order_gid) && !empty($afterSaleInfo)){
                $orderModel=M('order');
                $where['order_gid']=$order_gid;
                $where['shopper_id']=$this->userid;

                $orderModel->startTrans();
                //判断用户操作权限
                $orderData = $orderModel
                            ->where($where)
                            ->field('goods_data,order_serial')
                            ->find();

                if($orderData!==false){                    

                    $orderData['goods_data']=json_decode($orderData['goods_data'],true);

                    //解析提交的售后申请（有可能是批量的提交）
                    $afterSaleInfo=json_decode($afterSaleInfo,true);
                     
                    $this->changeModelProperty($orderModel,'after_sale');
                    //添加售后申请数据
                    foreach ($afterSaleInfo as $key => $value) {
                        if(!empty($orderData['goods_data'][$key])){
                            //验证商品是否处于退货状态或者已经是售后状态，否则不允许申请售后
                            if(empty($orderData['goods_data'][$key]['refund_status']) && empty($orderData['goods_data'][$key]['afterSaleData'])){
                                $orderData['goods_data'][$key]['afterSaleData']['afterSaleStatus']=0;
                            }else{
                                unset($afterSaleInfo[$key]);
                                continue;
                            }

                            //生成返回的gid组
                            $gidGroup.=empty($gidGroup)?$key:','.$key;


                            //插入新的申请售后数据                        
                            $updateData['order_id']=$order_gid;
                            $updateData['order_serial']=$orderData['order_serial'];
                            //生成售后申请商品的id与单品属性索引值
                            $goods_id=explode('-',$key);
                            $updateData['goods_id']=$goods_id[0];
                            $updateData['sku_idx']=$goods_id[1];
                            $updateData['goods_data']=json_encode($orderData['goods_data'][$key]);
                            $updateData['shopper_id']=$where['shopper_id'];
                            $updateData['contact']=$value['contact'];
                            $updateData['telnum']=$value['telNum'];
                            $updateData['apply_detail']=$value['detail'];

                            $result=$orderModel->add($updateData);

                            if($result===false){
                                $errorState=1;
                                $orderModel->rollback();
                                break;
                            }
                            //将插入的数据id写入到映射数据中
                            $orderData['goods_data'][$key]['afterSaleData']['as_id']=$result;
                        }
                    }

                    unset($orderData['order_serial']);
                    $orderData['goods_data']=json_encode($orderData['goods_data']);
                    //保存申请售后的商品gid到对应的订单数据中
                    if($this->changeModelProperty($orderModel,'order')->where($where)->save($orderData)===false){
                        $errorState=1;
                        $orderModel->rollback();
                    }

                    if($errorState==0){
                        $orderModel->commit();
                        $this->orderReturn(1,$gidGroup);
                    }
                }

            }
            $this->orderReturn(0,null,'无法保存提交的申请售后数据，请稍候尝试！');
        }
    }

    public function postAfterSaleWayBillNo(){
        if(IS_POST){
            $as_id=I('post.as_id',0,'intval');
            $waybill_no=I('post.waybill_no');
            $errorState=0;

            if(!empty($as_id) && !empty($waybill_no)){
                $afterSaleModel=M('after_sale');
                $afterSaleData=$afterSaleModel->where(array('as_id'=>$as_id))->field('shopper_id,waybill_no,post_waybill_time')->find();
                //验证操作权限
                if($afterSaleData['shopper_id']==$this->userid && $waybill_no!==$afterSaleData['waybill_no']){

                    $updateData['waybill_no']=$waybill_no;
                    $updateData['status']=2;
                    if(empty($afterSaleData['post_waybill_time'])){
                        $updateData['post_waybill_time']=array('exp','CURRENT_TIMESTAMP');
                        $postTime=date('Y-m-d H:i:ss');
                    }

                    $result=$afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);
                    if($result===false){
                        $errorState=1;
                    }
                }

                if($errorState==0){
                    $this->orderReturn(1,$postTime);
                }
            }
            $this->orderReturn(0,null,'无法保存提交的申请售后数据，请稍候尝试！');
        }
    }

    //查看售后服务详情
    public function viewAfterSale(){
        $as_id=I('get.as_id',0,'intval');
        /*$order_id=I('get.order_id',0,'intval');
        $goods_id=I('get.gid');
        $goods_id=explode('-',$goods_id);
        $where['order_id']=$order_id;
        $where['goods_id']=$goods_id[0];
        $where['sku_idx']=$goods_id[1];*/

        if(!empty($as_id)){
            $where['as_id']=$as_id;
            $afterSaleModel=M('after_sale');
            $afterSaleData=$afterSaleModel->where($where)->order('as_id desc')->find();
            if(!empty($afterSaleData)){
                $this->assign('afterSaleData',$afterSaleData);
            }else{
                $this->error('无法获取售后申请数据！');
            }
        }

        $this->display(':afterSaleView');
    }

    //修改售后申请信息
    public function postEditAfterSale(){
        if(IS_POST){
            $as_id=I('post.as_id',0,'intval');
            $contact=I('post.contact');
            $telNum=I('post.telNum');
            $afterSaleInfo=I('post.detail');
            $errorState=0;
            
            if(!empty($as_id) && !empty($contact) && !empty($telNum) && !empty($afterSaleInfo) && strlen($afterSaleInfo)<500){

                $afterSaleModel=M('after_sale');
                $afterSaleData=$afterSaleModel->where(array('as_id'=>$as_id))->field('shopper_id')->find();
                if(!empty($afterSaleData)){
                    //验证操作权限
                    if($afterSaleData['shopper_id']==$this->userid){
                        $updateData['contact']=$contact;
                        $updateData['telnum']=$telNum;
                        $updateData['apply_detail']=$afterSaleInfo;

                        $result=$afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);

                        if($result===false){
                            $errorState=1;
                        }
                    }else{
                        $errorState=1;
                    }
                }else{
                    $errorState=1;
                }
            }
            if($errorState==0){
                $this->orderReturn(1);
            }else{
                $this->orderReturn(0,null,'无法保存提交的申请售后数据，请稍候尝试！');
            }

        }
    }

    //支付售后服务费用
    public function doAfterSalePay(){
        $as_id=I('get.as_id',0,intval);        

        if(!empty($as_id)){
            $afterSaleModel=M('after_sale');
            $payData=$afterSaleModel->where(array('as_id'=>$as_id))->field('shopper_id,service_type,service_price,finished_payment')->find();
            //校验操作权限、支付状态和金额
            if($payData['finished_payment']<=0 && $payData['shopper_id']==$this->userid){
                //支付接口待实现 
                //
                //改变售后服务费用支付状态为完成并更新换修处理进度数值
                //if($payData['service_type']==2){
                $updateData['repaire_step']=3;
               //}
                $updateData['finished_payment']=1;
                $result=$afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);
                if($result===false){
                    $this->error('支付已经成功，但服务器接收数据异常，请联系售后客服！');
                }else{
                    $this->success('支付成功，请耐心等待售后客服处理！');
                }
            }
        }
    }

    //确认完成售后服务
    public function confirmFinishedAfterSale(){
        if(IS_POST){
            $as_id=I('post.as_id',0,'intval');
            $goods_id=I('post.gid');
            $errorState=0;

            if(!empty($as_id) && !empty($goods_id)){
                $afterSaleModel=M('after_sale');
                $afterSaleModel->startTrans();
                $where['B.shopper_id']=$this->userid;
                $where['as_id']=$as_id;
                //这里也可以不用join，无需要时可修改
                $afterSaleData=$afterSaleModel
                ->alias('A')
                ->join('__ORDER__ B ON A.order_id=B.order_gid')
                ->where($where)
                ->field('B.order_gid,B.goods_data')
                ->find();

                if($afterSaleData!==false){
                    //更新订单中的映射数据
                    $afterSaleData['goods_data']=json_decode($afterSaleData['goods_data'],true);
                    $afterSaleData['goods_data'][$goods_id]['afterSaleData']['afterSaleStatus']=4;
                    //ChromePhp::log($afterSaleData);
                    //更新售后申请表数据
                    $updateData['status']=4;
                    $updateData['finish_time']=array('exp','CURRENT_TIMESTAMP');
                    $result=$afterSaleModel->where(array('as_id'=>$as_id,'shopper_id'=>$this->userid))->save($updateData);
                    if($result===false){
                        $afterSaleModel->rollback();
                        $errorState=1;
                    }else{
                        //更新订单数据入库
                        $afterSaleData['goods_data']=json_encode($afterSaleData['goods_data']);
                        if(changeModelProperty($afterSaleModel,'order')
                            ->where(array('order_gid'=>$afterSaleData['order_gid'],'shopper_id'=>$this->userid))
                            ->save(array('goods_data'=>$afterSaleData['goods_data']))===false){
                            $afterSaleModel->rollback();
                            $errorState=1;
                        }
                    }

                    if($errorState==0){
                        $afterSaleModel->commit();
                        //返回完成时间
                        $this->orderReturn(1,date('Y-m-d H:i:s'));
                    }
                }else{
                    $errorState=1;
                }

            }
            $this->orderReturn(0,null,'确认完成售后服务操作失败，请稍候尝试！');
        }
    }

    //提交商品评价
    public function postAppraise(){
        if(IS_POST){
            $order_gid=I('post.order_id',0,'intval');
            $gid=I('post.gid');
            $appraiseContent=I('post.appraiseContent');
            //评论字数限制
            $appraiseTxtNum=$this->EMallConfig['APPRAISE_STRLEN'];
            $appraiseScore=I('post.appraiseScore',0,'intval');
            $goods_sku=I('post.goods_sku');
            $errorState=0;

            if(!empty($order_gid) && !empty($gid) && !empty($appraiseContent) && strlen($appraiseContent)<=$appraiseTxtNum){

                $appraiseModel=M('order');
                $where['shopper_id']=$this->userid;
                $where['order_gid']=$order_gid;
                $appraiseModel->startTrans();
                //更新映射数据
                $orderData=$appraiseModel->where($where)->field('goods_data')->find();
                if($orderData!==false){
                    $orderData['goods_data']=json_decode($orderData['goods_data'],true);
                    if(!empty($orderData['goods_data'][$gid])){
                        $status=$orderData['goods_data'][$gid]['status'];
                        //判断是否已经确认收货，其它状态下不允许评价
                        if($status==4){
                            $orderData['goods_data'][$gid]['status']=5;
                            $orderData['goods_data']=json_encode($orderData['goods_data']);
                            $update=$appraiseModel->where($where)->save($orderData);
                            if($update!==false){
                                //更新订单商品数据
                                $gid=explode('-',$gid);
                                $where['goods_id']=intval($gid[0]);
                                $where['sku_idx']=intval($gid[1]);
                                $updateData['status']=5;

                                $this->changeModelProperty($appraiseModel,'order_relationships');
                                $update=$appraiseModel->where($where)->save($updateData);
                                if($update!==false){
                                    //插入评论数据
                                    $this->changeModelProperty($appraiseModel,'appraise');
                                    $appraiseData['goods_id']=$gid[0];
                                    $appraiseData['sku_idx']= $gid[1];
                                    $appraiseData['order_gid']=$order_gid;
                                    $appraiseData['appraise_content']=$appraiseContent;
                                    $appraiseData['appraise_score']=$appraiseScore;
                                    $appraiseData['shopper_id']=$this->userid;
                                    $appraiseData['shopper_name']=session('user.user_nicename');
                                    //模型表操作转到评价表写入数据 
                                    $update=$appraiseModel->add($appraiseData);
                                    if($update!==false){
                                        $appraiseModel->commit();
                                        $this->orderReturn(1);
                                    }
                                }
                            }
                            $appraiseModel->rollback();
                            $this->orderReturn(0,null,'无法保存提交的商品追评数据，请稍候尝试！');
                        }else{
                            return false;
                        }
                    }
                }

            }
        }
    }

    //追评
    public function postAppraiseAdditional(){
        $order_gid=I('post.order_id',0,'intval');
        $appraiseContent=I('post.appraiseContent');
        $gid=I('post.gid');
        //原有的已经追评的数据
        $appraiseAddt=I('post.appraiseAddt');
        $errorState=0;
        $appraiseTxtNum=$this->EMallConfig['APPRAISE_STRLEN'];
        


        $appraiseModel=M('order');
        $appraiseModel->startTrans();
        //取原有的
        //$appraiseData =$appraiseModel->where($where)->field('appraise_additional')->find();
        if(!empty($appraiseContent) && strlen($appraiseContent)<=$appraiseTxtNum){
                $where['order_gid']=$order_gid;
                $where['shopper_id']=$this->userid;
                $appraiseModel->startTrans();
                //更新映射数据
                $orderData=$appraiseModel->where($where)->field('goods_data')->find();
                if($orderData!==false){
                    $orderData['goods_data']=json_decode($orderData['goods_data'],true);
                    //更新映射数据
                    if(!empty($orderData['goods_data'][$gid])){
                        $orderData['goods_data'][$gid]['status']=6;
                        $orderData['goods_data']=json_encode($orderData['goods_data']);
                        $update=$appraiseModel->where($where)->save($orderData);
                        //更新商品数据
                        if($update!==false){
                            unset($orderData);
                            $gid=explode('-',$gid);
                            $where['goods_id']=intval($gid[0]);
                            $where['sku_idx']=intval($gid[1]);
                            $updateData['status']=6;

                            $this->changeModelProperty($appraiseModel,'order_relationships');
                            $update=$appraiseModel->where($where)->save($updateData);
                            if($update!==false){
                                //更新评论数据
                                $this->changeModelProperty($appraiseModel,'appraise');
                                $appraiseData['appraise_additional']=$appraiseContent;
                                $appraiseData['appraise_addt_time']=array('exp','CURRENT_TIMESTAMP');
                                $update=$appraiseModel->where($where)->save($appraiseData);
                                if($update!==false){
                                    $appraiseModel->commit();
                                    $this->orderReturn(1);    
                                }
                            }
                        }

                        $appraiseModel->rollback();
                        $this->orderReturn(0,null,'无法保存提交的商品追评数据，请稍候尝试！');
                    }
                }
 
        }

    }

    //将配送方式的索引值转为对应的文本
   public function changeToLogistaticsStr($idx){
        $wayStr='';
            switch($idx){
                case '0':
                    $wayStr='快递';
                    break;
                case '1':
                    $wayStr='物流';
                    break;
                case '2':
                    $wayStr='EMS';
                    break;
                case '3':
                    $wayStr='平邮';
                    break;
            }
        return $wayStr;
    }

    /**
     * 使用事务需要同时多表更新时转换模型属性进行对应表更新
     * @access public
     * @param string $name 属性值，因为模型名与表名都是一致的，所以直接设置表名即可（无需加入表前缀）
     * @param Model $Model 要改变属性值的模型对象
     */

    public function changeModelProperty($Model,$name){
        $Model->setProperty('tableName',$name);
        $Model->setProperty('name',$name);
        $Model->setProperty('trueTableName',C('DB_PREFIX').$name);
        $Model->setProperty('fields','');
        $Model->flush();  
        return $Model; 
    }

    public function orderReturn($status,$returnData,$msg){
        $this->ajaxReturn(array('status'=>$status,'data'=>$returnData,'error'=>$msg));
    }

}
