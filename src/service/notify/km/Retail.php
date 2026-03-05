<?php

declare (strict_types=1);
namespace app\common\service\notify\km;

use app\common\model\BaseSyncData;
use app\admin\service\Notify;
use app\common\service\OpenService;
use app\data\service\ConfigService;
use think\admin\Exception;

/**
 * 短信接口
 */
class Retail extends Notify
{

    /**
     * 科脉零售单异步通知
     * @return void
     */
     public function create(): void
     {
         try {
             if (empty( ConfigService::get('kemai_sync_state')))  $this->notify->error('系统未开通积分兑换功能',[],2);
             if ($this->EventData['Retail']['SellWay'] == 'B'){
                 $base = BaseSyncData::mk()->where(['code' =>$this->EventData['Retail']['VoucherId']])->whereIn('status',[1,2,3])->findOrEmpty();
                 if ($base->isEmpty()){
                     $this->notify->error('数据不存在');
                 }
                 if (in_array($base->getAttr('status'),[1,2])){
                     $this->notify->error('数据状态错误');
                 }
                 $res = OpenService::instance()->synRetail($this->EventData);
                 $res->setAttrs(['mode' => 'auto']);
                 $res->save();
             }
             $this->notify->success('处理成功');
         }catch (Exception $exception){
             $this->notify->error($exception->getMessage());
         }
     }
}