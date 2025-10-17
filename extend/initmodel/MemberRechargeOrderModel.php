<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"MemberRechargeOrder",
    *     "name_underline"   =>"member_recharge_order",
    *     "table_name"       =>"member_recharge_order",
    *     "model_name"       =>"MemberRechargeOrderModel",
    *     "remark"           =>"充值订单",
    *     "author"           =>"",
    *     "create_time"      =>"2024-08-31 10:59:12",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\MemberRechargeOrderModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberRechargeOrderModel extends Model{

	protected $name = 'member_recharge_order';//充值订单

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
