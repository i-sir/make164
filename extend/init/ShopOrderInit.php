<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"ShopOrder",
 *     "table_name"      =>"shop_order",
 *     "model_name"      =>"ShopOrderModel",
 *     "remark"          =>"订单管理",
 *     "author"          =>"",
 *     "create_time"     =>"2023-09-29 09:57:21",
 *     "version"         =>"1.0",
 *     "use"             => new \init\ShopOrderInit();
 * )
 */

use think\facade\Db;


class ShopOrderInit extends Base
{
    public $Field         = '*';//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = 'id desc';//排序
    public $InterfaceType = 'api';//接口类型:admin=后台,api=前端

    //用户筛选状态
    public $status_where_member_api = [1 => [1], 2 => [2], 3 => [3, 6, 7], 4 => [4], 5 => [5], 8 => [8], 10 => [9, 10, 11]];

    //技师筛选状态
    public $status_where_technician_api = [2 => [2], 3 => [3, 6, 7], 4 => [4], 5 => [5], 8 => [8], 10 => [9, 11]];


    public $status_member     = [1 => '待付款', 2 => '待接单', 3 => '待服务', 4 => '进行中', 5 => '待评价', 6 => '已出发', 7 => '已到达', 8 => '已完成', 9 => '已取消', 10 => '已取消', 11 => '已取消'];
    public $status_technician = [1 => '待付款', 2 => '待接单', 3 => '待服务', 4 => '进行中', 5 => '已完成', 6 => '已出发', 7 => '已到达', 8 => '已完成', 9 => '用户取消', 10 => '系统取消', 11 => '技师取消'];
    public $status_admin      = [1 => '待付款', 2 => '待接单', 3 => '待服务', 4 => '进行中', 5 => '待评价', 6 => '已出发', 7 => '已到达', 8 => '已完成', 9 => '用户取消', 10 => '系统取消', 11 => '技师取消'];
    public $pay_type          = [1 => '微信支付', 2 => '余额支付', 3 => '积分支付', 4 => '支付宝支付', 5 => '组合支付'];
    public $identity_type     = ['technician' => '技师', 'member' => '用户'];
    public $type              = [1 => '预约单', 2 => '加钟'];

    //本init和model
    public function _init()
    {
        $ShopOrderInit        = new \init\ShopOrderInit();//订单管理
        $ShopOrderDetailInit  = new \init\ShopOrderDetailInit();//订单详情   (ps:InitController)
        $ShopOrderModel       = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel();//订单详情  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $ShopOrderDetailInit = new \init\ShopOrderDetailInit();//订单详情   (ps:InitController)
        $MemberInit          = new \init\MemberInit();//会员管理 (ps:InitController)
        $TechnicianInit      = new \init\TechnicianInit();//技师管理   (ps:InitController)

        //状态名称
        $item['type_name']   = $this->type[$item['type']];
        $item['status_name'] = $this->status_admin[$item['status']];
        if ($params['identity_type'] == 'member') $item['status_name'] = $this->status_member[$item['status']];
        if ($params['identity_type'] == 'technician') $item['status_name'] = $this->status_technician[$item['status']];

        //状态,支付方式,信息
        $item['pay_type_name'] = $this->pay_type[$item['pay_type']];

        //用户,商品信息
        $item["user_info"] = $MemberInit->get_find(['id' => $item['user_id']]);

        //技师信息
        $item["technician_info"] = $TechnicianInit->get_find(['id' => $item['technician_id']]);

        //订单详情
        $map                = [];
        $map[]              = ['order_num', '=', $item['order_num']];
        $item["goods_list"] = $ShopOrderDetailInit->get_list($map);

        //剩余时间/秒
        $item['residue_time'] = $item['end_time'] - time();

        //取消人身份
        $item['cancel_identity'] = $this->identity_type[$item['cancel_identity_type']];

        //取消图集
        if ($item['cancel_images']) $item['cancel_images'] = $this->getImagesUrl($item['cancel_images']);

        //导出数据处理
        if (isset($params['is_export']) && $params['is_export']) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件


        } else {
            //admin处理文件
            $item['status_name'] = $this->status_admin[$item['status']];

        }


        return $item;
    }


    /**
     * 获取列表
     * @param $where    条件
     * @param $params   扩充参数
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)


        $result = $ShopOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->limit($params["limit"] ?? $this->Limit)
            ->select()
            ->each(function ($item, $key) use ($params) {

                //处理数据
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
     * @param $where    条件
     * @param $params   扩充参数
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)

        $MemberInit           = new \init\MemberInit();//会员管理 (ps:InitController)
        $ShopOrderDetailModel = new \initmodel\ShopOrderDetailModel();//订单详情 (ps:InitModel)


        $result = $ShopOrderModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
            ->each(function ($item, $key) use ($params) {


                //处理数据
                $item = $this->common_item($item, $params);

                return $item;
            });

        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        //if ($this->InterfaceType == 'api' && $result->isEmpty()) return false;


        return $result;
    }


    /**
     * 获取详情
     * @param $where    条件
     * @param $params   扩充参数
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        $item = $ShopOrderModel
            ->where($where)
            ->field($params['field'] ?? $this->Field)
            ->find();

        if (empty($item)) return false;

        //处理数据
        $item = $this->common_item($item, $params);


        return $item;
    }


    /**
     * 前端  编辑&添加
     * @param $params 参数
     * @return void
     */
    public function api_edit_post($params = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params);//api提交

        return $result;
    }


    /**
     * 后台  编辑&添加
     * @param $model  类
     * @param $params 参数
     * @return void
     */
    public function admin_edit_post($params = [])
    {
        $result = false;

        //处理共同数据


        $result = $this->edit_post($params);//admin提交

        return $result;
    }


    /**
     * 提交 编辑&添加
     * @param $params
     * @return void
     */
    public function edit_post($params)
    {
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)

        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopOrderModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopOrderModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopOrderModel->strict(false)->insert($params, true);
        }

        return $result;
    }


    /**
     * 删除数据
     * @param $where     where 条件
     * @param $type      1真实删除 2软删除
     * @param $params    扩充参数
     * @return void
     */
    public function delete_post($where, $type = 1, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        if ($type == 1) $result = $model->where($where)->delete();//真实删除

        if ($type == 2) $result = $model->where($where)->strict(false)->update(['delete_time' => time()]);//软删除


        return $result;
    }


    /**
     * 后台  推荐
     * @param $id
     * @param $is_recommend 修改值
     * @param $params       扩充参数
     * @return void
     */
    public function recommend_post($id, $is_recommend, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)


        $where   = [];
        $where[] = ['id', 'in', $id];//$id 为数组

        $result = $model->where($where)->strict(false)->update(['is_recommend' => $is_recommend, 'update_time' => time()]);//设为推荐

        return $result;
    }


    /**
     * 后台  状态
     * @param $id
     * @param $status 状态值
     * @param $params 扩充参数
     * @return void
     */
    public function status_post($id, $status, $params = [])
    {
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)


        $where   = [];
        $where[] = ['id', 'in', $id];//$id 为数组

        $result = $model->where($where)->strict(false)->update(['status' => $status, 'update_time' => time()]);//修改状态

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
        $model = new \initmodel\ShopOrderModel(); //订单管理 (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ['id', '=', $k];
            $result  = $model->where($where)->strict(false)->update(['list_order' => $v, 'update_time' => time()]);//排序
        }

        return $result;
    }


}
