<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;

/**
 * ThinkEMall电子商城商品分类管理控制器
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
 * $Id: AdminEMallTermController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminEMallTermController extends AdminbaseController{
    
	protected $terms_model;
	protected $taxonomys=array("goods"=>"商品","article"=>"文章","picture"=>"图片");
	
	function _initialize() {
		parent::_initialize();
		$this->terms_model = D("EMall/MallTerms");
		$this->assign("taxonomys",$this->taxonomys);
	}
	
	// 后台商品类目列表（类目层级数以二级为准）
    public function index(){
    	$term_id=I('post.term_id');
    	$keywords=I('post.keyword');
		$termOption='';

    	if(!empty($keywords)){
    		$where['match(term_keywords)']=array('exp','against(\''.$keywords.'\' IN BOOLEAN MODE)');
    		$where['_logic'] = 'or';
    	}else{
    		if(IS_POST){
		    	if(!empty($term_id)){
		    		$where['term_id']=$term_id;
		    		$where['parent']=$term_id;
		    		$where['_logic'] = 'or';
		    	}
	    	}else{
    			$getTermId=I('get.term_id');
		    	if(!empty($getTermId)){
		    		$where['term_id']=$getTermId;
		    		$where['parent']=$getTermId;
		    		$where['_logic'] = 'or';
		    	}
	    	}
    	}

		$result = $this->terms_model->where($where)->order(array("listorder"=>"asc"))->select();

		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			//添加子类链接（展示商品的类目不允许添加子类目）
			$addTermHref=$r['term_type']==1 ? '' : '<a href="' . U("AdminEMallTerm/add", array("parent" => $r['term_id'])) . '">'.L('ADD_SUB_CATEGORY').'</a> | ';

			$editTermHrefParam=$r['term_type']==1 ? array("id" => $r['term_id'],'parent'=>$r['parent'],'property_id'=>$r['property_id']) : array("id" => $r['term_id']);

			$brandManageHref=$r['term_type']==1 ? '<a href="javascript:manageTermBrand('.$r['term_id'].',\''.$r['brand_id'].'\')">类目品牌</a> | ' : '';

			$r['str_manage'] = $addTermHref.'<a href="' . U("AdminEMallTerm/edit", $editTermHrefParam) . '">'.L('EDIT').'</a> | '
			.$brandManageHref
			.'<a class="js-ajax-delete" href="' . U("AdminEMallTerm/delete", array("id" => $r['term_id'])) . '">'.L('DELETE').'</a> ';

			$url=U('AdminEMallTerm/index',array('term_id'=>$r['term_id']));
			$r['url'] = $url;
			//$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];

			if($r['term_type']==0){
				$r['price_low']='无需设定';
				$r['price_mid']='无需设定';
				$r['price_high']='无需设定';
			}else{
				$r['price_low']=str_replace('|',' , ',$r['price_low']);
				$r['price_mid']=str_replace('|',' , ',$r['price_mid']);
				$r['price_high']=str_replace('|',' , ',$r['price_high']);
			}

			$r['status'] = $r['status']==1 ? L('DISPLAY') : L('HIDDEN');
			$r['term_type'] = $r['term_type']==1 ? L('SHOW_GOODS') : L('LIST_TERM');

			$array[] = $r;

			//生成类目的option,这里只生成顶级目录的选项用于查找类目
			if(empty($term_id) && empty($keywords)){
				if($r['parentid']==0){
					$selected=$r['term_id']==$term_id?'selected':'';
					$termOption.='<option value="'.$r['term_id'].'"'.$selected.'>'.$r['name'].'</option>';
				}
			}
		}
		
		$tree->init($array);
		$str = "<tr>
					<td><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td>\$id</td>
					<td>\$spacer <a href='\$url' target='_blank'>\$name</a></td>
					<td><em>\$price_low</em></td>
					<td><em>\$price_mid</em></td>
					<td><em>\$price_high</em></td>
	    			<td>\$term_type</td>
	    			<td>\$status</td>
					<td>\$str_manage</td>
				</tr>";
		$taxonomys = $tree->get_tree(0, $str);

		//包含查询条件时需要重新调取所有顶层类目
		if(!empty($term_id) || !empty($keywords)){
			unset($result);

			$result=$this->terms_model->where(array('parent'=>0))->field('term_id,name')->select();

			foreach ($result as $key => $value) {
				$selected=$value['term_id']==$term_id?'selected':'';
				$termOption.='<option value="'.$value['term_id'].'"'.$selected.'>'.$value['name'].'</option>';
			}

		}

		$this->assign("taxonomys", $taxonomys);
		$this->assign("termOption",$termOption);
		$this->display();
	}
	
	// 添加商品类目（类目层级数以二级为准）
	public function add(){
	 	$parentid = I("get.parent",0,'intval');
	 	$tree = new \Tree();
	 	$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
	 	$tree->nbsp = '&nbsp;&nbsp;&nbsp;';


	 	$terms = $this->terms_model->where(array('parent'=>0))->order(array("path"=>"asc"))->select();
	 	
	 	$new_terms=array();
	 	foreach ($terms as $r) {
	 		$r['id']=$r['term_id'];
	 		$r['parentid']=$r['parent'];
	 		$r['selected']= (!empty($parentid) && $r['term_id']==$parentid)? "selected":"";
	 		$new_terms[] = $r;
	 	}
	 	$tree->init($new_terms);
	 	$tree_tpl="<option value='\$id' \$selected>\$spacer\$name</option>";
	 	$tree=$tree->get_tree(0,$tree_tpl);
	 	unset($terms);

	 	//取商品属性列表
	 	$propertyOption=$this->getGoodsPropertyList();
	 	
	 	$this->assign("terms_tree",$tree);
	 	$this->assign("parent",$parentid);
	 	$this->assign("propertyOption",$propertyOption);
	 	$this->display();
	}
	
	// 提交保存商品类目
	public function add_post(){
		if (IS_POST) {
			if ($this->terms_model->create()!==false) {
				$this->terms_model->startTrans();
				$addTermId=$this->terms_model->add();
				if (!$addTermId===false) {
					$this->clearTermsCache();
				    $parent=I('post.parent',0,'intval');
				    //更新子类目时同时刷新顶层类目的关键词
				    if($parent>0){
				    	if($this->updateTopTermKeywords($this->terms_model,$addTermId,$parent)===false){
				    		$this->error("添加失败！");
				    	}
				    }
				    $this->terms_model->commit();
					$this->success("添加成功！",U("AdminEMallTerm/index"));
				} else {
					$this->terms_model->rollback();
					$this->error("添加失败！");
				}
			} else {
				$this->error($this->terms_model->getError());
			}
		}
	}
	
	// 编辑商品类目
	public function edit(){
		$id = I("get.id",0,'intval');
		$parent=I('get.parent',0,'intval');


		$data=$this->terms_model->where(array("term_id" => $id))->find();
		if($data==false){
			$this->error('无法编辑当前类目，请稍候尝试！');
		}

		$filterData=Apiservice::getTermsFilter($id);

		$result=$this->terms_model->where(array('parent'=>0))->field('term_id,name')->select();
		if($result==false){
			$this->error('无法编辑当前类目，请稍候尝试！');
		}

		foreach ($result as $key => $value) {
			$selected=$value['term_id']==$parent && !empty($parent)?'selected':'';
			$termOption.='<option value="'.$value['term_id'].'"'.$selected.'>'.$value['name'].'</option>';
		}

		$propertyOption=$this->getGoodsPropertyList();

		$this->assign("termOption",$termOption);
		$this->assign("data",$data);
		$this->assign('parent',$parent);
		$this->assign('property_id',$data['property_id']);
		$this->assign('filterData',$filterData);
	 	$this->assign("propertyOption",$propertyOption);
	 	$this->assign('id',$id);
		$this->display();
	}
	
	// 提交保存商品类目
	public function edit_post(){
		if (IS_POST) {
			if ($this->terms_model->create()!==false) {
				$this->terms_model->startTrans();
				if ($this->terms_model->save()!==false) {
				    $term_id=I('post.term_id',0,'intval');
				    $parent=I('post.parent',0,'intval');
				    //如果是非顶层类目更新则重新更新顶层类目的关键词，否则直接更新
				    if($parent>0){
				    	if($this->updateTopTermKeywords($this->terms_model,$term_id,$parent)===false){
				    		$this->error("修改失败！");
				    	}
				    }

				    $this->terms_model->commit();
					$this->clearTermsCache($term_id,$parent);
					$this->success("修改成功！");
				} else {
					$this->terms_model->rollback();
					$this->error("修改失败！");
				}
			} else {
				$this->error($this->terms_model->getError());
			}
		}
	}

	//更新顶层类目的关键词，顶层类目需要包含所有子类目的关键词
	protected  function updateTopTermKeywords($TermModel,$term_id,$parent){
	    $where['parent']=$parent;
	    $where['term_id']=$term_id;
	    $where['_logic']='or';
	    $newTermData=$TermModel->where($where)->field('term_id,parent,term_keywords')->select();
	    $saveTopTermId=0;
	    $newKeywords='';
	    foreach ($newTermData as $key => $value) {
	    	$newKeywords.=empty($newKeywords) ? $value['term_keywords'] : ' '.$value['term_keywords'];
	    }
	    unset($where);
	    unset($newTermData);
	    $where['term_id']=$parent;
	    if($TermModel->where($where)->save(array('term_keywords'=>$newKeywords))===false){
	    	$this->terms_model->rollback();
	    	return false;
	    }
	    return true;
	}
	
	// 商品类目排序
	public function listorders() {
		$status = parent::_listorders($this->terms_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}
	
	// 删除商品类目
	public function delete() {
		$id = I("get.id",0,'intval');
		$count = $this->terms_model->where(array("parent" => $id))->count();
		$goodsCount=M('goods')->where(array('term_id'=>$id))->count();
		
		if ($count > 0) {
			$this->error("该类目下还有子类目，无法删除，请先删除所有的子类目！");
		}

		if ($goodsCount > 0) {
			$this->error("该类目下还有商品，无法删除，请先删除或者移动此类目下所有的商品！");
		}
		
		if ($this->terms_model->delete($id)!==false) {
			$this->clearTermsCache($id);
			$this->success("删除成功！");
		} else {
			$this->error("删除失败！");
		}
	}

	//取商品属性列表
	public function getGoodsPropertyList(){
		$propertyOption=F('EMall/GoodsPropertyOption');
		if(empty($propertyOption)){
		 	$propertyData=M('goods_type')->where(array('status'=>1))->field('cat_id,cat_name')->select();
		 	if(!empty($propertyData)){
		 		$propertyOption='';
		 		foreach ($propertyData as $key => $value) {
		 			$propertyOption.='<option value="'.$value['cat_id'].'">'.$value['cat_name'].'</option>';
		 		}
		 		F('EMall/GoodsPropertyOption',$propertyOption);
		 	}
	 	}
	 	return $propertyOption;
	}

	//清空和类目相关的缓存
	protected function clearTermsCache($term_id,$parentId){
		F('all_emall_terms',null);
		if(!empty($term_id)){
			F('Filter/EMall_Filter_Group_'.$parentId,null);
			F('EMall_Brand_Filter_'.$id,null);
			F('EMall_Price_Filter_'.$id,null);
		}
	}
	
}