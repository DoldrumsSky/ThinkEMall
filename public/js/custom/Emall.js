/**
 * ThinkEMall电子商城商品管理前端脚本
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
 * $Id: custom.js 17217 2017-01-10 06:29:08Z YangHua $
*/

//商品属性设置
$('input:radio[name="post[type_id]"]').change(function(){
	$this=$(this);
	if($this.val()==1){
		$('#goods_discount').attr({'disabled':'true','required':false});
		$('#startDiscountTime').val('').attr('disabled',true);
		$('#endDiscountTime').val('').attr('disabled',true);
	}else if($this.val()==2){
		$('#goods_discount').removeAttr('disabled').attr('required',true);		
        $('#startDiscountTime').removeAttr('disabled');
        $('#endDiscountTime').removeAttr('disabled');
	}
});

//消费积分设置
$('input:radio[name="optPoints"]').change(function(){
	$this=$(this);
	if($this.val()==1){
		$('#goods_points').attr('disabled','disabled');
	}else if($this.val()==2){
		$('#goods_points').removeAttr('disabled');
	}
});


//输入参数表单
//typeId:表单类型ID,1是普通的单行输入文本框，2是选择列表框
//paramName:参数标题名
//options:当参数为选择列表时解析options中的数据
function getParamFormStr(typeId,paramName,options){
	formHtmlCode='';
	switch(typeId){
		case '1':
			formHtmlCode= '<tr><th>'+paramName+':<input type="hidden" name="param_name[]" value="'+paramName
			+'"></th><td><input type="text" name="goods_param[]"></td></tr>';
			break;
		case '2':
			var selectFormCode;
			$.each(options,function(index,Item){
				selectFormCode+='<option value="'+paramName+'">'+Item+'</option>';
			})
			formHtmlCode= '<tr><th>'+paramName+':<input type="hidden" name="param_name[]" value="'+paramName
			+'"></th><td><select name="goods_param[]">'+selectFormCode+'</select></td></tr>';
			break;
	}
	//console.log(formHtmlCode);
	return formHtmlCode;
}


//解析设置勾选的单品项
var Emall={

	//Ajax快速添加品牌
	AddEMallBrand:function(postURL,term_id){
		brandPanel=$('#fastAddBrand');
		if(brandPanel.data('expand')!=='expanded'){
			htmlCode='<div class="form-group" id="brandPanel"><form id="brandForm" method="post" enctype="multipart/form-data">'
					+'<label>品牌名称：<input type="text" name="brand_name" placeholder="请填写品牌名称" required="true"></label>'
					+'<label>品牌别名：<input type="text" name="alias" placeholder="中英文分开时填写如：lenovo"></label>'
					+'<input type="text" id="brandTermId" name="brandTermId" value="'+term_id+'" style="display:none">'
					+'<label>品牌图标：<div class="controls"><div id="logoUploader" style="width:100%;overflow:hidden">'						
					+'<div id="logoList" class="uploader-list"></div><div id="logoPicker">选择图片</div></div></div></label>'						
					+'<div class="ctrlBtnWrap"><input type="button" name="cancel" class="btn btn-gray" value="取消"><input type="button" name="submit" value="提交" class="btn btn-orange" ></div></form></div>'							
												
			brandPanel.append(htmlCode);
			brandPanel.data('expand','expanded');
    		brandPanel.find("#logoUploader").setUploader('#logoList',{pick:{id:'#logoPicker'},uploadImgType:'normal'});
    		//取消添加事件绑定
    		cancelBtn=brandPanel.find('input[name="cancel"]');
			cancelBtn.click(function(){
				brandPanel.find('div').remove();
				brandPanel.data('expand','normal');
			});
			//提交商品数据绑定
			brandPanel.find('input[name="submit"]').click(function(){
				brand_name=brandPanel.find('input[name="brand_name"]').val();
				brand_logo=brandPanel.find('input[name="UpFilePathInfo"]');
				//校验表单
				if(brand_name==''){
					alert('品牌名称必须填写！');
					return false;
				}
				if(brand_logo.length>0){
					if(brand_logo.val()==''){
						alert('必须上传品牌Logo！');
						return false;
					}
				}else{
						alert('必须上传品牌Logo！');
						return false;					
				}

				$.post(postURL,$('#brandForm').serialize(),function(response){
					if(response.status==1){
						$('#goods_brand').append(response.data);
						cancelBtn.trigger('click');
						console.log(response);
					}else{
						alert(response.data);
						//console.log('failed：'+response.data);
					}
				})
			})
		}

	},

	//单品项原始数据载入，用于商品数据编辑页面
	SetSKUOption:function (SKUOptionData){
		if(!SKUOptionData || SKUOptionData==''){
			return false;
		}

		SKUOptionData=$.parseJSON(SKUOptionData);
		//对比标注已经勾选的外观类单品项属性
		if(SKUOptionData.SKU_Style){
			$.each(SKUOptionData.SKU_Style,function(index,value){
				var checkedOption=$('input[type="checkbox"][name="SKU_Style[]"]:eq('+value.SKU_Style_Idx+')');
				var checkboxWrap=checkedOption.closest('label');
				checkboxWrap.find('span').text(value.modifyValue);
				checkboxWrap.find('[data-tag="modifyValue"]').attr('value',value.modifyValue);
				if(!checkedOption.is(':checked')){
					checkedOption.trigger('click');
				}
			});
		}

		//外观样式属性单品项图片数据载入
		if(SKUOptionData.SKU_Style){
			$.each(SKUOptionData.SKU_Style,function(index,value){
				var SKU_Upload=$('#SKU_Upload .SKU_Uploader_Wrap:eq('+index+')');
				SKU_Upload.find('.webuploader-pick').css({'background':'url('+value.SKU_ThumbImg_M+') no-repeat center center','background-size':'80% 80%'});
				SKU_Upload.find('.picTitle').text(value.modifyValue);
				SKU_Upload.find('.uploader-list').append(
						'<input type="hidden" name="UpFilePathInfo[SKU_Pic][]" value="'+value.SKU_Img+'">'
						);
				//这个隐藏域用于存放图片更新状态，如果里面有值，表示图片处于更新状态，后台会根据这个值判断是否需要生成图片缩略图
				SKU_Upload.append('<input type="hidden" name="updateTag[SKU_Pic][]" value="">');
			});
		}

		//对比标注已经勾选的规格类单品项属性
		if(SKUOptionData.SKU_Spec){
			$.each(SKUOptionData.SKU_Spec,function(index,value){
				var checkedOption=$('input[type="checkbox"][name="SKU_Spec[]"]:eq('+value.SKU_Spec_Idx+')');
				var checkboxWrap=checkedOption.closest('label');
				checkboxWrap.find('span').text(value.modifyValue);
				checkboxWrap.find('[data-tag="modifyValue"]').attr('value',value.modifyValue);
				if(!checkedOption.is(':checked')){
					checkedOption.trigger('click');
				}
			});
		}

		//单品项单项数据行载入
		$.each(SKUOptionData.SKU_Form,function(index,value){
			var SKU_Form=$('#SKU_Form .form-group:eq('+index+')');
			//console.log(value.sku_spec);
			SKU_Form.find('input[data-sku="SKU_Spec"]').val(value.sku_spec);			
			SKU_Form.find('input[data-sku="SKU_Style"]').val(value.sku_style);
			SKU_Form.find('input[name="SKU_Price[]"]').val(value.sku_price);
			SKU_Form.find('input[name="SKU_Stock[]"]').val(value.sku_stock).trigger('propertychange');
			SKU_Form.find('input[name="SKU_SeriesNum[]"]').val(value.sku_series);

		});


	},

	//主要单品项设置
	SetSKUOptionListen:function(optionItem){
		//建立存放SKU表单数据的对象
		var SKU_Data={main:[],sub:[]};		
		var SKU_Form=$('#SKU_Form');
		var SKU_Upload=$('#SKU_Upload');
		var SKU_TitleBar=$('#SKU_Title');
		var goodsStock=$('#goodsStock');
		//计算库存
		countStock=function(){
			var totalStock=0;
			$.each(SKU_Form.find('.form-group'),function(index){
				value=$(this).find('input[name="SKU_Stock[]"]').val();
				value=value==''?0:parseInt(value);
				totalStock+=value;				
			});	
			goodsStock.attr('value',totalStock);	
			//console.log('');
		};

		//添加单品项图片上传对象,dataTag传入的是颜色或者外观样式类的标识标签，用于批量设置相同的对应图片
		//infoText:上传对象标题
		//dataTag:选中的对应SKU单品项属性的标识
		//selIndex:选中的SKU单品项属性的索引号，用于给上传对象排序
		//skuId:单品项类型ID标识
		addSKU_Uploader=function(infoText,dataTag,selIndex,skuId){
			//获取单品项属性的个数，用于排序
			SKU_TypeNum=$('#'+skuId).length;
			//获取已经生成的上传对象个数
			uploaderWrap=SKU_Upload.find('.SKU_Uploader_Wrap');
			SKU_UploadNum=uploaderWrap.length;
			time = (new Date()).valueOf();
			//console.log(time);
			var SKU_Num=SKU_Form.find('.form-group').length;
			var uploaderName="SKU_Uploader"+time;
			var picker='SKUPicker'+time;
			var listName='SKU_fileList'+time;
			var $uploader;
			uploaderCode='<div class="SKU_Uploader_Wrap" data-tag="'+dataTag+'" data-idx="'+selIndex+'">'
							+'<div id="'+uploaderName+'" class="uploaderWrap">'
							+'<div id="'+picker+'"></div>'
							+'<div class="picTitle">'+infoText+'</div>'  
							+'<div id="'+listName+'" class="uploader-list"></div>'
							+'</div>';

			
			if(SKU_UploadNum>0){
				$.each(uploaderWrap,function(index){
					if(selIndex<$(this).data('idx')){
							$uploader=$(uploaderCode).insertBefore(this);
							$uploader.setUploader(
								'#'+listName,{postName:'SKU_Pic',infoText:infoText,pick:{id:'#'+picker},thumb:{height:102}}
							);					
							return false;
					}else{
						 if(SKU_UploadNum-1==index){
							$uploader=$(uploaderCode).insertAfter(this);
							$uploader.setUploader(
								'#'+listName,{postName:'SKU_Pic',infoText:infoText,pick:{id:'#'+picker},thumb:{height:102}}
							);								
						}
					}
				})
			}else{
				$uploader=$(uploaderCode).appendTo(SKU_Upload);
				$uploader.setUploader(
					'#'+listName,{postName:'SKU_Pic',infoText:infoText,pick:{id:'#'+picker},thumb:{height:102}}
				);			
			}

		}



		//移除单品项上传对象
		removeSKU_Uploader=function(dataTag){
			console.log(SKU_Upload.find('[data-tag="'+dataTag+'"]'));
			SKU_Upload.find('[data-tag="'+dataTag+'"]').remove();
		}


		//重新生成图片上传对象对应单品项的标题信息
		//dataTag：指定更新对象的标识
		//specifyText:指定更新的标题信息
		//skutype：单品项类型标识
		//toPicTitle：是否更新到上传图片标题，这个只应用于sub属性的SKU项如：颜色
		resetSKUInfoText=function(dataTag,specifyText,skutype,toPicTitle){
			if(toPicTitle==true){
				SKU_Upload.find('[data-tag="'+dataTag+'"] .picTitle').text(specifyText);
			}

				SKU_CheckBox=$('#'+skutype).find('input[data-tag="'+dataTag+'"]');
				SKU_Label=SKU_CheckBox.closest('label');
				//修改对应checkbox的label值 
				SKU_Label.find('span').text(specifyText);
				//修改后的值会存储在这个hidden输入框中一并提交到后台做为数据修改编辑时的数据构造参数
				SKU_Label.find('input[name="'+skutype+'_Cus[]"]').val(specifyText);
		}

		//保持相同属性的单品项名称一致
		sameSKUInputValue=function(dataTag,value){
			SKU_Form.find('.form-group input[data-tag="'+dataTag+'"]').val(value);
		}

		//为没有定义data-tag的上传对象定义这个tag标识，图片上传后，默认会为相同外观样式的单品项设置同样的图片
		setUploaderDataTag=function(tagName){
			$.each(SKU_Upload.find('.SKU_Uploader_Wrap'),function(){
				$(this).attr('data-tag',tagName);
			});
		}

		//SKU表单项的索引值转换，用于序列化新增的SKU表单项，转换索引用的是两项SKU属性元素的索引标识
		//rowIdx:规格单品项属性元素的索引值
		//colIdx:外观样式单品项属性元素的索引值
		changeToSKUFormIdx=function(rowIdx,colIdx){
			//如果只有单个单品属性被选中，那么直接返回选择的单品属性元素索引
			if(rowIdx<0){
				return colIdx;
			}else if(colIdx<0){
				return rowIdx;
			}
			return skuGroupIdx[[rowIdx,colIdx]];
		}

		//所有单品项组合的索引值生成，用于单品项表单创建位置排序时的索引值对比
		createSKUOptionIdx=function(){
			//获取主副两个单品项属性的元素个数
			colIdx=$(optionItem[0]).find('label').length-1;
			rowIdx=$(optionItem[1]).find('label').length-1;
			//console.log(colIdx+','+rowIdx);
			skuGroupIdx=new Array();
			idxValue=-1;
			for(i=0;i<=rowIdx;i++){
				for(j=0;j<=colIdx;j++){
					idxValue++;
					skuGroupIdx[[i,j]]=idxValue;
					//console.log('skuGroupIdx:['+i+']['+j+']'+skuGroupIdx[[i,j]]);
				}
			}
			return skuGroupIdx;
		}
		//生成单品项属性序列索引值存储的二维数组
		var skuGroupIdx=createSKUOptionIdx();
		//console.log(changeToSKUFormIdx(1,-1));

		//添加单品项数据勾选监听
			$.each(optionItem,function(index,Item){
				$(Item+' input[type="checkbox"]').click(function(){
					$this=$(this);
					OptionItem=$(Item);
					labelWrap=$this.closest('label');
					//取选择的单品项属性索引值
					SKU_OptItemIdx=labelWrap.index();
					//拿到设置项最外层容器
					wrap=OptionItem.parent();
					//拿到当前checkbox索引，用于生成表单项ID，因为用了bootstrap,所以html代码结构导致要选择上一层拿索引号
					selItemTag=$this.attr('data-tag');
					//当前生成的对象属性名，用于查找生成的副单品项表单元素
					seledItemCallTag='input[data-tag="'+selItemTag+'"]';
					splitCode='<span>-</span>';
					//库存表单代码
					stockHtmlCode=splitCode+'<input type="text" name="SKU_Stock[]" placeholder="库存数量" class="stockInput">';
					//单品价格表单代码
					priceHtmlCode=splitCode+'<input type="text" name="SKU_Price[]" placeholder="单品价格" class="priceInput">';
					//单品项SKU编号代码
					seriesNumHtmlCode=splitCode+'<input type="text" name="SKU_SeriesNum[]" placeholder="SKU编号" class="stockInput">';

					if($this.is(':checked')){
						SKU_TitleBar.show();
						mainSKUType=$this.attr('data-sku');
						//为选择的属性项添加name用于表单数据提交
						labelWrap.find('input[data-tag="modifyValue"]').attr('name',mainSKUType+'_Cus[]');
						labelWrap.find('input[data-tag="idx"]').attr('name',mainSKUType+'_Idx[]');
						//存在单品项时禁止设置总库存
						$('#goodsStock').attr('readonly',true);		

						SKUHtmlCode='<input type="text" name="'+mainSKUType+'Name[]" '
								+'value="'+$this.val()+'" data-tag="'+selItemTag+'" data-sku="'+mainSKUType+'" data-idx="'+SKU_OptItemIdx+'">';	
						//判断选择的单品项属性
						if(OptionItem.data("skutype")==='sub'){
							//遍历查找是否存在选择的主项，如果存在，则为主项添加副项
							if(SKU_Data.main.length>0){
								//如果已经存在副单品项，则直接新建一组单品项，否则将新勾选的属性表单添加进主单品项组里
								if(SKU_Data.sub.length>0){
									$.each(SKU_Data.main,function(index,Item){
										mainSKUItem=SKU_Form.find('input[data-tag="'+Item+'"]');
										//取当前要设置的单品项属性选项
										curOptItem=$('input[type="checkbox"][data-tag="'+mainSKUItem.attr('data-tag')+'"]');
										//取单品项种类名
										mainSKUType=curOptItem.attr('data-sku');
										//生成索引值
										SKU_RowIdx=curOptItem.closest('label').index();
										groupIdx=changeToSKUFormIdx(SKU_RowIdx,SKU_OptItemIdx);
										//console.log('groupIdx:'+groupIdx+',row:'+SKU_RowIdx+',col:'+SKU_OptItemIdx);
										allCode='<div class="form-group" data-idx="'+groupIdx+'">'
											+'<input type="text" name="'+mainSKUType+'Name[]" '
											+'value="'+mainSKUItem.val()
											+'" data-tag="'+mainSKUItem.attr('data-tag')+'" data-sku="'+mainSKUType+'" data-idx="'+SKU_RowIdx+'">'
											+splitCode+SKUHtmlCode+stockHtmlCode+priceHtmlCode+seriesNumHtmlCode+'</div>';

										var $formGroup;
										//排序插入新的SKU表单项
										SKU_FormWrap=SKU_Form.find('.form-group');
										$.each(SKU_FormWrap,function(index){
											//console.log('index:'+index+',SKULength:'+SKU_FormWrap.length+',idx:'+$(this).attr('data-idx'));
											if(groupIdx<$(this).attr('data-idx')){
												$formGroup=$(allCode).insertBefore($(this));
												return false;
											}else{
												if(SKU_FormWrap.length-1==index){
													$formGroup=$(allCode).insertAfter($(this));
												}
											}
										})

										//绑定库存计算	
										$formGroup.find('input[name="SKU_Stock[]"]').bind('input propertychange',function(){
											countStock();
										});	
										//绑定SKU主属性标题即时输入设置事件
										$formGroup.find('input[name="SKU_SpecName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());
											//infoText=$formGroup.find('input[name="SKU_Style[]"]').val();
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'));
										});
										//绑定SKU副属性标题即时输入设置事件
										$formGroup.find('input[name="SKU_StyleName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());
											//infoText=$formGroup.find('input[name="SKU_Spec[]"]').val()+' ';
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'),true);
										});	
										
									});
								}else{									
									SKU_TitleBar.find('.title_style').show();
									$.each(SKU_Data.main,function(index,Item){
										mainSKUItem=SKU_Form.find('input[data-tag="'+Item+'"]');
										formWrapper=mainSKUItem.closest('.form-group');
										mainSKUItem.after(splitCode+SKUHtmlCode);
										//绑定单品项属性即时输入设置上传对象标题事件
										$.each(formWrapper,function(){
											//重设单品项表单组索引值											
											SKU_RowIdx=$(this).attr('data-idx');
											$(this).attr('data-idx',changeToSKUFormIdx(SKU_RowIdx,SKU_OptItemIdx));
											$(this).find('input[name="SKU_StyleName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());

												//infoText=tmpWrapper.find('input[name="SKU_Spec[]"]').val();
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'),true);
											});											
										})

									});	
									//设置上传对象对应单品项的标题
									resetSKUInfoText();
									//为没有dataTag的上传对象设置对应外观样式单品项属性
									setUploaderDataTag(selItemTag);
								}
							}else{
								SKU_TitleBar.find('.title_style').show();
								//取所有已经添加的单品项表单，根据其索引数据来判断新添加数据的插入位置
								SKU_FormWrap=SKU_Form.find('.form-group');
								allCode='<div class="form-group" data-idx="'+SKU_OptItemIdx+'">'+SKUHtmlCode+stockHtmlCode+priceHtmlCode+seriesNumHtmlCode+'</div>';
								var $formGroup;
								//不存在主项时直接获取排序后添加
								if(SKU_FormWrap.length>0){
									$.each(SKU_FormWrap,function(index){
										if(SKU_OptItemIdx<$(this).attr('data-idx')){
											$formGroup=$(allCode).insertBefore($(this));
											return false;
										}else{
											if(SKU_FormWrap.length-1==index){
												$formGroup=$(allCode).insertAfter($(this));
											}
										}
									})
								}else{
									$formGroup=$(allCode).appendTo(SKU_Form);
								}
									//绑定库存计算	
									$formGroup.find('input[name="SKU_Stock[]"]').bind('input propertychange',function(){
										countStock();
									});	
										//绑定SKU属性标题即时输入设置事件
										$formGroup.find('input[name="SKU_StyleName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());
											//infoText=$formGroup.find('input[name="SKU_Spec[]"]').val();
											//infoText=infoText!==undefined?infoText+' ':'';
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'),true);
										});	
									//添加对应单品项的图片上传对象
									//addSKU_Uploader($this.val(),selItemTag);									
							}

							SKU_Data.sub.push(selItemTag);
							//添加对应单品项的图片上传对象
							addSKU_Uploader($this.val(),selItemTag,$this.closest('label').index(),$this.attr('data-sku'));
						}else{
							//如果勾选的是主项，直接添加一组表单
							if(SKU_Data.sub.length>0){
								//
								if(SKU_Data.main.length>0){
									$.each(SKU_Data.sub,function(index,Item){
										mainSKUItem=SKU_Form.find('input[data-tag="'+Item+'"]');
										//取当前要设置的单品项属性选项
										curOptItem=$('input[type="checkbox"][data-tag="'+mainSKUItem.attr('data-tag')+'"]');
										//取单品项种类名
										mainSKUType=curOptItem.attr('data-sku');
										//生成索引值
										SKU_ColIdx=curOptItem.closest('label').index();
										groupIdx=changeToSKUFormIdx(SKU_OptItemIdx,SKU_ColIdx);

										allCode='<div class="form-group" data-idx="'+groupIdx+'">'
											+SKUHtmlCode+splitCode
											+'<input type="text" name="'+mainSKUType+'Name[]" '
											+'value="'+mainSKUItem.val()
											+'" data-tag="'+mainSKUItem.attr('data-tag')+'" data-sku="'+mainSKUType+'" data-idx="'+SKU_ColIdx+'">'
											+stockHtmlCode+priceHtmlCode+seriesNumHtmlCode+'</div>';

										var $formGroup
										//排序插入新的SKU表单项
										SKU_FormWrap=SKU_Form.find('.form-group');
										$.each(SKU_FormWrap,function(index){
											if(groupIdx<$(this).attr('data-idx')){
												$formGroup=$(allCode).insertBefore(this);
												return false;
											}else{
												if(SKU_FormWrap.length-1==index){
													$formGroup=$(allCode).insertAfter(this);
												}
											}
										})
										//绑定库存计算	
										$formGroup.find('input[name="SKU_Stock[]"]').bind('input propertychange',function(){
											countStock();
										});	
										//绑定SKU属性标题即时输入设置事件
										$formGroup.find('input[name="SKU_SpecName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());
											//infoText=$formGroup.find('input[name="SKU_Style[]"]').val();
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'));
										});
										//绑定SKU副属性标题即时输入设置事件
										$formGroup.find('input[name="SKU_StyleName[]"]').bind('input propertychange',function(){
											sameSKUInputValue($(this).attr('data-tag'),$(this).val());
											//infoText=$formGroup.find('input[name="SKU_Spec[]"]').val()+' ';
											resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'),true);
										});	
										//添加对应单品项的图片上传对象
										//addSKU_Uploader($this.val()+' '+mainSKUItem.val(),Item);
									});									
								}else{									
									SKU_TitleBar.find('.title_spec').show();
									$.each(SKU_Data.sub,function(index,Item){
										subSKUItem=SKU_Form.find('input[data-tag="'+Item+'"]');
										formWrapper=subSKUItem.closest('.form-group');
										subSKUItem.before(SKUHtmlCode+splitCode);
										//绑定单品项属性即时输入设置上传对象标题事件
										$.each(formWrapper,function(){
											//重设单品项表单组索引值
											SKU_ColIdx=$(this).attr('data-idx');
											$(this).attr('data-idx',changeToSKUFormIdx(SKU_OptItemIdx,SKU_ColIdx));
											$(this).find('input[name="SKU_SpecName[]"]').bind('input propertychange',function(){
												sameSKUInputValue($(this).attr('data-tag'),$(this).val());
												//infoText=tmpWrapper.find('input[name="SKU_Style[]"]').val();
												resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'));
											});											
										})
									});
									//更新上传对象对应的单品项名
									resetSKUInfoText();
								}
							}else{
								SKU_TitleBar.find('.title_spec').show();
								//取所有已经添加的单品项表单，根据其索引数据来判断新添加数据的插入位置
								SKU_FormWrap=SKU_Form.find('.form-group');
								SKUHtmlCode='<input type="text" name="'+mainSKUType+'Name[]" '
								+'value="'+$this.val()+'" data-tag="'+selItemTag+'" data-sku="'+$this.attr('data-sku')+'" data-idx="'+SKU_OptItemIdx+'">';

								allCode='<div class="form-group" data-idx="'+SKU_OptItemIdx+'">'+SKUHtmlCode+stockHtmlCode+priceHtmlCode+seriesNumHtmlCode+'</div>';
								var $formGroup;
								//不存在主项时直接获取排序后添加
								if(SKU_FormWrap.length>0){
									$.each(SKU_FormWrap,function(index){
										if(SKU_OptItemIdx<$(this).attr('data-idx')){
											$formGroup=$(allCode).insertBefore(this);
											return false;
										}else{
											if(SKU_FormWrap.length-1==index){
												$formGroup=$(allCode).insertAfter(this);
											}
										}
									})
								}else{
									$formGroup=$(allCode).appendTo(SKU_Form);
								}

								//绑定库存计算	
								$formGroup.find('input[name="SKU_Stock[]"]').bind('input propertychange',function(){
									countStock();
								});
								//绑定SKU属性标题即时输入设置事件
								$formGroup.find('input[name="SKU_SpecName[]"]').bind('input propertychange',function(){
									sameSKUInputValue($(this).attr('data-tag'),$(this).val());
									resetSKUInfoText($(this).attr('data-tag'),$(this).val(),$(this).attr('data-sku'));
								});	
								//添加对应单品项的图片上传对象
								//addSKU_Uploader($this.val());										
							}

							SKU_Data.main.push(selItemTag);
						}
						
					//取消勾选时的处理
					}else{
						//为选择的属性项添加name用于表单数据提交
						labelWrap.find('input[data-tag="modifyValue"]').removeAttr();
						labelWrap.find('input[data-tag="idx"]').removeAttr();

						selItemTag=$this.attr('data-tag');

						//获取SKU标识，用于移除时的记号判断
						optSKUItem=SKU_Form.find('input[data-tag="'+selItemTag+'"]');
						//获取SKU表单容器
						wrapper=optSKUItem.parent();
						//判断选择的单品项属性
						if(OptionItem.data("skutype")==='sub'){		
							if(SKU_Data.main.length>0){	
								if(SKU_Data.sub.length>1){
									//当存在多个单品项属性时，使用遍历进行合并删除
									$.each(wrapper,function(){
										//移除单品项
										$(this).remove();								
									});
								}else{
									SKU_TitleBar.find('.title_style').hide();
									//移除分隔符和对应表单项
									wrapper.find('span:eq(0)').remove();			
									optSKUItem.remove();
									//当所有单品项副属性移除后重新更换单品项索引值
									$.each(SKU_Form.find('.form-group'),function(index){
										$(this).attr('data-idx',$(this).find('input:eq(0)').attr('data-idx'));
									})
								}		
															
							}else{								
								wrapper.remove();
							}
							SKU_Data.sub.splice($.inArray(selItemTag,SKU_Data.sub),1);	
							//移除上传表单
							removeSKU_Uploader($this.attr('data-tag'));
								
						}else{
							if(SKU_Data.sub.length>0){
								//所有主项取消勾选时的操作
								if(SKU_Data.main.length==1){
									SKU_TitleBar.find('.title_spec').hide();
									//移除分隔符和对应表单项
									wrapper.find('span:eq(0)').remove();
									optSKUItem.remove();									
									//当所有单品项主属性移除后重新更换单品项索引值
									$.each(SKU_Form.find('.form-group'),function(index){
										$(this).attr('data-idx',$(this).find('input:eq(0)').attr('data-idx'));
									})
								}else{
									//当存在多个单品项属性时，使用遍历进行合并删除
									$.each(wrapper,function(){
										//移除单品项
										$(this).remove();								
									});						
								}
							}else{
								wrapper.remove();
									
							}
							SKU_Data.main.splice($.inArray(selItemTag,SKU_Data.main),1);	
						}	

						//不存在单品项时激活设置总库存
						if(SKU_Data.sub.length==0 && SKU_Data.main.length==0){	
								SKU_TitleBar.hide();
								SKU_TitleBar.find('.title_spec').hide();
								SKU_TitleBar.find('.title_style').hide();
								$('#goodsStock').attr('readonly',false);	
						}	

						//重计算总库存
						countStock();
						//恢复SKU选项默认值
						$this.closest('label').find('span').text($this.val());

					}
				});
			});


		}

	}

	//以列表形式获取数据并填充进列表表单项,以ajax形式调用
	$.fn.getSelectListData=function(options,selEvtCB){
		var dft={
			wrapName:'#paramForm',			//属性参数表单的容器
			selectName:'goodsProperty',		//列表表单ID名
			getDataURL:'',					//ajax提交的链接地址
			formWidth:'150px',				//列表表单宽度
			selListId:'',					//编辑商品时如果此参数存在则表示有选择的参数模板
			paramData:''					//编辑商品时如果有存在的参数数据，则直接传进来解析赋值
		}

		var Option=$.extend(dft,options);

		$(this).click(function(){
			$this=$(this);
			var selectForm=$('#'+Option.selectName);
			if($this.val()=='填写更多商品参数'){
				//判断表单是否存在，如果存在，直接显示			
				if(selectForm.length>0){
					$this.attr('value','取消商品参数设置');
					selectForm.show();
					//直接调用回调函数
					selItemId=selectForm.val();
					selItemText=selectForm.find('option:selected').text();
					if(selItemId>0){
						selEvtCB(selItemId,selItemText);
					}
					return true;
				}

				$this.parent().append('<img src="'+BASE_URL+'/images/loading.gif" id="loadingIco" />');
				//加载商品属性参数项
				$.post(Option.getDataURL,{},function(response){
					$('#loadingIco').remove();
					if(response.status==1){
						$this.attr('value','取消商品参数设置');
						//加入属性列表
						if(response.data.length>0){
							propertyHtmlCode='';
							$.each(response.data,function(index,name){
								propertyHtmlCode+='<option value="'+name.cat_id+'">'+name.cat_name+'</option>';
							});
							propertyHtmlCode='<select id="'+Option.selectName+'" style="width:'+Option.formWidth+'" name="post[property_id]"><option value="-1">请选择参数模板</option>'
							+propertyHtmlCode+'</select>';
							$this.parent().append(propertyHtmlCode);
							//监听列表选择
							selectForm=$('#'+Option.selectName);
							selectForm.change(function(){
								selItemId=$(this).val();
								selItemText=$(this).find('option:selected').text();
								//获取对应参数
								if(selItemId>0){
									//移除默认项
									$(this).find('option[value="-1"]').remove();
									selEvtCB(selItemId,selItemText);
								}
								//console.log(selItemId);
							});
						}else{
							$this.parent().append('没有任何商品属性数据，请先添加！');
							$('#'+Option.selectName).removeAttr('name');
						}

						//console.log(response);
					}else{
						$('#loadingIco').remove();
						$this.parent().append('<a href="#">重新获取</a>');
						//console.log(response);
					}
				});

			}else{
				$this.attr('value','填写更多商品参数');
				selectForm.hide();
				$(Option.wrapName).remove();
				$('#loadingIco').remove();
			}	
		
	});

}

//运费配置代码生成
function getLogisticsCode(id){
	var logisticsCode,optRowCode;
	switch(id){
	case '0':
	optRowCode='<tr class="optRow"><td class="area-td"><a class="addArea" data-rowIdx="0" href="javascript:">指定配送地区（点击链接可编辑）</a></td><td>1</td><td><input type="text" value="15" class="firstPrice"></td><td>1</td><td><input type="text" value="0" class="nextPrice"></td><td><a class="delOption" data-rowIdx="0" href="javascript:">删除</a></td></tr>';
	logisticsCode='<div class="optWrap"><table class="table table-bordered"><thead style="background:#fafafa"><tr>'
	+'<th class="area">运送到</th><th>首件</th><th>运费（元）</th><th>续件</th><th>续费（元）</th><th>操作</th></tr>'
	+'</thead><tbody>'+optRowCode+'</tbody></table>'
	+'<div style="padding:0 0 20px 0"><a class="addOption" href="javascript:">新增地区运费设置</a>（未指定的地区表示不支持配送）</div></div>';
	break;
	case '1':
	optRowCode='<tr class="optRow"><td class="area-td"><a class="addArea" data-rowIdx="0" href="javascript:">指定配送地区（点击链接可编辑）</a></td><td><input type="text" value="0.5" class="first"></td><td><input type="text" value="15" class="firstPrice"></td><td><input type="text" value="0.5" class="next"></td><td><input type="text" value="5" class="nextPrice"></td><td><a class="delOption" data-rowIdx="0" href="javascript:">删除</a></td></tr>';
	logisticsCode='<div class="optWrap"><table class="table table-bordered"><thead style="background:#fafafa"><tr>'
	+'<th class="area">运送到</th><th>首重（Kg）</th><th>运费（元）</th><th>续重（Kg）</th><th>续费（元）</th><th>操作</th></tr>'
	+'</thead><tbody>'+optRowCode+'</tbody></table>'
	+'<div style="padding:0 0 20px 0"><a class="addOption" href="javascript:">新增地区运费设置</a>（未指定的地区表示不支持配送）</div></div>';
	break;
	case '2':
	optRowCode='<tr class="optRow"><td class="area-td"><a class="addArea" data-rowIdx="0" href="javascript:">指定配送地区（点击链接可编辑）</a></td><td><input type="text" value="0.1" class="first"></td><td><input type="text" value="15" class="firstPrice"></td><td><input type="text" value="0.1" class="next"></td><td><input type="text" value="5" class="nextPrice"></td><td><a class="delOption" data-rowIdx="0" href="javascript:">删除</a></td></tr>';
	logisticsCode='<div class="optWrap"><table class="table table-bordered"><thead style="background:#fafafa"><tr>'
	+'<th class="area">运送到</th><th>首体积（m<sup>3</sup>）</th><th>运费（元）</th><th>续体积（m<sup>3</sup>）</th><th>续费（元）</th><th>操作</th></tr>'
	+'</thead><tbody>'+optRowCode+'</tbody></table>'
	+'<div style="padding:0 0 20px 0"><a class="addOption" href="javascript:">新增地区运费设置</a>（未指定的地区表示不支持配送）</div></div>';
	break;
	default:
	break;
	};	

	var code={'logisticsCode':logisticsCode,'optRowCode':optRowCode};

	return code;
}

/*初始化，加载省区数据*/
function getProvinceCode(){
		$.ajax({
			type: 'GET',
			url: jsonURLRoot+'cmf_province.json',
			dataType: 'json',
			async:false,
			success: function(data){
            	provinceData= data.province;
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

        var basicCode='<ul><li class="position"><label class="checkbox"><input class="pRoot" type="checkbox" value="">';

		var east=basicCode+'华东</label></li>';
		var north=basicCode+'华北</label></li>';
		var middle=basicCode+'华中</label></li>';
		var south=basicCode+'华南</label></li>';
		var northeast=basicCode+'东北</label></li>';
		var northwest=basicCode+'西北</label></li>';
		var southwest=basicCode+'西南</label></li>';
		var around=basicCode+'港澳台</label></li>';
		//生成代码
		$.each(provinceData,function(index,Item){
			var checkboxCode='<li><label class="checkbox"><input type="checkbox" value="'+Item.province+'" data-id="'+Item.id+'">'+Item.province+'</label></li>';
			if(Item.position=='华东'){
				east+=checkboxCode;
			}else if(Item.position=='华北'){
				north+=checkboxCode;
			}else if(Item.position=='华中'){
				middle+=checkboxCode;
			}else if(Item.position=='华南'){
				south+=checkboxCode;
			}else if(Item.position=='东北'){
				northeast+=checkboxCode;
			}else if(Item.position=='西北'){
				northwest+=checkboxCode;
			}else if(Item.position=='西南'){
				southwest+=checkboxCode;
			}else if(Item.position=='港澳台'){
				around+=checkboxCode;
			}
		})  

        return '<div class="selAreaBox">'+east+'</ul>'+north+'</ul>'+middle+'</ul>'+south+'</ul>'+northeast+'</ul>'+northwest+'</ul>'+southwest+'</ul>'+around+'</ul><div style="text-align:center;padding:10px 0"><button type="button" class="btn btn-blue" role="confirm">确定</button><button type="button" class="btn btn-gray" role="cancel">取消</button></div></div>';
}

//获取地域数据用于设置收发货地址,传递的参数是对应加载数据的列表框表单ID
//selItem为默认选中项的数据
function getAreaData(selItem,province,city,district){
	var dft={
		'provinceId':-1,
		'cityId':-1,
		'districtId':-1
	}

	var selData=$.extend(dft,selItem);
	var provinceForm=$(province);
	$.ajax({
		type: 'GET',
		url: jsonURLRoot+'areaData.json',
		dataType: 'json',
		async:false,
		success: function(data){
			var htmlProvince = '';

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
						if(cityData[code][i].id==selData.cityId){
							selStr='selected="selected"'
						}
					  	htmlCity += '<option value="'+cityData[code][i].id+'" '+selStr+'>'+cityData[code][i].name+'</option>';
					}
	        		$(city).html(htmlCity);
	        		if(district){
		        		for(var i=0; i<districtData[cityCode].length;i++){
		        			var selStr='';
							if(districtData[cityCode][i].id==selData.districtId){
								selStr='selected="selected"'
							}
						  	htmlDistrict += '<option value="'+districtData[cityCode][i].id+'">'+districtData[cityCode][i].name+'</option>';
						}
		        		$('#district').html(htmlDistrict);	        			
	        		}

	        	}	        	
	        });
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
	}
}


//运费模板设置
$.fn.setLogisticsWay=function(){
	var $this=$(this);
	var curAreaDialog;
	var id=$(':radio[name="post[logistics_type]"]:checked').val();
	//返回当前默认选择中的设置代码
	var optCode=getLogisticsCode(id);
	//加载地区数据
	var provinceCode=getProvinceCode();

	//选择地区对话框按钮事件绑定
	function addDialogEvent(element){
		element.find('button[role="confirm"]').click(function(){
			var selAreaName='';
			var rowIdx=element.attr('data-rowidx');
			var idx=element.attr('data-idx');
			//var selItem=[];
			var selData={'provinceName':[],'provinceId':[]};
			//存储数据
			$.each(element.find('ul :checkbox:checked'),function(index){
				//跳过总地区选项
				if($(this).hasClass('pRoot')){
					return true;
				}

				if($(this).attr('disabled')!=='disabled'){
					//console.log(Item);
					selAreaName+=$(this).val()+'，';
					selData.provinceName.push($(this).val());
					selData.provinceId.push($(this).attr('data-id'));
				}
			})
			if(selData.provinceId.length>0){
				wayData[idx].push([]);	
				wayData[idx][rowIdx]=selData;
			}else{
				wayData[idx].splice(rowIdx,1);
				//console.log();
				//wayData[idx][rowIdx].remove();
			}

			console.log(wayData);
			selAreaName=selAreaName==''?'指定配送地区（点击链接可编辑）':selAreaName;
			element.closest('.optWrap').find('.addArea[data-rowidx="'+rowIdx+'"]').text(selAreaName);
			element.hide();
			curAreaDialog=undefined;
		});

		element.find('button[role="cancel"]').click(function(){
			element.hide();
			curAreaDialog=undefined;
		});

		element.find('.position :checkbox').click(function(){
			if($(this).is(':checked')){
				$(this).closest('ul').find(':checkbox:gt(0)').prop('checked','true');			
			}else if(!$(this).is(':checked')){
				$(this).closest('ul').find(':checkbox:gt(0)').removeAttr('checked');				
			}

		})
	}

	//配送设置事件监听
	var addAction=function(element,wayIdx){
		var addArea=element.find('.addArea');
		var addOption=element.find('.addOption');
		var delOption=element.find('.delOption');
		//配置配送查询存储索引 
		if(wayIdx){
			addArea.attr('data-idx',wayIdx);
			addOption.attr('data-idx',wayIdx);
			delOption.attr('data-idx',wayIdx);
		//如果重新切换计费方式，则遍历设定选中的配送方式对应的配置查询存储索引
		}else{
			$.each(element.find(':checkbox[role="transway"]:checked'),function(){				
				optWrap=$(this).parent().next();
				optWrap.find('.addArea').attr('data-idx',$(this).val());
				optWrap.find('.addOption').attr('data-idx',$(this).val());
				optWrap.find('.delOption').attr('data-idx',$(this).val());
			});
		}
		//设置配送地区
		addArea.click(function(){
			var rowIdx=$(this).attr('data-rowidx');
			var idx=$(this).attr('data-idx');
			selAreaBox=$(this).closest('.optWrap').find('.selAreaBox');
			//检测是否已经获取地区数据
			if(provinceCode==undefined){
				provinceCode=getProvinceCode();
			}
			//检查是否已经有存在的地区选择对话框，没有则载入
			if(selAreaBox.length>0){
				selAreaBox.show();
			}else{
				selAreaBox=$(provinceCode).appendTo($(this).closest('.optWrap'));
				//选择对话框事件绑定
				addDialogEvent(selAreaBox);
				selAreaBox.show();
			}
			//标识当前操作的地区选项对话框，限制只能操作一个对话框
			if(curAreaDialog){
				curAreaDialog.hide();
				curAreaDialog=selAreaBox;
			}else{
				curAreaDialog=selAreaBox;
			}
			//传递查询存储索引值
			selAreaBox.attr({'data-rowidx':$(this).attr('data-rowidx'),'data-idx':$(this).attr('data-idx')});
			//设置数据前先查询已经存在的数据，屏蔽已经勾选的地区选项
			$.each(selAreaBox.find('ul :checkbox'),function(index){
				var checkbox=$(this);
				//路过总地区选项
				if(checkbox.hasClass('pRoot')){
					return true;										
				}
				//遍历查找存在的配送地区数据
				$.each(wayData[idx],function(index,selItem){
					//如果索引的数据不存在则跳过
					if(!selItem.provinceId){
						return true;
					}
					//如果当前编辑的是已经存在的数据，则激活对应选项，否则禁止选择
					if(index==rowIdx){
						$.each(selItem.provinceId,function(index,selData){
							if(selData==checkbox.attr('data-id')){
								checkbox.removeAttr('disabled');
								checkbox.prop('checked','true');
								return false;
							}								
						})
					}else{

						//禁用已经选择的不相关选项
						$.each(selItem.provinceId,function(index,selData){
							if(selData==checkbox.attr('data-id')){
								checkbox.attr('disabled','disabled');	
								checkbox.prop('checked','true');					
							}else if(!checkbox.is(':checked') && !checkbox.attr('disabled')){
								checkbox.removeAttr('disabled');
							}						
						})							
					}
		
				})
			})			
			
		});

		//删除配送地区设置
		delOption.click(function(){
			var curOptBody=$(this).closest('tbody');
			var curOptRow=$(this).closest('.optRow');
			var rowIdx=$(this).attr('data-rowidx');
			var idx=$(this).attr('data-idx');
			var selAreaBox=$(this).closest('.optWrap').find('.selAreaBox');
			//取消原来选择的地区选项
			$.each(selAreaBox.find('ul :checkbox:checked'),function(){
				var checkbox=$(this);
				if(checkbox.hasClass('pRoot')){
					return true;
				}
				$.each(wayData[idx][rowIdx].provinceId,function(index,selData){
					if(checkbox.attr('data-id')==selData){
						checkbox.prop('checked',false);
						checkbox.removeAttr('disabled');
						return false;
					}
				})				
			})
			//删除存在的配送运费设置数据
			wayData[idx].splice(rowIdx,1);
			//关闭地区选项设置
			selAreaBox.hide();
			//console.log(wayData);
			curOptRow.remove();
			//重新设定行索引值rowIdx
			$.each(curOptBody.find('.optRow'),function(index){
				$(this).find('.addArea').attr('data-rowidx',index);
			})
		});

		//添加一组配送设置
		addOption.click(function(){
			var wayIdx=$(this).attr('data-idx');
			var addCode=$(optCode.optRowCode).appendTo($(this).closest('.optWrap').find('tbody'));
			rowIdx=addCode.index();
			//console.log(rowIdx);
			//添加配置行号索引
			var curOptRow=element.find('.optRow:eq('+rowIdx+')');
			curOptRow.find('.addArea').attr({'data-rowIdx':rowIdx,'data-idx':wayIdx});
			curOptRow.find('.delOption').attr({'data-rowIdx':rowIdx,'data-idx':wayIdx});
			//绑定配置事件监听
			addAction(addCode,wayIdx);
		});
	}
	//重选择配送计费方式时的事件 
	$(':radio[name="post[logistics_type]"]').click(function(){
		optCode=getLogisticsCode($(this).val());
		//重新调整已经选中项的配置
		selWayItem=$this.find(':checkbox:checked');
		selWayItem.parent().next().replaceWith(optCode.logisticsCode);
		//删除原有配置数据
		$.each(selWayItem,function(){
			var idx=$(this).val();
			if(wayData[idx]){			
				$.each(wayData[idx],function(index){
					wayData[idx].splice(index,1);
				});
			}		
		})
		//console.log(wayData);
		//重新绑定配送设置事件监听
		addAction($this);
	})

	//配送方式选择事件监听
	$this.find(':checkbox').click(function(){
		var itemIdx=$(this).val();
		if($(this).is(':checked')){
			var checkItem=$(this).parent()
			//添加配送设置代码
			checkItem.after(optCode.logisticsCode);
			newItem=checkItem.next();
			//加入地区数据选择对话框代码
			var selAreaBox=$(provinceCode).appendTo(newItem);
			//选择对话框事件绑定
			addDialogEvent(selAreaBox);
			//绑定配送设置事件监听，这里传入配送方式的索引号，用于索引查询存储数据
			addAction(newItem,itemIdx);
			if(!wayData[itemIdx]){
				wayData[itemIdx]='快递运费数据';
				console.log(wayData);
			}
		//移除配送设置
		}else{
			$(this).parent().next().remove();
			//删除对应的模板数据
			$.each(wayData[itemIdx],function(index){
				wayData[itemIdx].splice(index,1);
			});
			//console.log(wayData);
		}
	})
}

//提交运费模板数据
$.fn.upLogisticsData=function(){
	var submitEnable=true;
	$(this).click(function(){
		var logisticsData={'0':[],'1':[],'2':[],'3':[]};
		var selWay=$('.optWrap');
		if(selWay.length==0){
			alert('至少选择一种配送方式！');
			submitEnable=false;
			return false;
		}

		$.each(selWay,function(){
			//如果已经存在非法数据则不再生成数据
			if(submitEnable==false){
				return false;
			}
			var optWrap=$(this);
			var idx=$(this).find('.addOption').attr('data-idx');
			var optRow=optWrap.find('.optRow');
			if(optRow.length==0){
				alert('存在未设置配送地区的数据行，请设置后再提交！');
				submitEnable=false;
				return false;
			}
			//遍历单行数据并生成Json代码
			$.each(optRow,function(rowIdx){
				var firstData,nextData;
				var firstPrice=$(this).find('.firstPrice').val();
				var nextPrice=$(this).find('.nextPrice').val();
				//根据计费方式生成对应的行数据
				switch($(':radio[name="post[logistics_type]"]:checked').val()){
					case '0':
						firstData=nextData=1;
					break;
					default:
						firstData=$(this).find('.first').val();
						nextData=$(this).find('.next').val();
					break;
				}
				//验证数据是否完整，如果有空值或者非法值，则提示校正
				if(isNaN(firstData)|isNaN(firstPrice)|isNaN(nextData)|isNaN(nextPrice)){
					alert('有添加的数据不是数字');
					submitEnable=false;
					return false;
				}
				//限制运费最大值
				if(firstPrice>999){
					firstPrice=999;
				}
				if(nextPrice>999){
					nextPrice=999;
				}
				console.log(wayData[idx].length);
				if(!wayData[idx][rowIdx]){
					alert('存在未设置配送地区的数据行，请设置后再提交！');
					submitEnable=false;
					return false;
				}
				//整合数据
				var rowData={'area':wayData[idx][rowIdx],'first':firstData,'firstPrice':firstPrice,'next':nextData,'nextPrice':nextPrice};
				logisticsData[idx].push(rowData);
			})
		});

		//判断是否可提交数据
		if(submitEnable==true){
			//将数据填入对应表单
			$('#logisticsData').val(JSON.stringify(logisticsData));
			//提交表单数据
			$.post(submitURL,$('#logisticsForm').serialize(),function(data){
				if(data.status==1){
					alert('提交成功！');

				}else{
					alert('提交失败');
				}
				console.log(data);
			})			
		}

		submitEnable=true;

		//console.log(logisticsData);
	})

	//JSON.stringify();
}

//编辑运费模板时解析加载运费模板数据
function parseLogistics(data){
	var logisticsData=$.parseJSON(data);
	//加载选择的配送方式
	$.each(logisticsData,function(idx){
		if(logisticsData[idx].length>0){
			var selItem=$('#logistics_way :checkbox[value="'+idx+'"]');
			selItem.trigger('click');
			//获取数据表对象
			var optWrap=selItem.parent().next();
			//加载行数据
			$.each(logisticsData[idx],function(rowIdx){
				var selAreaName='';
				//加载选择的地区项数据到wayData供编辑时调用
				wayData[idx].push(logisticsData[idx][rowIdx].area);				
				//重载选择的地区数据进wayData
				$.each(logisticsData[idx][rowIdx].area.provinceName,function(index,selData){
					selAreaName+=selData+',';
				})

				if(rowIdx>0){
					//添加数据行后对应索引填充行数据
					optWrap.find('.addOption').trigger('click');					
				}
				var addArea=optWrap.find('.addArea[data-rowidx="'+rowIdx+'"]');
				addArea.text(selAreaName);

				//填充计费价格数据
				var optRow=addArea.closest('.optRow');
				optRow.find('.firstPrice').val(logisticsData[idx][rowIdx].firstPrice);
				optRow.find('.nextPrice').val(logisticsData[idx][rowIdx].nextPrice);
				//根据计费方式生成对应的行数据
				switch($(':radio[name="post[logistics_type]"]:checked').val()){
					case '0':
						
					break;
					default:
						optRow.find('.first').val(logisticsData[idx][rowIdx].first);
						optRow.find('.next').val(logisticsData[idx][rowIdx].next);
					break;
				}
			})
		}
	})
}

//获取运费模板列表
$.fn.getLogisticsTmpl=function(selectTmplId){
	var selItem=$(this);
	var logisticsNum=selItem.find('option').length;
	//加载运费模板数据
	$.get(logisticsTmplURL,function(data,status){
		if(data.status==1){
			var optionStr='';
			$.each(data.data,function(index,Item){
				var selected='';
				if(selectTmplId && selectTmplId==Item.logistics_id){
					selected='selected';
				}
				optionStr+='<option value="'+Item.logistics_id+'" '+selected+'>'+Item.tmpl_name+'</option>'
			})
			if(data.data.length>0){
				selItem.html(optionStr);									
			}else{
				alert('您还未添加任何运费模板，请添加后再添加商品！');
			}
			//console.log(data);
		}else{
			alert('没有发现任何运费模板数据或者无法获取运费模板信息！');
		}
	})
}


getTermFilter=function (postURL,term_id,callFunc){
	$.post(postURL,{term_id:term_id},function(data){
		if(data.status==1){
			if(callFunc){
				callFunc(data.data);
				console.log(data.data);
			}
		}else{
			alert('无法获取筛选项数据，请稍候尝试！');
		}
	}).error(function(){
		alert('链接服务器出错，无法加载筛选项数据，请稍候尝试！')
	})
}

//后台专用加载等待动画
$.fn.setLoadingState=function(option,callFunc){
	var dft={
		container:'',						//loading加载显示的父容器
		duration:300,						//动画速率
		loadingTxt:'加载中，请等待！',		//加载时显示的文本
		icon:'',							//加载时的图标样式，暂时无用
		destroyAnimate:'opacity',			//加载完成时的动画，top为向上滑出，opacity为渐隐
		frameShow:true					//是否是在框架中显示
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
			destroy();
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
		if(Options.frameShow && Options.container=='body'){
			container=Options.container!==''?$(window.parent.document).find('body'):$this;
		}else{
			container=Options.container!==''?$(Options.container):$this;
		}
		var data_target=Options.container;
		$this.attr('data-loading',data_target);
		var parentWidth=Options.container=='body'?'100%':container.outerWidth()+'px';
		var parentHeight=Options.container=='body'?'100%':container.outerHeight()+'px';
		var cssPosition=Options.container=='body'?'fixed':'absolute';
		var marginTop=Options.container=='body'?$(window).height()/2-28:container.outerHeight()/2-28;
		var top=0-parseInt(container.css('border-top-width'));
		var left=0-parseInt(container.css('border-left-width'));

		if(Options.container=='body'){
			if(Options.frameShow==true){
				container.css('overflow','hidden');
			}else{
				$('body').css('overflow','hidden');
			}
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

	function destroy(){
		var target=$this.attr('data-loading');
		if(target=='body'){
			target=$(window.parent.document).find('body');
		}
		container=target!==''?target:$this;
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
			container.removeAttr('style');
			if(callFunc){
				callFunc();
			}

		});	
	}
}

//模态对话框类，建立在bootstrap的模态对话框基础上
messagesBox=function(option){
	var dft={
		title:'提示',
		content:'',
		type:'confirm',
		frameShow:true,		//是否是在框架中显示
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

      var container=Options.frameShow?$(window.parent.document).find('body'):$('body');

	switch(Options.type){
		case 'confirm':
		msgBox=$(msgBoxHtml+confirmCode).appendTo(container);
		msgBox.modal();
		break;
		default:
		msgBox=$(msgBoxHtml+normalCode).appendTo(container);
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

//checkbox全选/取消
$.fn.setAllCheckbox=function(option){
	var $this=$(this);
	var dft={
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

		checkItem.prop('checked',$(this).prop('checked'));
		checkAllinput.prop('checked',$(this).prop('checked'));
		Options.callFunc.apply($(this));
	});
}

//按数组对象中的某个键值(需为数字)进行插入排序
function insertSort(sortObj,key,saveOldKey,orderby){
	var temp;
	$.each(sortObj,function(index,Item){
		temp=sortObj[index+1];
		if(temp){
			temp.oldKey=index+1;
			for(var i=index;i>=0;i--){
				//console.log(sortObj[i]);
				if(orderby=='asc' || !orderby){
					if(parseInt(temp[key])<parseInt(sortObj[i][key])){
						sortObj[i+1]=sortObj[i];
						if(saveOldKey==true){
							if(sortObj[i+1].oldKey==undefined){
								sortObj[i+1].oldKey=i;
							}
						}
					}else{
						break;
					}
				}else if(orderby=='desc'){
					if(parseInt(temp[key])>parseInt(sortObj[i][key])){
						sortObj[i+1]=sortObj[i];
						if(saveOldKey==true){
							if(sortObj[i+1].oldKey==undefined){
								sortObj[i+1].oldKey=i;
							}
						}
					}else{						
						break;
					}
				}
			}
			if((i+1)!==index+1){
				sortObj[i+1]=temp;
			}else{
				if(saveOldKey==true){
					if(sortObj[i].oldKey==undefined){
						sortObj[i].oldKey=i;
					}
					if(sortObj[i+1].oldKey==undefined){
						sortObj[i+1].oldKey=i+1;
					}
				}
			}
		}
	})
	return sortObj;
}


