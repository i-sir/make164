<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"ShopGoods",
 *     "name_underline"          =>"shop_goods",
 *     "controller_name"         =>"ShopGoods",
 *     "table_name"              =>"shop_goods",
 *     "remark"                  =>"商品管理"
 *     "api_url"                 =>"/api/wxapp/shop_goods/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-30 10:44:19",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\ShopGoodsController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/shop_goods/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/shop_goods/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class ShopGoodsController extends AuthController
{


    public function initialize()
    {
        //商品管理

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/shop_goods/index
     * https://dzam164.wxselling.net/api/wxapp/shop_goods/index
     */
    public function index()
    {
        $ShopGoodsInit  = new \init\ShopGoodsInit();//商品管理   (ps:InitController)
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)

        $result = [];

        $this->success('商品管理-接口请求成功', $result);
    }


    /**
     * 分类列表
     * @OA\Post(
     *     tags={"商品管理"},
     *     path="/wxapp/shop_goods/find_class_list",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_goods/find_class_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_goods/find_class_list
     *   api:  /wxapp/shop_goods/find_class_list
     *   remark_name: 分类列表
     *
     */
    public function find_class_list()
    {
        $ShopGoodsClassInit  = new \init\ShopGoodsClassInit();//分类管理   (ps:InitController)
        $ShopGoodsClassModel = new \initmodel\ShopGoodsClassModel(); //分类管理   (ps:InitModel)

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
        $result                  = $ShopGoodsClassInit->get_list($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 商品管理 列表
     * @OA\Post(
     *     tags={"商品管理"},
     *     path="/wxapp/shop_goods/find_shop_goods_list",
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
     *     @OA\Parameter(
     *         name="class_id",
     *         in="query",
     *         description="class_id",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="technician_id",
     *         in="query",
     *         description="技师id,筛选技师关联的项目",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_goods/find_shop_goods_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_goods/find_shop_goods_list
     *   api:  /wxapp/shop_goods/find_shop_goods_list
     *   remark_name: 商品管理 列表
     *
     */
    public function find_shop_goods_list()
    {
        $ShopGoodsInit   = new \init\ShopGoodsInit();//商品管理   (ps:InitController)
        $ShopGoodsModel  = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ['id', '>', 0];
        if ($params["keyword"]) $where[] = ["goods_name", "like", "%{$params['keyword']}%"];
        if ($params["class_id"]) $where[] = ["class_id", "=", $params["class_id"]];
        if ($params["is_index"]) $where[] = ["is_index", "=", 1];
        if ($params["status"]) $where[] = ["status", "=", $params["status"]];

        //技师关联的项目
        if ($params["technician_id"]) {
            $technician_info = $TechnicianModel->where('id', '=', $params['technician_id'])->find();
            if ($technician_info['goods_ids']) {
                $where[] = ["id", "in", $this->getParams($technician_info['goods_ids'])];
            } else {
                $where[] = ["id", "in", [0]];
            }
        }


        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopGoodsInit->get_list_paginate($where, $params);
        if (empty($result)) $this->error("暂无信息!");

        $this->success("请求成功!", $result);
    }


    /**
     * 商品管理 详情
     * @OA\Post(
     *     tags={"商品管理"},
     *     path="/wxapp/shop_goods/find_shop_goods",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/shop_goods/find_shop_goods
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/shop_goods/find_shop_goods
     *   api:  /wxapp/shop_goods/find_shop_goods
     *   remark_name: 商品管理 详情
     *
     */
    public function find_shop_goods()
    {
        $ShopGoodsInit  = new \init\ShopGoodsInit();//商品管理    (ps:InitController)
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "api";//接口类型
        $result                  = $ShopGoodsInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        $this->success("详情数据", $result);
    }


}
