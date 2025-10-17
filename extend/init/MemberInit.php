<?php

namespace init;


use initmodel\PointModel;
use think\exception\HttpResponseException;
use think\Response;

/**
 * @Init(
 *     "name"            =>"Member",
 *     "table_name"      =>"Member",
 *     "model_name"      =>"MemberModel",
 *     "remark"          =>"用户管理",
 *     "author"          =>"",
 *     "create_time"     =>"2023-12-13 10:57:08",
 *     "version"         =>"1.0",
 *     "use"             => new \init\MemberInit();
 * )
 */
class MemberInit extends Base
{
    public $Field         = 'id,avatar,nickname,phone,openid';//过滤字段,默认全部
    public $Limit         = 100000;//如不分页,展示条数
    public $PageSize      = 15;//分页每页,数据条数
    public $Order         = 'id desc';//排序
    public $InterfaceType = 'api';//接口类型:admin=后台,api=前端

    //本init和model
    public function _init()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $MemberInit  = new \init\MemberInit();//用户管理
    }

    /**
     * 获取个人信息,全部扩展信息
     * @param $where    条件
     * @param $params   扩充参数 field=字段 '*'
     * @return false|mixed
     */
    public function get_my_info($where = [], $params = [])
    {
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;
        $item = $MemberModel->where($where)->field('*')->find();
        if (empty($item)) return false;

        $ShopOrderModel  = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $MemberModel     = new \initmodel\MemberModel();//用户管理
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)

        //处理数据&      注:这里要全部用model模板,不然会自调用死循环!!!

        //有效下单金额满足n元后成为分销员
        $order_required_consume = cmf_config('order_required_consume');

        //分销员
        $item['is_distributor'] = false;
        $map                    = [];
        $map[]                  = ['status', 'in', [5, 8]];
        $map[]                  = ['user_id', '=', $item['id']];
        $total_amount           = $ShopOrderModel->where($map)->sum('amount');
        if ($total_amount >= $order_required_consume) $item['is_distributor'] = true;


        //累计邀请用户
        $map2               = [];
        $map2[]             = ['pid', '=', $item['id']];
        $map2[]             = ['effective_invitation', '=', 1];//是否有效邀请:1是,2否
        $item['total_user'] = $MemberModel->where($map2)->count();


        //积累邀请用户佣金
        $map3                          = [];
        $map3[]                        = ['order_type', '=', 500];
        $map3[]                        = ['user_id', '=', $item['id']];
        $item['total_user_commission'] = PointModel::where($map3)->sum('price');


        //积累邀请技师
        $map4                     = [];
        $map4[]                   = ['pid', '=', $item['id']];
        $item['total_technician'] = $TechnicianModel->where($map4)->count();

        //积累邀请技师佣金
        $map5                                = [];
        $map5[]                              = ['order_type', '=', 600];
        $map5[]                              = ['user_id', '=', $item['id']];
        $item['total_technician_commission'] = PointModel::where($map5)->sum('price');


        if ($item['avatar']) $item['avatar'] = cmf_get_asset_url($item['avatar']);


        //上级信息
        $p_user_info         = $MemberModel->where('id', '=', $item['pid'])->find();
        $item['p_user_info'] = $p_user_info;


        return $item;
    }


    /**
     * 处理公共数据
     * @param array $item   单条数据
     * @param array $params 参数 is_admin==true后台,false==前端
     * @return array|mixed
     */
    public function common_item($item = [], $params = [])
    {
        $MemberModel     = new \initmodel\MemberModel();//用户管理

        if ($item['avatar']) $item['avatar'] = cmf_get_asset_url($item['avatar']);


        //接口类型
        if ($params['InterfaceType']) $this->InterfaceType = $params['InterfaceType'];
        /** 处理数据 **/
        if ($this->InterfaceType == 'api') {


        } else {
            /** admin处理文件 **/


            //上级信息
            $p_user_info         = $MemberModel->where('id', '=', $item['pid'])->find();
            $item['p_user_info'] = $p_user_info;

        }


        return $item;
    }


    /**
     * 获取微信昵称
     * @return void
     */
    public function get_member_wx_nickname()
    {
        $MemberModel = new \initmodel\MemberModel();//用户管理
        $max_id      = $MemberModel->max('id');
        return '微信用户_' . ($max_id + 1);
    }

    /**
     * 获取列表
     * @param $where    条件
     * @param $params   扩充参数
     * @return false|mixed
     */
    public function get_list($where = [], $params = [])
    {
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        $result = $MemberModel
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
     * @param $where    条件
     * @param $params   扩充参数
     * @return mixed
     */
    public function get_list_paginate($where = [], $params = [])
    {
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        $result = $MemberModel
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
     * 获取详情
     * @param $where    条件
     * @param $params   扩充参数 field=字段 'id,avatar,nickname,phone'
     * @return false|mixed
     */
    public function get_find($where = [], $params = [])
    {
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        //传入id直接查询
        if (is_string($where) || is_int($where)) $where = ["id" => (int)$where];
        if (empty($where)) return false;

        $item = $MemberModel
            ->where($where)
            ->field($params['field'] ?? $this->Field)
            ->find();

        if (empty($item)) return false;

        //处理公共数据
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
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)
        $item        = $this->get_find(['id' => $params['id']]);

        //密码
        if ($params['pass']) $params['pass'] = cmf_password($params['pass']);
        if (empty($params['pass'])) unset($params['pass']);


        if (!empty($params['id'])) {
            //编辑用户


            //检测,如果手机号发生变化,手机号是否已经存在问题
            if ($params['old_phone'] != $params['phone']) {
                $member_info = $MemberModel->where('phone', '=', $params['phone'])->find();
                if (!empty($member_info)) $this->error('手机号已经存在');
            }


            $params['update_time'] = time();
            $result                = $MemberModel->strict(false)->update($params);
            if ($result) $result = $params['id'];
        } else {
            //添加用户


            //用户基本信息生成
            $params['nickname'] = $params['nickname'] ?? $this->get_member_wx_nickname();
            $params['avatar']   = $params['avatar'] ?? cmf_config('app_logo');
            $params['openid']   = 'o2x' . sha1(md5($params['phone'])) . '_' . md5(cmf_random_string(60, 3));
            //$params['openid'] = 'o2x' . $this->insertRandomUnderscore(md5(cmf_random_string(60, 3)));


            //检测手机号是否已经存在
            $map      = [];
            $map[]    = ['phone', '=', $params['phone']];
            $is_phone = $MemberModel->where($map)->count();
            if ($is_phone) $this->error('手机号已存在!');


            //如密码为空,生成一个默认密码
            if (empty($params['pass'])) $params['pass'] = cmf_password($params['phone']);


            //添加
            $params['create_time'] = time();
            $result                = $MemberModel->strict(false)->insert($params, true);
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
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)


        //查询数据
        if (!empty($params["id"])) $item = $this->get_find(["id" => $params["id"]]);
        if (empty($params["id"]) && !empty($where)) $item = $this->get_find($where);


        if (!empty($params["id"])) {
            //如传入id,根据id编辑数据
            $params["update_time"] = time();
            $result                = $MemberModel->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } elseif (!empty($where)) {
            //传入where条件,根据条件更新数据
            $params["update_time"] = time();
            $result                = $MemberModel->where($where)->strict(false)->update($params);
            if ($result) $result = $item["id"];
        } else {
            //无更新条件则添加数据
            $params["create_time"] = time();
            $result                = $MemberModel->strict(false)->insert($params, true);
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
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        if ($type == 1) $result = $MemberModel->destroy($id);//软删除 数据表字段必须有delete_time
        if ($type == 2) $result = $MemberModel->destroy($id, true);//真实删除

        return $result;
    }


    /**
     * 删除数据 版本1.0不用了
     * @param $where     where 条件
     * @param $type      1真实删除 2软删除
     * @param $params    扩充参数
     * @return void
     */
    public function delete_post_v1($where, $type = 1, $params = [])
    {
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        if ($type == 1) $result = $MemberModel->where($where)->delete();//真实删除
        if ($type == 2) $result = $MemberModel->where($where)->strict(false)->update(['delete_time' => time()]);//软删除

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
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        $where   = [];
        $where[] = ['id', 'in', $id];//$id 为数组

        $params['update_time'] = time();
        $result                = $MemberModel->where($where)->strict(false)->update($params);//修改状态

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
        $MemberModel = new \initmodel\MemberModel(); //会员管理  (ps:InitModel)

        foreach ($list_order as $k => $v) {
            $where   = [];
            $where[] = ['id', '=', $k];
            $result  = $MemberModel->where($where)->strict(false)->update(['list_order' => $v, 'update_time' => time()]);//排序
        }

        return $result;
    }


    /**
     * 插入随机下划线
     * @param $inputString 字符串
     * @return array|string|string[]
     */
    function insertRandomUnderscore($inputString)
    {
        // 获取字符串长度
        $length = strlen($inputString);

        // 生成一个随机位置
        $randomPosition = mt_rand(0, $length);

        // 在随机位置插入下划线
        $resultString = substr_replace($inputString, '_', $randomPosition, 0);

        return $resultString;
    }

}