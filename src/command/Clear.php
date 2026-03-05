<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\admin\command;


use think\admin\Command;
use think\admin\model\SystemOpenApi;
use think\console\Input;
use think\console\Output;

/**
 * 自动清理
 * @class Clear
 * @package app\data\command
 */
class Clear extends Command
{
    /**
     * 指令参数配置
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('openClient:clean');
        $this->setDescription('清理订单数据');
    }

    /**
     * 业务指令执行
     * @param Input $input
     * @param Output $output
     * @return void
     * @throws \think\admin\Exception
     */
    protected function execute(Input $input, Output $output): void
    {
        $time = time() - intval(7 * 86400);
        $where = [['status', 'in', [1]], ['create_time', '<', date('Y-m-d H:i:s', $time)]];
        $clean = SystemOpenApi::mk()->where($where)->delete();
        $this->setQueueSuccess("清理 {$clean} 条历史记录");
    }
}