<?php
// +----------------------------------------------------------------------
// | 会员中心
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use initmodel\BalanceModel;
use initmodel\PointModel;
use think\facade\Db;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');


error_reporting(0);


class MemberController extends AuthController
{
    public function initialize()
    {
        parent::initialize();//初始化方法

    }

    /**
     * 测试用
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/index
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/index
     *   api: /wxapp/member/index
     *   remark_name: 测试用
     *
     */
    public function index()
    {
        $MemberInit = new \init\MemberInit();//用户管理

        $map                     = [];
        $map[]                   = ['id', '>', 99999];
        $params['InterfaceType'] = 'api';
        $result                  = $MemberInit->get_list_paginate($map, $params);

        $this->success('请求成功');
    }


    /**
     * 查询会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_member",
     *
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/find_member
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/find_member
     *   api: /wxapp/member/find_member
     *   remark_name: 查询会员信息
     *
     */
    public function find_member()
    {
        $this->checkAuth();
        //查询会员信息
        $result = $this->getUserInfoByOpenid($this->openid);

        $this->success("请求成功!", $result);
    }


    /**
     * 更新会员信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/update_member",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="nickname",
     *         in="query",
     *         description="昵称",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="avatar",
     *         in="query",
     *         description="头像",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *      @OA\Parameter(
     *         name="used_pass",
     *         in="query",
     *         description="旧密码,如需要传,不需要请勿传",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *     @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="更改密码,如需要传,不需要请勿传",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/update_member
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/update_member
     *   api: /wxapp/member/update_member
     *   remark_name: 更新会员信息
     *
     */
    public function update_member()
    {
        $this->checkAuth();

        $MemberModel = new \initmodel\MemberModel();//用户管理


        $params                = $this->request->param();
        $params['update_time'] = time();
        $member                = $this->getUserInfoByOpenid($this->openid);


        //        $result = $this->validate($params, 'Member');
        //        if ($result !== true) $this->error($result);


        if (empty($member)) $this->error("该会员不存在!");
        if ($member['pid']) unset($params['pid']);


        //修改密码
        if ($params['pass']) {
            if (!cmf_compare_password($params['used_pass'], $member['pass'])) $this->error('旧密码错误');
            $params['pass'] = cmf_password($params['pass']);
        }

        $result = $MemberModel->where('id', $member['id'])->strict(false)->update($params);
        if ($result) {
            $result = $this->getUserInfoByOpenid($this->openid);
            $this->success("保存成功!", $result);
        } else {
            $this->error("保存失败!");
        }
    }


    /**
     * 账户(佣金)变动明细
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_point_list",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="2023-04-05",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="2023-04-05",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/find_point_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/find_point_list
     *   api: /wxapp/member/find_point_list
     *   remark_name: 账户(佣金)变动明细
     *
     */
    public function find_point_list()
    {
        $this->checkAuth();

        $params  = $this->request->param();
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = ['identity_type', '=', $this->user_info['identity_type'] ?? 'member'];
        $where[] = $this->getBetweenTime($params['begin_time'], $params['end_time']);

        $result = PointModel::where($where)
            ->order("id desc")
            ->paginate($params['page_size'])
            ->each(function ($item, $key) {

                if ($item['type'] == 2) {
                    $item['price'] = -$item['price'];
                } else {
                    $item['price'] = '+' . $item['price'];
                }

                return $item;
            });

        $this->success("请求成功！", $result);
    }


    /**
     * 账户(余额)变动明细
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_balance_list",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Parameter(
     *         name="begin_time",
     *         in="query",
     *         description="2023-04-05",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="end_time",
     *         in="query",
     *         description="2023-04-05",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/find_balance_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/find_balance_list
     *   api: /wxapp/member/find_balance_list
     *   remark_name: 账户(余额)变动明细
     *
     */
    public function find_balance_list()
    {
        $this->checkAuth();

        $params  = $this->request->param();
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = ['identity_type', '=', $this->user_info['identity_type'] ?? 'member'];
        $where[] = $this->getBetweenTime($params['begin_time'], $params['end_time']);


        $result = BalanceModel::where($where)
            ->order("id desc")
            ->paginate($params['page_size'])
            ->each(function ($item, $key) {
                if ($item['type'] == 2) {
                    $item['price'] = -$item['price'];
                } else {
                    $item['price'] = '+' . $item['price'];
                }
                return $item;
            });

        $this->success("请求成功！", $result);
    }


    /**
     * 团队列表查询
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_team_list",
     *
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="类型:1技师 2用户",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/find_team_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/find_team_list
     *   api: /wxapp/member/find_team_list
     *   remark_name: 团队列表查询
     *
     */
    public function find_team_list()
    {
        $this->checkAuth();
        $MemberModel     = new \initmodel\MemberModel();//用户管理
        $ShopOrderModel  = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $params  = $this->request->param();
        $user_id = $this->user_id;
        if ($params['user_id']) $user_id = $params['user_id'];


        //技师
        if ($params['type'] == 1) {
            $result = $TechnicianModel
                ->where("pid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel, $ShopOrderModel) {
                    if ($item['avatar']) $item['avatar'] = $this->getImagesUrl($item['avatar'])[0];

                    //总佣金数
                    $map                      = [];
                    $map[]                    = ['order_type', '=', 600];
                    $map[]                    = ['child_id', '=', $item['id']];
                    $item['total_commission'] = PointModel::where($map)->sum('price');

                    return $item;
                });
        }


        //用户
        if ($params['type'] == 2) {
            $result = $MemberModel
                ->where("pid", $user_id)
                ->field('*')
                ->order("id desc")
                ->paginate(10)
                ->each(function ($item, $key) use ($MemberModel, $ShopOrderModel) {
                    $item['avatar'] = cmf_get_asset_url($item['avatar']);

                    //总佣金数
                    $map                      = [];
                    $map[]                    = ['order_type', '=', 500];
                    $map[]                    = ['child_id', '=', $item['id']];
                    $item['total_commission'] = PointModel::where($map)->sum('price');

                    //总订单数
                    $map2                = [];
                    $map2[]              = ['user_id', '=', $item['id']];
                    $map2[]              = ['status', 'in', [5, 8]];
                    $item['total_order'] = $ShopOrderModel->where($map2)->count();

                    return $item;
                });
        }


        $this->success("请求成功！", $result);
    }


    /**
     * 团队订单列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/find_team_order_list",
     *
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="下级用户id",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/find_team_order_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/find_team_order_list
     *   api: /wxapp/member/find_team_order_list
     *   remark_name: 团队订单列表
     *
     */
    public function find_team_order_list()
    {
        $ShopOrderInit  = new \init\ShopOrderInit();//订单管理   (ps:InitController)
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)

        //参数
        $params = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['user_id', '=', $params['user_id']];
        $where[] = ["status", "in", [5, 8]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型

        $result = $ShopOrderInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 获客海报&分享&推广二维码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @OA\Post(
     *     tags={"会员中心模块"},
     *     path="/wxapp/member/poster",
     *
     *
     *     @OA\Parameter(
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/member/poster
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/member/poster
     *   api: /wxapp/member/poster
     *   remark_name: 获客海报&分享&推广二维码
     *
     */
    public function poster()
    {
        $this->checkAuth();

        $Qr               = new \init\QrInit();
        $PublicController = new PublicController();
        $MemberModel      = new \initmodel\MemberModel();//用户管理


        //邀请链接
        $invitation_link = cmf_config('invitation_link');

        //完整链接
        $scheme         = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';// 获取协议（http或https）
        $invitation_url = $scheme . $_SERVER['HTTP_HOST'] . $invitation_link;


        //邀请码提示
        $invitation_code_prompt_text = cmf_config('invitation_code_prompt_text');


        //分销+二维码图
        $image = $this->user_info['invite_image'];
        if (empty($image)) {
            $image_url = $Qr->get_qr($invitation_url . $this->user_info['invite_code']);
            $image     = $Qr->applet_share($image_url, $this->user_info['invite_code'], $invitation_code_prompt_text);
            //$MemberModel->where('id', '=', $this->user_id)->update(['invite_image' => $image]);
        }


        $this->success('请求成功', cmf_get_asset_url($image));
    }


}