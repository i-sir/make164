<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Problem",
 *     "name_underline"          =>"problem",
 *     "controller_name"         =>"Problem",
 *     "table_name"              =>"problem",
 *     "remark"                  =>"常见问题"
 *     "api_url"                 =>"/api/wxapp/problem/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-09-01 15:16:24",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ProblemController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/problem/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/problem/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ProblemController extends AuthController
{


    public function initialize()
    {
        //常见问题

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/problem/index
     * https://dzam164.wxselling.net/api/wxapp/problem/index
     */
    public function index()
    {
        $ProblemInit  = new \init\ProblemInit();//常见问题   (ps:InitController)
        $ProblemModel = new \initmodel\ProblemModel(); //常见问题   (ps:InitModel)

        $result = [];

        $this->success('常见问题-接口请求成功', $result);
    }


    /**
     * 常见问题 列表
     * @OA\Post(
     *     tags={"常见问题"},
     *     path="/wxapp/problem/find_problem_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="(选填)关键字搜索",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="openid",
     *         in="query",
     *         description="openid",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/problem/find_problem_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/problem/find_problem_list
     *   api:  /wxapp/problem/find_problem_list
     *   remark_name: 常见问题 列表
     *
     */
    public function find_problem_list()
    {
        $ProblemInit  = new \init\ProblemInit();//常见问题   (ps:InitController)
        $ProblemModel = new \initmodel\ProblemModel(); //常见问题   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ProblemInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 常见问题 详情
     * @OA\Post(
     *     tags={"常见问题"},
     *     path="/wxapp/problem/find_problem",
     *
     *
     *
     *    @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/problem/find_problem
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/problem/find_problem
     *   api:  /wxapp/problem/find_problem
     *   remark_name: 常见问题 详情
     *
     */
    public function find_problem()
    {
        $ProblemInit  = new \init\ProblemInit();//常见问题    (ps:InitController)
        $ProblemModel = new \initmodel\ProblemModel(); //常见问题   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ProblemInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }




}
