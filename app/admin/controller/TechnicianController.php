<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"Technician",
 *     "name_underline"      =>"technician",
 *     "controller_name"     =>"Technician",
 *     "table_name"          =>"technician",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"技师管理",
 *     "author"              =>"",
 *     "create_time"         =>"2024-08-30 15:12:32",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\TechnicianController();
 * )
 */


use initmodel\BalanceModel;
use initmodel\PointModel;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class TechnicianController extends AdminBaseController
{


    //    public function initialize()
    //    {
    //        //技师管理
    //        parent::initialize();
    //    }


    /**
     * 展示
     * @adminMenu(
     *     'name'             => 'Technician',
     *     'name_underline'   => 'technician',
     *     'parent'           => 'index',
     *     'display'          => true,
     *     'hasView'          => true,
     *     'order'            => 10000,
     *     'icon'             => '',
     *     'remark'           => '技师管理',
     *     'param'            => ''
     * )
     */
    public function index()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where = [];
        if ($params["keyword"]) $where[] = ["nickname|phone|introduce", "like", "%{$params["keyword"]}%"];
        if ($params["test"]) $where[] = ["test", "=", $params["test"]];
        //if($params["status"]) $where[]=["status","=", $params["status"]];
        //$where[]=["type","=", 1];


        $params["InterfaceType"] = "admin";//接口类型


        //导出数据
        if ($params["is_export"]) $this->export_excel($where, $params);

        //查询数据
        $result = $TechnicianInit->get_list_paginate($where, $params);

        //数据渲染
        $this->assign("list", $result);
        $this->assign("page", $result->render());//单独提取分页出来

        return $this->fetch();
    }

    //佣金管理
    public function commission()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理  (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理  (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();


        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];


        //提交数据
        $result = $TechnicianInit->admin_edit_post($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //提交(副本,无任何操作) 编辑&添加
    public function edit_post_two()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //提交数据
        $result = $TechnicianInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //编辑详情
    public function edit2()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理  (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        $ShopGoodsInit = new \init\ShopGoodsInit();//商品管理   (ps:InitController)
        $goods_list    = $ShopGoodsInit->get_list();
        if ($toArray['goods_ids']) {
            foreach ($goods_list as $k => &$v) {
                if (in_array($v['id'], $toArray['goods_ids'])) $v['checked'] = true;
            }
        }
        $this->assign('goods_list', $goods_list);

        return $this->fetch();
    }

    //编辑提交技师信息
    public function edit_technician()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        if ($params['avatar']) $params['avatar'] = $this->setParams($params['avatar']);
        if ($params['goods_ids']) $params['goods_ids'] = $this->setParams(array_keys($params['goods_ids']));

        //提交数据
        $result = $TechnicianInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //驳回
    public function refuse()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理  (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //驳回,更改状态
    public function audit_post()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //更改数据条件 && 或$params中存在id本字段可以忽略
        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];

        //通过&拒绝时间
        if ($params['status'] == 2) $params['pass_time'] = time();
        if ($params['status'] == 3) $params['refuse_time'] = time();

        //提交数据
        $result = $TechnicianInit->edit_post_two($params, $where);
        if (empty($result)) $this->error("失败请重试");

        $this->success("操作成功");
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //插入数据
        $result = $TechnicianInit->admin_edit_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //查看详情
    public function find()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //虚拟销量
    public function virtually_sell()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //虚拟点赞数
    public function virtually_like()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理    (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        //查询条件
        $where   = [];
        $where[] = ["id", "=", $params["id"]];

        //查询数据
        $params["InterfaceType"] = "admin";//接口类型
        $result                  = $TechnicianInit->get_find($where, $params);
        if (empty($result)) $this->error("暂无数据");

        //数据格式转数组
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //删除
    public function delete()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        if ($params["id"]) $id = $params["id"];
        if (empty($params["id"])) $id = $this->request->param("ids/a");

        //删除数据
        $result = $TechnicianInit->delete_post($id);
        if (empty($result)) $this->error("失败请重试");

        $this->success("删除成功", "index{$this->params_url}");
    }


    //批量操作
    public function batch_post()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param();

        $id = $this->request->param("id/a");
        if (empty($id)) $id = $this->request->param("ids/a");

        //提交编辑
        $result = $TechnicianInit->batch_post($id, $params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    //更新排序
    public function list_order_post()
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $params          = $this->request->param("list_order/a");

        //提交更新
        $result = $TechnicianInit->list_order_post($params);
        if (empty($result)) $this->error("失败请重试");

        $this->success("保存成功", "index{$this->params_url}");
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $TechnicianInit  = new \init\TechnicianInit();//技师管理   (ps:InitController)
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)


        $result = $TechnicianInit->get_list($where, $params);

        $result = $result->toArray();
        foreach ($result as $k => &$item) {

            //订单号过长问题
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";

            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';


            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";


            //背景颜色
            if ($item['unit'] == '测试8') $item['BackgroundColor'] = 'red';
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "名字", "rowVal" => "name", "width" => 20],
            ["rowName" => "年龄", "rowVal" => "age", "width" => 20],
            ["rowName" => "测试", "rowVal" => "test", "width" => 20],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => count($headArrValue)/2],
        //            ["rowName" => "列2", "acrossCells" => count($headArrValue)/2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "导出"]);
    }


    //编辑详情
    public function log()
    {
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }

        //数据库
        if ($params['type'] == 1) $name = 'base_balance';
        if ($params['type'] == 2) $name = 'base_point';
        if (empty($name)) $name = 'base_balance';

        if ($name == 'base_balance') {
            $this->assign('type', 1);
            $this->assign('type1', 'class="active"');
        }
        if ($name == 'base_point') {
            $this->assign('type', 2);
            $this->assign('type2', 'class="active"');
        }


        //筛选条件
        $map   = [];
        $map[] = ["user_id", "=", $params["user_id"]];
        $map[] = ["identity_type", "=", $params["identity_type"] ?? 'technician'];
        $map[] = $this->getBetweenTime($params['beginTime'], $params['endTime']);
        if ($params['keyword']) $map[] = ["content", "like", "%{$params['keyword']}%"];


        //导出数据
        //        if ($params["is_export"]) $this->export_excel_use($map, $params, $name);


        $type   = [1 => '收入', 2 => '支出'];
        $result = Db::name($name)
            ->where($map)
            ->order('id desc')
            ->paginate(['list_rows' => 15, 'query' => $params])
            ->each(function ($item, $key) use ($type) {

                $item['type_name'] = $type[$item['type']];

                return $item;
            });


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来

        return $this->fetch();
    }


    //操作 积分 或余额
    public function operate()
    {
        $params = $this->request->param();
        foreach ($params as $k => $v) {
            $this->assign($k, $v);
        }
        return $this->fetch();
    }


    //提交修改余额
    public function operate_post()
    {
        $MemberModel       = new \initmodel\MemberModel();//用户管理
        $TechnicianModel   = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        $params                  = $this->request->param();
        $params['identity_type'] = $params['identity_type'] ?? 'technician';

        //用户
        if ($params['identity_type']) $info = $TechnicianModel->where('id', '=', $params['id'])->find();


        /**余额操作**/
        if ($params['operate_type'] == 1) {
            //增加
            if ($params['type'] == 1) {
                if (empty($params['content'])) $params['content'] = '管理员添加';
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户余额];";//管理备注
                BalanceModel::inc_balance($params['identity_type'], $params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }

            //扣除
            if ($params['type'] == 2) {
                if (empty($params['content'])) $params['content'] = '管理员扣除';
                if ($info['balance'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户余额];";//管理备注
                BalanceModel::dec_balance($params['identity_type'], $params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }
        }


        /**积分操作**/
        if ($params['operate_type'] == 2) {
            //增加
            if ($params['type'] == 1) {
                if (empty($params['content'])) $params['content'] = '管理员添加';
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[增加用户积分];";//管理备注
                PointModel::inc_point($params['identity_type'], $params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }

            //扣除
            if ($params['type'] == 2) {
                if (empty($params['content'])) $params['content'] = '管理员扣除';
                if ($info['point'] < $params['price']) $this->error('请输入正确金额');
                $remark = "操作人[{$admin_id_and_name}];操作说明[{$params['content']}];操作类型[扣除用户积分];";//管理备注
                PointModel::dec_point($params['identity_type'], $params['id'], $params['price'], $params['content'], $remark, 0, cmf_order_sn(6), 100);
            }
        }

        $this->success('操作成功');
    }


}
