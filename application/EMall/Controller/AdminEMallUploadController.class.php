<?php
namespace EMall\Controller;

use Common\Controller\AdminbaseController;
use Think\Upload;
/**
 * ThinkEMall电子商城上传文件控制器
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
 * $Id: AdminEMallUploadController.php 17217 2016-12-10 06:29:08Z YangHua $
*/

class AdminEMallUploadController extends AdminbaseController{
	//protected $allowPicUploadNum = C('EMALL_SHOWPIC_NUM');

	protected $config = array(
		    'maxSize'    =>    1145728,
		    'rootPath'   =>    './data/upload/',
		    'savePath'   =>    'EMall/',
		    'saveName'   =>    'com_create_guid',
		    'exts'       =>    array('jpg', 'gif', 'png', 'jpeg'),
		    'autoSub'    =>    true,
		    'subName'    =>    array('date','Ymd'),
		);

	public function _initialize() {
		$adminid=sp_get_current_admin_id();
		$userid=sp_get_current_userid();
		if(empty($adminid) && empty($userid)){
			exit("非法上传！");
		}
	}

	public function uploadMultiPic(){
			//$allowPicUploadNum = C('EMALL_SHOWPIC_NUM');
			$picFile = $_FILES["file"];
			/*if(count($picFile['name'])>$allowPicUploadNum){
				$this->ajaxReturn(array('status'=>'0','errStr'=>'上传文件数不能超过'.$allowPicUploadNum));
			}*/

			$upload = new Upload($this->config);
			$info = $upload->upload(array($picFile));
		    if(!$info) {// 上传错误提示错误信息
		        $errStr=$upload->getError();
		        $this->ajaxReturn(array('status'=>'0','errStr'=>$errStr));
		    }else{// 上传成功 获取上传文件信息
		    	$fullPath=$info[0]['savepath'].$info[0]['savename'];
		    	//缩略图生成
		    	//$thumbPicPath=$info[0]['savepath'].basename($info[0]['savename']).'-thumb.jpg';
		    	//$this->getPicThumb($this->config['rootPath'].$fullPath,50,50,1,$this->config['rootPath'].$thumbPicPath);
		    	//返回保存路径
		 		$this->ajaxReturn(array('status'=>'1','saveURL'=>$fullPath,'thumb'=>$thumbPicPath));
		    }

		    //$this->ajaxReturn(array('status'=>'1','saveURL'=>$picFile['name']));
	}


	// 单个图片上传
    public function uploadSinglePic(){

	    $picFile = $_FILES["file"];


	    $upload = new Upload($this->config);
	    $info = $upload->uploadOne($picFile);
	    if(!$info) {// 上传错误提示错误信息
	        $errStr=$upload->getError();
	        $this->ajaxReturn(array('status'=>'0','errStr'=>$errStr));
	    }else{// 上传成功 获取上传文件信息
	    	$fullPath=$info['savepath'].$info['savename'];
	    	//返回保存路径
	 		$this->ajaxReturn(array('status'=>'1','saveURL'=>$fullPath));
	    }

	}


	/**
	 * @param   $type 生成缩略图的方式
	*IMAGE_THUMB_SCALE     =   1 ; //等比例缩放类型
	*IMAGE_THUMB_FILLED    =   2 ; //缩放后填充类型
	*IMAGE_THUMB_CENTER    =   3 ; //居中裁剪类型
	*IMAGE_THUMB_NORTHWEST =   4 ; //左上角裁剪类型
	*IMAGE_THUMB_SOUTHEAST =   5 ; //右下角裁剪类型
	*IMAGE_THUMB_FIXED     =   6 ; //固定尺寸缩放类型

	**/
	private function getPicThumb($ImagePath,$width,$height,$type,$savePath){
		$image = new \Think\Image(); 
		$image->open($ImagePath);
		// 按照设定的生成方式产生缩略图
		$image->thumb($width,$height,$type)->save($savePath);
	}
	
}