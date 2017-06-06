<?php
namespace EMall\Controller;

use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;
/**
 * ThinkEMall电子商城商品属性管理控制器
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
 * $Id: AdminGoodsPropertyController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminGoodsPropertyController extends AdminbaseController{
    
	protected $property_model;
	
	function _initialize() {
		parent::_initialize();
		$this->property_model = M("goods_type");
	}
	
	// 后台商品属性列表
    public function index(){
		$result = $this->property_model->order(array("cat_id"=>"asc"))->select();
		//dump($result);
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="javascript:addPropertyCategory(' . $r['cat_id'] . ',\'' .$r['cat_name'] . '\')">'.L('ADD_PROPERTY_CATEGORY').'</a> | <a href="javascript:editPropertyCategory(' . $r['cat_id'] . ',\'' .$r['cat_name'] . '\')">'.L('EDIT_PROPERTY_CATEGORY').'</a> | <a href="' . U("AdminGoodsProperty/listProperty", array("id" => $r['cat_id'],"pname"=>$r['cat_name'])) . '">'.L('EMALL_ADMINGOODS_LIST_PARAM').'</a> | <a href="' . U("AdminGoodsProperty/edit", array("id" => $r['cat_id'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("AdminGoodsProperty/delete", array("id" => $r['cat_id'])) . '">'.L('DELETE').'</a> ';
			$r['editURL']=U("AdminGoodsProperty/listProperty", array("id" => $r['cat_id'],"pname"=>$r['cat_name']));
			$array[] = $r;
		}
		
		$this->assign("taxonomys", $array);
		$this->display();
	}

	//只获取属性列表的基本信息,以ajax调用
	public function getPropertyInfo(){
		$result = $this->property_model->where(array('enabled'=>array('EQ',1)))->Field('cat_id,cat_name,enabled')->order(array("cat_id"=>"asc"))->select();	
		$this->ajaxReturn(array('status'=>1,'data'=>$result));
	}

	//商品属性参数列表
	public function listProperty(){
		$id=I('get.id');
		$pname=I('get.pname');
		$oProperty=$this->property_model->where(array('cat_id'=>$id))->getField('property');
		if(!empty($oProperty)){
			$oProperty=json_decode($oProperty,true);

			foreach ($oProperty as $key => $r) {
				$propertyCategory[]=$key;
				foreach ($r as $idx => $value) {
					$propertyList[$key][$idx]['strManage']='<a href="' . U("AdminGoodsProperty/editProperty", array("cat_id"=>$id,"pname"=>$pname,'category'=>$key,'paramname'=>$value['paramname'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("AdminGoodsProperty/deleteProperty", array("cat_id"=>$id,'category'=>$key,'paramname'=>$value['paramname'])) . '">'.L('DELETE').'</a> ';
					//获取参数配置对应的文本
					$formStr=$this->getFormStr(2,$value['formtype'],$value['searchtype']);
					$propertyList[$key][$idx]['paramname']=$value['paramname'];
					$propertyList[$key][$idx]['formtype']=$formStr['formStr'];
					$propertyList[$key][$idx]['searchtype']=$formStr['searchStr'];
					$array[$key][$idx] = $propertyList[$key][$idx];
				}
			}
		}
		
		F('EMall_Property_Category_'.$id,$propertyCategory);
		//dump($array);
		$this->assign(array('cat_id'=>$id,'pname'=>$pname));
		$this->assign('propertyList',$array);
	
		//dump($this->fetch());
		$this->display();
	}

	//只获取属性参数项的基本信息,以ajax调用
	public function getPropertyParamInfo(){
		$cid=I('get.cat_id');
		$result = $this->property_model->where(array('cat_id'=>$cid))->getField('property');	
		if($result!==''){
			$this->ajaxReturn(array('status'=>1,'data'=>$result));
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'未获取到任何数据！'));
		}
	}

	public function addPropertyCategory(){
		$id=I('get.id',0,'intval');
		$pname=I('get.pname');
		$category=I('get.category');
		$this->assign(array('id'=>$id,'pname'=>$pname,'category'=>$category));
		$this->display();	
	}

	public function postPropertyCategory(){
		if(IS_POST){
			$catId=I('post.catId',0,'intval');
			$pname=I('post.pname');
			$category_name=I('post.category_name');

			if(!empty($category_name)){
				$propertyData=$this->property_model->where(array('cat_id'=>$catId))->field('property')->find();
				if(!empty($propertyData)){
					$propertyData['property']=json_decode($propertyData['property'],true);
					$repeat=false;
					foreach ($propertyData as $key => $value) {
						if($key==$category_name){
							$repeat=true;
						}
					}

					if(!$repeat){
						$propertyData['property'][$category_name]=[];
						$propertyData['property']=json_encode($propertyData['property']);
					}
				}

				if($this->property_model->where(array('cat_id'=>$catId))->save($propertyData)===false){
					$this->ajaxReturn(array('status'=>0,'error'=>'无法保存提交的商品属性分类，请稍候尝试！'));
				}
				$this->clearFCache($catId);
				$this->ajaxReturn(array('status'=>1));
			}
		}
	}

	public function editPropertyCategory(){
		$catId=I('get.catId',0,'intval');
		$pname=I('get.pname');

		$propertyData=$this->property_model->where(array('cat_id'=>$catId))->field('property')->find();
		if(!empty($propertyData)){
			$propertyData['property']=json_decode($propertyData['property'],true);
			foreach ($propertyData['property'] as $key => $value) {
				$category[]=$key;
			}
			$this->ajaxReturn(array('status'=>1,'data'=>$category));
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'无法获取商品属性分类数据，请稍候尝试！'));
		}
	}

	//提交编辑商品属性分类
	public function postEditPropertyCategory(){
		if(IS_POST){
			$catId=I('post.catId',0,'intval');
			$pname=I('post.pname');
			$categoryData=$_POST['categoryData'];
			$categoryData=json_decode($categoryData,true);

			if(!empty($categoryData)){
				$propertyData=$this->property_model->where(array('cat_id'=>$catId))->field('property')->find();
				if(!empty($propertyData)){
					$propertyData['property']=json_decode($propertyData['property'],true);

					foreach ($propertyData['property'] as $key => $value) {
						if(!empty($categoryData[$key]) && $categoryData[$key]!==$key){
							$propertyData['property'][$categoryData[$key]]=$value;
							unset($propertyData['property'][$key]);
						}
					}

					$propertyData['property']=json_encode($propertyData['property']);

					if($this->property_model->where(array('cat_id'=>$catId))->save($propertyData)===false){
						$this->ajaxReturn(array('status'=>0,'error'=>'无法保存提交的商品属性分类，请稍候尝试！'));
					}
					$this->clearFCache($catId);
					$this->ajaxReturn(array('status'=>1));
				}
			}
		}
	}
	
	// 商品属性添加
	public function add(){
	 	
	 	$this->display();
	}
	
	// 商品属性添加提交
	public function add_post(){
		if (IS_POST) {
			if ($this->property_model->create()!==false) {
				if ($this->property_model->add()!==false) {
				    F('EMall/GoodsPropertyOption',null);
					$this->success("添加成功！",U("AdminGoodsProperty/index"));
				} else {
					$this->error("添加失败！");
				}
			} else {
				$this->error($this->property_model->getError());
			}
		}
	}

	//添加商品属性参数
	public function addProperty(){
		$id=I("get.cat_id",0,'intval');
		$pname=I("get.pname");

		$category=F('EMall_Property_Category_'.$id);
		if(empty($category)){
			$oProperty=$this->property_model->where(array('cat_id'=>$id))->getField('property');
			if(!empty($oProperty)){
				$oProperty=json_decode($oProperty,true);
				foreach ($oProperty as $key => $value) {
					$category[]=$key;
				}
				F('EMall_Property_Category_'.$id,$category);				
			}
		}
		//dump($category);
		$this->assign(array('cat_id'=>$id,'cat_name'=>$pname,'category'=>$category));
		$this->display();
	}

	//处理添加的参数数据并保存
	public function addPropertyPost(){
		if(IS_POST){
			$goodsProperty=I("post.post");
			$pname=I('post.extra');
			$propertyStr=I("post.property");
			//处理下拉列表类型参数的选项
			$propertyStr['selectParam']=$this->getSelectParamStr($propertyStr['selectParam']);
			//取出原来的数据
			$oProperty=$this->property_model->where(array('cat_id'=>$goodsProperty['cat_id']))->getField('property');

			if(empty($oProperty)){
				$goodsProperty['property']=json_encode(array($propertyStr));
			}else{
				$oProperty=json_decode($oProperty,true);
				//添加数据到更新的数组
				$oProperty[$propertyStr['category_name']][]=$propertyStr;
				//重新合成参数json字串
				$goodsProperty['property']=json_encode($oProperty);
			}
			//dump($oProperty);
			//$this->ajaxReturn(array('status'=>'1','list'=>$goodsProperty));
			//$goodsProperty['property']=json_encode($_POST['property']);
			if ($this->property_model->create()!==false) {
				//转为json文本存储				
				if ($this->property_model->save($goodsProperty)!==false) {
				    //F('all_terms',null);
					//$this->success("添加成功！",U("AdminGoodsProperty/index"));					
				} else {
					$this->ajaxReturn(array('status'=>'0','error'=>'数据库出错！'));
					//$this->error("添加失败！");
				}
			} else {
				$this->error($this->property_model->getError());
			}
			$this->clearFCache($goodsProperty['cat_id']);
			
			$returnData=array('returnURL'=>U('AdminGoodsProperty/editProperty',array('cat_id'=>$goodsProperty['cat_id'],'pname'=>$pname['pname'])));
			$this->ajaxReturn(array('status'=>'1','data'=>$returnData));		
		}
		//$this->ajaxReturn(array('status'=>'0'));
	}
	
	// 商品属性编辑
	public function edit(){
		$id = I("get.id",0,'intval');
		$data=$this->property_model->where(array("cat_id" => $id))->field('property')->find();	

		$this->assign("propertyData",$data);
		$this->display();
	}
	
	// 商品属性编辑提交
	public function edit_post(){
		if (IS_POST) {
			if ($this->property_model->create()!==false) {
				if ($this->property_model->save()!==false) {
				    F('EMall/GoodsPropertyOption',null);
					$this->success("修改成功！");
				} else {
					$this->error("修改失败！");
				}
			} else {
				$this->error($this->property_model->getError());
			}
		}
	}

		// 商品属性参数编辑
	public function editProperty(){
		$cat_id = I("get.cat_id",0,'intval');
		$pname=I('get.pname');
		$category=I('get.category');
		$paramname=I('get.paramname');
		$oProperty=$this->property_model->where(array("cat_id" => $cat_id))->getField('property');

		$this->assign(array('cat_id'=>$cat_id,'pname'=>$pname,'category'=>$category,'paramname'=>$paramname,'propertyData'=>$oProperty));
		$this->display();
	}

	public function editPropertyPost(){
		$sourceData=I('post.post');
		$editProperty=I('post.property');
		$oProperty=$this->property_model->where(array('cat_id'=>$sourceData['cat_id']))->getField('property');
		$oProperty=json_decode($oProperty,true);

		foreach ($oProperty[$sourceData['category_name']] as $key => $value) {

			if($value['paramname']==$sourceData['paramname']){
				//判断是否更换了商品属性分类
				$categoryChange=false;
					

				if($sourceData['category_name']==$editProperty['category_name']){
					$editCategoryKey=$sourceData['category_name'];
					$oProperty[$editCategoryKey][$key]['paramname']=$editProperty['paramname'];
					$oProperty[$editCategoryKey][$key]['formtype']=$editProperty['formtype'];
					//处理下拉列表类型参数数据
					if($editProperty['formtype']==2){
						$oProperty[$editCategoryKey][$key]['selectParam']=$this->getSelectParamStr($editProperty['selectParam']);
					}else{
						$oProperty[$editCategoryKey][$key]['selectParam']=[];
					}

					$oProperty[$editCategoryKey][$key]['description']=$editProperty['description'];
				}else{
					$editCategoryKey=$editProperty['category_name'];
					$moveData['paramname']=$editProperty['paramname'];
					$moveData['formtype']=$editProperty['formtype'];
					//处理下拉列表类型参数数据
					if($editProperty['formtype']==2){
						$moveData['selectParam']=$this->getSelectParamStr($editProperty['selectParam']);
					}else{
						$moveData['selectParam']='';
					}

					$moveData['description']=$editProperty['description'];
					$oProperty[$editCategoryKey][]=$moveData;
					$categoryChange=true;
				}

				//$array=$oProperty[$editCategoryKey][$editParamKey]['paramname'];	

				//如果更换了商品属性分类则在插入数据到选择分类后删除原分类中对应的数据
				if($categoryChange==true){
					unset($oProperty[$sourceData['category_name']][$key]);
				}
				break;
			}

		}	

		$oProperty=json_encode($oProperty);
		//dump($oProperty);
		if($this->property_model->where(array('cat_id'=>$sourceData['cat_id']))->setField('property',$oProperty)===false){
			$this->ajaxReturn(array('status'=>'0','error'=>'无法保存提交的商品属性参数，请稍候尝试！'));
		}
		$this->clearFCache($sourceData['cat_id']);

		$returnData['returnURL']=U('AdminGoodsProperty/editProperty',array('cat_id'=>$sourceData['cat_id'],'pname'=>$sourceData['pname'],'category'=>$editProperty['category_name'],'paramname'=>$editProperty['paramname']));
		$this->ajaxReturn(array('status'=>'1','data'=>$returnData));
	}
	
	/*/ 属性排序
	public function listorders() {
		$status = parent::_listorders($this->terms_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}*/
	
	// 删除商品属性
	public function delete() {
		$id = I("get.id",0,'intval');
				
		if ($this->property_model->delete($id)!==false) {
			F('EMall/GoodsPropertyOption',null);
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	// 删除商品属性参数
	public function deleteProperty() {
		$cat_id=I('get.cat_id',0,'intval');
		$paramname = I("get.paramname");
		$category = I('get.category');

		$oProperty=$this->property_model->where(array('cat_id'=>$cat_id))->getField('property');

		if(!empty($oProperty) && !empty($category)){
			
			$oProperty=json_decode($oProperty,true);

			foreach ($oProperty[$category] as $key => $value) {
				if($paramname==$value['paramname']){
					array_splice($oProperty[$category],$key,1);
					break;
				}
			}

			$oProperty=json_encode($oProperty);
			if ($this->property_model->where(array('cat_id'=>$cat_id))->setField('property',$oProperty)!==false) {
				$this->clearFCache($cat_id);
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}

	}

	//表单字串生成
	public function getFormStr($strType=1,$formID=1,$searchType=1,$searchOpt=true){
		$str=[];
		if($strType==1){
			switch ($formID) {
				case 1:
					$str['formStr']= '';
					break;
				
				default:

			}

		}else if($strType==2){
			switch ($formID) {
				case 1:
					$str['formStr']= '文本框';
					break;
				
				case 2:
					$str['formStr']= '下拉列表框';
					break;
				case 3:
					$str['formStr']= '单选框';
					break;
				case 4:

					break;
				default:

			}
		}

		if($searchOpt==true){
			switch ($searchType) {
				case 1:
					$str['searchStr']='精确搜索';
					break;
				case 2:
					$str['searchStr']='模糊搜索';
					break;
				case 0:
					$str['searchStr']='禁止搜索';
					break;

				default:
					# code...
					break;
			}
		}
		//生成搜索方式字串
		return $str;
	}

	//生成下拉列表选项参数字串
	public function getSelectParamStr($selectParam, $type='add'){
		//判断是添加还是编辑
		if($type=='add'){
			if($selectParam!==''){
				return $arr=explode("\n",str_replace("\r",'',$selectParam));
			}			
		}else{
			foreach ($selectParam as $value) {
				if($paramStr==''){
					$paramStr.=$value;					
				}else{
					$paramStr.="\n".$value;	
				}

			}
			//dump($paramStr);
			return $paramStr;
		}

		return '';
	}

	//清除商品属性相关缓存
	private function clearFCache($cat_id){
		F('EMall/GoodsPropertyOption',null);
		F('EMall_Term_Property_'.$cat_id,null);
	}
	
}