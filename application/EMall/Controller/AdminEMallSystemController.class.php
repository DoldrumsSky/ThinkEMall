<?php
/**
 * ThinkEMall电子商城系统配置管理控制器
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
 * $Id: AdminEMallSystemController.php 17217 2017-04-09 06:29:08Z YangHua $
*/

namespace EMall\Controller;

use EMall\Service\ApiService;
use Common\Controller\AdminbaseController;
use EMall\Service\ChromePhp;

class AdminEMallSystemController extends AdminbaseController {

    protected $messageModel;

    public function _initialize() {
        parent::_initialize();
        $this->messageModel = M("mall_message");

    }

    //加载配置页面
    public function systemConfig(){
        //dump(ApiService::getEMallConfig());
        $configModel=M('mall_config');
        $configData=$configModel->select();
        if(!empty($configData)){
            foreach ($configData as $key => $value) {
                $configData[$key]['config_option']=json_decode($value['config_option'],true);
            }
        }else{
            $this->error('无法获取商品配置数据，请稍候尝试！');
        }
        $this->assign('configData',$configData);
        $this->display();
    }

    //保存商城配置
    public function saveEMallConfig(){
        if(IS_POST){
            $saveData=I('post.post');
            //dump($saveData);
            $configModel=M('mall_config');
            $configModel->startTrans();
            foreach ($saveData as $key => $value) {
                //ChromePhp::log($key);
                $value=json_encode($value);
                $configData=$configModel->where(array('config_id'=>$key))->save(array('config_option'=>$value));
                if($configData===false){
                    $configModel->rollback();
                    $this->error('无法保存商品配置数据，请稍候尝试！');
                }
                foreach ($saveData[$key] as $name => $options) {
                    //生成配置的键值对
                    $config[$name]=$options['value'];
                }
                //如果配置ID为1（也就是系统配置），生成快速缓存，其它支付配置不生成快速缓存
                if($key==1){
                    F('Think_EMall_Config_'.$key,$config);
                }
            }
            $configModel->commit();
            $this->success('修改配置保存成功！');            
        }
    }

    // 消息列表（如果消息类型有改变，请自行修正前端查看消息类型缓存的数据，目前是在User/center控制器中调用）
    // F('EMall_Message_Type',array('message_type_1'=>1,'message_type_2'=>2));
    public function messageView() {
        $type_id=I('request.type_id',0,'intval');
        if(!empty($type_id)){
            $where['message_type']=$type_id;
        }

        $start_time=I('request.start_time');
        if(!empty($start_time)){
            $where['send_time']=array(
                array('EGT',$start_time)
            );
        }
        
        $end_time=I('request.end_time');
        if(!empty($end_time)){
            if(empty($where['send_time'])){
                $where['send_time']=array();
            }
            array_push($where['send_time'], array('ELT',$end_time));
        }

        $keywords=I('request.keyword');
        if(!empty($keywords)){
            $where['message_title']=array('like','%'.$keywords.'%');
        }

        $count=$this->messageModel
        ->alias('b')
        ->join('__USERS__ a ON a.id = b.sender_id')
        ->field('a.id,a.user_nicename,b.*')
        ->where($where);

        $page = $this->page($count, 20);

        $messageData = $this->messageModel
        ->limit($page->firstRow,$page->listRows)
        ->order("send_time DESC")
        ->select();

        $this->assign('type_id',$type_id);
        $this->assign('page',$page->show('Admin'));
        $this->assign("messageData",$messageData);
        $this->display();
    }

    //添加推送消息
    public function addMessage(){
        $this->display();
    }

    //保存推送消息
    public function addPostMessage(){
        if(IS_POST){
            $messageData=I('post.');
            $messageData['sender_id']=sp_get_current_admin_id();
            if ($this->messageModel->add($messageData)!==false) {
                    $this->success('添加推送消息成功！');
            }
            $this->error('添加推送消息失败！');
        }
    }

    //编辑推送消息
    public function editMessage(){
        $message_id=I('get.id',0,'intval');

        $messageData=$this->messageModel->where(array('message_id'=>$message_id))->find();
        if($messageData!==false){
            $this->assign('messageData',$messageData);
        }else{
            $this->error('无法编辑此消息，请稍候尝试！');
        }
        $this->display();
    }

    //保存编辑的推送消息
    public function editPostMessage(){
         if(IS_POST){
            $messageData=I('post.');
            $messageData['message_id']=I('get.id',0,'intval');
            $messageData['sender']=sp_get_current_admin_id();
            if($this->messageModel->create($messageData)!==false){
                if ($this->messageModel->save()!==false) {
                    $this->success('保存推送消息成功！');
                }
            }
            $this->error('保存推送消息失败！');
        }       
    }

 

    // 删除消息
    public function deleteMessage(){
        if(isset($_GET['id'])){
            $message_id = I("get.id",0,'intval');
            if ($this->messageModel->where(array('message_id'=>$message_id))->delete() !==false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
        
        if(isset($_POST['ids'])){
            $ids = I('post.ids/a');
            
            if ($this->messageModel->where(array('message_id'=>array('in',$ids)))->delete()!==false) {
                $this->success("删除成功！");
            } else {
                $this->error("删除失败！");
            }
        }
    }

    //清除过期消息
    public function deleteExpiredMessage(){
        $configData=ApiService::getEMallConfig();
        $where['_string']='TO_DAYS(NOW())- TO_DAYS(send_time)>'.$configData['MESSAGE_EXPIRE'];
        if ($this->messageModel->where($where)->delete()!==false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

}
