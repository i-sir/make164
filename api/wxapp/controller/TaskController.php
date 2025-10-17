<?php

namespace api\wxapp\controller;

/**
 * @ApiMenuRoot(
 *     'name'   =>'Task',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   =>'cogs',
 *     'remark' =>'定时任务'
 * )
 */

use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TaskController
{

    /**
     * 执行定时任务
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/task/index
     *   official_environment: https://ljh.wxselling.net/api/wxapp/task/index
     *   api: /wxapp/task/index
     *   remark_name: 执行定时任务
     *
     */
    public function index()
    {
        $task = new \init\TaskInit();
        $task->operation_vip();//处理vip
        $task->operation_coupon();//处理优惠券
        $task->operation_coupon_user();//处理用户优惠券
        $task->operation_cancel_order();//自动取消订单
        $task->operation_technician_cancel_order();//技师 自动取消订单
        $task->operation_star();//评分

        //将公众号的official_openid存入member表中  可以在用户授权登录后操作
        //$task->update_official_openid();

        echo("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");
        Log::write("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");

        return json("定时任务已执行完毕-------" . date('Y-m-d H:i:s'));
    }


    /**
     * 执行定时任务 (每个月发放佣金)  此项目不需要
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/task/send_order_commission
     *   official_environment: https://ljh.wxselling.net/api/wxapp/task/send_order_commission
     *   api: /wxapp/task/send_order_commission
     *   remark_name: 给技师增加佣金
     *
     */
    public function send_order_commission()
    {
        $task               = new \init\TaskInit();
        $CommissionLogModel = new \initmodel\CommissionLogModel(); //结算记录   (ps:InitModel)


        //每月1-10号结算佣金/11号结算
        $settlement_from_1st_to_10th = cmf_config('settlement_from_1st_to_10th');

        //每月11-20号结算佣金/21号结算
        $settlement_on_the_11th_and_21th = cmf_config('settlement_on_the_11th_and_21th');


        //每月21-31号结算佣金/1号结算
        $settlement_on_the_21st_to_31st = cmf_config('settlement_on_the_21st_to_31st');


        $day  = date('d');
        $day1 = explode("/", $settlement_from_1st_to_10th)[1];
        $day2 = explode("/", $settlement_on_the_11th_and_21th)[1];
        $day3 = explode("/", $settlement_on_the_21st_to_31st)[1];


        //每个月结算一次
        $is_send = $CommissionLogModel->where('date', '=', date('Y-m-d'))->count();

        if (!$is_send && in_array($day, [$day1, $day2, $day3])) {
            $task->operation_send_order_commission();//给技师增加佣金
            echo("走结算\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");
        }


        echo("定时任务,执行成功\n" . cmf_random_string(80) . "\n" . date('Y-m-d H:i:s') . "\n\n\n");

        return json("定时任务已执行完毕-------" . date('Y-m-d H:i:s'));
    }


}