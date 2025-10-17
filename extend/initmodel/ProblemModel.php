<?php

namespace initmodel;

/**
    * @AdminModel(
    *     "name"             =>"Problem",
    *     "name_underline"   =>"problem",
    *     "table_name"       =>"problem",
    *     "model_name"       =>"ProblemModel",
    *     "remark"           =>"常见问题",
    *     "author"           =>"",
    *     "create_time"      =>"2024-09-01 15:16:24",
    *     "version"          =>"1.0",
    *     "use"              => new \initmodel\ProblemModel();
    * )
    */


use think\facade\Db;
use think\Model;
use think\model\concern\SoftDelete;


class ProblemModel extends Model{

	protected $name = 'problem';//常见问题

	//软删除
	protected $hidden            = ['delete_time'];
	protected $deleteTime        = 'delete_time';
    protected $defaultSoftDelete = 0;
    use SoftDelete;
}
