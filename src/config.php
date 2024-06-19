<?php

return [
    // 模板引擎左边标记
    'left_delimiter' => '<{',
    // 模板引擎右边标记
    'right_delimiter' => '}>',
    // 空格策略
    'auto_literal' => false,
    // 开启缓存
    'caching' => false,
    // 缓存周期(开启缓存生效) 单位:秒
    'cache_lifetime' => 120,
    // Smarty工作空间目录名称(该目录用于存放模板目录、插件目录、配置目录)
    'workspace_dir_name' => 'view',
    // 模板目录名
    'template_dir_name' => 'templates',
    // 插件目录名
    'plugins_dir_name' => 'plugins',
    // 配置目录名
    'config_dir_name' => 'configs',
    // 模板编译目录名
    'compile_dir_name' => 'templates_compile',
    // 模板缓存目录名
    'cache_dir_name' => 'templates_cache',
    // 全局替换
    'tpl_replace_string'  =>  []
];