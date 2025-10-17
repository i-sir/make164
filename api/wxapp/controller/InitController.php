<?php

namespace api\wxapp\controller;

use initmodel\BalanceModel;
use initmodel\MemberModel;
use initmodel\PointModel;
use think\facade\Db;

/**
 * @ApiController(
 *     "name"                    =>"Init",
 *     "name_underline"          =>"init",
 *     "controller_name"         =>"Init",
 *     "table_name"              =>"无",
 *     "remark"                  =>"基础接口,封装的接口"
 *     "api_url"                 =>"/api/wxapp/init/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-04-24 17:16:22",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\InitController();
 *     "test_environment"        =>"http://makeTemplate.ikun/api/wxapp/init/index",
 *     "official_environment"    =>"https://dzam157.wxselling.net/api/wxapp/init/index",
 * )
 */
class InitController
{
    /**
     * 本模块,用于封装常用方法,复用方法
     */


    /**
     * 给上级发放佣金 (point)佣金
     * @param $p_user_id
     * https://dzam157.wxselling.net/api/wxapp/init/send_invitation_commission?order_num=2
     */
    public function send_invitation_commission($order_num = 0)
    {
        $ShopOrderModel  = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $MemberModel     = new \initmodel\MemberModel();//用户管理


        /** 邀请下级佣金 **/
        $order_info = $ShopOrderModel->where('order_num', '=', $order_num)->find();
        //订单状态,不正确不返佣
        if (!in_array($order_info['status'], [3, 4, 5, 8])) return false;

        //加钟是否发放佣金  开启[1] 关闭[2]
        $additional_commission = cmf_config('additional_commission');
        //订单类型加钟,加钟佣金关闭  不在向下执行
        if ($order_info['type'] == 2 && $additional_commission == 2) return false;

        //查看自己是否为有效推广人
        $user_info = $MemberModel->where('id', '=', $order_info['user_id'])->find();
        if ($user_info['effective_invitation'] == 1) {
            //佣金
            $balance = $order_info['total_commission'];

            //查看上级
            $pid = $MemberModel->where('id', '=', $order_info['user_id'])->value('pid');
            if ($balance > 0 && $pid) {
                $remark = "操作人[下级订单完成佣金];操作说明[订单{$order_info['order_num']};佣金{$balance};商品价格{$order_info['goods_amount']};车费{$order_info['freight_amount']}];操作类型[下级订单完成返佣];";//管理备注
                PointModel::inc_point('member', $pid, $balance, '订单佣金', $remark, $order_info['id'], $order_num, 500, $order_info['user_id']);
            }
        }


        /** 邀请技师奖励 **/
        //邀请技师,满足n单,可得佣金
        $number_of_technicians = cmf_config('number_of_technicians');
        //邀请技师可得n元佣金
        $technician_commission = cmf_config('technician_commission');

        //检测技师是否满意条件,给上级返佣
        $map         = [];
        $map[]       = ['technician_id', '=', $order_info['technician_id']];
        $map[]       = ['status', 'in', [5, 8]];
        $order_count = $ShopOrderModel->where($map)->count();
        if ($order_count >= $number_of_technicians) {
            //查看上级
            $technician_info = $TechnicianModel->where('id', '=', $order_info['technician_id'])->find();
            $pid             = $technician_info['pid'];
            if ($technician_commission > 0 && $pid && $technician_info['is_send'] == 2) {
                //技师改为已发放状态
                $TechnicianModel->where('id', '=', $order_info['technician_id'])->update(['is_send' => 1]);

                //发放佣金
                $remark = "操作人[邀请技师佣金];操作说明[技师订单:{$order_count}单;佣金{$technician_commission}];操作类型[邀请技师佣金];"; //管理备注
                PointModel::inc_point('member', $pid, $technician_commission, '邀请技师', $remark, $order_info['id'], $order_num, 600, $order_info['technician_id']);
            }
        }


        return "true";
    }


    /**
     * 完成订单给技师发放佣金 ,订单完成直接给技师发放
     * @param $order_num 订单号
     *                   https://dzam157.wxselling.net/api/wxapp/init/send_order_commission?order_num=240903618498713402
     */
    public function send_order_commission($order_num = 0)
    {
        $ShopOrderModel  = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $order_info = $ShopOrderModel->where('order_num', '=', $order_num)->find();

        //订单状态,不正确不返佣
        if (!in_array($order_info['status'], [3, 4, 5, 8])) return false;


        //技师拿商品佣金, 车费全拿
        $technician_info = $TechnicianModel->where('id', '=', $order_info['technician_id'])->find();
        $commission      = $technician_info['commission'] / 100;//技师佣金比例


        //订单类型加钟,加钟佣金
        if ($order_info['type'] == 2) $commission = $technician_info['add_commission'] / 100;//技师佣金比例


        $balance = ($order_info['goods_amount'] * $commission) + $order_info['freight_amount'];
        if ($balance > 0) {
            $remark = "操作人[技师订单佣金];操作说明[订单{$order_info['order_num']};佣金{$balance};商品价格{$order_info['goods_amount']};车费{$order_info['freight_amount']}];操作类型[技师佣金奖励];";//管理备注
            BalanceModel::inc_balance('technician', $order_info['technician_id'], $balance, '订单佣金', $remark, $order_info['id'], $order_num, 500);
        }
        return "true";
    }


    /**
     * 获取技师预计佣金 按月计算
     * @param $technician_id
     */
    public function get_estimate_commission($technician_id = 0)
    {
        $TechnicianModel           = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $ShopOrderModel            = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianCommissionModel = new \initmodel\TechnicianCommissionModel(); //技师佣金管理   (ps:InitModel)


        //每月1-10号结算佣金/11号结算
        $settlement_from_1st_to_10th = cmf_config('settlement_from_1st_to_10th');

        //每月11-20号结算佣金/21号结算
        $settlement_on_the_11th_and_21th = cmf_config('settlement_on_the_11th_and_21th');


        //每月21-31号结算佣金/1号结算
        $settlement_on_the_21st_to_31st = cmf_config('settlement_on_the_21st_to_31st');


        $day  = date('d');
        $day1 = explode("-", explode("/", $settlement_from_1st_to_10th)[0]);
        $day2 = explode("-", explode("/", $settlement_on_the_11th_and_21th)[0]);
        $day3 = explode("-", explode("/", $settlement_on_the_21st_to_31st)[0]);


        //1-10号
        $range = range($day1[0], $day1[1]);
        // 使用 str_pad 补零
        $formattedRange = array_map(function ($number) {
            return str_pad($number, 2, '0', STR_PAD_LEFT);
        }, $range);
        if (in_array($day, $formattedRange)) {
            //1-10号
            $min_day = $day1[0];
            $max_day = $day1[1];

            //获取时间戳
            $begin_time = strtotime(date("Y-m-{$min_day} 00:00:00"));
            $end_time   = strtotime(date("Y-m-{$max_day} 23:59:59"));
        }

        //11-20号
        $range = range($day2[0], $day2[1]);
        // 使用 str_pad 补零
        $formattedRange = array_map(function ($number) {
            return str_pad($number, 2, '0', STR_PAD_LEFT);
        }, $range);
        if (in_array($day, $formattedRange)) {
            //11-20号
            $min_day = $day2[0];
            $max_day = $day2[1];

            //获取时间戳
            $begin_time = strtotime(date("Y-m-{$min_day} 00:00:00"));
            $end_time   = strtotime(date("Y-m-{$max_day} 23:59:59"));
        }


        //21-31号
        $range = range($day3[0], $day3[1]);
        // 使用 str_pad 补零
        $formattedRange = array_map(function ($number) {
            return str_pad($number, 2, '0', STR_PAD_LEFT);
        }, $range);
        if (in_array($day, $formattedRange)) {
            //21-31号
            $min_day = $day3[0];
            $max_day = $day3[1];

            //获取时间戳
            $begin_time = strtotime(date("Y-m-{$min_day} 00:00:00"));
            $end_time   = strtotime(date("Y-m-{$max_day} 23:59:59"));
        }


        //技师详情
        $technician_info = $TechnicianModel->where('id', '=', $technician_id)->find();


        //计算佣金
        $map100   = [];
        $map100[] = ['create_time', 'between', [$begin_time, $end_time]];
        $map100[] = ['status', 'in', [5, 8]];

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
        if ($balance > 0) return $balance;

        return 0;
    }


    /**
     * 有服务订单通知技师,公众号,短信
     * @param int $order_num
     * @param int $type 通知类型: 1下单成功   2取消订单
     *
     */
    public function send_msg($order_num = 0, $type = 1)
    {
        $ShopOrderModel        = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianModel       = new \initmodel\TechnicianModel();//技师管理   (ps:InitModel)
        $SendTempMsgController = new SendTempMsgController();
        $MemberModel           = new \initmodel\MemberModel();//用户管理


        //公众号模板消息,模板id
        $official_account_template_message = cmf_config('official_account_template_message');


        $map             = [];
        $map[]           = ['order_num', '=', $order_num];
        $order_info      = $ShopOrderModel->where($map)->find();
        $technician_info = $TechnicianModel->where('id', '=', $order_info['technician_id'])->find();


        //短息通知
        $ali_sms = cmf_get_plugin_class("AliSms");
        $sms     = new $ali_sms();
        //        if ($type == 1) $template = '2294680';//下单通知
        //        $sms->sendSms(['phone' => $technician_info['phone'], 'template' => $template]);


        //公众号通知  && 下单通知
        if ($type == 1) {
            $template_id = $official_account_template_message;//模板id
            $send_data   = [
                'time14'            => ['value' => date('Y-m-d H:i:s', $order_info['begin_time'])],
                'thing13'           => ['value' => $order_info['username']],
                'character_string2' => ['value' => $order_info['order_num']],
                'thing15'           => ['value' => $order_info['goods_name']],
            ];

            //域名
            $url = cmf_get_domain() . "/h5/#/pages/technician/orderDetail?orderNum={$order_num}";
            //https://dzam107.wxselling.net/h5/#/pages/technician/orderDetail?orderNum=
            $SendTempMsgController->sendTempMsg($technician_info['wx_openid'], $template_id, $send_data, '', 1, $url);
        }

        //语音通知
        $ali_sms = cmf_get_plugin_class("HuYi");
        $sms     = new $ali_sms();
        //通知
        $params = ["mobile" => $technician_info['phone'], 'content' => ''];
        $sms->sendVoiceMsg($params);


        //通知管理
        $template_id   = cmf_config('new_order_notification_administrator');//公众号模板消息,模板id
        $administrator = cmf_config('alarm_notification_administrator');        //用户id,使用逗号隔开
        $user_list     = $MemberModel->where('id', 'in', explode(',', $administrator))->select();
        $send_data   = [
            'time14'            => ['value' => date('Y-m-d H:i:s', $order_info['begin_time'])],
            'thing13'           => ['value' => $order_info['username']],
            'character_string2' => ['value' => $order_info['order_num']],
            'thing19'           => ['value' => $order_info['goods_name']],
            'phrase5'           => ['value' => $technician_info['nickname']],
        ];
        if ($user_list) {
            foreach ($user_list as $user_info) {
                if ($user_info['openid']) $SendTempMsgController->sendTempMsg($user_info['openid'], $template_id, $send_data);
            }
        }

    }


    /**
     * 设置openid 记录
     * @param $pid           用户|技师 id
     * @param $openid        openid
     * @param $identity_type 身份类型:member|technician
     */
    public function set_openid($pid, $openid, $identity_type = 'member')
    {
        $map         = [];
        $map[]       = ['openid', '=', $openid];
        $map[]       = ['identity_type', '=', $identity_type];
        $openid_info = Db::name('openid')->where($map)->find();
        if (empty($openid_info)) {
            $map2         = [];
            $map2[]       = ['identity_type', '=', $identity_type];
            $map2[]       = ['pid', '=', $pid];
            $openid_info2 = Db::name('openid')->where($map2)->find();
            if ($openid_info2) {
                //技师每次登陆更新一下openid
                Db::name('openid')->where($map2)->strict(false)->update([
                    'openid'      => $openid,
                    'update_time' => time(),
                ]);
            } else {
                //第一次登录存入一下基础信息
                Db::name('openid')->strict(false)->insert([
                    'openid'        => $openid,
                    'pid'           => $pid,
                    'identity_type' => $identity_type,
                    'create_time'   => time(),
                ]);
            }

        } elseif ($openid_info['pid'] != $pid) {
            //如删除用户了,openid 还在,则更新用户id
            Db::name('openid')->where($map)->strict(false)->update([
                'pid'         => $pid,
                'update_time' => time(),
            ]);
        }
        return true;
    }


    /**
     * 检查技师在指定时间段是否有冲突的预约
     *
     * @param int $technician_id 技师ID
     * @param int $begin_time    新预约的开始时间 (Unix 时间戳)
     * @param int $end_time      新预约的结束时间 (Unix 时间戳)
     * @return bool 如果时间段可预约则返回 true，否则返回 false
     */
    public function isTimeSlotAvailable($technician_id, $begin_time, $end_time)
    {
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)


        /**
         * 数据库两字段
         * begin_hour_time=9:00
         * end_hour_time=15:00
         * 一下三种情况满足一种需查询出结果
         * 1.提交时间为11:00-12:00  完全在设定区间内
         * 2.提交时间为12:00-16:00  开始时间在设定区间内
         * 3.提交时间为8:00-12:00   结束时间在设定区间内
         */

        $map   = [];
        $map[] = ['status', '=', 1];// 仅检查 status 为 1 的有效预约
        $map[] = ['technician_id', '=', $technician_id];//技师

        //1.在时间区间内
        $map1   = [];
        $map1[] = ['begin_time', '>=', $begin_time];
        $map1[] = ['end_time', '<=', $end_time];
        $map1   = array_merge($map, $map1);


        //2.开始时间在设定区间内
        $map2   = [];
        $map2[] = ['begin_time', '>=', $begin_time];
        $map2[] = ['end_time', '<=', $end_time];
        $map2   = array_merge($map, $map2);


        //3.结束时间在设定区间内
        $map3   = [];
        $map3[] = ['begin_time', '>=', $begin_time];
        $map3[] = ['end_time', '<=', $end_time];
        $map3   = array_merge($map, $map3);


        $conflicts = $ShopOrderSaveModel->whereOr([$map1, $map2, $map3])->find();


        // 如果没有找到冲突的预约记录，返回 true 表示该时间段可预约
        return $conflicts;
    }

}