<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"MemberRecharge",
    *     "name_underline"   =>"member_recharge",
    *     "table_name"       =>"member_recharge",
    *     "model_name"       =>"MemberRechargeModel",
    *     "remark"           =>"充值管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-08-31 10:58:53",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\MemberRechargeModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class MemberRechargeModel extends Model{

	protected $name = 'member_recharge';//充值管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
