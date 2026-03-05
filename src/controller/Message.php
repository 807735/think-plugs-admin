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

namespace app\admin\controller;

use app\manage\model\SystemMsms;
use app\manage\service\Message as MessageService;
use app\manage\service\OpenService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;

/**
 * 短信计划管理
 * @class Message
 * @package app\manage\controller
 */
class Message extends Controller
{

    /**
     * 缓存配置名称
     * @var string
     */
    protected string $smskey;

    /**
     * 初始化控制器
     * @return void
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->smskey = 'plugin.common.smscfg';
    }


    /**
     * 短信计划管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(): void
    {
        $this->type =  input('type','ALL');
        SystemMsms::mQuery()->layTable(function (QueryHelper $query) {
            $this->title = '短信计划管理';
            $this->scenes =  array_merge(['ALL' => ['name' => '全部场景','state' => 1,'total' => 0]], MessageService::getScenes() );
            foreach ($query->db()->field('scene,count(1) total')->group('scene')->cursor() as $vo) {
                [$this->scenes[$vo['scene']]['total'] = $vo['total'], $this->scenes['ALL']['total'] += $vo['total']];
            }


//            foreach ($this->scenes as $k => $vo) if ($vo['state'] == 0) unset($this->scenes[$k]);
        },  function (QueryHelper $query) {
            if ($this->type != 'ALL')  $query->where(['scene' => $this->type]);
            $query->equal('status')->like('scene,mobile')->dateBetween('create_time');
        });
    }

    /**
     * 修改短信配置
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function config(): void
    {
        if ($this->request->isGet()) {
            $this->scenes = json_encode(MessageService::getScenes(),JSON_UNESCAPED_UNICODE);
            $this->fetch();
        } else {
            $config = json_decode($this->request->post('config','{}'),true);
            foreach ($config as $k => $v){
                if ($v['type'] == 'instant' && empty($v['template']) ) $this->error("【{$v['name']}】 模版编号不能为空！");
                if ($v['type'] == 'instant' && empty($v['content']) ) $this->error("【{$v['name']}】 模版内容不能为空！");
            }
            sysdata($this->smskey, $config);
            $this->success('修改配置成功！');
        }
    }

    /**
     * 获取模版
     * @return void
     */
    public function getTemp(): void
    {
        $map = $this->_vali([
            'template.require'   => '请输入模版编号！'
        ]);
        [$state,$info,$data] = OpenService::OpenMsg()->tempInfo($map['template']);
        if (empty($state)) $this->error($info);
        $this->success('模版获取成功',$data);
    }

    /**
     * 获取模版列表
     * @return void
     */
    public function getTempList(): void
    {
        [$state,$info,$data] = OpenService::OpenMsg()->tempList();
        if (empty($state)) $this->error($info);
        $this->success('模版获取成功',$data['list']);
    }

    /**
     * 启动自动任务
     * @auth true
     * @return void
     */
    public function task(){
        if (AdminService::isSuper()) {
            sysoplog('系统运维管理', '发送短信计划任务');
            $this->_queue('发送短信计划任务', "openClient:msg", 0, [], 0, 60);
        } else {
            $this->error('请使用超管账号操作！');
        }
    }
}