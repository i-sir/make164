<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopOrderAddress",
 *     "name_underline"          =>"shop_order_address",
 *     "controller_name"         =>"ShopOrderAddress",
 *     "table_name"              =>"shop_order_address",
 *     "remark"                  =>"技师定位"
 *     "api_url"                 =>"/api/wxapp/shop_order_address/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-12-06 16:02:05",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopOrderAddressController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/shop_order_address/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/shop_order_address/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopOrderAddressController extends AuthController
{


    public function initialize()
    {
        //技师定位

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_order_address/index
     * https://dzam164.wxselling.net/api/wxapp/shop_order_address/index
     */
    public function index()
    {
        $ShopOrderAddressInit  = new \init\ShopOrderAddressInit();//技师定位   (ps:InitController)
        $ShopOrderAddressModel = new \initmodel\ShopOrderAddressModel(); //技师定位   (ps:InitModel)

        $result = [];

        $this->success('技师定位-接口请求成功', $result);
    }


    /**
     * 技师定位(技师身份请求)  添加
     * @OA\Post(
     *     tags={"技师定位"},
     *     path="/wxapp/shop_order_address/add_address",
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
     *    @OA\Parameter(
     *         name="operate",
     *         in="query",
     *         description="操作说明 ('receive' => '已接单', 'depart' => '已出发', 'reach' => '已到达', 'start' => '开始服务', 'done' => '结束服务', 'cancel' => '取消订单', 'alarm' => '报警')  ",
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
     *         name="lng",
     *         in="query",
     *         description="经度",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="纬度",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_order_address/add_address
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_order_address/add_address
     *   api:  /wxapp/shop_order_address/add_address
     *   remark_name: 技师定位(技师身份请求) 添加
     *
     */
    public function add_address()
    {
        $this->checkAuth();
        $ShopOrderAddressInit  = new \init\ShopOrderAddressInit();//技师定位    (ps:InitController)
        $ShopOrderAddressModel = new \initmodel\ShopOrderAddressModel(); //技师定位   (ps:InitModel)

        $params = $this->request->param();

        if ($this->user_info['identity_type'] != 'technician') $this->error('非法操作');
        if (empty($params['lng']) || empty($params['lat'])) $this->error('经纬度参数缺失');


        $params['technician_id'] = $this->user_id;
        $params['phone']         = $this->user_info['phone'];
        $params['nickname']      = $this->user_info['nickname'];
        $params['lnglat']        = $params['lat'] . ',' . $params['lng'];
        $params['create_time']   = time();

        //经纬度转地址
        $address_info      = $this->reverse_address($params['lnglat']);
        $params['address'] = $address_info['result']['address'];

        $result = $ShopOrderAddressModel->strict(false)->insert($params);
        if (!$result) $this->error('添加失败');


        //报警需要通知管理员
        if ($params['operate'] == 'alarm') {
            $MemberModel           = new \initmodel\MemberModel();//用户管理
            $SendTempMsgController = new SendTempMsgController(); //发送模板消息

            //公众号模板消息,模板id
            $official_id = cmf_config('alarm_official_account_notice');

            //用户id,使用逗号隔开
            $administrator = cmf_config('alarm_notification_administrator');


            //通知内容
            $send_data = [
                'thing2' => ['value' => $this->user_info['nickname']],
                'thing6' => ['value' => $SendTempMsgController->processString($params['address'])],
                'time3'  => ['value' => date('Y-m-d H:i:s')],
            ];


            $user_list = $MemberModel->where('id', 'in', $this->getParams($administrator))->select();
            if ($user_list) {
                foreach ($user_list as $user_info) {
                    if ($user_info['openid']) $SendTempMsgController->sendTempMsg($user_info['openid'], $official_id, $send_data);
                }
            }
        }


        $this->success("添加成功");
    }


}
