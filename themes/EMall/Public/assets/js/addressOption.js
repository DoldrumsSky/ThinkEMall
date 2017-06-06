/**
 * ThinkEMall电子商城前端地址管理脚本
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

var addressOption={
	addressItemBind:function(selector,option){
		var dft={
			setDftCallFunc:$.noop(),
			modifyCallFunc:$.noop(),
			deleteCallFunc:$.noop()
		}
		var Options=$.extend(dft,option);
		$('.addressItem'+selector).hover(function(){
			var setDefaultCode;
			var dataIdx=$(this).attr('data-idx');
			//检查是否当前鼠标停留在默认使用的地址上，如果是则不添加设为默认的按钮
			if(parseInt(dataIdx)>1){
				setDefaultCode='<span class="edit"><a class="setDefault" href="javascript:void(0);">设为默认</a></span>';
			}else{
				setDefaultCode='';
			}
			var option=$('<div class="option">'+setDefaultCode
				+'<span class="edit"><a class="modify" href="javascript:void(0);">修改</a></span>'
				+'<span class="edit"><a class="delete" href="javascript:void(0);">删除</a></span></div>').appendTo($(this));

			option.find('.setDefault').on('click',function(e){
				e.stopPropagation();
				var dataIdx=$(this).closest('.addressItem').attr('data-idx');
				addressOption.setToDefault(dataIdx,Options.setDftCallFunc);
			})

			option.find('.modify').on('click',function(e){
				e.stopPropagation();
				var dataIdx=$(this).closest('.addressItem').attr('data-idx');
				addressOption.editAddress(dataIdx,Options.modifyCallFunc);
			})

			option.find('.delete').on('click',function(e){
				e.stopPropagation();
				var dataIdx=$(this).closest('.addressItem').attr('data-idx');
				addressOption.deleteAddress(dataIdx,Options.deleteCallFunc);
			})

			$(this).find('.default').css({'background':'#ff6600','border-color':'#ff6600'});
		},function(){
			$(this).find('.option').remove();
			$(this).find('.default').css({'background':'#11a6ec','border-color':'#11a6ec'});
		});	
	},

	//编辑地址
	editAddress:function(idx){
		editIdx=idx;
		var editItem=$('div[data-idx="'+idx+'"]');
		$('#input_tag').val(editItem.attr('data-tag'));
		$('#input_consignee').val(editItem.attr('data-consignee'));
		$('#input_telNumber').val('');
		$('#province').val(editItem.attr('data-province_id'));
		$('#province').trigger('change');
		$(':input[name="province"]').val(editItem.attr('data-province_name'));
		$('#city').val(editItem.attr('data-city_id'));
		$('#city').trigger('change');
		$(':input[name="city"]').val(editItem.attr('data-city_name'));
		$('#district').val(editItem.attr('data-district_id'));
		$('#district').trigger('change');
		$(':input[name="district"]').val(editItem.attr('data-district_name'));
		$('#detailInfo').val(editItem.attr('data-address'));
		$('#input_zipcode').val(editItem.attr('data-zipcode'));	
		$('#confirmBtn').text('确认修改').attr('data-post','edit');
		$('#addressModalLabel').text('编辑修改收货地址');		
		$('#addressModal').modal('show');
		return false;
	},

	//删除地址
	deleteAddress:function(idx,callFunc){
		console.log(idx);
		messagesBox({
			title:'删除提示！',
			content:'点击确定按钮将永久删除当前选择的收货地址，您确定要删除吗？',
			callFunc:function(){
				var deleteItem=$('div[data-idx="'+idx+'"]');
				deleteItem.setLoadingState();
				$.ajax({
					type: 'GET',
					url: deleteAddressURL,
					dataType: 'json',
					data:{'idx':idx},
					async:true,
					success: function(data){
						if(data.status==1){
							deleteItem.animate({'opacity':0},300).animate({'width':0},200).queue(function(){
								deleteItem.remove();
								addressOption.reOrderIdx();
								totalAddrNum--;
								$('.addressItem:eq(1)').addClass('default').prepend('<em class="default">默认使用地址</em>');
								if(callFunc){
									callFunc(data.data);
								}
							});
						}else{
							$('div[data-idx="'+idx+'"]').find('.delete').setPopTip({
								content:data.error,
								placement:'top',
								arrow:'bottom',
								arrowAlign:'vcenter',
								showonce:true
							})
						}
						deleteItem.setLoadingState('destroy');
					},
					error:function(XMLHttpRequest, textStatus, errorThrown){
						deleteItem.setLoadingState('destroy',function(){
							alert('数据链接错误，statuCode:'+XMLHttpRequest.status);						
						});
					}
				})
			}
		})
		return false;
	},

	//设为默认使用地址
	setToDefault:function(setIdx,callFunc){
		var setItem=$('div[data-idx="'+setIdx+'"]');
		setItem.setLoadingState();
		$.ajax({
			type: 'GET',
			url: setDefaultURL,
			dataType: 'json',
			data:{'idx':setIdx},
			async:true,
			success: function(data){
				if(data.status==1){
					setItem.setLoadingState('destroy',function(){
						setItem.addClass('default').css('opacity',0).queue(function(next){
							$(this).find('.setDefault').remove();
							next();
						});
						var setItemPosLeft=setItem.position().left;
						var setItemPosTop=setItem.position().top;
						var dftItemPosLeft,dftItemPosTop;
						var defaultItem=$('div[data-idx="1"]');
						defaultItem.removeClass('default').css('opacity',0).queue(function(next){
							dftItemPosLeft=$(this).position().left;
							dftItemPosTop=$(this).position().top;	
							next();
						}).find('.default').remove();
						//动画效果
						defaultItem.before(
							setItem.attr('data-idx',1).clone(true).css({
								'width':287.672,
								'opacity':1,
								'position':'absolute',
								'left':setItemPosLeft,
								'top':setItemPosTop,
								'z-index':1					
							}).prepend('<em class="default">默认使用地址</em>').animate({'left':dftItemPosLeft,'top':dftItemPosTop}).queue(function(next){
								setItem.remove();
								$(this).removeAttr('style').trigger('mouseleave');
								next();
							})
						).after(defaultItem.attr('data-idx',setIdx).clone(true).css({
								'width':287.672,
								'opacity':1,
								'position':'absolute',
								'left':dftItemPosLeft,
								'top':dftItemPosTop,
								'z-index':1					
							}).animate({'left':setItemPosLeft,'top':setItemPosTop}).queue(function(next){
								defaultItem.remove();
								$(this).removeAttr('style');
								next();
							})
						);				
					});

					if(callFunc){
						callFunc(data.data);
					}

				}else{
					$('div[data-idx="'+setIdx+'"]').find('.setDefault').setPopTip({
						content:data.error,
						placement:'top',
						arrow:'bottom',
						arrowAlign:'vcenter',
						showonce:true
					})
					setItem.setLoadingState('destroy');
				}
			},
			error:function(XMLHttpRequest, textStatus, errorThrown){
				setItem.setLoadingState('destroy',function(){
					alert('数据链接错误，statuCode:'+XMLHttpRequest.status);				
				});
			}

		})
		return false;
	},

	//删除后更新数据索引值
	reOrderIdx:function(delIdx){
		var addressItem=$('#addressList .addressItem');
		$.each(addressItem,function(index){
			if(index==0){
				return true;
			}
			$(this).attr('data-idx',index);
		})
	},

	init:function(option){
		var dft={
			setDftCallFunc:$.noop(),
			modifyCallFunc:$.noop(),
			deleteCallFunc:$.noop(),
			submitCallFunc:$.noop()				//地址添加编辑成功后的回调函数，主要用于即时刷新本地的对应地址数据
		}
		var Options=$.extend(dft,option);

		//获取地区信息
		getAreaData({},'#province','#city','#district');
		//事件绑定
		addressOption.addressItemBind(':gt(0)',option);	

		$('#addAddress').hover(function(){
			$(this).css('color','#ff6600');
			$(this).find('.tm-mcPlus').addClass('hover');
		},function(){
			$(this).css('color','#c8a235');
			$(this).find('.tm-mcPlus').removeClass('hover');
		});

		//添加地址事件绑定
		$('#addAddress').on('click',function(){
			$('#confirmBtn').text('确认添加').attr('data-post','add');
			$('#addressModalLabel').text('添加新的收货地址');			
		});

		//添加地址-提交表单事件
		$('#confirmBtn').unbind().on('click',function(e){
			e.stopPropagation();
			var $this=$(this);
			var optionURL;
			if($this.attr('data-post')=='add'){
				optionURL= addAddressURL;
			}else if($this.attr('data-post')=='edit'){
				optionURL= editAddressURL+'&idx='+editIdx;
			}
			var editDialog=$('#addressModal .modal-dialog');
			editDialog.setLoadingState({destroyAnimate:'opacity'});

			$.ajax({
				type: 'POST',
				url:optionURL,
				dataType: 'json',
				data:$('#addressForm').serialize(),
				async:true,
				success: function(data){
					if(data.status==1){
						var tag=$('#input_tag').val();
						var consignee=$('#input_consignee').val();
						var telNumber=$('#input_telNumber').val();
						var provinceCode=$('#province').val();
						var province=$(':input[name="province"]').val();
						var cityCode=$('#city').val();
						var city=$(':input[name="city"]').val();
						var districtCode=$('#district').val();
						var district=$(':input[name="district"]').val();
						var detailInfo=$('#detailInfo').val();
						var zipcode=$('#input_zipcode').val();

						editDialog.setLoadingState('destroy',function(){
							$('#addressModal').modal('hide');					
						});

						//检查操作方法
						if($this.attr('data-post')=='add'){
							//添加新的数据到页面中
							var defaultAddress=$('.addressItem:eq(1)');
							var bindIndex=data.idx;
							var defaultTag='';
							var tagStyle='curSel';
							//没有任何地址时直接在添加操作栏位后面,并且切换添加默认地址的样式和标识
							if(defaultAddress.length==0){
								defaultAddress=$('.addressItem:eq(0)');
								bindIndex=1;
								defaultTag='<em class="default">默认使用地址</em>';
								tagStyle='default';
							}
							//
							$('#addressList').append(
								'<div class="addressItem '+tagStyle+'" data-idx="'+data.idx+'" data-consignee="'+consignee+'" data-tel="'+telNumber+'" data-province_id="'+provinceCode+'" data-province_name="'+province+'" data-city_id="'+cityCode+'" data-city_name="'+city+'" data-district_id="'+districtCode+'" data-district_name="'+district+'" data-zipcode="'+zipcode+'" data-address="'+detailInfo+'" data-tag="'+tag+'"><em class="new">新添加</em><dl>'
								+defaultTag+'<dt><em>'+consignee+'</em><span>'+tag+'</span></dt>'
								+'<dd>'+telNumber+'</dd>'
								+'<dd>'+province+' '+city+' '+district+'</br>'
								+detailInfo+' ('+zipcode+')</dd></dl></div>');
							totalAddrNum++;		
							addressOption.addressItemBind('[data-idx="'+bindIndex+'"]');	

						}else{

							var editItem=$('.addressItem:eq('+editIdx+')');
							editItem.html('<dl><dt><em>'+consignee+'</em><span>'+tag+'</span></dt>'
								+'<dd>'+telNumber+'</dd>'
								+'<dd>'+province+' '+city+' '+district+'</br>'
								+detailInfo+' ('+zipcode+')</dd></dl>');
							//修改地址属性数据
							editItem.attr('data-consignee',consignee);
							editItem.attr('data-tel',telNumber);
							editItem.attr('data-province_id',provinceCode);
							editItem.attr('data-province_name',province);
							editItem.attr('data-city_id',cityCode);
							editItem.attr('data-city_name',city);
							editItem.attr('data-district_id',districtCode);
							editItem.attr('data-district_name',district);
							editItem.attr('data-zipcode',zipcode);
							editItem.attr('data-address',detailInfo);
							editItem.attr('data-tag',tag);
							editItem.addClass('curSel');
							if(editIdx==1){
								editItem.prepend('<em class="default">默认使用地址</em>');						
							}else{
								editItem.append('<em class="new">新编辑</em>');
							}
						}
						//执行指定的回调函数
						if(Options.submitCallFunc){
							Options.submitCallFunc(data.data);
						}
					}else{
						//console.log(data);
						$this.setPopTip({
							content:data.error,
							width:234,
							placement:'top',
							arrow:'bottom',
							arrowAlign:'vcenter',
							showonce:true
						})
						editDialog.setLoadingState('destroy');
						//alert(data.error);
					}

				},
				error:function(XMLHttpRequest, textStatus, errorThrown){
			       /*alert(XMLHttpRequest.responseText); 
			       alert(XMLHttpRequest.status);
			       alert(XMLHttpRequest.readyState);
			       alert(textStatus); // parser error;*/
			       editDialog.setLoadingState('destroy',function(){
						alert('数据链接错误，statuCode:'+XMLHttpRequest.status);	       	
			       });
				}
			})
		})
	}
}
