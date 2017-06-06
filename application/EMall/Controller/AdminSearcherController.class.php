<?php
/**
 * ThinkEMall电子商城filter商品筛选项管理控制器
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
 * $Id: AdminSearcherController.php 17217 2017-04-09 06:29:08Z YangHua $
*/

namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;

class AdminSearcherController extends AdminbaseController {

    protected $searcherModel;
    protected $auth_rule_model;


    public function _initialize() {
        parent::_initialize();
        $this->searcherModel = D("Common/Searcher");
        $this->auth_rule_model = D("Common/AuthRule");

    }

    // 筛选项列表
    public function index() {
        $term_id=I('get.term_id',0,'intval');
        $postTermId=I('post.term_id',0,'intval');
        $keywords=I('post.keyword');

        if(!empty($term_id) || !empty($postTermId)){
            if(empty($term_id)){
                $term_id=$postTermId;
            }
            $this->$term_id=$term_id;
            $where['a.term_id']=$term_id;
        }

        if(!empty($keywords)){
            unset($where);
            $where['match(b.term_keywords)']=array('exp','against(\''.$keywords.'\')');
        }

        //管理主页面只加载第一个商品类目的筛选项
        if(empty($term_id) && empty($keywords) && empty($postTermId)){
            $firstData=$this->searcherModel
            ->field('term_id')
            ->where(array('parentid'=>0))
            ->order('listorder asc')
            ->find();

            $term_id=$firstData['term_id'];
            $where['a.term_id']=$term_id;
        }

        $result = $this->searcherModel
        ->alias('a')
        ->join('__MALL_TERMS__ b ON b.term_id = a.term_id')
        ->field(
            'a.*,
            b.term_id as tid,
            b.name')
        ->where($where)
        ->order(array("a.listorder" => "ASC"))
        ->select();

        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        
        $newfilters=array();
        foreach ($result as $m){
        	$newfilters[$m['id']]=$m;
        	 
        }

        foreach ($result as $n=> $r) {
   	
        	//$result[$n]['level'] = $this->_get_level($r['id'], $newmenus);
        	$result[$n]['parentid_node'] = ($r['parentid']) ? ' class="child-of-node-' . $r['parentid'] . '"' : '';        	
        	$result[$n]['style'] = empty($r['parentid']) ? '' : 'display:none;';

            $addFilterHtml=$r['parentid']==0?'<a href="' . U("AdminSearcher/addFilterOption", array("parentid" => $r['id'], "term_id" => $r['term_id'])) . '">'.L('ADD_FILTER').'</a> | ':'';
            $editFilterPage=$r['parentid']==0?'editFilter':'editFilterOption';

            $result[$n]['str_manage'] = $addFilterHtml.'<a href="' . U("AdminSearcher/". $editFilterPage, array("id" => $r['id'], "term_id" => $r['term_id'],'parentid'=>$r['parentid'])) . '">'.L('EDIT').'</a> | <a class="js-ajax-delete" href="' . U("AdminSearcher/delete", array("id" => $r['id'], "term_id" => $r['term_id'],"parentid"=>$r['parentid']) ). '">'.L('DELETE').'</a>';

            $result[$n]['status'] = $r['status'] ? L('DISPLAY') : L('HIDDEN');
            $result[$n]['goods_required']=$r['goods_required'] ? '是' : '否';
        }

        $tree->init($result);
        $str = "<tr id='node-\$id' \$parentid_node style='\$style'>
					<td style='padding-left:20px;'><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td>\$id</td>
        			<td>\$spacer\$filter_name</td>
					<td>\$name</td>
                    <td>\$goods_required</td>
				    <td>\$status</td>
					<td>\$str_manage</td>
				</tr>";

        $categorys = $tree->get_tree(0, $str);
        unset($result);

        $filterOption=$this->getMallTermsOption($term_id);
        
        $this->assign("categorys", $categorys);
        $this->assign('term_id',$term_id);
        $this->assign("filterOption",$filterOption);
        $this->display();
    }
    
    // 后台所有菜单列表
    public function lists(){
    	session('admin_menu_index','Menu/lists');
    	$result = $this->searcherModel->order(array("app" => "ASC","model" => "ASC","action" => "ASC"))->select();
    	$this->assign("menus",$result);
    	$this->display();
    }

    public function addFilter(){
        $term_id=I('get.term_id',0,'intval');

        $termsOption=$this->getMallTermsOption($term_id);

        $this->assign('term_id',$term_id);
        $this->assign("termsOption",$termsOption);
        $this->display();
    }

    // 添加筛选项
    public function addFilterOption() {
    	$parentid = I("get.parentid",0,'intval');
        $term_id=I('get.term_id',0,'intval');

    	$result = $this->searcherModel->where(array('term_id'=>$term_id,'parentid'=>0))->field('id,term_id,filter_name')->select();

        foreach ($result as $key => $value) {
            if(empty($term_id)){
                $filterOption.='<option value="'.$value['id'].'">'.$value['filter_name'].'</option>';
            }else{
                if($parentid==$value['id']){
                    $filterOption.='<option value="'.$value['id'].'" selected>'.$value['filter_name'].'</option>';
                }else{
                    $filterOption.='<option value="'.$value['id'].'">'.$value['filter_name'].'</option>';
                }
            }
        }
    	$this->assign("filterOption", $filterOption);
        $this->assign('term_id',$term_id);
    	$this->display();
    }
    
    // 提交添加的筛选项
    public function postFilterOption() {
    	if (IS_POST) {
    		if ($this->searcherModel->create()!==false) {
                $term_id=I('post.term_id',0,'intval');
    			if ($this->searcherModel->add()!==false) {
                    F('EMall_Term_Filter_'.$term_id,null); 				
    				$this->success("添加成功！", U('AdminSearcher/index',array('term_id'=>$term_id)));
    			} else {
    				$this->error("添加失败！", U('AdminSearcher/index',array('term_id'=>$term_id)));
    			}
    		} else {
    			$this->error($this->searcherModel->getError());
    		}
    	}
    }

    // 后台菜单删除
    public function delete() {
        $id = I("get.id",0,'intval');
        $parentid=I('get.parentid',0,'intval');
        $term_id=I('post.term_id',0,'intval');

        //删除整个筛选类
        if($parentid==0){
            if ($this->searcherModel->where(array('id'=>$id,array('or',array('parentid'=>$id))))->delete($id)!==false) {
                F('EMall_Term_Filter_'.$term_id,null);
                $this->success("删除筛选类成功！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            } else {
                $this->error("删除失败！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            }            
        }else{
            if ($this->searcherModel->delete($id)!==false) {
                F('EMall_Term_Filter_'.$term_id,null);
                $this->success("删除筛选项成功！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            } else {
                $this->error("删除失败！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            }            
        }
    }

    // 编辑筛选类
    public function editFilter() {
        $id = I("get.id",0,'intval');
        $term_id=I('get.term_id',0,'intval');

        $termsOption=$this->getMallTermsOption($term_id);
        $result=$this->searcherModel->where(array('id'=>$id))->find();

        $this->assign('termsOption',$termsOption);
        $this->assign('filterData',$result);
        $this->display();
    }

    // 编辑筛选项
    public function editFilterOption() {
        $id = I("get.id",0,'intval');
        $term_id=I('get.term_id',0,'intval');
        $parentid = I("get.parentid",0,'intval');

        $filterOption=$this->getTermsFilter($term_id,$parentid);

        $result=$this->searcherModel->where(array('id'=>$id))->find();

        $this->assign('filterOption',$filterOption);
        $this->assign('filterData',$result);
        $this->assign('term_id',$term_id);
        $this->display();
    }
    
    //提交筛选编辑
    public function postEditFilter(){
        if (IS_POST) {
            $id=I('get.id',0,'intval');

            //编辑筛选类时此值为空
            $parentid=I('post.parentid',0,'intval');

            $term_id=$updateData['term_id']=I('post.term_id',0,'intval');
            $updateData['listorder']=I('post.listorder',0,'intval');
            $updateData['status']=I('post.status',0,'intval');
            $updateData['multiselect']=I('post.multiselect',0,'intval');
            $updateData['filter_name']=I('post.filter_name');
            $updateData['goods_required']=I('post.goods_required');

            if(!empty($parentid)){                
                $updateData['parentid']=$parentid;
            }

            $this->searcherModel->startTrans();
            if ($this->searcherModel->where(array('id'=>$id))->save($updateData)!==false) {
                if(empty($parentid)){
                    unset($updateData);
                    $updateData['term_id']=$term_id;
                    $updateData['parentid']=$id;
                    if($this->searcherModel->where(array('parentid'=>$id))->save($updateData)===false){
                        $this->searcherModel->rollback();
                    }
                }
                $this->searcherModel->commit();
                F('EMall_Term_Filter_'.$term_id,null);
                $this->success("编辑成功！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            } else {
                $this->searcherModel->rollback();
                $this->error("编辑失败！", U('AdminSearcher/index',array('term_id'=>$term_id)));
            }

        }
    }
 
    // 筛选项排序
    public function listorders() {
        $status = parent::_listorders($this->searcherModel);
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }

    //获取当前商品类目下所有的筛选类(返回下拉列表选项html)
    protected function getTermsFilter($term_id,$parentid){
        $result = $this->searcherModel->where(array('term_id'=>$term_id,'parentid'=>0))->field('id,term_id,filter_name')->select();

        foreach ($result as $key => $value) {
            if(empty($term_id)){
                $filterOption.='<option value="'.$value['id'].'">'.$value['filter_name'].'</option>';
            }else{
                if($parentid==$value['id']){
                    $filterOption.='<option value="'.$value['id'].'" selected>'.$value['filter_name'].'</option>';
                }else{
                    $filterOption.='<option value="'.$value['id'].'">'.$value['filter_name'].'</option>';
                }
            }
        }
        return $filterOption;
    }

    //用于外部获取对应类目的所有筛选项(ajax返回与上面的方法以及API里面的函数名相差一个s，不要混淆)
    public function getTermFilter(){
        if(IS_POST){
            $term_id=I('post.term_id',0,'intval');
            $filterData=ApiService::getTermsFilter($term_id);
            $this->ajaxReturn(array('status'=>1,'data'=>$filterData));
        }
    }
    
    //获取所有在用的商品类目并返回option的html,$selTermId:如果此类目ID值不为空，则让其成为select中的默认选中项
    protected function getMallTermsOption($selTermId){
        $filterOption='';
        //加载所有展示商品的类目
        $termsModel=M('mall_terms');
        $terms=$termsModel->where(array('term_type'=>1,'status'=>1))->field('term_id,term_type,name')->select();
        foreach ($terms as $key => $value) {
            if(empty($selTermId)){
                $filterOption.='<option value="'.$value['term_id'].'">'.$value['name'].'</option>';
            }else{
                if($selTermId==$value['term_id']){
                    $filterOption.='<option value="'.$value['term_id'].'" selected>'.$value['name'].'</option>';
                }else{
                    $filterOption.='<option value="'.$value['term_id'].'">'.$value['name'].'</option>';
                }
            }
        }
        return $filterOption;
    }

}
