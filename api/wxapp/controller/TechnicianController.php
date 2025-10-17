<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Technician",
 *     "name_underline"          =>"technician",
 *     "controller_name"         =>"Technician",
 *     "table_name"              =>"technician",
 *     "remark"                  =>"技师管理"
 *     "api_url"                 =>"/api/wxapp/technician/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-30 15:12:32",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\TechnicianController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/technician/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/technician/index",
 * )
 */


use cmf\lib\Storage;
use initmodel\BalanceModel;
use initmodel\MemberModel;
use plugins\weipay\lib\PayController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class TechnicianController extends AuthController
{


    public function initialize()
    {
        //技师管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/technician/index
     * https://dzam164.wxselling.net/api/wxapp/technician/index
     */
    public function index()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        $result = [];

        $this->success('技师管理-接口请求成功', $result);
    }


    /**
     * 技师管理 列表
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/find_technician_list",
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
     *
     *    @OA\Parameter(
     *         name="goods_id",
     *         in="query",
     *         description="关联服务",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *    @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         description="分类",
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
     *    @OA\Parameter(
     *         name="work_status",
     *         in="query",
     *         description="工作状态:1可服务,2服务中,3已下线",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *    @OA\Parameter(
     *         name="is_free",
     *         in="query",
     *         description="true 免费出行",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/find_technician_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/find_technician_list
     *   api:  /wxapp/technician/find_technician_list
     *   remark_name: 技师管理 列表
     *
     */
    public function find_technician_list()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        $where[] = ['status', '=', 2];
        $where[] = ['is_block', '=', 2];
        if ($params["keyword"]) $where[] = ["nickname|phone|introduce", "like", "%{$params['keyword']}%"];
        if ($params["work_status"]) $where[] = ["work_status", "=", $params["work_status"]];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //关联服务
        if ($params['goods_id']) {
            //只显示上线的人员
            $where[] = ['work_status', '=', 1];
            //关联服务
            $where[] = ['', 'EXP', Db::raw("FIND_IN_SET({$params['goods_id']},goods_ids)")];;
        }

        $field_lat = 'lat';// 数据库字段名 - 纬度  -90°到90°
        $field_lng = 'lng';// 数据库字段名 - 经度  -180°到180°
        $lat       = $params['lat'];// 数据库字段名 - 纬度  -90°到90°
        $lng       = $params['lng'];// 数据库字段名 - 经度  -180°到180°
        if (empty($lat) || empty($lng)) $this->error("经纬度参数错误");
        if (!empty($lat) && !empty($lng)) {
            $field           = "*, (6378.138 * 2 * asin(sqrt(pow(sin(({$field_lng} * pi() / 180 - {$lng} * pi() / 180) / 2),2) + cos({$field_lng} * pi() / 180) * cos({$lng} * pi() / 180) * pow(sin(({$field_lat} * pi() / 180 - {$lat} * pi() / 180) / 2),2))) * 1000) as distance";
            $params['order'] = 'work_status,distance asc,id desc';//上线优先
            $params['field'] = $field;
        } else {
            unset($params['distance']);
            $params['order'] = 'work_status,id desc';//上线优先
            $params['field'] = '*';
        }

        //筛选免费出行 (1km内免费出行)
        if ($params['is_free']) {
            $field           = "(6378.138 * 2 * asin(sqrt(pow(sin(({$field_lng} * pi() / 180 - {$lng} * pi() / 180) / 2),2) + cos({$field_lng} * pi() / 180) * cos({$lng} * pi() / 180) * pow(sin(({$field_lat} * pi() / 180 - {$lat} * pi() / 180) / 2),2))) * 1000)";
            $params['field'] = $field;
        }


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $params["earliest"]      = true;//查看最早预约时间
        $result                  = $TechnicianInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");


        $this->success("请求成功!", $result);
    }


    /**
     * 技师管理 详情
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/find_technician",
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
     *    @OA\Parameter(
     *         name="is_me",
     *         in="query",
     *         description=" true 如查看自己的信息，则传openid",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/find_technician
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/find_technician
     *   api:  /wxapp/technician/find_technician
     *   remark_name: 技师管理 详情
     *
     */
    public function find_technician()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['is_me'] && $this->user_id) $where[] = ["user_id", "=", $this->user_id];


        if (empty($params['is_me'])) {
            $field_lat = 'lat';// 数据库字段名 - 纬度  -90°到90°
            $field_lng = 'lng';// 数据库字段名 - 经度  -180°到180°
            $lat       = $params['lat'];// 数据库字段名 - 纬度  -90°到90°
            $lng       = $params['lng'];// 数据库字段名 - 经度  -180°到180°
            if (!empty($lat) && !empty($lng)) {
                $field           = "(6378.138 * 2 * asin(sqrt(pow(sin(({$field_lng} * pi() / 180 - {$lng} * pi() / 180) / 2),2) + cos({$field_lng} * pi() / 180) * cos({$lng} * pi() / 180) * pow(sin(({$field_lat} * pi() / 180 - {$lat} * pi() / 180) / 2),2))) * 1000)";
                $params['order'] = 'distance asc,id desc';
                $params['field'] = $field;
            } else {
                unset($params['distance']);
                $params['order'] = 'id desc';
                $params['field'] = '*';
            }
            $params["earliest"] = true;//查看最早预约时间
        }

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


    /**
     * 技师管理 编辑&添加
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/edit_technician",
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
     *
     *    @OA\Parameter(
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
     *
     *    @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="性别",
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
     *         description="联系电话",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="密码",
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
     *         name="sms_code",
     *         in="query",
     *         description="验证码",
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
     *         name="sign_image",
     *         in="query",
     *         description="签名",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="invite_code",
     *         in="query",
     *         description="邀请码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="identity_images",
     *         in="query",
     *         description="身份证照片 数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="qualifications_image",
     *         in="query",
     *         description="资质照片",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="introduce",
     *         in="query",
     *         description="介绍",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="goods_ids",
     *         in="query",
     *         description="关联项目  数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="province_id",
     *         in="query",
     *         description="省id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="city_id",
     *         in="query",
     *         description="市id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="county_id",
     *         in="query",
     *         description="区id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="province",
     *         in="query",
     *         description="省name",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="市name",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="county",
     *         in="query",
     *         description="区name",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="province_code",
     *         in="query",
     *         description="省code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="city_code",
     *         in="query",
     *         description="市code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="county_code",
     *         in="query",
     *         description="区code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="address",
     *         in="query",
     *         description="详细地址",
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
     *    @OA\Parameter(
     *         name="lnglat",
     *         in="query",
     *         description="经纬度",
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
     *         name="id",
     *         in="query",
     *         description="id空添加,存在编辑",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/edit_technician
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/edit_technician
     *   api:  /wxapp/technician/edit_technician
     *   remark_name: 技师管理 编辑&添加
     *
     */
    public function edit_technician()
    {
        $this->checkAuth();
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        //参数
        $params = $this->request->param();


        //验证码修改
        if ($params['sms_code']) {
            $sms_code = cmf_check_verification_code($params['phone'], $params['sms_code']);
            if ($sms_code) $this->error($sms_code);
        }

        //加密密码
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);


        $where           = [];//查询技师是否存在
        $where[]         = ['id', '=', $params['id']];
        $technician_info = $TechnicianModel->where($where)->find();
        if (empty($technician_info)) $this->error("该账号不存在,请注册");

        //检测是否拉黑
        if ($technician_info['is_block'] == 1) $this->error("该账号已被拉黑,请联系管理员");


        //更新技师,条件
        $map   = [];
        $map[] = ['id', '=', $technician_info['id']];

        //给id字段初始化
        unset($params['id']);

        //注册,更新 技师信息
        $technician_id = $TechnicianInit->api_edit_post($params, $map);
        if (empty($technician_id)) $this->error("失败请重试!");


        //查询技师信息
        $findTechnicianInfo = $this->getUserInfoByOpenid($technician_info['openid']);


        $this->success("编辑成功", $findTechnicianInfo);
    }


    /**
     * 技师入驻
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/settled_technician",
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
     *
     *    @OA\Parameter(
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
     *
     *    @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="性别",
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
     *         description="联系电话",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="密码",
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
     *         name="sms_code",
     *         in="query",
     *         description="验证码",
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
     *         name="invite_code",
     *         in="query",
     *         description="邀请码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="identity_images",
     *         in="query",
     *         description="身份证照片 数组",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="qualifications_image",
     *         in="query",
     *         description="资质照片",
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
     *         name="id",
     *         in="query",
     *         description="id空添加,存在编辑",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/settled_technician
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/settled_technician
     *   api:  /wxapp/technician/settled_technician
     *   remark_name: 技师入驻
     *
     */
    public function settled_technician()
    {
        $this->checkAuth();
        $TechnicianInit        = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel       = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $InitController        = new InitController();
        $MemberModel           = new \initmodel\MemberModel();//用户管理
        $SendTempMsgController = new SendTempMsgController();


        //参数
        $params           = $this->request->param();
        $params['status'] = 1;
        $params['refuse'] = null;


        //校验
        $result = $this->validate($params, 'Technician');
        if ($result !== true) $this->error($result);
        if (count($params['identity_images']) == 0) $this->error('请上传身份证照片');

        //验证码修改
        if ($params['sms_code']) {
            $sms_code = cmf_check_verification_code($params['phone'], $params['sms_code']);
            if ($sms_code) $this->error($sms_code);
        }


        //加密密码
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);


        $where           = [];//查询技师是否存在
        $map             = [];//技师存在,更新技师条件
        $technician_info = '';
        if ($params['id']) {
            $where[]         = ['id', '=', $params['id']];
            $technician_info = $TechnicianModel->where($where)->find();
        } elseif ($params['phone']) {
            //检测技师是否存在 && 防止重复注册
            $where[]         = ['phone', '=', $params['phone']];
            $technician_info = $TechnicianModel->where($where)->find();
        }

        //检测是否拉黑
        if ($technician_info['is_block'] == 1) $this->error("该账号已被拉黑,请联系管理员");


        //注册
        if (empty($technician_info)) {
            $params["user_id"] = $this->user_id;
            $TechnicianOpenid  = "Technician_" . md5(uniqid() . $params['phone'] ?? cmf_order_sn());
            $params['openid']  = $TechnicianOpenid;
        } else {
            //更新技师,条件
            $map[] = ['id', '=', $technician_info['id']];
            //拿到技师openid
            $TechnicianOpenid = $technician_info['openid'];
        }


        //绑定上级
        if (empty($technician_info['pid']) || empty($technician_info)) $params['pid'] = $this->user_info['pid'];

        //给id字段初始化
        unset($params['id']);

        //注册,更新 技师信息
        $technician_id = $TechnicianInit->api_edit_post($params, $map);
        if (empty($technician_id)) $this->error("失败请重试!");


        if (empty($technician_info["id"])) {
            //更新插入openid表
            $InitController->set_openid($technician_id, $TechnicianOpenid, 'technician');
            $msg = "注册成功,等待审核";
        }
        if (!empty($technician_info["id"])) $msg = "编辑成功,等待审核";


        //查询技师信息
        $findTechnicianInfo = $this->getUserInfoByOpenid($TechnicianOpenid);



        //通知管理
        $template_id   = cmf_config('apply_for_technician_notification');//公众号模板消息,模板id
        $administrator = cmf_config('alarm_notification_administrator');        //用户id,使用逗号隔开
        $user_list     = $MemberModel->where('id', 'in', explode(',', $administrator))->select();
        $send_data     = [
            'thing2'        => ['value' => $findTechnicianInfo['nickname']],
            'phone_number3' => ['value' => $findTechnicianInfo['phone']],
        ];
        if ($user_list) {
            foreach ($user_list as $user_info) {
                if ($user_info['openid']) $SendTempMsgController->sendTempMsg($user_info['openid'], $template_id, $send_data);
            }
        }


        $this->success($msg, $findTechnicianInfo);
    }


    /**
     * 技师管理 删除
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/delete_technician",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/delete_technician
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/delete_technician
     *   api:  /wxapp/technician/delete_technician
     *   remark_name: 技师管理 删除
     *
     */
    public function delete_technician()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //参数
        $params = $this->request->param();

        //删除数据
        $result = $TechnicianInit->delete_post($params["id"]);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功");
    }


    /**
     * 手机密码登录
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/pass_login",
     *
     *
     *
     *
     *
     *    @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="phone",
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
     *         name="pass",
     *         in="query",
     *         description="pass",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/pass_login
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/pass_login
     *   api:  /wxapp/technician/pass_login
     *   remark_name: 手机密码登录
     *
     */
    public function pass_login()
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $InitController  = new InitController();

        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");
        if (empty($params['pass'])) $this->error("密码不能为空！");

        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $TechnicianModel->where($map)->find();
        if (empty($findUserInfo)) $this->error("技师不存在，请先注册！");

        //检测密码是否正确
        if (!cmf_compare_password($params['pass'], $findUserInfo['pass'])) $this->error("密码错误！");

        //每次登陆更新openid
        $TechnicianOpenid = "Technician_" . md5(uniqid() . $params['phone'] ?? cmf_order_sn());


        //更新登录ip,时间,城市
        $TechnicianModel->where($map)->strict(false)->update([
            'update_time' => time(),
            'login_time'  => time(),
            'ip'          => get_client_ip(),
            'openid'      => $TechnicianOpenid,
            'login_city'  => $this->get_ip_to_city(),
        ]);
        $InitController->set_openid($findUserInfo['id'], $TechnicianOpenid, 'technician');


        //查询会员信息
        $findUserInfo = $this->getUserInfoByOpenid($TechnicianOpenid);
        $this->success("登录成功！", $findUserInfo);
    }


    /**
     * 修改密码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/update_pass",
     *
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="手机号码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="pass",
     *         in="query",
     *         description="密码",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="根据验证码修改密码 二选一",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="old_pass",
     *         in="query",
     *         description="根据旧密码修改 二选一",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/update_pass
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/update_pass
     *   api: /wxapp/technician/update_pass
     *   remark_name: 修改密码
     *
     */
    public function update_pass()
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        $params          = $this->request->param();
        $params['phone'] = trim($params['phone']);
        if (empty($params['phone'])) $this->error("手机号不能为空！");


        $map          = [];
        $map[]        = ['phone', '=', $params['phone']];
        $findUserInfo = $TechnicianModel->where($map)->find();
        if (empty($findUserInfo)) $this->error("账号不存在!");

        //旧密码修改检测,是否正确
        if ($params['old_pass'] && !cmf_compare_password($params['old_pass'], $findUserInfo['pass'])) $this->error("旧密码错误！");


        //验证码修改
        if ($params['code']) {
            $result = cmf_check_verification_code($params['phone'], $params['code']);
            if ($result) $this->error($result);
        }


        //更新登录ip,时间,城市,修改密码
        $TechnicianModel->where($map)->strict(false)->update([
            'pass'        => cmf_password($params['pass']),
            'update_time' => time(),
            'login_time'  => time(),
            'ip'          => get_client_ip(),
            'login_city'  => $this->get_ip_to_city(),
        ]);


        //查询会员信息
        $findUserInfo = $this->getUserInfoByOpenid($findUserInfo['openid']);
        $this->success("操作成功！", $findUserInfo);
    }


    /**
     * 接单
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/receive_order",
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
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/receive_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/receive_order
     *   api:  /wxapp/technician/receive_order
     *   remark_name: 接单
     *
     */
    public function receive_order()
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
        if (!in_array($order_info['status'], [2])) $this->error('非法操作');


        $params['receive_time'] = time();
        $params['update_time']  = time();
        $params['status']       = 3;


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success('接单成功');
    }


    /**
     * 出发
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/depart_order",
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
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/depart_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/depart_order
     *   api:  /wxapp/technician/depart_order
     *   remark_name: 出发
     *
     */
    public function depart_order()
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
        if (in_array($order_info['status'], [1, 5, 8, 9, 10, 11, 6, 7])) $this->error('非法操作');


        $params['depart_time'] = time();
        $params['update_time'] = time();
        $params['status']      = 6;


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success('操作成功');
    }


    /**
     * 到达
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/reach_order",
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
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/reach_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/reach_order
     *   api:  /wxapp/technician/reach_order
     *   remark_name: 到达
     *
     */
    public function reach_order()
    {
        $this->checkAuth();


        $ShopOrderInit         = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel        = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $SendTempMsgController = new SendTempMsgController();
        $TechnicianModel       = new \initmodel\TechnicianModel();//技师管理   (ps:InitModel)
        $MemberModel           = new \initmodel\MemberModel();//用户管理

        //参数
        $params = $this->request->param();

        //查询条件
        $where = [];
        if ($params['id']) $where[] = ["id", "=", $params["id"]];
        if ($params['order_num']) $where[] = ["order_num", "=", $params["order_num"]];


        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error('订单错误');
        if (in_array($order_info['status'], [1, 5, 8, 9, 10, 11, 7])) $this->error('非法操作');


        $params['reach_time']  = time();
        $params['update_time'] = time();
        $params['status']      = 7;


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        $technician_info = $TechnicianModel->where('id', '=', $order_info['technician_id'])->find();

        //通知管理员
        $template_id = cmf_config('technician_arrival_notification_administrator');  //公众号模板消息,模板id
        $send_data   = [
            'time4'  => ['value' => date('Y-m-d H:i:s', $order_info['begin_time'])],
            'thing7' => ['value' => $technician_info['nickname']],
            'thing2' => ['value' => $order_info['goods_name']],
        ];
        //通知管理
        $administrator = cmf_config('alarm_notification_administrator');        //用户id,使用逗号隔开
        $user_list     = $MemberModel->where('id', 'in', explode(',', $administrator))->select();
        if ($user_list) {
            foreach ($user_list as $user_info) {
                if ($user_info['openid']) $SendTempMsgController->sendTempMsg($user_info['openid'], $template_id, $send_data);
            }
        }

        $this->success('操作成功');
    }

    /**
     * 开始服务
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/start_order",
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
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/start_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/start_order
     *   api:  /wxapp/technician/start_order
     *   remark_name: 开始服务
     *
     */
    public function start_order()
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
        if (!in_array($order_info['status'], [3, 7])) $this->error('非法操作');


        $params['start_time']  = time();
        $params['update_time'] = time();
        $params['status']      = 4;


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success('操作成功');
    }


    /**
     * 结束服务
     * @OA\Post(
     *     tags={"技师管理"},
     *     path="/wxapp/technician/done_order",
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
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/done_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/done_order
     *   api:  /wxapp/technician/done_order
     *   remark_name: 结束服务
     *
     */
    public function done_order()
    {
        $this->checkAuth();


        $ShopOrderInit         = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel        = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $InitController        = new \api\wxapp\controller\InitController(); //基础方法
        $ShopOrderSaveModel    = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $TechnicianModel       = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $SendTempMsgController = new SendTempMsgController();
        $MemberModel           = new \initmodel\MemberModel();//用户管理


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


        $technician_info = $TechnicianModel->where('id', '=', $order_info['technician_id'])->find();

        //通知管理员
        $template_id = cmf_config('technician_end_service_notice');//模板id
        $send_data   = [
            'time4'   => ['value' => date('Y-m-d H:i:s', $order_info['begin_time'])],
            'thing10' => ['value' => $technician_info['nickname']],
            'thing3'  => ['value' => $order_info['goods_name']],
        ];

        //通知管理
        $administrator = cmf_config('alarm_notification_administrator');        //用户id,使用逗号隔开
        $user_list     = $MemberModel->where('id', 'in', explode(',', $administrator))->select();
        if ($user_list) {
            foreach ($user_list as $user_info) {
                if ($user_info['openid']) $SendTempMsgController->sendTempMsg($user_info['openid'], $template_id, $send_data);
            }
        }

        $this->success('操作成功');
    }


    /**
     * 取消订单(后台审核)
     * @OA\Post(
     *     tags={"用户订单管理"},
     *     path="/wxapp/technician/cancel_order",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/technician/cancel_order
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/technician/cancel_order
     *   api:  /wxapp/technician/cancel_order
     *   remark_name: 取消订单(后台审核)
     *
     */
    public function cancel_order()
    {
        // 启动事务
        Db::startTrans();


        $this->checkAuth();


        $ShopOrderInit      = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $Pay                = new PayController();
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)


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


        $params['cancel_time']   = time();
        $params['update_time']   = time();
        $params['status']        = 11;//用户取消
        $params['cancel_status'] = 1;//审核中


        /** 退款给用户**/
        //退款金额,默认全退
        $amount = $order_info['amount'];
        //检测是否可以免费取消,超过n分钟  技师已出发,或者已到达退钱给技师
        if (($order_info['begin_time'] + $user_minute * 60) < time() && ($order_info['status'] == 6 || $order_info['status'] == 7)) {
            //补偿金额
            $compensate_amount = round($order_info['amount'] * ($error_fee / 100), 2);

            //补偿给技师 &&  后台审核通过给余额
            //$remark = "操作人[用户取消订单,给技师补偿];操作说明[订单补偿:{$compensate_amount};订单号:{$order_info['order_num']}];操作类型[用户取消订单];";//管理备注
            //BalanceModel::inc_balance('technician', $order_info['technician_id'], $compensate_amount, '用户取消订单', $remark, $order_info['id'], $order_info['order_num'], 210);
        }

        //退款金额=订单金额-补偿金额
        $refund_amount = round($amount - $compensate_amount, 2);


        //获取支付单号  && 后台审核通过退款
        //        $map     = [];
        //        $map[]   = ['order_num', '=', $order_info['order_num']];
        //        $map[]   = ['status', '=', 2];
        //        $pay_num = Db::name('order_pay')->where($map)->value('pay_num');
        //
        //        //退款 && 微信退款
        //        if ($order_info['pay_type'] == 1) {
        //            $refund_result = $Pay->wx_pay_refund($pay_num, $refund_amount);
        //            $refund_result = json_decode($refund_result['data'], true);
        //            if (!isset($refund_result['amount'])) $this->error($refund_result['message']);
        //        }
        //        //余额退款
        //        if ($order_info['pay_type'] == 2) {
        //            $remark = "操作人[用户取消订单];操作说明[同意退款订单:{$order_info['order_num']};金额:{$refund_amount}];操作类型[用户取消订单];";//管理备注
        //            BalanceModel::inc_balance('member', $order_info['user_id'], $refund_amount, '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);
        //        }
        $params['refund_time']       = time();//退款时间
        $params['refund_amount']     = $refund_amount;//退款金额
        $params['compensate_amount'] = $compensate_amount;//补偿金额


        /** 释放技师时间 && 后台审核通过释放时间 **/
        //        $map2   = [];
        //        $map2[] = ['order_num', '=', $order_info['order_num']];
        //        $ShopOrderSaveModel->where($map2)->update([
        //            'status'             => 2,//取消
        //            'operation_end_time' => time(),
        //            'update_time'        => time(),
        //        ]);


        $result = $ShopOrderModel->strict(false)->where($where)->update($params);
        if (empty($result)) $this->error('失败请重试');

        // 提交事务
        Db::commit();


        $this->success('操作成功');
    }

}
