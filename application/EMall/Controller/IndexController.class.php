<?php
/*
 *      _______ _     _       _     _____ __  __ ______
 *     |__   __| |   (_)     | |   / ____|  \/  |  ____|
 *        | |  | |__  _ _ __ | | _| |    | \  / | |__
 *        | |  | '_ \| | '_ \| |/ / |    | |\/| |  __|
 *        | |  | | | | | | | |   <| |____| |  | | |
 *        |_|  |_| |_|_|_| |_|_|\_\\_____|_|  |_|_|
 */
/*
 *     _________  ___  ___  ___  ________   ___  __    ________  _____ ______   ________
 *    |\___   ___\\  \|\  \|\  \|\   ___  \|\  \|\  \ |\   ____\|\   _ \  _   \|\  _____\
 *    \|___ \  \_\ \  \\\  \ \  \ \  \\ \  \ \  \/  /|\ \  \___|\ \  \\\__\ \  \ \  \__/
 *         \ \  \ \ \   __  \ \  \ \  \\ \  \ \   ___  \ \  \    \ \  \\|__| \  \ \   __\
 *          \ \  \ \ \  \ \  \ \  \ \  \\ \  \ \  \\ \  \ \  \____\ \  \    \ \  \ \  \_|
 *           \ \__\ \ \__\ \__\ \__\ \__\\ \__\ \__\\ \__\ \_______\ \__\    \ \__\ \__\
 *            \|__|  \|__|\|__|\|__|\|__| \|__|\|__| \|__|\|_______|\|__|     \|__|\|__|
 */
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace EMall\Controller;
use Common\Controller\HomebaseController; 
/**
 * 首页
 */
class IndexController extends HomebaseController {

	public function index() {
		//加载类目组广告
		$adListJson=F('EMall_TermsAdListJson');
		if(empty($adListJson)){
			$ad_model=M('mall_group_ad');
			$adData=$ad_model->where(array('ad_status'=>1,'ad_home'=>1))->order('listorder asc')->select();
			//手工生成json文本
			foreach ($adData as $key => $value) {
				$value['ad_cover']=empty($value['ad_cover'])?'""':$value['ad_cover'];
				$value['ad_data']=empty($value['ad_data'])?'""':$value['ad_data'];
				$curData='"'.$key.'":{"ad_name":"'.$value['ad_name'].'","sub_name":'.'"'.$value['sub_name'].'","ad_type":'.$value['ad_type'].',"ad_container":"'.$value['ad_container'].'","ad_cover":'.$value['ad_cover'].',"ad_data":'.$value['ad_data'].'}';
				$adListJson.=$key==0?$curData:','.$curData;
			}
			$adListJson='{'.$adListJson.'}';
			F('EMall_TermsAdListJson',$adListJson);
		}
		$this->assign('curServerTime',date('Y-m-d H:i:s'));
		$this->assign('adData',$adListJson);
    	$this->display(":index");
    }

}


