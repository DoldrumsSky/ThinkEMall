<?php

/**
 * 通用的树型类，可以生成任何树型结构
 */
class Tree {

    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    public $arr = array();

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = array('│', '├', '└');
    public $nbsp = "&nbsp;";
    private $str ='';
    /**
     * @access private
     */
    public $ret = '';

    /**
     * 构造函数，初始化类
     * @param array 2维数组，例如：
     * array(
     *      1 => array('id'=>'1','parentid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','parentid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','parentid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','parentid'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','parentid'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','parentid'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','parentid'=>3,'name'=>'三级栏目二')
     *      )
     */
    public function init($arr=array()) {
        $this->arr = $arr;
        $this->ret = '';
        return is_array($arr);
    }

    /**
     * 得到父级数组
     * @param int
     * @return array
     */
    public function get_parent($myid) {
        $newarr = array();
        if (!isset($this->arr[$myid]))
            return false;
        $pid = $this->arr[$myid]['parentid'];
        $pid = $this->arr[$pid]['parentid'];
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a['parentid'] == $pid)
                    $newarr[$id] = $a;
            }
        }
        return $newarr;
    }

    /**
     * 得到子级数组
     * @param int
     * @return array
     */
    public function get_child($myid) {
        $a = $newarr = array();
        if (is_array($this->arr)) {
            foreach ($this->arr as $id => $a) {
                if ($a['parentid'] == $myid)
                    $newarr[$id] = $a;
            }
        }
        return $newarr ? $newarr : false;
    }

    /**
     * 得到当前位置数组
     * @param int
     * @return array
     */
    public function get_pos($myid, &$newarr) {
        $a = array();
        if (!isset($this->arr[$myid]))
            return false;
        $newarr[] = $this->arr[$myid];
        $pid = $this->arr[$myid]['parentid'];
        if (isset($this->arr[$pid])) {
            $this->get_pos($pid, $newarr);
        }
        if (is_array($newarr)) {
            krsort($newarr);
            foreach ($newarr as $v) {
                $a[$v['id']] = $v;
            }
        }
        return $a;
    }

    /**
     * 得到树型结构
     * @param int ID，表示获得这个ID下的所有子级
     * @param string 生成树型结构的基本代码，例如："<option value=\$id \$selected>\$spacer\$name</option>"
     * @param int 被选中的ID，比如在做树型下拉框的时候需要用到
     * @return string
     */
    public function get_tree($myid, $str, $sid = 0, $adds = '', $str_group = '') {
        $number = 1;
        //一级栏目
        $child = $this->get_child($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $value) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';
                $selected = $id == $sid ? 'selected' : '';
                @extract($value);
                $parentid == 0 && $str_group ? eval("\$nstr = \"$str_group\";") : eval("\$nstr = \"$str\";");
                $this->ret .= $nstr;
                $nbsp = $this->nbsp;
                $this->get_tree($id, $str, $sid, $adds . $k . $nbsp, $str_group);
                $number++;
            }
        }
        
        return $this->ret;
    }
    
    /**
     * 得到树型结构数组
     * @param int ID，表示获得这个ID下的所有子级
     * @param string 生成树型结构的基本代码，例如："<option value=\$id \$selected>\$spacer\$name</option>"
     * @param int 被选中的ID，比如在做树型下拉框的时候需要用到
     * @return string
     */
    public function get_tree_array($myid, $str, $sid = 0, $adds = '', $str_group = '') {
        $retarray = array();
        //一级栏目数组
        $child = $this->get_child($myid);
        if (is_array($child)) {
            //数组长度
            $total = count($child);
            foreach ($child as $id => $value) {
                @extract($value);
                $retarray[$value['id']] = $value;
                $retarray[$value['id']]["child"] = $this->get_tree_array($id, '');
            }
        }
        return $retarray;
    }

    /**
     * 同上一方法类似,但允许多选
     */
    public function get_tree_multi($myid, $str, $sid = 0, $adds = '') {
        $number = 1;
        $child = $this->get_child($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @extract($a);
                eval("\$nstr = \"$str\";");
                $this->ret .= $nstr;
                $this->get_tree_multi($id, $str, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * @param integer $myid 要查询的ID
     * @param string $str   第一种HTML代码方式
     * @param string $str2  第二种HTML代码方式
     * @param integer $sid  默认选中
     * @param integer $adds 前缀
     */
    public function get_tree_category($myid, $str, $str2, $sid = 0, $adds = '') {
        $number = 1;
        $child = $this->get_child($myid);
        if (is_array($child)) {
            $total = count($child);
            foreach ($child as $id => $a) {
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                } else {
                    $j .= $this->icon[1];
                    $k = $adds ? $this->icon[0] : '';
                }
                $spacer = $adds ? $adds . $j : '';

                $selected = $this->have($sid, $id) ? 'selected' : '';
                @extract($a);
                if (empty($html_disabled)) {
                    eval("\$nstr = \"$str\";");
                } else {
                    eval("\$nstr = \"$str2\";");
                }
                $this->ret .= $nstr;
                $this->get_tree_category($id, $str, $str2, $sid, $adds . $k . '&nbsp;');
                $number++;
            }
        }
        return $this->ret;
    }

    /**
     * 同上一类方法，jquery treeview 风格，可伸缩样式（需要treeview插件支持）
     * @param $myid 表示获得这个ID下的所有子级
     * @param $effected_id 需要生成treeview目录数的id
     * @param $str 末级样式
     * @param $str2 目录级别样式
     * @param $showlevel 直接显示层级数，其余为异步显示，0为全部限制
     * @param $style 目录样式 默认 filetree 可增加其他样式如'filetree treeview-famfamfam'
     * @param $currentlevel 计算当前层级，递归使用 适用改函数时不需要用该参数
     * @param $recursion 递归使用 外部调用时为FALSE
     */
    function get_treeview($myid, $effected_id='example', $str="<span class='file'>\$name</span>", $str2="<span class='folder'>\$name</span>", $showlevel = 0, $style='filetree ', $currentlevel = 1, $recursion=FALSE) {
        $child = $this->get_child($myid);
        if (!defined('EFFECTED_INIT')) {
            $effected = ' id="' . $effected_id . '"';
            define('EFFECTED_INIT', 1);
        } else {
            $effected = '';
        }
        $placeholder = '<ul><li><span class="placeholder"></span></li></ul>';
        if (!$recursion)
            $this->str .='<ul' . $effected . '  class="' . $style . '">';
        foreach ($child as $id => $a) {

            @extract($a);
            if ($showlevel > 0 && $showlevel == $currentlevel && $this->get_child($id))
                $folder = 'hasChildren'; //如设置显示层级模式@2011.07.01
            $floder_status = isset($folder) ? ' class="' . $folder . '"' : '';
            $this->str .= $recursion ? '<ul><li' . $floder_status . ' id=\'' . $id . '\'>' : '<li' . $floder_status . ' id=\'' . $id . '\'>';
            $recursion = FALSE;
            //判断是否为终极栏目
            if ($child == 1) {
                eval("\$nstr = \"$str2\";");
                $this->str .= $nstr;
                if ($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)) {
                    $this->get_treeview($id, $effected_id, $str, $str2, $showlevel, $style, $currentlevel + 1, TRUE);
                } elseif ($showlevel > 0 && $showlevel == $currentlevel) {
                    $this->str .= $placeholder;
                }
            } else {
                eval("\$nstr = \"$str\";");
                $this->str .= $nstr;
            }
            $this->str .=$recursion ? '</li></ul>' : '</li>';
        }
        if (!$recursion)
            $this->str .='</ul>';
        return $this->str;
    }
    
    /**
     * 同上一类方法，jquery treeview 风格，可伸缩样式（需要treeview插件支持）
     * @param $myid 表示获得这个ID下的所有子级
     * @param $effected_id 需要生成treeview目录数的id
     * @param $str 末级样式
     * @param $str2 目录级别样式
     * @param $showlevel 直接显示层级数，其余为异步显示，0为全部限制
     * @param $style 目录样式 默认 filetree 可增加其他样式如'filetree treeview-famfamfam'
     * @param $currentlevel 计算当前层级，递归使用 适用改函数时不需要用该参数
     * @param $recursion 递归使用 外部调用时为FALSE
     * @param $dropdown 有子元素时li的class
     */
    
    function get_treeview_menu($myid,$effected_id='example', $str="<span class='file'>\$name</span>", $str2="<span class='folder'>\$name</span>", $showlevel = 0,  $ul_class="" ,$li_class="" , $style='filetree ', $currentlevel = 1, $recursion=FALSE, $dropdown='hasChild') {
        $child = $this->get_child($myid);
    	if (!defined('EFFECTED_INIT')) {
    		$effected = ' id="' . $effected_id . '"';
    		define('EFFECTED_INIT', 1);
    	} else {
    		$effected = '';
    	}
	$placeholder = '<ul><li><span class="placeholder"></span></li></ul>';
    	if (!$recursion){
    		$this->str .='<ul' . $effected . '  class="' . $style . '">';
    	}

    	foreach ($child as $id => $a) {

    		@extract($a);
     		if ($showlevel > 0 && is_array($this->get_child($a['id']))){
    			$floder_status = " class='$dropdown $li_class'";
    		}else{
    			$floder_status = " class='$li_class'";
    		}
    		$this->str .= $recursion ? "<ul class='$ul_class'><li $floder_status id= 'menu-item-$id'>" : "<li  $floder_status   id= 'menu-item-$id'>";
    		$recursion = FALSE;
    		//判断是否为终极栏目
    		if ($this->get_child($a['id'])) {
    			eval("\$nstr = \"$str2\";");
    			$this->str .= $nstr;
    			if ($showlevel == 0 || ($showlevel > 0 && $showlevel > $currentlevel)) {
					$this->get_treeview_menu($a['id'], $effected_id, $str, $str2, $showlevel,   $ul_class ,$li_class ,$style, $currentlevel + 1, TRUE);
    			} elseif ($showlevel > 0 && $showlevel == $currentlevel) {
    				//$this->str .= $placeholder;
    			}
    		} else {
    			eval("\$nstr = \"$str\";");
    			$this->str .= $nstr;
    		}
    		$this->str .=$recursion ? '</li></ul>' : '</li>';
    	}
    	if (!$recursion)
    		$this->str .='</ul>';
    	return $this->str;
    }

    /**
     * //生成商城类目菜单代码
     * @param $pid 导航菜单中最顶层的类目ID，此类目不会显示在商城首页的类目导航中，一般用于索引其对应的次级类目
     * @param $showTopMenu 是否显示顶级菜单类目，这个一般用在类目的列表首页
     * @param $topShowLevel 显示顶级菜单类目时调用哪个层级的子类目，像天猫会在子类目首页顶级菜单中加入第三级的菜单，商城首页一般是取次级菜单
     */

    function getEMallTreeViewMenu($pid=0, $showTopMenu=false, $topShowLevel=1,$unlinkTopCategory=false, $showlevel=2){
        //先获取最顶层的类目，这个类目只有showtopMenu为true时才会以大标题显示在商城导航菜单中的
        $child=$this->get_child($pid);
        $sMenuStr='';


        //@extract($a);
        foreach($child as $id=>$a){
            //遍历顶级类，拿到当前级别的子级，判断是否还有子级，并设置样式
            $subChild=$this->get_child($a['id']);
            $topStr=$topStr==''?'<li>':'</li><li>';
            //生成菜单的html代码
            $childMenu=$this->getEMallChild($subChild,$topShowLevel,1,$unlinkTopCategory);
            //整合弹出菜单的html代码
            $sMenuStr='<div class="cate_pop">'.$childMenu['sMenuStr'].'</div>';
            //添加顶层菜单图标
            $iconStr=$showTopMenu==false?'<i class="icon iconfont '.$a['icon'].'"></i>':'';
            //整合顶层主菜单的html代码            
            $topStr.=$showTopMenu==true?'<h2>'.$a['label'].'</h2>'.$childMenu['tMenuStr'].$sMenuStr:$iconStr.$childMenu['tMenuStr'].$sMenuStr;
            $this->str.=$topStr;
 
        }
        //完全输出
        $this->str='<ul>'.$this->str.'</ul>';
        return $this->str;
    }

    /**
     * 商城菜单的核心html代码生成
     * @param $childArr 次级类目的数组数据，也是要显示在导航中的所有类目
     * @param $unlinkTopCategory 是否关闭弹出菜单中顶层类目的链接（像京东顶层类目可链接到类目专题页，关闭后，弹出菜单中的顶层类目只是文本如天猫）
     * @param $showlevel 直接显示层级数，这个在商城菜单中一般显示级别都是2层，顶层类目（主导航菜单）+次级类目（弹出菜单）
     * @param $currentlevel 计算当前层级，递归使用 适用改函数时不需要用该参数
     */

    function getEMallChild($childArr, $topShowLevel=1, $currentlevel = 1,$unlinkTopCategory=false, $showlevel=2){
        $tMenuStr=$sMenuStr='';
        $childMenu=array();
        if(is_array($childArr)){
            //遍历当前次级菜单
            foreach($childArr as $sid=>$sa){

                //判断菜单是否设置了在顶层菜单中显示
                if($sa['topshow']==1 && $currentlevel == $topShowLevel){
                    //生成顶层菜单的html代码
                    $tMenuStr.=($tMenuStr=='')?'<a href="'.$sa['href'].'">'.$sa['label'].'</a>':'<span>/</span><a href="'.$sa['href'].'">'.$sa['label'].'</a>';
                    //echo $tMenuStr;
                }
                //生成弹出菜单的html代码
                if($sa['popshow']==1){
                    //如果当前已经遍历到第三层子菜单，实际就是顶层菜单的弹出子菜单,则独立生成一段html代码,否则生成顶层主菜单的html代码
                    if($currentlevel==1){
                        $sMenuStr.=!$unlinkTopCategory?'<dl><dt><a href="'.$sa['href'].'">'.$sa['label'].'</a><i class="icon iconfont"></i></dt>':'<dl><dt>'.$sa['label'].'<i class="icon iconfont"></i></dt>';
                        //取得当前顶层类目的次级类目
                        $arr=$this->get_child($sa['id']);
                        //输出显示在弹出菜单中的次级类目html代码
                        $childMenu=$this->getEMallChild($arr,$topShowLevel,$currentlevel+1);
                        //如果顶层菜单要显示第三级子级类目，则必须到childMenu中取，因为跳级显示的菜单只会在当前级调用getEmallChild的时候才会生成 
                        if($topShowLevel>=2){
                            $tMenuStr=$childMenu['tMenuStr'];
                        }                        
                        //dump($childMenu);
                        $sMenuStr.= '<dd>'.$childMenu['sMenuStr'].'</dd></dl>';
                    }else if($currentlevel>=$showlevel && $currentlevel>1){
                        //高亮显示pop菜单中的类目
                        $highlight=$sa['highlight']==1?' class="highlight"':'';
                        //输出弹出菜单中每个顶层菜单的所有次级菜单                        
                        $sMenuStr.='<a'.$highlight.' href="'.$sa['href'].'">'.$sa['label'].'</a>';                            
                    } 

                }
               
            }
            return array('tMenuStr'=>$tMenuStr,'sMenuStr'=>$sMenuStr);
        }
           return false;  

    }

    /**
     * 获取子栏目json
     * Enter description here ...
     * @param unknown_type $myid
     */
    public function creat_sub_json($myid, $str='') {
        $sub_cats = $this->get_child($myid);
        $n = 0;
        if (is_array($sub_cats))
            foreach ($sub_cats as $c) {
                $data[$n]['id'] = iconv(CHARSET, 'utf-8', $c['catid']);
                if ($this->get_child($c['catid'])) {
                    $data[$n]['liclass'] = 'hasChildren';
                    $data[$n]['children'] = array(array('text' => '&nbsp;', 'classes' => 'placeholder'));
                    $data[$n]['classes'] = 'folder';
                    $data[$n]['text'] = iconv(CHARSET, 'utf-8', $c['catname']);
                } else {
                    if ($str) {
                        @extract(array_iconv($c, CHARSET, 'utf-8'));
                        eval("\$data[$n]['text'] = \"$str\";");
                    } else {
                        $data[$n]['text'] = iconv(CHARSET, 'utf-8', $c['catname']);
                    }
                }
                $n++;
            }
        return json_encode($data);
    }

    private function have($list, $item) {
        return(strpos(',,' . $list . ',', ',' . $item . ','));
    }

}

