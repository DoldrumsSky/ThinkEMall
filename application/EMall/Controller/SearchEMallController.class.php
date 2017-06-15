<?php
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\HomebaseController;
use vendor\PHPAnalysis;

/**
 * ThinkEMall电子商城搜索商品结果列表展示控制器
 * ============================================================================
 * 引用、修改及衍生本系统代码请保留以下信息
 * 版权所有 2016-2020 作者：阳华 ThinkEMall，并保留所有权利。 * 
 * ----------------------------------------------------------------------------
 * 项目地址：https://github.com/DoldrumsSky/ThinkEMall * 
 * 			 https://git.oschina.net/langweaver/thinkemall
 * 联系方式：
 * QQ:451343282  Email:451343282@qq.com
 * 技术交流群:1950562
 * ============================================================================
 * $Author: YangHua $
 * $Id: SearchEMallController.php 17217 2017-06-14 22:29:08Z YangHua $
*/

class SearchEMallController extends HomebaseController {
	//搜索结果显示
	public function index() {
	    $keywords=trim(I('request.keywords'));
	    $term_id=I('request.term_id',0,'intval');
	    $brand_id=trim(I('request.bfid'));
	    //排序参数
        $sort=I('get.sort');

	    if(!empty($keywords)){
	    	vendor('PHPAnalysis.phpanalysis');
    		$pa = new \PhpAnalysis('utf-8', 'utf-8', false);
    		//载入词典
    		$pa->LoadDict();
    		//执行分词
		    $pa->SetSource($keywords);
		    $pa->differMax = false;
		    $pa->unitWord = false;
		    
		    $pa->StartAnalysis( true );
		    //第一个参数为分词后的分隔符，第二个是我加的分词前缀（用于全文搜索的精确查询）
		    //第三个参数是我加的分词后缀（用于全文搜索的模糊查询），第四个参数为词性标注，默认就是flase
		    $segment = $pa->GetFinallyResult(' ','','*');
		    //dump($segment);

		    $goodsModel=M('goods');
		    $where['goods_status']=array('gt',0);
		    if(!empty($term_id)){
		    	$where['term_id']=$term_id;
		    }
		    if(!empty($brand_id)){
		    	$where['brand_id']=array('in',$brand_id);
		    }

		    $where['match(goods_keywords)']=array('exp',' against(\''.$segment.'\' IN BOOLEAN MODE)');
		    $count=$goodsModel->where($where)->count();
		    if($count>0){
		    	//排序参数设定
		        if($sort=='total'){
		            $order='permonth_sales desc';
		        }else if($sort=='appraisecount'){
		            $order='appraise_num desc';
		        }else if($sort=='dredisprice'){
		            $order='goods_price asc';
		        }else if($sort=='dredisprice'){
		            $order='post_date desc';
		        }

			    $page = new \Page($count,20);
			    $lists['goods']=$goodsModel->where($where)->limit($page->firstRow,$page->listRows)->order($order)->select();
			    $lists['total_pages']=$page->getTotalPages();
			    $lists['page']=$page->show('default');
			    $lists['count']=$count;

				unset($where['term_id'],$where['brand_id']);
			    //加载品牌数据
			    $brandFilterData['brandData']=$goodsModel->alias('A')
			    					->join('__BRAND__ B ON A.brand_id = B.brand_id')
									->field('B.brand_id,B.brand_name,B.brand_logo,B.chartext')
									->where($where)
									->group('A.brand_id')
									->select();
				$brandFilterData=json_encode($brandFilterData);
				//dump($brandFilterData);
				//加载分类数据
				$termFilterData=$goodsModel->alias('A')
			    					->join('__MALL_TERMS__ B ON A.term_id = B.term_id')
									->field('B.term_id,B.name')
									->where($where)
									->group('A.term_id')
									->select();
				//$termFilterData=json_encode($termFilterData);
				//dump($termFilterData);
		    }
		    //dump($lists);
		    
			//取购物车商品数
			$shopcart=session('user.shopcart');
			if(!in_array('',$shopcart)){
				$cartGoodsNum=count($shopcart);
			}else{
				$cartGoodsNum=0;
			}
			unset($shopcart);
	    }

    	$this->assign('cartGoodsNum',$cartGoodsNum);
    	$this->assign('brandFilterData',$brandFilterData);
    	$this->assign('term_id',$term_id);
    	$this->assign('termFilterData',$termFilterData);
    	$this->assign('lists',$lists);
    	$this->assign('keywords',$keywords);
    	$this->display(":searchResult");
	}

}
