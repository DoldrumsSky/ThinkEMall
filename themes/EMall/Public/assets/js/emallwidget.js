/**
 * ThinkEMall电子商城前端插件脚本
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
 * $Id: custom.js 17217 2017-04-09 06:29:08Z YangHua $
*/


//异步判断ajax返回数据状态
checkState=function(delay,timeOut,callFunc,data){
	var curTimer=0;
	var check=setInterval(function(){
		if (GV.loginState!==undefined|curTimer>=timeOut) {
			clearInterval(check);
			if(callFunc){
				callFunc(data);
			}
		}
		curTimer+=delay;
	},delay);	
}

//检查用户是否登录,type=1时表示需要更新页面登录信息，为0时表示只返回登录状态,并激活登录框,
//callFunc返回已登录状态时需要执行的函数,failCall为返回未登录状态时执行的函数
function checkUserLogin(type,callFunc,failCall){
	//检查登录状态
	$.ajax({
			type: 'POST',
			url: GV.LoginCheckURL,
			dataType: 'json',
			async:true,
			data:{checkReturnType:'ajax'},
			success: function(data,status){
				if(data.status==1){
					if(type==1){
						//显示顶部登录文字
						$('.welcomeLang').html('欢迎访问ThinkEMall电子商城，'+data.user.user_nicename+'<a href="'+GV.UCenter+'">用户中心</a><a href="'+GV.logoutURL+'">退出</a>');
						if(data.user.avatar){
							$("#main-menu-user .headicon").attr("src",data.user.avatar.indexOf("http")==0?data.user.avatar:"{:sp_get_image_url('[AVATAR]','!avatar')}".replace('[AVATAR]',data.user.avatar));
						}
						
						$("#main-menu-user .user-nicename").text(data.user.user_nicename!=""?data.user.user_nicename:data.user.user_login);
						$("#main-menu-user .login").show();
						$("#main-menu-user .offline").hide();	
						//我的资产数据
						$('#myScore').text(data.user.points);
						$('#myBalance').text(data.user.balance);
						//充值事件绑定
						$('#doDeposit').on('click',function(e){
							e.stopPropagation();
							var deposit_input=$('#deposit_input :input');
							if(deposit_input.length==0){
								//添加充值输入框
								$(this).before('<div id="deposit_input">充值金额：<input type="text" placeholder="请输入充值金额"><p>您未输入正确的充值金额！</p></div>');
								$(this).html('进入支付<i></i>');
							}else{
								//跳转到支付页面
								var deposit_fee=parseFloat(deposit_input.val());
								if(deposit_fee>0){
									window.location="/index.php?g=User&m=Payment&a=deposit&orderType=1&deposit_fee="+Number(deposit_fee).toFixed(2);
								}else{
									$('#deposit_input p').show();
								}
							}
						})
					}

					GV.loginState=true;

						if(callFunc){
							callFunc(data);
						}				
				}else{
					if(type==1){				
						$('.welcomeLang').html('欢迎访问ThinkEMall电子商城！<a data-toggle="modal" data-target="#loginModal" href="javascript:;">登录商城</a><a id="regBtn" href="'+GV.regURL+'">免费注册</a>');
						$("#main-menu-user .offline").show();
						$("#main-menu-user .login").hide();
					//激活登录框
					}else if(type==0){
						$('#loginModal').modal();
					}
					GV.loginState=false;	
					if(failCall){
						failCall();
					}
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown){
				alert('网络错误，无法验证用户登录状态！：'+textStatus);

			}

	
		/* $.post("{:U('user/notification/getLastNotifications')}",{},function(data){
			$(".nav .notifactions .count").text(data.list.length);
		}); */
		
	});		
}


;(function ($) {

	$.fn.setMicroDropMenu=function(options){
		var dft={
			menuStyle:'menu-drop',
			dropMenuSytle:'menuItemWrap',
			width:'200px',
			callFunc:$.noop()
		}

		Option=$.extend(dft,options);
		$this=$(this);
		$this.attr('class',Option.menuStyle);

		$this.hover(function(){
			dropMenu=$(this).find('.'+Option.dropMenuSytle);
			dropMenu.css('width',Option.width)
			dropMenu.show();
			if(Option.callFunc){
				Option.callFunc();
			}
		},function(){
			$(this).find('.'+Option.dropMenuSytle).hide();

		});
	}

	//商品单品项图片显示控制，也可用于类似的图片幻灯预览
	$.fn.setGoodsSlider=function(options){
		var dft={
			direction:'horizontal',	//滚动方向
			sliderCount:5,			//设置单品项的默认显示数
			curSKUPage:1, 			//初始显示页数
			thumbWrapper:'.sku',	//小图容器名
			naturePic:'.goodsList',	//大图容器名，用于选中时改变显示对应缩略图的原始图,null时不会显示大图
			hoverStyle:'sku-hover',	//选中时的样式，null时不会监听选中事件
			pageStyle:0, 			//0表示默认的翻页样式，不加入图标，大于0时可以自己定义翻页图标样式
			width:0,				//幻灯容器宽度，0时表示自动计算所需宽度，非0时用于横向滚动时固定宽度
			height:0				//幻灯容器高度，0时表示自动计算所需高度，非0时用于纵向滚动时固定高度
		}
		var Option=$.extend(dft,options);
		var $this=$(this);

		var li=$this.find('li');		
		var SKUCount=li.length;
		var thumbWrapper=$this.find(Option.thumbWrapper);
		var pageCount=(SKUCount+Option.sliderCount-1)/Option.sliderCount;
		var marginR,itemWidth,liWidth,itemWidth,curSelSKU=null;
		//根据滚动方向取间隔距离以及显示项的总宽、高,为了方便，纵向滚动的宽度等变量不再单独声明使用
		if(Option.direction=='horizontal'){
			marginR=parseInt(li.css('marginRight'));
			liWidth=parseInt(li.css('width'));
			if(Option.width==0){
				//这里会根据滚动方向获取幻灯缩略图元素中右、底边距加入计算，所以样式中一定要以右、底边距来做元素与元素之间的距离间隔
				itemWidth=(Option.sliderCount-1)*marginR+Option.sliderCount*liWidth;
				thumbWrapper.css('width',itemWidth);
			}else{
				itemWidth=Option.width;
				thumbWrapper.css('width',itemWidth);
			}
		}else{
			marginR=parseInt(li.css('marginBottom'));
			liWidth=parseInt(li.css('height'));
			if(Option.height==0){
				itemWidth=(Option.sliderCount-1)*marginR+Option.sliderCount*liWidth;
				thumbWrapper.css('height',itemWidth);
			}else{
				itemWidth=Option.height;
				thumbWrapper.css('height',itemWidth);
			}
		}


		if(SKUCount>Option.sliderCount){
			thumbWrapper.css('margin','0 auto');
			//根据翻页样式式添加翻页按钮
			if(Option.pageStyle==0){
				$this.append('<div class="ps-prev"><</div><div class="ps-next">></div>');
			}else{
				$this.append('<div class="ps-prev"></div><div class="ps-next"></div>');
			}
			//滚动翻页
			$this.find('.ps-prev').click(function(){
				if(Option.curSKUPage>1){
					Option.curSKUPage--;
					itemList=$(this).parent().find('ul');
					//根据方向设定滚动
					if(Option.direction=='horizontal'){
						oldMargin=parseInt(itemList.css('marginLeft'));
						newMargin=oldMargin+itemWidth+marginR;
						itemList.animate({marginLeft:newMargin+'px'});
					}else{
						oldMargin=parseInt(itemList.css('marginTop'));
						newMargin=oldMargin+itemWidth+marginR;
						itemList.animate({marginTop:newMargin+'px'});						
					}
				}
			});

			$this.find('.ps-next').click(function(event){
				if(Option.curSKUPage<pageCount){
					Option.curSKUPage++;
					itemList=$(this).parent().find('ul');
					//根据方向设定滚动
					if(Option.direction=='horizontal'){					
						oldMargin=parseInt(itemList.css('marginLeft'));
						newMargin=oldMargin-(itemWidth+marginR);
						itemList.animate({marginLeft:newMargin+'px'});
					}else{
						oldMargin=parseInt(itemList.css('marginTop'));
						newMargin=oldMargin-(itemWidth+marginR);
						itemList.animate({marginTop:newMargin+'px'});						
					}
				}
			});
		}
		//选中事件监听
		if(Option.hoverStyle!==null){
			li.hover(function(){
				selLi=$(this);
				if(curSelSKU!==selLi){
					if(curSelSKU!==null){
						curSelSKU.removeClass('curSel');
						curSelSKU.find('div').removeClass(Option.hoverStyle);					
					}

					liImg=selLi.find('img');
					skuImgSrc=liImg.attr('data-skuimg');
					//console.log(skuImgSrc);
					//主图切换
					mainImg=selLi.closest(Option.naturePic).find('img[class="listMainImg"]');
					mainImg.attr({'src':skuImgSrc,'data-nature':liImg.attr('data-nature')});			
					
					selLi.addClass('curSel');
					selLi.find('div').addClass(Option.hoverStyle);
					curSelSKU=selLi;
				}
			});

			//默认选中第一个元素
			selLi=$this.find('li:first');
			skuImgSrc=selLi.find('img').attr('data-skuimg');
			selLi.closest(Option.naturePic).find('img[class="listMainImg"]').attr('src',skuImgSrc);			
			selLi.addClass('curSel');
			selLi.find('div').addClass(Option.hoverStyle);
			curSelSKU=selLi;
		}
	}


	//简易商品展示图片预览放大镜
	$.fn.setPicZoomPreview=function(options){
		var dft={
			bigImgWidth:800,			//大图尺寸
			bigImgHeight:800
		}
		
		var Option=$.extend(dft,options);
		var wrapperWidth=parseInt($(this).css('width'));
		var wrapperHeight=parseInt($(this).css('height'));
		var zoomCtrl,zoomDiv,zoomCtrlWidth,zoomCtrlHeight,zoomDivWidth,zoomDivHeight;
		var offset = $(this).offset();
		var zoomImg,zoomWMulti,zoomHMulti,moveOffsetX=0,moveOffsetY=0,relativeX,relativeY,zoomCtrlLeft,zoomCtrlTop;
		//添加移除放大镜组件
		$(this).hover(function(){
			zoomCtrl=$('<div class="zoomCtrl"></div>').appendTo($(this));
			zoomDiv=$('<div class="zoomDiv"></div>').appendTo($(this));
			zoomImg=$('<img src="'+$(this).find('img').attr('data-nature')
				+'" width+"'+Option.bigImgWidth+'" height="'+Option.bigImgHeight+'" />').appendTo(zoomDiv);

			zoomCtrlWidth=parseInt(zoomCtrl.css('width'));
			zoomCtrlHeight=parseInt(zoomCtrl.css('height'));
			zoomDivWidth=parseFloat(zoomDiv.css('width'));
			zoomDivHeight=parseFloat(zoomDiv.css('height'));
			//获取缩放倍率
			zoomWMulti=Option.bigImgWidth/zoomDivWidth;
			zoomHMulti=parseFloat(Option.bigImgHeight/zoomDivHeight);
			$(this).find('i').hide();
		},function(){
			if(zoomCtrl){
				zoomCtrl.remove();
				zoomDiv.remove();				
			}
			$(this).find('i').show();
		});

		//跟随鼠标计算显示放大的图片位置
		$(this).mousemove(function(e){
			if(!zoomCtrl|!zoomDiv){
				return false;
			}
			relativeX = (e.pageX - offset.left);
			relativeY = (e.pageY - offset.top);
			zoomCtrlLeft = relativeX-zoomCtrlWidth/2;
			zoomCtrlTop = relativeY-zoomCtrlHeight/2;

			zoomCtrl.css('left',zoomCtrlLeft);
			zoomCtrl.css('top',zoomCtrlTop);

			if(zoomCtrlLeft<0){
				zoomCtrl.css('left',0);
			}

			if(zoomCtrlTop<0){
				zoomCtrl.css('top',0);
			}

			if(zoomCtrlLeft+zoomCtrlWidth>wrapperWidth){
				zoomCtrl.css('left',wrapperWidth-zoomCtrlWidth);
			}

			if(zoomCtrlTop+zoomCtrlHeight>wrapperHeight){
				zoomCtrl.css('top',wrapperHeight-zoomCtrlHeight);
			}

			moveOffsetX=(parseInt(zoomCtrl.css('left'))+zoomCtrlWidth/2)*zoomWMulti;
			moveOffsetY=(parseFloat(zoomCtrl.css('top'))+parseFloat(zoomCtrlHeight/2))*zoomHMulti;
			zoomImgLeft=parseFloat(moveOffsetX-zoomDivWidth/2);
			zoomImgTop=parseFloat(moveOffsetY-zoomDivHeight/2);
			//定位放大显示位置
			zoomImg.css({'margin-left':0-zoomImgLeft,'margin-top':0-zoomImgTop});	

			//console.log((0-zoomImgLeft)+','+(0-zoomImgTop));		
		})
	}

	//tab菜单控件
	//使用时需要在代码上添加属性，菜单项标签中添加role="tabItem",对应内容添加role="tabContent"
	//内容与tab标签的顺序需要对应
	$.fn.setTabMenu=function(option){
		var dft={
			//tab菜单切换时的方式，normal表示普通tab，直接加样式,line表示以亮色线的移动标识切换,线的样式名统一固定为activeLine
			tabChangeStyle:0,
			//是否有对应tab的内容显示
			hasTabContent:true,
			//是否有tab域外的内容需要隐藏显示
			hasExtra:false,
			//用于查找Tab域外关联内容，但hasExtra的值需要设置为true，如：
			//点击一个tab，但有隐藏或者显示的内容在页面的其它元素里，这时可以设置此参数为外部元素的容器名
			//不指定时会进行全局查找
			bindCWrap:'',
			//额外内容的role属性前缀名，后请接-1,-2,-n这样的数字
			extraContentPrefix:''			
		}

		var Options=$.extend(dft,option);

		var $tabWrapper=$(this);
		var allTabItem=$('[role="tabItem"]',this);
		var curSelMenu=$('[role="tabItem"]:first',this);
		curSelMenu.addClass('curSel');
		var curShowContent=$('[role="tabContent"]:first',this);
		var activeLine=$('.activeLine',$tabWrapper);

		//线条激活样式的事件监听
		if(Options.tabChangeStyle==1){
			var marginEnd,lineWidth,hoverTab;
			allTabItem.hover(function(){
				hoverTab=$(this);
				marginEnd=hoverTab.position().left;
				lineWidth=hoverTab.width();
				activeLine.stop().animate({'margin-left':marginEnd+'px','width':lineWidth+'px'},150);
			},function(){
				lineWidth=$(this).width();
				marginEnd=curSelMenu.position().left;	
			});

			//移开tabMenu时触发亮线回滚
			$tabWrapper.on('mouseleave',function(){
				if(hoverTab){
					if(!hoverTab.hasClass('curSel')|curSelMenu!==hoverTab|activeLine.position().left!==marginEnd){
						activeLine.stop().animate({'margin-left':marginEnd,'width':lineWidth+'px'},150);			
					}						
				}
			})
		}

		//监听菜单
		allTabItem.click(function(e){
			e.stopPropagation();
			if(!$(this).hasClass('curSel')){
				curSelMenu.removeClass('curSel');
				$(this).addClass('curSel');

				if(Options.tabChangeStyle==1){
					var marginEnd=$(this).position().left;
					var lineWidth=$(this).width();
					curSelMenu.find('a').removeClass('cur');
					$(this).find('a').addClass('cur');
					//activeLine.animate({'margin-left':marginEnd+'px','width':lineWidth+'px'},200);
				}

				//显示隐藏对应内容
				if(Options.hasTabContent){
					curShowContent.hide();
					showContent=$tabWrapper.find('[role="tabContent"]:eq('+$(this).index()+')').show();
					//显示隐藏tab域外的对应内容
					if(Options.hasExtra==true){
						if(Options.bindCWrap!==''){
							CWrap=$(Options.bindCWrap)
							CWrap.find('[role="'+Options.extraContentPrefix+curShowContent.index()+'"]').hide();
							CWrap.find('[role="'+Options.extraContentPrefix+$(this).index()+'"]').show();
						}else{
							console.log(curSelMenu.index()+','+$(this).index());
							$('[role="'+Options.extraContentPrefix+curSelMenu.index()+'"]').hide();
							$('[role="'+Options.extraContentPrefix+$(this).index()+'"]').show();
						}
					}	
					curShowContent=showContent;			
				}

				curSelMenu=$(this);

			}
			//console.log($(this).index());
		})		
	}	

	//控制文档滚动的tab菜单,使用时，tab菜单需要加入role="scrollTabItem"属性
	$.fn.setScrollTabMenu=function(option){
		var dft={
			bindTab:'' 			//绑定关联的对应Tab菜单，设置后将以绑定对象为基准切换
		}

		var Options=$.extend(dft,option);	
		curSelSMenu=$(this).find('[role="scrollTabItem"]:first');

		$(this).find('[role="scrollTabItem"]').click(function(){
			//if(!$(this).hasClass('curSel')){
				curSelSMenu.removeClass('curSel');
				$(this).addClass('curSel');
				curSelSMenu=$(this);
				//执行关联绑定的Tab菜单代码
				if(Options.bindTab!==''){
					bindTab=$(Options.bindTab);
					bindTab.find('[role="tabItem"]:eq('+$(this).index()+')').trigger('click');
					//页面滚动到关联的Tab菜单顶部
					$(window).scrollTop(bindTab.offset().top);					
				}

			//}
		})	
	}

	//顶部浮动菜单脚本用于窗口scroll滚动事件，回调函数自定，使用时需要绑定到显示位置所在的对象上
	$.fn.setFixedTopMenu=function(option){
		var dft={
			menuWidth:40,						//菜单导航自身的宽度
			offsetTop:$(this).offset().top,		//顶部偏移定位
			offsetLeft:0,						//左偏移定位，偏移起始位置是绑定的对象位置而不是0
			load:$.noop,						//加载时的事件
			//offset:元素在文档中的顶部绝对位置，scrollTop:当前窗口滚动条的位置
			cbFunc:function(offsetTop,scrollTop,offsetLeft){}		
		}

		var Options=$.extend(dft,option);
		Options.load();
		var offsetLeft=Options.offsetLeft;		
		fixTopMenu=$(this);
		//var offsetTop=fixTopMenu.offset().top;
		$(window).scroll(function(){
			//console.log($this.offset().top+','+$(this).scrollTop());
			Options.cbFunc(Options.offsetTop,$(this).scrollTop(),offsetLeft);
		})

		if(Options.offsetLeft>0){
			offsetLeft=($(window).width()-fixTopMenu.outerWidth())/2-(Options.offsetLeft+Options.menuWidth);
			Options.cbFunc(Options.offsetTop,$(this).scrollTop(),offsetLeft);

			$(window).resize(function(){
				offsetLeft=($(this).width()-fixTopMenu.outerWidth())/2-(Options.offsetLeft+Options.menuWidth);
				//console.log(Options.offsetLeft);
				Options.cbFunc(Options.offsetTop,$(this).scrollTop(),offsetLeft);
			})
		}

	}


	//商品描述浮动侧面导航菜单
	$.fn.setFixedScrollMenu=function(option){
		var dft={
			type:'fixed',			//显示方式，absolute在老浏览器中显示有抖动
			linkElement:'',			//fixed模式时使用，用于定位，如果需要附在某个元素的右边可以填写
			marginTop:46, 			//顶边距
			marginLeft:'',			//左边距
			offsetMargin:0 			//浮动偏移量如:fixed显示时受父样式中设置margin-top:22的影响而无法置顶，那么设置此值即可
		}

		var Options=$.extend(dft,option);
		$scrollMenu=$(this);
		$scrollMenu.css('height',$(window).height());
		var marginTop;
		var offsetTop=$scrollMenu.offset().top;
		var marginLeft=$scrollMenu.offset().left;

		if(Options.type=='fixed'){
			if(Options.marginLeft==''){
				Options.marginLeft=marginLeft;
			}
			//当窗口尺寸变化时重新调整浮动位置
			$(window).resize(function(){
				setTimeout(function(){
					linkElement=$(Options.linkElement)
					linkElementMarginLeft=linkElement.offset().left+parseInt(linkElement.css('width'));
					marginLeft=Options.marginLeft=linkElementMarginLeft;
					if($scrollMenu.css('position')=='fixed'){
						//console.log(Options.marginLeft);
						$scrollMenu.css({
							'position':Options.type,
							"top":marginTop+'px',
							'left':Options.marginLeft+'px'
						});				
					}
				},150);
			})
		}
		//监听滚动事件
		$(window).on('scroll',function(event){
			scrollTop=$(this).scrollTop();
			if(scrollTop>offsetTop){	
				//console.log(scrollTop-offsetTop);
				if(Options.type=='absolute'){
					marginTop=parseInt(scrollTop-offsetTop+Options.marginTop);
				}else{
					//
					marginTop=Options.marginTop-Options.offsetMargin;
				}

				$scrollMenu.css({
					'position':Options.type,
					"top":marginTop+'px',
					'left':Options.marginLeft+'px'
				});
			}else{
				$scrollMenu.css({
					'position':'absolute',
					"top":0,
					'left':''
				});
			}
		});	
	}

	//SKU表单项的索引值转换，用于查找存在的SKU表单项对应数据，转换索引用的是两项SKU属性元素的索引标识
	//rowIdx:规格类单品项属性元素的索引值
	//colIdx:外观样式类单品项属性元素的索引值
	changeToSKUFormIdx=function(rowIdx,colIdx){
		//如果只有单个单品属性被选中，那么直接返回选择的单品属性元素索引
		if(rowIdx<0){
			return skuGroupIdx[colIdx];
		}else if(colIdx<0){
			return skuGroupIdx[rowIdx];
		}
		return skuGroupIdx[[rowIdx,colIdx]];
	}

	//所有单品项组合的索引值生成，用于单品项表单创建位置排序时的索引值对比
	//SpecNum:存在的规格类单品项属性数量
	//StyleNum:存在的样式外观类单品项属性数量
	//isCompare:由sku_idx数据行索引值对比获取当前的单品属性对应选择项，用于购物车的图片数据调用，true时对比返回外观单品属性的索引值
	createSKUOptionIdx=function(SpecNum,StyleNum,isCompare,sku_idx){
		//获取主副两个单品项属性的元素个数
		colIdx=StyleNum-1;
		rowIdx=SpecNum-1;
		//console.log(colIdx+','+rowIdx);

		var groupIdx=new Array();
		idxValue=-1;
		if(SpecNum>0 && StyleNum>0){
			for(i=0;i<=rowIdx;i++){
				for(j=0;j<=colIdx;j++){
					idxValue++;
					groupIdx[[i,j]]=idxValue;
					if(isCompare){
						if(idxValue==sku_idx){
							return {'sku_styleIdx':j,'sku_specIdx':i};
						}
					}
					//console.log('skuGroupIdx:['+i+']['+j+']'+skuGroupIdx[[i,j]]);
				}
			}			
		}else if(StyleNum>0){
			for(i=0;i<=colIdx;i++){
				idxValue++;
				groupIdx[i]=idxValue;
				if(isCompare){
					if(idxValue==sku_idx){
						return {'sku_styleIdx':i};
					}
				}
			}
		}else if(SpecNum>0){
			for(i=0;i<=rowIdx;i++){
				idxValue++;
				groupIdx[i]=idxValue;
				if(isCompare){
					if(idxValue==sku_idx){
						return {'sku_specIdx':i};
					}
				}
			}
		}
		//console.log(skuGroupIdx);
		return groupIdx;
	}

	//用于更新当前选中单品的价格和库存信息
	//skuFormInfo:SKU单品项数据
	changeSKUInfoText=function(skuFormInfo){
		//设置价格
		var mPrice=$('#J_StrPriceModBox .tm-price');
		//商品市场
		var marketPrice=parseFloat(market_price);
		//促销价格
		var discountPrice=$('#J_PromoPrice .tm-price');

		//如果为负值，表示单品已经没有库存
		if(parseFloat(skuFormInfo.sku_price)>0){
			//判断单品价格是否高于市场价，高于市场价则市场价文本按单品原价显示
			if(marketPrice>skuFormInfo.sku_price){
				mPrice.text(marketPrice.toFixed(2));
			}else{
				//只要设置了单品价格，显示的单品实价即为设置的单品价格
				mPrice.text(Number(skuFormInfo.sku_price).toFixed(2));
			}
			//促销价
			discountPrice.text(Number(skuFormInfo.sku_price).toFixed(2));
		//没有设置单品价格时按默认的实价显示
		}else{
			mPrice.text(marketPrice.toFixed(2));
			discountPrice.text(Number(goods_price).toFixed(2));
		}

		//设置库存
		$('#J_EmStock').text('库存'+skuFormInfo.sku_stock+'件');
	}

	//SKU单品项数据解析器:P_SelSKUIdx为数据加载完成后默认选中的SKU单品项数据行索引，传入此参数可以在解析后选中对应的单品项属性
	$.fn.skuParse=function(skuJsonStr,P_SelSKUIdx,isJsonObj){
		//初始加载时遍历每个单品的库存状态，如果所有库存为0则禁止购买:SKU_Element为单品属性对象
		//colIdx是当前选择的样式外观类单品项的索引值，如果存在，则表示包含两个单品项
		//如果SKU_Element为null，则表示当前商品没有SKU数据，直接取库存值 对比
		setItemStockState=function(SKU_Element,colIdx){
			//无任何SKU单品属性时直接按库存处理购买的按钮状态
			if(SKU_Element==null){
				if(goods_stock>0){
					setShopBtnState(1);
				}else{
					setShopBtnState(0);
				}
				return false;
			}

			//判断当前点击的外观样式类单品是否是无库存状态，并且已经完成单品的初始化选择，是则跳过执行
			if(colIdx>=0){
				var curSelStyleItem=SKU_Style.find('li:eq('+colIdx+')');
				if(curSelStyleItem.hasClass('outOfStock') && SKU_Init==false){
					//重新切换回原来选择的单品
					SKU_Style.find('li:eq('+curSelSKUStyle+')').trigger('click');
					return true;
				}
			}
			//
			var allOutOfStock=true;
			SKU_SpecItem=SKU_Element.find('li');
			$.each(SKU_SpecItem,function(index){
				var stockIdx;
				if(colIdx>=0){
					stockIdx=changeToSKUFormIdx(index,colIdx);
				}else{
					stockIdx=index;
				}
				//console.log('index:'+stockIdx+',curindex:'+index+',colIdx:'+colIdx+','+skuFormData[stockIdx].sku_stock);
				if(skuFormData[stockIdx].sku_stock<=0){
					$(this).addClass('outOfStock');
					//如果当前无库存的规格类单品项已经被选中，去除样式
					if($(this).hasClass('curSel')){
						removeSKUSelState($(this),'curSel','i');
						//标识当前选中的单品无库存
						curSelOutStock=true;
					}
				}else{
					//移除已经存在的选中项标识及样式属性
					$(this).removeClass('outOfStock');
					allOutOfStock=false;
				}
			})
			if(allOutOfStock==false){
				//返回第一个有库存的单品项后标识初始化选择完成
				if(SKU_Init==true){
					SKU_Init=false;
				}
				//激活购买按钮
				setShopBtnState(1);
			}else{
				//如果当前选中的外观样式单品项对应的所有规格库存都为空时重新激活所有规格的显示状态，准备下一个单品的库存检测循环
				SKU_SpecItem.removeClass('outOfStock');
				//跳转回原来选择的单品项
				SKU_Element.find('li:eq('+curSelSKUSpec+')').trigger('click');

				//如果一个外观样式单品属性下所有库存都为零时禁用购买按钮
				//setShopBtnState(0);
				curSelStyleItem.addClass('outOfStock').removeClass('curSel').find('i').remove();
				//console.log($('[data-property="SKU_Style"] li:eq('+colIdx+')'));
			}
			return allOutOfStock;
		}

		//移除单品选中的状态
		removeSKUSelState=function(element,removeCSS,removeChild){
			element.removeClass(removeCSS);
			if(removeChild && removeChild!=='')
			element.find(removeChild).remove();
		}

		//购买按钮状态控制
		setShopBtnState=function(state){
			if(state==1){
				$('#J_LinkAdd').removeClass('disabled').attr('href','javascript:shopCart.addToCart(\''+GV.addToCartURL+'\')').setPopTip('destroy');
				$('#J_LinkBuy').removeClass('disabled').attr('href','javascript:shopCart.addToCart(\''+GV.addToCartURL+'\',true)');
			}else{
				var addToCartBtn=$('#J_LinkAdd');
				addToCartBtn.setPopTip({
					content:'抱歉，您选购的商品已经到达库存上限，下次来早点哦！',
	    			placement:'top',
	    			arrow:'bottom',
	    			arrowAlign:'vcenter'
    			});
    			addToCartBtn.addClass('disabled').attr('href','javascript:void(0);');
				$('#J_LinkBuy').addClass('disabled').attr('href','javascript:void(0);');				
			}
		}

		//选中第一个有库存的单品:SKU_Element是单品项元素对象
		//SKU_Name是单品项元素的名称，这个用来区分在座当前操作的元素的索引值
		selectSKUFirstItem=function(SKU_Element,SKU_Name){
			var curSelItem;
			$.each(SKU_Element.find('li'),function(){
				if(!$(this).hasClass("outOfStock")){
					curSelItem=$(this);
					if(SKU_Name=='SKU_Style'){
						curSelSKUStyle=$(this).index();

					}else if(SKU_Name=='SKU_Spec'){
						curSelSKUSpec=$(this).index();
					}					
					return false;
				}
			})
			return curSelItem;
		}

		//如果没有SKU单品数据，则直接更新库存状态后退出程序执行
		if(skuJsonStr==''){
			setItemStockState(null);
			return false;
		}

		var skuData=isJsonObj?skuJsonStr:$.parseJSON(skuJsonStr);
		//console.log(skuData);
		//获取所有单品项行数据
		skuFormData=skuData.SKU_Form;
		//获取外观样式类单品属性数据，用于购物车数据提交
		SKU_StyleData=skuData.SKU_Style;
		//页面加载时默认会选中第一个可购买的单品项，在选中这个单品项前此值为true，选中后为false;
		var SKU_Init=true;

		var styleCode='<dl><dt class="tb-metatit">外观</dt><dd><ul data-property="SKU_Style" class="imgView">';
		var specCode='<dl><dt class="tb-metatit">规格</dt><dd><ul data-property="SKU_Spec" class="">';
		var styleList='',specList='';
		var SKU_Style,SKU_Spec;
		var SKU_StyleCount,SKU_SpecCount;
		var firstSpecTrigger=firstStyleTrigger=true;
		var curSelOutStock,allSpecOutStock=false;

		//判断是否存在SKU单品外观样式或者规格类数据，不存在的设置长度为-1
		if(skuData.SKU_Style){
			SKU_StyleCount=skuData.SKU_Style.length;
		}else{
			SKU_StyleCount=0;
		}
		if(skuData.SKU_Spec){
			SKU_SpecCount=skuData.SKU_Spec.length;
		}else{
			SKU_SpecCount=0;
		}

		//返回选中的单品属性索引，用于查找对应的SKU单品外观样式图片
		var selSKUGroupIdx;
		if(P_SelSKUIdx){
			selSKUGroupIdx=createSKUOptionIdx(SKU_SpecCount,SKU_StyleCount,true,P_SelSKUIdx);
			console.log(selSKUGroupIdx);
			//手动设定当前选中的两个单品项索引值
			curSelSKUSpec=selSKUGroupIdx.sku_specIdx?selSKUGroupIdx.sku_specIdx:-1;
			curSelSKUStyle=selSKUGroupIdx.sku_styleIdx?selSKUGroupIdx.sku_styleIdx:-1;
		}

		//生成单品项属性序列索引值存储的二维数组
		skuGroupIdx=createSKUOptionIdx(SKU_SpecCount,SKU_StyleCount);
		//console.log(skuGroupIdx);

		if(SKU_StyleCount>0){
			//样式外观参数
			$.each(skuData.SKU_Style,function(index,Item){
				var skuContent;
				if(Item.SKU_ThumbImg==''){
					skuContent=Item.modifyValue;
				}else{
					skuContent='<img src="'+Item.SKU_ThumbImg+'" width="100%" >';
				}
				styleList+='<li><a href="javascript:void(0);" tabindex="'+index+'">'+skuContent+'</a></li>';
			});			

			styleCode=styleCode+styleList+'</ul></dd></dl>';
			SKU_Style=$(styleCode).prependTo($(this));
		}

		if(SKU_SpecCount>0){
			//规格参数
			$.each(skuData.SKU_Spec,function(index,Item){
				specList+='<li><a href="javascript:void(0);" tabindex="'+index+'">'+Item.modifyValue+'</a></li>';
			});

			specCode=specCode+specList+'</ul></dd></dl>';
			SKU_Spec=$(specCode).prependTo($(this));

		}
					
		//存在外观样式单品时添加选择事件监听
		if(SKU_Style){
			//绑定单品项属性单击事件
			SKU_Style.find('li').click(function(){
				//if(!$(this).hasClass('curSel')){
					var $this=$(this);
					var rowIdx=SKU_SpecCount>0?curSelSKUSpec:-1;
					var colIdx=$(this).index();
					//console.log(rowIdx+','+colIdx);

					//显示外观样式属性图片数据
					var SKU_Img=skuData.SKU_Style[colIdx].SKU_Img;
					var SKU_ThumbImg=skuData.SKU_Style[colIdx].SKU_ThumbImg_M;
					var outStockItem,checkIdx;
					if(SKU_Img!==''){
						$('#previewPic img').attr({'src':SKU_ThumbImg,'data-nature':SKU_Img});								
					}else{
						//如果只有文字，则按文字样式显示
						$(this).closest('ul[data-property="SKU_Style"]').attr('class','txtView');
						if(P_SelSKUIdx){
							$('#previewPic img').attr({'src':$this.closest('.cartListContent').find('.itemImg img').attr('src')});
						}
					}

					//无库存处理，SKU库存存在三种判断方式，这里判断两种，
					//分别是同时有规格类及外观样式单品项和只有外观样式类单品项
					if(SKU_SpecCount>0){

						//如果所当前所有单品规格都没有库存则禁止购买
						if(setItemStockState(SKU_Spec,colIdx)==false){
							//更新选中状态
							$this.addClass('curSel');
							$this.append('<i></i>');
							//如果当前选中的单品无库存，找到第一个有库存的单品,如果上一个单品规格全无库存执行同样操作
							if(curSelOutStock==true | allSpecOutStock==true){
								//由于指定默认选中项只在首次加载SKU数据时执行一次，所以第一次模拟点击执行完后不再干扰原始程序执行
								if(!P_SelSKUIdx){
									selectSKUFirstItem(SKU_Spec,'SKU_Sepc').trigger('click');										
								}else if(P_SelSKUIdx && firstStyleTrigger==false){
									selectSKUFirstItem(SKU_Spec,'SKU_Sepc').trigger('click');	
								}
								curSelOutStock=allSpecOutStock=false;
							}
							//移除已经存在的选中项标识及样式属性
							if(colIdx!==curSelSKUStyle){
								removeSKUSelState(SKU_Style.find('li:eq("'+curSelSKUStyle+'")'),'curSel','i');										
							}
							curSelSKUStyle=colIdx;

						//如果当前外观样式单品对应的所有规格都没有库存，则自动往下选，也可根据需要选择执行下面的注释代码段
						}else{

							//如果想保持外观样式单品属性在任何情况下都可选中可以注释掉下面这行代码并移除上方的代码段注释
							if(!P_SelSKUIdx){

								//如果当前是初始加载页面，自动继续循环选择第一个有库存的单品项
								if(SKU_Init==true){
									selectSKUFirstItem(SKU_Style,'SKU_Style').trigger('click');
								//初始化完成后再点击无库存的单品时显示无库存提示
								}else{
									//SKU_Style.find('li:eq("'+curSelSKUStyle+'")').trigger('click');
									$this.setPopTip('destroy');
									//绑定显示无库存的提示框
									$this.setPopTip({
										content:'抱歉，您选购的商品已经脱销，下次来早些哦！',
										placement:'bottom',
										arrow:'top',
										arrowAlign:'vcenter',
										showonce:true
									})
								}
								
							}else{
								//如果当前是初始加载页面，自动继续循环选择第一个有库存的单品项
								if(SKU_Init==true){
									selectSKUFirstItem(SKU_Style,'SKU_Style').trigger('click');
								//初始化完成后再点击无库存的单品时显示无库存提示
								}else{
									$this.setPopTip('destroy');
									//绑定显示无库存的提示框
									$this.setPopTip({
										content:'抱歉，您选购的商品已经脱销，下次来早些哦！',
										placement:'bottom',
										arrow:'top',
										arrowAlign:'vcenter',
										showonce:true
									})	
								}						
							}
							return false;
						}

					//只有外观样式类单品项数据时的遍历	
					}else{
						if(setItemStockState(SKU_Style)==true){
							//手动改变库存量
							changeSKUInfoText({'sku_price':-1,'sku_stock':0});
							return false;
						}
						//如果当前点击的商品无库存，查看是否是页面初次加载
						if($(this).hasClass('outOfStock')){
							//如果是默认加载页面时点击的第一个单品，则往下查找有库存的单品，之后不再跳选
							if(firstStyleTrigger==true){
								if(!P_SelSKUIdx){
									selectSKUFirstItem(SKU_Style,'SKU_Style').trigger('click');
								}
								firstStyleTrigger=false;
								return false;
							}else{
								return false;
							}						
						}

						//console.log(checkIdx);
							$this.addClass('curSel');
							$this.append('<i></i>');
							//移除已经存在的选中项标识及样式属性
							if(colIdx!==curSelSKUStyle){
								removeSKUSelState(SKU_Style.find('li:eq("'+curSelSKUStyle+'")'),'curSel','i');										
							}
							curSelSKUStyle=colIdx;
					}
					//获取当前选择的单品项数据行索引值
					curSelSKUIdx=changeToSKUFormIdx(curSelSKUSpec,colIdx);
					//console.log(curSelSKUIdx);

					//获取显示单品项数据
					changeSKUInfoText(skuData.SKU_Form[curSelSKUIdx]);
	
					//根据库存重新设置购买数量,前提是邮费模板加载完成，所以第一次不执行此操作
					if(curSelProvince)
					setAmount.modify('#buy-num',0,'SKU');
					//console.log(rowIdx+','+colIdx);
					//console.log(skuFormIdx);
				//}

			})
		}

		if(SKU_Spec){
			SKU_Spec.find('li').click(function(){
				if(!$(this).hasClass('outOfStock')){
					if(!$(this).hasClass('curSel')){
						var colIdx,rowIdx;
						curSel=$(this).parent().find('.curSel');
						curSel.removeClass('curSel');
						curSel.find('i').remove();
						$(this).addClass('curSel');
						$(this).append('<i></i>');

						//获取显示单品项数据,如果不存在选中的外观样式，表示是进入页面的状态，默认选中第一个
						if(SKU_StyleCount>0){
							if(curSelSKUStyle>=0){
								colIdx=curSelSKUStyle;
							}else{
								colIdx=0;							
							}
						}else{
							colIdx=-1;
						}
						rowIdx=$(this).index();
						curSelSKUIdx=changeToSKUFormIdx(rowIdx,colIdx);
						changeSKUInfoText(skuData.SKU_Form[curSelSKUIdx]);
						curSelSKUSpec=rowIdx;

						//console.log(rowIdx+','+colIdx);
						//console.log(curSelSKUIdx);
					}
					//构造商品数据，用于购物车
					goodsData=shopCart.createShopData();
					//根据库存重新设置购买数量,前提是邮费模板加载完成，所以第一次不执行此操作
					if(curSelProvince)
					setAmount.modify('#buy-num',0,'SKU');
				}else{
					//如果是默认加载页面时点击的第一个单品，则往下查找有库存的单品，之后不再跳选
					if(firstSpecTrigger==true){
						if(allSpecOutStock==false){
							//检查是否有默认设定选中参数，没有则执行选中第一个单品项
							if(!P_SelSKUIdx){
								selectSKUFirstItem(SKU_Spec,'SKU_Spec').trigger('click');								
							}
							firstSpecTrigger=allSpecOutStock=false;							
						}else{
							firstSpecTrigger=false;
						}
					}
				}
			});

			//不存在指定选中项时执行正常的默认选中第一个有库存的单品属性
			var specIdx;
			if(selSKUGroupIdx){
				specIdx=selSKUGroupIdx.sku_specIdx>=0?'eq('+selSKUGroupIdx.sku_specIdx+')':'first';
			}else{
				specIdx='first';
			}
				//加载页面后模拟首次点击单品规格类属性的第一个
				if(SKU_StyleCount>0){
					if(!P_SelSKUIdx){
						if(setItemStockState(SKU_Spec,0)==false){
							SKU_Spec.find('li:'+specIdx).trigger('click');
						}else{
							allSpecOutStock=true;
						}
					}else{
						//如果指定了默认选择的单品属性，先遍历全部外观样式单品属性判断库存，完成后再选择
						for(var i=0;i<SKU_StyleCount;i++){
							setItemStockState(SKU_Spec,i);
						}
						SKU_Spec.find('li:'+specIdx).trigger('click');
					}
				}else{
					if(setItemStockState(SKU_Spec)==false){
						SKU_Spec.find('li:'+specIdx).trigger('click');
					}
				}

			firstSpecTrigger=false;
		}

		//加载页面后模拟首次点击单品外观样式类属性的第一个
		if(SKU_Style){
			var styleIdx;
			if(selSKUGroupIdx){
				styleIdx=selSKUGroupIdx.sku_styleIdx>=0?'eq('+selSKUGroupIdx.sku_styleIdx+')':'first';
			}else{
				styleIdx='first';
			}
			SKU_Style.find('li:'+styleIdx).trigger('click');
			//如果指定了默认选中项，在首次执行完选中后，将不再干扰原程序的执行
			if(P_SelSKUIdx){
				firstStyleTrigger=false;
			}
		}
		
		console.log(skuData);
		//console.log(skuGroupIdx);
	}

})(jQuery);

//设置数量:element是对象元素的查找器字串，callFuncCode用于回调，0为计算运费,
//checkStock='SKU'时以当前页面商品库存校验可购买数，否则以购物车类单行商品库存进行校验
var setAmount={

	plus:function(element,callFuncCode,checkStock,doCartAccount){
		setInput=$(element);
		curNum=setInput.is(':input')?parseInt(setInput.val()):parseInt(setInput.text());
		//超出库存退出程序
		if(checkStock=='SKU'){
			//判断是否存在SKU数据
			if(skuFormData){
				if(skuFormData[curSelSKUIdx].sku_stock<=curNum){
					return false;
				}				
				//如果不存在，则直接比对商品库存数	
			}else{
				if(parseInt(setInput.attr('data-stock'))<=curNum){
					return false;
				}
			}
		
		}else{
			if(parseInt(setInput.attr('data-stock'))<=curNum){
				return false;
			}
		}

		curNum+=1;
		if(setInput.is(':input')){
			setInput.val(curNum);			
		}else{
			setInput.text(curNum);
		}

		//激活减数按钮
		if((curNum+1)>1){
			setInput.parent().find('.btn-reduce').removeClass('disabled');
		}
		switch(callFuncCode){
			case 0:
			staticLogisticsPrice({
				buyNum:curNum,
				selProvinceId:curSelProvince.attr('data-id')
			});
			break;
			default:
			break;
		}
		//更新购物车结算数据
		if(doCartAccount==true){
			if(GV.cartView=='fullView'){
				shopCart.logisticsPriceAccounts('refresh',null,setInput.closest('.cartList'));				
			}

			shopCart.cartAccounts();			
		}

	},

	reduce:function(element,callFuncCode,doCartAccount){
		setInput=$(element);
		curNum=setInput.is(':input')?parseInt(setInput.val()):parseInt(setInput.text());
		//禁用减数按钮及功能
		if(curNum>1){
			curNum-=1;
			if(setInput.is(':input')){
				setInput.val(curNum);			
			}else{
				setInput.text(curNum);
			}
		}else if(curNum<=1){
			setInput.parent().find('.btn-reduce').addClass('disabled');
			return false;
		}
		switch(callFuncCode){
			case 0:
			staticLogisticsPrice({buyNum:curNum,selProvinceId:curSelProvince.attr('data-id')});
			break;
			default:
			break;
		}
		//更新购物车结算数据
		if(doCartAccount==true){
			if(GV.cartView=='fullView'){
				shopCart.logisticsPriceAccounts('refresh',null,setInput.closest('.cartList'));				
			}
			shopCart.cartAccounts();			
		}
	},

	modify:function(element,callFuncCode,checkStock,doCartAccount){
		setInput=$(element);
		var stockCount;
		curNum=setInput.is(':input')?parseInt(setInput.val()):parseInt(setInput.text());
		if(!isNaN(curNum)){
			//超出库存设置为库存量
			if(checkStock=='SKU'){
					if(skuFormData){
						stockCount=skuFormData[curSelSKUIdx].sku_stock;
					}else{
						stockCount=parseInt(setInput.attr('data-stock'));
					}
			}else{
				stockCount=parseInt(setInput.attr('data-stock'));
			}

			if(curNum>stockCount){
				curNum=stockCount;	
			}else if(curNum<1){
				curNum=1;
			}

		}else{
			curNum=1;
		}
		setInput.val(curNum);

		//激活减数按钮
		if(curNum>1){
			setInput.parent().find('.btn-reduce').removeClass('disabled');
		}else{
			setInput.parent().find('.btn-reduce').addClass('disabled');
		}
		switch(callFuncCode){
			case 0:
			staticLogisticsPrice({buyNum:curNum,selProvinceId:curSelProvince.attr('data-id')});
			break;
			default:
			break;
		}
		//更新购物车结算数据
		if(doCartAccount==true){
			shopCart.logisticsPriceAccounts('refresh',null,setInput.closest('.cartList'));
			shopCart.cartAccounts();			
		}
	}
}


var curSelProvince;
var curSelCityBox,curSelCity;
//用户当前使用的收货地址区名索引号
var curUseProvince,curUseCity;
//运费计算方式
var logisticsType;
//商品重量
var goods_weight;
//商品体积
var goods_volume;

//前端运费信息计算显示，目前只按省级来判断
function displayAreaData(fromArea){
	$.ajax({
			type: 'GET',
			url: jsonURLRoot+'areaData.json',
			dataType: 'json',
			async:true,
			success: function(data){
				var htmlProvince = '';
	        	var provinceData = data.province;
	        	var cityData = data.city;
	        	var districtData = data.district;
	        	var fromProvince='',fromCity='';
	        	var isMunicipality=false;
	        	var curSelProvinceId;

				for(var i=0; i<provinceData.length;i++){
					if(provinceData[i].id==fromArea.provinceId){
						fromProvince+=provinceData[i].name;
					}
					//直辖市跳过
					if(provinceData[i].id==110000|provinceData[i].id==120000|provinceData[i].id==310000|provinceData[i].id==500000){
						continue;
					}
				  	htmlProvince += '<li><span data-id="'+provinceData[i].id+'">'+provinceData[i].name+'</span></li>';
				}
				//console.log(fromProvince);
				//获取发货城市
				for(var i=0;i<cityData[fromArea.provinceId].length;i++){
					if(cityData[fromArea.provinceId][i].id==fromArea.cityId){
						fromCity=cityData[fromArea.provinceId][i].name;
						break;
					}
				}
				//console.log(fromCity);

				$('#fromArea').text(fromProvince+' '+fromCity);
				var selAreaBox=$('<i class="closeIcon"></i><ul><li><span data-id="110000" class="cur">北京市</span></li><li><span data-id="120000">上海市</span></li><li><span data-id="310000">天津市</span></li><li><span data-id="500000">重庆市</span></li></ul><ul class="muityAreaList">'+htmlProvince+'</ul>').appendTo($('#selToArea .areaItem'));
				//默认选中北京
				curSelProvince=selAreaBox.find('li:eq(0) span');
				staticLogisticsPrice({
					buyNum:$('#buy-num').val(),
					selProvinceId:110000,
					countType:logisticsType
				});
				//绑定关闭事件
				$('#selToArea .closeIcon').click(function(){
					$('#selToArea .areaItem').hide();
				})
				//添加选择事件监听
				selAreaBox.find('li span').click(function(){
					var selProvinceId=$(this).attr('data-id');
					var selToArea=$('#selToArea .mui_addr_tri_1');
					var buyNum=$('#buy-num').val();
					if(selProvinceId==110000|selProvinceId==120000|selProvinceId==310000|selProvinceId==500000){
						selToArea.html($(this).text()+'<i class="menu-drop-arrow"></i>');
						selToArea.attr('data-id',selProvinceId);
						$('#selToArea .areaItem').hide();
						isMunicipality=true;
					}

					$(this).addClass('cur');
					curSelProvince.removeClass('cur');
					curSelProvince.find('s').remove()
					curSelProvince=$(this);
					curSelProvinceId=$(this).attr('data-id');
					//为了方便市区选择框定位，先移除存在的城市选项框
					if(curSelCityBox){
						curSelCityBox.remove();
					}
					//直辖市直接返回数据
					if(isMunicipality){
						//计算运费
						staticLogisticsPrice({
							buyNum:buyNum,
							selProvinceId:curSelProvinceId
						});
						isMunicipality=false;
						return false;
					}

					//显示市级区域,这里使用的是计算行数及行头元素来进行定位添加
					var curIdx=$(this).parent().index()+4+1;
					var rowSize=5;
					var curRow=parseInt(curIdx/rowSize);
					var nextRow=curRow*rowSize-1;
					//判断是否存在城市数据
					if(cityData[selProvinceId]){
						$(this).append('<s></s>');
						$('.muityAreaList').find('li:eq('+nextRow+')').after('<li class="selCityBox"><ul></ul></li>');
						curSelCityBox=$('.selCityBox');	

						//插入城市数据
						var htmlCity = '';
						for(var i=0;i<cityData[selProvinceId].length;i++){
							if(cityData[selProvinceId][i].name=='省直辖县级行政区划'|cityData[selProvinceId][i].name=='自治区直辖县级行政区划'){
								continue;
							}								
							htmlCity+='<li><span data-id="'+cityData[selProvinceId][i].id+'">'+cityData[selProvinceId][i].name+'</span></li>';

						}
						curSelCityBox.append(htmlCity);
						//加入选择城市的监听
						curSelCityBox.find('li span').click(function(){
							buyNum=$('#buy-num').val();
							if(curSelCity){
								curSelCity.removeClass('cur');
								curSelCity=$(this);
							}else{
								curSelCity=$(this);
							}
							$(this).addClass('cur');
							selToArea.html($(this).text()+'<i class="menu-drop-arrow"></i>');
							selToArea.attr('data-id',selProvinceId);
							//关闭
							$('.areaItem').hide();
							//计算运费
							staticLogisticsPrice({
								buyNum:buyNum,
								selProvinceId:curSelProvinceId,
								selCityId:$(this).attr('data-id')
							});
						});
					//不存在城市数据时直接返回选择结果，计算运费
					}else{
						selToArea.html($(this).text()+'<i class="menu-drop-arrow"></i>');
						selToArea.attr('data-id',selProvinceId);	
						//关闭
						$('.areaItem').hide();
						//计算运费
						staticLogisticsPrice({
							buyNum:buyNum,
							selProvinceId:curSelProvinceId
						});
					}
					//console.log(curIdx+','+curRow+','+nextRow);
				});

				//provinceForm.append(htmlProvince);
  

				/*选择省事件
		        provinceForm.change(function(){
		        	var code = $(this).val();
		        	if(code!=''){
		        		var cityCode  = cityData[code][0].id;
		        		var htmlCity = '',
		        			htmlDistrict = '';
		        		for(var i=0; i<cityData[code].length;i++){
		        			var selStr='';
							if(cityData[code][i].id==selData.provinceId){
								selStr='selected="selected"'
							}
						  	htmlCity += '<option value="'+cityData[code][i].id+'" '+selStr+'>'+cityData[code][i].name+'</option>';
						}
		        		$(city).html(htmlCity);
		        		if(district){
			        		for(var i=0; i<districtData[cityCode].length;i++){
			        			var selStr='';
								if(districtData[cityCode][i].id==selData.provinceId){
									selStr='selected="selected"'
								}
							  	htmlDistrict += '<option value="'+districtData[cityCode][i].id+'">'+districtData[cityCode][i].name+'</option>';
							}
			        		$('#district').html(htmlDistrict);	        			
		        		}

		        	}	        	
		        });*/
	        	//console.log(provinceData);
	        },
	        error: function(XMLHttpRequest, textStatus, errorThrown){
	        	alert('获取地区数据失败,请重新操作！');
	        	//console.log('请求失败');
		       /*alert(XMLHttpRequest.responseText); 
		       alert(XMLHttpRequest.status);
		       alert(XMLHttpRequest.readyState);
		       alert(textStatus); // parser error;*/
	        	return false;
	        }
	    })
}

//将配送方式的索引值转为对应的文本
function changeToLogistaticsStr(idx){
	var wayStr='';
		switch(idx){
			case '0':
				wayStr='快递';
				break;
			case '1':
				wayStr='物流';
				break;
			case '2':
				wayStr='EMS';
				break;
			case '3':
				wayStr='平邮';
				break;
		}
	return wayStr;
}

//运费计算函数（通过解析运费模板数据，根据选择的区域进行计算）
function staticLogisticsPrice(option){
	var dft={
		buyNum:0,						//购买的商品数量 
		selProvinceId:null,				//收货地址省区ID号
		selCityId:null,					//收货地址市县ID号
		viewType:null,					//视图类型，在购物车详情页中使用，一种是按同商品合并计费'sameGIDAccounts'，一种是按同商家商品计费（未完成启用）
		returnType:null					//返回的计算数据类型，'htmlCode'表示返回对应的html代码，'data'表示返回数据对象（在购物车详情页中使用）
	}

	var Options=$.extend(dft,option);

	var buyNum=Options.buyNum;
	var selProvinceId=Options.selProvinceId;
	var selCityId=Options.selCityId;
	var viewType=Options.viewType;
	var returnType=Options.returnType;
	var countType=logisticsType;

	if(tmplLogisticsData==''){
		alert('未获取到运费模板数据！无法正常解析商品数据！');
		return false;
	}
	var logisticsData=$.parseJSON(tmplLogisticsData);
	var wayStr='';
	var priceHtmlCode='';
	var priceObj={};
	var isSurpportArea=false;
	var returnData={};			//returnType为'data'时返回运费结算后的数据集

	//遍历每种配送方式设置
	$.each(logisticsData,function(idx){
		if(logisticsData[idx].length>0){
			//配送方式的索引值转为对应的文本
			wayStr=changeToLogistaticsStr(idx);

			$.each(logisticsData[idx],function(rowIdx,rowData){
				//如果当前配送方式已经查找到过支持选择的地区则不再遍历
				if(isSurpportArea==true){
					return false;
				}
				//遍历查找支持的配送地区
				$.each(rowData.area.provinceId,function(index,areaId){
					if(areaId==selProvinceId){
						isSurpportArea=true;
						var totalPrice;
						//按件计费
						if(countType==0){
							totalPrice=buyNum<=0?0:parseFloat(rowData.firstPrice*rowData.first)+rowData.nextPrice*(buyNum-rowData.first);
						//按重量或者体积计费
						}else if(countType==1 || countType==2){
							//取购买商品总重或者总体积
							logiCount=countType==1?buyNum*goods_weight:buyNum*goods_volume;
							//低于首重首体积时按对应价计算
							if(logiCount<rowData.first){
								totalPrice=parseFloat(rowData.firstPrice);
							}else{
								totalPrice=parseFloat(rowData.firstPrice)+Math.ceil((logiCount-rowData.first)/rowData.next)*rowData.nextPrice;			
							}
						}
						
						//判断是在哪个页面中进行运费统计，商品页面不需要统计运费险，购物车结算页需要加入选择表单
						if(viewType=='sameGIDAccounts'){
							priceHtmlCode+='<option value="'+totalPrice.toFixed(2)+'" data-wayidx="'+idx+'">'+wayStr+'</option>';
							returnData[idx]=totalPrice;
						}else if(viewType=='sameSalerIDAccounts'){
							priceObj[wayStr]=totalPrice.toFixed(2);
						}else{
							wayStr+='：<em class="price">'+Number(totalPrice).toFixed(2)+'</em><em>元</em>';							
						}
						return false;
					}
				})
				//console.log( rowData);
			})
			//如果当前区域配送不被支持，则显示不支持配送
			if(isSurpportArea==false && !viewType){
				wayStr+='：<em>不支持</em>';
			}

			if(!viewType){
				priceHtmlCode+='<span>'+wayStr+'</span>';
			}
			isSurpportArea=false;
		}
	})
	//单用户商城商品结算时运费计算时会按商品ID合并为一组，运费按设置合并计算
	if(viewType=='sameGIDAccounts'){
		//返回数据的方式，htmlCode为返回静态代码，data为返回运费结算关键数据
		if(returnType=='htmlCode'){
			priceHtmlCode=priceHtmlCode==''?'<option value="0" data-wayidx="-1">收货地址无法配送</option>':priceHtmlCode;
			var bottomAccount='<div class="bottomAccount"><em class="subTotal">0.00</em><em class="currency">¥</em>'
								+'<span class="logisticsPrice"><em>¥</em><em class="logiPrice">0.00</em></span>'
								+'<span><label class="select">配送方式：<select role="logistics">'+priceHtmlCode+'</select></label></span>'
								+'<span><label class="checkbox"><input type="checkbox" data-name="freInsurance"><em class="tag">运费险</em><span class="insurancePrice"> ¥ <em>0.00</em> 购买</span></label></span></div>'		
								return bottomAccount;			
		}else if(returnType=='data'){
			return returnData;
		}

	//多用户商城商品结算时按商家ID号将商品合并为一组，运费为单独计算，只返回当前商品的运费金额
	}else if(viewType=='sameSalerIDAccounts'){
		return priceObj;
	}else{
		$('#J_PostageToggleCont').html(priceHtmlCode);		
	}
}

//获取地域数据用于设置收发货地址,传递的参数是对应加载数据的列表框表单ID
function getAreaData(selItem,province,city,district){
	var dft={
		'provinceId':-1,
		'cityId':-1,
		'districtId':-1
	}

	var selData=$.extend(dft,selItem);
	var provinceForm=$(province);
	var cityForm=$(city);
	var districtForm=$(district);
	$.ajax({
		type: 'GET',
		url: GV.jsonURLRoot+'areaData.json',
		dataType: 'json',
		async:true,
		success: function(data){
			var htmlProvince = '';
			GV.areaData=data;
        	var provinceData = data.province;
        	var cityData = data.city;
        	var districtData = data.district;
			for(var i=0; i<provinceData.length;i++){
				var selStr='';
				if(provinceData[i].id==selData.provinceId){
					selStr='selected="selected"'
				}
			  	htmlProvince += '<option value="'+provinceData[i].id+'" '+selStr+'>'+provinceData[i].name+'</option>';
			}
			provinceForm.append(htmlProvince);

			/*选择省事件*/
	        provinceForm.change(function(){
	        	var code = $(this).val();
	        	if(code!=''){
	        		var cityCode  = cityData[code][0].id;
	        		var htmlCity = '',
	        			htmlDistrict = '';
	        		for(var i=0; i<cityData[code].length;i++){
	        			var selStr='';
						if(cityData[code][i].id==selData.provinceId){
							selStr='selected="selected"'
						}
					  	htmlCity += '<option value="'+cityData[code][i].id+'" '+selStr+'>'+cityData[code][i].name+'</option>';
					}
	        		cityForm.html(htmlCity);
	        		if(district){
		        		for(var i=0; i<districtData[cityCode].length;i++){
		        			var selStr='';
							if(districtData[cityCode][i].id==selData.provinceId){
								selStr='selected="selected"'
							}
						  	htmlDistrict += '<option value="'+districtData[cityCode][i].id+'">'+districtData[cityCode][i].name+'</option>';
						}
		        		districtForm.html(htmlDistrict);	        			
	        		}
	        		//取值添加提交收货地址时使用
	        		provinceName=$(this).find("option:selected").text();
	        		$(this).closest('div').find('input[name="province"]').val(provinceName);
	        		cityName=cityForm.find("option:selected").text();
	        		cityForm.closest('div').find('input[name="city"]').val(cityName);
	        	}	        	
	        });

			/*选择市*/
				cityForm.change(function(){
					var cityCode = $(this).val();
		        	var htmlDistrict = '';
		    		for(var i=0; i<districtData[cityCode].length;i++){
					  	htmlDistrict += '<option value="'+districtData[cityCode][i].id+'">'+districtData[cityCode][i].name+'</option>';
					}
		    		districtForm.html(htmlDistrict);
	        		//取值添加提交收货地址时使用
	        		cityName=$(this).find("option:selected").text();
	        		$(this).closest('div').find('input[name="city"]').val(cityName);
	        		districtName=$(this).find("option:selected").text();
	        		districtForm.closest('div').find('input[name="city"]').val(districtName);
				});

			/*选择市*/
				districtForm.change(function(){
					districtName=$(this).find("option:selected").text();
	        		$(this).closest('div').find('input[name="district"]').val(districtName);	
				})

        	//console.log(provinceData);
        },
        error: function(XMLHttpRequest, textStatus, errorThrown){
        	alert('获取地区数据失败,请重新操作！');
        	//console.log('请求失败');
	       /*alert(XMLHttpRequest.responseText); 
	       alert(XMLHttpRequest.status);
	       alert(XMLHttpRequest.readyState);
	       alert(textStatus); // parser error;*/
        	return false;
        }
    })

	//编辑时执行选择事件
	if(selData.provinceId>0){
		provinceForm.trigger('change');
		if(selData.cityId>0){
			cityForm.trigger('change');
			if(selData.districtId>0){
				districtForm.trigger('change');
			}		
		}
	}

}

//侧栏购物导航条
$.fn.setEMallShopBar=function(){
	var curShowContent;
	//滑动时的margin偏移，需要和样式设置的一致
	var marginROffset='-290px';
	var $this=$(this);
	//tooltip提示
	var barTab=$(this).find('.shopBarTab');
	barTab.hover(function(){
		$(this).addClass('tab-hover');
		$(this).find('.barBorder').hide();
		var barTooltip=$(this).find('.barTooltip');
		barTooltip.show();
		barTooltip.animate({'left':'-100px','opacity':1},100);
	},function(){
		$(this).removeClass('tab-hover');
		$(this).find('.barBorder').show();
		var barTooltip=$(this).find('.barTooltip');
		barTooltip.animate({'left':'-130px','opacity':0},100,function(){
		$(this).hide();
		});
	});

	barTabEvent=function(role,tabContent){
		//判断点击的按钮
		if(role=='shopCart'){
			//获取购物车数据
				if(ShopCartData==undefined){
					shopCart.getShopCartData(
						function callFunc(data){
							shopCart.pushCartData(data,GV.cartContainer,GV.cartView);
						}
					);
				}else{
					//刷新购物车数据
					shopCart.refreshShopCart();
				}
		}else if(role=='favorGoods'){
			tabContent.getFavorGoods();
		}
	}

	barTab.click(function(e){
		e.stopPropagation();
		//返回顶部时不触发后继的内容面板切换代码
		if($(this).find('.tab-backTop').length>0){
			$('html,body').animate({scrollTop: '0px'},800);
			return false;
		}
		//校验登录状态
		if(GV.loginState==false){
			//直接弹出登录框
			$('#loginModal').modal('show');
			return false;
		}
		var tabIdx=$(this).index();
		var barWrap=$this.find('.barWrap');
		var tabContent=$this.find('.barTabContent:eq('+tabIdx+')');
		//收起
		if($this.attr('data-expand')=='true' && $(this).hasClass('cur')){
			barWrap.animate({'margin-right':marginROffset},200);
			curShowContent=null;
			$(this).removeClass('cur tab-hover');
			$(this).find('.barBorder').css({'border-top':'1px #666 solid','border-bottom':'1px #666 solid'}).show();
			$this.attr('data-expand','false');
		//展开
		}else if($this.attr('data-expand')=='false'){
			barTabEvent($(this).attr('role'),tabContent);

			barWrap.animate({'margin-right':'0'}, 200);
			
			$(this).addClass('cur').find('.barBorder').css('border','none');
			$this.attr('data-expand','true');
			tabContent.show();
		//在展开状态下切换内容
		}else if(!$(this).hasClass('cur')){
			$(this).closest('.shopBar').find('.cur').removeClass('cur');
			$(this).addClass('cur');
			barTabEvent($(this).attr('role'),tabContent);
		}

		if(curShowContent){
			curShowContent.hide();
		}
		tabContent.show();
		curShowContent=tabContent;
	})
}

//侧栏购物车商品数据获取后的回调,singleBind为单个监听绑定的选择器字串如'eq(0)'，否则绑定全部列表元素
function setCartList(element,singleBind,viewType){
	var cartListItem=$(element).find('[role="cartItem"]'+singleBind);
	cartListItem.hover(function(){
		var $this=$(this);
		var itemIdx=$this.index();
		var curListItem=$(this).find('.shopBuyNum');
		var itemId=curListItem.attr('id');
		if(viewType=='barView'){
			//添加购买数据设置的按钮
			curListItem.before('<a href="javascript:void(0);" onclick="javascript:setAmount.reduce(\'#'+itemId+'\''+',-1,true);" class="btn-reduce" ><s></s></a>');
			curListItem.after('<a href="javascript:void(0);" onclick="javascript:setAmount.plus(\'#'+itemId+'\''+',-1,\'\',true);" class="tm-mcPlus" ><s></s><b></b></a><a href="javascript:void(0);" class="deleteBtn">删除商品</a>');
			//绑定删除购物车商品事件
			$this.find('.deleteBtn').click(function(){
				//取删除元素高度
				var liMarginBottom=parseInt($this.css('margin-bottom'));
				var liHeight=0-($this.height()+liMarginBottom);
				//添加删除动效
				shopCart.removeCartItem({type:'single',data_gid:$this.attr('data-gid')});

				//防止触发区域外点击
				return false;
			});			
		}else if(viewType=='fullView'){
			var skuInfoTxt=$this.find('.selSkuInfo');
			var data_gid=skuInfoTxt.attr('data-gid');

			function DoSKUParse(container,data,p_skuIdx,isJsonObj){
				container.find('#skuInfo').append(
					'<div class="footBar"><a role="button" class="btn btn-gray" href="javascript:void(0);">取消</a>'
					+'<a role="button" class="btn btn-blue" href="javascript:void(0);">确定</a></div>'
					).skuParse(data,p_skuIdx,isJsonObj);	

				//添加取消编辑按钮事件
				container.find('[role="button"]').click(function(){
					var confirmBtn=$(this);
					if(confirmBtn.hasClass('btn-blue')){
						var gid=data_gid.split('-');
						var hasSameCartItem=false;
						//检查列表中是否已经存在已经选购的同SKU商品
						if(parseInt(gid[1])!==curSelSKUIdx){
							$.each(ShopCartData,function(index){
								var curGID=gid[0]+'-'+curSelSKUIdx;
								if(curGID==index && itemIdx!==$(this).index()){
									hasSameCartItem=true;
									return false;
								}
							})							
						}

						if(hasSameCartItem==true){
							confirmBtn.setPopTip({
				    			content:'您的购物车中已经包含同样的商品，无需重复选择！',
				    			placement:'right',
				    			arrow:'left',
				    			arrowAlign:'hcenter',
				    			showonce:true
							})

							return false;
						}

						//更新购物商品数据
						var newCartData={};
						var sku_spec=skuFormData[curSelSKUIdx].sku_spec;
						var sku_style=skuFormData[curSelSKUIdx].sku_style;
						//转换成单品项数据行索引
						var sku_Idx=changeToSKUFormIdx(curSelSKUSpec,curSelSKUStyle);
						var newGID=gid[0]+'-'+sku_Idx;
						//console.log(curSelSKUIdx+','+sku_spec);
						var skuStr='<p>外观：'+sku_style+'</p><p>规格：'+sku_spec+'</p>';
						skuInfoTxt.html(skuStr);
						//更新data库存值
						var sku_stock=skuFormData[curSelSKUIdx].sku_stock;
						//低库存数量显示更新
						var isLowStockItem=ShopCartData[data_gid].sku_stock<=GV.lowStockNum?true:false;
						if(sku_stock<=GV.lowStockNum){
							if(!isLowStockItem){
								GV.lowStockGoodsNum++;
							}
						}else{
							if(isLowStockItem==true){
								GV.lowStockGoodsNum--;
							}
						}
						$('#lowStockGoodsNum').text(GV.lowStockGoodsNum);
						$this.attr('data-stock',sku_stock)
						//替换图片
						var imgSrc=SKU_StyleData[curSelSKUStyle].SKU_ThumbImg_M;
						if(imgSrc!==''){
							$this.find('.cartGoodsImg').attr('src',imgSrc);							
						}
						//价格更新
						$this.find('.price').text(Number(skuFormData[curSelSKUIdx].sku_price).toFixed(2));
						//更新对应shopBuyNum的ID号
						var shopBuyNumInput=$this.find('input[class="text buy-num"]');
						//console.log(skuFormData[curSelSKUIdx].sku_stock+','+shopBuyNumInput.val());
						var ItemShopBuyNum=parseInt(sku_stock)<parseInt(shopBuyNumInput.val())?sku_stock:shopBuyNumInput.val();

						shopBuyNumInput.attr({
							'id':'shopBuyNum-'+newGID,
							'onkeyup':'setAmount.modify(\'#shopBuyNum-'+newGID+'\',-1,\'\',true);',
							'data-stock':sku_stock
						}).val(ItemShopBuyNum);
						$this.find('.btn-reduce').attr('href','javascript:setAmount.reduce(\'#shopBuyNum-'+newGID+'\',-1,true);');
						$this.find('.btn-add').attr('href','javascript:setAmount.plus(\'#shopBuyNum-'+newGID+'\',-1,\'\',true);');

						//重新构造新的商品数据，此数据用于在删除购物车商品后的同商品单品选择重复判断
						newCartData.goods_id=gid[0];
						newCartData.sku_img=imgSrc;
						newCartData.sku_spec=sku_spec;
						newCartData.sku_style=sku_style;
						newCartData.shopBuyNum=ItemShopBuyNum;
						newCartData.sku_stock=sku_stock;
						newCartData.sku_price=Number(skuFormData[curSelSKUIdx].sku_price).toFixed(2);
						newCartData.goods_discount=ShopCartData[data_gid].goods_discount;
						newCartData.goods_title=ShopCartData[data_gid].goods_title;
						//更新
						ShopCartData[newGID]=newCartData;
						delete ShopCartData[data_gid];

						//console.log(ShopCartData);
						
						//更新列表中三组索引数据用的data-gid属性
						$this.attr('data-gid',newGID);
						skuInfoTxt.attr('data-gid',newGID);
						$this.find('.option .delCartItem').attr('href','javascript:shopCart.removeCartItem({type:\'single\',data_gid:\''+newGID+'\'})');
						//重新统计消费金额
						shopCart.cartAccounts();

						//更新购物车数据
						shopCart.updateCartData({
							'gid':newGID,
							'oldGID':data_gid,
							'shopBuyNum':ItemShopBuyNum,
							'saveModel':'replace'
						});
					}

					skuInfoTxt.removeClass('cur');
					skuInfoTxt.find('.editSKU').remove();					
					curEidtSelSKU.remove();
				});	
			}

			function editSKUClickEvt(){
				//检查当前是否存在编辑修改中的SKU商品
				if(curEidtSelSKU){
					curEidtSelSKU.remove();
				}
				//弹出SKU编辑框
				curEidtSelSKU=$('<div class="SKU_Content" role="SKU_Edit">'
					+'<div class="skuInfo tem-sku" id="skuInfo"></div>'
					+'<div class="skuImg"><div id="previewPic" class="bigPic">'
					+'<img src="" class="listMainImg" data-nature="" width="100%">'
					+'</div></div>'
					+'<div class="arrowWrap"><span class="arrow">◆</span><i class="down">◆</i></div>'
					+'</div>').appendTo(skuInfoTxt);


				var gid=data_gid.split('-');
				//console.log(gid);
				var sku_idx=gid[1];
				var EditSKUData;

				//console.log(GV.cartGoodsServData);
				//判断是否存在可用的本地变量数据
				if(!$.isEmptyObject(GV.cartGoodsServData)){
					$.each(GV.cartGoodsServData.goods_info,function(index,Item){
						if(Item.goods_id==gid[0]){
							DoSKUParse(curEidtSelSKU,Item.goods_sku,sku_idx);
							return false;						
						}
					})
				//不存在任何可调用数据时重新从服务器获取
				}else{
					getGoodsSKUData(data_gid,function(data){
						DoSKUParse(curEidtSelSKU,data.data.goods_sku,sku_idx);
					});					
				}

				/*/本地读取商品对应SKU数据
				if(GV.ajaxSKUReturn['sku-'+data_gid]){
					EditSKUData=GV.ajaxSKUReturn['sku-'+data_gid];
				}

				//如果数据不存在则重新请求服务器数据
				if(EditSKUData!=='' && EditSKUData){
					//sku解析绑定
					DoSKUParse(curEidtSelSKU,EditSKUData,sku_idx);
				//链接服务器获取数据
				}else{
					getGoodsSKUData(data_gid,function(data){
						DoSKUParse(curEidtSelSKU,data.data.goods_sku,sku_idx);
					});
				}*/

			}

			skuInfoTxt.hover(function(){
				var editSKUBtn=$(this).find('.editSKU');
				if(editSKUBtn.length==0){
					var editSKUBtn=$('<em class="editSKU">修改</em>').appendTo($(this));
					$(this).addClass('cur');
					data_gid=$(this).attr('data-gid');
					editSKUBtn.on('click',function(){
						//判断当前商品是否包含SKU选项，如果不包含，则不向服务器请求SKU数据
						if(skuInfoTxt.hasClass('noSKU')){
							$(this).setPopTip({
								content:'没有可编辑的数据，无需修改！',
								placement:'top',
								arrow:'bottom',
								arrowAlign:'vcenter',
								showonce:true
							})
							return false;
						}
						editSKUClickEvt();
					});
				};
			})

			//SKU信息栏高亮显示
			skuInfoTxt.addClass('cur');
			var editSKUBtn=skuInfoTxt.find('.editSKU');
			if(editSKUBtn.length==0){
				editSKUBtn=$('<em class="editSKU">修改</em>').appendTo(skuInfoTxt);
				//编辑修改购买商品SKU信息事件
				editSKUBtn.on('click',function(){
					//判断当前商品是否包含SKU选项，如果不包含，则不向服务器请求SKU数据
					if(skuInfoTxt.hasClass('noSKU')){
						$(this).setPopTip({
							content:'没有可编辑的数据，无需修改！',
							placement:'top',
							arrow:'bottom',
							arrowAlign:'vcenter',
							showonce:true
						})
						return false
					}
					editSKUClickEvt();
				});
			}

		}

	},function(){
		if(viewType=='barView'){
			$(this).find('.btn-reduce,.tm-mcPlus,.deleteBtn').remove();

		}else if(viewType=='fullView'){
			var skuInfoTxt=$(this).find('.selSkuInfo');
			if(skuInfoTxt.find('.SKU_Content').length==0){
				//移除SKU信息栏高亮显示
				skuInfoTxt.removeClass('cur');
				skuInfoTxt.find('.editSKU').remove();
			}
		}
	});
}

//ajax单独调用单个商品的SKU数据
function getGoodsSKUData(data_gid,callFunc){
	var gid=data_gid.split('-');
	//链接服务器获取数据
	$.ajax({
		type: 'GET',
		url: GV.getSKUDataURL,
		async:true,
		dataType: "json",
		data:{'goods_id':gid[0]},
		success: function(data,status){		
			if(data.status==1){
				//console.log(data.data);
				if(callFunc){
					callFunc(data);
				}
				//返回数据保存到数组中
				GV.ajaxSKUReturn['sku-'+data_gid]=data.data.goods_sku;
			}else if(data.status==0){
				alert(data.error);
			}
		},
		error:function(){
			alert('网络错误，无法链接到服务器！')
		}		
	})
}

var curCartFilterType='all';

//购物车操作方法集
var shopCart={
	//构造添加到购物车的商品数据(仅用于商品详情页面)
	createShopData:function(){
		var data={};
		//判断是否存在sku数据，没有则按普通商品构造数据
		if(skuFormData){
			data=skuFormData[curSelSKUIdx];	
			data.goods_title=$('h1').text();
			data.sku_idx=curSelSKUIdx;
			data.goods_id=goods_id;
			data.shopBuyNum=shopBuyNum;			
			data.goods_discount=goods_discount;
			//规格类单品属性没有图片，所以需要在这里判断sku_img引用的图片地址
			data.sku_img=curSelSKUStyle>=0 && SKU_StyleData[curSelSKUStyle].SKU_ThumbImg!==''?SKU_StyleData[curSelSKUStyle].SKU_ThumbImg:goods_ThumbImg;;
		}else{
			data.goods_title=$('h1').text();
			data.goods_price=goods_price;
			data.goods_stock=goods_stock;
			data.shopBuyNum=shopBuyNum;
			data.goods_id=goods_id;
			data.goods_img=goods_ThumbImg;
			data.goods_discount=goods_discount;
		}
		//console.log(data);
		return data;
	},
	//添加商品到购物车
	//toAccounts为true时加入购物车后直接跳转到结算页面
	addToCart:function(submitURL,toAccounts){
		//检查本地存储的登录状态标识
		if(!GV.loginState){
			$('#loginModal').modal();			
		}else{
			checkUserLogin(0,function(){
		    	//生成商品ID，由商品ID+单品ID组成,没有单品数据的使用商品ID-0
		    	var data_gid=goods_id+'-'+curSelSKUIdx;
		    	//获取购物车容器
		    	var cartList=$('#cartList').find('.mCSB_container');

				//加入购物车时商品图片动画的起点位置
				var startLeft,startTop;
				//获取选择的商品SKU图片，如果没有图片，则取商品主图
				var skuImg=$('ul[data-property="SKU_Style"] .curSel img');
				if(!skuImg.attr('src')==''){
					startLeft=skuImg.offset().left;
					startTop=skuImg.offset().top
				}else{
					skuImg=$('#goodsPicSlider li:first img');
					var curSelSKUItem;
					//判断单品显示的方式
					if(curSelSKUSpec<0 && curSelSKUStyle<0){
						//这里为了避免后面多写代码就直接等于了
						curSelSKUItem=skuImg;
					}else if(curSelSKUStyle>=0){
						curSelSKUItem=$('ul[data-property="SKU_Style"] .curSel');
					}else if(curSelSKUSpec>=0){
						curSelSKUItem=$('ul[data-property="SKU_Spec"] .curSel');
					}
					startLeft=curSelSKUItem.offset().left;
					startTop=curSelSKUItem.offset().top
				}

				var cartImgHtml='<div style="position:absolute;left:'+startLeft+'px;top:'+startTop+'px;opacity:.9;z-index:10000;width:50px;height:50px"><img src="'+skuImg.attr('src')+'" width="100%""></div>';
				var cartImg;
				//直接购买时不生成动画图片对象
				if(!toAccounts){
					cartImg=$(cartImgHtml).appendTo($('body'));
				}
				var cartTab=$('#EMallShopBar .shopBarTab .tab-cart');

				//将商品信息加入购物车列表
				//取购买的商品数量
		    	var buyNum=shopBuyNum=parseInt($('#buy-num').val());
		    	//购物车中添加商品信息
		    	var cartGoodsStock,cartGoodsPrice,cartGoodsSpec,cartGoodsStyle;
		    	//根据是否存在单品数据进行价格和库存等的赋值
		    	if(skuFormData){
		    		cartGoodsStock=skuFormData[curSelSKUIdx].sku_stock;
		    		cartGoodsPrice=parseFloat(skuFormData[curSelSKUIdx].sku_price)>0?skuFormData[curSelSKUIdx].sku_price:goods_price;
		    		cartGoodsSpec=skuFormData[curSelSKUIdx].sku_spec;
		    		cartGoodsStyle=skuFormData[curSelSKUIdx].sku_style;
		    	}else{
		    		cartGoodsStock=goods_stock;
		    		cartGoodsPrice=goods_price;
		    		cartGoodsSpec='';
		    		//没有SKU单品属性时在标题栏上默认显示以下文字
		    		cartGoodsStyle='勾选当前商品加入结算';
		    	}

		    	//console.log(cartGoodsStock);
		    	var cartListHtml='<li data-gid="'+data_gid+'" role="cartItem"><div class="cartListTitleBar"><input type="checkbox" data-name="shopList" checked="true"><span>'+cartGoodsStyle+'</span> <span>'+cartGoodsSpec+'</span></div>'
		    	+'<div class="cartImg"><img src="'+skuImg.attr('src')+'" width="50px"></div><div class="cartGoodsInfo"><a href="">'+$('h1').text()+'</a>'
		    	+'<span class="goodsPrice"><em>¥</em><em class="price">'+Number(cartGoodsPrice).toFixed(2)+'</em> x '
		    	+'<span class="shopBuyNum" id="shopBuyNum-'+data_gid+'" data-stock="'+cartGoodsStock+'">'+buyNum+'</span></span></div></li>';

		    	endFunc=function(){
			    	//动画完成后判断购物车中是否存在当前商品，如果不存在则添加，否则只添加数量
			    	var curCartGoods=cartList.find('li[data-gid="'+data_gid+'"]');
			    	//更新到购物车列表的状态，0表示更新的是购买的数量，1表示新添加到购物车，此变量用于单件结算时的选择件数统计
			    	var addToCartState=1;
			    	if(!curCartGoods.length>0){
			    		cartList.append(cartListHtml);
				    	GV.cartGoodsNum++;
				    	$('.mui-mbar-tab-sup-bd').text(GV.cartGoodsNum);

				    	//绑定事件
				    	setCartList('#EMallShopBar .cartList','[data-gid="'+data_gid+'"]','barView');	
				    	cartList.find(':checkbox[data-name="shopList"]').click(function(){
				    		shopCart.cartAccounts();
				    	})
			    	}else{
			    		var curGoodsBuyNum=curCartGoods.find('#shopBuyNum-'+data_gid);
			    		var totalNum=parseInt(curGoodsBuyNum.text())+buyNum; 
			    		//判断是否超出库存,点击立即购买按钮时如果购物车中已经包含商品则不再添加购买数
			    		if(totalNum<=cartGoodsStock && !toAccounts){
			    			curGoodsBuyNum.text(totalNum);		    			
			    		}else{
			    			curGoodsBuyNum.text(cartGoodsStock);	
			    		}
			    		addToCartState=0;
			    	}

			    	$.cookie('cartGoodsNum',GV.cartGoodsNum);
			    	//统计结算，全局变量中totalPrice存放了当前总金额
			    	//selCartListItemNum为当前选择结算的商品件数
			    	shopCart.cartAccounts();
			    	//购买数量的数字动画
					var addCartNum=$('<div class="addToCartGoodsNum" style="position:absolute;top:0;left:10px;">'+buyNum+'</div> ').prependTo(cartTab);
			    	addCartNum.animate({'top':'-20px','opacity':0},500,function(){
			    		addCartNum.remove();
			    	});
		    	}

		    	//判断当前购物车的商品数是否已满
		    	if(GV.cookieEnabled){
		    		GV.cartGoodsNum=$.cookie('cartGoodsNum');
		    	}
		    	if(parseInt(GV.cartGoodsNum)==parseInt(GV.limitCartGoodsNum)){
		    		//删除生成的添加到购物车的动画图片
		    		if(!toAccounts){
		    			cartImg.remove();
		    		}
		    		alert('抱歉，您的购物车太满了，先结算一部分吧！');
		    		return false;
		    	}

		    	//购物车数据更新方式,1表示新增数据，0表示更新购买数量，-1表示不需要更新提交数据
		    	var updateType=1;
		    	//更新商品部分信息
		    	var updateField;
		    	
		    	//检查更新当前购物车的数据
		    	if(ShopCartData!==null && ShopCartData!==undefined){
		    		//直接结算时不运行其它购物车数据更新代码
		    		if(!toAccounts){
			    		if(typeof(ShopCartData[data_gid])!=="undefined" && ShopCartData[data_gid]!==null){
							//console.log('已有商品');
							//更新购买数量
							var newNum=parseInt(ShopCartData[data_gid].shopBuyNum)+buyNum;

							//判断库存,超出库存后设定更新标识为负，提示购买已达上限
							if(newNum<=cartGoodsStock){
								ShopCartData[data_gid].shopBuyNum=newNum;
								updateType=0;
							}else{
								updateType=-1;
							}						
			    		}
		    		}
		    	}else{
		    		//如果不存在任何购物车的数据，在这里声明数组用于存储
		        	ShopCartData={};
		    	}
		    	

		    	//更新到购物车数据
		    	if(updateType==1){
					if(toAccounts){
						$('#J_LinkBuy').setLoadingState({container:'body',loadingTxt:'正在移步到结算页面，请稍等...'});
					} 
			    	//将当前商品原始数据构造成购物车商品数据
			    	goodsData=shopCart.createShopData();
		    		ShopCartData[data_gid]=goodsData;
		    		//curShopCartData.push({'gid':data_gid,'data':goodsData});
		    	}else if(updateType==0){
		    		updateField={data_gid:ShopCartData[data_gid].shopBuyNum};
		    		//销毁不需要更新的商品信息
		    		goodsData=null;
		    	}
		    	//console.log('shopBuyNum:'+ShopCartData[data_gid].shopBuyNum);

		    	if(updateType>=0){
			    	//将添加的商品数据提交到服务器上进行更新，如果是直接购买则会在更新完后直接跳转到购物车详情页
				 	shopCart.updateCartData({
				 		'gid':data_gid,
				 		'shopBuyNum':ShopCartData[data_gid].shopBuyNum,
				 		'updatField':updateField,
				 		'saveModel':'update'
				 	},toAccounts);
				 	//直接购买结算时不运行后面的添加动画
					if(toAccounts){
						//endFunc();
						return false;
					}
				 	//加入购物车的动画
					cartImg.fly({
					    start: {
					        left: startLeft-2,
					        top: startTop-$(window).scrollTop()-10
					    },
					    end: {
					        left: cartTab.offset().left,
					        top: cartTab.offset().top-$(window).scrollTop(),
					        width: 20,
					        height: 20
					    },
					    onEnd:function(){
					    	endFunc();
					    }
					});
					//移除pop提示
					$('#J_LinkAdd').setPopTip('destroy');			

					//如果不需要提交更新数据，提示购买量已经超出库存
		    	}else{
		    		cartImg.remove();
		    		$('#J_LinkAdd').setPopTip({
		    			content:'抱歉，您选购的商品数量已经超出库存数量！',
		    			placement:'top',
		    			arrow:'bottom',
		    			arrowAlign:'vcenter',
		    			showonce:true
		    		});
		    	}
		    //未登录时弹出登录对话框
		    },function(){
		    	$('#loginModal').modal();	
		    })
			
		}
		//console.log(skuImg.offset().top+','+skuImg.offset().left);
	},
	/**		
	 * @param string reqType为获取数据的方式，为空是默认方式：加载的是购物车中商品的基本数据，不包含结算的运费等数据;
	 * reqType='accounts'时表示结算方式，加载的数据包括商品运费等结算数据，数据返回后直接执行结算程序
	 * @param function callFunc为获取数据后的回调函数
	 * @param boolean isCartOverview:是否是顶部购物车的数据概览
	 */
	getShopCartData:function(callFunc,reqType,isCartOverview){
		var barCartContainer;
		function getDataFromServer(){
			var loadingContainer=GV.cartView=='fullView'?'body':'';
			var loadingTxt=reqType=='accounts'?'正在结算购物车商品，请稍等...':'正在加载购物车数据，请稍等...';
			//显示加载等待UI
			if(GV.cartView=='fullView' && !isCartOverview){
				$("#shopAccounts").setLoadingState({container:loadingContainer,loadingTxt:loadingTxt});
			}else if(GV.cartView=='barView'){
				barCartContainer=$('#shopCart');
				barCartContainer.setLoadingState({container:loadingContainer,loadingTxt:loadingTxt});
			}
			$.ajax({
				type: 'GET',
				url: GV.getCartDataURL,
				async:true,
				dataType: "json",
				data:{'type':reqType,checkReturnType:'ajax'},
				success: function(data,status){
					console.log(data);
					if(data.status==1){
						//如果当前已经没有任何购物车数据，则直接退出
						if(data.data=='no shopcart data'){
							//输出顶部购物车栏未登录的信息
							$('#settleup-content').html('<div style="text-align:center;padding:10px 0">购物车是空的，快去找找中意的商品吧！</span>');
							$('#shopping-amount').text(0);
							//销毁加载等待UI
							destroyLoading(true);
							return false;
						}

						//由数据是在销毁等待UI时清零，所以这里需要在输出数据前先调用销毁程序，否则购物车商品数会叠加累计
						destroyLoading(true);

						if(callFunc){
							//保存购物车数据到本地变量中
							GV.cartGoodsServData=data.data;
							callFunc(shopCart.constructCartDataFromServ(data.data,true));	
						}

					}else{
						//console.log(data);
						if(data.error=='请登录后再进行操作！'){
							//判断是否需要弹出登录框
							if(!GV.getCartView){
								$('#loginModal').modal();
							}else{
								//输出顶部购物车栏无商品的信息
								$('#settleup-content').html('<div style="text-align:center;padding:10px 0">您还没有登录，请登录后再操作！</span>');
							}
						}else{
							alert(data.error+' type:'+reqType);
						}
						GV.isCartOverviewLoaded=false;
						//销毁加载等待UI
						destroyLoading();
					}
					
					//cartContainer.setLoadingState('destroy');
				},
				error:function(XMLHttpRequest, textStatus, errorThrown){
					destroyLoading();
					alert('链接服务器出错，无法获取购物车数据：'+textStatus);
				}
			});	
		}

		//销毁加载等待UI:noGoods，购物车中没有任何商品时传此参数为true销毁loadingUI
		function destroyLoading(noGoods){

			//如果是在侧栏购物车直接结算进入的详情页，只在没有购物车商品和进入结算视图后才会有需要销毁的加载等待UI
			if((GV.cartView=='fullView' && reqType!=='accounts') || (GV.cartView=='fullView' && noGoods)){
				$("#shopAccounts").setLoadingState('destroy');
			}else if(GV.cartView=='barView'){
				//清空原有数据并归零
				if(noGoods){
					$('[role="cartItem"]').remove();
					$('.mui-mbar-tab-sup-bd').text(0);
					$('#selCartListItemNum').text(0);
					$('#accountPrice').text(0);
					GV.cartGoodsNum=0;
					ShopCartData=null;
					$.cookie('cartGoodsNum',GV.cartGoodsNum);
				}
				barCartContainer.setLoadingState('destroy');
			}
		}

		getDataFromServer();

	},
	//使用服务器获取的数据构造出需要显示的购物车数据
	constructCartDataFromServ:function(data,isJsonObj){
		var constructData={};
		var servData;
		//判断是否是Json对象，是的话直接解析
		if(isJsonObj){
			servData=data;
		}else{
			servData=$.parseJSON(data);
		}

		$.each(servData.goods_id,function(index){
			$.each(servData.goods_info,function(gIdx){
				if(servData.goods_id[index]==servData.goods_info[gIdx].goods_id){
					//生成gid键
					var gid=servData.goods_info[gIdx].goods_id+'-'+servData.sku_idx[index];
					//购物车商品数据存储变量
					var goods_info={};

					//判断是否有SKU数据
					if(servData.goods_info[gIdx].goods_sku){
						var sku_data=$.parseJSON(servData.goods_info[gIdx].goods_sku);
						var sku_idx=servData.sku_idx[index];
						var SKU_StyleCount,SKU_SpecCount;
						//判断是否存在SKU单品外观样式或者规格类数据，不存在的设置长度为-1
						if(sku_data.SKU_Style){
							SKU_StyleCount=sku_data.SKU_Style.length;
						}else{
							SKU_StyleCount=0;
						}
						if(sku_data.SKU_Spec){
							SKU_SpecCount=sku_data.SKU_Spec.length;
						}else{
							SKU_SpecCount=0;
						}
						//返回选中的单品属性索引，用于查找对应的SKU单品外观样式图片
						var selSKUGroupIdx=createSKUOptionIdx(SKU_SpecCount,SKU_StyleCount,true,sku_idx);

						goods_info.sku_price=sku_data.SKU_Form[sku_idx].sku_price;
						goods_info.sku_spec=sku_data.SKU_Form[sku_idx].sku_spec;
						goods_info.sku_style=sku_data.SKU_Form[sku_idx].sku_style;
						goods_info.sku_stock=sku_data.SKU_Form[sku_idx].sku_stock;
						//判断是否存在Style单品属性及图片链接
						var imgSrc=SKU_StyleCount>0?sku_data.SKU_Style[selSKUGroupIdx.sku_styleIdx].SKU_ThumbImg_M:null;
						goods_info.sku_img=imgSrc && imgSrc!==''?imgSrc:$.parseJSON(servData.goods_info[gIdx].goods_img)[0].m_thumb;							
					}else{
						goods_info.goods_stock=servData.goods_info[gIdx].goods_stock;
						var imgData=$.parseJSON(servData.goods_info[gIdx].goods_img);
						goods_info.goods_img=imgData[0].thumb;
					}

					goods_info.goods_id=servData.goods_info[gIdx].goods_id;
					goods_info.goods_title=servData.goods_info[gIdx].goods_title;
					goods_info.goods_price=servData.goods_info[gIdx].goods_price;
					goods_info.goods_discount=servData.goods_info[gIdx].goods_discount;
					goods_info.logistics_id=servData.goods_info[gIdx].logistics_id;
					goods_info.shopBuyNum=servData.shopBuyNum[index];
					goods_info.goods_weight=servData.goods_info[gIdx].goods_weight;
					goods_info.goods_volume=servData.goods_info[gIdx].goods_volume;

					constructData[gid]=goods_info;
					return false;
				}

			})
		})


		//console.log(constructData);
		return constructData;
		//console.log(data);
	},
	//将添加的购物车商品数据提交到服务器上进行更新
	//toAccounts为true时表示在商品加入到购物车后直接进入结算页面,也就是立即购买
	updateCartData:function(data,toAccounts,callFunc){
	 	$.post(GV.addToCartURL,data,function(returnData,status){
	 		if(returnData.status==1){
	 			//回调
	 			if(callFunc){
	 				callFunc(returnData);
	 			}
	 			//立即购买时的操作
	 			if(toAccounts){
	 				$('#J_LinkBuy').setLoadingState('destroy',function(){
	 					//return false;
	 					location.href=GV.fullViewCartURL+'&reqType=accounts&specParam=buyNow&gid='+data.gid;
	 				});
	 			}
	 		}else{
	 			
		 		if(toAccounts){
		 			$('#J_LinkBuy').setLoadingState('destroy');
		 		}
	 			//alert('抱歉，网络错误！商品数据无法保存到服务器！');
	 			if(returnData.error=='请登录后再进行操作！'||returnData.info=="您还没有登录！"){
	 				$('#loginModal').modal();
	 			}
	 		}
	 		console.log(returnData);
	 	}).error(function(){
	 		alert('链接服务出错，无法结算购物车商品，请稍候尝试！');
	 	})
	},
	//将数据添加到购物车:data购物车数据，selector查找数据容器的选择器
	//viewType:fullView时表示数据显示购物车详情页，barView表示显示在页面侧栏购物车中
	pushCartData:function(data,selector,viewType){
		//console.log(data);
		var accountsGID=GV.accountsGID!==''?GV.accountsGID.split('.'):'';
		var cartList=$(selector);
		cartList.empty();
		if(!data){			
			return false;
		}
		ShopCartData=data;

		$.each(ShopCartData,function(index,Item){
			//console.log(Item);
			var data_gid=index;
			var goods_id=index.split('-')[0];
			var logistics_id=Item.logistics_id;		
			var salerId='0';	//商家的沟通ID号
			var goods_img=Item.sku_img?Item.sku_img:Item.goods_img;
			var cartGoodsStyle=Item.sku_style?Item.sku_style:'勾选当前商品加入结算';
			var cartGoodsSpec=Item.sku_spec?Item.sku_spec:'';
			var cartGoodsPrice=Item.sku_price?Item.sku_price:Item.goods_price;
			var cartGoodsDiscount=Item.goods_discount;
			var cartGoodsFinalPrice=cartGoodsDiscount<=0?cartGoodsPrice:cartGoodsDiscount;
			var cartGoodsStock=Item.sku_stock?Item.sku_stock:Item.goods_stock;
			var cartGoodsBuyNum=Item.shopBuyNum;
			//console.log(Item.shopBuyNum.shopBuyNum);
			var cartGoodsName=Item.goods_title;
			var cartGoodsWeight=Item.goods_weight;
			var cartGoodsVolume=Item.goods_volume;
			var hasSKU=cartGoodsSpec=='' && cartGoodsStyle=='勾选当前商品加入结算'?'noSKU':'';
			//购物车详情页中不需要显示没有外观样式类SKU单品项的商品属性
			if(cartGoodsStyle=='勾选当前商品加入结算'){
				if(viewType=='fullView'){
					cartGoodsStyle='';
				}
			}
			//这里的内容用于显示在侧栏购物车中
	    	var cartListHtml='<li data-gid="'+data_gid+'" data-stock="'+cartGoodsStock+'" role="cartItem"><div class="cartListTitleBar"><input type="checkbox" data-name="shopList" checked="true"><span>'+cartGoodsStyle+'</span> <span>'+cartGoodsSpec+'</span></div>'
	    	+'<div class="cartImg"><img src="'+goods_img+'" width="50px"></div><div class="cartGoodsInfo"><a href="'+GV.goodsDetailURL+'&id='+Item.goods_id+'">'+cartGoodsName+'</a>'
	    	+'<span class="goodsPrice"><em>¥</em><em class="price">'+Number(cartGoodsFinalPrice).toFixed(2)+'</em> x '
	    	+'<span class="shopBuyNum" id="shopBuyNum-'+data_gid+'" data-stock="'+cartGoodsStock+'">'+cartGoodsBuyNum+'</span></span></div></li>';

	    	//这里的内容用于显示在购物车详情页面中
			var cartListTitle='<div class="cartListTitle"></div>';
			var cartListContent='<ul role="cartItem"  data-stock="'+cartGoodsStock+'" data-gid="'+data_gid+'" data-lid="'+logistics_id
						+'" data-weight="'+cartGoodsWeight+'" data-volume="'+cartGoodsVolume+'">'
						+'<li><input type="checkbox" data-name="shopList"></li>'
						+'<li class="itemImg fixImgPadding"><a href="'+GV.goodsDetailURL+'&id='+Item.goods_id+'"><img src="'+goods_img+'" width="80" class="cartGoodsImg" alt=""></a></li>'
						+'<li class="listInfo"><a href="'+GV.goodsDetailURL+'&id='+Item.goods_id+'">'+cartGoodsName+'</a></li>'
						+'<li class="selSkuInfo '+hasSKU+'" data-gid="'+data_gid+'"><p>外观：'+cartGoodsStyle+'</p><p>规格：'+cartGoodsSpec+'</p></li>'
						+'<li class="goodsPrice"><span>市场价：<del>¥'+Number(cartGoodsPrice).toFixed(2)+'</del></span><span>促销价：<em>¥</em><em class="price">'+Number(cartGoodsFinalPrice).toFixed(2)+'</em></span></li>'
						+'<li class="buyNum" id="J_Amount"><span class="wrap-input choose-amount">'
		                        +'<input class="text buy-num" onkeyup="setAmount.modify(\'#shopBuyNum-'+data_gid+'\''+',-1,\'\',true);" id="shopBuyNum-'+data_gid+'" value="'+cartGoodsBuyNum+'" data-stock="'+cartGoodsStock+'">'
		                        +'<a class="btn-reduce" href="javascript:setAmount.reduce(\'#shopBuyNum-'+data_gid+'\''+',-1,true);" data-disabled="1">-</a>'
		                        +'<a class="btn-add" href="javascript:setAmount.plus(\'#shopBuyNum-'+data_gid+'\''+',-1,\'\',true)" data-disabled="1">+</a>'
		                    +'</span></li>'
						+'<li class="payAmount"><em>¥</em><em class="singleAccount">'+(cartGoodsFinalPrice*cartGoodsBuyNum)+'</em></li>'
						+'<li class="option"><a href="">移入收藏夹</a><a class="delCartItem" href="javascript:shopCart.removeCartItem({type:\'single\',data_gid:\''+data_gid+'\'})">删除</a></li>'
					+'</ul>';
			var bottomAccount='';

			//根据显示方式显示数据内容
			if(GV.cartView=='barView'){
    			cartList.append(cartListHtml);
    			//循环单个绑定事件
	    		setCartList('#EMallShopBar .cartList','[data-gid="'+data_gid+'"]','barView');	
		    	GV.cartGoodsNum++;
		    	$('.mui-mbar-tab-sup-bd').text(GV.cartGoodsNum);
		    //购物车详情页显示
			}else if(GV.cartView=='fullView'){
				//判断商品是否需要合并为一组，同一商家，同一商品ID的合并到一组
				var sameProptyItem=$('.cartList[data-id="'+goods_id+'"]');
				if(sameProptyItem.length==0){
					cartList.append(
						'<div class="cartList" data-id="'+goods_id+'" data-salerId="'+salerId+'">'
						+cartListTitle+'<div class="cartListContent">'+cartListContent+'</div></div>'
						);
				}else{
					sameProptyItem.find('.cartListContent').append(cartListContent);
				}
				//

			}

		});

		$.cookie('cartGoodsNum',GV.cartGoodsNum);
	
    	//绑定勾选事件
    	cartList.find(':checkbox[data-name="shopList"]').on('change',function(){
    		var $this=$(this);
    		var cartList=$(this).closest('.cartList');
    		var cartItem=$(this).closest('[role="cartItem"]')

    		/*if($(this).is(':checked')){
				shopCart.logisticsPriceAccounts('plus',cartItem,filterType);
			}else{
				shopCart.logisticsPriceAccounts('reduce',cartItem,filterType);				
			}*/

			//直接刷新运费计算数据
			shopCart.logisticsPriceAccounts('refresh',null,cartList);		
    		shopCart.cartAccounts();

    		//当勾选不处于结算状态的商品时需要重新调用结算数据，所以设定按钮功能状态为结算状态
    		if($(this).is(':checked') && $(this).closest('[role="cartItem"]').attr('data-action')!=='accounts'){
	    		shopCart.setAccountBtnState('doAccounts'); 	
    		}
    	})

		if(viewType=='fullView'){
				//商品总数和低库存数计算
				$.each(ShopCartData,function(index,Item){
					if(Item.sku_stock){
						if(Item.sku_stock<=GV.lowStockNum){
							GV.lowStockGoodsNum++;
						}
					}else{
						if(Item.goods_stock<=GV.lowStockNum){
							GV.lowStockGoodsNum++;
						}
					}
				})
				
				$('#totalGoodsNum').text(GV.cartGoodsNum);
				$('#lowStockGoodsNum').text(GV.lowStockGoodsNum);
				setCartList('#cartList .cartListContainer','','fullView');
		}
		//如果是在其它页面直接结算商品如：点击立即购买，则将对应的GID传入购物车详情页面，并在默认选中后执行结算
		if(accountsGID!==''){
			$.each(accountsGID,function(index){
				$('[role="cartItem"][data-gid="'+accountsGID[index]+'"]').find(':checkbox[data-name="shopList"]').prop('checked',true);
			})
			//执行结算
			$('#shopAccounts').trigger('click');
		}
		shopCart.cartAccounts();
	},
	//刷新购物车数据,主要是在侧栏购物车中使用，用于不同页面中打开侧栏购物车时的数据同步刷新
	refreshShopCart:function(){
		//判断是否需要更新购物车数据
		if(GV.cookieEnabled){
			oldCartGoodsNum=parseInt($.cookie('cartGoodsNum'));
		}else{
			oldCartGoodsNum=GV.cartGoodsNum;
		}
		//如果是侧栏购物车判断当前容器中商品元素个数是否与实际购物车商品数不一致，不一致时执行刷新
		if(GV.cartView=='barView'){
			var cartItem=parseInt($('[role="cartItem"]').length);
			if(oldCartGoodsNum==cartItem){
				$('#shopCart').setLoadingState('destroy');
				return false;
			}
		}
		//数据刷新时重置部分变量
		GV.cartGoodsNum=0;
		shopCartData=null;
		//购物车数据刷新
		checkUserLogin(GV.logoutStateExec,function(){
			shopCart.getShopCartData(
				function callFunc(data){
					shopCart.pushCartData(data,GV.cartContainer,GV.cartView);
				}
			);			
		});	
	},
	//运费结算，type:计算方式，cartItem购物车商品数据容器,cartList购物车商品组容器
	logisticsPriceAccounts:function(type,cartItem,cartList,data){
			//如果只传cartItem表示只更新单个商品数据，如果传入cartList表示计算更新单组商品数据
			var bottomAccount=cartList?cartList.find('.bottomAccount'):cartItem.closest('.cartList').find('.bottomAccount');
			var data_gid;
			var isTotalAccounts=curCartFilterType=='accounts'?true:false;
			//单用户商城中只需要取列表组中的一个商品数据就可以计算出本组的运费
			var cartItem=!cartItem?cartList.find('[role="cartItem"][data-action="accounts"]:first'):cartItem;

			if(bottomAccount.length>0){
				//多用户商城运费计算使用（未完成）
				if(type!=='refresh'){
					data_gid=cartItem.attr('data-gid');
					//取运费模板数据
					$.each(servLogisticsData,function(index,Item){
						if(cartItem.attr('data-lid')==Item.logistics_id){
							tmplLogisticsData=Item.logistics_param;
							return false;
						}					
					})
					//提取当前结算列表组中商品的运费结算金额
					var bottomCode=staticLogisticsPrice({
						buyNum:cartItem.find('#shopBuyNum-'+data_gid).val(),
						selProvinceId:curUseProvince,
						selCityId:curUseCity,
						viewType:'sameGIDAccounts',
						returnType:'data'
					});

					//累计运费数据
					bottomAccount.find('[role="logistics"]').queue(function(next){
						$(this).find('option').each(function(){
							var option=$(this);
							$.each(bottomCode,function(index,value){
								if(option.text()==changeToLogistaticsStr(index)){
									var curPrice=option.val();
									if(type=='plus'){
										option.val((parseFloat(curPrice)+parseFloat(value)).toFixed(2));
									}else if(type=='reduce'){
										option.val((parseFloat(curPrice)-parseFloat(value)).toFixed(2));
									}
									return false;
								}								
							})
						})
						next();
					}).trigger('change');

				//重新刷新当前商品组运费结算数据
				}else{
					//记录当前选择的配送方式，数据刷新计算后默认选中之前的选项
					var selWayIdx=bottomAccount.find('[role="logistics"] option:selected').attr('data-wayidx');
					//清空当前的运费结算数据
					bottomAccount.find('[role="logistics"] option').val('0.00');
					bottomAccount.find('.logisticsPrice .logiPrice').text('0.00');
					//取当前列表组容器
					//var cartList=cartItem.closest('.cartList');
					//循环遍历列表组中的商品并计算运费数据
					$.each(cartList,function(){
						var shopBuyNum=0
						var lid;			//运费模板ID
						var noLogiSupport=false;		//收货地址是否无任何支持的配送方式
						$.each($(this).find('[role="cartItem"]'),function(){
							var $this=$(this);
							data_gid=$this.attr('data-gid');
							var checkInput=$this.find(':checkbox[data-name="shopList"]');
							//非选中并且不处于禁用状态的商品不会被重新统计
							if(!checkInput.is(':checked') && !checkInput.prop('disabled')){
								return true;
							}
							//计算当前列表组所有商品购买总数进行运费合并计算（单用户商城计算方式）
							shopBuyNum+=parseInt($this.find('#shopBuyNum-'+data_gid).val());
							lid=$this.attr('data-lid');
							//取运费模板数据
							$.each(servLogisticsData,function(index,Item){
								if(lid==Item.logistics_id){
									tmplLogisticsData=Item.logistics_param;
									return false;
								}					
							})


						})
						//提取当前结算列表组中商品的运费结算金额
						var bottomCode=staticLogisticsPrice({
							buyNum:shopBuyNum,
							selProvinceId:curUseProvince,
							selCityId:curUseCity,
							viewType:'sameGIDAccounts',
							returnType:'data'
						});
						//如果至少有一项支持收货地的配送方式则计算运费，否则直接禁止结算购买商品
						if(!$.isEmptyObject(bottomCode)){
							//console.log(bottomCode);
							//累计运费数据
							bottomAccount.queue(function(next){
								//激活运费险勾选
								var freInsurance=$(this).find(':checkbox[data-name="freInsurance"]');
								if(parseFloat(freInsurance.attr('data-price'))>0)
								freInsurance.prop({'disabled':false});
								next()
							}).find('[role="logistics"]').queue(function(next){
								var selectInput=$(this);
								//如果当前返回的配送方式不存在于现有的配送列表中，则添加
								$.each(bottomCode,function(index){
									var wayStr=changeToLogistaticsStr(index);
									//用于标识是否包含当前配送方式，如果更换地址后，地址不在配送列表中，则添加配送选项
									var hasLogistics=false;
									selectInput.find('option').each(function(){
										var option=$(this);
										if(option.text()==wayStr){
											hasLogistics=true;
											return false;
										}
									})
									if(!hasLogistics){
										selectInput.append('<option value="0" data-wayidx="'+index+'">'+wayStr+'</option>');
									}
								})

								//重新排序
								selectInput.find('option').sort(function(a,b){
								    var aIdx = parseInt($(a).attr('data-wayidx'));  
								    var bIdx = parseInt($(b).attr('data-wayidx')); 
								    return aIdx-bIdx; 
								}).appendTo(selectInput);

								//计算运费
								$(this).find('option').each(function(){
										var option=$(this);
										//用于标识是否包含当前配送方式，如果更换地址后，地址不在配送列表中，则去除当前存在的配送选项
										var hasLogistics=false;
										$.each(bottomCode,function(index,value){
											if(option.text()==changeToLogistaticsStr(index)){
												var curPrice=option.val();
												option.val((parseFloat(curPrice)+parseFloat(value)).toFixed(2));
												hasLogistics=true;
												return false;
											}
										})
										//移除不支持的配送方式
										if(!hasLogistics){
											option.remove();
										}	
								})
								//默认选中第一个
								$(this).removeAttr('disabled').find('option[data-wayidx="'+selWayIdx+'"]').prop('selected',true);
								//激活勾选
								//cartItem.find(':checkbox[data-name="shopList"]').prop({'checked':true,'disabled':false});
								next();
							}).trigger('change');
						}else{
							bottomAccount.queue(function(next){
								//禁止运费险勾选
								var freInsurance=$(this).find(':checkbox[data-name="freInsurance"]');
								if(parseFloat(freInsurance.attr('data-price'))>0)
								freInsurance.prop({'checked':false,'disabled':true});
								next();
							}).find('[role="logistics"]').queue(function(next){
								$(this).find('option').remove();												
								next();
							}).append('<option value="0.00" data-wayidx="-1">收货地址无法配送</option>').prop('disabled',true);

						}
					})
				}
			}	
	},
	//计算消费金额
	cartAccounts:function(){
		var totalPrice=0,selCartListItemNum=0,singleAccount=0,accountsGoodsNum=0;
		var isTotalAccounts=curCartFilterType=='accounts'?true:false;
		//遍历更新结算数据
		$.each($('#cartList [role="cartItem"]'),function(index){
			if(isTotalAccounts && $(this).attr('data-action')!=='accounts'){
				return true;
			}
			var data_gid=$(this).attr('data-gid');
			var priceObj=$(this).find('#shopBuyNum-'+data_gid);
			var shopBuyNum;
			//判断购买商品数的容器类型
			if(priceObj.is(':input')){
				shopBuyNum=priceObj.val();
			}else{
				shopBuyNum=priceObj.text();
			}

			if($(this).find(':checkbox[data-name="shopList"]').is(':checked')){
				selCartListItemNum++;
				//正在结算的商品数量
				if($(this).attr('data-action')=='accounts'){
					accountsGoodsNum++;
				}
				//总消费金额
				totalPrice+=shopBuyNum*parseFloat($(this).find('.price').text());

			}
			//如果是fullView，直接赋值单件商品金额统计
			$(this).find('.singleAccount').text((shopBuyNum*parseFloat($(this).find('.price').text())).toFixed(2));

		})

		//console.log(totalPrice+','+selCartListItemNum);

		$('#selCartListItemNum').text(selCartListItemNum);	
		$('#accountPrice').text(totalPrice.toFixed(2));
		$('#accountsGoodsNum').text(accountsGoodsNum);

		//如果未勾选任何商品，将全选checkbox取消勾选
		if(selCartListItemNum==0){
			$(':checkbox[data-target="shopList"]').prop('checked',false);
		}

		//购物车详情页面结算时的小计
		if(isTotalAccounts){
			//实付金额
			var actualPrice=0,logisticsPrice=0;
			//小计
			$.each($('.cartList'),function(){
				var subTotal=0;
				var hasAccountItem=false;	//判断当前列表组是否有结算的商品
				//如果没有任何支持的配送方式则跳过本组列表小计
				if($(this).find('[role="logistics"]').prop('disabled')){
					$(this).find('.subTotal').text('0.00');
					return true;
				}
				$(this).find('[role="cartItem"][data-action="accounts"]').each(function(){
					if($(this).find(':checkbox[data-name="shopList"]').is(':checked')){
						subTotal+=parseFloat($(this).find('.singleAccount').text());
						hasAccountItem=true;
					}
				})
				if(hasAccountItem){
					logisticsPrice=parseFloat($(this).find('.logisticsPrice .logiPrice').text());

					subTotal+=logisticsPrice;
					actualPrice+=subTotal;
				}
				$(this).find('.subTotal').text(subTotal.toFixed(2));
			})			

			$('#actualPayment').text(actualPrice.toFixed(2));
			//当存在结算商品时显示结算汇总信息
			if(isTotalAccounts>0){
				$('.payWrap').show();
			}
		}

	},
	//购物车商品过滤器,目前只有三种，一种是显示全部商品'all'，一种是显示库存紧张的商品'lowStock'，一种是结算中的商品'accounts'
	cartListFilter:function(option){
		var dft={
			filterType:'all',
			callFunc:$.noop()
		}

		var Options=$.extend(dft,option);
		var cartItem=$('[role="cartItem"]');
		if(Options.filterType=='lowStock'){
			//重置跳转页面时传递的参数
			GV.specParam=GV.accountsGID='';
			$('#shopAccounts').removeClass('disabled');		//激活结算按钮

			$.each(cartItem,function(){
				//console.log($(this).attr('data-stock'));
				if(parseInt($(this).attr('data-stock'))>GV.lowStockNum){
					shopCart.checkAllHide($(this));
				}else{
					$(this).attr('data-show',true).show().closest('.cartList').show().find('.bottomAccount').hide();
					//隐藏结算信息
					$('.payWrap').hide();
				}
			})
			shopCart.setAccountBtnState('doAccounts'); 
		}else if(Options.filterType=='all'){
			//重置跳转页面时传递的参数
			GV.specParam=GV.accountsGID='';
			$('#shopAccounts').removeClass('disabled');		//激活结算按钮

			$.each(cartItem,function(){
				var data_gid=$(this).attr('data-gid');
				$(this).attr('data-show',true).show().closest('.cartList').show().find('.bottomAccount').hide();
				//重新激活全部商品勾选（结算时不被支持的配送地区将会被禁止勾选，返回显示全部商品时重新激活）
				$(this).find(':checkbox[data-name="shopList"]').prop({'disabled':false});
				$(this).find('#shopBuyNum-'+data_gid).prop({'disabled':false});
			})
			//隐藏结算信息
			$('.payWrap').hide();
			shopCart.setAccountBtnState('doAccounts'); 	
		}else if(Options.filterType=='accounts'){
			$.each(cartItem,function(){
				if($(this).attr('data-action')!=='accounts'|| !$(this).find(':checkbox[data-name="shopList"]').is(':checked')){
					//检测一个组中是否所有商品都隐藏，如果是则容器也一并隐藏
					shopCart.checkAllHide($(this));
				}else{
					$(this).attr('data-show',true).show().closest('.cartList').show().find('.bottomAccount').show();
				}
			})
			//执行回调
			if(Options.callFunc){
				Options.callFunc();
			}
			//重计消费金额，只计算已经参与过结算的商品
			curCartFilterType=Options.filterType;
			shopCart.cartAccounts();
			shopCart.setAccountBtnState('createOrder'); 	
		}
		//标识当前购物车详情页面商品过滤显示的方式，用于重新计算结算商品金额
		curCartFilterType=Options.filterType;
	},
	//检测购物车商品列表组中是否所有商品都隐藏，如果是则容器也一并隐藏
	checkAllHide:function(element){
		element.attr('data-show',false).hide().closest('.cartList').queue(function(next){
			if($(this).find('[role="cartItem"][data-show="true"]').length==0){
				$(this).hide();
				next();
			}else{
				$(this).dequeue();
			}
		});			
	},
	//更新购物车结算与提交订单按钮的切换状态,用于购物车详情页面中结算与生成订单
	setAccountBtnState:function(state){
		var btnTxt=state=='createOrder'?'提交订单':'结算';
		$('#shopAccounts').attr('data-action',state).text(btnTxt);    
	},
	//移除选中的购物车商品,type为'all'时删除全部，'single'时删除选中的单个商品
	removeCartItem:function(option){
		var dft={
			type:'all',
			selector:'[role="cartItem"]',
			//删除单个商品时需要提供对应的ID
			data_gid:''
		}

		var Options=$.extend(dft,option);
		var removeCartIdx=new Array;		

		if(Options.type=='all'){
			var cartItem=$(Options.selector);
			var outDelayTime=0;
			$.each(cartItem,function(){
				var $this=$(this);
				var checkItem=$(this).find(':checkbox[data-name="shopList"]');
				var data_gid=$this.attr('data-gid');
				//加入移除动画
				if(checkItem.is(':checked')){
					removeCartIdx.push(data_gid);
					//商品列表容器
					var cartList=$this.closest('.cartList');
					//商品列表作为组所包含商品的数量，用于判断删除时是删除容器还是容器中对应的商品列表
					var cartListChildNum=cartList.find(Options.selector).length;
					//将要删除的对象
					var delCartElement=cartListChildNum>1?$this:cartList;

					setTimeout(function(){
						delCartElement.animate({'opacity':0},250).animate({'height':0},250,function(){
								delCartElement.remove();
								//更新购物车结算数据
								shopCart.cartAccounts();
													
						})
						outDelayTime=500;
					},outDelayTime);

					GV.cartGoodsNum--;
					//低库存商品数量更新
					if(GV.lowStockNum){
						if($this.attr('data-stock')<=GV.lowStockNum){
							GV.lowStockGoodsNum--;	
						}
					}

					//删除变量对应数据
					delete ShopCartData[data_gid];
				}

			})
		}else if(Options.type=='single'){
			var delCartItem=$(Options.selector+'[data-gid='+Options.data_gid+']');
			var delCartElement;
			//购物车详情页中需要判断是否删除列表组，侧栏购物车暂时没有编组
			if(GV.cartView=='fullView'){
				//商品列表容器
				var cartList=delCartItem.closest('.cartList');
				//商品列表作为组所包含商品的数量，用于判断删除时是删除容器还是容器中对应的商品列表
				var cartListChildNum=cartList.find(Options.selector).length;
				//将要删除的对象
				delCartElement=cartListChildNum>1?delCartItem:cartList;
			}else{
				delCartElement=delCartItem;
			}
			//减购物车商品数量显示
			GV.cartGoodsNum--;
			//低库存商品数量更新
			if(GV.lowStockNum){
				if(delCartItem.attr('data-stock')<=GV.lowStockNum){
					GV.lowStockGoodsNum--;				
				}
			}

			delCartElement.animate({'opacity':0},150).queue(function(next){
					$(this).animate({'height':0},150,function(){
						delCartElement.remove();
						//更新购物车结算数据
						shopCart.cartAccounts();					
					})
					next();
				})

			/*var testInterval=setInterval(function(){
				if(delCartItem.length==0){
					clearInterval(testInterval);
					shopCart.cartAccounts();
				}else{
					console.log(delCartItem);
				}
			},350);*/

			//刷新变量对应数据
			if(ShopCartData){
				delete ShopCartData[Options.data_gid];
			}
			//console.log(ShopCartData);
		}

		gid=Options.type=='all'?removeCartIdx:Options.data_gid;

		if(gid.length==0||gid==''){
			return false;
		}
		//console.log('removeItemGID:'+gid);
		//barView更新
		$('.mui-mbar-tab-sup-bd').text(GV.cartGoodsNum);	
		//fullView更新
		$('#totalGoodsNum').text(GV.cartGoodsNum);
		//低库存商品数量更新
		if(GV.lowStockNum){
			$('#lowStockGoodsNum').text(GV.lowStockGoodsNum);	
		}

		//商品gid提交到服务器进行删除
		$.ajax({
			type: 'POST',
			url: GV.removeCartDataURL,
			async:true,
			dataType: "json",
			data:{'gid':gid,'type':Options.type},
			success: function(data){
				console.log(data);
			}
		});	

		$.cookie('cartGoodsNum',GV.cartGoodsNum);

		//ShopCartData=curShopCartData=null;
	},
	//结算时需要生成的查询的商品id字串,生成后直接跳转到结算页面
	createGIDStrToAccount:function(accountGID){
		//$(GV.cartContainer).setLoadingState({container:'.barWrap',loadingTxt:'准备进入结算页面，请稍等...'});
		var gidStr='';
		//如果传入了GID字串，则直接跳转到结算页
		if(!accountGID){
			var updateField={};
			$('[role="cartItem"]').each(function(){
				if($(this).find(':checkbox[data-name="shopList"]').is(':checked')){
					var gid=$(this).attr('data-gid');
					var shopBuyNum=$('#shopBuyNum-'+gid).text();
					gidStr+=gidStr==''?gid:'.'+gid;
					//构造更新数据，在提交结算之前需要先更新当前选择结算的购物车商品数据
					updateField[gid]=shopBuyNum;
				}				
			})
			//判断是否存在结算的商品
			if(gidStr==''){
				alert('购物车中没有任何可结算的商品，先到商城逛逛吧！');
				return false;
			}
			//console.log(updateField);
			//开始更新
			$('body').setLoadingState({container:'body',loadingTxt:'准备进入结算页面，请稍等...'});
			checkUserLogin(0,function(){
				shopCart.updateCartData({saveModel:'update',updateField:updateField},false,function(data){
					$('body').setLoadingState('destroy',function(){
						location.href=GV.fullViewCartURL+'&reqType=accounts&gid='+gidStr;
					})
				});	
			},function(){
				$('body').setLoadingState('destroy');
			})

			//
		}else{
			gidStr=accountGID;
			location.href=GV.fullViewCartURL+'&reqType=accounts&gid='+gidStr;	
		}
		//$(GV.cartContainer).setLoadingState('destroy',function(){		
		//})
		return false;
	}
}

//顶部购物车概览
$.fn.cartOverview=function(){
	parseData=function(cartData){
		var cartListHtml='';
		var PromotionalActivities='暂无促销活动';
		var totalAccount=0;
		var cartGoodsNum=Object.keys(cartData).length;
		var data_gid;
		var goodsURL;

		//生成购物车商品列表数据及代码
		$.each(cartData,function(index,Item){
			//console.log(cartData.sku_idx);
			goodsURL=GV.goodsDetailURL+'&id='+Item.goods_id;
			var goods_price=parseFloat(Item.sku_price)<=0?Item.goods_price:Item.sku_price;
			totalAccount+=parseFloat(goods_price)*parseInt(Item.shopBuyNum);

			cartListHtml+='<li role="cartItem" data-gid="'+index+'">'
			+'<div class="p-img fl"><a href="'+goodsURL+'" target="_blank"><img src="'+Item.sku_img+'" width="50" height="50" alt=""></a></div>'
			+'<div class="p-name fl"><span></span><a href="'+goodsURL+'" title="'+Item.goods_title+'" target="_blank">'+Item.goods_title+'</a></div>'
			+'<div class="p-detail fr ar"><span class="p-price"><strong>￥'+goods_price+'</strong>×'+Item.shopBuyNum+'</span><br>'
			+'<a class="delete" href="javascript:shopCart.removeCartItem({type:\'single\',data_gid:\''+index+'\'})">删除</a></div></li>';
		})

		cartListHtml='<li class="dt"><div class="fl"><span class="hl-green">满减</span>'+PromotionalActivities
		+'</div><div class="fr"><em>小计：￥'+totalAccount.toFixed(2)+'</em></div><div class="clr"></div></li>'+cartListHtml+
		'<div class="smb ar"><div class="p-total"></div><a href="'+GV.fullViewCartURL+'" title="去购物车" id="btn-payforgoods">去购物车</a></div>';
		//插入代码
		$('#settleup-content').html('<div class="smt"><h4 class="fl">最新加入的商品</h4></div><div class="smc"><ul id="mcart-mj">'+cartListHtml+'</ul></div>');
		$('#settleup-content .p-total').html('共<b>'+cartGoodsNum+'</b>件商品　共计<strong>￥ '+totalAccount.toFixed(2)+'</strong>');
		$('#shopping-amount').text(cartGoodsNum);
		//console.log(cartData);
	}
	$(this).hover(function(){
		$(this).addClass('dropdown');
		if(GV.isCartOverviewLoaded){
			return false;
		}
		//标识当前顶部购物车正在加载购物车商品数据以锁定单发操作
		GV.isCartOverviewLoaded=true;
		//标识当前是通过顶部购物车加载购物车商品数据（用于判断未登录状态下是否需要弹出登录框，顶部购物车只需要显示未登录文本）
		GV.getCartView=true;
		shopCart.getShopCartData(parseData,null,true);
	},function(){
		$(this).removeClass('dropdown');
	})
}

//我的收藏栏中商品事件绑定
$.fn.setFavorGoods=function(){
	$(this).hover(function(){
		//$(this).find('.cancelFavor').show();
		//$(this).find('.favorRelation').show();
	},function(){
		//$(this).find('.cancelFavor').hide();
		//$(this).find('.favorRelation').hide();
	})
}

//收藏商品
$.fn.setToFavorGoods=function(){
	$(this).on('click',function(){
		if(goods_id!==''){
			var favorSame=false;
			//查看是否收藏过此商品
			if(favorData!==null){
				$.each(favorData,function(index,Item){
					if(index==goods_id){
						favorSame=true;
						return false;
					}
				})
			}

			if(favorSame==true){
				alert('已经收藏过此商品！');
				return false;
			}

			$.post(setToFavorGoodsURL,{id:goods_id},function(data){
				if(data.status==1){
					favorData=data.favorData;
					var favorListHtml=parseFavorGoodsData(data.data);
					if(favorGoodsData!==null){
						favorGoodsData.push(data.data[0]);
					}else{
						favorGoodsData=[];
						favorGoodsData.push(data.data[0]);
					}
					console.log(favorGoodsData);
					$this.find('.favorList ul').prepend(favorListHtml);
					popScreen({poptxt:'收藏商品成功!'});
				}else{
					if(data.info){
						$('#loginModal').modal();
					}else{
						alert(data.error);
					}
					//alert(data.info+'收藏失败，请稍候尝试！');
				}
					console.log(data);
			}).error(function(){
				alert('链接服务器出错，无法收藏商品，请稍候尝试！');
			})
		}		
	})

}

//获取收藏商品
$.fn.getFavorGoods=function(){
	$this=$(this);
	if(!favorGoodsData){
		$(this).setLoadingState({loadingTxt:'正在加载收藏商品，请稍候！'})

		$.get(getFavorGoodsURL,{},function(data){
			if(data.status==1){
				if(data.favorData){				
					//data.favorData为实际保存的收藏基本数据格式为：商品ID:收藏时间
					favorData=data.favorData;
					//排序
					var curfavorData=insertSort(data.favorData,0,function(a,b){
						return new Date(a.replace(/-/g,'/')) < new Date(b.replace(/-/g,'/'))?1:-1;
					});
					//返回的data.data为收藏商品的详细数据
					favorGoodsData=data.data;
					var favorListHtml=parseFavorGoodsData(curfavorData,data.data,10);
					if(favorListHtml!==''){
						$this.find('.favorList ul').html(favorListHtml);
					}
				}else{
					$this.find('.favorList ul').html('<li class="emptyData">您还没有收藏任何商品，赶紧去逛逛吧！</li>');
				}
			}else{
				var errorInfo=data.info?data.info+'请登录后再进行操作！':'无法获取收藏商品数据，请稍候尝试！';
				$this.find('.favorList ul').html('<li class="emptyData">'+errorInfo+'</li>');
			}
			console.log(data);
			$this.setLoadingState('destroy');
		}).error(function(XMLHttpRequest, textStatus, thrownError){
			$this.find('.favorList ul').html('<li class="emptyData">链接服务器获取数据出错，请稍候尝试！</li>');
			$this.setLoadingState('destroy');
		})
	}else{
		//排序
		var curfavorData=insertSort(favorData,0,function(a,b){
			return new Date(a.replace(/-/g,'/')) < new Date(b.replace(/-/g,'/'))?1:-1;
		});
		var favorListHtml=parseFavorGoodsData(curfavorData,favorGoodsData);
		if(favorListHtml!==''){
			$this.find('.favorList ul').html(favorListHtml);
		}else{
			$this.find('.favorList ul').html('<li class="emptyData">您还没有收藏任何商品，赶紧去逛逛吧！</li>');
		}
	}
}

//取消收藏商品,单个删除
function cancelFavorGoods(id){
	$.post(cancelFavorGoodsURL,{id:id},function(data){
		if(data.status==1){
			//console.log(data);
			favorData=data.data;
			$('#favorGoods .favorList ul li[data-gid="'+id+'"]').animate({'opacity':0},200).animate({'width':0},200).queue(function(next){
				$(this).remove();
				var favorListWrap=$('#favorGoods .favorList ul');
				if(favorListWrap.find('li').length==0){
					favorListWrap.html('<li class="emptyData">您还没有收藏任何商品，赶紧去逛逛吧！</li>');
				}
				$(this).next();
			});
			//删除对应的商品数据
			if(typeof(favorGoodsData)!=="undefined"){
				$.each(favorGoodsData,function(index,Item){
					if(Item.goods_id==id){
						favorGoodsData.splice(index,1);
						return false;
					}
				})
			}
			//刷新当前的收藏商品数
			$('.favorTitleBar em').queue(function(next){
				var favorGoodsNum=parseInt($(this).text());
				$(this).text(favorGoodsNum-1);
				$(this).next();
			})
		}else{
			var errorInfo=data.info?data.info:'';
			alert(errorInfo+'取消收藏商品失败！');
			//console.log(data);
		}
	}).error(function(){
		alert('链接服务器出错，无法取消收藏商品，请稍候尝试！');
	})
}

//批量取消收藏(只用于收藏详情页面)
function multiCancelFavorGoods(){
	var selectedItem=$('.favorList .selected');
	if(selectedItem.length==0){
		alert('请先至少选中一件商品！');
		return false;
	}
	//生成ids商品id字串组
	var ids='';
	$.each(selectedItem,function(){
		ids+=ids==''?$(this).attr('data-gid'):','+$(this).attr('data-gid');
	})

	$.post(cancelFavorGoodsURL,{ids:ids},function(data){
		if(data.status==1){
			favorData=data.data;
			idsArray=ids.split(',');
			$.each(idsArray,function(index,gid){
				$('#favorGoods .favorList ul li[data-gid="'+gid+'"]').animate({'opacity':0},200).animate({'width':0},200).queue(function(next){
					$(this).remove();
					var favorListWrap=$('#favorGoods .favorList ul');
					if(favorListWrap.find('li').length==0){
						favorListWrap.html('<li class="emptyData">您还没有收藏任何商品，赶紧去逛逛吧！</li>');
					}
					$(this).next();
				});				
			})

			//刷新当前的收藏商品数
			$('.favorTitleBar em').queue(function(next){
				var favorGoodsNum=parseInt($(this).text());
				$(this).text(favorGoodsNum-idsArray);
				$(this).next();
			})

		}else{
			var errorInfo=data.info?data.info:'';
			alert(errorInfo+'取消收藏商品失败！');
		}
	}).error(function(){
		alert('链接服务器出错，无法取消收藏商品，请稍候尝试！');
	})
}

//解析收藏的商品数据,limit为输出的数量
function parseFavorGoodsData(favorData,data,limit){
	var favorListHtml='';
	var outputNum=0;
	if(data){
		$.each(favorData,function(fidx,fData){
			$.each(data,function(index,Item){
				if(Item.goods_id==Object.keys(fData)){
					if(limit && outputNum>limit-1){
						return false;
					}

					var goods_img=$.parseJSON('{"imageData":'+Item.goods_img+'}');
					//console.log(goods_img);
					favorListHtml+='<li data-gid="'+Item.goods_id+'"><a href="javascript:cancelFavorGoods('+Item.goods_id+')" class="cancelFavor" title="取消收藏"></a>'
					+'<div class="favorRelation"><a href="">找相似</a><a href="">找搭配</a></div>'
		            +'<a href="javascript:jumpGoodsURL('+Item.goods_id+')"><img src="'+goods_img.imageData[0].thumb+'" width="100%"></a><div class="price">¥'+Item.goods_price+'</div></li>';

		            outputNum++;
		            return false;
	            }
			})			
		})
	}
	return favorListHtml;
}

//商品链接跳转
function jumpGoodsURL(goods_id){
  window.location=GV.goodsDetailURL+'&id='+goods_id;
}	


var selBrandNum=0;
//商品搜索筛选控制器
var SearchController={
	//展开收缩品牌筛选项
	getBrandsAll:function(){
		brandWrap=$('#selByBrand');
		selMoreBtn=brandWrap.find('.J_extMore');
		if(brandWrap.hasClass('expand')){
			selMoreBtn.removeClass('opened').html('更多<i></i>');
			brandWrap.removeClass('expand');
			$('#selByBrand .J_brandLetter').hide();			
			$('#selByBrand .J_brandLetter li:first').trigger('mouseenter');
		}else{
			brandWrap.addClass('expand');
			selMoreBtn.addClass('opened').html('收起<i></i>');
			$('#selByBrand .J_brandLetter').show();	
		}
	},
	//品牌筛选事件
	BrandsMultiSelect:function(){
		brandWrap=$('#selByBrand');
		if(!brandWrap.hasClass('expand')){
			SearchController.getBrandsAll();
		}
		if(!brandWrap.hasClass('multiple')){
			brandWrap.addClass('multiple');
			brandWrap.find('.sl-btns').show();
			//绑定多选择事件
			brandWrap.find('li').on('click',function(){
				var confirmBtn=brandWrap.find('.J_btnsConfirm');
				if($(this).hasClass('selected')){
					$(this).removeClass('selected');
					selBrandNum--;
					//禁用确定按钮
					if(selBrandNum==0){
						confirmBtn.addClass('disabled');
					}
				}else{
					$(this).addClass('selected');
					selBrandNum++;
					//激活确定按钮
					if(confirmBtn.hasClass('disabled')){
						confirmBtn.removeClass('disabled');
					}
				}
				return false;
			})
		}else{
			brandWrap.removeClass('multiple');
			brandWrap.find('.sl-btns').hide();
			SearchController.getBrandsAll();
			SearchController.ClearBrandsMultiSelect();
			brandWrap.find('li').unbind();
		}
	},
	//移除选中的品牌
	ClearBrandsMultiSelect:function(){
		$('#selByBrand li').each(function(){
			$(this).removeClass('selected');
			selBrandNum=0;
		})
	}
}

//checkbox全选/取消
$.fn.setAllCheckbox=function(option){
	var $this=$(this);
	var dft={
		context:'normal',
		callFunc:$.noop
	}
	var Options=$.extend(true,dft,option);
	var $this=$(this);
	//找到全选操作的checkbox
	var checkAllinput=$(this).find('.check-all');
	checkAllinput.click(function(){
		var _self=$(this);
		var checkName=$(this).attr('data-target');
		var checkItem=$this.find(':checkbox[data-name="'+checkName+'"]');
		//购物车详情页面中，只要是不隐藏的商品都将触发勾选事件
		checkItem.each(function(){
			if(Options.context=='shopAccounts'){
				var container=$(this).closest('[role="cartItem"]');
				if(container.css('display')!=='none' && !$(this).prop('disabled')){
					$(this).prop('checked',_self.prop('checked')).trigger('change');					
				}
			}else{
				$(this).prop('checked',_self.prop('checked'))
			}
		})


		/*.queue(function(next){

			if(!$(this).prop('disabled')){
				$(this).prop('checked',_self.prop('checked')).trigger('change');
			}

		});*/
		checkAllinput.prop('checked',$(this).prop('checked'));
		Options.callFunc.apply($(this));
	});
}

//模态对话框类，建立在bootstrap的模态对话框基础上
messagesBox=function(option){
	var dft={
		title:'提示',
		content:'',
		type:'confirm',
		callFunc:$.noop
	}

	var Options=$.extend(dft,option);
	var msgBox;
	var confirmCode='<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>'
      +'<button type="button" class="btn btn-red" id="msg-confirm">确定</button></div></div></div></div>';
    var normalCode='<button type="button" class="btn btn-primary" id="msg-confirm">确定</button></div></div></div></div>';

	var msgBoxHtml='<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">'
  	+'<div class="modal-dialog" role="document">'
    +'<div class="modal-content"><div class="modal-header">'
        +'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        +'<h4 class="modal-title" id="myModalLabel">'+Options.title+'</h4></div>'
      +'<div class="modal-body">'
      +Options.content+'</div>'      
      +'<div class="modal-footer">';

	switch(Options.type){
		case 'confirm':
		msgBox=$(msgBoxHtml+confirmCode).appendTo($('body'));
		msgBox.modal();
		break;
		default:
		msgBox=$(msgBoxHtml+normalCode).appendTo($('body'));
		msgBox.modal();
		break;
	}

	//绑定确认按钮事件
	msgBox.find('#msg-confirm').click(function(){
		//执行回调然后销毁对话框
		Options.callFunc.apply(this);
		msgBox.modal('hide');
		msgBox.on('hidden.bs.modal', function (e) {
  			$(this).remove();
		});
	})
}


//超精简toolTip组件,不支持多个tip同时显示，最大宽度250px，可以根据需要自行修改样式加宽
$.fn.setPopTip=function(option){
	var dft={
		title:'',				//Tip标题（目前参数无用）
		content:'',				//Tip内容
		width:200,				//手工设置宽度
		placement:'top',		//弹出位置
		arrow:'bottom',			//箭头方向
		arrowAlign:'hcenter',	//箭头对齐位置:vcenter垂直居中，hcenter水平居中，topLeft上左，topRight上右，bottomLeft下左，bottomRight下右
		display:'focus',		//焦点外消失
		animation:true,			//是否启用弹出动画
		trigger:'click',		//绑定触发事件
		showonce:false			//单次立刻显示，设为true后将不绑定任何事件，直接显示，所以此参数适合在按钮单击事件内使用，防止重复绑定
	}

	var $this=$(this);

	var Options=$.extend(dft,option);	

	function showTip(){
		if(Options.display=='focus'){
			tipfocus=true;
		}else{
			tipfocus=false;
		}
		if($this.find('.poptip').length>0){
			return false;
		}
		
		if($this.css('position')!=='absolute'){
			$this.css('position','relative');			
		}

		var tooltip=$('<div class="poptip"><span class="poptip-arrow poptip-arrow-'+Options.arrow+' arrow-align-'+Options.arrowAlign+'"><em>◆</em><i>◆</i></span>'+Options.content+'</div>').appendTo($this);
		//var tooltip=$('<div class="poptip">'+Options.content+'</div>').appendTo($this);
		var tipWidth=Options.width;
		var cWidth=$this.outerWidth()+2;
		var cHeight=$this.outerHeight();
		var tipHeight=tooltip.outerHeight()+2;
		//console.log(tipHeight);

		//用于定位动画的起始偏移位置
		var offsetNum=Options.animation?22:12;

		var cssSetting={};

		switch(Options.placement){
			case 'top':
			offsetPosition=(0-(tipHeight+offsetNum));		
			cssSetting.left=tipWidth<cWidth?(cWidth-tipWidth)/2:(0-(tipWidth-cWidth)/2);
			break;
			case 'bottom':
			offsetPosition=0-(tipHeight+offsetNum);
			cssSetting.left=tipWidth<cWidth?(cWidth-tipWidth)/2:(0-(tipWidth-cWidth)/2);
			break;
			case 'left':
			offsetPosition=0-(tipWidth+offsetNum);
			cssSetting.top=tipHeight<cHeight?(cHeight-tipHeight)/2:(0-(tipHeight-cHeight)/2);
			break;
			case 'right':
			offsetPosition=0-(tipWidth+offsetNum);
			offsetTop=tipHeight<cHeight?(cHeight-tipHeight)/2:(0-(tipHeight-cHeight)/2);
			cssSetting.top=offsetTop+'px';	
		}

		cssSetting[Options.placement]=offsetPosition+'px';
		cssSetting.width=tipWidth+'px';
		cssSetting.opacity=0.5;
		tooltip.css(cssSetting);

		var setting={};
		//动画后归位的位置减10像素
		setting[Options.placement]=offsetPosition+10;
		setting.opacity=1;
		if(Options.animation){
			tooltip.show();
			tooltip.animate(setting,150)
		}else{
			tooltip.css('opacity',1);
			tooltip.show();
		}	
				
	}

	switch(option){
		case 'destroy':
			$(this).find('.poptip').remove();
			break;
		case 'hide':
			$(this).find('.poptip').hide();
			break;
		case 'show':
			$(this).find('.poptip').show();
			break;
		default:
			if(Options.showonce){
				showTip();
			}else{
				$(this).on(Options.trigger,function(event){
					//防止冒泡
					event.stopPropagation();
					showTip();
				})			
			}

		break;
	}
}

//点击内容区域时需要的事件触发判断
var tipfocus=false;		//触发tooltip消失方式的标识
$(function(){
	$(document).bind("click",function(e){
		var target = $(e.target);
		//滑动时的margin偏移，需要和样式设置的一致
		var marginROffset='-290px';
		//关闭shopBar
		if(target.closest("#EMallShopBar").length == 0 && target.closest("#J_LinkAdd").length == 0){//点击id为sibader之外的地方触发
		 	var barWrap=$('#EMallShopBar .barWrap');
		 	barWrap.animate({'margin-right':marginROffset},200);
		 	var curSleTab=barWrap.closest('#EMallShopBar').find('.shopBar .cur');
		 	curSleTab.find('.barBorder').css({'border-top':'1px #666 solid','border-bottom':'1px #666 solid'}).show();
		 	curSleTab.removeClass('cur tab-hover');
		 	$("#EMallShopBar").attr('data-expand','false');
		}
		//关闭pop提示
		if(tipfocus==true){
			if(target.closest(".poptip").length == 0){
				var poptip=$('.poptip');
				poptip.animate({'opacity':0},200,function(){
					poptip.remove();
				})
			}
		}
		//关闭购物商品SKU修改框
		if(target.closest('.selSkuInfo').length==0){
			var skuInfoTxt=$('.selSkuInfo');
			skuInfoTxt.removeClass('cur');
			skuInfoTxt.find('.editSKU').remove();		
			$('[role="SKU_Edit"]').remove();
		}
	});
})



//低版本浏览器的一些脚本函数方法兼容
;(function ($) {
	//requestAnimationFrame动画兼容
    var lastTime = 0;
    var vendors = ["webkit", "moz"];
    for (var x = 0; x < vendors.length && !window.requestAnimationFrame; ++x) {
        window.requestAnimationFrame = window[vendors[x] + "RequestAnimationFrame"];
        // Webkit中此取消方法的名字变了
        window.cancelAnimationFrame = window[vendors[x] + "CancelAnimationFrame"] || window[vendors[x] + "CancelRequestAnimationFrame"];
    }
    if (!window.requestAnimationFrame) {
        window.requestAnimationFrame = function(callback, element) {
            var currTime = new Date().getTime();
            var timeToCall = Math.max(0, 16.7 - (currTime - lastTime));
            var id = window.setTimeout(function() {
                callback(currTime + timeToCall);
            }, timeToCall);
            lastTime = currTime + timeToCall;
            return id;
        };
    }
    if (!window.cancelAnimationFrame) {
        window.cancelAnimationFrame = function(id) {
            clearTimeout(id);
        };
    }

    //Object.keys兼容IE8及以下版本浏览器
    if (!Object.keys) Object.keys = function(o) {
  		if (o !== Object(o))
    	throw new TypeError('Object.keys called on a non-object');
  
  		var k=[],p;
  		for (p in o) if (Object.prototype.hasOwnProperty.call(o,p)) k.push(p);
  		return k;
	}

})(jQuery);

/*
 * jquery.fly
 * 
 * 抛物线动画
 * @github https://github.com/amibug/fly
 * Copyright (c) 2014 wuyuedong
 * copy from tmall.com
 */

  $.fly = function (element, options) {
    // 默认值
    var defaults = {
      version: '1.0.0',
      autoPlay: true,
      vertex_Rtop: 20, // 默认顶点高度top值
      speed: 1.2,
      start: {}, // top, left, width, height
      end: {},
      onEnd: $.noop
    };

    var self = this,
      $element = $(element);

    /**
     * 初始化组件，new的时候即调用
     */
    self.init = function (options) {
      this.setOptions(options);
      !!this.settings.autoPlay && this.play();
    };

    /**
     * 设置组件参数
     */
    self.setOptions = function (options) {
      this.settings = $.extend(true, {}, defaults, options);
      var settings = this.settings,
        start = settings.start,
        end = settings.end;

      $element.css({marginTop: '0px', marginLeft: '0px', position: 'fixed'}).appendTo('body');
      // 运动过程中有改变大小
      if (end.width != null && end.height != null) {
        $.extend(true, start, {
          width: $element.width(),
          height: $element.height()
        });
      }
      // 运动轨迹最高点top值
      var vertex_top = Math.min(start.top, end.top) - Math.abs(start.left - end.left) / 3;
      if (vertex_top < settings.vertex_Rtop) {
        // 可能出现起点或者终点就是运动曲线顶点的情况
        vertex_top = Math.min(settings.vertex_Rtop, Math.min(start.top, end.top));
      }

      /**
       * ======================================================
       * 运动轨迹在页面中的top值可以抽象成函数 y = a * x*x + b;
       * a = curvature
       * b = vertex_top
       * ======================================================
       */

      var distance = Math.sqrt(Math.pow(start.top - end.top, 2) + Math.pow(start.left - end.left, 2)),
        // 元素移动次数
        steps = Math.ceil(Math.min(Math.max(Math.log(distance) / 0.05 - 75, 30), 100) / settings.speed),
        ratio = start.top == vertex_top ? 0 : -Math.sqrt((end.top - vertex_top) / (start.top - vertex_top)),
        vertex_left = (ratio * start.left - end.left) / (ratio - 1),
        // 特殊情况，出现顶点left==终点left，将曲率设置为0，做直线运动。
        curvature = end.left == vertex_left ? 0 : (end.top - vertex_top) / Math.pow(end.left - vertex_left, 2);

      $.extend(true, settings, {
        count: -1, // 每次重置为-1
        steps: steps,
        vertex_left: vertex_left,
        vertex_top: vertex_top,
        curvature: curvature
      });
    };

    /**
     * 开始运动，可自己调用
     */
    self.play = function () {
      this.move();
    };

    /**
     * 按step运动
     */
    self.move = function () {
      var settings = this.settings,
        start = settings.start,
        count = settings.count,
        steps = settings.steps,
        end = settings.end;
      // 计算left top值
      var left = start.left + (end.left - start.left) * count / steps,
        top = settings.curvature == 0 ? start.top + (end.top - start.top) * count / steps : settings.curvature * Math.pow(left - settings.vertex_left, 2) + settings.vertex_top;
      // 运动过程中有改变大小
      if (end.width != null && end.height != null) {
        var i = steps / 2,
          width = end.width - (end.width - start.width) * Math.cos(count < i ? 0 : (count - i) / (steps - i) * Math.PI / 2),
          height = end.height - (end.height - start.height) * Math.cos(count < i ? 0 : (count - i) / (steps - i) * Math.PI / 2);
        $element.css({width: width + "px", height: height + "px", "font-size": Math.min(width, height) + "px"});
      }
      $element.css({
        left: left + "px",
        top: top + "px"
      });
      settings.count++;
      // 定时任务
      var time = window.requestAnimationFrame($.proxy(this.move, this));
      if (count == steps) {
        window.cancelAnimationFrame(time);
        self.destroy();
        // fire callback
        settings.onEnd.apply(this);
      }
    };

    /**
     * 销毁
     */
    self.destroy = function(){
      $element.remove();
    };

    self.init(options);
  };

  // add the plugin to the jQuery.fn object
  $.fn.fly = function (options) {
    return this.each(function () {
      if (undefined == $(this).data('fly')) {
        $(this).data('fly', new $.fly(this, options));
      }
    });
  };

//元素抖动效果：rate:抖动频率（次数），range：抖动范围大小值
function shake(o,rate,range){
    var $panel = $(o);
    box_left = $panel.offset().left;
    $panel.css('margin-left',box_left+'px');
    offset=range/rate;
    //为了兼容火狐将动画写成函数
    function shakeAnimate(curRate){
        $panel.animate({'margin-left':box_left-(range-offset*curRate)+'px'},50);
        $panel.animate({'margin-left':box_left+2*(range-offset*curRate)+'px'},{duration: 50,complete:function(){
         	if(curRate>=rate){
        		$panel.css('margin-left','');
        	} 
        }});    	
    }
    for(var i=1; rate>=i; i++){
    	shakeAnimate(i);
    }
}


//加载等待动画
$.fn.setLoadingState=function(option,callFunc){
	var dft={
		container:'',						//loading加载显示的父容器
		duration:300,						//动画速率
		loadingTxt:'加载中，请等待！',		//加载时显示的文本
		icon:'',							//加载时的图标样式，暂时无用
		destroyAnimate:'opacity'			//加载完成时的动画，top为向上滑出，opacity为渐隐	
	}
	var container;	
	var Options;
	var doLoading;

	var $this=$(this);

	switch(option){
		case 'show':
			var target=$this.attr('data-loading');
			container=target!==''?$(target):$this;
			container.find('.EMallLoading').show();			
		break;
		case 'hide':
			var target=$this.attr('data-loading');
			container=target!==''?$(target):$this;
			container.find('.EMallLoading').hide();
		break;
		case 'destroy':	
			destory();
		break;
		default:
			if($this.find('.EMallLoading').length>0){
				return false;
			}		
			showLoading();
		break;
	}

	function showLoading(){
		Options=$.extend(dft,option);
		container=Options.container!==''?$(Options.container):$this;
		var data_target=Options.container;
		$this.attr('data-loading',data_target);
		var parentWidth=Options.container=='body'?'100%':container.outerWidth()+'px';
		var parentHeight=Options.container=='body'?'100%':container.outerHeight()+'px';
		var cssPosition=Options.container=='body'?'fixed':'absolute';
		var marginTop=Options.container=='body'?$(window).height()/2-28:container.outerHeight()/2-28;
		var top=0-parseInt(container.css('border-top-width'));
		var left=0-parseInt(container.css('border-left-width'));

		if(Options.container=='body'){
			$('body').css('overflow','hidden');
		}

		container.css({'position':'relative'}).prepend(
			'<div class="EMallLoading '+dft.destroyAnimate+'" style="width:'+parentWidth+';height:'+parentHeight+';position:'+cssPosition+';z-index:10000;top:'+top+'px;left:'+left+'px;color:#fafafa;text-align:center;">'
			+'<div class="mask"></div><em class="sprite sprite-loadingIcon0" style="margin-top:'+marginTop+'px"></em>'+Options.loadingTxt+'</div>');

		//loading动画
		var loadingIcon=container.find('.sprite');
		var frameIdx=0;
		doLoading=setInterval(function(){
			loadingIcon.removeClass('sprite-loadingIcon'+frameIdx);
			frameIdx=frameIdx==9?0:frameIdx+1;
			loadingIcon.addClass('sprite-loadingIcon'+frameIdx);
		},50)	

	}

	function destory(){
		var target=$this.attr('data-loading');
		container=target!==''?$(target):$this;
		var loadingUI=container.find('.EMallLoading').css({'overflow':'hidden'});
		var destroyAnimate=loadingUI.hasClass('top')?'top':loadingUI.hasClass('opacity')?'opacity':'top';
		var animateSetting={};
		if(target!=='body' && destroyAnimate=='top'){
			animateSetting.top=0-container.outerHeight();			
		}else if(destroyAnimate=='opacity'){
			animateSetting.opacity=0;
		}
		loadingUI.delay(dft.duration).animate(animateSetting,dft.duration,function(){
			clearInterval(doLoading);
			$(this).remove();
			if(container.css('overflow')=='hidden'){
				container.removeAttr('style');
			}
			//container.removeAttr('style');
			if(callFunc){
				callFunc();
			}

		});	
	}
}

//屏幕pop提示
popScreen=function(option){
	var dft={
		poptxt:'操作成功！',
		bgcolor:'#dd2727',
		fontsize:'14px',
		fontcolor:'#FFF',
		height:''
	}

	var Options=$.extend(dft,option,true);
	var setHeight=Options.height==''?'':'height:'+Options.height+';line-height:'+Options.height;
	var popHtml='<div style="position:fixed;z-index:10;top:40%;left:50%;padding:12px 20px;border-radius:4px;-webkit-border-radius:4px;opacity:0;-webkit-opacity:0;background:'+Options.bgcolor+';font-size:'+Options.fontsize+';color:'+Options.fontcolor+';'+setHeight+'">'+Options.poptxt+'</div>'
	var popObject=$(popHtml).appendTo($('body'));
	popObject.css('margin-left',0-popObject.outerHeight());
	popObject.animate({'opacity':'1'},300).animate({'opacity':'0'},2000,function(){
		popObject.remove();
	})
}

function getCurrentTime(){
	var postTime=new Date();
	var year=postTime.getFullYear()+'年';
	var mounth=postTime.getMonth()+'月';
	var day=postTime.getDay()+'日';
	day=day<10?'0'+day:day;
	var hours=postTime.getHours();
	hours=hours<10?'0'+hours:hours;
	var minutes=postTime.getMinutes();
	minutes=minutes<10?'0'+minutes:minutes;

	return year+mounth+day+' '+hours+':'+minutes;
}


//不规则键名对象按值进行插入排序(需为数字),暂时只支持一维数组
function insertSort(sortObj,orderby,sortFunc){
	var temp;
	//排序时以键名为最后生成排序数据的参考，所以先将键单独存放到数组中并根据排序结果生成排序后的键名数组
	var keyData=Object.keys(sortObj);

	$.each(keyData,function(index,Key){
		temp=sortObj[Key];
		tempKey=keyData[index+1];
		if(temp){
			for(var i=index;i>=0;i--){
				if(!sortFunc){
					if(orderby=='asc' || !orderby){
						if(parseInt(temp)>parseInt(sortObj[tempKey])){
							keyData[i+1]=keyData[i];
						}else{
							break;
						}						
					}else if(orderby=='desc'){
						if(parseInt(temp)<parseInt(sortObj[Key])){
							keyData[i+1]=keyData[i];
						}else{						
							break;
						}
					}
				}else{
					if(!sortObj[tempKey])
					break;

					if(sortFunc(temp,sortObj[tempKey])==1){
						keyData[i+1]=keyData[i];
					}else{
						break;
					}
				}
			}
			if((i+1)!==index+1){
				keyData[i+1]=tempKey;
			}
		}
	})
	//console.log(keyData);
	//生成带序列索引的新对象
	var createSortObj={};
	$.each(keyData,function(index,Key){
		var data={};
		data[Key]=sortObj[Key];
		createSortObj[index]=data;
	})
	return createSortObj;
}

