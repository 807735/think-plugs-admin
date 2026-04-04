<?php

namespace app\admin\service;

use app\mall\model\MallOrder;
use app\mall\model\MallOrderSender;
use app\member\model\MemberBind;
use app\payment\model\PaymentRecord;
use app\payment\service\Payment;
use think\admin\Exception;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WeMini\Shipping;

class ShippingService
{
    /**
     * @param string $order_no
     * @return false|void
     * @throws Exception
     * @throws InvalidResponseException
     */
    public static function upload(string $order_no)
    {
        list($upload_time) = [date('Y-m-d\TH:i:s.vP')];
        $payment = PaymentRecord::mk()->where(['order_no'=>$order_no,'payment_status'=>1])->find();
        if ($payment->getAttr('channel_type') != Payment::WECHAT_XCX || $payment->getAttr('wechat_delivery') == 1){
            return false;
        }
        $senderCount = MallOrderSender::mk()->where(['order_no'=>$order_no])->count();
        if($senderCount > 1) return false;
        //$openid = MemberBind::mk()->where(['id'=>$payment['usid']])->value('openid');
        $param = [
            'order_key' => ['order_number_type'=>2,'transaction_id'=>$payment['payment_trade']],
            'delivery_mode' => 1,
            'upload_time' => $upload_time,
            //'payer' => ['openid'=>$openid],
            'payer' => $payment['payment_notify']['payer'],
        ];

        if ($payment['order_type'] !='mall_goods'){
            if ($payment['ac_type'] != 'member') return false;
            $param['logistics_type'] = '3';//物流模式，发货方式枚举值：1、实体物流配送采用快递公司进行实体物流配送形式 2、同城配送 3、虚拟商品，虚拟商品，例如话费充值，点卡等，无实体配送形式 4、用户自提
            $param['shipping_list'] =[['item_desc'=>'用户在线充值']];
        }else{
            $MallOrder= MallOrder::mk()->with(['items','payment','pack','sender'])->where(['order_no'=>$order_no])->findOrEmpty();
            if ($MallOrder->isEmpty()) return false;
            $delivery_code  = array_column($MallOrder['items']->toArray(),'delivery_code');

            if(in_array('NONE',$delivery_code)){
                $param['logistics_type'] = '3';
                $param['shipping_list'] =[['item_desc'=>'无需发货，券类产品']];
            }else{
                $param['logistics_type'] = 1;
                $param['shipping_list'] = [
                    [
                        'tracking_no'=>$MallOrder['pack'][0]['express_code'] ?? '',
                        'express_company'=>$MallOrder['pack'][0]['company_code'] ?? '',
                        'item_desc'=>$MallOrder['items'][0]['gname'] ?? '',
                        'contact'=>['receiver_contact'=>$MallOrder['sender']['0']['user_mobile'] ?? '',]
                    ]
                ];
                // if($MallOrder['sender']) return false;
            }
        }

        $wxapp = sysdata('plugin.wechat.wxapp');
        $config = [
            'appid'      => $wxapp['appid'] ?? '',
            'appsecret'  => $wxapp['appkey'] ?? '',
        ];

        try {
            Shipping::instance($config)->upload($param);
        } catch (LocalCacheException $e) {
            throw new InvalidResponseException($e->getMessage().'-'.$order_no, );
        }
        $payment->wechat_delivery = 1;
        $payment->save();
    }
}