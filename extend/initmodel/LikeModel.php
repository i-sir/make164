<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"Like",
    *     "name_underline"   =>"like",
    *     "table_name"       =>"like",
    *     "model_name"       =>"LikeModel",
    *     "remark"           =>"收藏",
    *     "author"           =>"",
    *     "create_time"      =>"2024-09-01 10:51:21",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\LikeModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class LikeModel extends Model{

	protected $name = 'like';//收藏

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
