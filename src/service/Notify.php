<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-common
// | github 代码仓库：https://github.com/zoujingli/think-plugs-common
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\service;

use think\admin\Exception;

abstract class Notify
{
    public \OpenClient\Notify $notify  ;
    public string $AppCode = '';
    public string $EventType = '';
    public array $EventData = [];

    public  function __construct($notify,$AppCode,$EventType,$EventData){
        $this->notify = $notify;
        $this->AppCode = $AppCode;
        $this->EventType = $EventType;
        $this->EventData = $EventData;
    }

    /**
     * @param $notify
     * @param $AppCode
     * @param $EventType
     * @param $EventData
     * @return Notify
     * @throws Exception
     */
    public static function mk($notify,$AppCode,$EventType,$EventData):Notify
    {
        try {
            [$classname,$func] = self::params($EventType);
            return app($classname, [$notify,$AppCode,$EventType,$EventData])->$func();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $EventType
     * @return array
     * @throws Exception
     */
    public static function params($EventType): array
    {
        try {
            [$mod,$class,$func] = explode('.', $EventType . '..');
            $classname = "\\app\\admin\\service\\notify\\{$mod}\\" . ucfirst($class);
            if (!class_exists($classname)) throw new \Exception("{$classname} 不存在的类");
            if (!method_exists($classname, $func)) throw new \Exception("{$classname}->{$func} 不存在的方法");
            return [$classname,$func];
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

}