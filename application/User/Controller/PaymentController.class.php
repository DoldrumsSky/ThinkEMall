<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;
use EMall\Service\ApiService;
use vendor\PHPAlipay;
use vendor\PHPWeiXinPay;
use vendor\PHPqrcode;

/**
 * 致阳网站设计 电子商城支付管理控制器（未实际测试，请根据使用时的需要自行测试修改）
 * ============================================================================
 * * 版权所有 2016-2020 致阳网站设计，并保留所有权利。
 * 代码设计：阳华
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的或者经作者本人授权的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: YangHua $
 * $Id: PaymentController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class PaymentController extends MemberbaseController {
    function _initialize(){
        parent::_initialize();
        $messageData['messageNum']=$this->messageCount;
        $this->assign('messageData',$messageData);
    }    
    
    // 付款页面
	public function index() {
	    $order_gid=I('request.order_gid',0,'intval');
        if(!empty($order_gid)){
            $userPoints=$this->user['points'];
            $where['order_gid']=$order_gid;
            $where['shopper_id']=$this->userid;
            $orderModel=M('order');
            $amountData=$orderModel->where($where)->field('order_serial,goods_data,total_price,modify_price')->find();
            if($amountData!==false){
                $amountData['order_gid']=$order_gid;
                //取实际支付价格，如果订单存在修改的价格则以修改后的订单价格为准
                $amountData['total_price']=empty($amountData['modify_price'])?$amountData['total_price']:$amountData['modify_price'];
                unset($amountData['modify_price']);
                $this->assign('userPoints',$userPoints);
                $this->assign('amountData',$amountData);
                $this->display(":payment");
            }
        }
    }

    //我要充值
    public function deposit(){
        $deposit_fee=I('request.deposit_fee',0,'floatval');
        $order_type=I('request.order_type',0,'intval');
        $service_id=I('request.service_id',0,'intval');
        //新订单充值
        if(empty($service_id)){
            $amountData['total_price']=$deposit_fee;
            $amountData['order_serial']='商城充值';
        //未支付订单充值
        }else{
            $orderModel=M('service_order');
            $where['shopper_id']=$this->userid;
            $where['service_id']=$service_id;
            $where['status']=0;
            $orderData=$orderModel->where($where)->field('total_price')->find();
            if($orderData!==false){
                $amountData['total_price']=$orderData['total_price'];
                $amountData['order_serial']='商城充值';
            }else{
                $this->error('未查询到对应的充值订单数据或者充值订单已失效！');
            }
        }

        $this->assign('amountData',$amountData);
        $this->assign('order_type',$order_type);
        $this->assign('deposit_fee',$amountData['total_price']);
        $this->assign('service_id',$service_id);    
        $this->display(':payment');
    }   

    //支付
    public function doPayment(){
        $payment=I('request.payment');
        //订单类型，1为商城充值类订单，2为售后类服务收费订单，否则为商品购物订单
        $order_type=I('request.order_type',0,'intval');
        $order_gid=I('request.order_gid',0,'intval');
        $service_id=I('request.service_id',0,'intval');
                                
        if(!empty($payment)){
            $orderModel=M('order');
            $where['shopper_id']=$this->userid;        

            //商城充值订单处理（付款前生成充值订单）
            if($order_type==1){
                $deposit_fee=I('request.deposit_fee',0,'floatval');
                if(!empty($deposit_fee)){   
                    changeModelProperty($orderModel,'service_order');
                    //如果不是未支付充值订单，则创建订单入库信息
                    if(empty($service_id)){
                        $order_serial=date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
                        $amountData['order_serial']=$order_serial;
                        $amountData['shopper_id']=$where['shopper_id'];
                        $amountData['service_name']='商城充值';
                        $amountData['order_type']=$order_type;
                        $amountData['total_price']=$deposit_fee;
                        $amountData['payment']=$payment;
                        //生成充值订单数据
                        $result=$orderModel->add($amountData);
                        if($result===false){
                            $this->error('生成充值订单出错，请稍候尝试！');
                        }
                    //未支付充值订单支付
                    }else{
                        $where['service_id']=$service_id;
                        $where['status']=0;
                        $amountData=$orderModel->where($where)->field('total_price,order_serial')->find();
                        if($amountData===false){
                            $this->error('未查询到对应的充值订单数据或者充值订单已失效！');
                        }
                        //先通过支付渠道记录查询当前订单是否已经支付过，已支付过的话直接更新当前订单数据
                        //.....未实现
                        
                        //如果查询为未支付则继续
                        
                    }
                }
            }

            //购物订单付款处理
            if(!empty($order_gid) && empty($order_type)){
                $where['order_gid']=$order_gid;
                $amountData=$orderModel->where($where)->field('order_serial,total_price,modify_price')->find();           
            }

            //开始支付(支付时提交的订单号将会使用$order_type补上一位用于识别支付的是什么类型的消费，实际数据库中的订单号不包含这一位数字)
            if($amountData!==false){
                //取实际支付价格，如果订单存在修改的价格则以修改后的订单价格为准
                $amountData['total_price']=empty($amountData['modify_price'])?$amountData['total_price']:$amountData['modify_price'];
                unset($amountData['modify_price']);

                if($payment=='alipay'){
                    $this->doAlipay($amountData['order_serial'].$order_type,$amountData['total_price'],$order_type);
                }else if($payment=='weixinPay'){
                    $code_url=$this->doWeixinPay($amountData['order_serial'].$order_type,$amountData['total_price'],$order_type);
                    if(!empty($code_url)){
                        $this->assign('payment','微信');
                        $this->assign('total_price',$amountData['total_price']);
                        $this->assign('order_serial',$amountData['order_serial']);
                        $this->assign('code_url',urlencode($code_url));
                        $this->display(':doPayment');
                    }else{
                        $this->error('未能成功生成微信支付二维码，请稍候尝试！');
                    }
                //余额支付
                }else if($payment=='balancePay'){
                    //检查余额
                    $balanceModel=M('users');
                    $result=$balanceModel->where(array('id'=>$where['shopper_id']))->field('balance')->find();
                    if($result!==false){
                        $balance=$result['balance'];
                        if($amountData['total_price']>$balance){
                            $this->error('您的余额不足，请先充值！');
                        }else{
                            //扣除余额
                            $afterBillBalance=$balance-$amountData['total_price'];
                            $result=$balanceModel->where(array('id'=>$where['shopper_id']))->save(array('balance'=>$afterBillBalance));
                            if($result===false){
                                $this->error('无法更新余额数据进行支付，请稍候尝试！');
                            }
                            //更新订单信息
                            $this->disposeAfterPay(array('out_trade_no'=>$amountData['order_serial'].$order_type,'time_end'=>date('Y-m-d H:i:s'),'total_fee'=>$amountData['total_price']),'balancePay');
                        }
                    }
                }
            }
        }
    }
  

    //支付宝结账
    private function doAlipay($order_serial,$total_price){
        vendor('PHPAlipay.pagepay.service.AlipayTradeService');
        vendor('PHPAlipay.pagepay.buildermodel.AlipayTradePagePayContentBuilder');
        //订单名称，必填
        $subject = '商品购物'.$order_serial;

        //商品描述，可空
        $body = '';

        //构造参数
        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setTotalAmount($total_price);
        $payRequestBuilder->setOutTradeNo($order_serial);

        $config=ApiService::getEMallConfig(2);

        //签名方式
        $config['charset']="UTF-8";
        $config['sign_type']="RSA2";

        $aop = new \AlipayTradeService($config);

        /**
         * pagePay 电脑网站支付请求
         * @param $builder 业务参数，使用buildmodel中的对象生成。
         * @param $return_url 同步跳转地址，公网可以访问
         * @param $notify_url 异步通知地址，公网可以访问
         * @return $response 支付宝返回的信息
        */
        $response = $aop->pagePay($payRequestBuilder,$config['return_url'],$config['notify_url']);        
    }

    //支付宝异步通知处理
    public function alipayNotify(){
        vendor('PHPAlipay.pagepay.service.AlipayTradeService');

        $config=ApiService::getEMallConfig(2);
        //签名方式
        $config['charset']="UTF-8";
        $config['sign_type']="RSA2";
        $alipaySevice = new \AlipayTradeService($config);

        $arr=$_POST;

        $alipaySevice->writeLog(var_export($_POST,true));
        $result = $alipaySevice->check($arr);
        /* 实际验证过程建议商户添加以下校验。
        1、商户需要验证该通知数据中的out_trade_no是否为商户系统中创建的订单号，
        2、判断total_amount是否确实为该订单的实际金额（即商户订单创建时的金额），
        3、校验通知中的seller_id（或者seller_email) 是否为out_trade_no这笔单据的对应的操作方（有的时候，一个商户可能有多个seller_id/seller_email）
        4、验证app_id是否为该商户本身。
        */
        if($result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            
            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];


            if($_POST['trade_status'] == 'TRADE_FINISHED') {

                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                    //如果有做过处理，不执行商户的业务程序            
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知
                //
                //商城处理付款完成后的订单状态更新
                if($this->identifyPaymentInfo($result,'alipay')){
                    $this->disposeAfterPay(array('out_trade_no'=>$out_trade_no,'transaction_id'=>$trade_no,'total_fee'=>$result['total_amount'],'time_end'=>$result['gmt_payment']),'alipay');
                }
            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success"; //请不要修改或删除
        }else {
            //验证失败
            echo "fail";
        }
    }

    //微信支付
    private function doWeixinPay($order_serial,$total_price){
        vendor('PHPWeiXinPay.lib.WxPayApi');
        //获取支付配置
        $config=ApiService::getEMallConfig(3);
        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no($order_serial);
        $input->SetTotal_fee($total_price*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url($config['notify_url']);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id("123456789");
        $result = $this->GetPayUrl($input);
        
        return $result["code_url"];
    }


    //支付二维码生成
    public function payqrcode(){
        vendor('PHPqrcode.phpqrcode') ;
        $QRcode= new \QRcode();
        $url = urldecode($_GET["data"]);
        $QRcode->png($url,false, 3, 8, 2);
    }


    /**
     * 微信支付
     * 生成直接支付url，支付url有效期为2小时,模式二
     * @param UnifiedOrderInput $input
     */
    private function GetPayUrl($input)
    {
        if($input->GetTrade_type() == "NATIVE")
        {
            $result = \WxPayApi::unifiedOrder($input);
            return $result;
        }
    }

    /*****
     *微信异步通知处理
     */
    public function WeixinNotify(){
        vendor('PHPWeiXinPay.lib.notify');
        $notify = new \PayNotifyCallBack();
        //var_dump($result);
        $result=$notify->Handle(false);

        //处理返回的数据，存储交易号
        if($result!==false){
            //查询核实订单的支付状态（如果不需要此即时验证可取消）
            $payState=$notify->Queryorder($result['transaction_id']);
            if($payState!==false){
                //验证商户信息
                if($this->identifyPaymentInfo($result,'weixinPay')){
                    //转换返回的订单总金额单位为元
                    $result['total_fee']=$result['total_fee']*100;
                    $this->disposeAfterPay($result,'weixin');
                }
            }
        }
    }

    //校验基础的商户身份信息
    private function identifyPaymentInfo($payData,$payment){
        //微信
        if($payment=='weixinPay'){
            $config=ApiService::getEMallConfig(3);
            if($payData['appid']==$config['APP_ID'] && $payData['mch_id']==$config['MCHID']){
                return true;
            }
        //支付宝
        }else if($payment=='alipay'){
            $config=ApiService::getEMallConfig(2);
            if($payData['app_id']==$config['app_id'] && $payData['app_id']==$config['app_id']){
                return true;
            }
        }
        return false;
    }

    //支付后的订单状态及数据修改
    private function disposeAfterPay($payData,$payment){
        $order_serial=$payData['out_trade_no'];
        $transaction_id=$payData['transaction_id'];
        $pay_time=$payData['time_end'];
        $total_fee=$payData['total_fee'];
        $where['shopper_id']=$this->userid;
        $where['order_serial']=substr($order_serial,0,16);
        $order_type=substr($order_serial,-1,1);

        //购物支付成功后的处理
        if(empty($orderType)){
            $orderModel=M('order');
            $orderData=$orderModel->where($where)->field('goods_data,total_price,modify_price,status')->find();

            //验证数据是否需要处理
            if($orderData!==false){
                //取实际支付价格，如果订单存在修改的价格则以修改后的订单价格为准
                $orderData['total_price']=empty($orderData['modify_price'])?$orderData['total_price']:$orderData['modify_price'];
                unset($orderData['modify_price']);
                //校验数据一致性
                if($orderData['status']==0 && $orderData['total_price']==$total_fee){
                    $orderData['goods_data']=json_decode($orderData['goods_data'],true);
                    $orderModel->startTrans();
                    //更新订单商品数据，如果更新支付状态失败需要自行处理
                    foreach ($orderData['goods_data'] as $key => $value) {
                        $orderData['goods_data'][$key]['status']=1;
                        $gid=explode('-',$key);
                        $where['goods_id']=$gid[0];
                        $where['sku_idx']=$gid[1];
                        changeModelProperty($orderModel,'order_relationships');
                        $update=$orderModel->where($where)->save(array('status'=>1));
                        if($update===false){
                            $orderModel->rollback();
                            break;
                        }

                        //更新商品的销售数据
                        changeModelProperty($orderModel,'goods');
                        $update=$orderModel->where(array('goods_id'=>$gid[0]))->save(array('permonth_sales'=>array('exp','permonth_sales+1')));
                        if($update===false){
                            $orderModel->rollback();
                            break;
                        }
                        //计算订单总消费积分
                        $points+=$value['points'];
                    }
                    //更新映射数据及订单组状态数据
                    unset($where['goods_id'],$where['sku_idx']);
                    $updateData['transaction_id']=$transaction_id;
                    $updateData['pay_time']=$pay_time;
                    $updateData['status']=1;
                    $updateData['payment']=$payment;
                    $updateData['goods_data']=json_encode($orderData['goods_data']);

                    changeModelProperty($orderModel,'order');
                    $update=$orderModel->where($where)->save($updateData);

                    if($update!==false){
                        unset($updateData);
                        //更新用户数据（积分和总消费额、未支付订单数）,如果退货，再重新扣除修正
                        changeModelProperty($orderModel,'users');
                        $updateData['score']=array('exp','score+'.$total_fee);
                        $updateData['points']=array('exp','points+'.$points);
                        $updateData['nopay_order_num']=array('exp','nopay_order_num-1');
                        $update=$orderModel->where(array('id'=>$where['shopper_id']))->save($updateData);
                        if($update!==false){
                            $orderModel->commit();
                            session('user.balance',$this->user['balance']-$total_fee);
                            $this->success('支付成功！',U('user/order/index'));
                        }
                    }
                    $orderModel->rollback();
                    //dump($orderModel->getLastSql());
                }
            }
        //商城充值成功后的处理
        }else if($orderType==1){
            $orderModel=M('service_order');
            $orderData=$orderModel->where($where)->field('total_price,status')->find();
            //验证数据是否需要处理
            if($orderData!==false && $orderData['status']==0 & $total_price==$total_fee){
                $updateData['status']=1;
                $updateData['payment']=$payment;
                $updateData['pay_time']=$pay_time;
                $updateData['transaction_id']=$transaction_id;
                $update=$orderModel->where($where)->save($updateData);
                if($updateData!==false){
                    $this->success('商城充值成功！');
                }
                //更新订单状态失败时自行添加处理方法
            }
        }
        $this->error('支付已经成功，但订单数据校验更新出错，请联系客服！');
    }
    

}
