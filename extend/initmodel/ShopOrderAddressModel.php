<?php

namespace initmodel;

/**
 * @AdminModel(
 *     "name"             =>"ShopOrderAddress",
 *     "name_underline"   =>"shop_order_address",
 *     "table_name"       =>"shop_order_address",
 *     "model_name"       =>"ShopOrderAddressModel",
 *     "remark"           =>"技师定位",
 *     "author"           =>"",
 *     "create_time"      =>"2024-12-06 16:02:05",
 *     "version"          =>"1.0",
 *     "use"              => new \initmodel\ShopOrderAddressModel();
 * )
 */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ShopOrderAddressModel extends Model
{

    protected $name = 'shop_order_address';//技师定位

    //软删除
    protected $hidden            = ['delete_time'];
    protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
