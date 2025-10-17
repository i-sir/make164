<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"OrderEvaluate",
 *     "name_underline"   =>"order_evaluate",
 *     "table_name"       =>"order_evaluate",
 *     "model_name"       =>"OrderEvaluateModel",
 *     "remark"           =>"评价管理",
 *     "author"           =>"",
 *     "create_time"      =>"2024-08-31 16:42:33",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\OrderEvaluateModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class OrderEvaluateModel extends Model
{

    protected $name = 'shop_order_evaluate';//评价管理

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
