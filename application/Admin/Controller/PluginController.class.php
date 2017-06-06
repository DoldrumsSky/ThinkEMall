<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class PluginController extends AdminbaseController{
    
	protected $plugins_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->plugins_model = D("Common/Plugins");
	}
	
	// 后台插件列表
	public function index(){
		$plugins=$this->plugins_model->getList();
		$this->assign("plugins",$plugins);
		$this->display();
	}
	
	// 插件启用/禁用
	public function toggle(){
		if(isset($_GET['id'])){
			if($_GET["enable"]){
				$id = I('get.id',0,'intval');
				if ($this->plugins_model->where(array('id'=>$id))->save(array('status'=>1))!==false) {
					S('hooks',null);
					$this->success("启用成功！");
				} else {
					$this->error("启用失败！");
				}
			}
			if($_GET["disable"]){
				$id = I('get.id',0,'intval');
				if ($this->plugins_model->where(array('id'=>$id))->save(array('status'=>0))!==false) {
					S('hooks',null);
					$this->success("禁用成功！");
				} else {
					$this->error("禁用失败！");
				}
			}
		}
	}
	
	// 插件设置
	public function setting(){
		$id     =   I('get.id',0,'intval');
		$plugin  =   $this->plugins_model->find($id);
		if(!$plugin)
			$this->error('插件未安装');
		$plugin_class = sp_get_plugin_class($plugin['name']);
		if(!class_exists($plugin_class)){
			trace("插件{$plugin['name']}无法实例化,",'PLUGINS','ERR');
		}
			
		$data  =   new $plugin_class;
		$plugin['plugin_path'] = $data->plugin_path;
		$plugin['custom_config'] = $data->custom_config;
		$db_config = $plugin['config'];
		$plugin['config'] = include $data->config_file;
		if($db_config){
			$db_config = json_decode($db_config, true);
			foreach ($plugin['config'] as $key => $value) {
				if($value['type'] != 'group'){
					$plugin['config'][$key]['value'] = $db_config[$key];
				}else{
					foreach ($value['options'] as $gourp => $options) {
						foreach ($options['options'] as $gkey => $value) {
							$plugin['config'][$key]['options'][$gourp]['options'][$gkey]['value'] = $db_config[$gkey];
						}
					}
				}
			}
		}
		$this->assign('data',$plugin);
		if($plugin['custom_config']){
			$this->assign('custom_config', $this->fetch($plugin['plugin_path'].$plugin['custom_config']));
		}
			
		$this->display();
		
	}
	
	// 插件设置提交
	public function setting_post(){
		if(IS_POST){
			$id     =   I('post.id',0,'intval');
			$config =   I('post.config/a');
			$result = $this->plugins_model->where(array('id'=>$id))->setField('config',json_encode($config));
			if($result !== false){
				$this->success('保存成功');
			}else{
				$this->error('保存失败');
			}
		}
	}
	
	// 插件安装
	public function install(){
		$plugin_name     =   trim(I('name'));
		$class          =   sp_get_plugin_class($plugin_name);
		if(!class_exists($class))
			$this->error('插件不存在!');
		$plugin  =   new $class;
		$info = $plugin->info;
		if(!$info || !$plugin->checkInfo())//检测信息的正确性
			$this->error('插件信息缺失!');
		$install_success   =   $plugin->install();
		if(!$install_success){
			$this->error('插件预安装失败!');
		}
		
		$methods=get_class_methods($plugin);
		$system_hooks=sp_get_hooks(true);
		
		$plugin_hooks=array_intersect($system_hooks, $methods);
		
		$info['hooks']=implode(",", $plugin_hooks);
		
		if(!empty($plugin->has_admin)){
			$info['has_admin'] = 1;
		}else{
			$info['has_admin'] = 0;
		}
		
		$info['config']=json_encode($plugin->getConfig());
		
		$data           =   $this->plugins_model->create($info);
		
		if(!$data){
			$this->error($this->plugins_model->getError());
		}
			
		if($this->plugins_model->add($data)){
			S('hooks', null);
			$this->success('安装成功!');
		
		}else{
			$this->error('写入插件数据失败!');
		}
	}
	
	// 插件更新
	public function update(){
		$plugin_name     =   trim(I('name'));
		$class          =   sp_get_plugin_class($plugin_name);
		if(!class_exists($class))
			$this->error('插件不存在!');
		$plugin  =   new $class;
		$info = $plugin->info;
		if(!$info || !$plugin->checkInfo())//检测信息的正确性
			$this->error('插件信息缺失!');
		
		$methods=get_class_methods($plugin);
		
		$system_hooks=sp_get_hooks(true);
		
		$plugin_hooks=array_intersect($system_hooks, $methods);
		
		$info['hooks']=implode(",", $plugin_hooks);
		
		if(!empty($plugin->has_admin)){
			$info['has_admin'] = 1;
		}else{
			$info['has_admin'] = 0;
		}
		
		$config=$plugin->getConfig();
		$info['config']=$config?json_encode($config):"";
		
		$data           =   $this->plugins_model->create($info);
		
		if(!$data){
			$this->error($this->plugins_model->getError());
		}
			
		if($this->plugins_model->where(array("name"=>$plugin_name))->save($data)!==false){
			$this->success('更新成功!');
		}else{
			$this->error('写入插件数据失败!');
		}
	}
	
	// 卸载插件
	public function uninstall(){
		$id             =  I('get.id',0,'intval');
		$find_plugin      =   $this->plugins_model->find($id);
		$class          =   sp_get_plugin_class($find_plugin['name']);
		
		$plugins =   new $class;
		$uninstall_success =   $plugins->uninstall();
		if(!$uninstall_success)
			$this->error('插件预卸载失败');
		S('hooks', null);
		$delete = $this->plugins_model->where(array("name"=>$find_plugin['name']))->delete();
		if($delete === false){
			$this->error('卸载失败');
		}else{
			$this->success('卸载成功');
		}
	}
	
	
	
}