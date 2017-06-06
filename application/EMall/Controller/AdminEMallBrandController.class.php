<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use vendor\PHPCharacter;
/**
 * ThinkEMall电子商城品牌管理控制器
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
 * $Id: AdminEMallBrandController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminEMallBrandController extends AdminbaseController{
	protected $brandModel;	

	function _initialize(){
		parent::_initialize();
		$this->brandModel = M("brand");
	}


	// 品牌列表
    public function index(){
		$keyword=I('request.keyword');		
    	$letter=I('post.letter');

		if(!empty($keyword)){
			$where['brand_name'] = array('like',"%$keyword%");
		}else{
			if(!empty($letter)){
				$where['chartext']=$letter;
			}
		}

 		$pagecount=$this->brandModel->where($where)->count();
 		$page=$this->page($pagecount,20);
 		$result=$this->brandModel->where($where)->limit($page->firstRow,$page->listRows)->select();
 		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminEMallBrand/edit", array("id" => $r['brand_id'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("AdminEMallBrand/delete", array("id" => $r['brand_id'])) . '">'.L('DELETE').'</a> ';
			$r['editURL']=U("AdminEMallBrand/edit", array("id" => $r['brand_id'])) ;
			$brand[] = $r;
		}
 		$this->assign("page",$page->show("Admin"));
		$this->assign("brand", $brand);
		$this->display();
	}
	
	// 添加品牌
	public function add(){
	 	$this->display();
	}
	
	// 品牌添加提交
	public function add_post(){
		if (IS_POST) {
			$isAjaxReturn=I('get.isAjaxReturn');	
			
			$brand=I('post.');
			$brand['brand_logo']=$brand['UpFilePathInfo'];	


			if(empty($brand['brand_name'])){
				if($isAjaxReturn==false){
					$this->error('请填写品牌名称！');
				}else{
					$this->ajaxReturn(array('status'=>0,'data'=>'请填写品牌名称！'));
				}
			}
			if(empty($brand['brand_logo'])){
				if($isAjaxReturn==false){
					$this->error('请添加品牌LOGO图片');
				}else{
					$this->ajaxReturn(array('status'=>0,'data'=>'请添加品牌LOGO图片'));
				}
			}

			//处理品牌名首字符
            vendor('PHPCharacter.PHPCharacter');
            $Character=new \PHPCharacter();
            $chartext=$Character->getFirstChar($brand['brand_name']);
            $brand['chartext']=$chartext;           


				$brandId=$this->brandModel->add($brand);
				if ($brandId) {
					if($isAjaxReturn==false){
						$this->success("添加成功！",U("AdminEMallBrand/index"));
					}else{						
						 $optionCode='<option value="'.$brandId.'" selected="selected">'.$chartext.'&nbsp;&nbsp;'.$brand['brand_name'].'</option>';
						 //同步更新商品类目的brand_id值
						 $terms_model=M('mall_terms');
						 $result=$terms_model->where(array('term_id'=>$brand['brandTermId']))->save(array('brand_id'=>array('exp','CONCAT_WS(\',\',brand_id,\''.$brandId.'\')')));

						$this->ajaxReturn(array('status'=>1,'data'=>$optionCode));
					}
				} else {
					if($isAjaxReturn==false){
						$this->error("添加失败！");
					}else{
						$this->ajaxReturn(array('status'=>0,'data'=>'添加失败！'));
					}
				}

		}
	}
	
	// 编辑品牌
	public function edit(){
		$id = I("get.id",0,'intval');
		$brand=$this->brandModel->where(array('brand_id'=>$id))->find();
		$this->assign("brand",$brand);
		$this->display();
	}
	
	// 提交品牌编辑
	public function edit_post(){
		if (IS_POST) {
			$brand=I('post.');
			//处理品牌名首字符
            vendor('PHPCharacter.PHPCharacter');
            $Character=new \PHPCharacter();
            $chartext=$Character->getFirstChar($brand['brand_name']);
            $brand['chartext']=$chartext;
            F('EMall_Brand',$chartext);

			$brand['brand_logo']=$brand['UpFilePathInfo'];
			unset($brand['UpFilePathInfo']);
			if ($this->brandModel->create()!==false) {
				if ($this->brandModel->save($brand)!==false) {
					$this->success("修改成功！");
				} else {
					$this->error("修改失败！");
				}
			} else {
				$this->error($this->brandModel->getError());
			}
		}
	}
	
	// 品牌排序
	public function listorders() {
		$status = parent::_listorders($this->brandModel);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	// 删除品牌
	public function delete() {
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->brandModel->where(array('brand_id'=>$id))->delete()!==false) {
				F('EMall_Brand',null);
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->brandModel->where(array('brand_id'=>array('in',$ids)))->delete()!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}

	}

	//获取商品类目下所有品牌数据(用于管理商品类目品牌时直接传其包含的品牌ID组进行查询)
	public function getTermBrand(){
		$brand_id=I('get.brand_id/s');

		$where['brand_id']=array('in',$brand_id);
		$where['display']=1;
		$result=$this->brandModel->field('brand_id,brand_name,brand_logo')->where($where)->select();
		if($result===false){
			$this->ajaxReturn(array('status'=>0,'error'=>'无法获取品牌数据，请稍候尝试：'.$this->brandModel.getError()));
		}
		$this->ajaxReturn(array('status'=>1,'data'=>$result));

	}

	//搜索品牌，Ajax返回（主要用于搜索添加商品类目对应的品牌）
	public function searchBrand(){
		$keywords=I('post.keywords');
		$chartext=I('post.chartext');
		$curSearchPageNum=I('post.curSearchPageNum',0,'intval');

		if(!empty($keywords)){
			$where['match(brand_keywords)']=array('exp','against(\''.$keywords.'*\' IN BOOLEAN MODE)');
			$where['_logic']='or';
			$where['brand_name']=array('like','%'.$keywords.'%');
		}else if(!empty($chartext)){
			$where['chartext']=$chartext;
		}
		$map['_complex'] = $where;

		if(!empty($where)){
			$map['display']=1;
			$pageSize=20;
			$total=$this->brandModel->where($map)->count();
			$pageCount=($total+$pageSize-1)/$pageSize;
			if(empty($curSearchPageNum)){
				$curSearchPageNum=1;
			}else{
				$curPage=$pageSize*($curSearchPageNum-1);
			}
			$page['total']=$total;
			$page['pageCount']=$pageCount;

			$result=$this->brandModel->where($map)->field('brand_id,brand_logo,brand_name')->limit($curPage,$curPage+$pageSize)->select();
			if($result!==false){
				$this->ajaxReturn(array('status'=>1,'data'=>$result,'page'=>$page));
			}
		}
		$this->ajaxReturn(array('status'=>0,'error'=>'无法获取品牌数据，请稍候尝试：'.$this->brandModel->getError()));
	}

	//提交添加的商品类目品牌
	public function postBrandToTerm(){
		if(IS_POST){
			$brand_id=I('post.brand_id/s');
			$term_id=I('post.term_id',0,'intval');

			if(!empty($term_id)){
				$where['term_id']=$term_id;
				$updateData['brand_id']=$brand_id;

				$terms_model=M('mall_terms');
				if($terms_model->where($where)->save($updateData)===false){
					$this->ajaxReturn(array('status'=>0,'error'=>'无法保存提交的品牌数据，请稍候尝试：'.$terms_model->getError()));
				}
				F('EMall_Brand_Filter_'.$term_id,null);
				$this->ajaxReturn(array('status'=>1));
			}
		}
	}

	public function test(){
		$listorders=$_POST['listorders[]'];

		$this->success("删除成功！");
				dump($listorders);
	}
	
}