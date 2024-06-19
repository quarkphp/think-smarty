<?php

namespace quarkphp\smarty\facade;

use think\Facade;


class Smarty extends Facade
{

    protected static function getFacadeClass()
    {
        return 'smarty';
    }
}
