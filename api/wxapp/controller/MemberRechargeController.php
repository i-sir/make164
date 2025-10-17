<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"MemberRecharge",
 *     "name_underline"          =>"member_recharge",
 *     "controller_name"         =>"MemberRecharge",
 *     "table_name"              =>"member_recharge",
 *     "remark"                  =>"充值管理"
 *     "api_url"                 =>"/api/wxapp/member_recharge/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-26 15:28:08",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\MemberRechargeController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/member_recharge/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/member_recharge/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class MemberRechargeController extends AuthController
{


    public function initialize()
    {
        //充值管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/member_recharge/index
     * https://dzam164.wxselling.net/api/wxapp/member_recharge/index
     */
    public function index()
    {
        $MemberRechargeInit  = new \init\MemberRechargeInit();//充值管理   (ps:InitController)
        $MemberRechargeModel = new \initmodel\MemberRechargeModel(); //充值管理   (ps:InitModel)

        $result = [];

        $this->success('充值管理-接口请求成功', $result);
    }


    /**
     * 充值管理 列表
     * @OA\Post(
     *     tags={"充值管理"},
     *     path="/wxapp/member_recharge/find_member_recharge_list",
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
     *         name="token",
     *         in="query",
     *         description="token",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member_recharge/find_member_recharge_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member_recharge/find_member_recharge_list
     *   api:  /wxapp/member_recharge/find_member_recharge_list
     *   remark_name: 充值管理 列表
     *
     */
    public function find_member_recharge_list()
    {
        $MemberRechargeInit  = new \init\MemberRechargeInit();//充值管理   (ps:InitController)
        $MemberRechargeModel = new \initmodel\MemberRechargeModel(); //充值管理   (ps:InitModel)

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
        $result                  = $MemberRechargeInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 下单
     * @OA\Post(
     *     tags={"充值管理"},
     *     path="/wxapp/member_recharge/add_order",
     *
     *
     *
     *     @OA\Parameter(
     *         name="recharge_id",
     *         in="query",
     *         description="充值id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="balance",
     *         in="query",
     *         description="自定义充值金额",
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
     *         name="token",
     *         in="query",
     *         description="token",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member_recharge/add_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member_recharge/add_order
     *   api:  /wxapp/member_recharge/add_order
     *   remark_name: 下单
     *
     */
    public function add_order()
    {
        $this->checkAuth();
        $MemberRechargeInit       = new \init\MemberRechargeInit();//充值管理   (ps:InitController)
        $MemberRechargeModel      = new \initmodel\MemberRechargeModel(); //充值管理   (ps:InitModel)
        $MemberRechargeOrderModel = new \initmodel\MemberRechargeOrderModel(); //充值订单   (ps:InitModel)
        $MemberRechargeOrderInit  = new \init\MemberRechargeOrderInit();//充值订单    (ps:InitController)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        if ($params["recharge_id"]) {
            $where    = [];
            $where[]  = ['id', '=', $params["recharge_id"]];
            $recharge = $MemberRechargeInit->get_find($where);
            if (empty($recharge)) $this->error("非法操作!");
        }


        $params['openid']        = $this->openid;
        $params['user_id']       = $this->user_id;
        $order_num               = $this->get_only_num('member_recharge_order');
        $params['order_num']     = $order_num;
        $params['amount']        = $recharge['price'] ?? $params['balance'];
        $params['balance']       = $recharge['balance'] ?? $params['balance'];
        $params['give_balance']  = $recharge['give_balance'] ?? 0;
        $params['total_balance'] = ($recharge['balance'] ?? $params['balance']) + ($recharge['give_balance'] ?? 0);

        $result = $MemberRechargeOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试');

        $this->success('请支付', ['order_num' => $order_num, 'order_type' => 20]);
    }


}
