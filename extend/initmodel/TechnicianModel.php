<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"Technician",
    *     "name_underline"   =>"technician",
    *     "table_name"       =>"technician",
    *     "model_name"       =>"TechnicianModel",
    *     "remark"           =>"技师管理",
    *     "author"           =>"",
    *     "create_time"      =>"2024-08-30 15:12:32",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\TechnicianModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class TechnicianModel extends Model{

	protected $name = 'technician';//技师管理

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
