<?php

namespace initmodel;

use think\facade\Db;
use think\Model;

/**
 * @AdminModel(
 *     "name"             =>"Balance",
 *     "table_name"       =>"base_balance",
 *     "model_name"       =>"BalanceModel",
 *     "remark"           =>"余额操作",
 *     "author"           =>"",
 *     "create_time"      =>"2024年9月4日11:11:56",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\BalanceModel();
 * )
 */
class BalanceModel extends Model
{
    protected $name = 'base_balance';


    /**
     * 新增余额
     * @param $identity_type    身份类型
     * @param $id               id值
     * @param $balance          金额
     * @param $content          展示内容
     * @param $remark           管理员备注
     * @param $order_id         订单id
     * @param $order_num        订单单号
     * @param $order_type       订单类型  100后台操作  200技师未接单退款  210用户取消订单,给技师补偿  500佣金奖励   900提现驳回
     * @return void
     * @throws \think\db\exception\DbException
     */
    public static function inc_balance($identity_type = 'member', $id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        if ($identity_type == 'member') $Model = new \initmodel\MemberModel();//用户管理
        if ($identity_type == 'technician')  $Model = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $info = $Model->where('id', '=', $id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'       => $id,
            'type'          => 1,
            'price'         => $balance,
            'identity_type' => $identity_type,
            'before'        => $info['balance'],
            'after'         => $info['balance'] + $balance,
            'content'       => $content,
            'remark'        => $remark,
            'order_id'      => $order_id,
            'order_type'    => $order_type,
            'create_time'   => time(),
            'order_num'     => $order_num,
        );
        //写入明细
        Db::name('base_balance')->strict(false)->insert($log);
        //更新当前金额
        $Model->where('id', '=', $id)->inc('balance', $balance)->update();
    }


    /**
     * 减少余额
     * @param $identity_type    身份
     * @param $id               id值
     * @param $balance          金额
     * @param $content          展示内容
     * @param $remark           管理备注
     * @param $order_id         订单id
     * @param $order_num        订单单号
     * @param $order_type       订单类型 10支付订单 100后台操作  800提现申请
     * @return void
     * @throws \think\db\exception\DbException
     */
    public static function dec_balance($identity_type = 'member', $id, $balance, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0)
    {
        if ($identity_type == 'member') $Model = new \initmodel\MemberModel();//用户管理
        if ($identity_type == 'technician')  $Model = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $info = $Model->where('id', '=', $id)->find();
        if ($balance <= 0) return;

        $log = array(
            'user_id'       => $id,
            'type'          => 2,
            'price'         => $balance,
            'identity_type' => $identity_type,
            'before'        => $info['balance'],
            'after'         => $info['balance'] - $balance,
            'content'       => $content,
            'remark'        => $remark,
            'order_id'      => $order_id,
            'order_type'    => $order_type,
            'create_time'   => time(),
            'order_num'     => $order_num,
        );
        //写入明细
        Db::name('base_balance')->strict(false)->insert($log);
        //更新当前金额
        $Model->where('id', '=', $id)->dec('balance', $balance)->update();
    }

}