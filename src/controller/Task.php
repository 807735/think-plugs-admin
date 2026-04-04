<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\admin\Controller;
use think\admin\model\SystemQueue;
use think\exception\HttpResponseException;

/**
 * 系统任务启动
 * @site admin
 */
class Task extends Controller
{

    const LIST = [
        ['title'=>'用户扣除服务费，发放关爱金，检查用户欠费状态','command' => 'data:member','loops' => 60],
        ['title'=>'清理订单数据','command' => 'data:order:clear','loops' => 60],
        ['title'=>'商城订单顾问延迟返佣','command' => 'xdata:mall:rebate','loops' => 7200],
        ['title'=>'清理商城订单数据','command' => 'mall:clean','loops' => 60],
        ['title'=>'同步科脉零售单到本地用户','command' => 'data:km:retail sync','loops' => 60],
        ['title'=>'虚拟订单同步微信小程序发货','command' => 'WechatDelivery:upload','loops' => 300],
    ];



    /**
     * @auth true
     * @return void
     */
    public function index()
    {
       if ($this->request->get('output') == 'layui.table'){

           $list = [];

           $sysQueue = SystemQueue::mk()
               ->whereIn('command',array_column(self::LIST,'command'))->where('loops_time','>',0)->column('id','command');



           foreach (self::LIST as $vo){
               $vo['id'] = $sysQueue[$vo['command']]??'';
               $list[] = $vo;
           }


           $result = ['code' => 0,'msg' => '','count' => count(self::LIST),'data' => $list ];
           throw new HttpResponseException(json($result));
       }else{
           $this->title="任务启动";
           $this->fetch();
       }
    }

    public function sync(){
        $key = $this->request->get('key','-1');
        $info = self::LIST[$key]??[];
        empty($info) && $this->error('任务不存在');

        $this->_queue($info['title'], $info['command'], 0, [], 0, $info['loops']);
    }
}