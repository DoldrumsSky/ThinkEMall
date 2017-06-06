<?php
namespace EMall\Service;
use EMall\Service\ChromePhp;

/**
 * ThinkEMall电子商城API服务类
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
 * $Id: ApiService.class.php 17217 2017-04-27 20:41:08Z YangHua $
*/

class ApiService {
    
    //获取商城系统配置(默认为1系统配置，2为支付宝配置，3为微信支付配置,重要的配置数据不生成缓存如支付配置)
    public static function getEMallConfig($config_id=1){
        $configData=F('Think_EMall_Config_'.$config_id);
        if(empty($configData)){
            $configModel=M('mall_config');
            $configData=$configModel->where(array('config_id'=>$config_id))->find();

            $configOption=json_decode($configData['config_option'],true);
            foreach ($configOption as $name => $options) {
                //生成配置的键值对
                $config[$name]=$options['value'];
            }
            $configData=$config;
            if($config_id==1){
                F('Think_EMall_Config_'.$config_id,$config);
            }
        }
        return $configData;
    }

    /*public static function getPropertySearcherStr($EMallTermId){
        $propertyModel=M('mall_terms');
        $data=$propertyModel
        ->alias('mallterms')
        ->join('__GOODS_TYPE__ as goodstype on goodstype.term_id = mallterms.term_id')
        ->field('mallterms.*,goodstype.count as typecount')
        ->where(array('mallterms.term_id'=>$EMallTermId))
        ->select();

    }   */

    //初始化购物车数据
    //所有的购物车操作结果会等到用户退出系统或者会话结束才会保存进数据库，
    //期间的购物车操作数据变化都保存在会话变量中
    public static function initShopCartData($cartData){
        $cartDataGroup=explode(',',$cartData);
        foreach ($cartDataGroup as $key => $value) {
            $goodsDataGroup=explode('-',$value);
            //生成查询数据库商品要使用的in字串
            $goods_idStr.=empty($goods_idStr)?$goodsDataGroup[0]:','.$goodsDataGroup[0];
            //商品ID号
            $goods_id[]=$goodsDataGroup[0];
            //商品单品项行数据索引
            $sku_idx[]=$goodsDataGroup[1];
            //商品购买数
            $shopBuyNum[]=$goodsDataGroup[2];
            //生成gid键及对应购买数量值对
            $gid=$goodsDataGroup[0].'-'.$goodsDataGroup[1];
            $sessionCart[$gid]=$goodsDataGroup[2];
        } 
        return $sessionCart;
    }

    //获取购物车商品数据
    public static function getShopCartData($type){

        //session('shopCart',null);
        $shopCartData=session('user.shopcart');
        if(empty($shopCartData)){
            return 'no shopcart data';
        }

        if(empty($type)||$type=='accounts'){

             $shopCartData;

                if(empty($shopCartData)){
                    return 'no shopcart data';
                }
             foreach ($shopCartData as $key => $value) {
                $goodsDataGroup=explode('-',$key);
                //生成查询数据库商品要使用的in字串
                $goods_idStr.=empty($goods_idStr)?$goodsDataGroup[0]:','.$goodsDataGroup[0];
                //商品ID号
                $goods_id[]=$goodsDataGroup[0];
                //商品单品项行数据索引
                $sku_idx[]=$goodsDataGroup[1];
                 //商品购买数
                $shopBuyNum[]=$value;
                //生成gid键及对应购买数量值对
                $gid=$goodsDataGroup[0].'-'.$goodsDataGroup[1];
             }


           $result=M('goods')->where(array('goods_id'=>array('in',$goods_idStr)))
           ->field('
            goods_id,
            goods_title,
            goods_price,
            goods_weight,
            goods_volume,
            goods_discount,
            goods_stock,
            goods_sku,
            goods_img,
            freinsurance,
            logistics_id,
            logistics_borne
            ')
           ->select();

            if($result){
                $data['goods_info']=$result;
                $data['goods_id']=$goods_id;
                $data['sku_idx']=$sku_idx;
                $data['shopBuyNum']=$shopBuyNum;

                return $data;                
            }else{
                //return '服务器无法获取或找到任何购物车数据！';      
            }
        }

        return 'no shopcart data';
    }

    /**
     * 功能:查询文章列表,支持分页;<br>
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     * @param int $pagesize 每页条数,为0,false表示不分页
     * @param string $pagetpl 以字符串方式传入,例："{first}{prev}{liststart}{list}{listend}{next}{last}"
     * @return array 包括分页的文章列表<pre>
     * 格式:
     * array(
     *     "posts"=>array(),//文章列表,array
     * 	   "page"=>"",//生成的分页html,不分页则没有此项
     *     "count"=>100, //符合条件的文章总数,不分页则没有此项
     *     "total_pages"=>5 // 总页数
     * )</pre>
     */
    public static function posts($tag,$where=array(),$pagesize=0,$pagetpl=''){
    	$where=is_array($where)?$where:array();
    	$tag=sp_param_lable($tag);
    	
    	$field = !empty($tag['field']) ? $tag['field'] : '*';
    	$limit = !empty($tag['limit']) ? $tag['limit'] : '0,10';
    	$order = !empty($tag['order']) ? $tag['order'] : 'post_date DESC';
    
    	//根据参数生成查询条件
    	$where['term_relationships.status'] = array('eq',1);
    	$where['posts.post_status'] = array('eq',1);
    
    	if (isset($tag['cid'])) {
    	    $tag['cid']=explode(',', $tag['cid']);
    	    $tag['cid']=array_map('intval', $tag['cid']);
    		$where['term_relationships.term_id'] = array('in',$tag['cid']);
    	}
    
    	if (isset($tag['ids'])) {
    	    $tag['ids']=explode(',', $tag['ids']);
    	    $tag['ids']=array_map('intval', $tag['ids']);
    		$where['term_relationships.object_id'] = array('in',$tag['ids']);
    	}
    	
    	if (isset($tag['where'])) {
    		$where['_string'] = $tag['where'];
    	}
    
    	$join = '__POSTS__ as posts on term_relationships.object_id = posts.id';
    	$join2= '__USERS__ as users on posts.post_author = users.id';
    	
    	$term_relationships_model= M("TermRelationships");
    	$content=array();
    
    	if (empty($pagesize)) {
    	    $posts=$term_relationships_model
    	    ->alias("term_relationships")
    	    ->join($join)
    	    ->join($join2)
    	    ->field($field)
    	    ->where($where)
    	    ->order($order)
    	    ->limit($limit)
    	    ->select();
    	}else{
    	    $pagetpl = empty($pagetpl) ? '{first}{prev}{liststart}{list}{listend}{next}{last}' : $pagetpl;
    	    $totalsize=$term_relationships_model
    	    ->alias("term_relationships")
    	    ->join($join)
    	    ->join($join2)
    	    ->field($field)
    	    ->where($where)
    	    ->count();
    	    
    	    $pagesize = intval($pagesize);
    	    $page_param = C("VAR_PAGE");
    	    $page = new \Page($totalsize,$pagesize);
    	    $page->setLinkWraper("li");
    	    $page->__set("PageParam", $page_param);
            if(sp_is_mobile()){
                $pagesetting= array("listlong" => "2", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }else{
                $pagesetting=array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }
    	    $page->SetPager('default', $pagetpl,$pagesetting);
    	    
    	    $posts=$term_relationships_model
    	    ->alias("term_relationships")
    	    ->join($join)
    	    ->join($join2)
    	    ->field($field)
    	    ->where($where)
    	    ->order($order)
    	    ->limit($page->firstRow, $page->listRows)
    	    ->select();
    	    
    	    $content['page']=$page->show('default');
    	    $content['total_pages']=$page->getTotalPages(); // 总页数
    	    $content['count']=$totalsize;
    	}
    	
    	$content['posts']=$posts;
    	
    	return $content;
    }

    //获取添加商品时必须填写的筛选项
    public function getTermFilter($term_id){
        //F('EMall_Term_Filter_'.$term_id,null);
        $filterData=F('EMall_Term_Filter_'.$term_id);
        if(empty($filterData)){
            $where['term_id']=$term_id;
            $filterData=M('filter')->where($where)->select();

            if(!empty($filterData)){
                $tree = new \Tree();
                $tree->init($filterData);
                $filterData=$tree->get_tree_array(0);
                $filterData=json_encode($filterData);
                F('EMall_Term_Filter_'.$term_id,$filterData);
            }
        }
        return $filterData;
    }

     /**
     * 获取指定id的商品，使用方法同posts()函数
      * @param string $tag 查询标签,以字符串方式传入,例："field:post_title,post_content;"<br>
     *  field:调用指定字段,如(id,goods_name...) 默认全部<br>
     * @return array 返回指定id的文章
     */
    public static function getGoodsData($tag,$where=array(),$pagesize=0,$pagetpl=''){     
        //其它选择的筛选项参数   
        $filter_id=I('get.fid');
        //价格筛选项参数
        $filter_price=I('get.pfid');
        //品牌筛选项参数
        $filter_brand=I('get.bfid');
        //排序参数
        $sort=I('get.sort');


        $where=is_array($where)?$where:array();
        $tag=sp_param_lable($tag);
        
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '0,10';
        $order = !empty($tag['order']) ? $tag['order'] : 'permonth_sales DESC';

        if($sort=='total'){
            $order='permonth_sales desc';
        }else if($sort=='appraisecount'){
            $order='appraise_num desc';
        }else if($sort=='dredisprice'){
            $order='goods_price asc';
        }else if($sort=='dredisprice'){
            $order='post_date desc';
        }

        if (isset($tag['where'])) {
            $where['_string'] = $tag['where'];
        }       

        $where['goods_status'] = array('eq',1);

        //如果带筛选项，需要增加筛选条件
        if(!empty($filter_id)){
            $filter_id=str_replace(',',' ',$filter_id);
            
            $where['match(filter_id)']=array('exp','against(\''.$filter_id.'\' IN BOOLEAN MODE)');
            unset($filter_id);
        }

        if(!empty($filter_brand)){
            $where['brand_id']=array('in',$filter_brand);
        }

        if(!empty($filter_price)){
            //判断是否为发烧级的价格
            if(substr($filter_price,-3,strlen($filter_price))=='上'){
                $priceStr=substr($filter_price,0,-9);
                $where['goods_price']=array('egt',$priceStr);
            }else{
                $filter_price=str_replace('-',',',$filter_price);
                $where['goods_price']=array('between',$filter_price);
            }
        }


        if (!empty($pagesize)) {

            $pagetpl = empty($pagetpl) ? '{first}{prev}{liststart}{list}{listend}{next}{last}' : $pagetpl;
            $goods_model=M('goods');
            $totalsize=$goods_model
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->count();  
            
            $pagesize = intval($pagesize);
            $page_param = C("VAR_PAGE");
            $page = new \Page($totalsize,$pagesize);
            $page->setLinkWraper("li");
            $page->__set("PageParam", $page_param);
            if(sp_is_mobile()){
                $pagesetting= array("listlong" => "2", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }else{
                $pagesetting=array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }
            $page->SetPager('default', $pagetpl,$pagesetting);

            $goods=$goods_model
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($page->firstRow, $page->listRows)
                ->select();            
          
            $content['page']=$page->show('default');
            $content['total_pages']=$page->getTotalPages(); // 总页数
            $content['count']=$totalsize;
        }else{
            $goods=$goods_model
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
        }
 
        /*foreach ($goods as $key => $value) {
            $goods[$key]['SKU_Data']=self::getSKUData($value['goods_id']);            
         }*/

        $content['goods']=$goods;    

        //dump($content);        
        return $content;
    }


    /**
     * 获取指定term_id类目的商品，使用方法同posts()函数
      * @param string $tag 查询标签,以字符串方式传入,例："field:post_title,post_content;"<br>
     *  field:调用指定字段,如(id,goods_name...) 默认全部<br>
     * @return array 返回指定term_id类目的商品
     */
    public static function getOrderData($tag,$where=array(),$pagesize=0,$pagetpl=''){
        
        $where=is_array($where)?$where:array();
        $tag=sp_param_lable($tag);
        
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '0,10';
        $order = !empty($tag['order']) ? $tag['order'] : 'order_id DESC';

        if (isset($tag['where'])) {
            $where['_string'] = $tag['where'];
        }       

        //$where['goods_status'] = array('eq',1);
        


        if (!empty($pagesize)) {

            $pagetpl = empty($pagetpl) ? '{first}{prev}{liststart}{list}{listend}{next}{last}' : $pagetpl;
            $totalsize=M('order')
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->count();  
            
            $pagesize = intval($pagesize);
            $page_param = C("VAR_PAGE");
            $page = new \Page($totalsize,$pagesize);
            $page->setLinkWraper("li");
            $page->__set("PageParam", $page_param);
            if(sp_is_mobile()){
                $pagesetting= array("listlong" => "2", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }else{
                $pagesetting=array("listlong" => "4", "first" => "首页", "last" => "尾页", "prev" => "上一页", "next" => "下一页", "list" => "*", "disabledclass" => "");
            }
            $page->SetPager('default', $pagetpl,$pagesetting);

            $orderData=M('order')
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($page->firstRow, $page->listRows)
                ->select();            
          
            $content['page']=$page->show('default');
            $content['total_pages']=$page->getTotalPages(); // 总页数
            $content['count']=$totalsize;
        }else{
            $orderData=M('order')
                ->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
        }



        $content['order']=$orderData;    

        //dump($content);        
        return $content;
    }

/**
    *调用SKU单品项数据
    * @param string $goods_id 应商品ID
    * @param bool $isAjaxReturn 是否以ajax返回数据
 **/
    public static function getSKUData($goods_id,$isAjaxReturn=false){
        $SKU_Modal=M('sku');
        $SKU_Data=$SKU_Modal->where(array('goods_id'=>array('eq',$goods_id)))->select(); 
        if($isAjaxReturn==false){
            return $SKU_Data;
        }else{
            return array('status'=>1,'SKU_Data'=>$SKU_Data);
        }
    }
    
    /**
     * 查询文章列表,不做分页
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     * 
     */
    public static function postsNotPaged($tag,$where=array()){
        $content=self::posts($tag,$where);
        return $content['posts'];
    }
    
    /**
     * 功能：根据分类文章分类ID 获取该分类下所有文章(包含子分类中文章)
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @author labulaka 2014-11-09 14:30:49
     * @param int $term_id 文章分类ID.
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;     
     */
    public static function postsByTermId($term_id,$tag,$where=array()){
        $term_id=intval($term_id);
        
        if(!is_array($where)){
            $where=array();
        }
        
        $term_ids=array();
        
        $term_ids=M("Terms")->where("status=1 and ( term_id=$term_id OR path like '%-$term_id-%' )")->order('term_id asc')->getField('term_id',true);
        
        if(!empty($term_ids)){
            $where['term_relationships.term_id']=array('in',$term_ids);
        }
        
        $content=self::posts($tag,$where);
        
        return $content['posts'];
    }
    
    /**
     * 功能:查询文章列表,支持分页;<br>
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param int $pagesize 每页条数,为0,false表示不分页
     * @param string $pagetpl 以字符串方式传入,例："{first}{prev}{liststart}{list}{listend}{next}{last}"
     * @return array 包括分页的文章列表<pre>
     * 格式:
     * array(
     *     "posts"=>array(),//文章列表,array
     * 	   "page"=>""//生成的分页html,不分页则没有此项
     *     "count"=>100 //符合条件的文章总数,不分页则没有此项
     * )</pre>
     */
    public static function postsPaged($tag,$pagesize=20,$pagetpl=''){
        return self::posts($tag,array(),$pagesize,$pagetpl);
    }

    //以分页方式获取商品列表，使用方法同上
    public static function getGoodsByPaged($tag,$pagesize=20,$pagetpl=''){
        return self::getGoodsData($tag,array(),$pagesize,$pagetpl);
    }

    //以分页方式获取订单列表，使用方法同上
    public static function getOrderByPaged($tag,$pagesize=10,$pagetpl=''){
        return self::getOrderData($tag,array(),$pagesize,$pagetpl);
    }
    
    /**
     * 根据分类文章分类ID 获取该分类下所有文章（包含子分类中文章）,已经分页
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @author labulaka 2014-11-09 14:30:49
     * @param int $cid 文章分类ID.
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param int $pagesize 每页条数.
     * @param string $pagetpl 以字符串方式传入,例："{first}{prev}{liststart}{list}{listend}{next}{last}"
     */
    public static function postsPagedByTermId($term_id,$tag,$pagesize=20,$pagetpl=''){
        $term_id=intval($term_id);
        $term_ids=array();
        $where=array();
        $term_ids=M("Terms")->field("term_id")->where("status=1 and ( term_id=$term_id OR path like '%-$term_id-%' )")->order('term_id asc')->getField('term_id',true);
        
        if(!empty($term_ids)){
            $where['term_relationships.term_id']=array('in',$term_ids);
        }
        
        $content=self::posts($tag,$where,$pagesize,$pagetpl);
        
        return $content;
    }
    
    /**
     * 功能：根据关键字 搜索文章（包含子分类中文章）,已经分页,调用方式同sp_sql_posts_paged<br>
     * 注:此方法查询时关联三个表term_relationships,posts,users;在指定查询字段(field),排序(order),指定查询条件(where)最好指定一下表名
     * @author WelkinVan 2014-12-04
     * @param string $keyword 关键字.
     
     * @param string $tag <pre>查询标签,以字符串方式传入
     * 例："cid:1,2;field:posts.post_title,posts.post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"
     * ids:文章id,可以指定一个或多个文章id,以英文逗号分隔,如1或1,2,3
     * cid:文章所在分类,可指定一个或多个分类id,以英文逗号分隔,如1或1,2,3 默认值为全部
     * field:调用指定的字段
     *   如只调用posts表里的id和post_title字段可以是field:posts.id,posts.post_title; 默认全部,
     *   此方法查询时关联三个表term_relationships,posts,users;
     *   所以最好指定一下表名,以防字段冲突
     * limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * order:排序方式,如按posts表里的post_date字段倒序排列：posts.post_date desc
     * where:查询条件,字符串形式,和sql语句一样,请在事先做好安全过滤,最好使用第二个参数$where的数组形式进行过滤,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突</pre>
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样,此方法查询时关联多个表,所以最好指定一下表名,以防字段冲突;
     * @param int $pagesize 每页条数.
     * @param string $pagetpl 以字符串方式传入,例："{first}{prev}{liststart}{list}{listend}{next}{last}"
     */
    public static function postsPagedByKeyword($keyword,$tag,$pagesize=20,$pagetpl=''){
        $where=array();
        $where['posts.post_title'] = array('like',"%$keyword%");
        
        $content=self::posts($tag,$where,$pagesize,$pagetpl);
        
        return $content;
    }
    
    /**
     * 获取指定id的文章
     * @param int $post_id posts表下的id.
     * @param string $tag 查询标签,以字符串方式传入,例："field:post_title,post_content;"<br>
     *	field:调用post指定字段,如(id,post_title...) 默认全部<br>
     * @return array 返回指定id的文章
     */
    public static function post($post_id,$tag){
        $where=array();
        
        $tag=sp_param_lable($tag);
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        
        $where['post_status'] = array('eq',1);
        $where['id'] = array('eq',$post_id);
        
        $post=M('Posts')->field($field)->where($where)->find();
        
        return $post;
    }


    
    /**
     * 获取指定条件的页面列表
     * @param string $tag 查询标签,以字符串方式传入,例："ids:1,2;field:post_title,post_content;limit:0,8;order:post_date desc,listorder desc;where:id>0;"<br>
     * 	ids:调用指定id的一个或多个数据,如 1,2,3<br>
     * 	field:调用post指定字段,如(id,post_title...) 默认全部<br>
     * 	limit:数据条数,默认值为10,可以指定从第几条开始,如0,8(表示共调用8条,从第1条开始)<br>
     * 	order:排序方式,如：post_date desc<br>
     *	where:查询条件,字符串形式,和sql语句一样
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样；
     * @return array 返回符合条件的所有页面
     */
    public static function pages($tag,$where=array()){
        if(!is_array($where)){
            $where=array();
        }
        $tag=sp_param_lable($tag);
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '0,10';
        $order = !empty($tag['order']) ? $tag['order'] : 'post_date DESC';
        
        //根据参数生成查询条件
        $where['post_status'] = array('eq',1);
        $where['post_type'] = array('eq',2);
        
        if (isset($tag['ids'])) {
            $tag['ids']=explode(',', $tag['ids']);
            $tag['ids']=array_map('intval', $tag['ids']);
            $where['id'] = array('in',$tag['ids']);
        }
        
        if (isset($tag['where'])) {
            $where['_string'] = $tag['where'];
        }
        
        $posts_model= M("Posts");
        
        $pages=$posts_model->field($field)->where($where)->order($order)->limit($limit)->select();
        
        return $pages;
    }
    
    /**
     * 获取指定id的页面
     * @param int $id 页面的id
     * @return array 返回符合条件的页面
     */
    public static function page($id){
        $where=array();
        $where['id'] = array('eq',$id);
        $where['post_type'] = array('eq',2);
        $where['post_status'] = array('eq',1);
        
        $posts_model = M("Posts");
        $post = $posts_model->where($where)->find();
        return $post;
    }
    
    /**
     * 返回指定分类
     * @param int $term_id 分类id
     * @return array 返回符合条件的分类
     */
    public static function term($term_id){
    	$terms=F('all_terms');
    	if(empty($terms)){
    		$terms_model= M("Terms");
    		$terms=$terms_model->where("status=1")->select();
    		$mterms=array();
    		
    		foreach ($terms as $t){
    			$tid=$t['term_id'];
    			$mterms["t$tid"]=$t;
    		}
    		
    		F('all_terms',$mterms);
    		return $mterms["t$term_id"];
    	}else{
    		return $terms["t$term_id"];
    	}
    }
    
    /**
     * 返回指定分类下的子分类
     * @param int $term_id 分类id
     * @return array 返回指定分类下的子分类
     */
    public static function child_terms($term_id){
        $term_id=intval($term_id);
        $terms_model = M("Terms");
        $terms=$terms_model->where("status=1 and parent=$term_id")->order("listorder asc")->select();
    
        return $terms;
    }
    
    /**
     * 返回指定分类下的所有子分类
     * @param int $term_id 分类id
     * @return array 返回指定分类下的所有子分类
     */
    public static function all_child_terms($term_id){
        $term_id=intval($term_id);
        $terms_model = M("Terms");
    
        $terms=$terms_model->where("status=1 and path like '%-$term_id-%'")->order("listorder asc")->select();
    
        return $terms;
    }
    
    /**
     * 返回符合条件的所有分类
     * @param string $tag 查询标签,以字符串方式传入,例："ids:1,2;field:term_id,name,description,seo_title;limit:0,8;order:path asc,listorder desc;where:term_id>0;"<br>
     * 	ids:调用指定id的一个或多个数据,如 1,2,3
     * 	field:调用terms表里的指定字段,如(term_id,name...) 默认全部,用*代表全部
     * 	limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
     * 	order:排序方式,如：path desc,listorder asc<br>
     * 	where:查询条件,字符串形式,和sql语句一样
     * @param array $where 查询条件(只支持数组),格式和thinkphp的where方法一样；
     * @return array 返回符合条件的所有分类
     *
     */
    public static function terms($tag,$where=array()){
        if(!is_array($where)){
            $where=array();
        }
        
        $tag=sp_param_lable($tag);
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '';
        $order = !empty($tag['order']) ? $tag['order'] : 'term_id';
    
        //根据参数生成查询条件
        $where['status'] = array('eq',1);
    
        if (isset($tag['ids'])) {
            $tag['ids']=explode(',', $tag['ids']);
            $tag['ids']=array_map('intval', $tag['ids']);
            $where['term_id'] = array('in',$tag['ids']);
        }
    
        if (isset($tag['where'])) {
            $where['_string'] = $tag['where'];
        }
    
        $terms_model= M("Terms");
        $terms=$terms_model->field($field)->where($where)->order($order)->limit($limit)->select();
        return $terms;
    }



    public static function MallTerms($tag,$where=array()){
        if(!is_array($where)){
            $where=array();
        }
        
        $tag=sp_param_lable($tag);
        $field = !empty($tag['field']) ? $tag['field'] : '*';
        $limit = !empty($tag['limit']) ? $tag['limit'] : '';
        $order = !empty($tag['order']) ? $tag['order'] : 'term_id';

        //根据参数生成查询条件
        $map['_complex']=$where;
        $map['status']= 1;
    
        if (isset($tag['ids'])) {
            $tag['ids']=explode(',', $tag['ids']);
            $tag['ids']=array_map('intval', $tag['ids']);
            $map['term_id'] = array('in',$tag['ids']);
        }
    
        if (isset($tag['where'])) {
            $where['_string'] = $tag['where'];
        }

    
        $terms_model= M("mall_terms");
        $terms=$terms_model->field($field)->where($map)->order($order)->limit($limit)->select();
        //dump($terms_model->getLastSql());
        return $terms;
    }

    //获取当前商品类目下所有的筛选类
    public static function getTermsFilter($term_id,$parentid=0,$returnType='htmlOption'){
        $term_id=explode(',',$term_id);
        if(!empty($parentid)){
            $parentid=explode(',',$parentid);
        }
        //if(count($term_id)>1){
            $where['term_id']=array('in',$term_id);
       // }else{
        //    $where['term_id']=$term_id;
        //}
        if(count($parentid)>1){
            $where['parentid']=array('in',$parentid);
        }else{
            $where['parentid']=$parentid;
        }

        $filterModel=M('filter');
        $result = $filterModel->where($where)->field('id,term_id,filter_name,parentid')->select();

        if($returnType=='htmlOption'){
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
        }else if($returnType='data'){
            if(!empty($result)){
                $tree=new \Tree();
                $tree->init($result);
                return $tree->get_tree_array(0);
            }

            return false;
        }
    }
    
    /**
     * 获取面包屑数据
     * @param int $term_id 当前文章所在分类,或者当前分类的id
     * @param boolean $with_current 是否获取当前分类
     * @return array 面包屑数据
     */
    public static function breadcrumb($term_id,$with_current=false){
        $terms_model= M("Terms");
        $data=array();
        $path=$terms_model->where(array('term_id'=>$term_id))->getField('path');
        if(!empty($path)){
            $parents=explode('-', $path);
            if(!$with_current){
                array_pop($parents);
            }
            
            if(!empty($parents)){
                $data=$terms_model->where(array('term_id'=>array('in',$parents)))->order('path ASC')->select();
            }
        }
        
        return $data;
    }


 /**
     * 获取面包屑数据，调用的是导航数据，而非实际的分类数据
     * @param int $Nav_id 当前导航id
     * @param boolean $with_current 是否获取当前类目导航数据
     * @param boolean $returnHtml 是否以html代码返回
     * @param string $splitLetter 以html代码返回时导航之间的分隔符
     * 
     * @return array 面包屑数据
     * */
    public static function MallBreadcrumb($Nav_id,$with_current=false,$returnHtml=true,$splitLetter='>'){
        $nav_obj= M("Nav");

        $id= intval($Nav_id);

        if(empty($id)){
            return array();
        }

        $path= $nav_obj->where(array('id'=>$id))->order("path ASC")->getField('path');

       if(!empty($path)){
            $parents=explode('-', $path);
            $levelNum=count($parents);
            //去掉第一个为0的顶层菜单标识的元素
            array_splice($parents,0,1);

            if(!$with_current){
                array_pop($parents);
            }
            //一次性输出所有之上层级的菜单数据
            if(!empty($parents)){
                $where['parentid']=$id;
                $where['parentid']=array('in',$parents);
                $where['id']=array('in',$parents);
                $where['_logic']='or';
                $data=$nav_obj->where($where)->order('path ASC')->select();
            }
        }

        $default_app=C("DEFAULT_MODULE");
        $g=C("VAR_MODULE");

        //建立树结构代码
        $tree = new \Tree();
        $tree->init($data);
        $data=$tree->get_tree_array(0);
        //dump($parents);
        if($returnHtml){
            $htmlCode='';
            $htmlCode=self::eachNavData($id,$data,$levelNum,$splitLetter);

            //dump($data);
            return $htmlCode;
        }

        return $data=$tree->get_tree_array(0);

    }

    //遍历面包屑导航数据并生成对应层级的代码
    protected static function eachNavData($navId,$navData,$levelNum,$splitLetter,$curLevel=1,$htmlCode=''){
        foreach ($navData as $key => $Nav) {
            $href=htmlspecialchars_decode($Nav['href']);
            $hrefold=$href;
            if(strpos($hrefold,"{")){//序列 化的数据
                $href=unserialize(stripslashes($Nav['href']));
                $href=leuu($href['action'],$href['param']);
                $href=preg_replace("/\/$default_app\//", "/",$href);
                $href=preg_replace("/$g=$default_app&/", "",$href);
                //这里需要把导航ID一并加入传参，用于页面内部调用导航数据如面包屑
                $href.='&navid='.$Nav['id'];
            }else{
                if($hrefold=="home"){
                    $href=__ROOT__."/";
                }else{
                    $href=$hrefold;
                }
            }
            $Nav['href']=$href;

            if($curLevel==1){
                //如果是顶级菜单不使用次级导航样式
                $htmlCode.='<a href="'.$Nav['href'].'" class="crumbs-link topLevel noBorder">'.$Nav['label'].'</a><span>'.$splitLetter.'</span>';
                //取下一级循环的导航数组
                $eachData=$Nav['child'];
                break;
            //如果层级大于等于2生成同类导航菜单组
            }else if($curLevel>=2){
                //取父类数据进行比较查找
                if((!empty($Nav['child']) && $curLevel!==$levelNum) || $key==$navId){
                    $curMenuHtml.='<div class="crumbs-nav-item" style="z-index:'.($levelNum+5-$curLevel).'"><a href="'.$Nav['href'].'" class="menu-top">'.$Nav['label']
                                    .'<i class="menu-drop-arrow"></i></a><div class="menuItemWrap"><ul>';
                    //取下一级循环的导航数组
                    $eachData=$Nav['child'];
                    continue;
                }
                $otherMenu.='<li><a href="'.$Nav['href'].'">'.$Nav['label'].'</a></li>';
            }

        }
        $curLevel++;
        $htmlCode.=!empty($curMenuHtml) && $curLevel>=2?$curMenuHtml.$otherMenu.'</ul></div></div><span>'.$splitLetter.'</span>':'';

        if($curLevel<$levelNum){
            $htmlCode=self::eachNavData($navId,$eachData,$levelNum,$splitLetter,$curLevel,$htmlCode);
        }
        //dump($htmlCode);
        return $htmlCode;
    }

    //获取指定导航下的所有显示在顶层的推荐菜单数据（返回一个二级的菜单树数组）,CID为导航的分类id
    public static function getAllTopShowNav($cid){
        $cid=intval($cid);
        $nav_obj= M("Nav");
        $parent=$nav_obj->where(array('parentid'=>0,'cid'=>$cid))->field('id,parentid,label')->select();

        if(!empty($parent)){
            $parentid='';
            foreach ($parent as $key => $value) {
                $parentid.=empty($parentid)?$value['id']:','.$value['id'];
            }

            $where['parentid']=array('in',$parentid);
            $where['topshow']=1;

            $navData=$nav_obj->where($where)->select();
            foreach ($navData as $key => $topShowNav) {
                $href=htmlspecialchars_decode($topShowNav['href']);
                $hrefold=$href;
                if(strpos($hrefold,"{")){//序列 化的数据
                    $href=unserialize(stripslashes($topShowNav['href']));
                    $href=leuu($href['action'],$href['param']);
                    $href=preg_replace("/\/$default_app\//", "/",$href);
                    $href=preg_replace("/$g=$default_app&/", "",$href);
                    //这里需要把导航ID一并加入传参，用于页面内部调用导航数据如面包屑
                    $href.='&navid='.$topShowNav['id'];
                }else{
                    if($hrefold=="home"){
                        $href=__ROOT__."/";
                    }else{
                        $href=$hrefold;
                    }
                }
          
                $navData[$key]['href']=$href;
            }

            //将父类数据加入，以树形式输出，用于数据按行排版
            foreach ($parent as $key => $value) {
                $navData[]=$value;
            }

            $tree=new \Tree();
            $tree->init($navData);

            return $tree->get_tree_array(0);
        }
        return false;
    }

//根据广告类id获取对应的广告数据(返回类型为Json文本)
    public static function getSpecifyAdData($id,$returnType){
        if(!empty($id)){
            if(is_array($id)){
                ksort($id);
                $sortId=join('_',$id);
                $where['id']=array('in',$id);
            }else{
                $where['id']=$id;
                $sortId=$id;
            }

            $adListJson=F('ADJson/EMall_AdListJson_'.$sortId);
            $goodsGroupId=array();
            $createGoodsGroupId=false;
            if(empty($adListJson)){
                $where['ad_status']=1;
                $adData=M('mall_group_ad')->where($where)->order('listorder asc')->select();
                if($adData!==false){
                    if($returnType==0){
                        //手工生成json文本
                        foreach ($adData as $key => $value) {
                            $value['ad_cover']=empty($value['ad_cover'])?'""':$value['ad_cover'];
                            $value['ad_data']=empty($value['ad_data'])?'""':$value['ad_data'];
                            $curData='"'.$key.'":{"ad_name":"'.$value['ad_name'].'","sub_name":'.'"'.$value['sub_name'].'","ad_type":'.$value['ad_type'].',"ad_container":"'.$value['ad_container'].'","ad_cover":'.$value['ad_cover'].',"ad_data":'.$value['ad_data'].'}';
                            $adListJson.=$key==0?$curData:','.$curData;
                            //生成绑定商品的id字串
                            if(!empty($value['goods_id'])){
                                if(count($goodsGroupId)>0){
                                    array_merge($value['goods_id'],$goodsGroupId);
                                }else{
                                    $goodsGroupId[]=$value['goods_id'];
                                }
                            }
                        }
                        $adListJson='{'.$adListJson.'}';
                        $goodsGroupId=join(',',$goodsGroupId);
                        $createGoodsGroupId=true;
                        F('ADJson/EMall_AdListJson_'.$sortId,$adListJson);                        
                    }                   

                }else{
                    return false;
                }
            }else{
                $createGoodsGroupId=false;
            }
            //取广告需要即时调用的商品数据
            if(empty($goodsGroupId) && $createGoodsGroupId==false){
                $where['ad_status']=1;
                $goods_id=M('mall_group_ad')->where($where)->field('goods_id')->order('listorder asc')->select();
                if($goods_id!==false){
                    foreach ($goods_id as $key => $value) {
                        if(!empty($value['goods_id'])){
                            if(count($goodsGroupId)>0){
                                 array_merge($value['goods_id'],$goodsGroupId);
                            }else{
                                $goodsGroupId[]=$value['goods_id'];
                            }
                        }
                    }
                    $goodsGroupId=join(',',$goodsGroupId);
                }
            }
            if(!empty($goodsGroupId)){
                $goodsModel=M('goods');
                $goodsData=$goodsModel->where(array('goods_id'=>array('in',$goodsGroupId)))->field('goods_id,goods_price,appraise_num')->select();
            }
            return array('adListJson'=>$adListJson,'goodsData'=>$goodsData);        
           
        }

    }

    //获取指定用户所有订单商品的状态数
    public static function getOrderGoodsStatusCount($orderModel,$userId){
        if(empty($userId)){
            return false;
        }
       $orderStatusCount=$orderModel->field('status,count(*) as count')->where(array('shopper_id'=>$userId))->group('status')->select();
        if($orderModel!==false){
            foreach ($orderStatusCount as $key => $value) {
                $returnCount[$value['status']]=$value['count'];
            }
            //单独查询退货商品数
            $refundGoodsCount=$orderModel->where(array('shopper_id'=>$userId,'refund_status'=>array('exp','between 1 and 4')))->count();
            $returnCount['refund']=$refundGoodsCount;
            return $returnCount;
        }
        return false;
    }

}