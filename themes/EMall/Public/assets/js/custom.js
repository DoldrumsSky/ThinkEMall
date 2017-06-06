/**
 * ThinkEMall电子商城前端自定义脚本
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

;(function($){

	var nav = $('.navbar'),
		doc = $(document),
		win = $(window);

	win.scroll(function() {

		if (doc.scrollTop() > 106) {
			nav.addClass('navbar-fixed-top scrolled');
		} else {
			nav.removeClass('navbar-fixed-top scrolled');
		}

	});

	// Trigger the scroll listener on page load
	
	win.scroll();

	$.fn.sModal=function(param){
		show=function(){
			$this.show();			
		}
		var $this=$(this);
		switch(param){
			case 'show':
			show();
			console.log('show');
			break;
			default:
			break;	
		}

	}

	

	//登录验证码刷新
	$('#logRefreshVerifyImg').click(function(e){
		$(this).prev().attr('src','/index.php?g=api&m=checkcode&a=index&length=4&font_size=14&width=120&height=34&charset=123456780&use_noise=1&use_curve=0&time='+Math.random());
	});

	//注册验证码刷新
	$('#regRefreshVerifyImg').click(function(e){
		$(this).prev().attr('src','/index.php?g=api&m=checkcode&a=index&length=4&font_size=14&width=120&height=34&charset=123456780&use_noise=1&use_curve=0&time='+Math.random());
	});


	//弹出用户登录模态框
	$('#loginBtn').click(function(){
		$('#loginModal').modal('show');
		//显示登录表单
		$('#userLogin').trigger('click');
	});
	//弹出用户注册模态框
	$('#regBtn').click(function(){
		$('#loginModal').modal('show');
		//显示注册表单
		$('#userReg').trigger('click');
	});	

	//为了在移动端能正常显示注册登录按钮，在navbar-header中也加入了登录注册按钮
	$('#loginBtnxs').click(function(e){
		$('#loginBtn').trigger('click');		
	});


	$('#regBtnxs').click(function(e){
		$('#regBtn').trigger('click');
	});


	//顶部导航的下拉菜单
	$.fn.TopDropMenu=function(options){
		var dft={
			//模态框的显示位置，按百分比来算
			position:0.5
		}
		var opt=$.extend(dft,options);
		var $this=$(this);
		//显示后设定位置
		$this.hover(function(e){
			$this.find('.topDropItems').show();
			$this.find('.zy-menuItem').addClass('expanded');
			//alert(bodyHeight);
			//获取浏览器当前的高度，用于定位模态框			
			
		},function(){
			$this.find('.topDropItems').hide();
			$this.find('.zy-menuItem').removeClass('expanded');
		});		
	};

	//
	$.fn.DoSlider=function(options){
		var dft={
			curIndex:0,
			preIndex:0,
			totalNum:1,
			duration:3000,
			sliderShow:function(type){
				var _this=this;
				sliderNavBtn=sliderNav.find('li');
				if(type==null){
					if(this.curIndex==this.totalNum-1){
						this.curIndex=0;
						this.preIndex=this.totalNum-1;
					}else {
						this.preIndex=this.curIndex;
						this.curIndex++;
					}
				}

				sliderNavBtn.eq(this.preIndex).removeClass('cur');
				//hide
				$oldSlide=$sliderElement.eq(this.preIndex);
				$oldSlide.animate({opacity:0},400,function(){
					$oldSlide.hide();					
				});

				sliderNavBtn.eq(this.curIndex).addClass('cur');
				$curSlide=$sliderElement.eq(this.curIndex);
				//show
				$curSlide.show();

				$curSlide.animate({opacity:1},400,function(){
				
				});						

				//换背景	
				$('.content').css({'background-color':$curSlide.css('background-color')});
			
			}
		}

		var opt=$.extend(dft,options);
		var $this=$(this);
		var $sliderElement=$this.find('.slider');
		var timerID,sliderStr="";
		sliderNav=$('.sliderNav');
		
		opt.totalNum=$sliderElement.length;
		opt.preIndex=opt.totalNum-1;

		if(opt.totalNum>1){				
			opt.sliderShow(1);
			timerID=setInterval(function(){opt.sliderShow()},opt.duration);
		
			//鼠标经过事件
			$this.hover(function(){
				clearInterval(timerID);
			},function(){
				timerID=setInterval(function(){opt.sliderShow()},opt.duration);
			});

			//添加幻灯导航
			for(i=0;i<opt.totalNum;i++){
				sliderStr+="<li></li>";
			}

			sliderNav.html(sliderStr);
			sliderNavBtn=sliderNav.find('li');
			sliderNavBtn.eq(opt.curIndex).addClass('cur');
			sliderNavBtn.click(function(){
				if(opt.curIndex!==$(this).index()){
					opt.preIndex=opt.curIndex;
					opt.curIndex=$(this).index();
					opt.sliderShow(1);
				}
			});
		}
	
	}

	$.fn.showCatMenuItem=function(){
		var cateItem=$(this).find('li');
		var catePop=cateItem.find('.cate_pop');
		var curIndex=-1;

		cateItem.hover(function(){

			curIndex=$(this).index();

				$(this).addClass('popshow');
				 catePop.eq(curIndex).show();

		},function(){
				$(this).removeClass('popshow');
				catePop.eq(curIndex).hide();
		});
	}


	// 导航菜单样式控制，目前是按照bootstrap原有的样式结构来调用的，如果自己定义了下拉菜单的样式，请自行修改程序

	$.fn.menuAction=function(options){

		var dft={
			//本来想用这个参数按分类ID激活导航的，可是一定得改后台导航生成代码，有兴趣的可以自己试试
			cid:1,
			//下拉菜单弹出样式，1是普通的原样式，2是全屏式弹出，不适合做移动适配的PC网站，不好控制，容易出BUG
			menuType:1,
			//暂时没特别用途，目前用来获取第一个主菜单对象，也就是'首页'
			menuClassID:'#menu-item-',
			//下拉菜单动画时长 
			duration:200,
			//弹出菜单的高度
			height:'30px'
		};

		var opt=$.extend(dft,options);
		var $this=$(this);
		var $thisLi=$this.find('li');
		//激活首页菜单背景样式
		var activeMenu=$this.find(opt.menuClassID+opt.cid);
		activeMenu.addClass('active');
		var dropdownMenu=$thisLi.find('ul');


		//监听导航菜单鼠标经过事件
		$thisLi.hover(function(e){
			//防止事件冒泡
			e.stopPropagation();
			//移动时展开下拉菜单
			$(this).addClass("open");

			if($(this).hasClass('active')==false){
				activeMenu.removeClass('active');
			}	
			//设定全屏式的菜单弹出的样式
			if($(this).hasClass('dropdown')==true && opt.menuType==2){

				//全屏必改的position样式，这里需要改两个才能生效
				$('#navbar').css('position','static');
				$('.nav>li').css('position','static');

				//移除原来的弹出菜单样式
				dropdownMenu.removeClass('dropdown-menu');

				//弹出菜单所有菜单项宽度的总和
				var menuWidth=0;
				//获取菜单的位置和宽度，用于定位弹出菜单项的显示位置
				var dropdMenuWidth=dropdownMenu.width();	
				var dropdMenuPos=dropdownMenu.offset().left+dropdMenuWidth/2;

				dropdownMenu.children('li').each(function(i,n){
					var obj = $(n);
					menuWidth+=obj.width();     		
				});

				//我设置的padding样式与其它类有重复，如果不重新定义填充高度会导致动画在padding的位置失效
				dropdownMenu.css('padding','0px 0px 0px '+parseInt(dropdMenuPos-menuWidth/2)+'px');
				//alert(dropdownMenu.offset().left+','+dropdMenuPos+','+menuWidth);	
				dropdownMenu.stop(true,false).addClass('fullScreen-dropdown-menu').animate({height: opt.height},opt.duration);		
						
			}
				
		},function(e){	
			//收起下拉菜单
			$(this).removeClass("open");		
			if($(this).hasClass('hasChild')==false){
				activeMenu.addClass('active');
				//恢复原菜单弹出的样式
				if($(this).hasClass('dropdown')==true && opt.menuType==2){
				//动画完成后恢复原有样式
				dropdownMenu.stop(true,false).animate({height: '0px'},opt.duration,function(){
					$('#navbar').css('position','relative');
					$('.nav>li').css('position','relative');
					dropdownMenu.removeClass('fullScreen-dropdown-menu');	
					dropdownMenu.addClass('dropdown-menu');
					dropdownMenu.css('padding','0px');	
				});
				};
			}
		});

	}

	//加载广告数据
	getAdListData=function(id,callFunc){
		$.post(GV.getSpecifyAdDataURL,{id:id,returnType:0},function(data){
			if(data.status==1){
				//console.log(data.data);
				loadAdvertisementData(data.data,data.curServerTime,data.goodsData);
				if(callFunc){
					callFunc();
				}
			}else{
				console.log(data.error);
			}
		}).error(function(){

		})
	}


	checkEndTime=function (limitTime,curTime){
	    var limit=new Date(limitTime.replace("-", "/").replace("-", "/")); 
	    var cur=new Date(curTime.replace("-", "/").replace("-", "/"));
	    if(limit<cur){  
	        return false;  
	    }  
	    return true;  
	}  

	//加载广告类数据
	loadAdvertisementData=function(adList,curServerTime,goodsData){
		if(adList!==''){
			var adListHtml='';
			adList=$.parseJSON(adList);
			var sortKey='listorder';
			$.each(adList,function(index,AD){
				var ad_container=$('#'+AD.ad_container);
				if(AD.ad_data==''){
					return true;
				}
				//数据排序
				var asc = function(x,y) {  
	        		return (x[sortKey] > y[sortKey]) ? 1 : -1  
	    		}  
	    		AD.ad_data.AdItem.sort(asc);

				//幻灯广告
				if(AD.ad_type==1){
					var sliderItem=ad_container.find('[data-role="sliderItem"]');
					//如果在幻灯中存在不同版式的输出，请将所有版式代码输入，数据留空
					if(sliderItem.length>1){
						$.each(sliderItem,function(index,Item){
							$(this).find('[data-role="ad_image"]').attr('src',AD.ad_data.AdItem[index].ad_image);
							$(this).find('[data-action="ad_link"]').attr('href','//'+AD.ad_data.AdItem[index].ad_link);	
							if(AD.ad_data.AdItem[index].ad_bgcolor!==""){
								$(this).css('background',AD.ad_data.AdItem[index].ad_bgcolor);		
							}			
						})
					//克隆第一个版式元素输出
					}else if(sliderItem.length>0){
						var outputNum=parseInt(ad_container.attr('data-num'));
						$.each(AD.ad_data.AdItem,function(index,Item){
							if(index>outputNum-1){
								return false;
							}
							var addSliderItem=sliderItem.clone();
							addSliderItem.find('[data-role="ad_image"]').attr('src',Item.ad_image);
							addSliderItem.find('[data-action="ad_link"]').attr('href','//'+Item.ad_link);
							if(Item.ad_bgcolor!==''){
								addSliderItem.css('background',Item.ad_bgcolor);	
							}
							
							sliderItem.after(addSliderItem);
						})	
						sliderItem.remove();				
					}

				//类目组广告
				}else if(AD.ad_type==2){
					var preIdx=0;
					//封面
					if(AD.ad_cover && AD.ad_cover!==''){
						var ad_cover=ad_container.find('[data-role="ad_cover"]');
						ad_cover.find('[data-action="ad_link"]').attr('href','//'+AD.ad_cover.ad_link);
						ad_cover.find('[data-role="ad_image"]').css({'background':'url('+GV.imgPath+AD.ad_cover.ad_image+') no-repeat center bottom','height':'330px','background-size':'100%','filter':'progid:DXImageTransform.Microsoft.AlphaImageLoader(src='+GV.imgPath+AD.ad_cover.ad_image+',sizingMethod=scale)'});
						ad_cover.find('[data-role="ad_title"]').html(AD.ad_cover.ad_title);
						ad_cover.find('[data-role="second_title"]').html(AD.ad_cover.second_title);
						ad_cover.find('[data-role="sub_title"]').html(AD.ad_cover.sub_title);
						ad_cover.show();
					}

					//按顺序输出
					var outputNum=0;
					var AdItemWrap=ad_container.find('[data-role="termsAdItem"]');
					//是否已经完成当前广告栏输出的标识，此标识为true时，后面的广告栏都将不再输出数据
					var finishOutput=false;
					//累计广告元素不同版式部分的起始序位数，用于建立格式化元素版式的对应索引号
					var cumulativeNum=0;

					if(AdItemWrap.length>0){
						var preOutputNum=0;
						var preIdx=0;
						$.each(AdItemWrap,function(index){
							$this=$(this);
							//取广告输出数量
							var limitItemNum=parseInt($(this).attr('data-num'));
							//取第一个广告版式元素用于复制出其它对应广告代码
							var AdItemForm=$(this).find('[data-role="AdItemForm"]:first');
							//取格式化版式数据（用于自定义格式化某个元素的样式）
							//字符格式为：第1个元素,添加的样式|第1个元素,添加的样式，如：1,tow-grid css2 css...|4,tow-grid
							//目前样式只添加到元素的外层
							var formatStr=$(this).attr('data-format');
							var dataFormat=[];
							if(formatStr!=='' && formatStr!==undefined){
								formatStr=formatStr.split('|');
								$.each(formatStr,function(formatIdx,data){
									var Item=data.split(',');
									formatIdx=cumulativeNum+parseInt(Item[0]);
									dataFormat[formatIdx]=Item[1];
									cumulativeNum+=limitItemNum;
								})
								//console.log(dataFormat);
							}						

							$.each(AD.ad_data.AdItem,function(adIdx,Item){
								if(finishOutput==true){
									//清空第一个用于复制的数据元素
									AdItemForm.remove();
									return false;
								}

								if(outputNum>=(preOutputNum+limitItemNum)){
									preOutputNum=outputNum;
									preIdx=adIdx;
									//清空第一个用于复制的数据元素
									AdItemForm.remove();
									return false;
								}

								if(Item.ad_status==0 || adIdx<preIdx){
									return true;
								}

								if((Item.end_time!=='' && !checkEndTime(Item.end_time,curServerTime)) || (Item.start_time!=='' && checkEndTime(Item.start_time,curServerTime))){
									return true;
								}

								//
								var addAdItem=AdItemForm.clone().show();
									if(addAdItem.attr('data-action')=='ad_link'){
										addAdItem.attr('href','//'+Item.ad_link);
									}else{
										addAdItem.find('[data-action="ad_link"]').attr('href','//'+Item.ad_link);
									}								
									addAdItem.find('[data-role="ad_title"]').html(Item.ad_title);
									addAdItem.find('[data-role="second_title"]').html(Item.second_title);
									addAdItem.find('[data-role="sub_title"]').html(Item.sub_title);
									addAdItem.find('[data-role="ad_image"]').attr('src',GV.imgPath+Item.ad_image);
									//判断是否需要格式化版式
									if(dataFormat.length>0){
										if(dataFormat[outputNum+1]!==undefined){
											addAdItem.addClass(dataFormat[outputNum+1]);
										}
									}

									$this.append(addAdItem);

								outputNum++;
							})
							//如果第一组广告输出数量低于限制数表示实际广告数少于广告位，则标识不再往下输出 
							if(preOutputNum<limitItemNum){
								finishOutput=true;
								//清空第一个用于复制的数据元素
								AdItemForm.remove();						
							}
						})
					}
				//首页普通图文类广告
				//当只有一个填充广告的元素并指定了填充数如：data-num=3时，程序会使用第一个广告填充元素拷贝出3个可填充广告的元素
				//当不指定填充数量，并且可填充广告元素大于等于1个时，程序会直接在可填充广告元素中加入广告数据
				}else if(AD.ad_type==3){
					AdItemWrap=ad_container.find('[data-role="normalAdItem"]');
					var adObjCount=AdItemWrap.length;
					var adCount=Object.keys(AD.ad_data.AdItem).length;
					//当容器包含data-num参数时，会按这个广告数量进行输出
					var specifyNum=parseInt(ad_container.attr('data-num'));
					specifyNum=isNaN(specifyNum)?0:specifyNum;
					//当前输出的广告索引号
					var curAdIdx=-1;
					var outputNum=0;

					$.each(AdItemWrap,function(index){
						if(curAdIdx==adCount-1){
							return false;
						}
						$this=$(this);

						//如果指定了输出的广告数量，按照指定数量一次性输出，否则找到可填充的广告后跳出循环，再继续循环一个广告容器进行填充
						$.each(AD.ad_data.AdItem,function(adIdx,Item){
							if(specifyNum>0 && outputNum>=specifyNum){
								return false;
							}
							if(adIdx<=curAdIdx && curAdIdx>=0){
								return true;
							}
							if(Item.ad_status==0){
								return true;
							}
							if((Item.end_time!=='' && !checkEndTime(Item.end_time,curServerTime)) || (Item.start_time!=='' && checkEndTime(Item.start_time,curServerTime))){
								return true;
							}
							//判断需要直接填充还是克隆第一个广告元素并添加
							var newAD;
							if(specifyNum>0 && adObjCount==1){
								newAD=$this.clone().show();
							}else{
								newAD=$this;
							}
							var actionItem=newAD.find('[data-action="ad_link"]');
							//由于绑定商品时的内链和外链协议不一定一样，所以内链使用/，外链使用//
							var realLink=decodeURIComponent(Item.ad_link);
							var transDelimiter=realLink.substr(0,5)=='index'?'/':'//';
							if(actionItem.length>0){
								actionItem.attr('href',transDelimiter+realLink);
							}else{
								newAD.attr('href',transDelimiter+realLink);
							}
							newAD.find('[data-role="ad_title"]').text(Item.ad_title);
							//查看是否存在绑定商品
							if(Item.goods_id=='' || !Item.goods_id){
								newAD.find('[data-role="ad_image"]').attr('src',GV.imgPath+Item.ad_image);
								//如果想混排各种版式，自己处理需要增加删的元素
								newAD.find('[data-role="goods_price"]').empty();
								newAD.find('[data-role="appraise_num"]').closest('.appraiseInfo').remove();
							}else{
								newAD.find('[data-role="ad_image"]').attr('src',Item.ad_image);

								//遍历查找对应绑定商品数据
								if(goodsData!==null){
									$.each(goodsData,function(gIdx,Goods){
										if(Goods.goods_id==Goods.goods_id){
											newAD.find('[data-role="goods_price"]').text('￥'+Goods.goods_price);
											newAD.find('[data-role="appraise_num"]').text(Goods.appraise_num);
										}
									})
								}
							}
							curAdIdx=adIdx;
							outputNum++;
							//直接填充时退出循环
							if(specifyNum==0 && adObjCount>=1){
								return false;
							}else{
								ad_container.append(newAD);
								$this.remove();
							}
						})				

					})
				}
			})
			
		}
	}

	//调用顶部mini导航数据
	$.fn.getTopShowNav=function(option){
		var dft={
			postURL:'',
			cid:2
		}

		var Options=$.extend(dft,option,true);

		if(Options.postURL=='' || isNaN(Options.cid)){
			alert('缺少调用导航的参数！');
		}

		//顶部分类导航监听
		$(this).hover(function(){
			$(this).addClass('hover');
			var categorys=$(this).find('#categorys-mini-main');
			categorys.show();
			if(categorys.find('.item').length==0){
				//加载菜单数据
				$.post(Options.postURL,{cid:Options.cid},function(data){
					if(data.status==1){
						console.log(data.data);
						var navHtml='';
						var container=categorys.find('.dd-inner');
						$.each(data.data,function(cidx,Catgory){
							navHtml+='<div class="item"><h3>';
							var i=0;
							var navNum=Object.keys(Catgory.child).length;
							$.each(Catgory.child,function(index,nav){
								var seperator=i<navNum-1?'、':'';
								navHtml+='<a href="'+nav.href+'" target="_blank">'+nav.label+'</a>'+seperator;
								i++;
							})
							navHtml+='</h3></div>';
						})
						container.append(navHtml);
					}else{
						container.append(data.error);
					}
				}).error(function(){
					container.append('无法获取导航数据！');
				})
			}
		},function(){
			$(this).removeClass('hover');
			$(this).find('#categorys-mini-main').hide();
		})
	}





//可用于获取隐藏元素尺寸的扩展插件，利用的是visibility与dispaly的区别
 $.fn.addBack = $.fn.addBack || $.fn.andSelf;

 $.fn.extend({

   actual : function ( method, options ){
     // check if the jQuery method exist
     if( !this[ method ]){
       throw '$.actual => The jQuery method "' + method + '" you called does not exist';
     }

     var defaults = {
       absolute      : false,
       clone         : false,
       includeMargin : false
     };

     var configs = $.extend( defaults, options );

     var $target = this.eq( 0 );
     var fix, restore;

     if( configs.clone === true ){
       fix = function (){
         var style = 'position: absolute !important; top: -1000 !important; ';

         // this is useful with css3pie
         $target = $target.
           clone().
           attr( 'style', style ).
           appendTo( 'body' );
       };

       restore = function (){
         // remove DOM element after getting the width
         $target.remove();
       };
     }else{
       var tmp   = [];
       var style = '';
       var $hidden;

       fix = function (){
         // get all hidden parents
         $hidden = $target.parents().addBack().filter( ':hidden' );
         style   += 'visibility: hidden !important; display: block !important; ';

         if( configs.absolute === true ) style += 'position: absolute !important; ';

         // save the origin style props
         // set the hidden el css to be got the actual value later
         $hidden.each( function (){
           var $this = $( this );

           // Save original style. If no style was set, attr() returns undefined
           tmp.push( $this.attr( 'style' ));
           $this.attr( 'style', style );
         });
       };

       restore = function (){
         // restore origin style values
         $hidden.each( function ( i ){
           var $this = $( this );
           var _tmp  = tmp[ i ];

           if( _tmp === undefined ){
             $this.removeAttr( 'style' );
           }else{
             $this.attr( 'style', _tmp );
           }
         });
       };
     }

     fix();
     // get the actual value with user specific methed
     // it can be 'width', 'height', 'outerWidth', 'innerWidth'... etc
     // configs.includeMargin only works for 'outerWidth' and 'outerHeight'
     var actual = /(outer)/.test( method ) ?
       $target[ method ]( configs.includeMargin ) :
       $target[ method ]();

     restore();
     // IMPORTANT, this plugin only return the value of the first element
     return actual;
   }
 });

})(jQuery);
