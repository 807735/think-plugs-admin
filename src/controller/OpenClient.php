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


use app\manage\service\OpenService;
use think\admin\Controller;
use OpenClient as Open;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemOpenClient;
use think\admin\service\AdminService;

/**
 * 中间件参数配置
 * Class Config
 * @package app\admin\controller
 */
class OpenClient extends Controller
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
        SystemOpenClient::mQuery()->layTable(function (){
            $this->title = "接口调用记录";
        },function (QueryHelper $query){
            $query->equal('model,status')->dateBetween('create_time');
        });
    }

    public function information(){
        SystemOpenClient::mForm('information');
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
     * 获取状态
     * @return void
     * @throws Exception
     */
    public function status(){
        $vo = sysdata('open');
        if (empty($vo) || empty($vo['app_path']) || empty($vo['app_code']) || empty($vo['appsecret'])){
            die(  "<span class='color-red pointer' data-tips-text='参数错误'>参数错误，请正确输入接口参数</span>" );
        }
        [$state,$info] = Open::App($vo)->check();
        if ($state){
            [,,$vo] = OpenService::OpenApp()->info();

            $code = $vo['app']['code']??'-';
            $name = $vo['app']['name']??'-';
            $companyCode =  $vo['app']['company']['code']??'-';
            $companyName = $vo['app']['company']['name']??'-';

            $appConfig = "<b data-tips-text='公司编码：{$companyCode}' class='pointer color-blue font-s12 font-w9'>公司名称：{$companyName}</b> ";
            $appConfig .= "<span class='ta-mr-10 ta-ml-5 layui-font-blue'>|</span><b data-tips-text='应用编码：{$code}' class='pointer color-blue font-s12 font-w9'>应用名称：{$name}</b> ";

            echo "
            <span class='color-green pointer' data-tips-text='通讯成功'>通讯成功</span> 
            <span class='ta-mr-40 layui-font-gray'></span>  
            <span class='color-text'>接口信息：</span> 
            <div class='font-w1 inline-block'>{$appConfig}</div> ";
        }else{
            echo "<span class='color-red pointer' data-tips-text='{$info}'>通讯失败 - {$info}</span>";
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
            $this->vo = sysdata('open');
            $this->fetch();
        } else {
            $post = $this->request->post('open');
            sysdata('open', $post);
            $this->success('接口地址保存成功！');
        }
    }
}