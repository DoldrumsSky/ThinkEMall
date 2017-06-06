// 文件上传

$(function() {

        // 优化retina, 在retina下这个值是2
        ratio = window.devicePixelRatio || 1;


        // Web Uploader实例
        var uploader;

        $.fn.setUploader=function($list,options){

            var curSelFileNum=0;
            $list = $list==null?this.find('#fileList'):this.find($list);

            var dft={
                //上传图片所属类型（商品图片和普通的图片上传显示界面不一样）
                uploadImgType:'goods',
                //
                postName:'',
                //显示的图片信息
                infoText:'',
                // 自动上传。
                auto: true,
                // swf文件路径
                swf: BASE_URL + '/js/webuploader/Uploader.swf',

                // 文件接收服务端。
                server: UPLOAD_SERVER,

                method:'POST',

                // 选择文件的按钮。可选。
                // 内部根据当前运行是创建，可能是input元素，也可能是flash.
                pick: {id:'#cfilePicker',multiple:false},                        

                thumb:{width:102,height:36,allowMagnify: false,type: 'image/png'},
                fileSingleSizeLimit: 1 * 1024 * 1024,

                // 只允许选择文件，可选。
                accept: {
                    title: 'Images',
                    extensions: 'gif,jpg,jpeg,bmp,png',
                    mimeTypes: 'image/*'
                },
                fileNumLimit:500,
                duplicate:true
            };

            var opt=$.extend(true,dft,options);
            // 缩略图大小
            var thumbnailWidth = opt.thumb.width * ratio,
            thumbnailHeight = opt.thumb.height * ratio;
            // 初始化Web Uploader
            uploader = WebUploader.create(opt);
            var wrapper=$list.parent();

            var $this=$(this);
            //此缩略图链接用于设置拥有相同外观样式（如:颜色）属性的SKU单品项相同图片时使用
            var thumbSrc='';
            var thumbImgHtml=opt.uploadImgType=='goods'?'':'<img>';
            var $img;

            // 当有文件添加进来的时候
            uploader.on( 'fileQueued', function( file ) {
                var infoText,fileInfo=opt.infoText!==''?'<div class="info">' +opt.infoText+ '</div></div>':opt.infoText+'</div>';

                var $li = $(
                        '<div id="' + file.id + '" class="file-item">'+thumbImgHtml+   
                        infoText
                        );
                    $img = opt.uploadImgType=='goods'?null:$li.find('img');

                    if(this.options.pick.multiple==true){
                        curSelFileNum++;
                            $list.append( $li );

                     }else{
                        $list.html( $li );
                     }

                showError = function( code ) {
                    switch( code ) {
                        case 'exceed_size':
                            text = '文件大小超出';
                            break;

                        case 'interrupt':
                            text = '上传暂停';
                            break;

                        default:
                            text = code;
                            break;
                    }

                };

                if ( file.getStatus() === 'invalid' ) {
                    showError( file.statusText );
                } 

                // 创建缩略图
                uploader.makeThumb( file, function( error, src ) {
                    if ( error ) {
                        $img.replaceWith('<span>不能预览</span>');
                        return;
                    }
                    thumbSrc=src;
                    //如果存在错误，移除错误标识
                    wrapper.find('.error').remove();
                    if(opt.uploadImgType=='goods'){
                        wrapper.find('.webuploader-pick').css('background','url('+src+') no-repeat center center');
                    }else{
                        $img.attr( 'src', src );
                    }
                }, thumbnailWidth, thumbnailHeight );

                $list.parent().hover(function(){
                    $(this).find(".file-panel").animate({height:'30px'}).show();
                },function(){
                    $(this).find(".file-panel").animate({height:'0px'},function(){});
                });

                //监听图片操作事件
                wrapper.on( 'click', 'span', function() {
                var index = $(this).index(),
                    deg;

                switch ( index ) {
                    //删除
                    case 0:
                        if(opt.uploadImgType=='goods'){
                            wrapper.find('.webuploader-pick').css('background','url('
                                +BASE_URL+'/Emall/images/image.png) no-repeat center center');
                            wrapper.find(".file-panel").remove();
                            wrapper.find('.successIco').remove();
                        }else{
                            $li.remove();
                        }
                        return;
                    //顺转90度
                    case 1:
                        file.rotation += 90;
                        break;
                    //逆转90度
                    case 2:
                        file.rotation -= 90;
                        break;
                    }
                });

            file.on('statuschange', function( cur, prev ) {
                if ( prev === 'progress' ) {
                    //$prgress.hide().width(0);
                } else if ( prev === 'queued' ) {
                    $li.off( 'mouseenter mouseleave' );
                    //$btns.remove();
                }

                // 成功
                if ( cur === 'error' || cur === 'invalid' ) {
                    //console.log( file.statusText );
                    showError( file.statusText );
                    //percentages[ file.id ][ 1 ] = 1;
                } else if ( cur === 'interrupt' ) {
                    showError( 'interrupt' );
                } else if ( cur === 'queued' ) {
                    //percentages[ file.id ][ 1 ] = 0;
                } else if ( cur === 'progress' ) {
                    //$info.remove();
                    //$prgress.css('display', 'block');
                } else if ( cur === 'complete' ) {
                    $li.append( '<span class="success"></span>' );
                }

                $li.removeClass( 'state-' + prev ).addClass( 'state-' + cur );
            });

            });



            // 文件上传过程中创建进度条实时显示。
            uploader.on( 'uploadProgress', function( file, percentage ) {
                var $li = $('#'+file.id ),
                    $percent = wrapper.find('.progress span');

                // 避免重复创建
                if ( !$percent.length ) {
                    $percent = $('<div class="progress"><span></span></div>')
                            .prependTo(wrapper.find(opt.pick.id) )
                            .find('span');
                }

                $percent.text( percentage * 100 + '%' );
            });

            // 文件上传成功，给item添加成功class, 用样式标记上传成功。
            uploader.on( 'uploadSuccess', function( file ,response) {
                var $li = $('#'+file.id );
                var pathStr= '';
                var picker= wrapper.find(opt.pick.id);


                if(response.status==1){
                    if(opt.pick.multiple==true){
                        if(opt.postName==''){
                            pathStr='<input type="hidden" name="UpFilePathInfo[]" value="';
                        }else{
                            pathStr='<input type="hidden" name="UpFilePathInfo['+opt.postName+'][]" value="';
                        }
                    }else{
                         if(opt.postName==''){
                            pathStr='<input type="hidden" name="UpFilePathInfo" value="';
                        }else{
                            pathStr='<input type="hidden" name="UpFilePathInfo['+opt.postName+'][]" value="';
                        }                       
                    }
                    //将返回的图片路径写入文本框中
                    $list.append( pathStr+ response.saveURL+'" >');
                    //如果是编辑商品页面，需要根据上传的图片归属类别将图片状态设置为待更新状态
                    if($this.hasClass('SKU_Uploader_Wrap')){
                        $this.find('input[name="updateTag[SKU_Pic][]"]').val('update');
                        //判断是否是上传单品项图片，第一次上传时会自动设置拥有相当属性的单品项为一样的图片                        
                        /*var dataTag=$this.data('tag');
                        if(!$this.hasClass('sameLock')){
                            $.each($this.parent().find('div[data-tag="'+dataTag+'"]'),function(){
                                $(this).find('.uploader-list').append(pathStr+ response.saveURL+'" >');
                                $(this).find('.webuploader-pick').css('background','url('+thumbSrc+') no-repeat center center');
                                //相同单品项属性的图片只自动设置一次，之后加上样式锁定，此时如果再修改单品项图片则不会影响其它相同属性单品项的图片
                                $(this).addClass('sameLock');
                            });
                        }else{
                            $list.append( pathStr+ response.saveURL+'" >');
                        }*/
                        
                    }else{
                        $this.find('input[name="updateTag[goodsPic][]"]').val('update');
                    }

                    var thumbContainer;
                   if(opt.uploadImgType=='goods'){
                        thumbContainer=picker;
                        $li.remove();  
                   }else{
                        thumbContainer=$li;
                        $li.addClass('thumbnail');
                        $li.find('.successIco').show();          
                   }
                   thumbContainer.prepend('<div class="file-panel">'
                    +'<span class="cancel">删除</span><span class="rotateRight">向右旋转</span><span class="rotateLeft">向左旋转</span></div></div>');
                   thumbContainer.prepend('<div class="successIco"></div>');
                }else if(response.status==0 || !response.success){
                    thumbContainer.find('.successIco').remove();
                    $error = thumbContainer.find('div.error');

                // 避免重复创建
                if ( !$error.length ) {
                    $error = $('<div class="error">上传失败</div>').appendTo( thumbContainer );
                    $error.text(response.errStr);

                }
                    
                   //alert('文件上传失败：'+response.errStr);
                }

                
            });

            // 文件上传失败，现实上传出错。
            uploader.on( 'uploadError', function( file,reason ) {
                var $li = wrapper.find(opt.pick.id),
                    $error = $li.find('div.error');

                // 避免重复创建
                if ( !$error.length ) {
                    $error = $('<div class="error"></div>').appendTo( $li );
                    $error.click(function(){
                         uploader.retry();
                    })
                }

                $error.text('上传失败,点此重传');
                alert('上传失败原因: ' + reason );
            });

            // 完成上传完了，成功或者失败，先删除进度条。
            uploader.on( 'uploadComplete', function( file ) {
                wrapper.find('.progress').remove();
            });

            uploader.onError = function( code ) {
                text='';
                switch(code){
                    case('F_EXCEED_NUM'):
                    text='超过发送文件数限制！'
                    break;
                    case('F_EXCEED_SIZE'):
                    text='超过发送文件大小限制！'
                    break;
                    case('F_TYPE_DENIED'):
                    text='上传文件类型错误！'
                    break;
                    default:code
                    break;
                }
                alert( '错误: ' + code );
            };

           uploader.onFileDequeued = function( file ) {
                curSelFileNum--;

                if ( curSelFileNum==0 ) {
                    $("#doUpload").hide();
                }

                removeFile( file );


            };

                    // 负责view的销毁
            function removeFile( file ) {
                var $li = $('#'+file.id);
                $li.off().find('.file-panel').off().end().remove();
                wrapper.find(opt.pick.id).find('.successIco').remove();
            }



        };//end setUploader

 
});
