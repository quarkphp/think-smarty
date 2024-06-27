<?php
// +----------------------------------------------------------------------
// | QuarkPHP [ WE CAN REALIZE OUR DREAMS THROUGH QUARK ]
// | Copyright 2021-2099 http://quarkphp.cn CO.,LTD. All Rights Reserved.
// | File: Smarty.php
// | Author: Mr.Zhang <10689579@qq.com>
// | Created: 2024-06-27 08:42:57
// | Desc: smarty5.x
// +----------------------------------------------------------------------

namespace quarkphp\smarty;

use Smarty\Template;
use think\App;
use think\Exception;
use think\helper\Str;

class Smarty extends \Smarty\Smarty
{

    private $app;

    // 模板引擎参数
    protected $config = [
        // 开启缓存
        'caching' => false,
        // 缓存周期(开启缓存生效) 单位:秒
        'cache_lifetime' => 120,
        // 空格策略
        'auto_literal' => false,
        // 模板压缩
        'template_compress'  => true,
        // 模板引擎左边标记
        'left_delimiter' => '<{',
        // 模板引擎右边标记
        'right_delimiter' => '}>',
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
        'auto_rule'     => 1,
        // 模板后缀
        'view_suffix'   => 'tpl',
        // 模板文件名分隔符
        'view_depr'     => DIRECTORY_SEPARATOR,
        // Smarty工作空间目录名称(该目录用于存放模板目录、插件目录、配置目录)
        'workspace_dir_name' => 'view',
        // 模板目录名
        'template_dir_name' => 'templates',
        // 模板名称
        'template_name'     => 'default',
        // 配置目录名
        'config_dir_name' => 'configs',
        // 模板编译目录名
        'compile_dir_name' => 'templates_compile',
        // 模板缓存目录名
        'cache_dir_name' => 'templates_cache',
        // 全局替换
        'tpl_replace_string' => []

    ];

    public function __construct(App $app)
    {
        // 调用父类的构造方法
        parent::__construct();
        $this->app = $app;
        //配置合并
        $this->config = array_merge($this->config, Config('smarty'));
        //语言包
        $lang=Config('lang.default_lang');
        //系统配置模板名
        $template_name=(config('app.template_name')?config('app.template_name'):$this->config['template_name']);
        //缓存配置
        $this->caching = $this->config['caching'];
        //缓存周期
        $this->cache_lifetime = $this->config['cache_lifetime'];
        if (is_dir($this->app->getAppPath() . $this->config['workspace_dir_name'])) {//判断应用名下是否有模板目录
            $path = $this->app->getAppPath() . $this->config['workspace_dir_name'] . DIRECTORY_SEPARATOR;
        } else {
            $appName = $this->app->http->getName();
            $path = $this->app->getRootPath() . $this->config['workspace_dir_name'] . DIRECTORY_SEPARATOR;
        }
        $readpath=$template_name . DIRECTORY_SEPARATOR . $lang. DIRECTORY_SEPARATOR . ($appName ? $appName . DIRECTORY_SEPARATOR : '');
        //保存当前路径
        $this->config['templates_path'] = $path . $this->config['template_dir_name'] . DIRECTORY_SEPARATOR .$readpath;
        $this->config['configs_path'] = $path . $this->config['config_dir_name'];
        
        //缓存目录及编译目录加入多应用
        $cache_dir=$this->app->getRuntimePath(). $this->config['cache_dir_name']. DIRECTORY_SEPARATOR . $readpath ;
        $compile_dir=$this->app->getRuntimePath() .$this->config['compile_dir_name']. DIRECTORY_SEPARATOR . $readpath ;

        //空格策略配置
        $this->setAutoLiteral($this->config['auto_literal']);
        //左分隔符配置
        $this->setLeftDelimiter($this->config['left_delimiter']);
        //右分隔符配置
        $this->setRightDelimiter($this->config['right_delimiter']);
        //设置起始模板路径
        $this->setTemplateDir($this->config['templates_path']);
        //设置配置路径
        $this->setConfigDir($this->config['configs_path']);
        //是否缓存
        $this->setCaching($this->caching);
        //设置缓存时间
        $this->setCacheLifetime($this->cache_lifetime);
        //设置缓存路径
        $this->setCacheDir($cache_dir);
        //设置编译目录
        $this->setCompileDir($compile_dir);

        //think-smarty保留变量think,为了快速在模板中使用tp的方法
        $this->assign('think', $app);
        //模板html压缩
        if($this->config['template_compress']){
            $this->registerFilter("post", [Smarty::class, 'compress']);
        }
        //注册全局过滤器,实现类似thinkphp官方的
        $this->registerFilter("output", [Smarty::class, 'filter']);
        
    }

    public static function filter($tpl_output, Template $template)
    {
        return strtr($tpl_output, config('smarty.tpl_replace_string'));
    }

    public static function compress($tpl_output,Template $template){
        $search = array(
            '/\>[^\S ]+/s',     // 去掉标签后空格
            '/[^\S ]+\</s',     // 去掉标签前空格
            '/(\s)+/s',         // 去掉多个空格
            '/<!--(.|\s)*?-->/' // 去掉注释
        );
        $replace = array('>','<','\\1','');
        $output = preg_replace($search, $replace, $tpl_output);
        return $output;
    }

    /**
     * 手工清除缓存
     * @param  string $template 模板文件，为空清空全部
     */
    public function rmCache($template=null,$cache_id=null){
        if(!empty($template)){
            $this->clearCache($template,$cache_id);
        }else{
            $this->clearAllCache();
        }
    } 

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        return $this->fetch($template, $cache_id, $compile_id, $parent);
    }


    public function fetch($template = null, $cacheId = null, $compileId = null, $parent = null)
    {
    
        $template=(string)$template;
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) { //说明没有明确指定资源类型
            $template = $this->parseTemplate($template);
            
            //模板不存在 抛出异常
            if (!is_file($template)) {
                throw new Exception('Unable to load template ' . $template);
            }
        }

        try {
            // 调用父类的 fetch() 方法
            return parent::fetch($template, $cacheId, $compileId, $parent);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 检测是否存在模板文件
     * @access public
     * @param  string $template 模板文件或者模板规则
     * @return bool
     */
    public function exists(string $template): bool
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }

        return is_file($template);
    }


    /**
     * 解析模板文件
     * @access private
     * @param string $template 模板文件规则
     * @return string
     */
    private function parseTemplate(string $template): string
    {
        // 分析模板文件规则
        $request = $this->app['request'];
        $depr = $this->config['view_depr'];
        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($app, $template) = explode('@', $template);

        }

        if (isset($app)) {
            $viewPath = $this->app->getBasePath() . $app . DIRECTORY_SEPARATOR . $this->config['workspace_dir_name'] . DIRECTORY_SEPARATOR;

            if (is_dir($viewPath)) {
                $path = $viewPath;
            } else {
                $path = $this->app->getRootPath() . $this->config['workspace_dir_name'] . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR;
            }

            //这里必须把模板路径返回
            $path = $path . $this->config['template_dir_name'] . $depr;

        } else {
            $path = $this->config['templates_path'] . $depr;
        }
        

        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller = $request->controller();

            if (strpos($controller, '.')) {
                $pos        = strrpos($controller, '.');
                $controller = substr($controller, 0, $pos) . '.' . Str::snake(substr($controller, $pos + 1));
            } else {
                $controller = Str::snake($controller);
            }

            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认模板渲染规则定位
                    if (2 == $this->config['auto_rule']) {
                        $template = $request->action(true);
                    } elseif (3 == $this->config['auto_rule']) {
                        $template = $request->action();
                    } else {
                        $template = Str::snake($request->action());
                    }

                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DIRECTORY_SEPARATOR, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }

    /**
     * 配置模板引擎
     * @access private
     * @param  array  $config 参数
     * @return void
     */
    public function config(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取模板引擎配置
     * @access public
     * @param  string  $name 参数名
     * @return void
     */
    public function getConfig(string $name)
    {
        return $this->config[$name];
    }

}
