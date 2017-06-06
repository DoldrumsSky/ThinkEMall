<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城订单管理控制器
 * ============================================================================
 * 引用、修改及衍生本系统代码请保留以下信息
 * 版权所有 2016-2020 作者：阳华 ThinkEMall，并保留所有权利。 * 
 * ----------------------------------------------------------------------------
 * 项目地址：https://github.com/DoldrumsSky/ThinkEMall * 
 * 联系方式：
 * QQ:451343282	 Email:451343282@qq.com
 * 技术交流群:1950562
 * ============================================================================
 * $Author: YangHua $
 * $Id: AdminOrderController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminOrderController extends AdminbaseController{
    protected $orderModel;

	function _initialize() {
		parent::_initialize();
		$this->orderModel=M('order');
	}
	
	// 后台商品列表
    public function index(){
  		$this->_lists();
		$this->display();
	}
	
	// 设置商品出库
	public function postDelivery(){
		if (IS_POST) {
			$order_gid=I('post.order_id',0,'intval');
			$gidGroup=I('post.gidGroup');
			$waybill_no=I('post.waybill_no');
			$prepare_gid=I('post.prepare_gid');
			$shipAll=I('post.shipAll',0,intval);
			$prepareAll=I('post.prepareAll',0,intval);

			if(!empty($order_gid) && !empty($waybill_no)){
				$where['order_gid']=$order_gid;	

				$gidGroup=explode(',',$gidGroup);


				//更新order表中对应的数据映射
				$this->orderModel->startTrans();
				$result=$this->orderModel->field('goods_data')->where($where)->find();
				if($result!==false){
					$result['goods_data']=json_decode($result['goods_data'],true);
					foreach ($gidGroup as $key => $value) {
						$result['goods_data'][$value]['status']=3;
						$result['goods_data'][$value]['waybill_no']=$waybill_no;
					}

					$result['goods_data']=json_encode($result['goods_data']);
					$update=$this->orderModel->where($where)->save($result);

					if($update===false){
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存设置出库的商品数据，请稍候尝试！'));
					}


					$updateData['waybill_no']=$waybill_no;
					$updateData['status']=3;
					$updateData['ship_time']=array('exp','CURRENT_TIMESTAMP');
					//更新订单单件商品的对应数据
					$this->changeModelProperty($this->orderModel,'order_relationships');
					//$where['order_id']=array('in',$order_id);
					foreach ($gidGroup as $key => $value) {
						$value=explode('-',$value);
						$where['goods_id']=$value[0];
						$where['sku_idx']=intval($value[1]);
						//直接更新所有选中订单商品的状态
						$result=$this->orderModel->where($where)->save($updateData);
						if($result===false){
							$this->orderModel->rollback();
							$this->ajaxReturn(array('status'=>0,'error'=>'无法保存设置出库的商品数据，请稍候尝试！'));
						}
					}

					$this->orderModel->commit();
					$this->ajaxReturn(array('status'=>1));
				}
				$this->ajaxReturn(array('status'=>0,'error'=>'无法保存设置出库的商品数据，请稍候尝试！'));
			}
		}
	}

	// 编辑保存运单号
	public function postEditSerial(){
		if (IS_POST) {
			$order_gid=I('post.order_id',0,'intval');
			$waybill_no=I('post.waybill_no');
			$oldSerial=I('post.oldSerial');

			if(!empty($order_gid) && !empty($waybill_no && !empty($oldSerial) && $waybill_no!==$oldSerial)){	
				$where['order_gid']=$order_gid;

				$this->orderModel->startTrans();
				$result=$this->orderModel->field('goods_data')->where($where)->find();
				if($result!==false){
					$result['goods_data']=json_decode($result['goods_data'],true);
					foreach ($result['goods_data'] as $key => $value) {
						if($value['waybill_no']==$oldSerial){
							$result['goods_data'][$key]['waybill_no']=$waybill_no;
						}
					}

					$result['goods_data']=json_encode($result['goods_data']);
					$update=$this->orderModel->where($where)->save($result);

					if($update===false){
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存修改的运单号数据，请稍候尝试！'));
					}
				}

				$where['waybill_no']=$waybill_no;
				//更新订单单件商品的对应数据
				$this->changeModelProperty($this->orderModel,'order_relationships');
				$updateData['waybill_no']=$waybill_no;
				$update=$this->orderModel->where($where)->save($updateData);
				if($update===false){
					$this->orderModel->rollback();
					$this->ajaxReturn(array('status'=>0,'error'=>'无法保存修改的运单号数据，请稍候尝试！'));
				}

				$this->orderModel->commit();
				$this->ajaxReturn(array('status'=>1));

			}
		}
	}
	
	// 删除定单(deleteType为1时将订单放入回收站，其它为正式删除，只有未支付的订单才能被正式删除)
	public function deleteOrder(){

		$order_id=I('get.order_id');
		$deleteType=I('get.deleteType',0,'intval');
		$errorState=0;

		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');				
		}

		if((!empty($order_id) || !empty($ids)) && !empty($deleteType)){
			$updateData['status']=-1;

			if($deleteType==1){
				//批量移入回收站
				if(!empty($ids)){
					if ($this->orderModel->where(array('order_gid'=>array('in',$ids),'status'=>0))->save($updateData)===false) {
						$errorState=1;
					}
				//单个移入回收站
				}else{
					if($this->orderModel->where(array('order_gid'=>$order_id,'status'=>0))->save($updateData)===false){
						$errorState=1;
					}
				}
			}else{
				//批量彻底删除
				if(!empty($ids)){
					if ($this->orderModel->where(array('order_gid'=>array('in',$ids),'status'=>array('elt',0)))->delete()==false) {
						$errorState=1;
					}
				//单个彻底删除
				}else{
					if($this->orderModel->where(array('order_gid'=>$order_id,'status'=>array('elt',0)))->delete()==false){
						$errorState=1;
					}
				}
			}
			$this->success('删除成功！');
		}	
		if($errorState>0){
			$this->error('删除定单数据出错，请稍候尝试！');
		}
		//$this->ajaxReturn(array('status'=>0,'error'=>'删除定单出错，请稍候尝试！'));
	
	}

	//恢复订单到未支付状态
	public function restoreOrder(){
		$order_id=I('get.order_id');
		$errorState=0;

		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');				
		}

		if(!empty($order_id) || !empty($ids)){
			$updateData['status']=0;
			//批量恢复
			if(!empty($ids)){
				if ($this->orderModel->where(array('order_id'=>array('in',$ids),'status'=>-1))->save($updateData)===false) {
					$errorState=1;
				}			
			//单个恢复	
			}else{
				if($this->orderModel->where(array('order_id'=>$order_id,'status'=>-1))->save($updateData)===false){
					$errorState=1;
				}				
			}
			$this->success('恢复成功！');
		}

		if($errorState>0){
			$this->error('恢复定单数据出错，请稍候尝试！');
		}
	}

	//查看回收站订单
	public function recycleOrder(){
		$this->_lists(array(),true);
		$this->display();
	}
	
		
	// 设置商品为配货完成状态(一种是设置未配货商品到配货状态，一种是设置出库商品到配货状态)
	public function postPrepare() {
		if(IS_POST){
			//未配货商品以及已配货的GID字串
			$gidGroup=I('post.gidGroup');
			$order_gid=I('post.order_id',0,'intval');
			//是否所有商品已经配货的状态标识
			$prepareAll=I('post.prepareAll',0,intval);
			
			if(!empty($gidGroup) && !empty($order_gid) && $order_gid>0){
				$updateData['waybill_no']='';
				$updateData['status']=2;
				$updateData['prepare_time']=array('exp','CURRENT_TIMESTAMP');
				$updateData['ship_time']=null;
				$where['order_gid']=$order_gid;	

				$gidGroup=explode(',',$gidGroup);

				//更新order表中对应的数据映射
				$this->orderModel->startTrans();
				$result=$this->orderModel->field('goods_data')->where($where)->find();
				if($result!==false){
					$result['goods_data']=json_decode($result['goods_data'],true);
					foreach ($gidGroup as $key => $value) {
						$result['goods_data'][$value]['status']=2;
						unset($result['goods_data'][$value]['waybill_no']);
					}

					$result['goods_data']=json_encode($result['goods_data']);
					$update=$this->orderModel->where($where)->save($result);

					if($update===false){
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存设置配货的商品数据，请稍候尝试！'));
					}
				}

				$this->changeModelProperty($this->orderModel,'order_relationships');
				//$where['order_id']=array('in',$order_id);
				foreach ($gidGroup as $key => $value) {
					$value=explode('-',$value);
					$where['goods_id']=$value[0];
					$where['sku_idx']=intval($value[1]);
					//直接更新所有选中订单商品的状态
					$result=$this->orderModel->where($where)->save($updateData);
					if($result===false){
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存设置配货的商品数据，请稍候尝试！'));
					}
				}

				$this->orderModel->commit();
				$this->ajaxReturn(array('status'=>1));

				
			}
		}
	}

	//批量设置订单商品配货状态
	public function setPrepareBatch(){
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');	
			$errorState=0;			
		}
		
		
		if(!empty($ids)){
			$where['status']=1;
			$where['order_gid']=array('in',$ids);
			$this->orderModel->startTrans();
			//修改订单映射数据
			$orderData=$this->orderModel->where($where)->field('order_gid,goods_data')->select();
			if(!empty($orderData)){
				foreach ($orderData as $key => $value) {
					$value['goods_data']=json_decode($value['goods_data'],true);
					foreach ($value['goods_data'] as $gid => $goods_data) {
						if($goods_data['status']==1){
							$value['goods_data'][$gid]['status']=2;
						}
					}
					$value['goods_data']=json_encode($value['goods_data']);
					$update=$this->orderModel->where(array('order_gid'=>$value['order_gid']))->save(array('goods_data'=>$value['goods_data']));
					if($update===false){
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'info'=>'设置商品配货状态出错，请稍候尝试！'));
					}
				}

				unset($orderData);

				//修改订单商品数据
				$updateData['status']=2;
				$updateData['prepare_time']=array('exp','CURRENT_TIMESTAMP');
				$this->changeModelProperty($this->orderModel,'order_relationships');
				$update=$this->orderModel->where($where)->save($updateData);
				if($update===false){					
					$this->orderModel->rollback();
					$this->ajaxReturn(array('status'=>0,'info'=>'设置商品配货状态出错，请稍候尝试！'));
				}
				$this->orderModel->commit();
			}
			
		}

		if($errorState>0){
			$this->error('无法设置订单状态，请稍候尝试！');
		}
	}
	
	/*/ 删除重置商品退货申请数据
	public function deleteRefund() {
		if(IS_POST){
			$order_id=I('post.order_id');
			$goods_id=I('post.gid');

			if(!empty($order_id) && !empty($goods_id)){
				$refundData=$this->orderModel->where(array('order_id'=>$order_id))->field('refund_info')->find();
				if(!empty($refundData)){
					$refundData['refund_info']=json_decode($refundData['refund_info'],true);
					unset($refundData['refund_info'][$goods_id]);

					$refundData['refund_info']=json_encode($refundData['refund_info']);
					$result=$this->orderModel->where(array('order_id'=>$order_id))->save($refundData);
					if($result===false){
						$this->ajaxReturn(array('status'=>0,'error'=>'无法删除重置当前退货数据，请稍候重试！'));
					}
				}else{
					$this->ajaxReturn(array('status'=>0,'error'=>'未查询到对应的退货数据，请稍候重试！'));
				}

				$this->ajaxReturn(array('status'=>1));
			}
		}
	}*/


	/**
	 * 订单列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 * @param bool $isRecycle 是否查询的是回收站的订单
	 */
	private function _lists($where=array(),$isRecycle){

		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['order_time']=array(
		        array('EGT',$start_time)
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['order_time'])){
		        $where['order_time']=array();
		    }
		    array_push($where['order_time'], array('ELT',$end_time));
		}
		
		$keyword=trim(I('request.keyword'));
		if(!empty($keyword)){
			if(preg_match("/^\d{16}$/",$keyword)){
            	$where['order_serial']=$keyword;
        	//商品关键词查询
        	}else{
            	$where['Match(goods_keywords)']=array('exp','Against (\''.$keyword.'\')');
       	 	}
		}
		
		$status=I('request.status','','intval');
		
		if($isRecycle){
			//查询回收站订单
			$where['status']=-1;
		}else if(!empty($status)){
			//10000为查询所有状态订单
			if($status<10000){
				//查询方式的标识，orderGoods是查询订单组中的商品，order是查询订单组信息
				$queryType='orderGoods';
				$field='A.*,B.consignee,B.telphone,B.address';
				$join='__ORDER_RELATIONSHIPS__ B ON A.order_gid = B.order_gid';

				if($status>0){
					$where['B.status']=$status<3?array(array('egt',1),array('lt',3)):$status;	
					$order='B.order_time DESC';		
				}
			}else{
				$queryType='order';
				$where['A.status']=array('egt',0);
				$order="A.order_time DESC";
			}
		}else if($status===0){
			$queryType='order';
			$where['A.status']=0;
		}else{
			$queryType='order';
			$where['A.status']=array('gt',0);
			$order="A.order_time DESC";
		}

		$this->orderModel
		->alias('A')
		->join($join)
		->where($where);
		
		$count=$this->orderModel->count();
			
		$page = $this->page($count, 20);
			
		$orderData=$this->orderModel
		->alias('A')
		->join($join)
		->where($where)
		->limit($page->firstRow , $page->listRows)
		->order($order)
		->select();

		//dump($this->orderModel->getLastSql());
		//dump($orderData);
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("orderData",$orderData);
		$this->assign('queryType',$queryType);
	}

	//提交退货处理数据（disposeType=0为删除重置，2为同意退货，-1为拒绝退货，4为确认收货，5为确认退款）
	public function disposeRefund(){
		if(IS_POST){
			$order_gid=I('post.order_id',0,'intval');
			$goods_id=I('post.gid');
			$disposeType=I('post.disposeType',0,'intval');
			$refundDetail=I('post.disposeDetail');
			$refundPrice=I('post.refundPrice');

			if(!empty($order_gid) && !empty($goods_id) && strlen($refundDetail)<200){
				$where['order_gid']=$order_gid;
				$refundData=$this->orderModel->where($where)->field('goods_data')->find();
				if($refundData!==false){
					$refundData['goods_data']=json_decode($refundData['goods_data'],true);
					//更新映射数据
					if(!empty($refundData['goods_data'][$goods_id])){
						if($disposeType==2 || $disposeType==-1){
							$refundData['goods_data'][$goods_id]['refund_info']['disposeDetail']=$refundDetail;
							$refundData['goods_data'][$goods_id]['refund_info']['disposeTime']=date('Y年m月d日 H:i');
						//拒绝退货时status为-1
						}else if($disposeType==4){
							$refundData['goods_data'][$goods_id]['refund_info']['receivedGoodsTime']=date('Y年m月d日 H:i');
						}else if($disposeType==5){
							$refundData['goods_data'][$goods_id]['refund_info']['refundPrice']=$refundPrice;
							$updateData['refund_price']=$refundPrice;
							$refundData['goods_data'][$goods_id]['refund_info']['refundPaymentTime']=date('Y年m月d日 H:i');
						}else if($disposeType==0){
							unset($refundData['goods_data'][$goods_id]['refund_info']);
						}

						if($disposeType<>0){
							$refundData['goods_data'][$goods_id]['refund_status']=$disposeType;
						}else{
							unset($refundData['goods_data'][$goods_id]['refund_status']);
						}

						//取refund_info数据准备直接更新到订单退货商品对应的字段中
						$refund_info=$refundData['goods_data'][$goods_id]['refund_info'];
						$refundData['goods_data']=json_encode($refundData['goods_data']);
						$this->orderModel->startTrans();
						//更新映射数据
						$result=$this->orderModel->where($where)->save($refundData);
						if($result!==false){
							//更新订单商品数据
							$goods_id=explode('-',$goods_id);
							$where['goods_id']=$goods_id[0];
							$where['sku_idx']=$goods_id[1];
							$updateData['refund_status']=$disposeType;
							$updateData['refund_info']=json_encode($refund_info);

							$this->changeModelProperty($this->orderModel,'order_relationships');
							$update=$this->orderModel->where($where)->save($updateData);
							if($update!==false){
								$this->orderModel->commit();
								$this->ajaxReturn(array('status'=>1,'data'=>$refundDetail));
							}
						}
						$this->orderModel->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存退货处理数据，请稍候尝试！'));
					}
				}

			}
			
			//$this->ajaxReturn(array('status'=>1,'data'=>$refundDetail));
		}
	}

	//确认返还退货款
	public function postRefundPrice(){
		if(IS_POST){
			$order_gid=I('post.order_id',0,'intval');
			$goods_id=I('post.gid');
			$deductPrice=I('post.deductPrice',0,'floatval');
			//程序执行状态码
			$errorState=0;

			if(!empty($order_gid) && !empty($goods_id)){
				$where['order_gid']=$order_gid;
				$refundData=$this->orderModel->where($where)->field('goods_data')->find();

				if($refundData!==false){
					$refundData['goods_data']=json_decode($refundData['goods_data'],true);					
					//计算实际退款金额
					if(!empty($refundData['goods_data'][$goods_id])){
						//验证金额，实际退款金额不能比原订单商品金额大
						$oldPrice=$refundData['goods_data'][$goods_id]['price'];
						if($oldPrice>=$oldPrice-$deductPrice){
							$refundPrice=$oldPrice-$deductPrice;
							$refundData['goods_data'][$goods_id]['refund_info']['refund_price']=$refundPrice;
							$refundData['goods_data'][$goods_id]['refund_info']['refundPriceTime']=date('Y年m月d日 H:i');
							$refundData['goods_data'][$goods_id]['refund_status']=5;
							$refundData['goods_data']=json_encode($refundData['goods_data']);
							//更新映射数据
							$this->orderModel->startTrans();
							$update=$this->orderModel->where($where)->save($refundData);
							if($update!==false){
								//更新订单商品数据
								$this->changeModelProperty($this->orderModel,'order_relationships');
								$goods_id=explode('-',$goods_id);
								$where['goods_id']=$goods_id[0];
								$where['sku_idx']=$goods_id[1];
								$updateData['refund_status']=5;
								$updateData['refund_price']=$refundPrice;
								$updateData['refund_finishtime']=array('exp','CURRENT_TIMESTAMP');
								$update=$this->orderModel->where($where)->save($updateData);
								if($update!==false){
									$this->orderModel->commit();
									//执行退款操作
									//……退款渠道代码自实现
									$this->ajaxReturn(array('status'=>1));
								}
							}
							$this->orderModel->rollback();
							$this->ajaxReturn(array('status'=>0,'data'=>$order_id,'error'=>'执行退款操作出错，请稍候尝试！'));
						}
					}
					
				}

			}
			
		}
	}

	public function editOrderPrice(){
		if(IS_POST){
			$order_id=I('post.order_id',0,'intval');
			$deductPrice=I('post.deductPrice',0,'floatval');
			$errorState=0;

			if(!empty($order_id)){
				$orderData=$this->orderModel->where(array('order_gid'=>$order_id))->field('total_price')->find();
				if(!empty($orderData)){
					$modifyPrice=$orderData['total_price']-$deductPrice;
					//验证修改金额
					if($orderData['total_price']>=$modifyPrice){
						$updateData['modify_price']=!empty($deductPrice)?$modifyPrice:NULL;
					}else{
						$errorState=1;
					}

					if($errorState==0){
						$result=$this->orderModel->where(array('order_gid'=>$order_id))->save($updateData);
						if($result===false){
							$errorState=1;
						}

						$this->ajaxReturn(array('status'=>1,'data'=>$updateData));
					}
				}
			}
			$this->ajaxReturn(array('status'=>0,'error'=>'无法修改订单金额，请稍候尝试！'));
		}
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

}