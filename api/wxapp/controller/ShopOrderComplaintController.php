<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopOrderComplaint",
 *     "name_underline"          =>"shop_order_complaint",
 *     "controller_name"         =>"ShopOrderComplaint",
 *     "table_name"              =>"shop_order_complaint",
 *     "remark"                  =>"订单投诉"
 *     "api_url"                 =>"/api/wxapp/shop_order_complaint/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-12-06 16:55:39",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopOrderComplaintController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/shop_order_complaint/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/shop_order_complaint/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopOrderComplaintController extends AuthController
{


    public function initialize()
    {
        //订单投诉

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_order_complaint/index
     * https://dzam164.wxselling.net/api/wxapp/shop_order_complaint/index
     */
    public function index()
    {
        $ShopOrderComplaintInit  = new \init\ShopOrderComplaintInit();//订单投诉   (ps:InitController)
        $ShopOrderComplaintModel = new \initmodel\ShopOrderComplaintModel(); //订单投诉   (ps:InitModel)

        $result = [];

        $this->success('订单投诉-接口请求成功', $result);
    }


    /**
     * 订单投诉 详情 (需要就用)
     * @OA\Post(
     *     tags={"订单投诉"},
     *     path="/wxapp/shop_order_complaint/find_complaint",
     *
     *
     *
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_order_complaint/find_complaint
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_order_complaint/find_complaint
     *   api:  /wxapp/shop_order_complaint/find_complaint
     *   remark_name: 订单投诉 详情
     *
     */
    public function find_complaint()
    {
        $ShopOrderComplaintInit  = new \init\ShopOrderComplaintInit();//订单投诉    (ps:InitController)
        $ShopOrderComplaintModel = new \initmodel\ShopOrderComplaintModel(); //订单投诉   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["order_num", "=", $params["order_num"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopOrderComplaintInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 订单投诉  添加
     * @OA\Post(
     *     tags={"订单投诉"},
     *     path="/wxapp/shop_order_complaint/add_complaint",
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
     *         name="images",
     *         in="query",
     *         description="图片",
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
     *         description="内容",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_order_complaint/add_complaint
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_order_complaint/add_complaint
     *   api:  /wxapp/shop_order_complaint/add_complaint
     *   remark_name: 订单投诉  添加
     *
     */
    public function add_complaint()
    {
        $this->checkAuth();
        $ShopOrderComplaintInit  = new \init\ShopOrderComplaintInit();//订单投诉    (ps:InitController)
        $ShopOrderComplaintModel = new \initmodel\ShopOrderComplaintModel(); //订单投诉   (ps:InitModel)
        $ShopOrderModel          = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单错误');

        //更改订单投诉状态
        $ShopOrderModel->where($where)->strict(false)->update([
            'is_complaint'   => 1,
            'complaint_time' => time(),
            'update_time'    => time(),
        ]);


        //提交更新
        $result = $ShopOrderComplaintInit->api_edit_post($params);
        if (empty($result)) $this->error("失败请重试");


        $this->success("投诉成功,感谢您的反馈");
    }


}
