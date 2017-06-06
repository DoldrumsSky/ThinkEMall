<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use vendor\PHPCharacter;
/**
 * ThinkEMall电子商城运费模板管理控制器
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
 * $Id: AdminEMallLogisticsController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminEMallLogisticsController extends AdminbaseController{
	protected $logisticsModel;	

	function _initialize(){
		parent::_initialize();
		$this->logisticsModel = M("logistics");
	}


	// 后台文章分类列表
    public function index(){

 		$pagecount=$this->logisticsModel->count();
 		$page=$this->page($pagecount,20);
 		$result=$this->logisticsModel
		->join('__PROVINCE__ ON __LOGISTICS__.province_send = __PROVINCE__.id')
		->join('__CITY__ ON __LOGISTICS__.city_send = __CITY__.id')
 		->limit($page->firstRow,$page->listRows)->select();
 		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminEMallLogistics/edit", array("id" => $r['logistics_id'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("AdminEMallLogistics/delete", array("id" => $r['logistics_id'])) . '">'.L('DELETE').'</a> ';
			$r['editURL']=U("AdminEMallLogistics/edit", array("id" => $r['logistics_id'])) ;
			$r['typeStr']=$this->getLogisticsType($r['logistics_type']);
			$r['labelStr']=$this->getLogisticsLabel($r['logistics_label']);
			$logistics[] = $r;
		}
 		$this->assign("page",$page->show("Admin"));
		$this->assign("logistics", $logistics);
		$this->display();
	}
	
	// 添加运费模板
	public function add(){
	 	$this->display();
	}
	
	// 运费模板添加提交
	public function add_post(){
		if (IS_POST) {
			
			$logistics=I('post.post');
			$logistics['logistics_param']=$_POST['logistics_param'];
			//检测json数据是否符合标准
			$firstStr=substr( $logistics['logistics_param'], 0, 1 );
			$endStr=substr( $logistics['logistics_param'],strlen($logistics['logistics_param'])-1, 1 );
			if($firstStr!=='{' | $endStr!=='}'){
				$this->ajaxReturn(array('status'=>0,'data'=>'提交的运费模板配置数据不符合Json标准'));
			}
			//dump($logistics['logistics_param']);

			if(empty($logistics['tmpl_name'])){
				$this->ajaxReturn(array('status'=>0,'data'=>'请填写运费模板名称！'));
			}

			//$this->ajaxReturn(array('status'=>1,'logistics'=>$logistics));

			if ($this->logisticsModel->create()!==false) {
				if ($this->logisticsModel->add($logistics)!==false) {
					$this->ajaxReturn(array('status'=>1));
				} else {
					$this->ajaxReturn(array('status'=>0,'error'=>'添加数据存储错误！'));
				}
			} else {
				$this->ajaxReturn(array('status'=>0,'error'=>$this->logisticsModel->getError()));
			}
					
		}
	}
	
	// 运费模板编辑
	public function edit(){
		$id = I("get.id",0,'intval');
		$logistics=$this->logisticsModel->where(array('logistics_id'=>$id))->find();
		$this->assign("logistics",$logistics);
		$this->display();
	}
	
	// 运费模板编辑提交
	public function edit_post(){
		if (IS_POST) {
			$id = I("get.id",0,'intval');
			$logistics=I('post.post');
			$logistics['logistics_param']=$_POST['logistics_param'];
			$logistics['logistics_id']=$id;
			//检测json数据是否符合标准
			$firstStr=substr( $logistics['logistics_param'], 0, 1 );
			$endStr=substr( $logistics['logistics_param'],strlen($logistics['logistics_param'])-1, 1 );
			if($firstStr!=='{' | $endStr!=='}'){
				$this->ajaxReturn(array('status'=>0,'data'=>'提交的运费模板配置数据不符合Json标准'));
			}
			//dump($logistics['logistics_param']);

			if(empty($logistics['tmpl_name'])){
				$this->ajaxReturn(array('status'=>0,'data'=>'请填写运费模板名称！'));
			}
		
			if ($this->logisticsModel->create()!==false) {
				if ($this->logisticsModel->save($logistics)!==false) {
					$this->ajaxReturn(array('status'=>1));
				} else {
					$this->ajaxReturn(array('status'=>0,'error'=>'数据存储错误！'));
				}
			} else {
				$this->ajaxReturn(array('status'=>0,'error'=>$this->logisticsModel->getError()));
			}
		}
	}

	
	// 删除运费模板
	public function delete() {
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->logisticsModel->where(array('logistics_id'=>$id))->delete()!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->logisticsModel->where(array('logistics_id'=>array('in',$ids)))->delete()!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}

	}

	//搜索运费模板
	public function search(){
		$keyword=I('request.keyword');

		if(!empty($keyword)){
			$where['logistics_name'] = array('like',"%$keyword%");
			//dump($where);
			$logistics=$this->logisticsModel->where($where)->select();
			$this->assign('logistics',$logistics);
			$this->display('AdminEMallLogistics/index');
		}else{
			$this->error("请输入关键字！");
		}
	}

	//转换运费计算方式为文本
	public function getLogisticsType($tid){
		$typeStr='';
		switch ($tid) {
			case 0:
				$typeStr='按件数计费';
				break;
			case 1:
				$typeStr='按重量计费';
				break;		
			case 2:
				$typeStr='按体积计费';
				break;	
			default:
				$typeStr='';
				break;
		}

		return $typeStr;
	}
	
	//转换运费标签文本
	public function getLogisticsLabel($labelId){
		$labelStr='';
		switch ($labelId) {
			case 0:
				$labelStr='包邮';
				break;
			case 1:
				$labelStr='顺丰包邮';
				break;		
			case 2:
				$labelStr='EMS包邮';
				break;	
			default:
				$labelStr='';
				break;
		}

		return $labelStr;
	}
	//编辑商品时ajax获取模板数据
	public function getLogisticsTmpl(){
		//如果不需要根据用户获取，请自行更改
		//$userId=sp_get_current_admin_id();
		//$where['user_id']=$userId;
		$result=$this->logisticsModel->where($where)->field('logistics_id,tmpl_name')->select();
		if($result){
			$this->ajaxReturn(array('status'=>1,'data'=>$result));			
		}
		$this->ajaxReturn(array('status'=>0,'data'=>''));
	}

}