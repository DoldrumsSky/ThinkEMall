<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class ProfileController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
        $messageData['messageNum']=$this->messageCount;
        $this->assign('messageData',$messageData);
	}
	
    // 编辑用户资料
	public function edit() {
		$this->assign($this->user);
    	$this->display();
    }
    
    // 编辑用户资料提交
    public function edit_post() {
    	if(IS_POST){
    		$_POST['id']=$this->userid;
    		if ($this->users_model->field('id,user_nicename,sex,birthday,user_url,signature,mobile')->create()!==false) {
				if ($this->users_model->save()!==false) {
					$this->user=$this->users_model->find($this->userid);
					sp_update_current_user($this->user);
					$this->success("保存成功！",U("user/profile/edit"));
				} else {
					$this->error("保存失败！");
				}
			} else {
				$this->error($this->users_model->getError());
			}
    	}
    	
    }
    
    // 个人中心修改密码
    public function password() {
		$this->assign($this->user);
    	$this->display();
    }
    
    // 个人中心修改密码提交
    public function password_post() {
    	if (IS_POST) {
    	    $old_password=I('post.old_password');
    		if(empty($old_password)){
    			$this->error("原始密码不能为空！");
    		}
    		
    		$password=I('post.password');
    		if(empty($password)){
    			$this->error("新密码不能为空！");
    		}
    		
    		$uid=sp_get_current_userid();
    		$admin=$this->users_model->where(array('id'=>$uid))->find();
    		if(sp_compare_password($old_password, $admin['user_pass'])){
    			if($password==I('post.repassword')){
    				if(sp_compare_password($password, $admin['user_pass'])){
    					$this->error("新密码不能和原始密码相同！");
    				}else{
    					$data['user_pass']=sp_password($password);
    					$data['id']=$uid;
    					$r=$this->users_model->save($data);
    					if ($r!==false) {
    						$this->success("修改成功！");
    					} else {
    						$this->error("修改失败！");
    					}
    				}
    			}else{
    				$this->error("密码输入不一致！");
    			}
    	
    		}else{
    			$this->error("原始密码不正确！");
    		}
    	}
    	 
    }
    
    // 第三方账号绑定
    public function bang(){
    	$oauth_user_model=M("OauthUser");
    	$uid=sp_get_current_userid();
    	$oauths=$oauth_user_model->where(array("uid"=>$uid))->select();
    	$new_oauths=array();
    	foreach ($oauths as $oa){
    		$new_oauths[strtolower($oa['from'])]=$oa;
    	}
    	$this->assign("oauths",$new_oauths);
    	$this->display();
    }
    
    // 用户头像编辑
    public function avatar(){
		$this->assign($this->user);
    	$this->display();
    }
    
    // 用户头像上传
    public function avatar_upload(){
    	$config=array(
			'rootPath' => './'.C("UPLOADPATH"),
			'savePath' => './avatar/',
			'maxSize' => 512000,//500K
			'saveName'   =>    array('uniqid',''),
			'exts'       =>    array('jpg', 'png', 'jpeg'),
			'autoSub'    =>    false,
    	);
    	$upload = new \Think\Upload($config,'Local');//先在本地裁剪
    	$info=$upload->upload();
    	//开始上传
    	if ($info) {
    	//上传成功
    	//写入附件数据库信息
    		$first=array_shift($info);
    		$file=$first['savename'];
    		session('avatar',$file);
    		$this->ajaxReturn(sp_ajax_return(array("file"=>$file),"上传成功！",1),"AJAX_UPLOAD");
    	} else {
    		//上传失败，返回错误
    		$this->ajaxReturn(sp_ajax_return(array(),$upload->getError(),0),"AJAX_UPLOAD");
    	}
    }
    
    // 用户头像裁剪
    public function avatar_update(){
        $session_avatar=session('avatar');
    	if(!empty($session_avatar)){
    		$targ_w = I('post.w',0,'intval');
    		$targ_h = I('post.h',0,'intval');
    		$x = I('post.x',0,'intval');
    		$y = I('post.y',0,'intval');
    		$jpeg_quality = 90;
    		
    		$avatar=$session_avatar;
    		$avatar_dir=C("UPLOADPATH")."avatar/";
    		
    		$avatar_path=$avatar_dir.$avatar;
    		
    		$image = new \Think\Image();
    		$image->open($avatar_path);
    		$image->crop($targ_w, $targ_h,$x,$y);
    		$image->save($avatar_path);
    		
    		$result=true;
    		
    		$file_upload_type=C('FILE_UPLOAD_TYPE');
    		if($file_upload_type=='Qiniu'){
    		    $upload = new \Think\Upload();
    		    $file=array('savepath'=>'','savename'=>'avatar/'.$avatar,'tmp_name'=>$avatar_path);
    		    $result=$upload->getUploader()->save($file);
    		}
    		if($result===true){
    		    $userid=sp_get_current_userid();
    		    $result=$this->users_model->where(array("id"=>$userid))->save(array("avatar"=>'avatar/'.$avatar));
    		    session('user.avatar','avatar/'.$avatar);
    		    if($result){
    		        $this->success("头像更新成功！");
    		    }else{
    		        $this->error("头像更新失败！");
    		    }
    		}else{
    		    $this->error("头像保存失败！");
    		}
    		
    	}
    }
    
    // 保存用户头像
    public function do_avatar() {
		$imgurl=I('post.imgurl');
		//去'/'
		$imgurl=str_replace('/','',$imgurl);
		$old_img=$this->user['avatar'];
		$this->user['avatar']=$imgurl;
		$res=$this->users_model->where(array("id"=>$this->userid))->save($this->user);		
		if($res){
			//更新session
			session('user',$this->user);
			//删除旧头像
			sp_delete_avatar($old_img);
		}else{
			$this->user['avatar']=$old_img;
			//删除新头像
			sp_delete_avatar($imgurl);
		}
		$this->ajaxReturn($res);
	} 

    //收货地址设置
    public function addressOption(){
        $result=$this->users_model->where(array('id'=>$this->userid))->field('address')->find();
        if(!empty($result['address'])){
            $result=json_decode($result['address']);
            $this->assign('addressList',$result);
        }
        $this->display('address');
    }

    //提交地址内容
    public function addAddress(){

        //session('user.addr_enable',1);
        //检查是否超出添加数量
        if(session('user.addr_enable')==0){
            $this->ajaxReturn(array('status'=>0,'error'=>'已经超出添加的地址数量，无法添加！'));
        }

        $addressPost=I('post.'); 
        $validate=$this->validateAddressField($addressPost);
        //验证表单长度
        if(!empty($validate)){
            $this->ajaxReturn(array('status'=>0,'error'=>'表单'.$validate.'内容为空或者长度超限！','errorCode'=>4));
        }
   
        $result=$this->users_model->where(array('id'=>$this->userid))->field('address,addr_enable')->find();
        if(!empty($result['address'])){
            if($result['addr_enable']==1){
                $result=json_decode($result['address'],true);
                unset($result['addr_enable']);
            }else{
                $this->ajaxReturn(array('status'=>0,'error'=>'已经超出添加的地址数量，无法添加！'));
            }
        }else{
            unset($result);
        }
        //提交的地址数据为固定表单序列，所以都是直接加入数组，所以请保证数据序列的一致性进行添加、引用、索引，防止数据混乱造成错误
        $result[]=array_values($addressPost); 

        //检查添加数量
        $idx=count($result);
        if($idx<8){
            $addressPost['address']=json_encode($result);
        }else if($idx==8){
            session('user.addr_enable',0);
            $addressPost['addr_enable']=0;
            $addressPost['address']=json_encode($result);              
        }else{
            return false;
        }

        $result=$this->users_model->where(array('id'=>$this->userid,'addr_enable'=>1))->save($addressPost);
        if($result!==false){
            $this->ajaxReturn(array('status'=>1,'idx'=>$idx,'data'=>$addressPost['address'],'msg'=>'添加成功，您可以使用新添加的地址进行购物！'));
        }

        $this->ajaxReturn(array('status'=>0,'error'=>'数据无法添加保存，请稍候再试！','errorCode'=>10));

    }

    public function editAddress(){
        $idx=I('get.idx',0,'intval');
        $addressPost=I('post.'); 
        $validate=$this->validateAddressField($addressPost);
        //验证表单长度
        if(!empty($validate)){
            $this->ajaxReturn(array('status'=>0,'error'=>'表单'.$validate.'内容为空或者长度超限！','errorCode'=>4));
        }

        $result=$this->users_model->where(array('id'=>$this->userid))->field('address')->find();
        if(!empty($result['address'])){
            $result=json_decode($result['address'],true);
            //校验索引数据是否存在
            if(empty($result[$idx-1])){
                return false;
            }
            $result[$idx-1]=array_values($addressPost);
            $newAddress['address']=json_encode(array_values($result));
            //dump($newAddress);
            $updateRes=$this->users_model->where(array('id'=>$this->userid))->save($newAddress);
            if($updateRes!==false){
                $this->ajaxReturn(array('status'=>1,'idx'=>$idx,'data'=>$newAddress['address'],'msg'=>'地址内容修改成功！'));
            }else{
                $this->ajaxReturn(array('status'=>0,'error'=>'服务器保存数据出错，请稍候再试！','errorCode'=>10));
            }
        }else{
            $this->ajaxReturn(array('status'=>1,'msg'=>'没有可编辑的数据！'));
        }

    }

    //删除收货地址
    public function deleteAddress(){
        $idx=I('get.idx',0,'intval');
        $result=$this->users_model->where(array('id'=>$this->userid))->field('address')->find();
        if(!empty($result['address'])){
            $result=json_decode($result['address'],true);
            if(empty($result[$idx-1])){
                return false;
            }
            unset($result[$idx-1]);
            //array_splice($result,$idx-1,1);

            $newAddress['address']=json_encode(array_values($result));
            //dump($newAddress);
            //判断是否当前添加数量为最大，如果是最大则重新激活可添加状态
            if(session('user.addr_enable')==0){
                $newAddress['addr_enable']=1;
            }
            $updateRes=$this->users_model->where(array('id'=>$this->userid))->save($newAddress);
            if($updateRes!==false){
                session('user.addr_enable',1);
                $this->ajaxReturn(array('status'=>1,'idx'=>$idx,'data'=>$newAddress['address'],'msg'=>'地址内容成功删除！'));
            }else{
                $this->ajaxReturn(array('status'=>0,'error'=>'服务器保存数据出错，请稍候再试！','errorCode'=>10));
            }
        }else{
            $this->ajaxReturn(array('status'=>1,'msg'=>'没有可删除的数据！'));
        }
    }

    //设为默认收货地址
    public function setDefaultAddress(){
        $idx=I('get.idx',0,'intval');
        $result=$this->users_model->where(array('id'=>$this->userid))->field('address')->find();
        if(!empty($result['address'])){
            $result=json_decode($result['address']);
            $temp=$result[0];
            $result[0]=$result[$idx-1];
            $result[$idx-1]=$temp;
            $newAddress['address']=json_encode(array_values($result));
            //dump($newAddress);
            $updateRes=$this->users_model->where(array('id'=>$this->userid))->save($newAddress);
            if($updateRes!==false){
                $this->ajaxReturn(array('status'=>1,'idx'=>$idx,'data'=>$newAddress['address'],'msg'=>'成功设置为默认购物收货地址！'));
            }else{
                $this->ajaxReturn(array('status'=>0,'error'=>'服务器保存数据出错，请稍候再试！','errorCode'=>10));
            }
        }else{
            $this->ajaxReturn(array('status'=>1,'msg'=>'没有可设置的数据！'));
        }    
    }

    //添加收货地址表单内容长度验证
    public function validateAddressField($addressPost){
        if(strlen($addressPost['tag'])>20 | empty($addressPost['tag']))
            return 'tag';
        if(strlen($addressPost['consignee'])>30 | empty($addressPost['consignee']))
            return 'consignee';
        if(strlen($addressPost['telNumber'])>20 | empty($addressPost['telNumber']))
            return 'telNumber';
        if(strlen($addressPost['detailInfo'])>100 | empty($addressPost['detailInfo']))
            return 'detailInfo';
        if(strlen($addressPost['province'])>20 | empty($addressPost['province']))
            return 'province';
        if(strlen($addressPost['provinceCode'])>20 | empty($addressPost['provinceCode']))
            return 'provinceCode';
        if(strlen($addressPost['city'])>20 | empty($addressPost['city']))
            return 'city';
        if(strlen($addressPost['cityCode'])>20 | empty($addressPost['cityCode']))
            return 'cityCode';
        if(strlen($addressPost['district'])>30 | empty($addressPost['district']))
            return 'district';
        if(strlen($addressPost['districtCode'])>20 | empty($addressPost['districtCode']))
            return 'districtCode';
        if(strlen($addressPost['zipcode'])>20)
            return 'zipcode';
        return '';
    }
}