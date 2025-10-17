<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"OrderEvaluate",
 *     "name_underline"          =>"order_evaluate",
 *     "controller_name"         =>"OrderEvaluate",
 *     "table_name"              =>"order_evaluate",
 *     "remark"                  =>"评价管理"
 *     "api_url"                 =>"/api/wxapp/order_evaluate/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-31 16:42:33",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\OrderEvaluateController();
 *     "test_environment"        =>"http://makeTemplate.ikun/api/wxapp/order_evaluate/index",
 *     "official_environment"    =>"https://ljh.wxselling.net/api/wxapp/order_evaluate/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class OrderEvaluateController extends AuthController
{


    public function initialize()
    {
        //评价管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/order_evaluate/index
     * https://ljh.wxselling.net/api/wxapp/order_evaluate/index
     */
    public function index()
    {
        $OrderEvaluateInit  = new \init\OrderEvaluateInit();//评价管理   (ps:InitController)
        $OrderEvaluateModel = new \initmodel\OrderEvaluateModel(); //评价管理   (ps:InitModel)

        $result = [];

        $this->success('评价管理-接口请求成功', $result);
    }


    /**
     * 评价管理 列表
     * @OA\Post(
     *     tags={"评价管理"},
     *     path="/wxapp/order_evaluate/find_evaluate_list",
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
     *     @OA\Parameter(
     *         name="technician_id",
     *         in="query",
     *         description="技师id",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/order_evaluate/find_evaluate_list
     *   official_environment: https://ljh.wxselling.net/api/wxapp/order_evaluate/find_evaluate_list
     *   api:  /wxapp/order_evaluate/find_evaluate_list
     *   remark_name: 评价管理 列表
     *
     */
    public function find_evaluate_list()
    {
        $OrderEvaluateInit  = new \init\OrderEvaluateInit();//评价管理   (ps:InitController)
        $OrderEvaluateModel = new \initmodel\OrderEvaluateModel(); //评价管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['technician_id', '=', $params['technician_id']];
        if ($params["keyword"]) $where[] = ["user_id", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $OrderEvaluateInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 评价管理 添加
     * @OA\Post(
     *     tags={"评价管理"},
     *     path="/wxapp/order_evaluate/add_evaluate",
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
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="订单号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="star",
     *         in="query",
     *         description="星级",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="evaluate",
     *         in="query",
     *         description="评价",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/order_evaluate/add_evaluate
     *   official_environment: https://ljh.wxselling.net/api/wxapp/order_evaluate/add_evaluate
     *   api:  /wxapp/order_evaluate/add_evaluate
     *   remark_name: 评价管理 添加
     *
     */
    public function add_evaluate()
    {
        $this->checkAuth();
        $OrderEvaluateInit  = new \init\OrderEvaluateInit();//评价管理    (ps:InitController)
        $OrderEvaluateModel = new \initmodel\OrderEvaluateModel(); //评价管理   (ps:InitModel)
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        //检测订单是否存在
        $where      = [];
        $where[]    = ['order_num', '=', $params['order_num']];
        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单不存在');
        $params['technician_id'] = $order_info['technician_id'];


        //检测是否已经提交
        $evaluate_info = $OrderEvaluateModel->where($where)->find();
        if ($evaluate_info) $this->error('已经评价过了');

        //更改订单状态
        $ShopOrderModel->where($where)->update([
            'status'          => 8,
            'evaluate_time'   => time(),
            'accomplish_time' => time(),
            'update_time'     => time(),
        ]);

        //提交更新
        $result = $OrderEvaluateInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试");


        $this->success('评价成功');
    }


}
