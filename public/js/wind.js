 /*!
 * HeadJS     The only script in your <HEAD>    
 * Author     Tero Piirainen  (tipiirai)
 * Maintainer Robert Hoffmann (itechnology)
 * License    MIT / http://bit.ly/mit-license
 *
 * Version 0.99
 * http://headjs.com
 */
 /* modify : head ==> Wind */
; (function (win, undefined) {
    "use strict";

    var doc = win.document,
        domWaiters = [],
        queue      = [], // waiters for the "head ready" event
        handlers   = {}, // user functions waiting for events
        assets     = {}, // loadable items in various states
        isAsync    = "async" in doc.createElement("script") || "MozAppearance" in doc.documentElement.style || win.opera,
        isHeadReady,
        isDomReady,

        /*** public API ***/
        headVar = win.head_conf && win.head_conf.head || "Wind",
        api     = win[headVar] = (win[headVar] || function () { api.ready.apply(null, arguments); }),

        // states
        PRELOADING = 1,
        PRELOADED  = 2,
        LOADING    = 3,
        LOADED     = 4;

    // Method 1: simply load and let browser take care of ordering
    if (isAsync) {
        api.load = function () {
            ///<summary>
            /// INFO: use cases
            ///    head.load("http://domain.com/file.js","http://domain.com/file.js", callBack)
            ///    head.load({ label1: "http://domain.com/file.js" }, { label2: "http://domain.com/file.js" }, callBack)
            ///</summary> 
            var args      = arguments,
                 callback = args[args.length - 1],
                 items    = {};

            if (!isFunction(callback)) {
                callback = null;
            }

            each(args, function (item, i) {
                if (item !== callback) {
                    item             = getAsset(item);
                    items[item.name] = item;

                    load(item, callback && i === args.length - 2 ? function () {
                        if (allLoaded(items)) {
                            one(callback);
                        }

                    } : null);
                }
            });

            return api;
        };


    // Method 2: preload with text/cache hack
    } else {
        api.load = function () {
            var args = arguments,
                rest = [].slice.call(args, 1),
                next = rest[0];

            // wait for a while. immediate execution causes some browsers to ignore caching
            if (!isHeadReady) {
                queue.push(function () {
                    api.load.apply(null, args);
                });

                return api;
            }            

            // multiple arguments
            if (!!next) {
                /* Preload with text/cache hack (not good!)
                 * http://blog.getify.com/on-script-loaders/
                 * http://www.nczonline.net/blog/2010/12/21/thoughts-on-script-loaders/
                 * If caching is not configured correctly on the server, then items could load twice !
                 *************************************************************************************/
                each(rest, function (item) {
                    if (!isFunction(item)) {
                        preLoad(getAsset(item));
                    }
                });

                // execute
                load(getAsset(args[0]), isFunction(next) ? next : function () {
                    api.load.apply(null, rest);
                });                
            }
            else {
                // single item
                load(getAsset(args[0]));
            }

            return api;
        };
    }

    // INFO: for retro compatibility
    api.js = api.load;
    
    api.test = function (test, success, failure, callback) {
        ///<summary>
        /// INFO: use cases:
        ///    head.test(condition, null       , "file.NOk" , callback);
        ///    head.test(condition, "fileOk.js", null       , callback);        
        ///    head.test(condition, "fileOk.js", "file.NOk" , callback);
        ///    head.test(condition, "fileOk.js", ["file.NOk", "file.NOk"], callback);
        ///    head.test({
        ///               test    : condition,
        ///               success : [{ label1: "file1Ok.js"  }, { label2: "file2Ok.js" }],
        ///               failure : [{ label1: "file1NOk.js" }, { label2: "file2NOk.js" }],
        ///               callback: callback
        ///    );  
        ///    head.test({
        ///               test    : condition,
        ///               success : ["file1Ok.js" , "file2Ok.js"],
        ///               failure : ["file1NOk.js", "file2NOk.js"],
        ///               callback: callback
        ///    );         
        ///</summary>    
        var obj = (typeof test === 'object') ? test : {
            test: test,
            success: !!success ? isArray(success) ? success : [success] : false,
            failure: !!failure ? isArray(failure) ? failure : [failure] : false,
            callback: callback || noop
        };

        // Test Passed ?
        var passed = !!obj.test;

        // Do we have a success case
        if (passed && !!obj.success) {
            obj.success.push(obj.callback);
            api.load.apply(null, obj.success);
        }
            // Do we have a fail case
        else if (!passed && !!obj.failure) {
            obj.failure.push(obj.callback);
            api.load.apply(null, obj.failure);
        }
        else {
            callback();
        }

        return api;
    };

    api.ready = function (key, callback) {
        ///<summary>
        /// INFO: use cases:
        ///    head.ready(callBack)
        ///    head.ready(document , callBack)
        ///    head.ready("file.js", callBack);
        ///    head.ready("label"  , callBack);        
        ///</summary>

        // DOM ready check: head.ready(document, function() { });
        if (key === doc) {
            if (isDomReady) {
                one(callback);
            }
            else {
                domWaiters.push(callback);
            }

            return api;
        }

        // shift arguments
        if (isFunction(key)) {
            callback = key;
            key      = "ALL";
        }

        // make sure arguments are sane
        if (typeof key !== 'string' || !isFunction(callback)) {
            return api;
        }

        // This can also be called when we trigger events based on filenames & labels
        var asset = assets[key];

        // item already loaded --> execute and return
        if (asset && asset.state === LOADED || key === 'ALL' && allLoaded() && isDomReady) {
            one(callback);
            return api;
        }

        var arr = handlers[key];
        if (!arr) {
            arr = handlers[key] = [callback];
        }
        else {
            arr.push(callback);
        }

        return api;
    };


    // perform this when DOM is ready
    api.ready(doc, function () {

        if (allLoaded()) {
            each(handlers.ALL, function (callback) {
                one(callback);
            });
        }

        if (api.feature) {
            api.feature("domloaded", true);
        }
    });


    /* private functions
    *********************/
    function noop() {
        // does nothing
    }

    function each(arr, callback) {
        if (!arr) {
            return;
        }

        // arguments special type
        if (typeof arr === 'object') {
            arr = [].slice.call(arr);
        }

        // do the job
        for (var i = 0, l = arr.length; i < l; i++) {
            callback.call(arr, arr[i], i);
        }
    }

    /* A must read: http://bonsaiden.github.com/JavaScript-Garden
     ************************************************************/
    function is(type, obj) {
        var clas = Object.prototype.toString.call(obj).slice(8, -1);
        return obj !== undefined && obj !== null && clas === type;
    }

    function isFunction(item) {
        return is("Function", item);
    }

    function isArray(item) {
        return is("Array", item);
    }

    function toLabel(url) {
        ///<summary>Converts a url to a file label</summary>
        var items = url.split("/"),
             name = items[items.length - 1],
             i    = name.indexOf("?");

        return i !== -1 ? name.substring(0, i) : name;
    }

    // INFO: this look like a "im triggering callbacks all over the place, but only wanna run it one time function" ..should try to make everything work without it if possible
    // INFO: Even better. Look into promises/defered's like jQuery is doing
    function one(callback) {
        ///<summary>Execute a callback only once</summary>
        callback = callback || noop;

        if (callback._done) {
            return;
        }

        callback();
        callback._done = 1;
    }

    function getAsset(item) {
        ///<summary>
        /// Assets are in the form of
        /// { 
        ///     name : label,
        ///     url  : url,
        ///     state: state
        /// }
        ///</summary>
        var asset = {};

        if (typeof item === 'object') {
            for (var label in item) {
                if (!!item[label]) {
                    asset = {
                        name: label,
                        url : item[label]
                    };
                }
            }
        }
        else {
            asset = {
                name: toLabel(item),
                url : item
            };
        }

        // is the item already existant
        var existing = assets[asset.name];
        if (existing && existing.url === asset.url) {
            return existing;
        }

        assets[asset.name] = asset;
        return asset;
    }

    function allLoaded(items) {
        items = items || assets;

        for (var name in items) {
            if (items.hasOwnProperty(name) && items[name].state !== LOADED) {
                return false;
            }
        }
        
        return true;
    }


    function onPreload(asset) {
        asset.state = PRELOADED;

        each(asset.onpreload, function (afterPreload) {
            afterPreload.call();
        });
    }

    function preLoad(asset, callback) {
        if (asset.state === undefined) {

            asset.state     = PRELOADING;
            asset.onpreload = [];

            loadAsset({ url: asset.url, type: 'cache' }, function () {
                onPreload(asset);
            });
        }
    }

    function load(asset, callback) {
        ///<summary>Used with normal loading logic</summary>
        callback = callback || noop;

        if (asset.state === LOADED) {
            callback();
            return;
        }

        // INFO: why would we trigger a ready event when its not really loaded yet ?
        if (asset.state === LOADING) {
            api.ready(asset.name, callback);
            return;
        }

        if (asset.state === PRELOADING) {
            asset.onpreload.push(function () {
                load(asset, callback);
            });
            return;
        }

        asset.state = LOADING;
        
        loadAsset(asset, function () {
            asset.state = LOADED;
            callback();

            // handlers for this asset
            each(handlers[asset.name], function (fn) {
                one(fn);
            });

            // dom is ready & no assets are queued for loading
            // INFO: shouldn't we be doing the same test above ?
            if (isDomReady && allLoaded()) {
                each(handlers.ALL, function (fn) {
                    one(fn);
                });
            }
        });
    }

    /* Parts inspired from: https://github.com/cujojs/curl
    ******************************************************/
    function loadAsset(asset, callback) {
        callback = callback || noop;

        var ele;
        if (/\.css[^\.]*$/.test(asset.url)) {
            ele      = doc.createElement('link');
            ele.type = 'text/' + (asset.type || 'css');
            ele.rel  = 'stylesheet';
            ele.href = asset.url;
        }
        else {
            ele      = doc.createElement('script');
            ele.type = 'text/' + (asset.type || 'javascript');
            ele.src  = asset.url;
        }

        ele.onload  = ele.onreadystatechange = process;
        ele.onerror = error;

        /* Good read, but doesn't give much hope !
         * http://blog.getify.com/on-script-loaders/
         * http://www.nczonline.net/blog/2010/12/21/thoughts-on-script-loaders/
         * https://hacks.mozilla.org/2009/06/defer/
         */

        // ASYNC: load in parellel and execute as soon as possible
        ele.async = false;
        // DEFER: load in parallel but maintain execution order
        ele.defer = false;

        function error(event) {
            event = event || win.event;
            
            // need some more detailed error handling here

            // release event listeners
            ele.onload = ele.onreadystatechange = ele.onerror = null;
                        
            // do callback
            callback();
        }

        function process(event) {
            event = event || win.event;

            // IE 7/8 (2 events on 1st load)
            // 1) event.type = readystatechange, s.readyState = loading
            // 2) event.type = readystatechange, s.readyState = loaded

            // IE 7/8 (1 event on reload)
            // 1) event.type = readystatechange, s.readyState = complete 

            // event.type === 'readystatechange' && /loaded|complete/.test(s.readyState)

            // IE 9 (3 events on 1st load)
            // 1) event.type = readystatechange, s.readyState = loading
            // 2) event.type = readystatechange, s.readyState = loaded
            // 3) event.type = load            , s.readyState = loaded

            // IE 9 (2 events on reload)
            // 1) event.type = readystatechange, s.readyState = complete 
            // 2) event.type = load            , s.readyState = complete 

            // event.type === 'load'             && /loaded|complete/.test(s.readyState)
            // event.type === 'readystatechange' && /loaded|complete/.test(s.readyState)

            // IE 10 (3 events on 1st load)
            // 1) event.type = readystatechange, s.readyState = loading
            // 2) event.type = load            , s.readyState = complete
            // 3) event.type = readystatechange, s.readyState = loaded

            // IE 10 (3 events on reload)
            // 1) event.type = readystatechange, s.readyState = loaded
            // 2) event.type = load            , s.readyState = complete
            // 3) event.type = readystatechange, s.readyState = complete 

            // event.type === 'load'             && /loaded|complete/.test(s.readyState)
            // event.type === 'readystatechange' && /complete/.test(s.readyState)

            // Other Browsers (1 event on 1st load)
            // 1) event.type = load, s.readyState = undefined

            // Other Browsers (1 event on reload)
            // 1) event.type = load, s.readyState = undefined            

            // event.type == 'load' && s.readyState = undefined


            // !doc.documentMode is for IE6/7, IE8+ have documentMode
            if (event.type === 'load' || (/loaded|complete/.test(ele.readyState) && (!doc.documentMode || doc.documentMode < 9))) {
                // release event listeners               
                ele.onload = ele.onreadystatechange = ele.onerror = null;

                // do callback
                callback();
            }

            // emulates error on browsers that don't create an exception
            // INFO: timeout not clearing ..why ?
            //asset.timeout = win.setTimeout(function () {
            //    error({ type: "timeout" });
            //}, 3000);
        }

        // use insertBefore to keep IE from throwing Operation Aborted (thx Bryan Forbes!)
        var head = doc['head'] || doc.getElementsByTagName('head')[0];
        // but insert at end of head, because otherwise if it is a stylesheet, it will not ovverride values
        head.insertBefore(ele, head.lastChild);
    }

    /* Mix of stuff from jQuery & IEContentLoaded
     * http://dev.w3.org/html5/spec/the-end.html#the-end
     ***************************************************/
    function domReady() {
        // Make sure body exists, at least, in case IE gets a little overzealous (jQuery ticket #5443).
        if (!doc.body) {
            // let's not get nasty by setting a timeout too small.. (loop mania guaranteed if assets are queued)
            win.clearTimeout(api.readyTimeout);
            api.readyTimeout = win.setTimeout(domReady, 50);
            return;
        }

        if (!isDomReady) {
            isDomReady = true;
            each(domWaiters, function (fn) {
                one(fn);
            });
        }
    }

    function domContentLoaded() {
        // W3C
        if (doc.addEventListener) {
            doc.removeEventListener("DOMContentLoaded", domContentLoaded, false);
            domReady();
        }

        // IE
        else if (doc.readyState === "complete") {
            // we're here because readyState === "complete" in oldIE
            // which is good enough for us to call the dom ready!            
            doc.detachEvent("onreadystatechange", domContentLoaded);
            domReady();
        }
    };

    // Catch cases where ready() is called after the browser event has already occurred.
    // we once tried to use readyState "interactive" here, but it caused issues like the one
    // discovered by ChrisS here: http://bugs.jquery.com/ticket/12282#comment:15    
    if (doc.readyState === "complete") {
        domReady();
    }

    // W3C
    else if (doc.addEventListener) {
        doc.addEventListener("DOMContentLoaded", domContentLoaded, false);

        // A fallback to window.onload, that will always work
        win.addEventListener("load", domReady, false);
    }

    // IE
    else {
        // Ensure firing before onload, maybe late but safe also for iframes
        doc.attachEvent("onreadystatechange", domContentLoaded);

        // A fallback to window.onload, that will always work
        win.attachEvent("onload", domReady);

        // If IE and not a frame
        // continually check to see if the document is ready
        var top = false;

        try {
            top = win.frameElement == null && doc.documentElement;
        } catch (e) { }

        if (top && top.doScroll) {
            (function doScrollCheck() {
                if (!isDomReady) {
                    try {
                        // Use the trick by Diego Perini
                        // http://javascript.nwbox.com/IEContentLoaded/
                        top.doScroll("left");
                    } catch (error) {
                        // let's not get nasty by setting a timeout too small.. (loop mania guaranteed if assets are queued)
                        win.clearTimeout(api.readyTimeout);
                        api.readyTimeout = win.setTimeout(doScrollCheck, 50);
                        return;
                    }

                    // and execute any waiting functions
                    domReady();
                }
            })();
        }
    }

    /*
        We wait for 300 ms before asset loading starts. for some reason this is needed
        to make sure assets are cached. Not sure why this happens yet. A case study:

        https://github.com/headjs/headjs/issues/closed#issue/83
    */
    setTimeout(function () {
        isHeadReady = true;
        each(queue, function (fn) {
            fn();
        });

    }, 300);

    // browser type & version
    var ua = navigator.userAgent.toLowerCase();

    ua = /(webkit)[ \/]([\w.]+)/.exec( ua ) ||
        /(opera)(?:.*version)?[ \/]([\w.]+)/.exec( ua ) ||
        /(msie) ([\w.]+)/.exec( ua ) ||
        !/compatible/.test( ua ) && /(mozilla)(?:.*? rv:([\w.]+))?/.exec( ua ) || [];


    if (ua[1] == 'msie') {
        ua[1] = 'ie';
        ua[2] = document.documentMode || ua[2];
    }

    api.browser = { version: ua[2] };
    api.browser[ua[1]] = true;

    // IE specific
    if (api.browser.ie) {
        // HTML5 support
        each("abbr|article|aside|audio|canvas|details|figcaption|figure|footer|header|hgroup|mark|meter|nav|output|progress|section|summary|time|video".split("|"), function(el) {
            doc.createElement(el);
        });
    }

})(window);

/*********Wind JS*********/
/*
 * PHPWind JS core
 * @Copyright   : Copyright 2011, phpwind.com
 * @Descript    : PHPWind核心JS
 * @Author      : chaoren1641@gmail.com
 * @Thanks      : head.js (http://headjs.com)
 * $Id: wind.js 21971 2012-12-17 12:11:36Z hao.lin $            :
 */


/*
 * 防止浏览器不支持console报错
 */
if(!window.console) {
    window.console = {};
    var funs = ["profiles", "memory", "_commandLineAPI", "debug", "error", "info", "log", "warn", "dir", "dirxml", "trace", "assert", "count", "markTimeline", "profile", "profileEnd", "time", "timeEnd", "timeStamp", "group", "groupCollapsed", "groupEnd"];
    for(var i = 0;i < funs.length; i++) {
        console[funs[i]] = function() {};
    }
}

/*
*解决ie6下不支持背景缓存
*/
Wind.ready(function() {
	if (!+'\v1' && !('maxHeight' in document.body.style)) {
		try{
			document.execCommand("BackgroundImageCache", false, true);
		}catch(e){}
	}
});

/*
*wind core
*/
(function(win) {
	var root = win.GV.WEB_ROOT+win.GV.JS_ROOT || location.origin + '/public/js/', //在wind.js加载之前定义GV.JS_ROOT
		ver = '',
		//定义常用JS组件别名，使用别名加载
		alias = {
            datePicker         : 'datePicker/datePicker',
            jquery             : 'jquery',
            colorPicker        : 'colorPicker/colorPicker',
            tabs               : 'tabs/tabs',
            swfobject          : 'swfobject',
            imgready           : 'imgready',

            //jquery util plugs
            ajaxForm          : 'ajaxForm',
            cookie            : 'cookie',
			treeview          : 'treeview',
            treeTable         : 'treeTable/treeTable',
            draggable         : 'draggable',
            validate          : 'jquery.validate/jquery.validate',
            'validate-extends': 'jquery.validate/additional-methods',
            artDialog         : 'artDialog/artDialog',
            iframeTools       : 'artDialog/iframeTools',
            xd                : 'xd',//Iframe跨域通信

            noty			  : 'noty/noty',
            jcrop             : 'jcrop/js/jcrop',
            ajaxfileupload    : 'ajaxfileupload',

            layer             : 'layer/layer',
			plupload          : 'plupload/plupload.full.min',
			
			echarts           : 'echarts/echarts.min'
		},
        //CSS路径
		alias_css = {
            colorPicker : 'colorPicker/style',
            artDialog   : 'artDialog/skins/default',
			datePicker	: 'datePicker/style',
            treeTable   : 'treeTable/treeTable',
            jcrop       : 'jcrop/css/jquery.Jcrop.min',

            layer       : 'layer/skin/layer',
		};

	//add suffix and version
	for(var i in alias) {
		if (alias.hasOwnProperty(i)) {
			alias[i] = root + alias[i] +'.js?v=' + ver;
		}
	}

	for(var i in alias_css) {
		if (alias_css.hasOwnProperty(i)) {
			alias_css[i] = root + alias_css[i] +'.css?v=' + ver;
		}
	}

	//css loader
	win.Wind = win.Wind || {};
    //!TODO old webkit and old firefox does not support
	Wind.css = function(alias/*alias or path*/,callback) {
		var url = alias_css[alias] ? alias_css[alias] : alias
		var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = url;
        link.onload = link.onreadystatechange = function() {//chrome link无onload事件
            var state = link.readyState;
            if (callback && !callback.done && (!state || /loaded|complete/.test(state))) {
                callback.done = true;
                callback();
            }
        }
        document.getElementsByTagName('head')[0].appendChild(link);
	};
	
	/**
	 * 更新或者添加js别名,在 Wind.use()调用名使用
	 * @param newalias ,要设置的别名对象
	 */
	Wind.alias = function (newalias){
		for(var i in newalias) {
			alias[i] =newalias[i];
		}
	}
	
	/**
	 * 更新或者添加css别名,在 Wind.css()调用名使用
	 * @param newalias ,要设置的别名对象
	 */
	Wind.aliasCss = function (newalias){
		for(var i in newalias) {
			alias_css[i] =newalias[i];
		}
	}

	//Using the alias to load the script file
	Wind.use = function() {
		var args = arguments,len = args.length;
        for( var i = 0;i < len;i++ ) {
        	if(typeof args[i] === 'string' && alias[args[i]]) {
        		args[i] = alias[args[i]];
        	}
        }
		Wind.js.apply(null,args);
	};

    //Wind javascript template (author: John Resig http://ejohn.org/blog/javascript-micro-templating/)
    var cache = {};
    Wind.tmpl = function (str, data) {
        var fn = !/\W/.test(str) ? cache[str] = cache[str] || tmpl(str) :
        new Function("obj", "var p=[],print=function(){p.push.apply(p,arguments);};" +
        "with(obj){p.push('" +
        str.replace(/[\r\t\n]/g, " ").split("<%").join("\t").replace(/((^|%>)[^\t]*)'/g, "$1\r").replace(/\t=(.*?)%>/g, "',$1,'").split("\t").join("');").split("%>").join("p.push('").split("\r").join("\\'") + "');}return p.join('');");
        return data ? fn(data) : fn;
    };
    //Wind全局功能函数命名空间
    Wind.Util = {}
})(window);

