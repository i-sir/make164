<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"CommissionLog",
    *     "name_underline"   =>"commission_log",
    *     "table_name"       =>"commission_log",
    *     "model_name"       =>"CommissionLogModel",
    *     "remark"           =>"结算记录",
    *     "author"           =>"",
    *     "create_time"      =>"2024-09-05 19:31:58",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\CommissionLogModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class CommissionLogModel extends Model{

	protected $name = 'commission_log';//结算记录

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
