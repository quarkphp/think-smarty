<?php


use think\Response;


if (!function_exists('smarty')) {

    function smarty()
    {
        return app('smarty');
    }
}


if (!function_exists('assign')) {

    function assign($tpl_var, $value = null, $nocache = false)
    {
        return smarty()->assign($tpl_var, $value, $nocache);
    }
}


if (!function_exists('fetch')) {

    function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        return smarty()->fetch($template, $cache_id, $compile_id, $parent);
    }
}


if (!function_exists('display')) {

    function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        return smarty()->display($template, $cache_id, $compile_id, $parent);
    }
}



