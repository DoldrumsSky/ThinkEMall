<?php
namespace EMall\Controller;

use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城广告管理控制器
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
 * $Id: AdminEMallADController.php 17217 2017-04-27 20:41:08Z YangHua $
*/

class AdminEMallADController extends AdminbaseController{
    
	protected $ad_model;
	public $thumbPath;
	
	function _initialize() {
		parent::_initialize();
		$this->ad_model = M("mall_group_ad");
		$this->thumbPath=__ROOT__.'data/upload/';
	}
	
	// 类目组广告管理首页
    public function termsAD(){
		$keywords=I('post.keywords');
		$status=I('post.ad_status',0,'intval');

    	$where['ad_type']=2;
    	if(!empty($keywords)){
			$where['ad_name']=array('like','%'.$keywords.'%');
		}
		$where['ad_status']=array('egt',0);
		if($status>0){
			$where['ad_status']=$status;
		}

    	$count=$this->ad_model->where($where)->count();
    	$page=$this->page($count,20);
    	$termsAD=$this->ad_model->where($where)->limit($page->firstRow,$page->listRows)->order('listorder')->select();

    	$this->assign('adData',$termsAD);
    	$this->assign('page',$page->show('Admin'));
		$this->display();
	}

	public function manageTermsAD(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->find();
		if($adData===false){
			$this->error('无法进入广告管理页面：'.$this->ad_model->getError());
		}

		$this->assign('adData',$adData);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	public function addTermsAdItem(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$this->assign('id',$id);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	public function editTermsAdItem(){
		$adKey=I('get.adKey',0,'intval');
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$this->assign('adData',$adData);
		$this->assign('id',$id);
		$this->assign('adKey',$adKey);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	public function postTermsAD(){
		$postType=I('get.postType');
		$adKey=I('get.adKey',0,'intval');
		$data=I('post.');
		$id=I('get.id',0,'intval');

		if(empty($data['ad_title'])){
			$this->error('必须填写广告主标题！');
		}

		if(empty($data['UpFilePathInfo'])){
			$this->error('必须上传广告图片！');
		}else{			
			$data['ad_image']=$data['UpFilePathInfo'];
			unset($data['UpFilePathInfo']);
			unset($data['file']);
		}

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$adData['ad_data']=json_decode($adData['ad_data'],true);

		if(empty($postType)){
			$adData['ad_data']['AdItem'][]=$data;
		}else if($postType=='edit'){
			$adData['ad_data']['AdItem'][$adKey]=$data;
		}

		$adData['ad_data']=json_encode($adData['ad_data']);
		$this->ad_model->where(array('id'=>$id))->save($adData);

		$this->clearAdCache();
		$this->success('成功添加类目组广告！');
	}

	//设置类目组广告的封面
	public function setTermsADCover(){
		$id=I('get.id',0,'intval');

		$coverData=$this->ad_model->where(array('id'=>$id))->field('ad_cover')->find();
		if(!empty($coverData)){
			$this->assign('coverData',$coverData['ad_cover']);
		}

		$this->assign('id',$id);
		$this->display();
	}

	//提交类目组广告的封面
	public function postTermsADCover(){
		$id=I('get.id',0,'intval');
		$data=I('post.');

		if(empty($data['ad_title'])){
			$this->error('必须填写广告主标题！');
		}

		if(empty($data['UpFilePathInfo'])){
			$this->error('必须上传封面图片！');
		}else{			
			$data['ad_image']=$data['UpFilePathInfo'];
			unset($data['UpFilePathInfo']);
			unset($data['file']);
		}

		$adData['ad_cover']=json_encode($data);
		$this->ad_model->where(array('id'=>$id))->save($adData);
		$this->clearAdCache();
		$this->success('成功添加类目组广告！');
	}

	// 幻灯广告管理首页
    public function sliderAD(){
		$keywords=I('post.keywords');
		$status=I('post.ad_status',0,'intval');

    	$where['ad_type']=1;
    	if(!empty($keywords)){
			$where['ad_name']=array('like','%'.$keywords.'%');
		}
		$where['ad_status']=array('egt',0);
		if($status>0){
			$where['ad_status']=$status;
		}

    	$count=$this->ad_model->where($where)->count();
    	$page=$this->page($count,20);
    	$sliderAD=$this->ad_model->where($where)->limit($page->firstRow,$page->listRows)->order('listorder')->select();

    	$this->assign('adData',$sliderAD);
    	$this->assign('page',$page->show('Admin'));
		$this->display();	
	}

	//管理幻灯广告
	public function manageSliderAD(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->find();
		if($adData===false){
			$this->error('无法进入广告管理页面：'.$this->ad_model->getError());
		}

		$this->assign('adData',$adData);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	//添加幻灯广告项
	public function addSliderAdItem(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$this->assign('id',$id);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	//编辑幻灯广告项
	public function editSliderAdItem(){
		$adKey=I('get.adKey',0,'intval');
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$this->assign('adData',$adData);
		$this->assign('id',$id);
		$this->assign('adKey',$adKey);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	public function postSliderAD(){
		$postType=I('get.postType');
		$adKey=I('get.adKey',0,'intval');
		$data=I('post.');
		$id=I('get.id',0,'intval');

		if(empty($data['ad_title'])){
			$this->error('必须填写广告主标题！');
		}

		if(empty($data['UpFilePathInfo'])){
			$this->error('必须上传广告图片！');
		}else{			
			$data['ad_image']=$data['UpFilePathInfo'];
			unset($data['UpFilePathInfo']);
			unset($data['file']);
			//缩略图生成
			
			if((!file_exists($data['ad_image']) && $postType=='edit') || $postType=='add'){
	    		$thumbPicPath_S=$this->thumbPath.$data['ad_image'].'-s_thumb.jpg';
	    		//$thumbPicPath_M=$this->thumbPath.$value.'-m_thumb.jpg';
	    		getPicThumb($this->thumbPath.$data['ad_image'],200,200,1,$thumbPicPath_S);
	    		$data['ad_image_thumb_s']=$thumbPicPath_S;
	    		$data['ad_image']=$this->thumbPath.$data['ad_image'];
	    	}else{
	    		$data['ad_image_thumb_s']=$data['ad_image'].'-s_thumb.jpg';
	    		$data['ad_image']=$data['ad_image'];	    		
	    	}
		}

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$adData['ad_data']=json_decode($adData['ad_data'],true);

		if($postType=='add'){
			$adData['ad_data']['AdItem'][]=$data;
		}else if($postType=='edit'){
			$adData['ad_data']['AdItem'][$adKey]=$data;
		}

		$adData['ad_data']=json_encode($adData['ad_data']);
		$this->ad_model->where(array('id'=>$id))->save($adData);

		$this->clearAdCache();
		$this->success('成功添加类目组广告！');
	}

	// 普通广告管理首页
    public function normalAD(){
		$keywords=I('post.keywords');
		$status=I('post.ad_status',0,'intval');

    	$where['ad_type']=3;
    	if(!empty($keywords)){
			$where['ad_name']=array('like','%'.$keywords.'%');
		}
		$where['ad_status']=array('egt',0);
		if($status>0){
			$where['ad_status']=$status;
		}

    	$count=$this->ad_model->where($where)->count();
    	$page=$this->page($count,20);
    	$normalAD=$this->ad_model->where($where)->limit($page->firstRow,$page->listRows)->order('listorder')->select();

    	$this->assign('adData',$normalAD);
    	$this->assign('page',$page->show('Admin'));
		$this->display();
	}

	//管理普通广告
	public function manageNormalAD(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->find();
		if($adData===false){
			$this->error('无法进入广告管理页面：'.$this->ad_model->getError());
		}

		$this->assign('adData',$adData);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	//添加普通广告项
	public function addNormalAdItem(){
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$this->assign('id',$id);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}
	//编辑普通广告项
	public function editNormalAdItem(){
		$adKey=I('get.adKey',0,'intval');
		$id=I('get.id',0,'intval');
		$ad_name=I('get.ad_name');

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$this->assign('adData',$adData);
		$this->assign('id',$id);
		$this->assign('adKey',$adKey);
		$this->assign('ad_name',$ad_name);
		$this->display();
	}

	public function postNormalAD(){
		$postType=I('get.postType');
		$adKey=I('get.adKey',0,'intval');
		$data=I('post.');
		$id=I('get.id',0,'intval');
		$goods_id=I('post.goods_id',0,'intval');
		$oldGoodsId=I('get.oldGoodsId',0,'intval');

		if(empty($data['ad_title'])){
			$this->error('必须填写广告主标题！');
		}

		if(!empty($data['UpFilePathInfo'])){						
			$data['ad_image']=$data['UpFilePathInfo'];
		}else{
			$data['ad_image']='';
		}		
		unset($data['UpFilePathInfo']);
		unset($data['file']);

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data,goods_id')->find();
		$adData['ad_data']=json_decode($adData['ad_data'],true);
		$sourceGoodsId=$adData['goods_id'];

		if(empty($postType)){
			//未添加重复绑定商品的判断，需要时可以自行添加
			$adData['ad_data']['AdItem'][]=$data;
			if(!empty($sourceGoodsId)){
				$adData['goods_id'].=','.$goods_id;
			}else{
				$adData['goods_id']=$goods_id;
			}
			
		}else if($postType=='edit'){
			$adData['ad_data']['AdItem'][$adKey]=$data;
			//重新生成绑定的广告商品id字串
			if(!empty($sourceGoodsId)){
				$sourceGoodsId=explode(',',$sourceGoodsId);
				if(empty($oldGoodsId)){
					$sourceGoodsId[]=$goods_id;
				}else{
					$oldIdx=array_search($oldGoodsId, $sourceGoodsId);
					if($oldIdx!==false){
						unset($sourceGoodsId[$oldIdx]);
					}
					$sourceGoodsId[]=$goods_id;
				}				
				$adData['goods_id']=join(',',$sourceGoodsId);
			}else{
				$adData['goods_id']=$goods_id;
			}
		}

		$adData['ad_data']=json_encode($adData['ad_data']);
		$this->ad_model->where(array('id'=>$id))->save($adData);

		$this->clearAdCache();
		$this->success('成功添加普通类广告！');
	}

	
	// 添加广告类(统一使用一个页面)
	public function add(){
		$ad_type=I('get.ad_type',0,'intval');
		$this->assign('ad_type',$ad_type);
	 	$this->display();
	}
	
	// 提交保存添加的广告类
	public function add_post(){
		if (IS_POST) {
			if($this->ad_model->create()!==false){
				if($this->ad_model->add()===false){
					$this->error('添加广告失败：'.$this->ad_model->getError());
				}else{
					$this->clearAdCache();
					$this->success('添加广告成功！');
				}
			}
		}
	}
	
	// 编辑广告类
	public function edit(){
		$id = I("get.id",0,'intval');
		$ad_data=$this->ad_model->where(array('id'=>$id))->find();
		$this->assign('adData',$ad_data);
		$this->display();
	}
	
	// 提交保存编辑广告类
	public function edit_post(){
		if (IS_POST) {
			$id=I('get.id',0,'intval');
			if(!empty($id)){
				if($this->ad_model->create()!==false){
					if($this->ad_model->where(array('id'=>$id))->save()===false){
						$this->error('编辑保存广告失败：'.$this->ad_model->getError());
					}else{
						$this->clearAdCache();
						$this->success('编辑广告成功！');
					}
				}
			}
		}
	}


	// 广告大类排序
	public function listorders() {
		$status = parent::_listorders($this->ad_model);
		if ($status) {
			$this->clearAdCache();
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}
	}

	//广告列表项排序
	public function adItemOrders(){
		$id=I('get.id');
		$orderItem=I('post.listorders/a');

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		$adData['ad_data']=json_decode($adData['ad_data'],true);

		foreach ($orderItem as $key => $value) {
			$adData['ad_data']['AdItem'][$key]['listorder']=$value;
		}
		$adData['ad_data']=json_encode($adData['ad_data']);

		$status=$this->ad_model->where(array('id'=>$id))->save($adData);

		if ($status!==false) {
			$this->clearAdCache();
			$this->success("排序更新成功！");
		} else {
			$this->error("排序更新失败！");
		}		
	}
	
	// 删除广告类
	public function delete() {
		if(isset($_GET['id'])){
			$id = I("get.id",0,'intval');
			if ($this->ad_model->where(array('id'=>$id))->delete()!==false) {
				$this->clearAdCache();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
		
		if(isset($_POST['ids'])){
			$ids = I('post.ids/a');
			
			if ($this->ad_model->where(array('id'=>array('in',$ids)))->delete()!==false) {
				$this->clearAdCache();
				$this->success("删除成功！");
			} else {
				$this->error("删除失败！");
			}
		}
	}

	//删除类目组广告项
	public function deleteTermsAdItem(){
		$id=I('post.id',0,'intval');
		$adKey=explode(',',I('post.adKey'));

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		if(!empty($adData)){
			$adData['ad_data']=json_decode($adData['ad_data'],true);
			if(is_array($adKey)){
				foreach ($adKey as $key => $value) {
					unset($adData['ad_data']['AdItem'][$value]);
				}
				rsort($adData['ad_data']['AdItem']);
			}else{
				array_splice($adData['ad_data']['AdItem'],$adKey,1);
			}

			$adData['ad_data']=json_encode($adData['ad_data']);
			if($this->ad_model->where(array('id'=>$id))->save($adData)===false){
				$this->ajaxReturn(array('status'=>0,'error'=>'更新广告数据失败！'.$this->ad_model->getError()));
			}else{
				$this->clearAdCache();
				$this->ajaxReturn(array('status'=>1,'data'=>$deleteIdx));
			}
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'未查询到对应的广告数据！'));
		}
	}

	//删除幻灯广告项（代码和其它广告的删除代码一样，如果确定不更改数据结构可以合而为一）
	public function deleteSliderAdItem(){
		$id=I('post.id',0,'intval');
		$adKey=explode(',',I('post.adKey'));

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		if(!empty($adData)){
			$adData['ad_data']=json_decode($adData['ad_data'],true);
			if(is_array($adKey)){
				foreach ($adKey as $key => $value) {
					unset($adData['ad_data']['AdItem'][$value]);
				}
				rsort($adData['ad_data']['AdItem']);
			}else{
				array_splice($adData['ad_data']['AdItem'],$adKey,1);
			}

			$adData['ad_data']=json_encode($adData['ad_data']);
			if($this->ad_model->where(array('id'=>$id))->save($adData)===false){
				$this->ajaxReturn(array('status'=>0,'error'=>'更新广告数据失败！'.$this->ad_model->getError()));
			}else{
				$this->clearAdCache();
				$this->ajaxReturn(array('status'=>1,'data'=>$deleteIdx));
			}
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'未查询到对应的广告数据！'));
		}
	}

	//删除普通广告项（代码和其它广告的删除代码一样，如果确定不更改数据结构可以合而为一）
	public function deleteNormalAdItem(){
		$id=I('post.id',0,'intval');
		$adKey=explode(',',I('post.adKey'));

		$adData=$this->ad_model->where(array('id'=>$id))->field('ad_data')->find();
		if(!empty($adData)){
			$adData['ad_data']=json_decode($adData['ad_data'],true);
			if(is_array($adKey)){
				foreach ($adKey as $key => $value) {
					unset($adData['ad_data']['AdItem'][$value]);
				}
				rsort($adData['ad_data']['AdItem']);
			}else{
				array_splice($adData['ad_data']['AdItem'],$adKey,1);
			}

			$adData['ad_data']=json_encode($adData['ad_data']);
			if($this->ad_model->where(array('id'=>$id))->save($adData)===false){
				$this->ajaxReturn(array('status'=>0,'error'=>'更新广告数据失败！'.$this->ad_model->getError()));
			}else{
				$this->clearAdCache();
				$this->ajaxReturn(array('status'=>1,'data'=>$deleteIdx));
			}
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'未查询到对应的广告数据！'));
		}
	}

	//绑定商品数据到普通广告类中
	public function bindGoods(){
		$goods_id=I('post.goods_id',0,'intval');
		$goodsModel=M('goods');
		$where['goods_id']=$goods_id;
		$where['goods_status']=1;
		$goodsData=$goodsModel->where($where)->field('goods_title,goods_price,appraise_num,goods_img,term_id')->find();

		if(!empty($goodsData)){
			$this->ajaxReturn(array('status'=>1,'data'=>$goodsData));
		}else{
			$this->ajaxReturn(array('status'=>0,'error'=>'无法获取商品数据，商品ID错误或商品已下架！'));
		}
	}

	//更新缓存
	public function clearAdCache(){
		F('EMall_TermsAdListJson',null);
		clearFCache('ADJson/*');
	}
	
}