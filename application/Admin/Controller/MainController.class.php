<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class MainController extends AdminbaseController {
	
    public function index(){
    	$orderModel=M('order');

        $where['date_format(pay_time, \'%Y-%m-%d\')']=array('exp','=curdate()');
        $where['status']=array('gt',0);
        $curDateOrderNum=$orderModel->where($where)->count();
        //日销售
        $daySales=$orderModel->where(array('to_days(pay_time)'=>array('exp','= to_days(now())','status'=>array('gt',0))))->field('sum(total_price) as sales')->group('total_price')->select();
        //月销售额
        $monthSales=$orderModel->where(array('status'=>array('gt',0)))->field('sum(total_price) as sales')->group('date_format(pay_time, \'%Y-%m\')')->select();

        //当天时段销售额统计
        unset($where);
        $where['date_format(pay_time, \'%Y%m%d\')']=array('exp','=DATE_FORMAT(curdate(),\'%Y%m%d\')');
        $where['status']=array('gt',0);
        $daySalesData=$orderModel->where($where)->field('sum(total_price) as sales,DATE_FORMAT(pay_time,\'%H\') as times, count(*) ordercount')->group('times')->select();
        $daySalesData=json_encode(array('data'=>$daySalesData));  

        //当前月每天销售额统计
        unset($where);
        $where['date_format(pay_time, \'%Y%m\')']=array('exp','=DATE_FORMAT(curdate(),\'%Y%m\')');
        $where['status']=array('gt',0);
        $monthSalesData=$orderModel->where($where)->field('sum(total_price) as sales,DATE_FORMAT(pay_time,\'%d\') as days, count(*) ordercount')->group('days')->select();
        $monthSalesData=json_encode(array('data'=>$monthSalesData));

        //待处理的退货商品
        changeModelProperty($orderModel,'order_relationships');
        $refundNum=$orderModel->where(array('status'=>array('gt',0),array('refund_status'=>array('exp','between 1 and 4'))))->count();
        
        //待处理的售后商品
        changeModelProperty($orderModel,'after_sale');
        $afterSaleNum=$orderModel->where(array('refund_status'=>array('exp','between 0 and 3')))->count();


        //dump($orderModel->getLastSql());
    	/*$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
    			L('OPERATING_SYSTEM') => PHP_OS,
    			L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
    	        L('PHP_VERSION') => PHP_VERSION,
    			L('PHP_RUN_MODE') => php_sapi_name(),
				L('PHP_VERSION') => phpversion(),
    			L('MYSQL_VERSION') =>$mysql,
    			L('PROGRAM_VERSION') => THINKCMF_VERSION . "&nbsp;&nbsp;&nbsp; [<a href='http://www.thinkcmf.com' target='_blank'>ThinkCMF</a>]",
    			L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
    			L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
    			L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);*/

    	$this->assign('curDateOrderNum',  $curDateOrderNum);
        $this->assign('daySales',$daySales[0]['sales']);
        $this->assign('monthSales',$monthSales[0]['sales']);
        $this->assign('monthSalesData',$monthSalesData);
        $this->assign('daySalesData',$daySalesData);
        $this->assign('refundNum',$refundNum);
        $this->assign('afterSaleNum',$afterSaleNum);
    	$this->display();
    }
}