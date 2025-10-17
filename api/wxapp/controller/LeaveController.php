<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Leave",
 *     "name_underline"          =>"leave",
 *     "controller_name"         =>"Leave",
 *     "table_name"              =>"leave",
 *     "remark"                  =>"投诉建议"
 *     "api_url"                 =>"/api/wxapp/leave/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-06-06 11:38:50",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\LeaveController();
 *     "test_environment"        =>"http://makeTemplate.ikun/api/wxapp/leave/index",
 *     "official_environment"    =>"https://ljh.wxselling.net/api/wxapp/leave/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class LeaveController extends AuthController
{


    public function initialize()
    {
        //投诉建议

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/leave/index
     * https://ljh.wxselling.net/api/wxapp/leave/index
     */
    public function index()
    {
        $LeaveInit  = new \init\LeaveInit();//投诉建议   (ps:InitController)
        $LeaveModel = new \initmodel\LeaveModel(); //投诉建议   (ps:InitModel)

        $result = [];

        $this->success('投诉建议-接口请求成功', $result);
    }


    /**
     * 投诉建议 列表
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/leave/find_leave_list",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/leave/find_leave_list
     *   official_environment: https://ljh.wxselling.net/api/wxapp/leave/find_leave_list
     *   api:  /wxapp/leave/find_leave_list
     *   remark_name: 投诉建议 列表
     *
     */
    public function find_leave_list()
    {
        $this->checkAuth();

        $LeaveInit  = new \init\LeaveInit();//投诉建议   (ps:InitController)
        $LeaveModel = new \initmodel\LeaveModel(); //投诉建议   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];

        if ($params["keyword"]) $where[] = ["username|phone|content", "like", "%{$params['keyword']}%"];
        if ($params["user_id"]) $where[] = ["user_id", "=", $params["user_id"]];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $LeaveInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 投诉建议 详情
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/leave/find_leave",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/leave/find_leave
     *   official_environment: https://ljh.wxselling.net/api/wxapp/leave/find_leave
     *   api:  /wxapp/leave/find_leave
     *   remark_name: 投诉建议 详情
     *
     */
    public function find_leave()
    {
        $LeaveInit  = new \init\LeaveInit();//投诉建议    (ps:InitController)
        $LeaveModel = new \initmodel\LeaveModel(); //投诉建议   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $LeaveInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 投诉建议 添加
     * @OA\Post(
     *     tags={"投诉建议"},
     *     path="/wxapp/leave/add_leave",
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
     *
     *
     *    @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="联系人 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="title",
     *         in="query",
     *         description="标题 (选)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="content",
     *         in="query",
     *         description="留言内容",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="images",
     *         in="query",
     *         description="图集",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/leave/add_leave
     *   official_environment: https://ljh.wxselling.net/api/wxapp/leave/add_leave
     *   api:  /wxapp/leave/add_leave
     *   remark_name: 投诉建议  添加
     *
     */
    public function add_leave()
    {
        $this->checkAuth();

        $LeaveInit  = new \init\LeaveInit();//投诉建议    (ps:InitController)
        $LeaveModel = new \initmodel\LeaveModel(); //投诉建议   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        //提交更新
        $result = $LeaveInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试");


        $this->success("感谢您提出宝贵的建议");
    }


}
