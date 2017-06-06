<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城商品管理控制器
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
 * $Id: AdminEMallGoodsController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminEMallGoodsController extends AdminbaseController{
    
	protected $terms_model;
	protected $goods_model;
	protected $SKU_model;
	public $thumbPath;
	
	function _initialize() {
		parent::_initialize();
		$this->terms_model = D("EMall/MallTerms");
		$this->goods_model = M('goods');
		$this->SKU_model=M('sku');
		$this->thumbPath=__ROOT__.'data/upload/';
	}
	
	// 后台商品列表
    public function index(){
  		$this->_lists();
		$this->_getTermTree();
		$this->display();
	}
	
	// 商品添加
	public function add(){
		$term_id = I("get.term",0,'intval');
		$terms = $this->terms_model->order(array("listorder"=>"asc"))->select();
		//取第一个选中的商品类目数据用于加载对应的必填筛选项
		if(empty($term_id)){
			foreach ($terms as $key => $value) {
				if($value['parent']>0){
					$term_id=$value['term_id'];
					break;
				}
			}
		}

		$defaultTermData=$this->_getTermTree($term_id);

		$brandData=$this->getTermBrandData($defaultTermData);
		
		/*foreach ($brandData['brandData'] as $key => $value) {
			$optionCode.='<option value="'.$value['brand_id'].'">'.$value['chartext']{0}.'&nbsp;&nbsp;'.$value['brand_name'].'</option>';
		}
		$brandData['optionCode']=$optionCode;*/
		$this->assign('brand',$brandData);

		$this->assign('termFilter',$this->getTermFilter($term_id));
		$propertyData=$this->getTermProperty($term_id);
		$this->assign(array('termProperty'=>$propertyData['property'],'cat_id'=>$propertyData['cat_id']));

	 	$SKU_View=$this->CreateSKUView();
	 	//dump($SKU_View);
	 	$this->assign('SKU_View',$SKU_View);

		$this->assign("term_id",$term_id);

	 	$this->display();
	}
	
	// 商品添加提交(包含添加和编辑的数据提交更新)
	public function postAddGoodsData(){
		if (IS_POST) {
			if(empty($_POST['post']['term_id'])){
				$this->ajaxReturn(array('status'=>0,'error'=>"请至少选择一个分类！"));
			}
			if($_POST['post']['brand_id']<0){
				$this->ajaxReturn(array('status'=>0,'error'=>"必须选择品牌！"));
			}
			if(empty($_POST['UpFilePathInfo']['goodsPic'])){
				$this->ajaxReturn(array('status'=>0,'error'=>"请至少选择上传一张商品图片！"));
			}
			if($_POST['post']['logistics_id']<=0){
				$this->ajaxReturn(array('status'=>0,'error'=>"请选择一个运费模板！"));
			}
			//添加或者编辑商品的提交标识,0为添加，1为编辑
			$postType=I('post.postType',0,'intval');
			if(!empty($postType)){
				$goods_id=I('post.goods_id',0,'intval');
			}

			 
			/*$_POST['post']['post_modified']=date("Y-m-d H:i:s",time());*/
			$goods=I("post.post");
			$goods['user_id']=get_current_admin_id();
			$goodsPic=$_POST['UpFilePathInfo']['goodsPic'];
			$SKU_SeledItem['SKU_Style_Idx']=I('post.SKU_Style_Idx');
			$SKU_SeledItem['SKU_Spec_Idx']=I('post.SKU_Spec_Idx');
			$SKU_SeledItem['SKU_Style']=I('post.SKU_Style');
			$SKU_SeledItem['SKU_Spec']=I('post.SKU_Spec');
			$SKU_SeledItem['SKU_Style_Cus']=I('post.SKU_Style_Cus');
			$SKU_SeledItem['SKU_Spec_Cus']=I('post.SKU_Spec_Cus');
			$SKU_StyleName=I('post.SKU_StyleName');
			$SKU_SpecName=I('post.SKU_SpecName');
			$SKU_Stock=I('post.SKU_Stock');
			$SKU_Price=I('post.SKU_Price',0,'floatval');
			$SKU_SeriesNum=I('post.SKU_SeriesNum');
			$SKU_Pic=$_POST['UpFilePathInfo']['SKU_Pic'];
			$goods['logistics_borne']=I('post.logistics_borne',0,'intval');
			//$param_name=I('post.param_name');
			//$goods_param=I('post.goods_param');
			$goods['goods_description']=htmlspecialchars_decode($_POST['goods_description']);
			$goods['postType']=$postType;
			$goods['goods_id']=$goods_id;

			if(empty($goods['goods_discount'])){
				$goods['goods_discount']=0;
			}
			//处理商品价格生成实际销售价格（购买时如sku单品价格不为空则以单品价格为准）
			$goods['goods_price']=$goods['goods_discount']==0?$goods['market_price']:$goods['goods_discount'];

			//积分数据处理，当选择按配置进行计算时，不需要设置手动积分字段值
			if($_POST['optPoints']==1){
				$goods['goods_points']=0;
			}

			//运费险价格处理
			if(intval($goods['freeInsurance'])==1){
				$goods['freinsurance']=0;
			}

			//判断是否有外观样式单品选中项并生成Json代码
			$SKU_StyleCount=count($SKU_SeledItem['SKU_Style']);
			if($SKU_SeledItem['SKU_Style']!==''){
				//配置项信息处理，这里处理的是选择的SKU单品属性项和对应的图片信息
				for($i=0;$i<$SKU_StyleCount;$i++){
					$thumbPicPath_M='';
					$thumbPicPath_S='';
					$SKU_Img='';
					//缩略图生成
					if(!empty($SKU_Pic)){
						//文件名已经存在时跳过（编辑商品数据时保留没有改动的原数据）
						if(!file_exists($SKU_Pic[$i])){
							$SKU_Img=$this->thumbPath.$SKU_Pic[$i];
					    	$thumbPicPath_S=$this->thumbPath.$SKU_Pic[$i].'-s_thumb.jpg';
					    	$thumbPicPath_M=$this->thumbPath.$SKU_Pic[$i].'-m_thumb.jpg';
					    	getPicThumb($this->thumbPath.$SKU_Pic[$i],120,120,1,$thumbPicPath_S);
					    	getPicThumb($this->thumbPath.$SKU_Pic[$i],400,400,1,$thumbPicPath_M);	
				    	}else{
				    		$SKU_Img=$SKU_Pic[$i];
				    		$thumbPicPath_S=$SKU_Pic[$i].'-s_thumb.jpg';
					    	$thumbPicPath_M=$SKU_Pic[$i].'-m_thumb.jpg';
				    	}
					}

					$style[]=array('SKU_Style_Idx'=>$SKU_SeledItem['SKU_Style_Idx'][$i],
						'defaultValue'=>$SKU_SeledItem['SKU_Style'][$i],
						'modifyValue'=>$SKU_SeledItem['SKU_Style_Cus'][$i],
						'SKU_Img'=>$SKU_Img,
						'SKU_ThumbImg_M'=>$thumbPicPath_M,
						'SKU_ThumbImg'=>$thumbPicPath_S);
					//生成添加或者更新时对应sku表中sku_img的图片数据，由于是json，这里需要重新生成一个数组
					$skuImgData[]=array('SKU_Img'=>$SKU_Img,
						'SKU_ThumbImg_M'=>$thumbPicPath_M,
						'SKU_ThumbImg'=>$thumbPicPath_S);
				}
				$SKU_Info['SKU_Style']=$style;
			}

			//判断是否有规格单品选中项生成Json代码
			$SKU_SpecCount=count($SKU_SeledItem['SKU_Spec']);
			if($SKU_SeledItem['SKU_Spec']!==''){
				for($i=0;$i<$SKU_SpecCount;$i++){
					$spec[]=array('SKU_Spec_Idx'=>$SKU_SeledItem['SKU_Spec_Idx'][$i],
						'defaultValue'=>$SKU_SeledItem['SKU_Spec'][$i],
						'modifyValue'=>$SKU_SeledItem['SKU_Spec_Cus'][$i]);
				}
				$SKU_Info['SKU_Spec']=$spec;
			}

			if($SKU_SpecCount>0|$SKU_StyleCount>0){
				//处理单品项表单数据，转为数据行，这里以必然出现的表单项为长度进行遍历
				for($i=0;$i<count($SKU_Stock);$i++){
					$SKU_Price[$i]=$SKU_Price[$i]==''?$goods['goods_price']:$SKU_Price[$i];
					$SKU_Stock[$i]=$SKU_Stock[$i]==''?0:$SKU_Stock[$i];
					$SKU_Form[]=array('sku_spec'=>$SKU_SpecName[$i],
						'sku_style'=>$SKU_StyleName[$i],
						'sku_stock'=>$SKU_Stock[$i],
						'sku_price'=>$SKU_Price[$i],
						'sku_series'=>$SKU_SeriesNum[$i]);
				}
				$SKU_Info['SKU_Form']=$SKU_Form;
				//加入SKU单品项更新数据
				$goods['goods_sku']=json_encode($SKU_Info);
			}


			//商品展示图片数据处理
			foreach ($goodsPic as $key => $value) {
				//文件名已经存在时跳过（编辑商品数据时保留没有改动的原数据）
				if(!file_exists($value)){
					//缩略图生成
			    	$thumbPicPath_S=$this->thumbPath.$value.'-s_thumb.jpg';
			    	$thumbPicPath_M=$this->thumbPath.$value.'-m_thumb.jpg';
			    	getPicThumb($this->thumbPath.$value,120,120,1,$thumbPicPath_S);					
					getPicThumb($this->thumbPath.$value,400,400,1,$thumbPicPath_M);	
					$goods_img[]=array('goods_img'=>$this->thumbPath.$value,'m_thumb'=>$thumbPicPath_M,'thumb'=>$thumbPicPath_S);
				}else{
					$thumbPicPath_S=$value.'-s_thumb.jpg';
			    	$thumbPicPath_M=$value.'-m_thumb.jpg';
			    	$goods_img[]=array('goods_img'=>$value,'m_thumb'=>$thumbPicPath_M,'thumb'=>$thumbPicPath_S);
				}
			
			}
			$goods['goods_img']=json_encode($goods_img);

			//处理筛选项
			$filterKey='';
			$filterData=I('post.filter');
			foreach ($filterData as $key => $value) {
				$filterKey.=empty($filterKey) ? $value : ' '.$value;
			}
			$goods['filter_id']=$filterKey;

			//保存商品属性参数（Json数据）
			$goods_keywords='';
			$propertyData=$_POST['property'];
			foreach ($propertyData as $key => $value) {
				$value=json_decode($value,true);
				$smeta[$value['category']][]=array('paramname'=>$value['paramname'],'value'=>$value['value']);
				//所有参数值都加入商品搜索关键词
				if(!empty($value['value'])){			
					$goods_keywords.=empty($goods_keywords) ? $value['value'] : ' ' . $value['value'];
				}
			}
			$goods['goods_keywords']=$goods_keywords;
			$goods['smeta']=json_encode($smeta);

			//postType为0时表示添加，1表示编辑
			if(empty($postType) || $postType==0){
				//添加新商品时初始化当前统计的商品月销售起始月份
				$goods['static_month']=date('m');
				$result=$this->goods_model->add($goods);
			}else{
				$result=$this->goods_model->where(array('goods_id'=>$goods_id))->save($goods);
			}

			if($result!==false){
				//开启事务批量插入数据
				$this->SKU_model->startTrans();
				//如果存在SKU单品数据，则为其添加对应所属的商品ID
				if(count($SKU_Form)>0){
					if ($result!==false) {
						foreach ($SKU_Form as $key => $value) {
							//判断是插入数据还是编辑更新数据
							if(empty($postType) || $postType==0){
								$value['goods_id']=$result;
								$value['sku_idx']=$key;
								$value['sku_img']=!empty($skuImgData[$key])?json_encode($skuImgData[$key]):null;
								$skuResult=$this->SKU_model->add($value);
							}else{
								$value['sku_img']=!empty($skuImgData[$key])?json_encode($skuImgData[$key]):null;
								$skuResult=$this->SKU_model->where(array('goods_id'=>$goods_id,'sku_idx'=>$key))->save($value);
							}
						}
						if($skuResult===false){
							$this->SKU_model->rollback();
						}else{
							$this->SKU_model->commit();
						}
					}else{
						$this->SKU_model->rollback();
						$this->ajaxReturn(array('status'=>0,'error'=>"添加商品数据失败，请稍候尝试！"));
					}
				//不存在SKU单品数据时直接提交商品数据
				}else{
					if($result!==false){
						$this->SKU_model->commit();
					}else{
						$this->SKU_model->rollback();
					}
					
				}
				$this->ajaxReturn(array('status'=>1,'data'=>$goods));
			}else{
			 	$this->ajaxReturn(array('status'=>0,'error'=>'提交编辑商品数据失败，请稍候尝试！'));
			}
			//$this->ajaxReturn(array('status'=>1,'data'=>$goods));
		}
	}
	
	// 商品编辑
	public function edit(){
		$id=  I("get.id",0,'intval');
		$term_id=I('get.term_id',0,'intval');

		$goods=$this->goods_model->where(array('goods_id'=>$id))->find();

		$defaultTermData=$this->_getTermTree($term_id);

		$brandData=$this->getTermBrandData($defaultTermData);

		/*foreach ($brandData['brandData'] as $key => $value) {
			$isCheck=$value['brand_id']==$goods['brand_id']?'selected':'';
			$optionCode.='<option value="'.$value['brand_id'].'" '.$isCheck.'>'.$value['chartext']{0}.'&nbsp;&nbsp;'.$value['brand_name'].'</option>';
		}

		$brandData['optionCode']=$optionCode;*/
		$this->assign('brand',$brandData);

		$this->assign('termFilter',$this->getTermFilter($term_id));
		$propertyData=$this->getTermProperty($term_id);
		$this->assign(array('termProperty'=>$propertyData['property'],'cat_id'=>$propertyData['cat_id']));

		//dump($optionCode);
	 	$SKU_View=$this->CreateSKUView();
	 	//dump($SKU_View);
	 	$this->assign('SKU_View',$SKU_View);

		$this->assign("goods",$goods);
		$this->assign("smeta",json_decode($goods['smeta'],true));
		$this->assign("terms",$terms);

		$this->display();
	}
	
	
	// 文章分类排序
	public function listorders() {
		$status = parent::_listorders($this->terms_model);
		if ($status) {
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}

	// 商品下架
	public function offSale(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->goods_model->where(array('goods_id'=>$id))->save(array('goods_status'=>0)) !==false) {
				$this->success("商品下架成功！");
			} else {
				$this->error("商品下架失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->goods_model->where(array('goods_id'=>array('in',$ids)))->save(array('goods_status'=>0))!==false) {
				$this->success("商品上架成功！");
			} else {
				$this->error("商品上架失败！");
			}
		}
	}

	// 商品上架
	public function onSale(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->goods_model->where(array('goods_id'=>$id))->save(array('goods_status'=>1)) !==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->goods_model->where(array('goods_id'=>array('in',$ids)))->save(array('goods_status'=>1))!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}
	
	// 移除商品到回收站
	public function delete(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->goods_model->where(array('goods_id'=>$id))->save(array('goods_status'=>-1)) !==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->goods_model->where(array('goods_id'=>array('in',$ids)))->save(array('goods_status'=>-1))!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}

	
	// 商品回收站列表
	public function recyclebin(){
		$this->_lists(array('goods_status'=>array('eq',-1)));
		$this->_getTree();
		$this->display();
	}
	
	// 清除回收站中的商品
	public function clean(){
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			$ids = array_map('intval', $ids);
			$delSKU=$this->SKU_model->where(array("goods_id"=>array('in',$ids)))->delete();
			if($delSKU!==false){
				$status=$this->goods_model->where(array("goods_id"=>array('in',$ids),'goods_status'=>-1))->delete();
			}
			
			if ($status!==false) {
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}else{
			if(isset($_GET['id'])){
				$id = I("get.id",0,'intval');
				$delSKU=$this->SKU_model->where(array("goods_id"=>$id))->delete();
				if($delSKU!==false){
					$status=$this->goods_model->where(array("goods_id"=>$id,'goods_status'=>-1))->delete();
				}
				
				if ($status!==false) {
					$this->success("删除成功！");
				} else {
					$this->error("删除失败！");
				}
			}
		}
	}
	
	// 回收站商品还原（恢复后为下架状态）
	public function restore(){
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->goods_model->where(array("goods_id"=>$id,'goods_status'=>-1))->save(array("goods_status"=>0))) {
				$this->success("还原成功！");
			} else {
				$this->error("还原失败！");
			}
		}else{
			$ids = explode(',',I('get.ids/s'));
			$ids = array_map('intval',$ids);
			if ($this->goods_model->where(array("goods_id"=>array('in',$ids),'goods_status'=>-1))->save(array("goods_status"=>0))) {
				$this->success("批量还原成功！");
			} else {
				$this->error("批量还原失败！");
			}			
		}
	}

	/**
	 * 商品列表处理方法,根据不同条件显示不同的列表
	 * @param array $where 查询条件
	 */
	private function _lists($where=array()){
		$term_id=I('request.term',0,'intval');		

		if(empty($where)){
			$where['goods_status']=array('EGT',0);
		}

		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['post_date']=array(
		        array('EGT',$start_time)
		    );
		}
		
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['post_date'])){
		        $where['post_date']=array();
		    }
		    array_push($where['post_date'], array('ELT',$end_time));
		}
		
		$keyword=I('request.keyword');
		if(!empty($keyword)){
		    $where['match(goods_keywords)']=array('exp','against(\''.$keyword.'\' IN BOOLEAN MODE)');
		}

		if(!empty($term_id)){
			$where['a.term_id']=$term_id;
		    $this->goods_model->join("__MALL_TERMS__ b ON a.term_id = b.term_id");
		}
		
		$count=$this->goods_model
		->alias("a")
		->join("__USERS__ c ON a.user_id = c.id")
		->where($where)
		->count();
		
		$field='goods_id,
				a.term_id,
				goods_title,
				goods_status,
				goods_img,
				goods_price,
				goods_discount,
				market_price,
				goods_stock,
				appraise_num,
				a.hits,
				a.post_date,
				c.user_nicename';		
			
		$page = $this->page($count, 20);
			
		$this->goods_model
		->alias("a")
		->join("__USERS__ c ON a.user_id = c.id")
		->where($where)
		->limit($page->firstRow , $page->listRows)
		->order("a.post_date DESC");
		if(empty($term_id)){
		    $this->goods_model->field($field);
		}else{
		    $this->goods_model->field($field);
		    $this->goods_model->join("__MALL_TERMS__ b ON a.term_id = b.term_id");
		}
		$posts=$this->goods_model->select();
		
		$this->assign('term_id',$term_id);
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("goods",$posts);
	}
	
	// 获取文章分类树结构 select 形式
	private function _getTree(){
		$term_id=empty($_REQUEST['term'])?0:intval($_REQUEST['term']);
		$result = $this->terms_model->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
			$r['visit'] = "<a href='#'>访问</a>";
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			$r['selected']=$term_id==$r['term_id']?"selected":"";
			$array[] = $r;
		}
		
		$tree->init($array);
		$str="<option value='\$id' \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		$this->assign("taxonomys", $taxonomys);
	}
	
	// 获取商品类目树结构 
	/**
	 * @param array $term_id :默认选中的商品类目
     */
	private function _getTermTree($term_id){
		$result = $this->terms_model->order(array("listorder"=>"asc"))->select();
		
		$tree = new \Tree();
		$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
		$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
		foreach ($result as $r) {
			$r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
			$r['id']=$r['term_id'];
			$r['parentid']=$r['parent'];
			if($term_id==$r['term_id']){
				$r['selected']= "selected";
				$r['checked'] = "checked";
				$termData=$r;	
			}

			$r['disabled'] = $r['parent']==0 ? 'disabled="true"':'';
			$array[] = $r;
		}
		

		$tree->init($array);
		$str="<option value='\$id' data-cid='\$property_id' \$disabled \$selected>\$spacer\$name</option>";
		$taxonomys = $tree->get_tree(0, $str);
		//dump($taxonomys); 
		$this->assign("taxonomys", $taxonomys);
		//返回选中的类目数据
		return $termData;
	}

	//获取指定商品类目下的品牌数据,$termData:指定商品类目的数据，必须包含类目id和品牌id组
	public function getTermBrandData($termData){
		$brandData=F('EMall_Brand_Filter_'.$termData['term_id']);
		if(empty($brandData)){
			$brandModel=M('brand');
			$where['display']=1;
			$where['brand_id']=array('in',$termData['brand_id']);
			$brandData=$brandModel->where($where)
			->Field('brand_id,brand_name,alias,brand_logo,chartext')
			->order(array('chartext'=>'asc'))
			->select();
			$brandData['brandData']=$brandData;
			$brandData=json_encode($brandData);
			F('EMall_Brand_Filter_'.$termData['term_id'],$brandData);
		}
		return $brandData;
	}

	//获取添加商品时必须填写的筛选项
	public function getTermFilter($term_id){
		//F('EMall_Term_Filter_'.$term_id,null);
		$filterData=F('EMall_Term_Filter_'.$term_id);
		if(empty($filterData)){
			$where['term_id']=$term_id;
			$filterData=M('filter')->where($where)->select();

			if(!empty($filterData)){
				$tree = new \Tree();
				$tree->init($filterData);
				$filterData=$tree->get_tree_array(0);
				$filterData=json_encode($filterData);
				F('EMall_Term_Filter_'.$term_id,$filterData);
			}
		}
		return $filterData;
	}

	public function getMoreGoodsProperty(){
		$term_id=I('get.term_id',0,'intval');
		$cat_id=I('get.cat_id',0,'intval');

		$filterData=$this->getTermFilter($term_id);
		$propertyData=$this->getTermProperty($term_id,$cat_id);
		$termData=M('mall_terms')->where(array('term_id'=>$term_id))->field('term_id,brand_id')->find();
		//获取品牌数据
		$brandData=$this->getTermBrandData($termData);
		$this->ajaxReturn(array('status'=>1,'filterData'=>$filterData,'propertyData'=>$propertyData['property'],'brandData'=>$brandData,'cat_id'=>$propertyData['cat_id']));
	}

	//获取更多详细参数的设置选项
	public function getTermProperty($term_id,$cat_id){
		if(!empty($cat_id)){
			$propertyData=F('EMall_Term_Property_'.$cat_id);
			if(empty($propertyData)){	
				$where['cat_id']=$cat_id;
				changeModelProperty($this->terms_model,'goods_type');
				$propertyData=$this->terms_model->where($where)->field('cat_id,property')->find();
			}		
		}else{
			changeModelProperty($this->terms_model,'mall_terms');
			$where['a.term_id']=$term_id;
			$propertyData=$this->terms_model
			->alias('a')
			->join('__GOODS_TYPE__ b ON b.cat_id = a.property_id')
			->field('b.property,b.cat_id')
			->where($where)
			->find();			
		}

		if(!empty($propertyData)){
			F('EMall_Term_Property_'.$propertyData['cat_id'],$propertyData);
		}
		//dump($propertyData);
		return $propertyData;
	}

	//创建SKU表单html代码

	private function CreateSKUView(){
		$i=0;
		$colorCode='';
		$SpecCode='';
		$SKU_Style=C('GOODS_COLOR');
		$SKU_Spec=C('GOODS_SPEC');

		//取出用户数据，一个是勾选的单品项属性,这个数据解析交给客户端处理，第二个是单品项的对应数据
		$seledSKUOption=json_encode(array('SKU_Color'=>array('blue'=>'蓝色','black'=>'黑色'),'SKU_Spec'=>array('SPEC-2'=>'规格二')));
		$SKU_Data;
		//根据用户数据生成视图代码


		foreach ($SKU_Style as $key => $value) {
			$colorCode.='<label class="checkbox-inline"><input name="SKU_Style[]" value="'
			.$value.'" type="checkbox" data-tag="'.$key.'" data-sku="SKU_Style" ><span>'.$value.'</span>
			<input type=hidden value="'.$value.'" data-tag="modifyValue">
			<input type=hidden value="'.$i.'" data-tag="idx"></label>';
			$i++;
		}

		$i=0;

		foreach ($SKU_Spec as $key => $value) {
			$SpecCode.='<label class="checkbox-inline"><input name="SKU_Spec[]" value="'
			.$value.'" type="checkbox" data-tag="'.$key.'" data-sku="SKU_Spec" ><span>'.$value.'</span>
			<input type=hidden value="'.$value.'" data-tag="modifyValue">
			<input type=hidden value="'.$i.'" data-tag="idx"></label>';	
			$i++;		
		}

		$colorCode='<div id="SKU_Style" class="checkbox" data-skutype="sub">'.$colorCode.'</div>';
		$SpecCode='<div id="SKU_Spec" class="checkbox" data-skutype="main">'.$SpecCode.'</div>';
		$this->assign('seledSKUOption',$seledSKUOption);

		return $colorCode.$SpecCode;
	}

	// 文章批量移动
	public function move(){
		if(IS_POST){
			if(isset($_GET['ids']) && isset($_POST['term_id'])){
			    $old_term_id=I('get.old_term_id',0,'intval');
			    $term_id=I('post.term_id',0,'intval');
			    if($old_term_id!=$term_id){
			        $ids= I('get.ids/s');
			        $where['goods_id']=array('in',$ids);
			        if($this->goods_model->where($where)->save(array('term_id'=>$term_id))===false){
			        	$this->error("移动失败！");
			        }			        
			    }			    
			    $this->success("移动成功！");
			}
		}else{
			$tree = new \Tree();
			$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$terms = $this->terms_model->order(array("path"=>"ASC"))->select();
			$new_terms=array();
			foreach ($terms as $r) {
				$r['id']=$r['term_id'];
				$r['parentid']=$r['parent'];
				$r['disabled']=$r['parent']==0?'disabled':'';
				$new_terms[] = $r;
			}
			$tree->init($new_terms);
			$tree_tpl="<option value='\$id' \$disabled>\$spacer\$name</option>";
			$tree=$tree->get_tree(0,$tree_tpl);
			 
			$this->assign("terms_tree",$tree);
			$this->display();
		}
	}


	// 商品批量复制
	public function copy(){
	    if(IS_POST){
	        if(isset($_GET['ids']) && isset($_POST['term_id'])){
	            $ids=explode(',', I('get.ids/s'));
	            $ids=array_map('intval', $ids);
	            $uid=sp_get_current_admin_id();
	            $term_id=I('post.term_id',0,'intval');
	            $term_count=$this->terms_model->where(array('term_id'=>$term_id))->count();
	            if($term_count==0){
	                $this->error('分类不存在！');
	            }
	            
	            $data=array();
	            $finishSKUCopy=true;
	            //目前商品表用的是MyISAM表，不支持事务（所以以下商品表的事务处理无效，商品表数据复制失败时，对应数据并不会回滚，需要自行删除），实在需要改为支持事务表的话请自行修改
	            $this->goods_model->startTrans();
	            $this->SKU_model->startTrans();
	            foreach ($ids as $id){
	                $find_goods=$this->goods_model->where(array('goods_id'=>$id))->find();
	                $find_sku=$this->SKU_model->where(array('goods_id'=>$id))->select();
	                if($find_goods){
	                	unset($find_goods['goods_id']);
	                    $find_goods['post_author']=$uid;
	                    $find_goods['post_date']=date('Y-m-d H:i:s');
	                    $find_goods['modified_date']=date('Y-m-d H:i:s');
	                    $find_goods['term_id']=$term_id;
	                    $goods_id=$this->goods_model->add($find_goods);
	                    if($goods_id>0){
	                    	//array_push($data,array('object_id'=>$goods_id));
	                    	if(!empty($find_sku)){
		                    	//copy 单品项数据
		                    	foreach ($find_sku as $key => $value) {
		                    		unset($value['sku_id']);
		                    		$value['goods_id']=$goods_id;
		                    		$sku_id=$this->SKU_model->add($value);
		                    		if($sku_id===false){
		                    			$finishSKUCopy=false;
		                    			$failId=$id;
		                    			$this->SKU_model->rollback();
		                    			break;
		                    		}
		                    	}
	                    	}
	                    }else{
	                    	$this->goods_model->rollback();
	                    	$finishSKUCopy=false;
	                    	break;
	                    }
	                }
	            }
	            
	            if($finishSKUCopy === false){
	                $this->error("复制ID为".$failId."的商品数据时失败！");	            	
	            }else{
	            	$this->goods_model->commit();
	            	$this->SKU_model->commit();
	            	unset($find_sku);
	            	$this->success("复制成功！");
	            }

	            	//ChromePhp::log($data);
	            	//return false;
	            /*if ($this->goods_model->addAll($data) === false ) {
	            	$this->error("复制商品时失败！");	 
	            } else {
	                
	            }*/
	        }
	    }else{
			$tree = new \Tree();
			$tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
			$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
			$terms = $this->terms_model->order(array("path"=>"ASC"))->select();
			$new_terms=array();
			foreach ($terms as $r) {
				$r['id']=$r['term_id'];
				$r['parentid']=$r['parent'];
				$r['disabled']=$r['parent']==0?'disabled':'';
				$new_terms[] = $r;
			}
			$tree->init($new_terms);
			$tree_tpl="<option value='\$id' \$disabled>\$spacer\$name</option>";
			$tree=$tree->get_tree(0,$tree_tpl);
			 
			$this->assign("terms_tree",$tree);
			$this->display();
	    }
	}	
}