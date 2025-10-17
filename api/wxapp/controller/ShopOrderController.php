<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopOrder",
 *     "name_underline"          =>"shop_order",
 *     "controller_name"         =>"ShopOrder",
 *     "table_name"              =>"shop_order",
 *     "remark"                  =>"订单管理"
 *     "api_url"                 =>"/api/wxapp/shop_order/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-30 16:18:12",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopOrderController();
 *     "test_environment"        =>"http://makeTemplate.ikun/api/wxapp/shop_order/index",
 *     "official_environment"    =>"https://ljh.wxselling.net/api/wxapp/shop_order/index",
 * )
 */


use initmodel\BalanceModel;
use initmodel\MemberModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopOrderController extends AuthController
{

    public function initialize()
    {
        //订单管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_order/index
     * https://ljh.wxselling.net/api/wxapp/shop_order/index
     */
    public function index()
    {
        $ShopOrderInit  = new \init\ShopOrderInit();//订单管理   (ps:InitController)
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        $result = [];

        $this->success('订单管理-接口请求成功', $result);
    }


    /**
     * 用户订单管理 列表
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/find_order_list",
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
     *         name="status",
     *         in="query",
     *         description="状态:1待付款,2待接单,3待服务,4进行中,5待评价,8已完成,10已取消",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/find_order_list
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/find_order_list
     *   api:  /wxapp/shop_order/find_order_list
     *   remark_name: 订单管理 列表
     *
     */
    public function find_order_list()
    {
        $this->checkAuth();
        $ShopOrderInit  = new \init\ShopOrderInit();//订单管理   (ps:InitController)
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["order_num|username|phone", "like", "%{$params['keyword']}%"];

        //用户订单列表
        if ($this->user_info['identity_type'] == 'member') {
            $where[] = ['user_id', '=', $this->user_id];
            if ($params["status"]) $where[] = ["status", "in", $ShopOrderInit->status_where_member_api[$params["status"]]];
        }


        //技师订单列表
        if ($this->user_info['identity_type'] == 'technician') {
            $where[] = ['technician_id', '=', $this->user_id];
            $where[] = ['status', 'not in', [1, 10]];
            if ($params["status"]) $where[] = ["status", "in", $ShopOrderInit->status_where_technician_api[$params["status"]]];
        }


        //查询数据
        $params["InterfaceType"] = "api";//接口类型

        $result = $ShopOrderInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 订单管理 详情
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/find_order",
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
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num 二选一",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/find_order
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/find_order
     *   api:  /wxapp/shop_order/find_order
     *   remark_name: 订单管理 详情
     *
     */
    public function find_order()
    {
        $this->checkAuth();

        $ShopOrderInit  = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params                  = $this->request->param();
        $params["user_id"]       = $this->user_id;
        $params["identity_type"] = $this->user_info['identity_type'];

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopOrderInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 获取价格
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/get_amount",
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
     *
     *    @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="地址id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
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
     *    @OA\Parameter(
     *         name="travel_type",
     *         in="query",
     *         description="出行方式",
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
     *         name="begin_time",
     *         in="query",
     *         description="开始时间",
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
     *         name="goods_ids",
     *         in="query",
     *         description="商品ids  数组",
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
     *         name="count",
     *         in="query",
     *         description="数量  数组",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/get_amount
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/get_amount
     *   api:  /wxapp/shop_order/get_amount
     *   remark_name: 计算价格
     *
     */
    public function get_amount()
    {
        $this->checkAuth();

        $ShopAddressModel    = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopGoodsModel      = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)
        $TechnicianModel     = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $RoadPriceModel      = new \initmodel\RoadPriceModel(); //路费配置   (ps:InitModel)


        //路费介绍
        $introduction_expenses = cmf_config('introduction_to_travel_expenses');
        //打车的起步价
        $starting_price = cmf_config('starting_price');
        //超过1公里加价n元
        $per_kilometer = cmf_config('per_kilometer');
        //n公里内免费出行
        $free_travel_kilometers = cmf_config('free_travel_kilometers');


        $params = $this->request->param();


        //技师
        $technician_info = $TechnicianModel->where('id', '=', $params['technician_id'])->find();
        if (empty($technician_info)) $this->error('技师不存在');

        //地址是否存在
        $address_info = $ShopAddressModel->where('id', '=', $params['address_id'])->find();
        if (empty($address_info)) $this->error('地址不存在');


        //优惠券信息
        if ($params['coupon_id']) {
            $coupon_info = $ShopCouponUserModel->where('id', '=', $params['coupon_id'])->find();
            if (empty($coupon_info)) $this->error('优惠券信息错误!');
            if ($coupon_info['used'] != 1) $this->error('优惠券状态错误!');
        }


        //此店铺购物车id
        $goods_name     = '';
        $goods_ids      = '';
        $amount         = 0;//实际支付金额
        $goods_amount   = 0;//商品金额
        $freight_amount = 0;//打车费
        $coupon_amount  = 0;//优惠金额
        $total_amount   = 0;//订单总金额,实际支付金额+优惠金额+车费
        $total_count    = 0;//总数量
        //订单详情
        $goods_list = $ShopGoodsModel->where('id', 'in', $params['goods_ids'])->select();

        /**计算商品价格**/
        foreach ($goods_list as $k => $value) {
            $count        = $params['count'][$k];
            $goods_amount += round($value['price'] * $count, 2);//商品总金额
        }

        /**计算车费**/
        $lng1 = $address_info['lng'];
        $lat1 = $address_info['lat'];
        $lng2 = $technician_info['lng'];
        $lat2 = $technician_info['lat'];
        $km   = $this->getdistance($lng1, $lat1, $lng2, $lat2);//计算两经纬度距离,返回km


        //技师路费 = 起步价+每公里加价*距离
        if ($km >= $free_travel_kilometers) $freight_amount = round($per_kilometer * $km + $starting_price, 2);


        /**计算优惠券价格**/
        //如有优惠券核销一下(给每个订单,单独计算优惠)
        if ($params['coupon_id'] && $coupon_info) $coupon_amount = $coupon_info['amount'];


        //返回结果
        $result                   = [];
        $result['total_amount']   = round($goods_amount + $freight_amount, 2);//总价格
        $result['goods_amount']   = $goods_amount;//商品金额
        $result['freight_amount'] = $freight_amount;//打车费
        $result['freight_text']   = $introduction_expenses;//打车费,介绍
        $result['coupon_amount']  = $coupon_amount;//优惠券
        $result['km']             = $km;//km
        $result['amount']         = round($goods_amount + $freight_amount - $coupon_amount, 2);//支付金额


        $this->success('请求成功', $result);
    }


    /**
     * 下单
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/add_order",
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
     *
     *    @OA\Parameter(
     *         name="address_id",
     *         in="query",
     *         description="地址id",
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
     *         name="km",
     *         in="query",
     *         description="距离",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
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
     *    @OA\Parameter(
     *         name="travel_type",
     *         in="query",
     *         description="出行方式",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="remark",
     *         in="query",
     *         description="备注",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="开始时间",
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
     *         name="goods_ids",
     *         in="query",
     *         description="商品ids  数组",
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
     *         name="count",
     *         in="query",
     *         description="数量  数组",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/add_order
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/add_order
     *   api:  /wxapp/shop_order/add_order
     *   remark_name: 订单管理 编辑&添加
     *
     */
    public function add_order()
    {
        $this->checkAuth();


        // 启动事务
        Db::startTrans();


        $ShopOrderModel       = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel(); //订单详情   (ps:InitModel)
        $ShopAddressModel     = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $ShopCouponUserModel  = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopGoodsModel       = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)
        $TechnicianModel      = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $ShopOrderSaveModel   = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $RoadPriceModel       = new \initmodel\RoadPriceModel(); //路费配置   (ps:InitModel)
        $InitController       = new \api\wxapp\controller\InitController();


        //自动取消订单分钟
        $automatically_cancel_orders = cmf_config('automatically_cancel_orders');
        //打车的起步价
        $starting_price = cmf_config('starting_price');
        //超过1公里加价n元
        $per_kilometer = cmf_config('per_kilometer');
        //n公里内免费出行
        $free_travel_kilometers = cmf_config('free_travel_kilometers');
        //下级下单订单佣金(%)
        $lower_level_commission = cmf_config('lower_level_commission');


        //参数
        $params                     = $this->request->param();
        $params["user_id"]          = $this->user_id;
        $order_num                  = $this->get_only_num('shop_order');
        $params["order_num"]        = $order_num;
        $begin_time                 = strtotime($params['begin_time']);
        $params['auto_cancel_time'] = time() + ($automatically_cancel_orders * 60);
        $params['pid']              = $this->user_info['pid'];//上级用户

        //技师
        $technician_info = $TechnicianModel->where('id', '=', $params['technician_id'])->find();
        if (empty($technician_info)) $this->error('技师不存在');

        //地址是否存在
        $address_info = $ShopAddressModel->where('id', '=', $params['address_id'])->find();
        if (empty($address_info)) $this->error('地址不存在');
        $params['username']      = $address_info['username'];
        $params['phone']         = $address_info['phone'];
        $params['province']      = $address_info['province'];
        $params['city']          = $address_info['city'];
        $params['county']        = $address_info['county'];
        $params['province_code'] = $address_info['province_code'];
        $params['county_code']   = $address_info['county_code'];
        $params['lng']           = $address_info['lng'];
        $params['lat']           = $address_info['lat'];
        $params['lnglat']        = $address_info['lnglat'];
        $params['address']       = $address_info['address'];

        //优惠券信息
        if ($params['coupon_id']) {
            $coupon_info = $ShopCouponUserModel->where('id', '=', $params['coupon_id'])->find();
            if (empty($coupon_info)) $this->error('优惠券信息错误!');
            if ($coupon_info['used'] != 1) $this->error('优惠券状态错误!');
        }

        //此店铺购物车id
        $goods_name     = '';
        $goods_ids      = '';
        $amount         = 0;//实际支付金额
        $goods_amount   = 0;//商品金额
        $freight_amount = 0;//打车费
        $coupon_amount  = 0;//优惠金额
        $total_amount   = 0;//订单总金额,实际支付金额+优惠金额+车费
        $total_count    = 0;//总数量
        $total_minute   = 0;//总分钟数
        $order_detail   = [];

        //订单详情
        $goods_list = $ShopGoodsModel->where('id', 'in', $params['goods_ids'])->select();

        foreach ($goods_list as $k => $value) {
            $count                            = $params['count'][$k];
            $order_detail[$k]['user_id']      = $this->user_id;
            $order_detail[$k]['goods_id']     = $value['id'];
            $order_detail[$k]['minute']       = $value['minute'];
            $order_detail[$k]['introduce']    = $value['introduce'];
            $order_detail[$k]['sell_count']   = $value['sell_count'];
            $order_detail[$k]['goods_name']   = $value['goods_name'];
            $order_detail[$k]['goods_price']  = $value['price'];
            $order_detail[$k]['old_price']    = $value['old_price'];
            $order_detail[$k]['image']        = cmf_get_asset_url($value['image']);
            $detail_total_minute              = $value['minute'] * $count;
            $order_detail[$k]['total_minute'] = $detail_total_minute;
            $detail_goods_amount              = round($value['price'] * $count, 2);
            $order_detail[$k]['total_amount'] = $detail_goods_amount;
            $order_detail[$k]['count']        = $count;
            $order_detail[$k]['order_num']    = $order_num;
            $order_detail[$k]['create_time']  = time();
            $goods_amount                     += $detail_goods_amount;//商品总金额
            $total_minute                     += $detail_total_minute;//总分钟数
            $total_count                      += $count;//总数量
            $goods_name                       .= $value['goods_name'] . ',';//商品名称
            $goods_ids                        .= $value['id'] . ',';//商品id
        }


        //计算出结束时间
        $end_time = $begin_time + ($total_minute * 60);

        /**检测技师这个时间段是否被占用**/
        $is_make = $InitController->isTimeSlotAvailable($technician_info['id'], $begin_time, $end_time);//下单
        if ($is_make) $this->error('时间冲突不可以预约');

        /**计算车费**/
        /**计算车费**/
        $lng1 = $address_info['lng'];
        $lat1 = $address_info['lat'];
        $lng2 = $technician_info['lng'];
        $lat2 = $technician_info['lat'];
        $km   = $this->getdistance($lng1, $lat1, $lng2, $lat2);//计算两经纬度距离,返回km


        //技师路费 = 起步价+每公里加价*距离
        if ($km >= $free_travel_kilometers) $freight_amount = round($per_kilometer * $km + $starting_price, 2);


        //优惠券
        $coupon_amount = 0;
        //如有优惠券核销一下(给每个订单,单独计算优惠)
        if ($params['coupon_id'] && $coupon_info) {
            //核销优惠券
            $ShopCouponUserModel->where('id', '=', $params['coupon_id'])->strict(false)->update([
                'used'        => 2,
                'order_num'   => $order_num,
                'update_time' => time(),
                'used_time'   => time(),
            ]);
            $coupon_amount = $coupon_info['amount'];
        }

        //订单总金额 = 商品总金额 + 车费
        $total_amount = round($goods_amount + $freight_amount, 2);

        //实际支付金额 =  商品总金额 + 车费   - 优惠金额
        $amount = round($goods_amount + $freight_amount - $coupon_amount, 2);


        //订单基础信息
        $params['goods_ids']        = rtrim($goods_ids, ',');//商品id
        $params['goods_name']       = rtrim($goods_name, ',');//商品名称
        $params['goods_amount']     = $goods_amount;//商品总金额
        $params['total_commission'] = round($goods_amount * $lower_level_commission / 100, 2);//总佣金
        $params['freight_amount']   = $freight_amount;//打车费
        $params['coupon_amount']    = $coupon_amount;//优惠金额
        $params['total_amount']     = $total_amount;//订单总金额
        $params['amount']           = $amount;//实际支付金额
        $params['total_count']      = $total_count;//总数量
        $params['total_minute']     = $total_minute;//总分钟数
        $params['begin_time']       = $begin_time;//开始时间戳
        $params['end_time']         = $end_time;//结束时间戳
        $params['create_time']      = time();


        //插入订单基础表
        $ShopOrderModel->strict(false)->insert($params);

        //插入订单详情表
        $ShopOrderDetailModel->strict(false)->insertAll($order_detail);


        //技师服务时间,记录
        $ShopOrderSaveModel->strict(false)->insert([
            'technician_id' => $params['technician_id'],
            'begin_time'    => $params['begin_time'],
            'end_time'      => $params['end_time'],
            'order_num'     => $order_num,
            'user_id'       => $this->user_id,
            'create_time'   => time(),
        ]);

        // 提交事务
        Db::commit();


        $this->success('下单成功', ['order_num' => $order_num, 'order_type' => 10]);
    }


    /**
     * 加钟
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/add_clock",
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
     *         name="p_order_num",
     *         in="query",
     *         description="父订单 关联",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="服务开始时间,默认订单结束时间   2025-03-22 18:30",
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
     *         name="goods_ids",
     *         in="query",
     *         description="商品ids  数组",
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
     *         name="count",
     *         in="query",
     *         description="数量  数组",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/add_clock
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/add_clock
     *   api:  /wxapp/shop_order/add_clock
     *   remark_name: 加钟订单
     *
     */
    public function add_clock()
    {

        $this->checkAuth();

        // 启动事务
        Db::startTrans();


        $ShopOrderModel       = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel(); //订单详情   (ps:InitModel)
        $ShopAddressModel     = new \initmodel\ShopAddressModel(); //地址管理  (ps:InitModel)
        $ShopCouponUserModel  = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)
        $ShopGoodsModel       = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)
        $TechnicianModel      = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $ShopOrderSaveModel   = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $InitController       = new \api\wxapp\controller\InitController();
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $SendTempMsgController = new SendTempMsgController();

        $params = $this->request->param();

        //测试数据
        //        $params['goods_ids'] = [7];
        //        $params['count']     = [1];

        $map          = [];
        $map[]        = ['order_num', '=', $params['p_order_num']];
        $p_order_info = $ShopOrderModel->where($map)->find();
        if (empty($p_order_info)) $this->error('订单不存在');


        //自动取消订单分钟
        $automatically_cancel_orders = cmf_config('automatically_cancel_orders');
        //打车的起步价
        $starting_price = cmf_config('starting_price');
        //超过1公里加价n元
        $per_kilometer = cmf_config('per_kilometer');
        //n公里内免费出行
        $free_travel_kilometers = cmf_config('free_travel_kilometers');
        //下级下单订单佣金(%)
        $lower_level_commission = cmf_config('lower_level_commission');

        //基础信息
        $order_num                  = $this->get_only_num('shop_order');
        $params['order_num']        = $order_num;
        $params['user_id']          = $this->user_id;
        $params['type']             = 2;
        $params['create_time']      = time();
        $params['auto_cancel_time'] = time() + ($automatically_cancel_orders * 60);
        $params['pid']              = $this->user_info['pid'];//上级用户
        $params['technician_id']    = $p_order_info['technician_id'];

        //地址信息
        $params['username']      = $p_order_info['username'];
        $params['phone']         = $p_order_info['phone'];
        $params['province']      = $p_order_info['province'];
        $params['city']          = $p_order_info['city'];
        $params['county']        = $p_order_info['county'];
        $params['province_code'] = $p_order_info['province_code'];
        $params['county_code']   = $p_order_info['county_code'];
        $params['lng']           = $p_order_info['lng'];
        $params['lat']           = $p_order_info['lat'];
        $params['lnglat']        = $p_order_info['lnglat'];
        $params['address']       = $p_order_info['address'];


        //开始服务时间
        if (empty($params['begin_time'])) {
            $begin_time = $p_order_info['end_time'];
        } else {
            $begin_time = strtotime($params['begin_time']);
        }


        //订单详情
        $goods_list = $ShopGoodsModel->where('id', 'in', $params['goods_ids'])->select();

        foreach ($goods_list as $k => $value) {
            $count                            = $params['count'][$k];
            $order_detail[$k]['user_id']      = $this->user_id;
            $order_detail[$k]['goods_id']     = $value['id'];
            $order_detail[$k]['minute']       = $value['minute'];
            $order_detail[$k]['introduce']    = $value['introduce'];
            $order_detail[$k]['sell_count']   = $value['sell_count'];
            $order_detail[$k]['goods_name']   = $value['goods_name'];
            $order_detail[$k]['goods_price']  = $value['price'];
            $order_detail[$k]['old_price']    = $value['old_price'];
            $order_detail[$k]['image']        = cmf_get_asset_url($value['image']);
            $detail_total_minute              = $value['minute'] * $count;
            $order_detail[$k]['total_minute'] = $detail_total_minute;
            $detail_goods_amount              = round($value['price'] * $count, 2);
            $order_detail[$k]['total_amount'] = $detail_goods_amount;
            $order_detail[$k]['count']        = $count;
            $order_detail[$k]['order_num']    = $order_num;
            $order_detail[$k]['create_time']  = time();
            $goods_amount                     += $detail_goods_amount;//商品总金额
            $total_minute                     += $detail_total_minute;//总分钟数
            $total_count                      += $count;//总数量
            $goods_name                       .= $value['goods_name'] . ',';//商品名称
            $goods_ids                        .= $value['id'] . ',';//商品id
        }


        //计算出结束时间
        $end_time = $begin_time + ($total_minute * 60);

        /**检测技师这个时间段是否被占用**/
        $is_make = $InitController->isTimeSlotAvailable($p_order_info['technician_id'], $begin_time, $end_time);//加钟
        if ($is_make) $this->error('时间冲突不可以预约');


        /**计算车费**/
        //        $lng1 = $p_order_info['lng'];
        //        $lat1 = $p_order_info['lat'];
        //        $lng2 = $technician_info['lng'];
        //        $lat2 = $technician_info['lat'];
        //        $km   = $this->getdistance($lng1, $lat1, $lng2, $lat2);//计算两经纬度距离,返回km
        //
        //
        //        //技师路费 = 起步价+每公里加价*距离
        //        if ($km >= $free_travel_kilometers) $freight_amount = round($per_kilometer * $km + $starting_price, 2);

        //路费不在计算
        $freight_amount = 0;

        //优惠券   关闭优惠券金额
        $coupon_amount = 0;
        //如有优惠券核销一下(给每个订单,单独计算优惠)
        //        if ($params['coupon_id'] && $coupon_info) {
        //            //核销优惠券
        //            $ShopCouponUserModel->where('id', '=', $params['coupon_id'])->strict(false)->update([
        //                'used'        => 2,
        //                'order_num'   => $order_num,
        //                'update_time' => time(),
        //                'used_time'   => time(),
        //            ]);
        //            $coupon_amount = $coupon_info['amount'];
        //        }

        //订单总金额 = 商品总金额 + 车费
        $total_amount = round($goods_amount + $freight_amount, 2);

        //实际支付金额 =  商品总金额 + 车费   - 优惠金额
        $amount = round($goods_amount + $freight_amount - $coupon_amount, 2);


        //订单基础信息
        $params['goods_ids']        = rtrim($goods_ids, ',');//商品id
        $params['goods_name']       = rtrim($goods_name, ',');//商品名称
        $params['goods_amount']     = $goods_amount;//商品总金额
        $params['total_commission'] = round($goods_amount * $lower_level_commission / 100, 2);//总佣金
        $params['freight_amount']   = $freight_amount;//打车费
        $params['coupon_amount']    = $coupon_amount;//优惠金额
        $params['total_amount']     = $total_amount;//订单总金额
        $params['amount']           = $amount;//实际支付金额
        $params['total_count']      = $total_count;//总数量
        $params['total_minute']     = $total_minute;//总分钟数
        $params['begin_time']       = $begin_time;//开始时间戳
        $params['end_time']         = $end_time;//结束时间戳
        $params['create_time']      = time();


        //插入订单基础表
        $ShopOrderModel->strict(false)->insert($params);

        //插入订单详情表
        $ShopOrderDetailModel->strict(false)->insertAll($order_detail);


        //技师服务时间,记录
        $ShopOrderSaveModel->strict(false)->insert([
            'technician_id' => $p_order_info['technician_id'],
            'begin_time'    => $params['begin_time'],
            'end_time'      => $params['end_time'],
            'order_num'     => $order_num,
            'user_id'       => $this->user_id,
            'create_time'   => time(),
        ]);


        // 提交事务
        Db::commit();




        $this->success('下单成功', ['order_num' => $order_num, 'order_type' => 10]);

    }


    /**
     * 取消订单
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/cancel_order",
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
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="cancel_content",
     *         in="query",
     *         description="取消理由",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="cancel_images",
     *         in="query",
     *         description="取消图片 数组",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/cancel_order
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/cancel_order
     *   api:  /wxapp/shop_order/cancel_order
     *   remark_name: 取消订单
     *
     */
    public function cancel_order()
    {
        // 启动事务
        Db::startTrans();


        $this->checkAuth();


        $ShopOrderInit       = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel      = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $Pay                 = new PayController();
        $ShopOrderSaveModel  = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $ShopCouponUserModel = new \initmodel\ShopCouponUserModel(); //优惠券领取记录   (ps:InitModel)


        //参数
        $params = $this->request->param();

        //处理图片
        if ($params['cancel_images']) $params['cancel_images'] = $this->setParams($params['cancel_images']);


        //如超过预约时间n分钟以上且技师已点击接单和已出发按钮需要扣费
        $user_minute = cmf_config('user_exceeded_reserved_minute');


        //误工费订单的n%
        $error_fee = cmf_config('error_fee');

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单错误');
        if (!in_array($order_info['status'], [1, 2, 3, 5, 6, 8, 9, 10, 11])) $this->error('非法操作');


        $params['cancel_time'] = time();
        $params['update_time'] = time();
        $params['status']      = 9;//用户取消


        /** 退款给用户**/
        //退款金额,默认全退
        $amount = $order_info['amount'];
        //检测是否可以免费取消,超过n分钟  技师已出发,或者已到达退钱给技师
        if (($order_info['begin_time'] + $user_minute * 60) < time() && ($order_info['status'] == 6 || $order_info['status'] == 7) && $order_info['pay_time']) {
            //补偿金额
            $compensate_amount = round($order_info['amount'] * ($error_fee / 100), 2);

            //补偿给技师
            $remark = "操作人[用户取消订单,给技师补偿];操作说明[订单补偿:{$compensate_amount};订单号:{$order_info['order_num']}];操作类型[用户取消订单];";//管理备注
            BalanceModel::inc_balance('technician', $order_info['technician_id'], $compensate_amount, '用户取消订单', $remark, $order_info['id'], $order_info['order_num'], 210);
        }

        //退款金额=订单金额-补偿金额
        $refund_amount = round($amount - $compensate_amount, 2);


        //获取支付单号
        $map      = [];
        $map[]    = ['order_num', '=', $order_info['order_num']];
        $map[]    = ['status', '=', 2];
        $pay_info = Db::name('order_pay')->where($map)->find();

        //退款 && 微信退款
        if ($order_info['pay_type'] == 1 && $order_info['pay_time']) {
            $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['pay_num'], $refund_amount, $amount);//用户取消订单 && 部分退款
            $refund_result = $refund_result['data'];
            if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
        }
        //余额退款
        if ($order_info['pay_type'] == 2 && $order_info['pay_time']) {
            $remark = "操作人[用户取消订单];操作说明[同意退款订单:{$order_info['order_num']};金额:{$refund_amount}];操作类型[用户取消订单];";//管理备注
            BalanceModel::inc_balance('member', $order_info['user_id'], $refund_amount, '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);
        }
        $params['refund_time']       = time();//退款时间
        $params['refund_amount']     = $refund_amount;//退款金额
        $params['compensate_amount'] = $compensate_amount;//补偿金额


        //如果使用优惠券将优惠券退回
        if ($order_info['coupon_id']) {
            $ShopCouponUserModel->where('id', '=', $order_info['coupon_id'])->strict(false)->update([
                'used_time'   => 0,
                'used'        => 1,
                'order_num'   => 0,
                'update_time' => time(),
            ]);
        }


        /** 释放技师时间 **/
        $map2   = [];
        $map2[] = ['order_num', '=', $order_info['order_num']];
        $ShopOrderSaveModel->where($map2)->update([
            'status'             => 2,//取消
            'operation_end_time' => time(),
            'update_time'        => time(),
        ]);


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');

        // 提交事务
        Db::commit();


        $this->success('操作成功');
    }


    /**
     * 删除订单
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/delete_order",
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
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/delete_order
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/delete_order
     *   api:  /wxapp/shop_order/delete_order
     *   remark_name: 删除订单
     *
     */
    public function delete_order()
    {
        $this->checkAuth();


        $ShopOrderInit  = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params = $this->request->param();

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单错误');


        $params['delete_time'] = time();
        $params['update_time'] = time();


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success('删除成功');
    }


    /**
     * 服务结束
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/shop_order/accomplish_order",
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
     *    @OA\Parameter(
     *         name="order_num",
     *         in="query",
     *         description="order_num 二选一",
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
     *   test_environment: http://makeTemplate.ikun/api/wxapp/shop_order/accomplish_order
     *   official_environment: https://ljh.wxselling.net/api/wxapp/shop_order/accomplish_order
     *   api:  /wxapp/shop_order/accomplish_order
     *   remark_name: 服务结束
     *
     */
    public function accomplish_order()
    {
        $this->checkAuth();


        $ShopOrderInit      = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $InitController     = new \api\wxapp\controller\InitController(); //基础方法
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $TechnicianModel    = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //参数
        $params = $this->request->param();

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单错误');
        if (!in_array($order_info['status'], [4])) $this->error('非法操作');


        $params['done_time']   = time();
        $params['update_time'] = time();
        $params['status']      = 5;


        //释放技师时间
        $map   = [];
        $map[] = ['order_num', '=', $order_info['order_num']];
        $ShopOrderSaveModel->where($map)->update([
            'status'             => 3,
            'operation_end_time' => time(),
            'update_time'        => time(),
        ]);


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        //发放技师佣金 & 完成订单就发放
        $InitController->send_order_commission($params['order_num']);

        //邀请奖励
        $InitController->send_invitation_commission($params['order_num']);


        //技师服务单数+1
        $TechnicianModel->where('id', '=', $order_info['technician_id'])->inc('sell_count')->update();


        $this->success('操作成功');
    }


    /**
     * 求两个已知经纬度之间的距离
     * @param float $lng1 经度1
     * @param float $lat1 纬度1
     * @param float $lng2 经度2
     * @param float $lat2 纬度2
     * @return float 距离 (单位：km)
     * @edit www.jbxue.com
     **/
    public function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为弧度
        $radLat1 = deg2rad($lat1);
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);

        // 计算两点之间的差值
        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        // 使用 Haversine 公式计算距离
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) +
                cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378137; // 地球半径为6378137米

        return $s / 1000; // 返回距离，单位为千米
    }


}
