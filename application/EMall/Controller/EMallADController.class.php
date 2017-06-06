<?php

namespace EMall\Controller;
use EMall\Service\ApiService;
use Common\Controller\HomebaseController; 
/**
 * ThinkEMall前端获取广告数据的控制器
 * ============================================================================
 * 引用、修改及衍生本系统代码请保留以下信息
 * 版权所有 2016-2020 作者：阳华 ThinkEMall，并保留所有权利。 * 
 * ----------------------------------------------------------------------------
 * 项目地址：https://github.com/DoldrumsSky/ThinkEMall * 
 * 联系方式：
 * QQ:451343282  Email:451343282@qq.com
 * 技术交流群:1950562
 * ============================================================================
 * $Author: YangHua $
 * $Id: EMallADController.php 17217 2017-04-09 06:29:08Z YangHua $
*/
class EMallADController extends HomebaseController {

	//根据广告类id获取对应的广告数据(返回类型为Json文本)
	public function getSpecifyAdData($id,$returnType){
        $id=explode(',',I('post.id'));
        $returnType=I('post.returnType',0,intval);
		$adListData=ApiService::getSpecifyAdData($id,$returnType);
		if($adListData!==false){
			if($returnType==0){
				$this->ajaxReturn(array('status'=>1,'data'=>$adListData['adListJson'],'curServerTime'=>date('Y-m-d'),'goodsData'=>$adListData['goodsData']));
			}else{
				return $adListData;
			}
		}else{
			if($returnType==0){
				$this->ajaxReturn(array('status'=>0,'error'=>'广告数据加载失败'));
			}else{
				return $adListData;
			}			
		}
	}

}


