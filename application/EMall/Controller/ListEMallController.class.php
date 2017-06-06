<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\HomebaseController;

/**
 * ThinkEMall电子商城前端商品列表展示控制器
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
 * $Id: ListEMallController.php 17217 2017-04-09 06:29:08Z YangHua $
*/

class ListEMallController extends HomebaseController {

	// 前台商品列表
	// 列表页分顶层类目专题列表页和子类目的商品列表页，前者调用其下所有的类目数据，后者只调用自己的数据
	public function index() {
	    $term_id=I('get.id',0,'intval');
		$navid=I('get.navid',0,'intval');

	    $where['term_id']=$term_id;
	    $where['parent']=$term_id;
	    $where['_logic']='or';
		$term=sp_get_MallTerm('',$where);

		if(empty($term)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    if(sp_template_file_exists(MODULE_NAME."/404")){
		        $this->display(":404");
		    }
		    return;
		}
		//dump($term);
		//返回当前类的数据
		$filterId='';
		$termGroupId='';
		foreach ($term as $key => $value) {
			if($term_id==$value['term_id']){
				$curTerm=$value;
			}
			if($value['parent']==0){
				continue;
			}
			//生成类目、筛选项的id组准备查询
			$filterId .=empty($filterId)?$value['filter_menu']:','.$value['filter_menu'];
			$termGroupId.=empty($termGroupId)?$value['term_id']:','.$value['term_id'];
		}
		//如果当前访问的是顶层类目，则以列表专题页模式输出数据
		if($curTerm['parent']==0){
			$termData=F('Filter/EMall_Filter_Group_'.$curTerm['term_id']);
			if(empty($termData)){

				//取所有筛选项数据
				$filterData=ApiService::getTermsFilter($termGroupId,$filterId,'data');
				//整合数据
				foreach ($term as $key => $value) {
					foreach ($filterData as $fIdx => $data) {
						if($value['filter_menu']==$data['id']){
							$term[$key]['filter_menu']=$data;
							unset($filterData[$fIdx]);
							break;
						}
					}
				}
				$termData=json_encode(array('termGroupFilter'=>$term));
				unset($term);
				F('Filter/EMall_Filter_Group_'.$curTerm['term_id'],$termData);
			}
			//dump($termData);

			$tplname=$curTerm["list_tpl"];
	    	$tplname=sp_get_apphome_tpl($tplname, "list");

	    	$this->assign('termData',$termData);
	    	$this->assign('cat_id', $term_id);
	    	$this->assign('navid',$navid);
	    	//$this->assign('filterData',$filterData);
	    	$this->display(":$tplname");
	    	return;
		}

		//加载品牌和价格筛选数据
		$brandFilterData=F('EMall_Brand_Filter_'.$term_id);
		if(empty($brandFilterData) && !empty($term[0]['brand_id'])){
			$brandData=M('brand')->where(array('brand_id'=>array('in',$term[0]['brand_id'])))->field('brand_id,brand_name,brand_logo,chartext')->select();
			//排序
			$brandRankId=explode(',',$term[0]['brand_id']);
			foreach ($brandData as $key => $value) {
				foreach ($brandRankId as $idx => $rankId) {
					if($value['brand_id']==$rankId){
						$brandFilterData['brandData'][$idx]=$value;
						unset($brandData[$key]);
						break;
					}
				}
			}
			unset($brandRankId);
			//$brandFilterData['brandOrder']=$term[0]['brand_id'];
			$brandFilterData=json_encode($brandFilterData);
			F('EMall_Brand_Filter_'.$term_id,$brandFilterData);
		}
		
		//生成价格筛选项
		//$priceFilterData=F('EMall_Price_Filter_'.$term_id);
		//if(empty($priceFilterData)){

			$price_low=explode('|',$term[0]['price_low']);
			$price_mid=explode('|',$term[0]['price_mid']);
			$price_high=explode('|',$term[0]['price_high']);

			//发烧级价格（1个）
			if(!empty($price_high[0])){
				$price_supper=(array_pop(explode('-',end($price_high)))+1).'及以上';	
			}else if(!empty($price_mid[0])){
				$price_supper=(array_pop(explode('-',end($price_mid)))+1).'及以上';	
			}else if(!empty($price_high[0])){
				$price_supper=(array_pop(explode('-',end($price_high)))+1).'及以上';	
			}

			$priceFilterData=array_merge($price_low,$price_mid,$price_high);
			$priceFilterData[]=$price_supper;
			unset($price_low,$price_mid,$price_high,$price_supper);


			//$priceFilterData=F('EMall_Price_Filter_'.$term_id,$priceFilterData);
		//}

		//加载其它筛选数据
		$filterData=F('EMall_Filter_'.$term_id);
		if(empty($filterData)){
			//获取商品筛选数据
			$filterModel=M('filter');
			$result=$filterModel->where(array('term_id'=>$term_id))->order(array('listorder asc'))->select();

			if($result!==false){
				$filterTree=new \Tree();
				$filterTree->init($result);
				$filterData=$this->getFilterTree($filterTree->get_tree_array(0));
				F('EMall_filter_'.$term_id,$filterData);
			}
		}

		//dump($filterData);

		//取购物车商品数
		$shopcart=session('user.shopcart');
		if(!in_array('',$shopcart)){
			$cartGoodsNum=count($shopcart);
		}else{
			$cartGoodsNum=0;
		}
		unset($shopcart);

		$tplname=$curTerm["list_tpl"];
    	$tplname=sp_get_apphome_tpl($tplname, "list");

    	$this->assign($term);
    	$this->assign('filterData',$filterData);
    	$this->assign('brandFilterData',$brandFilterData);
    	$this->assign('priceFilterData',$priceFilterData);
    	$this->assign('cat_id', $term_id);
    	$this->assign('navid',$navid);
    	$this->assign('cartGoodsNum',$cartGoodsNum);
    	$this->display(":$tplname");
	}
	
	// 商品类目列表接口,返回商品类目列表,用于后台导航编辑添加
	public function nav_index($returnType="normal"){
		$navcatname="电子商城类目";
        $term_obj= M("mall_terms");

        $where=array();
        $where['status'] = array('eq',1);
        $terms=$term_obj->field('term_id,name,parent')->where($where)->order('term_id')->select();
		$datas=$terms;
		$navrule = array(
		    "id"=>'term_id',
            "action" => "EMall/ListEMall/index",
            "param" => array(
                "id" => "term_id"
            ),
            "label" => "name",
		    "parentid"=>'parent'
        );
		
		return sp_get_nav4admin($navcatname,$datas,$navrule) ;	

	}

    //取得树形结构的菜单
    public function getFilterTree($filterData,$parent = "", $Level = 1) {
    	$Level++;
        if (is_array($filterData)) {
        	$ret=NULL;
            foreach ($filterData as $a) {
                $id = $a['id'];
                $name=$a['filter_name'];
                $array = array(
                    "id" => $id,
                    "name" => $a['filter_name'],
                    "parent" => $parent,
                    "multiselect"=>$a['multiselect']
                ); 

                $ret[$id] = $array;
                $child = $this->getFilterTree($a['child'],$a['id'], $id, $Level);
                //由于后台管理界面只支持三层，超出的层级的不显示
                if ($child && $Level <= 2) {
                    $ret[$id]['items'] = $child;
                }
               
            }
            return $ret;
        }
       
        return false;
    }

}
