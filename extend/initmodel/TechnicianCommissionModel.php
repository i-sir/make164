<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"TechnicianCommission",
    *     "name_underline"   =>"technician_commission",
    *     "table_name"       =>"technician_commission",
    *     "model_name"       =>"TechnicianCommissionModel",
    *     "remark"           =>"技师佣金管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-09-05 17:17:47",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\TechnicianCommissionModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class TechnicianCommissionModel extends Model{

	protected $name = 'technician_commission';//技师佣金管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
