<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"ShopOrderSave",
    *     "name_underline"   =>"shop_order_save",
    *     "table_name"       =>"shop_order_save",
    *     "model_name"       =>"ShopOrderSaveModel",
    *     "remark"           =>"技师已约时间",
    *     "author"           =>"",
    *     "create_time"      =>"2024-08-30 17:53:16",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\ShopOrderSaveModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopOrderSaveModel extends Model{

	protected $name = 'shop_order_save';//技师已约时间

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
