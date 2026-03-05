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

namespace app\admin\controller\api;


use app\manage\service\Notify;
use app\manage\service\OpenService;
use think\admin\Controller;
use think\admin\Exception;

/**
 * 手机短信管理
 * @class Message
 * @package app\manage\controller
 */
class OpenApi extends Controller
{
    /**
     * @return void
     */
    public function index(): void
    {
        try {
            $this->notify = OpenService::OpenNotify();
            [$AppCode,$EventType,$EventData ] = $this->notify->checkSignature();
            if ($EventType == 'check_url') $this->notify->success('效验通过');
            Notify::mk($this->notify,$AppCode, $EventType,$EventData);
        } catch (Exception|\OpenClient\Contracts\Exception $exception){
            $this->notify->error($exception->getMessage());
        }
    }

}