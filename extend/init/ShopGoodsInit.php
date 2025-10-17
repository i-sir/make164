<?php

namespace init;


/**
 * @Init(
 *     "name"            =>"ShopGoods",
 *     "name_underline"  =>"shop_goods",
 *     "table_name"      =>"shop_goods",
 *     "model_name"      =>"ShopGoodsModel",
 *     "remark"          =>"商品管理",
 *     "author"          =>"",
 *     "create_time"     =>"2024-08-30 10:44:19",
 *     "version"         =>"1.0",
 *     "use"             => new \init\ShopGoodsInit();
 * )
 */

use think\facade\Db;


class ShopGoodsInit extends Base
{

    public $type     = [1 => '抢购', 2 => '限购'];//商品类型
    public $status   = [1 => '展示', 2 => '隐藏'];//是否展示
    public $is_index = [1 => '是', 2 => '否'];//首页推荐

    public $Field         = "*";//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = "list_order,id desc";//排序
    public $InterfaceType = "api";//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $ShopGoodsInit  = new \init\ShopGoodsInit();//商品管理   (ps:InitController)
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)
    }

    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $ShopGoodsClassModel = new \initmodel\ShopGoodsClassModel(); //分类管理   (ps:InitModel)


        //处理转文字
        $item['type_name']     = $this->type[$item['type']];//商品类型
        $item['status_name']   = $this->status[$item['status']];//是否展示
        $item['is_index_name'] = $this->is_index[$item['is_index']];//首页推荐


        //分类名字
        $class_info = $ShopGoodsClassModel->where('id', $item['class_id'])->find();
        if ($class_info) {
            $item['class_name'] = $class_info['name'];
            $item['class_info'] = $class_info;

            //禁忌说明
            $taboo_instructions = $class_info['taboo_instructions'];
            if (empty($taboo_instructions)) $taboo_instructions = cmf_config('taboo_instructions');


            //下单须知
            $order_notice = $class_info['order_notice'];
            if (empty($order_notice)) $order_notice = cmf_config('order_notice');

            //禁忌说明  &&   下单须知
            $item['taboo_instructions'] = cmf_replace_content_file_url(htmlspecialchars_decode($taboo_instructions));
            $item['order_notice']       = cmf_replace_content_file_url(htmlspecialchars_decode($order_notice));
        }


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        if ($this->InterfaceType == 'api') {
            //api处理文件
            if ($item['image']) $item['image'] = cmf_get_asset_url($item['image']);
            if ($item['images']) $item['images'] = $this->getImagesUrl($item['images']);


        } else {
            //admin处理文件
            if ($item['images']) $item['images'] = $this->getParams($item['images']);
        }


        //导出数据处理
        if (isset($params["is_export"]) && $params["is_export"]) {
            $item["create_time"] = date("Y-m-d H:i:s", $item["create_time"]);
            $item["update_time"] = date("Y-m-d H:i:s", $item["update_time"]);
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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)


        //查询数据
        $result = $ShopGoodsModel
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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)


        //查询数据
        $result = $ShopGoodsModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->paginate(["list_rows" => $params["page_size"] ?? $this->PageSize, "query" => $params])
            ->each(function ($item, $key) use ($params) {

                //处理公共数据
                $item = $this->common_item($item, $params);

                return $item;
            });

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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)

        //查询数据
        $result = $ShopGoodsModel
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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        //查询数据
        $item = $ShopGoodsModel
            ->where($where)
            ->order($params['order'] ?? $this->Order)
            ->field($params['field'] ?? $this->Field)
            ->find();


        if (empty($item)) return false;


        //处理公共数据
        $item = $this->common_item($item, $params);

        //富文本处理
        if ($item['content']) $item['content'] = htmlspecialchars_decode(cmf_replace_content_file_url($item['content']));

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
        if ($params['images']) $params['images'] = $this->setParams($params['images']);

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
        if ($params['images']) $params['images'] = $this->setParams($params['images']);

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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopGoodsModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopGoodsModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopGoodsModel->strict(false)->insert($params, true);
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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $ShopGoodsModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $ShopGoodsModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $ShopGoodsModel->strict(false)->insert($params, true);
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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)


        if ($type == 1) $result = $ShopGoodsModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $ShopGoodsModel->destroy($id, true);//真实删除

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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理  (ps:InitModel)

        $where   = [];
        $where[] = ["id", "in", $id];//$id 为数组


        $params["update_time"] = time();
        $result                = $ShopGoodsModel->where($where)->strict(false)->update($params);//修改状态

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
        $ShopGoodsModel = new \initmodel\ShopGoodsModel(); //商品管理   (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ["id", "=", $k];
            $result  = $ShopGoodsModel->where($where)->strict(false)->update(["list_order" => $v, "update_time" => time()]);//排序
        }

        return $result;
    }


}
