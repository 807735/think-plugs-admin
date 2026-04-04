<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace app\admin;

use app\admin\command\Msg;
use app\admin\service\Notify;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\Plugin;
use think\admin\service\OpenService;
use think\exception\HttpResponseException;
use think\Request;

/**
 * 插件服务注册.
 * @class Service
 */
class Service extends Plugin
{
    /**
     * 定义插件名称.
     * @var string
     */
    protected $appName = '系统管理';

    /**
     * 定义安装包名.
     * @var string
     */
    protected $package = 'baolong/think-plugs-admin';

    /**
     * 插件服务注册.
     */
    public function register(): void
    {
        $this->commands([Msg::class]);

        // 注册中间件异步通知路由
        $this->app->route->any('/openapi-notify/:vars', function (Request $request){
            try {
                $site_id = json_decode(CodeExtend::deSafe64($request->param('vars')), true)['site_id']??false;
                if ($site_id === false)  throw new Exception('URL参数错误');
                sysvar('api_site_id',$site_id);
                $this->notify = OpenService::OpenNotify();
                [$AppCode,$EventType,$EventData ] = $this->notify->checkSignature();
                if ($EventType == 'check_url') $this->notify->success('效验通过');
                Notify::mk($this->notify,$AppCode, $EventType,$EventData);
            } catch (Exception|\OpenClient\Contracts\Exception $exception){
               throw new HttpResponseException(json(['code' => 0, 'info' => $exception->getMessage(), 'data' => []]));
            }
        });
    }

    /**
     * 定义插件中心菜单.
     */
    public static function menu(): array
    {
        return [
            [
                'name' => '系统配置',
                'subs' => [
                    ['name' => '系统参数配置', 'icon' => 'layui-icon layui-icon-set', 'node' => 'admin/config/index'],
                    ['name' => '系统任务管理', 'icon' => 'layui-icon layui-icon-log', 'node' => 'admin/queue/index'],
                    ['name' => '系统日志管理', 'icon' => 'layui-icon layui-icon-form', 'node' => 'admin/oplog/index'],
                    ['name' => '数据字典管理', 'icon' => 'layui-icon layui-icon-code-circle', 'node' => 'admin/base/index'],
                    ['name' => '系统文件管理', 'icon' => 'layui-icon layui-icon-carousel', 'node' => 'admin/file/index'],
                    ['name' => '系统菜单管理', 'icon' => 'layui-icon layui-icon-layouts', 'node' => 'admin/menu/index'],
                ],
            ],
            [
                'name' => '权限管理',
                'subs' => [
                    ['name' => '系统权限管理', 'icon' => 'layui-icon layui-icon-vercode', 'node' => 'admin/auth/index'],
                    ['name' => '系统用户管理', 'icon' => 'layui-icon layui-icon-username', 'node' => 'admin/user/index'],
                ],
            ],
        ];
    }
}
