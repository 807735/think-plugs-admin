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

use app\admin\controller\Openapi;
use app\data\model\DataConfigCompany;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemAuth;
use think\admin\model\SystemBase;
use think\admin\model\SystemSite;
use think\admin\model\SystemUser;
use think\admin\service\AdminService;

/**
 * 商户管理.
 * @site admin
 */
class Site extends Controller
{

    /**
     * 商户管理
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(): void
    {

        $this->type = $this->get['type'] ?? 'index';
        SystemSite::mQuery()->layTable(function (){
            $this->title = "商户管理";
            $this->username =  AdminService::getUserName();
        },function (QueryHelper $query){
            $query->with(['user','wechatAuth']);
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加商户
     * @auth true
     * @return void
     */
    public function add(){
        SystemSite::mForm('form');
    }

    /**
     * 编辑商户
     * @auth true
     * @return void
     */
    public function edit(){
        SystemSite::mForm('form');
    }
    protected function _add_form_filter(&$data){
        if ($this->request->isPost() && empty($data['id']) && ( $id = SystemSite::mk()->getSiteId() )){
            if (SystemSite::mk()->where('id',$id)->count()>0) $this->error('商户ID重复，请稍后再试');
            $data['id'] = $id;
            // 初始化数据
            $data['openapi'] = [
                "app_path" => "",
                "app_code" => "",
                "appsecret" => ""
            ];
            $data['mallapi'] = [
                "app_path" => "",
                "app_code" => "",
                "appsecret" => ""
            ];
            $data['accountcfg'] = [
                "expire" => "3600",
                "disRegister" => "1",
                "userPrefix" => "用户",
                "headimg" =>  "{$this->request->domain()}/static/theme/img/headimg.png",
                "types" => [
                    "0" => "wap",
                    "1" => "web",
                    "2" => "wxapp",
                    "3" => "wechat",
                    "4" => "iosapp",
                    "5" => "android"
                ]
            ];
            $data['extra'] = [
                "assistance_plan_document_path" => "",
                "mentorShare" => [
                    "imageUrl" => "",
                    "originalId" => "",
                    "title" => ""
                ],
                "member_fund_amount" => "0",
                "publish_day" => "",
                "max_price" => "0.00",
                "help_rebate" => "0",
                "is_msg_warning" => "0",
                "warning_price" => "0.00",
                "is_msg_arrears" => "0",
                "is_msg_stop" => "0",
                "create_member_is_msg" => "0",
                "remove_member_is_msg" => "0",
                "create_employee_is_msg" => "0",
                "downsizing_clean_member_fund" => "0",
                "update_member_is_msg" => "0",
                "default_company_id" => "",
                "default_agency_id" => "",
                "default_agency_code" => ""
            ];
            $data['pagecfg'] = [
                "user_privacy" => [
                    "name" => "隐私权政策",
                    "content" => "",
                    "code" => "user_privacy"
                ],
                "user_agreement" => [
                    "name" => "用户协议",
                    "content" => "",
                    "code" => "user_agreement"
                ]
            ];
            $data['ordercfg'] = [
                "cancel_family_auto" => "0",
                "cancel_family_time" => "0.50",
                "cancel_family_text" => "",
                "remove_family_auto" => "0",
                "remove_family_time" => "0.50",
                "remove_family_text" => "",
                "cancel_recharge_auto" => "0",
                "cancel_recharge_time" => "0.50",
                "cancel_recharge_text" => "",
                "remove_recharge_auto" => "0",
                "remove_recharge_time" => "0.50",
                "remove_recharge_text" => ""
            ];
            $data['contractcfg'] = [
                "emp" => [
                    "state" => "0",
                    "auth" => "0",
                    "flowName" => "",
                    "essAccountId" => "",
                    "key" => "",
                    "token" => "",
                    "secretId" => "",
                    "secretKey" => "",
                    "templateId" => "",
                    "userId" => "",
                    "endPoint" => "",
                    "organizationName" => "",
                    "expireTime" => "0",
                    "wechatAppid" => "",
                    "wechatOriginal" => ""
                ]
            ];
        }
    }

    protected function _add_form_result($result, $edata) {
        if ($result !== false && ( $siteId = $edata['id'] ) ){
            $info = DataConfigCompany::mk()->where(['site_id' => $siteId,'deleted' => 0 ])->findOrEmpty();
            if ($info->isEmpty()){
                $info->setAttrs([
                    'site_id' => $siteId,  'code' => DataConfigCompany::mk()->getCode(),
                    'name' => $edata['name'], 'is_default' => 1,
                ]);
                $info->save();
            }
        }
    }

    /**
     * 中间件参数
     * @return void
     */
    public function openapi(): void
    {
        SystemSite::mForm('openapi/form');
    }
    protected function _openapi_form_filter(&$data){
        if ($this->request->isGet()){
            if (empty($this->geoip)) {
                $this->geoip = gethostbyname($this->request->host());
                $this->app->cache->set('mygeoip', $this->geoip, 360);
            }
            $vars = CodeExtend::enSafe64(json_encode( ['site_id' => $data['id']] , 64 | 256));
            $this->thrNotify = sysuri('@openapi-notify', [], false, true). "/{$vars}";
            $data = array_merge(['id' => $data['id']],$data['openapi']);
        }
    }

    /**
     * 修改商户状态
     * @auth true
     */
    public function state()
    {
        SystemSite::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }
    /**
     * 删除商户
     * @auth true
     */
    public function remove()
    {
        SystemSite::mDelete();
    }

    /**
     * 绑定管理员账号
     * @auth true
     * @return void
     */
    public function bindManage(){
        SystemSite::mForm('bind_manage');
    }

    protected function _bindManage_form_filter(array &$data){
        if ($this->request->isPost()) {
            $type = $data['type']??'';
            unset($data['type']);
            if ($type == 0){
                SystemUser::mk()->where(['id' => $data['user_id']])->update([
                    'authorize' => arr2str([1]),
                    'site_id' => $data['id'],
                ]);
            }else if ($type == 1){
                $site_id =  $data['id'];
                unset($data['id']);

                // 检查资料是否完整
                empty($data['username']) && $this->error('登录账号不能为空！');

                $data['site_id'] = $site_id;
                $data['usertype'] = 'site';
                $data['authorize'] = arr2str($data['authorize'] ?? []);

                $map = ['username' => $data['username'], 'is_deleted' => 0];
                $db = SystemUser::mk();
                if ($db->where($map)->count() > 0) {
                    $this->error('账号已经存在，请使用其它账号！');
                }
                // 新添加的用户密码与账号相同
                $data['password'] = md5($data['username']);

                $data = ['id' => $site_id,'user_id' => $db->insertGetId($data)];
            }
        }else{
            // 权限绑定处理
//            $this->auths = SystemAuth::items();
            $this->bases = SystemBase::items('身份权限');
            $this->super = AdminService::getSuperName();
        }
    }

    /**
     * 解除绑定
     * @auth true
     * @return void
     */
    public function unbind(){
        $map = $this->_vali([
            'id.require' => '参数错误',
            'user_id.require' => '解除会员参数错误',
        ]);

        SystemUser::mk()->where(['id' => $map['user_id']])->save(['site_id' => 0]);
        SystemSite::mk()->where(['id' => $map['id']])->save(['user_id' => 0]);
        $this->success('操作成功');
    }

    /**
     * * 切换商户
     * @auth true
     * @return void
     */
    public function switchSite(){
        $map = $this->_vali([
            'id.require' => '参数错误',
        ]);
        $site = SystemSite::mk()->with('user')->where($map)->findOrEmpty();
        $user = $site->getAttr('user');
        $this->app->session->set('adminUser',$user['id'] );
        $this->app->session->set('user', $user->toArray());
        $this->success('登录成功', sysuri('admin/index/index'));
    }
    /**
     * 获取账号信息
     * @return void
     */
    public function getUser(){
        $map = $this->_vali([
            'username.require' => '请输入登录用户名',
            'usertype.value' => 'site',
            'is_deleted.value' => 0,
        ]);
        $user = SystemUser::mk()->where($map)->findOrEmpty();
        if ($user->isEmpty()) $this->error('账号不存在');
        if ($user->getAttr('status') != 1) $this->error('账号已被禁用');
        if ($user->getAttr('site_id') > 0 && $user->getAttr('site_id') != input('sid',-1)) $this->error('账号已被绑定其他站');
        $this->success('账号信息',$user->toArray());
    }
}