<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"Like",
 *     "name_underline"          =>"like",
 *     "controller_name"         =>"Like",
 *     "table_name"              =>"like",
 *     "remark"                  =>"收藏"
 *     "api_url"                 =>"/api/wxapp/like/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-09-01 10:51:21",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\LikeController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/like/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/like/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class LikeController extends AuthController
{


    public function initialize()
    {
        //收藏

        parent::initialize();
    }


    /**
     * 默认接口
     * /api/wxapp/like/index
     * https://dzam164.wxselling.net/api/wxapp/like/index
     */
    public function index()
    {
        $LikeInit  = new \init\LikeInit();//收藏   (ps:InitController)
        $LikeModel = new \initmodel\LikeModel(); //收藏   (ps:InitModel)

        $result = [];

        $this->success('收藏-接口请求成功', $result);
    }


    /**
     * 收藏 列表
     * @OA\Post(
     *     tags={"收藏"},
     *     path="/wxapp/like/find_like_list",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/like/find_like_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/like/find_like_list
     *   api:  /wxapp/like/find_like_list
     *   remark_name: 收藏 列表
     *
     */
    public function find_like_list()
    {
        $this->checkAuth();
        $LikeInit  = new \init\LikeInit();//收藏   (ps:InitController)
        $LikeModel = new \initmodel\LikeModel(); //收藏   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        $where   = [];
        $where[] = ['l.user_id', '=', $this->user_id];

        $params['user_id'] = $this->user_id;//用于是否购买
        $params['order']   = 'l.id desc';
        $params['field']   = 'l.id as l_id,t.*';
        $result            = $LikeInit->get_join_list($where, $params);

        $this->success("请求成功!", $result);
    }


    /**
     * 收藏 删除&添加
     * @OA\Post(
     *     tags={"收藏"},
     *     path="/wxapp/like/edit_like",
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
     *         name="pid",
     *         in="query",
     *         description="pid",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/like/edit_like
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/like/edit_like
     *   api:  /wxapp/like/edit_like
     *   remark_name: 收藏 删除&添加
     *
     */
    public function edit_like()
    {
        $this->checkAuth();
        $LikeInit  = new \init\LikeInit();//收藏    (ps:InitController)
        $LikeModel = new \initmodel\LikeModel(); //收藏   (ps:InitModel)

        //参数
        $params            = $this->request->param();
        $params["user_id"] = $this->user_id;


        //检测是否已收藏,如果收藏了取消,如果未收藏则添加
        $where   = [];
        $where[] = ['user_id', '=', $this->user_id];
        $where[] = ['pid', '=', $params['pid']];
        $is_like = $LikeInit->get_find($where);
        if ($is_like) {
            $update['delete_time'] = time();
            $LikeInit->edit_post($update, $where);
            $this->success("取消成功");
        } else {
            $LikeInit->edit_post($params);
            $this->success("收藏成功");
        }
    }


}
