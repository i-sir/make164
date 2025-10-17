<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"Leave",
 *     "name_underline"   =>"leave",
 *     "table_name"       =>"leave",
 *     "model_name"       =>"LeaveModel",
 *     "remark"           =>"投诉建议",
 *     "author"           =>"",
 *     "create_time"      =>"2024-06-06 11:38:50",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\LeaveModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class LeaveModel extends Model
{

    protected $name = 'base_leave';//投诉建议

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
