<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"RoadPrice",
    *     "name_underline"   =>"road_price",
    *     "table_name"       =>"road_price",
    *     "model_name"       =>"RoadPriceModel",
    *     "remark"           =>"路费配置",
    *     "author"           =>"",
    *     "create_time"      =>"2024-08-30 15:47:00",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\RoadPriceModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class RoadPriceModel extends Model{

	protected $name = 'road_price';//路费配置

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
