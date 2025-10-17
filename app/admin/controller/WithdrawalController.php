<?php

namespace app\admin\controller;

use initmodel\BalanceModel;
use initmodel\PointModel;
use think\db\Query;
use think\facade\Db;
use cmf\controller\AdminBaseController;


class WithdrawalController extends AdminBaseController
{

    public function initialize()
    {
        parent::initialize();

        $this->type_array    = [1 => '支付宝', 2 => '微信'];
        $this->status_array  = [1 => '待审核', 2 => '已审核', 3 => '已拒绝'];
        $this->identity_type = ['member' => '用户', 'technician' => '技师'];
        $this->assign('status_list', $this->status_array);

    }


    /**
     * 提现记录查询
     */
    public function index()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $MemberInit            = new \init\MemberInit();//会员管理 (ps:InitController)
        $TechnicianInit        = new \init\TechnicianInit();//技师管理    (ps:InitController)

        $params = $this->request->param();


        $where   = [];
        $where[] = ['id', '>', 0];
        if (isset($params['keyword']) && $params['keyword']) $where[] = ['ali_username|ali_account', 'like', "%{$params['keyword']}%"];
        if (isset($params['status']) && $params['status']) $where[] = ['status', '=', $params['status']];
        if ($params['user_id']) $where[] = ['user_id', '=', $params['user_id']];
        if ($params['identity_type']) $where[] = ['identity_type', '=', $params['identity_type']];
        $where[] = $this->getBetweenTime($params['beginTime'], $params['endTime'], 'create_time');


        $list = $MemberWithdrawalModel
            ->where($where)
            ->order("id desc")
            ->paginate(10)
            ->each(function ($item, $key) use ($MemberInit, $TechnicianInit) {
                if ($item['create_time']) $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);

                if ($item['identity_type'] == 'member') {
                    $item['user_info'] = $MemberInit->get_find($item['user_id']);
                } else {
                    $user_info           = $TechnicianInit->get_find($item['user_id']);
                    $user_info['avatar'] = $user_info['avatar'][0];
                    $item['user_info']   = $user_info;
                }

                $item['type_name']          = $this->type_array[$item['type']];
                $item['status_name']        = $this->status_array[$item['status']];
                $item['identity_type_name'] = $this->identity_type[$item['identity_type']];


                return $item;
            });


        $list->appends($params);

        // 获取分页显示
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('list', $list);

        return $this->fetch();
    }


    /**
     * 修改状态
     */
    public function update_withdrawal()
    {
        // 启动事务
        Db::startTrans();

        $admin_id_and_name = cmf_get_current_admin_id() . '-' . session('name');//管理员信息


        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $params['update_time'] = time();


        //判断是否已处理
        $withdrawal_info = $MemberWithdrawalModel->where('id', $params['id'])->find();
        if ($withdrawal_info['status'] != 1) $this->error("已处理不能重复处理!");


        //更新
        $result = $MemberWithdrawalModel->where('id', $params['id'])
            ->strict(false)
            ->update($params);


        if ($result) {
            if ($params['status'] == 3) {
                $remark = "操作人[{$admin_id_and_name}];操作说明[提现驳回:{$params['refuse']}];操作类型[管理员驳回提现申请];";//管理备注
                //技师余额提现驳回
                if ($withdrawal_info['identity_type'] == 'technician') BalanceModel::inc_balance($withdrawal_info['identity_type'], $withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 900);


                //用户佣金提现驳回
                if ($withdrawal_info['identity_type'] == 'member') PointModel::inc_point($withdrawal_info['identity_type'], $withdrawal_info['user_id'], $withdrawal_info['price'], '提现驳回:' . $params['refuse'], $remark, $withdrawal_info['id'], $withdrawal_info['order_num'], 900);
            }

            // 提交事务
            Db::commit();

            $this->success("处理成功!");
        } else {
            $this->error("处理失败!");
        }
    }


    /**
     * 删除提现记录
     */
    public function delete_withdrawal()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $params                = $this->request->param();
        $result                = $MemberWithdrawalModel->where('id', $params['id'])->delete();
        if ($result) {
            $this->success("删除成功!");
        } else {
            $this->error("删除失败!");
        }
    }


    public function refuse()
    {
        $MemberWithdrawalModel = new \initmodel\MemberWithdrawalModel();//提现管理
        $id                    = $this->request->param('id');

        $result = $MemberWithdrawalModel->find($id);
        if (empty($result)) {
            $this->error("not found data");
        }
        $toArray = $result->toArray();

        foreach ($toArray as $k => $v) {
            $this->assign($k, $v);
        }
        return $this->fetch();
    }

}