<?php
/**
 * 验证码处理
 */
namespace Api\Controller;

use Think\Controller;

class CheckcodeController extends Controller {

    public function index() {
    	
    	$length=4;
    	if (isset($_GET['length']) && intval($_GET['length'])>2){
    		$length = intval($_GET['length']);
    	}
    	
    	//设置验证码字符库
    	$code_set="";
    	if(!empty($_GET['charset'])){
    	    $mletters=str_split($_GET['charset']);
    	    $mletters=array_unique($mletters);
    	    if(count($mletters)>5){
    	        $code_set= trim($_GET['charset']);
    	    }
    	}
    	$use_noise=1;
    	if(isset($_GET['use_noise'])){
    		$use_noise= intval($_GET['use_noise']);
    	}
    	
    	$use_curve=1;
    	if(isset($_GET['use_curve'])){
    		$use_curve= intval($_GET['use_curve']);
    	}
    	
    	$font_size=25;
    	if (isset($_GET['font_size']) && intval($_GET['font_size'])){
    		$font_size = intval($_GET['font_size']);
    	}
    	
    	$width=0;
    	if (isset($_GET['width']) && intval($_GET['width'])){
    		$width = intval($_GET['width']);
    	}
    	
    	$height=0;
    		
    	if (isset($_GET['height']) && intval($_GET['height'])){
    		$height = intval($_GET['height']);
    	}
    	
    	$background=array(243, 251, 254);
    	if (isset($_GET['background'])){
    	    $mbackground=array_map('intval', explode(',', $_GET['background']));
    	    if(count($mbackground)>2 && $mbackground[0]<=255 && $mbackground[1]<=255 && $mbackground[2]<=255){
    	        $background=array( $mbackground[0],$mbackground[1],$mbackground[2] );
    	    }
    	}

    	$config = array(
	        'codeSet'   =>  !empty($code_set)?$code_set:"2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY",             // 验证码字符集合
	        'expire'    =>  1800,            // 验证码过期时间（s）
	        'useImgBg'  =>  false,           // 使用背景图片 
	        'fontSize'  =>  !empty($font_size)?$font_size:25,              // 验证码字体大小(px)
	        'useCurve'  =>  $use_curve===0?false:true,           // 是否画混淆曲线
	        'useNoise'  =>  $use_noise===0?false:true,            // 是否添加杂点	
	        'imageH'    =>  $height,               // 验证码图片高度
	        'imageW'    =>  $width,               // 验证码图片宽度
	        'length'    =>  !empty($length)?$length:4,               // 验证码位数
	        'bg'        =>  $background,  // 背景颜色
	        'reset'     =>  true,           // 验证成功后是否重置
    	);
    	$Verify = new \Think\Verify($config);
    	$Verify->entry();
    }
    

}

