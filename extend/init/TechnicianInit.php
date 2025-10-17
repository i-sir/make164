<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"Technician",
 *     "name_underline"  =>"technician",
 *     "table_name"      =>"technician",
 *     "model_name"      =>"TechnicianModel",
 *     "remark"          =>"技师管理",
 *     "author"          =>"",
 *     "create_time"     =>"2024-08-30 15:12:32",
 *     "version"         =>"1.0",
 *     "use"             => new \init\TechnicianInit();
 * )
 */

use think\facade\Db;


class TechnicianInit extends Base
{

    public $status      = [1 => '审核中', 2 => '已通过', 3 => '已拒绝'];//状态
    public $is_new      = [1 => '是', 2 => '否'];//新人推荐
    public $is_index    = [1 => '是', 2 => '否'];//平台推荐
    public $work_status = [1 => '可服务', 2 => '服务中', 3 => '已下线'];//工作状态

    public $Field         = "*";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $LikeModel          = new \initmodel\LikeModel(); //收藏   (ps:InitModel)
        $OrderEvaluateModel = new \initmodel\OrderEvaluateModel(); //评价管理   (ps:InitModel)
        $SaveDateController = new \api\wxapp\controller\SaveDateController();//预约时间  (ps:InitController)
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $ShopGoodsModel     = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)
        $InitController     = new \api\wxapp\controller\InitController();
        $MemberModel        = new \initmodel\MemberModel();//用户管理


        //n公里内免费出行
        $free_travel_kilometers = cmf_config('free_travel_kilometers');


        //处理转文字
        $item['status_name']      = $this->status[$item['status']];//状态
        $item['is_new_name']      = $this->is_new[$item['is_new']];//新人推荐
        $item['is_index_name']    = $this->is_index[$item['is_index']];//平台推荐
        $item['work_status_name'] = $this->work_status[$item['work_status']];//工作状态


        //是否收藏技师
        $is_like = false;
        if ($params['user_id']) {
            $where   = [];
            $where[] = ['user_id', '=', $params['user_id']];
            $where[] = ['pid', '=', $item['id']];
            $is_like = $LikeModel->where($where)->count();
            if ($is_like) $is_like = true;
        }
        $item['is_like'] = $is_like;

        //收藏数量
        $map                = [];
        $map[]              = ['pid', '=', $item['id']];
        $item['like_count'] = $LikeModel->where($map)->count();

        //评价数量
        $item['evaluate_count'] = $OrderEvaluateModel->where(['technician_id' => $item['id']])->count();

        //商品
        if ($item['goods_ids']) $item['goods_ids'] = $this->getParams($item['goods_ids']);


        //距离
        $item['distance_km']   = '0.1km';
        $item['distance_name'] = null;
        if ($item['distance']) $item['distance_km'] = round($item['distance'] / 1000, 2) . 'km';
        if ($item['distance'] <= 1) $item['distance_name'] = "{$free_travel_kilometers}km免费出行";


        //技师最早预约时间
        if ($params['earliest']) $item['earliest_time'] = $SaveDateController->findEarliestTechnicianDate($item['id']);


        //预计可得佣金 &&  关闭不需要
        //$item['estimate_commission'] = $InitController->get_estimate_commission($item['id']);


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {


            //当前业绩 ,待评价,已完成
            $map9                = [];
            $map9[]              = ['technician_id', '=', $item['id']];
            $map9[]              = ['status', 'in', [5, 8]];
            $item['performance'] = $ShopOrderModel->where($map9)->sum('amount');


            //api处理文件
            //            if ($item['sign_image']) $item['sign_image'] = cmf_get_asset_url($item['sign_image']);
            if ($item['qualifications_image']) $item['qualifications_image'] = cmf_get_asset_url($item['qualifications_image']);
            if ($item['identity_images']) $item['identity_images'] = $this->getImagesUrl($item['identity_images']);
            if ($item['avatar']) $item['avatar'] = $this->getImagesUrl($item['avatar']);

            //虚拟销量
            $item['sell_count'] = $item['sell_count'] + $item['virtually_sell_count'];

            //虚拟点赞量
            $item['like_count'] = $item['like_count'] + $item['virtually_like_count'];


        } else {
            //admin处理文件
            if ($item['identity_images']) $item['identity_images'] = $this->getParams($item['identity_images']);
            if ($item['avatar']) $item['avatar'] = $this->getImagesUrl($item['avatar']);


            //上级信息
            $p_user_info         = $MemberModel->where('id', '=', $item['pid'])->find();
            $item['p_user_info'] = $p_user_info;

        }


        //导出数据处理
        if (isset($params["is_export"]) && $params["is_export"]) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
        }


        //订单数量
        if ($params['is_me']) {
            $map10   = [];
            $map10[] = ['technician_id', '=', $item['id']];

            //待接单
            $map11                  = [];
            $map11[]                = ['status', 'in', [2]];
            $item['pending_orders'] = $ShopOrderModel->where(array_merge($map11, $map10))->count();

            //服务中
            $map12                  = [];
            $map12[]                = ['status', 'in', [4]];
            $item['service_orders'] = $ShopOrderModel->where(array_merge($map12, $map10))->count();

            //已完成
            $map13                    = [];
            $map13[]                  = ['status', 'in', [5, 8]];
            $item['completed_orders'] = $ShopOrderModel->where(array_merge($map13, $map10))->count();


            //技师服务项目
            $item['goods_list'] = [];
            if ($item['goods_ids']) {
                $map14              = [];
                $map14[]            = ['id', 'in', $item['goods_ids']];
                $item['goods_list'] = $ShopGoodsModel->where($map14)->select()
                    ->each(function ($item22, $key) {
                        if ($item22['image']) $item22['image'] = cmf_get_asset_url($item22['image']);
                        return $item22;
                    });
            }
        }


        return $item;
    }


    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)


        //查询数据
        $result = $TechnicianModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 分页查询
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 page_size=每页条数  InterfaceType=admin|api后端,前端
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)


        //查询数据
        if (empty($params['is_free'])) {
            $result = $TechnicianModel
                ->where($where)
                ->order($params['order'] ?? $this->Order)
                ->field($params['field'] ?? $this->Field)
                ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
                ->each(function ($item, $key) use ($params) {

                    //处理公共数据
                    $item = $this->common_item($item, $params);

                    return $item;
                });
        }


        if ($params['is_free']) {
            //0-10   数字
            $min_distance = 0;//初始km
            $max_distance = 1000;//结束km

            //查询数据
            $result = $TechnicianModel
                ->where($where)
                ->field("*")
                ->field("{$params['field']} as distance")
                ->where("{$params['field']} BETWEEN {$min_distance} AND {$max_distance}")
                ->order($params['order'])
                ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
                ->each(function ($item, $key) use ($params) {

                    //处理公共数据
                    $item = $this->common_item($item, $params);


                    return $item;
                });
        }


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        //if ($this->InterfaceType == 'api' && $result->isEmpty()) return false;


        return $result;
    }

    /**
     * 获取列表
     * @param $where  条件
     * @param $params 扩充参数 order=排序  field=过滤字段 limit=限制条数  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_join_list($where = [], $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)

        //查询数据
        $result = $TechnicianModel
            ->alias('a')
            ->join('member b', 'a.user_id = b.id')
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);


                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api' && empty(count($result))) return false;

        return $result;
    }


    /**
     * 获取详情
     * @param $where     条件 或 id值
     * @param $params    扩充参数 field=过滤字段  InterfaceType=admin|api后端,前端
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        //查询数据
        $item = $TechnicianModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->find();


        if (empty($item)) return false;


        //处理公共数据
        $item = $this->common_item($item, $params);

        //富文本处理


        return $item;
    }


    /**
     * 前端  编辑&添加
     * @param $params 参数
     * @param $where  where条件
     * @return void
     */
    public function api_edit_post($params = [], $where = [])
    {
        $result = false;

        //处理共同数据
//        if ($params['qualifications_image']) $params['qualifications_image'] = $this->setParams($params['qualifications_image']);
        if ($params['identity_images']) $params['identity_images'] = $this->setParams($params['identity_images']);
        if ($params['avatar']) $params['avatar'] = $this->setParams($params['avatar']);
        if ($params['goods_ids']) $params['goods_ids'] = $this->setParams($params['goods_ids']);

        $result = $this->edit_post($params, $where);//api提交

        return $result;
    }


    /**
     * 后台  编辑&添加
     * @param $model  类
     * @param $params 参数
     * @param $where  更新提交(编辑数据使用)
     * @return void
     */
    public function admin_edit_post($params = [], $where = [])
    {
        $result = false;

        //处理共同数据
//        if ($params['qualifications_image']) $params['qualifications_image'] = $this->setParams($params['qualifications_image']);
        if ($params['identity_images']) $params['identity_images'] = $this->setParams($params['identity_images']);
        if ($params['avatar']) $params['avatar'] = $this->setParams($params['avatar']);
        if ($params['goods_ids']) $params['goods_ids'] = $this->setParams($params['goods_ids']);

        $result = $this->edit_post($params, $where);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @param $where where条件
     * @return void
     */
    public function edit_post($params, $where = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $TechnicianModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $TechnicianModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $TechnicianModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 提交(副本,无任何操作) 编辑&添加
     * @param $params
     * @param $where where 条件
     * @return void
     */
    public function edit_post_two($params, $where = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $TechnicianModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $TechnicianModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $TechnicianModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 删除数据 软删除
     * @param $id     传id  int或array都可以
     * @param $type   1软删除 2真实删除
     * @param $params 扩充参数
     * @return void
     */
    public function delete_post($id, $type = 1, $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)


        if ($type == 1) $result = $TechnicianModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $TechnicianModel->destroy($id, true);//真实删除

        return $result;
    }


    /**
     * 后台批量操作
     * @param $id
     * @param $params 修改值
     * @return void
     */
    public function batch_post($id, $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $TechnicianModel->where($where)->strict(false)->update($params);//修改状态

        return $result;
    }


    /**
     * 后台  排序
     * @param $list_order 排序
     * @param $params     扩充参数
     * @return void
     */
    public function list_order_post($list_order, $params = [])
    {
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $TechnicianModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
