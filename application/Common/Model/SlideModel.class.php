<?php
namespace Common\Model;
use Common\Model\CommonModel;
class SlideModel extends  CommonModel{
	
	//自动验证
	protected $_validate = array(
		//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
		array('slide_name', 'require', '名称不能为空！', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
	);
	
	protected $_auto = array (
	    array ('slide_pic', 'sp_asset_relative_url', self::MODEL_BOTH, 'function'),
	);
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
	
}