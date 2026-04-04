<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------
namespace app\admin\controller;


use think\admin\Controller;
use OpenClient as Open;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemOpenApi;
use think\admin\model\SystemOpenClient;
use think\admin\service\AdminService;
use think\admin\service\OpenService;


/**
 * 商城参数配置.
 * @site admin,site
 * @class Menu
 */
class Mallapi extends Controller
{
    /**
     * 接口调用记录
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(){
        $this->vo = [];
        SystemOpenApi::mQuery()->layTable(function (){
            $this->title = "接口调用记录";
        },function (QueryHelper $query){
            $query->where(['site_id' => $this->site_id])->equal('model,status')->dateBetween('create_time');
        });
    }

    public function information(){
        SystemOpenApi::mForm('information');
    }

    protected function _information_form_filter(&$data){
        $data['request'] = json_encode($data['request'],JSON_UNESCAPED_UNICODE);
        $data['response'] = json_encode($data['response'],JSON_UNESCAPED_UNICODE);
    }

    /**
     * 自动清除过期数据
     * @auth true
     * @return void
     */
    public function clean(){
        if (AdminService::isSuper()) {
            sysoplog('系统运维管理', '中间件自动清除过期数据');
            $this->_queue('中间件自动清除过期数据', "openClient:clean", 0, [], 0, 3600);
        } else {
            $this->error('请使用超管账号操作！');
        }
    }


    /**
     * 接口参数
     * @auth true
     * @return void
     * @throws Exception
     */
    public function form(){
        if ($this->request->isGet()) {

            $this->geoip = $this->app->cache->get('mygeoip', '');
            if (empty($this->geoip)) {
                $this->geoip = gethostbyname($this->request->host());
                $this->app->cache->set('mygeoip', $this->geoip, 360);
            }
            $this->vo = sysdata('mallapi.config');

            $this->thrNotify = sysuri('@help-api', [], false, true). "/";
            $this->fetch();
        } else {
            $post = $this->request->post('open');
            sysdata('mallapi.config',$post);
//            AdminService::setSite('mallapi',$post);
            $this->success('接口地址保存成功！');
        }
    }
}