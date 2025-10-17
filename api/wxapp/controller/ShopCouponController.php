<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopCoupon",
 *     "name_underline"          =>"shop_coupon",
 *     "controller_name"         =>"ShopCoupon",
 *     "table_name"              =>"shop_coupon",
 *     "remark"                  =>"优惠券"
 *     "api_url"                 =>"/api/wxapp/shop_coupon/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-07-18 19:03:15",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopCouponController();
 *     "test_environment"        =>"http://makeTemplate.ikun/api/wxapp/shop_coupon/index",
 *     "official_environment"    =>"https://ljh.wxselling.net/api/wxapp/shop_coupon/index",
 * )
 */


use initmodel\MemberModel;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopCouponController extends AuthController
{
    public function initialize()
    {
        //优惠券

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_coupon/index
     * https://ljh.wxselling.net/api/wxapp/shop_coupon/index
     */
    public function index()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        $result = [];

        $this->success('优惠券-接口请求成功', $result);
    }


    /**
     * 优惠券列表
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/find_shop_coupon_list",
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
     *         in="header",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_coupon/find_shop_coupon_list
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_coupon/find_shop_coupon_list
     *   api:  /wxapp/shop_coupon/find_shop_coupon_list
     *   remark_name: 优惠券 列表
     *
     */
    public function find_shop_coupon_list()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['end_time', '>', time()];
        if ($params["keyword"]) $where[] = ["name", "like", "%{$params['keyword']}%"];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopCouponInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 优惠券详情
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/find_coupon",
     *
     *
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="优惠券id",
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
     *         in="header",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_coupon/find_coupon
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_coupon/find_coupon
     *   api:  /wxapp/shop_coupon/find_coupon
     *   remark_name: 优惠券详情
     *
     */
    public function find_coupon()
    {
        $ShopCouponInit  = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopCouponInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 领取优惠券
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/add_coupon",
     *
     *
     *
     *     @OA\Parameter(
     *         name="coupon_id",
     *         in="query",
     *         description="优惠券id",
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
     *         in="header",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_coupon/add_coupon
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_coupon/add_coupon
     *   api:  /wxapp/shop_coupon/add_coupon
     *   remark_name: 领取优惠券
     *
     */
    public function add_coupon()
    {
        $this->checkAuth();

        $ShopCouponUserInit  = new \init\ShopCouponUserInit();//优惠券领取记录   (ps:InitController)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopCouponInit      = new \init\ShopCouponInit();//优惠券   (ps:InitController)
        $ShopCouponModel     = new \initmodel\ShopCouponModel(); //优惠券   (ps:InitModel)


        $params = $this->request->param();

        //优惠券id
        $newcomer_coupon = cmf_config('newcomer_coupon');


        $map         = [];
        $map[]       = ['id', '=', $params['coupon_id']];
        $coupon_info = $ShopCouponModel->where($map)->find();
        if (empty($coupon_info)) $this->error("优惠券不存在!");


        $map2      = [];
        $map2[]    = ['user_id', '=', $this->user_id];
        $map2[]    = ['coupon_id', '=', $params['coupon_id']];
        $is_coupon = $ShopCouponUserModel->where($map2)->find();
        if ($is_coupon) $this->error("您已领取过该优惠券!");

        //如果新人优惠券,更改个人信息
        if ($params['coupon_id'] == $newcomer_coupon) {
            MemberModel::where('id', '=', $this->user_id)->update([
                'is_coupon'   => 1,
                'update_time' => time(),
            ]);
        }

        //插入记录
        $result = $ShopCouponUserModel->strict(false)->insert([
            'user_id'     => $this->user_id,
            'coupon_id'   => $params['coupon_id'],
            'name'        => $coupon_info['name'],
            'full_amount' => $coupon_info['full_amount'],
            'amount'      => $coupon_info['amount'],
            'end_time'    => $coupon_info['end_time'],
            'create_time' => time(),
        ]);


        if (empty($result)) $this->error("失败请重试!");

        $this->success("领取成功!");

    }


    /**
     * 已领取优惠列表
     * @OA\Post(
     *     tags={"优惠券"},
     *     path="/wxapp/shop_coupon/my_coupon_list",
     *
     *
     *
     *     @OA\Parameter(
     *         name="amount",
     *         in="query",
     *         description="金额筛选",
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
     *         in="header",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_coupon/my_coupon_list
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_coupon/my_coupon_list
     *   api:  /wxapp/shop_coupon/my_coupon_list
     *   remark_name: 已领取优惠列表
     *
     */
    public function my_coupon_list()
    {
        $this->checkAuth();

        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)

        $params = $this->request->param();

        $map   = [];
        $map[] = ['user_id', '=', $this->user_id];
        $map[] = ['used', '=', 1];
        $map[] = ['end_time', '>', time()];
        if ($params['amount']) $map[] = ['full_amount', '<=', $params['amount']];

        $result = $ShopCouponUserModel->where($map)->select();

        if (empty($result)) $this->error("失败请重试!");

        $this->success("获取成功!", $result);
    }

}
