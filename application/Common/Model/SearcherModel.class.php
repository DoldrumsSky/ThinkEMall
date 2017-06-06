<?php

/* * 
 * 菜单
 */
namespace Common\Model;
use Common\Model\CommonModel;
class SearcherModel extends CommonModel {

    protected $tableName = 'filter';
    protected $term_id=0;

    //自动验证
    protected $_validate = array(
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
        array('filter_name', 'require', '筛选类名不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH ),
        array('term_id', 'require', '筛选项所在商品类目不能为空！', 1, 'regex', CommonModel:: MODEL_BOTH )
    );
    //自动完成
    protected $_auto = array(
            //array(填充字段,填充内容,填充条件,附加规则)
    );


    //验证action是否重复添加
    public function checkAction($data) {
        //检查是否重复添加
        $find = $this->where($data)->find();
        if ($find) {
            return false;
        }
        return true;
    }
    //验证action是否重复添加
    public function checkActionUpdate($data) {
    	//检查是否重复添加
    	$id=$data['id'];
    	unset($data['id']);
    	$find = $this->field('id')->where($data)->find();
    	if (isset($find['id']) && $find['id']!=$id) {
    		return false;
    	}
    	return true;
    }
    

    /**
     * 按父ID查找其下筛选项
     * @param integer $parentid   父菜单ID  
     * @param integer $with_self  是否包括他自己
     */
    public function admin_filter($parentid, $with_self = false) {
        //父节点ID
        $parentid = (int) $parentid;
        $result = $this->where(array('parentid' => $parentid, 'status' => 1))->order(array("listorder" => "ASC"))->select();
        if ($with_self) {
            $result2[] = $this->where(array('id' => $parentid))->find();
            $result = array_merge($result2, $result);
        }

        return $result;        
    }

    /**
     * 获取菜单 头部菜单导航
     * @param $parentid 菜单id
     */
    public function submenu($parentid = '', $big_menu = false) {
        $array = $this->admin_filter($parentid, 1);
        $numbers = count($array);
        if ($numbers == 1 && !$big_menu) {
            return '';
        }
        return $array;
    }

    /**
     * 菜单树状结构集合
     */
    public function filter_json() {
        $data = $this->get_tree(0);
        return $data;
    }

    //取得树形结构的菜单
    public function get_tree($myid, $parent = "", $Level = 1) {
        $data = $this->admin_filter($myid);
        $Level++;
        if (is_array($data)) {
            $ret = NULL;
            foreach ($data as $a) {
                $id = $a['id'];
                $name=$a['filter_name'];
                $array = array(
                    "id" => $id,
                    "name" => $a['filter_name'],
                    "parent" => $parent,
                    "multiselect"=>$a['multiselect']
                ); 

                $ret[$id] = $array;
                $child = $this->get_tree($a['id'], $id, $Level);
                //由于后台管理界面只支持三层，超出的层级的不显示
                if ($child && $Level <= 2) {
                    $ret[$id]['items'] = $child;
                }
               
            }
            return $ret;
        }
       
        return false;
    }

    /**
     * 更新缓存
     * @param type $data
     * @return type
     */
    public function filter_cache($data = null) {
        if (empty($data)) {
            $data = $this->select();
        } 
        F("EMall_Filter_".$term_id, $data);
        return $data;
    }

    /**
     * 后台有更新/编辑则删除缓存
     * @param type $data
     */
    public function _before_write(&$data) {
        parent::_before_write($data);
        F("EMall_Filter_".$term_id, NULL);
    }

    //删除操作时删除缓存
    public function _after_delete($data, $options) {
        parent::_after_delete($data, $options);
        $this->_before_write($data);
    }
    
    public function filterOption($parentid, $with_self = false){
    	//父节点ID
    	$parentid = (int) $parentid;
    	$result = $this->where(array('parentid' => $parentid))->select();
    	if ($with_self) {
    		$result2[] = $this->where(array('id' => $parentid))->find();
    		$result = array_merge($result2, $result);
    	}
    	return $result;
    }
    /**
     * 得到某筛选类所有的筛选项，包括自己
     * @param number $parentid 
     */
    public function get_filter_tree($parentid=0,$termId=0){
    	$filters=$this->where(array("parentid"=>$parentid,'term_id'=>$termId))->order(array("listorder"=>"ASC"))->select();
    	
    	if($filters){
    		foreach ($filters as $key=>$options){
    			$children=$this->get_filter_tree($options['id'],$termId);
    			if(!empty($children)){
    				$filters[$key]['children']=$children;
    			}
    			unset($filters[$key]['id']);
    			unset($filters[$key]['parentid']);
    		}
    		return $filters;
    	}else{
    		return $filters;
    	}
    	
    }

}