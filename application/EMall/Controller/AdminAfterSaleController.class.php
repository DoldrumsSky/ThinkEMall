<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城售后管理控制器
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
 * $Id: AdminAfterSaleController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminAfterSaleController extends AdminbaseController{
    protected $afterSaleModel;

	function _initialize() {
		parent::_initialize();
		$this->afterSaleModel=M('after_sale');
	}
	
	// 后台商品列表
    public function index(){
  		$this->_lists();
		$this->display();
	}
	



	/**
	 * 订单列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 * @param bool $isRecycle 是否查询的是回收站的订单
	 */
	private function _lists($where=array(),$isRecycle){

		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['apply_time']=array(
		        array('EGT',$start_time)
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['apply_time'])){
		        $where['apply_time']=array();
		    }
		    array_push($where['apply_time'], array('ELT',$end_time));
		}
		
		$keyword=trim(I('request.keyword'));
		if(!empty($keyword)){
			if(preg_match("/^\d{16}$/",$keyword)){
            	$where['order_serial']=$keyword;
        	//商品关键词查询
        	}
		}
		
		$status=I('request.status',0,'intval');
		
		if($isRecycle){
			//查询回收站订单
			$where['status']=-1;
		}else if(!empty($status)){
			//10000为查询所有状态订单
			if($status<10000){
				if($status<5){
					$where['status']=$status;		
				}				
			}else{
				$where['status']=array('egt',0);
			}
		}else if($status===0){
			$where['status']=0;
		}else{
			$where['status']=array('gt',0);
		}

		$this->afterSaleModel
		->where($where);
		
		$count=$this->afterSaleModel->count();
			
		$page = $this->page($count, 20);
			
		$afterSaleData=$this->afterSaleModel
		->where($where)
		->limit($page->firstRow , $page->listRows)
		->order("apply_time DESC")
		->select();		

		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("afterSaleData",$afterSaleData);
	}

	//提交售后处理数据
	public function disposeAfterSale(){
		if(IS_POST){
			//售后申请单id号
			$as_id=I('post.as_id',0,'intval');
			//售后服务类型
			$serviceType=I('post.serviceType',0,'intval');
			//售后处理的详情说明
			$serviceDetail=I('post.serviceDetail');
			//售后服务价格
			$servicePrice=I('post.servicePrice',0,'floatval');
			//当前售后服务状态进度值
			$curStatus=I('post.curStatus',0,'intval');
			//是否需要寄回商品的标识
			$returnGoods=I('post.returnGoods',0,'intval');
			//寄回返修商品后的检测信息
			$checkGoodsInfo=I('post.checkGoodsInfo');
			//技术支持信息
			$tech_support=I('post.tech_support');
			//寄回返修换货商品的运单号
			$waybill_no=I('post.waybill_no');

			//chromephp::log($serviceType."|".$as_id."|".$serviceDetail);
			if(!empty($as_id) && !empty($serviceType) && strlen($serviceDetail)<500){
					$admin=session('user');
					$updateData['service_type']=$serviceType;
					//确认接收寄回的换修商品后（状态码为3）执行检修换货状态更新
					if($curStatus==3){
						if($serviceType>1){						
							//更新检测换修商品结果详情
							if(!empty($checkGoodsInfo) && strlen($checkGoodsInfo<500)){
									//更新为已完成换修商品检测状态
									if($serviceType==2){
										//如果服务价格免费则直接跳到状态3，等待换修完成寄回商品
										if($servicePrice>0){
											$updateData['repaire_step']=2;
										}else{
											$updateData['repaire_step']=3;
										}
										$updateData['exchange_step']=0;
									//换货
									}else if($serviceType==3){
										//如果服务价格免费则直接跳到状态3，等待换修完成寄回商品
										if($servicePrice>0){
											$updateData['exchange_step']=2;
										}else{
											$updateData['exchange_step']=3;
										}
										$updateData['repaire_step']=0;
									}
							//检测换修商品结果详情填空时还原回未检测换修商品状态
							}else if(empty($checkGoodsInfo)){
								if($returnGoods==1){
									//返修
									if($serviceType==2){
										$updateData['repaire_step']=1;
										$updateData['exchange_step']=0;
										$updateData['status']=array('exp','if(service_type=1,2,status)');
									//换货
									}else if($serviceType==3 && !empty($waybill_no)){
										$updateData['exchange_step']=1;
										$updateData['repaire_step']=0;
										$updateData['status']=array('exp','if(service_type=1,2,status)');
									//从不需要寄回换货商品时再切换到需要寄回换货商品时直接更新状态码回1，等待用户寄回换货商品
									}else if($serviceType==3 && empty($waybill_no)){
										$updateData['status']=1;
										$updateData['exchange_step']=0;								
									}
								}
							}
							$updateData['check_info']=$checkGoodsInfo;
						}

					}else if($curStatus>0 && $curStatus<4){
						//技术支持信息填写后的状态码更新
						if($serviceType==1){
							if(!empty($tech_support)){
							$updateData['tech_support']=$tech_support;
							$updateData['status']=3;
							$updateData['tech_support_time']=array('exp','CURRENT_TIMESTAMP');
							//如果清空技术支持内容，状态码将返回到1						
							}else if(empty($tech_support)){
								$updateData['status']=1;
							}
							$updateData['repaire_step']=0;
							$updateData['exchange_step']=0;
						//换货时接受更新“是否需要寄回换货商品”字段值，也就是允许不寄回换货商品直接换货
						}else if($serviceType==3){
							$updateData['return_goods']=$returnGoods;
							//不需要寄回换货商品时直接将售后状态码更新为3
							if($returnGoods==0){
								$updateData['status']=3;
								$updateData['exchange_step']=2;
							}
						}
					}

					$updateData['reply_detail']=$serviceDetail;
					$updateData['service_price']=$servicePrice;
					$updateData['admin_id']=$admin['id'];
					$updateData['admin_name']=$admin['user_nicename'];
					$updateData['reply_time']=array('exp','CURRENT_TIMESTAMP');
					//
					if(!empty($serviceDetail) && $curStatus<1){
						$updateData['status']=1;
					}

					//保存数据
					$result=$this->afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);
					if($result===false){
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存退货处理数据，请稍候尝试！'));
					}				

			}else{
				$this->ajaxReturn(array('status'=>0,'error'=>'提交数据缺失，非法请求！'));
			}
			
			$this->ajaxReturn(array('status'=>1,
									'data'=>array(
										'status'=>$updateData['status'],
										'exchange_step'=>$updateData['exchange_step'],
										'repaire_step'=>$updateData['repaire_step'],
										)
									));
		}
	}

	//确认接收换修商品
	public function confirmReceivedGoods(){
		if(IS_POST){
			$as_id=I('post.as_id',0,'intval');
			$serviceType=I('post.serviceType',0,'intval');

			if(!empty($as_id) && !empty($serviceType)){
				//根据服务类型更新服务状态进度数值
				if($serviceType==2){
					$updateData['repaire_step']=1;
				}else if($serviceType==3){
					$updateData['exchange_step']=1;
				}
				$updateData['status']=3;

				$result=$this->afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);
				if($result===false){
					$this->ajaxReturn(array('status'=>0,'error'=>'无法执行确认接收换修商品操作，请稍候尝试！'));
				}

				$this->ajaxReturn(array('status'=>1));
			}
		}
	}

	//提交完成换修商品的寄回运单号
	public function postReturnWayBill(){
		if(IS_POST){
			$as_id=I('post.as_id',0,'intval');
			$return_waybill_no=I('post.return_waybill_no');
			$serviceType=I('post.serviceType',0,'intval');

			if(!empty($as_id) && !empty($serviceType) && strlen($return_waybill_no)<30){
				$updateData['return_waybill_no']=$return_waybill_no;
				$updateData['return_waybill_time']=array('exp','CURRENT_TIMESTAMP');

				if(!empty($return_waybill_no)){
					if($serviceType==2){
						$updateData['repaire_step']=4;
					}else if($serviceType==3){
						$updateData['exchange_step']=4;
					}
				//如果将运单号重新设置为空，则状态进度回退到换修处理中，状态码3
				}else{
					if($serviceType==2){
						$updateData['repaire_step']=3;
					}else if($serviceType==3){
						$updateData['exchange_step']=3;
					}					
				}

				$result=$this->afterSaleModel->where(array('as_id'=>$as_id))->save($updateData);
				if($result===false){
					$this->ajaxReturn(array('status'=>0,'error'=>'无法保存提交的寄回运单号，请稍候尝试！'));
				}else{
					$this->ajaxReturn(array('status'=>1));
				}
			}
		}
	}

}