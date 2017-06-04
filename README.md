# ThinkEMall 1.0 Beta
本项目是基于ThinkCMF X2.2.3内容管理框架开发的电子商城管理系统，是本人从设计转学程序的实践也是实验项目，目前系统还只是个雏形，代码相对比较随意并且使用了大量的自我构思，所以不喜勿喷！
整个系统仿制了大部分京东和天猫的最基础核心的功能包括：商城导航、购物车、订单、商品分类发布展示、商品筛选、品牌管理、收藏商品、收货地址管理、运费模板、广告、售后功能和支付做得比较随意……部分功能尚未开发实现：搜索功能，取消订单等、某些链接可能是空链接，开发的页面未进行seo优化，但基本不影响使用。文档教程暂时没空写，有问题可以进群交流或者联系我。
<h4>我的联系方式：</h4>
<p>QQ：451343282<p>
技术交流群：1950562
<h4>使用安装</h4>
<p>与ThinkCMF的使用安装方法相同，详细请访问 <a href="www.thinkcmf.com">www.thinkcmf.com</a></p>
<p>商城列表页只有电脑/笔记本类目下有商品数据</p>
<p>为了方便研究，系统一些console log的信息未关闭，使用时请自行查找注释掉这些log代码</p>
<h4>环境推荐</h4>
<blockquote>
<p>php5.5+</p>
</blockquote>
<blockquote>
<p>mysql 5.6+</p>
</blockquote>
<blockquote>
<p>打开rewrite</p>
</blockquote>
<h4>附上修改过的ThinkCMF文件及对应代码文件的基本目录</h4>
<p>商城系统应用目录</p>
<p>application\EMall</p>
<p>后台模板文件目录</p>
<p>admin\themes\simplebootx\EMall</p>
<p>修改过的原后台模板文件</p>
<p>admin\themes\simplebootx\Admin\Main\index.html</p>
<p>admin\themes\simplebootx\Admin\ header.html</p>
<p>admin\themes\simplebootx\Admin\Nav\add.html</p>
<p>admin\themes\simplebootx\Admin\Nav\edit.html</p>
<p>修改过的后台样式文件</p>
<p>public\simpleboot\css\simplebootadmin.css</p>
<p>修改过的后台控制器</p>
<p>application\User\Controller\IndexController.class.php</p>
<p>application\User\Controller\LoginController.class.php</p>
<p>application\User\Controller\ProfileController.class.php</p>
<p>application\User\Controller\RegisterController.class.php</p>
<p>application\Common\Controller\HomebaseController.class.php</p>
<p>application\Common\Controller\MemberbaseController.class.php</p>
<p>application\Admin\Controller\MainController.class.php</p>
<p>修改过的语言文件</p>
<p>application\Admin\Lang\zh-cn\menu.php</p>
<p>application\Admin\Lang\zh-cn\nav.php</p>

<p>Jquery替换到了1.91以上</p>
<p>public\js\jquery.js</p>
<p>后台改动的前端脚本（使用on替换掉了Live）</p>
<p>admin\themes\simplebootx\Public\assets\js\index.js</p>

<p>配置文件</p>
<p>application\Common\Conf\config.php</p>
<p>公共函数库（里面加入了一些其它自定义函数）</p>
<p>application\Common\Common\function.php</p>
<p>目录树处理类（加入了自定义的导航菜单树处理方法）</p>
<p>simplewind\Lib\Util\ Tree.class.php</p>
<p>筛选项模型</p>
<p>application\Common\Model \ SearcherModel.class .php</p>
<p>session会话处理类</p>
<p>simplewind\Core\Library\Think\Session\Driver\Mysqli.class.php</p>
<p>第三方类库</p>
<p>支付宝类库</p>
<p>simplewind\Core\Library\Vendor \ PHPAlipay </p>
<p>微信支付类库</p>
<p>simplewind\Core\Library\Vendor \PHPWeiXinPay </p>
<p>中文字符串转英文处理类库</p>
<p>simplewind\Core\Library\Vendor \ PHPCharacter</p>
<p>二维码类库</p>
<p>simplewind\Core\Library\Vendor \ PHPqrcode</p>
