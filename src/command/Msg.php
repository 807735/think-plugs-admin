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
use think\admin\Exception;
use think\admin\model\SystemMsms;
use think\admin\service\Message;
use think\console\Input;
use think\console\Output;

/**
 * 自动清理
 * @class Clear
 * @package app\data\command
 */
class Msg extends Command
{
    /**
     * 指令参数配置
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('openClient:msg')->setDescription('短信计划任务处理');
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
        $where = [['status', '=', 1], ['plan_time', '<=', date('Y-m-d H:i:s')]];
        [$count, $error, $total] = [0, 0,($items = SystemMsms::mk()->where($where)->select())->count()];
        $items->map(function (SystemMsms $order) use ($total, &$count,&$error) {
            $this->queue->message($total, ++$count, "尝试发送短信 => {$order->getAttr('mobile')}");
            try {
                [$state,$info,$data] =  Message::sendSms($order->getAttr('mobile'),$order->getAttr('extra'),$order->getAttr('scene'));
                if (!$state) throw new Exception($info);
                $order->save([
                    'status' => 3,
                    'send_time' => date('Y-m-d H:i:s'),
                    'content' => $data['content']??'',
                    'send_remark' => $info
                ]);
                $this->queue->message($total, $count, "完成发送短信 => {$order->getAttr('mobile')}", 1);
            } catch (Exception $e){
                $error++;
                $order->save([
                    'status' => 0,
                    'send_remark' => $e->getMessage()
                ]);
                $this->queue->message($total, $count, "发送短信 => {$order->getAttr('mobile')} 失败, {$e->getMessage()}", 1);
            }
        });
        $this->setQueueSuccess("此次共发送 {$total} 条短信, 其中有 {$error} 条发送失败。");
    }
}