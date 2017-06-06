<?php
$configs = array(
    'TAGLIB_BUILD_IN' => THINKCMF_CORE_TAGLIBS . ',Portal\Lib\Taglib\Portal',
    'HTML_CACHE_RULES' => array(
        // 定义静态缓存规则
        // 定义格式1 数组方式
        'article:index' => array('portal/article/{id}',600),
        'index:index' => array('portal/index',600),
        'list:index' => array('portal/list/{id}_{p}',60)
    ),
    'GOODS_COLOR'=>array(
    	'white'=>'白色',
    	'black'=>'黑色',
        'gray'=>'灰色',
        'blue'=>'蓝色',
        'red'=>'红色',
        'orange'=>'橙色',
        'yellow'=>'黄色',
        'golden'=>'金色',
        'silvery'=>'银色', 
        'green'=>'绿色',
        'cyan'=>'青色',       
        'pink'=>'粉色',
        'purple'=>'紫色',       
        'magenta'=>'洋红'
    ),
    'GOODS_SPEC'=>array(
    	'SPEC-1'=>'规格一',
    	'SPEC-2'=>'规格二',
    	'SPEC-3'=>'规格三',
        'SPEC-4'=>'规格三',
        'SPEC-5'=>'规格三',
        'SPEC-6'=>'规格三',
        'SPEC-7'=>'规格三'
    ),
    'EMALL_SHOWPIC_NUM'=>5
);

return array_merge($configs);
