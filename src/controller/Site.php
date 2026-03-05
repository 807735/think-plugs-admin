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

use app\manage\model\SystemSite;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemAuth;
use think\admin\model\SystemBase;
use think\admin\model\SystemUser;
use think\admin\service\AdminService;

/**
 * 站点管理
 * Class Config
 * @package app\admin\controller
 */
class Site extends Controller
{
    /**
     * 站点管理
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
            $this->title = "站点管理";
            $this->username =  AdminService::getUserName();
        },function (QueryHelper $query){
            $query->with('user');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加站点
     * @auth true
     * @return void
     */
    public function add(){
        SystemSite::mForm('form');
    }

    /**
     * 编辑站点
     * @auth true
     * @return void
     */
    public function edit(){
        SystemSite::mForm('form');
    }

    /**
     * 修改站点状态
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
     * 删除站点
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
                empty($data['authorize']) && $this->error('未配置权限！');

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
            $data['authorize'] = str2arr($data['authorize'] ?? '');
            $this->auths = SystemAuth::items();
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
     * 切换站点
     * @auth true
     * @return void
     */
    public function switchSite(){
        $map = $this->_vali([
            'id.require' => '参数错误',
        ]);
        $site = SystemSite::mk()->with('user')->where($map)->findOrEmpty();
        $user = $site->getAttr('user');
//        $this->app->session->destroy();
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