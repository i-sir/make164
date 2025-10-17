<?php

namespace api\wxapp\controller;

use initmodel\BalanceModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;

class OrderPayController extends AuthController
{

    public function initialize()
    {
        parent::initialize();//初始化方法

    }


    /**
     * 微信公众号支付
     * @OA\Post(
     *     tags={"订单支付"},
     *     path="/wxapp/order_pay/wx_pay_mp",
     *
     *
     * 	   @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="order_type",
     *         in="query",
     *         description="订单类型:10订单,20充值余额",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     * 	   @OA\Parameter(
     *         name="pay_type",
     *         in="query",
     *         description="支付类型:1微信支付,2余额支付",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/order_pay/wx_pay_mp
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/order_pay/wx_pay_mp
     *   api: /wxapp/order_pay/wx_pay_mp
     *   remark_name: 微信公众号支付
     *
     */
    public function wx_pay_mp()
    {
        //$this->checkAuth();

        $Pay                      = new PayController();
        $OrderPayModel            = new \initmodel\OrderPayModel();
        $MemberRechargeOrderModel = new \initmodel\MemberRechargeOrderModel(); //充值订单   (ps:InitModel)
        $ShopOrderModel           = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $InitController           = new \api\wxapp\controller\InitController();


        $params = $this->request->param();
        $openid = $this->openid;


        $map   = [];
        $map[] = ['order_num', '=', $params['order_num']];
        //订单
        if ($params['order_type'] == 10) {
            $order_info = $ShopOrderModel->where($map)->find();

            /**检测技师这个时间段是否被占用**/
            $is_make = $InitController->isTimeSlotAvailable($order_info['technician_id'], $order_info['begin_time'], $order_info['end_time']);//支付
            if ($is_make) $this->error('时间冲突不可以预约');
        }
        //充值
        if ($params['order_type'] == 20) $order_info = $MemberRechargeOrderModel->where($map)->find();

        if (empty($order_info)) $this->error('订单不存在');
        if ($order_info['amount'] < 0.01) $this->error('订单错误');


        //订单金额&&订单号
        $amount    = $order_info['amount'] ?? 0.01;
        $order_num = $order_info['order_num'] ?? cmf_order_sn(6);

        //支付记录插入一条记录
        $pay_num = $OrderPayModel->add($openid, $order_num, $amount, $params['order_type']);


        //微信支付
        if ($params['pay_type'] == 1) {
            $res = $Pay->wx_pay_mp($pay_num, $amount, $openid);

            if ($res['code'] != 1) $this->error($res['msg']);
            $this->success('请求成功', $res['data']);
        }


        //余额支付
        if ($params['pay_type'] == 2) {
            if ($this->user_info['balance'] < $amount) $this->error('余额不足');

            //扣除余额
            $remark = "操作人[{$this->user_id}-{$this->user_info['nickname']}];操作说明[订单号:{$order_num};金额:{$amount}];操作类型[支付订单];";//管理备注
            BalanceModel::dec_balance($this->user_info['identity_type'], $this->user_id, $amount, '支付订单', $remark, $order_info['id'], $order_num, 10);

            //更改订单支付类型
            $ShopOrderModel->where($map)->update(['pay_type' => 2]);

            //订单支付回调
            $Notify = new \api\wxapp\controller\NotifyController();
            $Notify->processOrder($pay_num);

            //更改支付记录,状态
            $data['time']            = time();
            $pay_update['pay_time']  = time();
            $pay_update['trade_num'] = $transaction_id ?? '9880' . cmf_order_sn(8);
            $pay_update['status']    = 2;
            $pay_update['notify']    = serialize($data);
            $OrderPayModel->where('pay_num', '=', $pay_num)->strict(false)->update($pay_update);

            $this->success('支付成功');
        }


    }


}