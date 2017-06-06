<?php
namespace Common\Controller;

use Common\Controller\HomebaseController;

class MemberbaseController extends HomebaseController{
    
	protected $user_model;
	protected $user;
	protected $userid;
	protected $messageCount;
	protected $EMallConfig;

	function _initialize() {
		parent::_initialize();
		$this->check_login();
		$this->check_user();
		//by Rainfer <81818832@qq.com>
		if(sp_is_user_login()){
			$this->userid=sp_get_current_userid();
			$this->users_model=D("Common/Users");
			$this->user=$this->users_model->where(array("id"=>$this->userid))->find();
			$this->messageCount=$this->getMessageData(null,'count');
			
		}
	}

    //获取消息数据$return为data时返回数据,其它只返回消息数
    //查询未读消息数语句示例：
    //SELECT message_type,count(message_id) as total,max(message_id) as maxid FROM `cmf_mall_message` WHERE ( need_score <= 100 and message_status=1) and (message_id>5 and message_type=1) or (message_id>1 and message_type=2) GROUP BY message_type
    protected function getMessageData($message_type,$return='data'){
		if(!empty($message_type)){
        	$where['message_type']=$message_type;
        	$mapLogic='and';
        }else{
        	$mapLogic='or';
        }

        //用户积分
        $userScore=$this->user['score'];
        //查询条件
        $where['sendee']=intval($this->user['id']);
        $where['_logic']='or';

        $map['_complex']=$where;
        $map['_string']='need_score <= '.$userScore.' and message_status=1';
		$map['_logic']=$mapLogic;
        
        $messageModel=M('mall_message');

        $count=$messageModel
        ->field('message_type,count(*) as total,max(message_id) as maxid')
        ->where($map)
        ->group('message_type')
        ->select();

        $total=0;
        //当前查看消息类型的总数
        $queryCount=0;
    	//取上次查看消息时的记录数据，如果消息库存在新的数据，则进行对应的浏览记录修改
    	//以每类消息最大id值作为上次查看到的消息上限来计算未读的消息数 
    	$message_handle=json_decode($this->user['message_handle'],true);
    	//查看消息的记录数据为空的标识，为空则未读消息总数为当前消息的总数
    	$emptyMsgHandle=empty($message_handle)?true:false;
    	$hasNewMessage=false;

        //取所有类型消息的未读数
        $messageTypeData=F('EMall_Message_Type');
        if(empty($messageTypeData)){
        	F('EMall_Message_Type',array('message_type_1'=>1,'message_type_2'=>2));
        }

        //生成所有消息类型的查询条件
        $typeWhere['_complex']=array('need_score'=>array('ELT',$userScore),'message_status'=>1);
        $typeLogic='';
        foreach ($messageTypeData as $key => $value) {
        	if(!empty($message_handle[$key])){
        		$typeLogic=empty($typeWhere['_string'])?'':' or';
        		$typeWhere['_string'].=$typeLogic.' message_type='.$value.' and message_id>'.$message_handle[$key];
        	//如果没有某个消息类型的数据表示此类型消息从未被查看过，则未读数为此类消息的总数
        	}else{
        		$readData[$key]='';
        	}
        }

		$unreadNum=$messageModel
        ->field('message_type,count(*) as total')
        ->where($typeWhere)
        ->group('message_type')
        ->select();

        //取总未读消息数
        $totalUnreadNum=0;
        if(!empty($unreadNum)){
        	//重新生成一个带消息类型键名的数组
	        foreach ($unreadNum as $key => $value) {
	        	$unreadData['message_type_'.$value['message_type']]=$value;
	        	$totalUnreadNum+=$value['total'];
	        }
	        $unreadData['total']=$totalUnreadNum;
	        unset($unreadNum);
        }

        //取所有消息类型的总数
        $allCount=$messageModel
        ->field('message_type,count(*) as total,max(message_id) as maxid')
        ->where(array('need_score'=>array('ELT',$userScore),'message_status'=>1))
        ->group('message_type')
        ->select();

        //将从未查看过的消息类型未读数设为总数
        foreach ($readData as $key => $value) {
        	$type_id=substr($key,-1,1);
        	$unreadData[$key]['message_type']=$type_id;
        	//获取对应消息类型的总数并插入到未读消息统计数组中
        	foreach ($allCount as $idx => $data) {
        		if($data['message_type']==$type_id){
        			$unreadData[$key]['total']=$data['total'];
        			$totalUnreadNum+=$data['total'];
        			$unreadData['total']=$totalUnreadNum;
        		}
        	}
        }

        foreach ($allCount as $key => $value) {
        	//计算所有消息的总数
    		$total+=$value['total'];
        }
        unset($allCount);

        //消息统计与生成更新浏览记录需要的数据
        foreach ($count as $key => $value) {
        	switch ($value['message_type']) {
        		case 1:
        			$type_1_maxid=$value['maxid'];
        			$queryCount+=$value['total'];
        			//比较是否有新的消息数据，有的话记录并标识
        			if($value['maxid']!==$message_handle['message_type_1']){
        				$message_handle['message_type_1']=$value['maxid'];
        				$hasNewMessage=true;
        			}
        			break;
        		case 2:
        			$type_2_maxid=$value['maxid'];
        			$queryCount+=$value['total'];
        			//比较是否有新的消息数据，有的话记录并标识
        			if($value['maxid']!==$message_handle['message_type_2']){
        				$message_handle['message_type_2']=$value['maxid'];
        				$hasNewMessage=true;
        			}
        			break;
        		default:
        			# code...
        			break;
        	}
        }

    	if($emptyMsgHandle){
    		$totalUnreadNum=$total;
    	}

        if($return=='data'){
        	//写入新的消息查看记录
        	if($hasNewMessage==true){
        		M('users')->where(array('id'=>$this->userid))->save(array('message_handle'=>json_encode($message_handle)));
        	}

	        $page=$this->page($queryCount,10);

	        $messageData['content']=$messageModel
	        ->where($map)
	        ->limit($page->firstRow,$page->listRows)
	        ->order('set_top DESC,send_time DESC')
	        ->select();

	        if($messageData['content']!==false){
	        	$messageData['messageNum']=empty($unreadData)?0:$totalUnreadNum;
	            $messageData['page']=$page->show('default');
	            $messageData['unreadData']=$unreadData;

	            return $messageData;
	        }	        
        }else{
        	return empty($unreadData)?0:$totalUnreadNum;
        }
        return false;
    }
	
}