<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"ShopOrderComplaint",
    *     "name_underline"   =>"shop_order_complaint",
    *     "table_name"       =>"shop_order_complaint",
    *     "model_name"       =>"ShopOrderComplaintModel",
    *     "remark"           =>"订单投诉",
    *     "author"           =>"",
    *     "create_time"      =>"2024-12-06 16:55:39",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\ShopOrderComplaintModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopOrderComplaintModel extends Model{

	protected $name = 'shop_order_complaint';//订单投诉

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
