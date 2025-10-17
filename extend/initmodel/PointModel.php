<?php

namespace initmodel;

use think\facade\Db;
use think\Model;

/**
 * @AdminModel(
 *     "name"             =>"Point",
 *     "table_name"       =>"base_point",
 *     "model_name"       =>"PointModel",
 *     "remark"           =>"积分操作",
 *     "author"           =>"",
 *     "create_time"      =>"2024年9月4日11:11:56",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\PointModel();
 * )
 */
class PointModel extends Model
{
    protected $name = 'base_point';

    /**
     * 新增积分
     * @param $identity_type    身份
     * @param $id               id值
     * @param $point            积分
     * @param $content          展示内容
     * @param $remark           管理员备注
     * @param $order_id         订单id
     * @param $order_num        订单单号
     * @param $order_type       订单类型  100后台操作   500订单佣金  600邀请技师佣金
     * @param $child_id         子级id
     * @return void
     */
    public static function inc_point($identity_type = 'member', $id, $point, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0, $child_id=0)
    {
        if ($identity_type == 'member') $Model = new \initmodel\MemberModel();//用户管理
        if ($identity_type == 'technician') $Model = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $info = $Model->where('id', '=', $id)->find();
        if ($point <= 0) return;

        $log = array(
            'user_id'       => $id,
            'type'          => 1,
            'price'         => $point,
            'identity_type' => $identity_type,
            'child_id'      => $child_id ?? 0,
            'before'        => $info['point'],
            'after'         => $info['point'] + $point,
            'content'       => $content,
            'remark'        => $remark,
            'order_type'    => $order_type,
            'order_id'      => $order_id,
            'create_time'   => time(),
            'order_num'     => $order_num,
        );
        //写入明细
        Db::name('base_point')->strict(false)->insert($log);
        //更新当前积分
        $Model->where('id', '=', $id)->inc('point', $point)->update();
    }


    /**
     * 减少积分
     * @param $identity_type        身份
     * @param $id                   id值
     * @param $point                积分
     * @param $content              展示内容
     * @param $remark               管理员备注
     * @param $order_id             订单id
     * @param $order_num            订单单号
     * @param $order_type           订单类型 100后台操作  10兑换商品  30转赠积分
     * @param $child_id             子级id
     * @param $service_charge       手续费 0
     * @return void
     */
    public static function dec_point($identity_type = 'member', $id, $point, $content, $remark, $order_id = 0, $order_num = 0, $order_type = 0, $child_id = 0, $service_charge = 0)
    {
        if ($identity_type == 'member') $Model = new \initmodel\MemberModel();//用户管理
        if ($identity_type == 'technician') $Model = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $info = $Model->where('id', '=', $id)->find();
        if ($point <= 0) return;

        $log = array(
            'user_id'        => $id,
            'type'           => 2,
            'price'          => $point,
            'identity_type'  => $identity_type,
            'child_id'       => $child_id ?? 0,
            'before'         => $info['point'],
            'after'          => $info['point'] - $point,
            'content'        => $content,
            'remark'         => $remark,
            'order_type'     => $order_type,
            'order_id'       => $order_id,
            'create_time'    => time(),
            'order_num'      => $order_num,
            'service_charge' => $service_charge,
        );
        //写入明细
        Db::name('base_point')->strict(false)->insert($log);
        //更新当前积分
        $Model->where('id', '=', $id)->dec('point', $point)->update();
    }

}