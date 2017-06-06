<?php
namespace User\Controller;

use Common\Controller\HomebaseController;

class IndexController extends HomebaseController {
    
    // 前台用户首页 (公开)
	public function index() {
	    
		$id=I("get.id",0,'intval');
		
		$users_model=M("Users");
		
		$user=$users_model->where(array("id"=>$id))->find();
		
		if(empty($user)){
			$this->error("查无此人！");
		}


		$this->assign($user);
		$this->display(":index");

    }
    
    // 前台ajax 判断用户登录状态接口
    function is_login(){
    	if(sp_is_user_login()){
    		$this->ajaxReturn(array("status"=>1,"user"=>sp_get_current_user()));
    	}else{
    		$this->ajaxReturn(array("status"=>0,"info"=>"此用户未登录！"));
    	}
    }

    //退出
    public function logout(){
    	$ucenter_syn=C("UCENTER_ENABLED");
    	$login_success=false;
    	if($ucenter_syn){
    		include UC_CLIENT_ROOT."client.php";
    		echo uc_user_synlogout();
    	}

        //生成并更新收藏的商品数据
        $userSession=session('user');
        //判断是否已经退出，如果已经退出，直接刷新页面
        if(empty($userSession)){
            header('location: '.U('user/login/index'));
            return true;
        }
        
        if(!empty($userSession['newFavorGoods']) && !empty($userSession['favor_goods'])){
            $allFavorData=json_encode($userSession['newFavorGoods']+$userSession['favor_goods']);
        }else if(!empty($userSession['favor_goods']) && empty($userSession['newFavorGoods'])){
            $allFavorData=json_encode($userSession['favor_goods']);
        }else if(empty($userSession['favor_goods']) && !empty($userSession['newFavorGoods'])){
            $allFavorData=json_encode($userSession['newFavorGoods']);
        }


        //更新新收藏的商品中对应的收藏数量
        $goodsModel=M('goods');
        $goodsModel->startTrans();
        //判断更新收藏商品的收藏数是否更新成功
        $savedFavor=true;
        //增加收藏数量
        foreach ($userSession['newFavorGoods'] as $key => $value) {
            $result= $goodsModel->where(array('goods_id'=>$key))->save(array('favor_num'=>array('exp','favor_num+1')));
            if($result===false){
                $savedFavor=false;
                $goodsModel->rollback();
            }            
        }

        if($savedFavor){
            //减少收藏数量
            foreach ($userSession['cancelFavorGoods'] as $key => $value) {
                $result= $goodsModel->where(array('goods_id'=>$key))->save(array('favor_num'=>array('exp','favor_num-1')));
                if($result===false){
                    $savedFavor=false;
                    $goodsModel->rollback();
                }
            }
        }
        //提交
        if($savedFavor){
            $goodsModel->commit();
        }

       //保存购物车数据，同时保存上面生成的用户收藏数据
        $shopCartData['favor_goods']=$allFavorData;

        foreach ($userSession['shopcart'] as $gid => $shopBuyNum) {
           $shopcartStr.=empty($shopcartStr)?$gid.'-'.$shopBuyNum:','.$gid.'-'.$shopBuyNum;
        }
        $shopCartData['shopcart']=$shopcartStr;

        $shopCartData['id']=$userSession['id'];

        $usersModel=M('users');
        if($usersModel->save($shopCartData)!==false){
            session("user",null);//只有前台用户退出           
        }else{
            $this->error('服务器存储用户数据出错,请稍候尝试！'); 
        }            


        header('location: '.U('user/login/index'));
    	//redirect(__ROOT__."/");
    }

}
