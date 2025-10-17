<?php

namespace init;

use initmodel\BalanceModel;
use initmodel\MemberModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * 定时任务
 */
class TaskInit
{


    /**
     * 更新vip状态
     */
    public function operation_vip()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理

        //操作vip   vip_time vip到期时间
        //$MemberModel->where('vip_time', '<', time())->update(['is_vip' => 0]);
        echo("更新vip状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 处理优惠券状态
     */
    public function operation_coupon()
    {
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券  (ps:InitModel)

        $map   = [];
        $map[] = ['end_time', '<', time()];
        $ShopCouponModel->where($map)
            ->strict(false)
            ->update([
                'status'      => 2,
                'update_time' => time(),
            ]);

        $map2   = [];
        $map2[] = ['end_time', '>', time()];
        $ShopCouponModel->where($map2)
            ->strict(false)
            ->update([
                'status'      => 1,
                'update_time' => time(),
            ]);


        echo("处理优惠券状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 处理用户优惠券状态
     */
    public function operation_coupon_user()
    {
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)


        $map   = [];
        $map[] = ['end_time', '<', time()];
        $map[] = ['used', '=', 1];
        $ShopCouponUserModel->where($map)
            ->strict(false)
            ->update([
                'used'        => 3,//已过期
                'update_time' => time(),
            ]);


        echo("处理用户优惠券状态,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 处理自动取消订单
     */
    public function operation_cancel_order()
    {
        $ShopOrderModel      = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)


        $map   = [];
        $map[] = ['auto_cancel_time', '<', time()];
        $map[] = ['status', '=', 1];
        $list  = $ShopOrderModel->where($map)->select();
        foreach ($list as $k => $value) {
            //更改订单状态
            $ShopOrderModel->where('id', $value['id'])
                ->strict(false)
                ->update([
                    'status'      => 10,
                    'cancel_time' => time(),
                    'update_time' => time(),
                ]);


            //优惠券返回
            if ($value['coupon_id']) {
                $ShopCouponUserModel->where('id', '=', $value['coupon_id'])
                    ->strict(false)
                    ->update([
                        'used_time'   => 0,
                        'used'        => 1,
                        'order_num'   => 0,
                        'update_time' => time(),
                    ]);
            }
        }


        echo("处理自动取消订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 技师n分钟不接单自动取消订单
     */
    public function operation_technician_cancel_order()
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $Pay            = new PayController();

        $map   = [];
        $map[] = ['auto_technician_cancel_time', '<', time()];
        $map[] = ['status', '=', 2];
        $list  = $ShopOrderModel->where($map)->select();

        //更改订单状态
        $ShopOrderModel->where($map)
            ->strict(false)
            ->update([
                'status'           => 11,//技师取消状态 为11
                'refund_pass_time' => time(),
                'cancel_time'      => time(),
                'update_time'      => time(),
            ]);

        //退款
        foreach ($list as $k => $info) {
            $refund_amount = $info['amount'];

            //获取支付单号
            $map      = [];
            $map[]    = ['order_num', '=', $info['order_num']];
            $map[]    = ['status', '=', 2];
            $pay_info = Db::name('order_pay')->where($map)->find();


            //退款 && 微信退款
            if ($info['pay_type'] == 1) {
                $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['pay_num'], $refund_amount, $refund_amount);//定时任务技师未接单&&全额退款
                $refund_result = $refund_result['data'];
                if ($refund_result['result_code'] != 'SUCCESS') {
                    Log::write("退款失败,订单号:{$info['order_num']},退款金额:{$refund_amount},原因:{$refund_result['err_code_des']}");
                }
            }
            //余额退款
            if ($info['pay_type'] == 2) {
                $remark = "操作人[技师未接单退款];操作说明[同意退款订单:{$info['order_num']};金额:{$info['balance']}];操作类型[技师未接单,系统自动退单];";//管理备注
                if ($refund_amount) BalanceModel::inc_balance('member', $info['user_id'], $refund_amount, '技师未接单退款', $remark, $info['id'], $info['order_num'], 200);
            }
        }


        echo("技师n分钟不接单自动取消订单,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 评分
     */
    public function operation_star()
    {
        $TechnicianModel    = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $OrderEvaluateModel = new \initmodel\OrderEvaluateModel(); //评价管理   (ps:InitModel)

        $technician_list = $TechnicianModel->select();
        foreach ($technician_list as $k => $v) {
            $map   = [];
            $map[] = ['technician_id', '=', $v['id']];
            $star  = $OrderEvaluateModel->where($map)->avg('star');
            if ($star) {
                $TechnicianModel->where('id', $v['id'])
                    ->strict(false)
                    ->update(['star' => $star]);
            }
        }


        echo("评分,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 将公众号的official_openid存入member表中
     */
    public function update_official_openid()
    {
        $gzh_list = Db::name('member_gzh')->select();
        foreach ($gzh_list as $k => $v) {
            Db::name('member')->where('unionid', '=', $v['unionid'])
                ->strict(false)
                ->update(['official_openid' => $v['openid']]);
        }

        echo("将公众号的official_openid存入member表中,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }


    /**
     * 每个月固定时间给技师发放佣金
     */
    public function operation_send_order_commission()
    {
        $TechnicianModel           = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $ShopOrderModel            = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianCommissionModel = new \initmodel\TechnicianCommissionModel(); //技师佣金管理   (ps:InitModel)
        $CommissionLogModel        = new \initmodel\CommissionLogModel(); //结算记录   (ps:InitModel)

        //技师列表
        $map             = [];
        $map[]           = ['status', '=', 2];
        $technician_list = $TechnicianModel->where($map)->select();

        //每月1-10号结算佣金/11号结算
        $settlement_from_1st_to_10th = cmf_config('settlement_from_1st_to_10th');

        //每月11-20号结算佣金/21号结算
        $settlement_on_the_11th_and_21th = cmf_config('settlement_on_the_11th_and_21th');


        //每月21-31号结算佣金/1号结算
        $settlement_on_the_21st_to_31st = cmf_config('settlement_on_the_21st_to_31st');

        $date = date('Y-m');
        $day  = date('d');
        $day1 = explode("/", $settlement_from_1st_to_10th)[1];
        $day2 = explode("/", $settlement_on_the_11th_and_21th)[1];
        $day3 = explode("/", $settlement_on_the_21st_to_31st)[1];

        //每月1-10号结算佣金/11号结算
        if ($day == $day1) {
            //1-10号
            $min_day = explode("-", $settlement_from_1st_to_10th)[0];
            $max_day = explode("-", $settlement_from_1st_to_10th)[1];

            //获取时间戳
            $begin_time = strtotime(date("Y-m-{$min_day} 00:00:00"));
            $end_time   = strtotime(date("Y-m-{$max_day} 23:59:59"));
        }

        //每月11-20号结算佣金/21号结算
        if ($day == $day2) {
            //11-20号
            $min_day = explode("-", $settlement_on_the_11th_and_21th)[0];
            $max_day = explode("-", $settlement_on_the_11th_and_21th)[1];

            //获取时间戳
            $begin_time = strtotime(date("Y-m-{$min_day} 00:00:00"));
            $end_time   = strtotime(date("Y-m-{$max_day} 23:59:59"));
        }

        //每月21-31号结算佣金/1号结算
        if ($day == $day3) {
            //21-31号
            $min_day = explode("-", $settlement_on_the_21st_to_31st)[0];
            $max_day = explode("-", $settlement_on_the_21st_to_31st)[1];

            //获取时间戳 & 上个月
            $begin_time = strtotime('-1 month', strtotime(date("Y-m-{$min_day} 00:00:00")));
            $end_time   = strtotime('-1 month', strtotime(date("Y-m-{$max_day} 23:59:59")));
        }


        //计算佣金发放佣金
        $map100   = [];
        $map100[] = ['create_time', 'between', [$begin_time, $end_time]];
        $map100[] = ['status', 'in', [5, 8]];

        foreach ($technician_list as $k => $technician_info) {
            //服务金额+车费   优惠券平台出
            $goodsAmountSum   = $ShopOrderModel->where($map100)->sum('goods_amount');
            $freightAmountSum = $ShopOrderModel->where($map100)->sum('freight_amount');
            $totalAmountSum   = $ShopOrderModel->where($map100)->sum('amount');


            //计算属于哪个段位佣金比例
            $map200     = [];
            $map200[]   = ['user_id', '=', $technician_info['id']];
            $map200[]   = ['min_amount', '<', $totalAmountSum];
            $map200[]   = ['max_amount', '>', $totalAmountSum];
            $commission = $TechnicianCommissionModel->where($map200)->value('commission');


            //技师拿商品佣金, 车费全拿
            $balance = ($goodsAmountSum * $commission) + $freightAmountSum;
            if ($balance > 0) {
                $remark = "操作人[技师订单佣金];操作说明[订单结算日期:{$date};佣金{$balance};总金额:{$totalAmountSum};商品价格:{$goodsAmountSum};车费:{$freightAmountSum}];操作类型[技师佣金奖励];";//管理备注
                BalanceModel::inc_balance('technician', $technician_info['id'], $balance, '订单佣金', $remark, 0, cmf_order_sn(), 500);

                //计算记录
                $CommissionLogModel->strict(false)->insert([
                    'technician_id'  => $technician_info['id'],
                    'date'           => $date,
                    'day'            => $day,
                    'commission'     => $commission,
                    'goods_amount'   => $goodsAmountSum,
                    'freight_amount' => $freightAmountSum,
                    'total_amount'   => $totalAmountSum,
                    'amount'         => $balance,
                    'create_time'    => time(),
                ]);
            }


        }


        echo("每个月固定时间给技师发放佣金,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n");
    }

}