<?php

namespace app\admin\controller;


/**
 * @adminMenuRoot(
 *     "name"                =>"ShopOrder",
 *     "controller_name"     =>"ShopOrder",
 *     "table_name"          =>"shop_order",
 *     "action"              =>"default",
 *     "parent"              =>"",
 *     "display"             => true,
 *     "order"               => 10000,
 *     "icon"                =>"none",
 *     "remark"              =>"订单管理",
 *     "author"              =>"",
 *     "create_time"         =>"2023-09-29 09:57:21",
 *     "version"             =>"1.0",
 *     "use"                 => new \app\admin\controller\ShopOrderController();
 * )
 */


use init\ShopOrderInit;
use initmodel\BalanceModel;
use initmodel\MemberModel;
use initmodel\ShopOrderModel;
use plugins\weipay\lib\PayController;
use think\facade\Cache;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class ShopOrderController extends AdminBaseController
{

    public function initialize()
    {
        //订单管理
        parent::initialize();


        //用户
        $this->assign('member_list', MemberModel::select());


        //技师
        $TechnicianModel = new \initmodel\TechnicianModel(); //技师管理   (ps:InitModel)
        $this->assign('technician_list', $TechnicianModel->select());
    }


    //检测是否有新订单
    public function order_notification()
    {
        $result = Cache::get('order_notification_admin');
        if (empty($result)) $this->error('无通知');
        Cache::delete('order_notification_admin');
        $this->success('有通知');
    }


    /**
     * 展示
     * @adminMenu(
     *     'name'   => 'ShopOrder',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '订单管理',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $params         = $this->request->param();
        $ShopOrderInit  = new ShopOrderInit();//订单管理
        $ShopOrderModel = new ShopOrderModel();//订单管理


        $where = [];
        if ($params['keyword']) $where[] = ['order_num', 'like', "%{$params['keyword']}%"];
        if ($params['order_num']) $where[] = ['order_num', 'like', "%{$params['order_num']}%"];
        if ($params['goods_name']) $where[] = ['goods_name', '=', $params['goods_name']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        if ($params['technician_id']) $where[] = ['technician_id', '=', $params['technician_id']];

        if ($params['order_date']) {
            $order_date_arr = explode(' - ', $params['order_date']);
            $where[]        = $this->getBetweenTime($order_date_arr[0], $order_date_arr[1]);
        }


        //状态筛选
        $status_where = [];
        if ($params['status']) $status_where[] = ['status', '=', $params['status']];
        //if (empty($params['status'])) $status_where[] = ['status', 'in', [2, 3]];


        //数据类型
        $params['InterfaceType'] = 'admin';//身份类型,后台


        //导出数据
        if ($params["is_export"]) $this->export_excel(array_merge($where, $status_where), $params);
        $result = $ShopOrderInit->get_list_paginate(array_merge($where, $status_where), $params);


        $this->assign("list", $result);
        $this->assign('page', $result->render());//单独提取分页出来

        //全部数量
        $this->assign("total", $ShopOrderModel->where($where)->count());//总数量


        //数据统计
        $status_arr = $ShopOrderInit->status_admin;
        $count      = [];
        foreach ($status_arr as $key => $status) {
            $map                    = [];
            $map[]                  = ['status', '=', $key];
            $map                    = array_merge($map, $where);
            $count[$key]['count']   = $ShopOrderModel->where($map)->count();
            $count[$key]['key']     = $key;
            $count[$key]['name']    = $status;
            $count[$key]['is_ture'] = false;
            if ($params['status'] == $key) $count[$key]['is_ture'] = true;
        }


        $this->assign('count', $count);


        return $this->fetch();
    }


    //编辑详情
    public function edit()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //提交编辑
    public function edit_post()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $result = $ShopOrderInit->admin_edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //修改备注
    public function setRemark()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理

        $result = $ShopOrderInit->admin_edit_post($params);
        if (empty($result)) $this->error('失败请重试');

        $this->success("保存成功", 'index' . $this->params_url);
    }


    //添加
    public function add()
    {
        return $this->fetch();
    }


    //添加提交
    public function add_post()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $result = $ShopOrderInit->admin_edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //查看详情
    public function details()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");


        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        return $this->fetch();
    }


    //退款理由
    public function reason()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //发货
    public function send()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理
        $where         = [];
        $where[]       = ['id', '=', $params['id']];
        $result        = $ShopOrderInit->get_find($where);
        if (empty($result)) $this->error("暂无数据");
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        //快递公司
        $express = Db::name('express')->select();
        $this->assign('express', $express);

        return $this->fetch();
    }


    //发货提交
    public function send_post()
    {
        $ShopOrderInit = new ShopOrderInit();//订单管理

        $params     = $this->request->param();
        $order_info = $ShopOrderInit->get_find($params['id']);
        if (empty($order_info)) $this->error('订单信息错误');

        //快递信息
        $express_info = Db::name('express')->find($params['exp_id']);

        //更改订单信息
        $params['exp_name']  = $express_info['name'];//快递名称
        $params['status']    = 4;
        $params['send_time'] = time();
        $ShopOrderInit->edit_post($params);


        //        $map     = [];
        //        $map[]   = ['order_num', '=', $order_info['order_num']];
        //        $map[]   = ['status', '=', 2];
        //        $pay_num = Db::name('order_pay')->where($map)->value('pay_num');
        //
        //        //微信支付&发货
        //        if ($order_info['pay_type'] != 2) {
        //            $phone   = $order_info['phone'];
        //            $exp_num = $params['exp_num'];
        //            //发货
        //            $openid           = $order_info['openid'];
        //            $WeChatController = new WeChatController();
        //
        //
        //            if ($params['is_virtual'] == 2) {
        //                //虚拟发货
        //                $send_result = $WeChatController->uploadShippingInfo($pay_num, $openid, '订单发货', 3);
        //            } else {
        //                //快递发货
        //                $send_result = $WeChatController->uploadShippingInfo($pay_num, $openid, '订单发货', 1, $express_info['abbr'], $exp_num, $phone);
        //            }
        //
        //            if ($send_result) {
        //                Log::write('uploadShippingInfo-');
        //                Log::write($send_result);
        //            }
        //        }


        $this->success('发货成功');
    }


    //删除
    public function delete()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where = [];
        if ($params['id']) $where[] = ['id', '=', $params['id']];
        if (empty($params['id'])) {
            $ids     = $this->request->param('ids/a');
            $where[] = ['id', 'in', $ids];
        }


        $result = $ShopOrderInit->delete_post($where);
        if (empty($result)) $this->error('失败请重试');


        $this->success("删除成功", 'index' . $this->params_url);
    }


    //修改状态
    public function status_post()
    {
        $params        = $this->request->param();
        $status        = $this->request->param('status');
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $id = $this->request->param('id/a');


        if (empty($id)) $id = $this->request->param('ids/a');
        if (empty($id) || $status == '') $this->error('参数错误');


        $result = $ShopOrderInit->status_post($id, $status);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //退款拒绝
    public function refuse()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //退款操作,退款全部金额
    public function reject_post()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理
        $Pay           = new PayController();


        if ($params['status'] == 14) $params['refund_reject_time'] = time();


        if ($params['status'] == 16) {
            $order_info = $ShopOrderInit->get_find($params['id']);
            //退款金额
            $refund_amount = $order_info['amount'];
            if ($order_info['pay_type'] == 2) $refund_amount = $order_info['balance'];
            //退款通过时间
            $params['refund_pass_time'] = time();

            //获取支付单号
            $map      = [];
            $map[]    = ['order_num', '=', $order_info['order_num']];
            $map[]    = ['status', '=', 2];
            $pay_info = Db::name('order_pay')->where($map)->find();

            //退款 && 微信退款
            if ($order_info['pay_type'] == 1) {
                $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['order_num'], $refund_amount, $refund_amount);//后台同意退款 && 全额退款
                $refund_result = $refund_result['data'];
                if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
            }
            //余额退款
            if ($order_info['pay_type'] == 2) {
                $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
                $remark            = "操作人[{$admin_id_and_name}];操作说明[同意退款订单:{$order_info['order_num']};金额:{$order_info['balance']}];操作类型[管理员同意退款申请];";//管理备注
                BalanceModel::inc_balance('member', $order_info['user_id'], $refund_amount, '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);
            }
            //组合支付 &&微信+余额
            if ($order_info['pay_type'] == 5) {
                //余额
                $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
                $remark            = "操作人[{$admin_id_and_name}];操作说明[同意退款订单:{$order_info['order_num']};金额:{$order_info['balance']}];操作类型[管理员同意退款申请];";//管理备注
                MemberModel::inc_balance($order_info['user_id'], $order_info['balance'], '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);

                //微信
                $refund_amount = $order_info['amount'];
                $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['order_num'], $refund_amount);//后台同意退款 && 全额退款
                $refund_result = $refund_result['data'];
                if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
            }
        }


        $result = $ShopOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //部分金额退款
    public function reject_post2()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理
        $Pay           = new PayController();

        //退款金额
        $refund_amount              = $params['refund_amount'];
        $order_info                 = $ShopOrderInit->get_find($params['id']);
        $params['refund_pass_time'] = time();//退款通过时间
        $params['status']           = 16;


        //获取支付单号
        $map      = [];
        $map[]    = ['order_num', '=', $order_info['order_num']];
        $map[]    = ['status', '=', 2];
        $pay_info = Db::name('order_pay')->where($map)->find();

        if ($refund_amount > $order_info['amount']) $this->error('请输入有效金额!');


        //退款 && 微信退款
        if ($order_info['pay_type'] == 1) {
            $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['order_num'], $refund_amount);//后台同意部分退款
            $refund_result = $refund_result['data'];
            if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
        }
        //余额退款
        if ($order_info['pay_type'] == 2) {
            $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
            $remark            = "操作人[{$admin_id_and_name}];操作说明[同意退款订单:{$order_info['order_num']};金额:{$order_info['balance']}];操作类型[管理员同意退款申请];";//管理备注
            MemberModel::inc_balance($order_info['user_id'], $refund_amount, '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);
        }
        //组合支付 &&微信+余额
        if ($order_info['pay_type'] == 5) {
            //余额
            $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息
            $remark            = "操作人[{$admin_id_and_name}];操作说明[同意退款订单:{$order_info['order_num']};金额:{$order_info['balance']}];操作类型[管理员同意退款申请];";//管理备注
            MemberModel::inc_balance($order_info['user_id'], $order_info['balance'], '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);

            //微信
            $refund_amount = $order_info['amount'];
            $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['order_num'], $refund_amount);//后台同意部分退款
            $refund_result = $refund_result['data'];
            if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
        }


        $result = $ShopOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //拒绝理由
    public function refund_why()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理
        $where         = [];
        $where[]       = ['id', '=', $params['id']];
        $result        = $ShopOrderInit->get_find($where);
        if (empty($result)) $this->error("暂无数据");
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //取消订单
    public function cancel_post()
    {
        $params             = $this->request->param();
        $ShopOrderInit      = new \init\ShopOrderInit();//订单管理    (ps:InitController)
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)
        $Pay                = new PayController();
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)


        $order_info = $ShopOrderModel->where('id', '=', $params['id'])->find();
        if (empty($order_info)) $this->error("暂无数据");

        //订单支付金额
        $amount = $order_info['amount'];
        //补偿金额
        $compensate_amount = $order_info['compensate_amount'];
        //退还金额
        $refund_amount = $order_info['refund_amount'];
        //通过
        if ($params['cancel_status'] == 2) {
            $params['pass_time'] = time();


            //补偿给技师 &&  后台审核通过给余额
            $remark = "操作人[用户取消订单,给技师补偿];操作说明[订单补偿:{$compensate_amount};订单号:{$order_info['order_num']}];操作类型[用户取消订单];";//管理备注
            BalanceModel::inc_balance('technician', $order_info['technician_id'], $compensate_amount, '用户取消订单', $remark, $order_info['id'], $order_info['order_num'], 210);


            //获取支付单号  && 后台审核通过退款
            $map      = [];
            $map[]    = ['order_num', '=', $order_info['order_num']];
            $map[]    = ['status', '=', 2];
            $pay_info = Db::name('order_pay')->where($map)->find();


            //退款 && 微信退款
            if ($order_info['pay_type'] == 1) {
                $refund_result = $Pay->wx_pay_refund($pay_info['trade_num'], $pay_info['pay_num'], $refund_amount, $amount);//后台取消订单,退款
                $refund_result = $refund_result['data'];
                if ($refund_result['result_code'] != 'SUCCESS') $this->error($refund_result['err_code_des']);
            }
            //余额退款
            if ($order_info['pay_type'] == 2) {
                $remark = "操作人[用户取消订单];操作说明[同意退款订单:{$order_info['order_num']};金额:{$refund_amount}];操作类型[用户取消订单];";//管理备注
                BalanceModel::inc_balance('member', $order_info['user_id'], $refund_amount, '订单退款成功', $remark, $order_info['id'], $order_info['order_num'], 200);
            }


            /** 释放技师时间 && 后台审核通过释放时间 **/
            $map2   = [];
            $map2[] = ['order_num', '=', $order_info['order_num']];
            $ShopOrderSaveModel->where($map2)->update([
                'status'             => 2,//取消
                'operation_end_time' => time(),
                'update_time'        => time(),
            ]);
        }

        //拒绝
        if ($params['cancel_status'] == 3) $params['refuse_time'] = time();


        $result = $ShopOrderInit->api_edit_post($params);
        if (empty($result)) $this->error('失败请重试');


        $this->success("保存成功", 'index' . $this->params_url);
    }


    /**
     * 导出数据
     * @param array $where 条件
     */
    public function export_excel($where = [], $params = [])
    {
        $ShopOrderInit = new ShopOrderInit();//订单管理

        $result = $ShopOrderInit->get_list($where, $params);
        $result = $result->toArray();

        foreach ($result as $k => &$item) {

            //订单号过长问题
            if ($item["order_num"]) $item["order_num"] = $item["order_num"] . "\t";

            //图片链接 可用默认浏览器打开   后面为展示链接名字 --单独,多图特殊处理一下
            if ($item["image"]) $item["image"] = '=HYPERLINK("' . cmf_get_asset_url($item['image']) . '","图片.png")';

            //商品信息
            $goodsInfo = '';
            foreach ($item['goods_list'] as $goods) {
                $goodsInfo .= "名称:{$goods['goods_name']}\n";
                if ($goods['sku_name']) $goodsInfo .= "规格:{$goods['sku_name']}\n";
                $goodsInfo .= "数量:{$goods['count']}\n";
                $goodsInfo .= "单价:{$goods['goods_price']}\n\n\n";
            }
            $item['goodsInfo'] = $goodsInfo;


            //地址信息
            $addressInfo         = "地址:{$item['province']}-{$item['city']}-{$item['county']}{$item['address']}\n";
            $addressInfo         .= "姓名:{$item['username']}\n";
            $addressInfo         .= "电话:{$item['phone']}\n";
            $item['addressInfo'] = $addressInfo;

            //物流信息
            if ($item['exp_name'] || $item['exp_num']) {
                $expInfo         = "快递名称:{$item['exp_name']}\n";
                $expInfo         .= "快递单号:{$item['exp_num']}\n";
                $item['expInfo'] = $expInfo;
            }

            //用户信息
            $user_info        = $item['user_info'];
            $item['userInfo'] = "(ID:{$user_info['id']}) {$user_info['nickname']}  {$user_info['phone']}";
        }

        $headArrValue = [
            ["rowName" => "ID", "rowVal" => "id", "width" => 10],
            ["rowName" => "用户信息", "rowVal" => "userInfo", "width" => 30],
            ["rowName" => "订单号", "rowVal" => "order_num", "width" => 30],
            ["rowName" => "状态", "rowVal" => "status_text", "width" => 30],
            ["rowName" => "支付方式", "rowVal" => "pay_type_text", "width" => 30],
            ["rowName" => "订单金额", "rowVal" => "total_amount", "width" => 30],
            ["rowName" => "收货地址", "rowVal" => "addressInfo", "width" => 30],
            ["rowName" => "商品信息", "rowVal" => "goodsInfo", "width" => 30],
            ["rowName" => "物流信息", "rowVal" => "expInfo", "width" => 30],
            ["rowName" => "创建时间", "rowVal" => "create_time", "width" => 30],
        ];


        //副标题 纵单元格
        //        $subtitle = [
        //            ["rowName" => "列1", "acrossCells" => 2],
        //            ["rowName" => "列2", "acrossCells" => 2],
        //        ];

        $Excel = new ExcelController();
        $Excel->excelExports($result, $headArrValue, ["fileName" => "导出"]);
    }


    //更换技师
    public function update_technician()
    {
        $params         = $this->request->param();
        $ShopOrderInit  = new ShopOrderInit();//订单管理
        $TechnicianInit = new \init\TechnicianInit();//技师管理   (ps:InitController)


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");
        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }


        $this->assign('technician_list', $TechnicianInit->get_list());


        return $this->fetch();
    }


    //更换技师  提交编辑
    public function update_technician_post()
    {
        // 启动事务
        Db::startTrans();

        $params             = $this->request->param();
        $ShopOrderInit      = new ShopOrderInit();//订单管理
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $InitController     = new \api\wxapp\controller\InitController();


        //订单详情
        $where      = [];
        $where[]    = ['id', '=', $params['id']];
        $order_info = $ShopOrderInit->get_find($where);

        $map   = [];
        $map[] = ['order_num', '=', $order_info['order_num']];

        //检测技师时间是否已被预约
        $is_make = $InitController->isTimeSlotAvailable($params['technician_id'], $order_info['begin_time'], $order_info['end_time']);//更换技师
        if ($is_make) $this->error('该技师时间冲突不可以预约');

        //订单更新
        $result = $ShopOrderModel->where($map)->strict(false)->update(['technician_id' => $params['technician_id'], 'update_time' => time()]);
        if (empty($result)) $this->error('失败请重试');

        //预约记录更新
        $result = $ShopOrderSaveModel->where($map)->strict(false)->update(['technician_id' => $params['technician_id'], 'update_time' => time()]);
        if (empty($result)) $this->error('失败请重试');


        // 提交事务
        Db::commit();


        $this->success("保存成功", 'index' . $this->params_url);
    }


    //修改预约时间
    public function update_make_time()
    {
        $params        = $this->request->param();
        $ShopOrderInit = new ShopOrderInit();//订单管理


        $where   = [];
        $where[] = ['id', '=', $params['id']];
        $result  = $ShopOrderInit->get_find($where);


        if (empty($result)) $this->error("暂无数据");

        $toArray = $result->toArray();
        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }

        return $this->fetch();
    }


    //修改预约时间 提交编辑
    public function update_make_time_post()
    {
        // 启动事务
        Db::startTrans();

        $params             = $this->request->param();
        $ShopOrderInit      = new ShopOrderInit();//订单管理
        $ShopOrderModel     = new \initmodel\ShopOrderModel(); //订单管理  (ps:InitModel)
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)
        $InitController     = new \api\wxapp\controller\InitController();


        //订单详情
        $where      = [];
        $where[]    = ['order_num', '=', $params['order_num']];
        $order_info = $ShopOrderModel->where($where)->find();
        if (empty($order_info)) $this->error("暂无数据");

        //算出开始时间结束时间
        $begin_time = strtotime($params['begin_time']);
        $end_time   = $begin_time + (60 * $order_info['total_minute']);


        //检测技师时间是否已被预约
        $is_make = $InitController->isTimeSlotAvailable($order_info['technician_id'], $begin_time, $end_time);//修改预约时间
        if ($is_make) $this->error('该技师时间冲突不可以预约');

        //订单更新
        $result = $ShopOrderModel->where($where)->strict(false)->update([
            'begin_time'  => $begin_time,
            'end_time'    => $end_time,
            'update_time' => time()
        ]);
        if (empty($result)) $this->error('失败请重试');

        //预约记录更新
        $result = $ShopOrderSaveModel->where($where)->strict(false)->update([
            'begin_time'  => $begin_time,
            'end_time'    => $end_time,
            'update_time' => time()
        ]);
        if (empty($result)) $this->error('失败请重试');


        // 提交事务
        Db::commit();


        $this->success("保存成功", 'index' . $this->params_url);
    }

}
