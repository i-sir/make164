<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\lib\Storage;
use Exception;
use initmodel\MemberModel;
use think\facade\Cache;
use think\facade\Db;
use cmf\lib\Upload;
use think\facade\Log;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WeChat\Oauth;
use WeChat\Script;
use WeMini\Crypt;
use WeMini\Qrcode;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');


error_reporting(0);


class PublicController extends AuthController
{
    public $wx_config;


    public function initialize()
    {
        parent::initialize();// 初始化方法

        $plugin_config        = cmf_get_option('weipay');
        $this->wx_system_type = $plugin_config['wx_system_type'];//默认 读配置可手动修改
        if ($this->wx_system_type == 'wx_mini') {//wx_mini:小程序
            $appid     = $plugin_config['wx_mini_app_id'];
            $appsecret = $plugin_config['wx_mini_app_secret'];
        } else {//wx_mp:公众号
            $appid     = $plugin_config['wx_mp_app_id'];
            $appsecret = $plugin_config['wx_mp_app_secret'];
        }
        $this->wx_config = [
            //微信基本信息
            'token'             => $plugin_config['wx_token'],
            'wx_mini_appid'     => $plugin_config['wx_mini_app_id'],//小程序 appid
            'wx_mini_appsecret' => $plugin_config['wx_mini_app_secret'],//小程序 secret
            'wx_mp_appid'       => $plugin_config['wx_mp_app_id'],//公众号 appid
            'wx_mp_appsecret'   => $plugin_config['wx_mp_app_secret'],//公众号 secret
            'appid'             => $appid,//读取默认 appid
            'appsecret'         => $appsecret,//读取默认 secret
            'encodingaeskey'    => $plugin_config['wx_encodingaeskey'],
            // 配置商户支付参数
            'mch_id'            => $plugin_config['wx_mch_id'],
            'mch_key'           => $plugin_config['wx_v2_mch_secret_key'],
            // 配置商户支付双向证书目录 （p12 | key,cert 二选一，两者都配置时p12优先）
            //	'ssl_p12'        => __DIR__ . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . '1332187001_20181030_cert.p12',
            'ssl_key'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_secret_cert'],
            'ssl_cer'           => $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $plugin_config['wx_mch_public_cert_path'],
            // 配置缓存目录，需要拥有写权限
            'cache_path'        => './wx_cache_path',
            'wx_system_type'    => $this->wx_system_type,//wx_mini:小程序 wx_mp:公众号
        ];

    }


    /**
     * 测试接口
     *
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/index
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/index
     *   api: /wxapp/public/index
     *   remark_name: 测试接口
     *
     * @return void
     */
    public function index()
    {
        $SendTempMsgController = new SendTempMsgController();


        $code                = cmf_random_string(2);
        $result['code']      = $code;
        $result['md5_code']  = md5($code);
        $result['sha1_code'] = sha1($code);

        $formData = http_build_query($result);

        //技师入驻,通知管理员
        $notify_administrator = cmf_config('notify_the_administrator');

        //短信通知管理员
        $notify_administrator = $this->getParams($notify_administrator, '/');
        $ali_sms              = cmf_get_plugin_class("TencentcloudSms");
        $sms                  = new $ali_sms();
        //        foreach ($notify_administrator as $k => $v){
        //            $send_data['phone'] = $v;
        //            $send_data['template'] = '2269109';
        //            $sms->sendSms($send_data);
        //        }


        //公众号模板消息,模板id
        $official_account_template_message = cmf_config('official_account_template_message');


        //公众号通知
        $wx_openid   = 'ocVxA68ql6oDNG5Fawz_KlMjDMmI';
        $template_id = $official_account_template_message;//模板id
        $send_data   = [
            'time14'            => ['value' => '2023-05-18 17:00:00'],
            'thing13'           => ['value' => '测试'],
            'character_string2' => ['value' => '20230313001'],
            'thing15'           => ['value' => '按摩'],
        ];
        $aaa         = $SendTempMsgController->sendTempMsg($wx_openid, $template_id, $send_data);


        $this->success('', $aaa);
    }


    /**
     * 查询系统配置信息
     * @OA\Get(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_setting",
     *
     *
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/find_setting
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/find_setting
     *   api: /wxapp/public/find_setting
     *   remark_name: 查询系统配置信息
     *
     */
    public function find_setting()
    {
        $config = cmf_config();

        $map   = [];
        $map[] = ['is_menu', 'in', [1]];
        //$map[]     = ['is_menu', 'in', [1,3]];
        $menu_list = Db::name('base_config')->where($map)->select()->toArray();


        $result = [];
        foreach ($config as $k => $v) {
            if (in_array($v['type'], ['img', 'file', 'video'])) {
                $v['value']         = cmf_get_asset_url($v['value']);
                $result[$v['name']] = $v['value'];
            } elseif ($v['type'] == 'textarea') {
                if ($v['scatter']) $v['value'] = preg_replace("/\r\n/", "", explode($v['scatter'], $v['value']));
                $result[$v['name']] = $v['value'];
            } elseif ($v['data_type'] == 'array' && $v['type'] == 'custom') {
                $result[$v['name']] = explode('/', $v['value']);//自定义表格
            } else {
                $result[$v['name']] = $v['value'];
            }

            if ($v['type'] == 'content') {
                // 协议不在这里展示
                unset($result[$v['name']]);
            }

            if ($v['value'] == 'true') $result[$v['name']] = true;
            if ($v['value'] == 'false') $result[$v['name']] = false;

            if ($v['is_label']) {
                //插架格式
                $value     = $v['value'];
                $new_value = [];
                foreach ($value as $key => $val) {
                    $new_value[$key]['label']   = $val;
                    $new_value[$key]['value']   = $val;
                    $new_value[$key]['checked'] = false;
                }
                $result[$v['name']] = $new_value;
            }


            //程序配置,私有配置过滤
            if (in_array($v['group_id'], [888888, 999999])) unset($result[$v['name']]);


            //给注释显示在接口中
            $menu_key  = array_search($v['group_id'], array_column($menu_list, 'key'));
            $menu_name = $menu_list[$menu_key]['label'];
            if (empty($v['about'])) $annotation[$menu_name][] = [$v['name'] => "{$v['label']}"];
            if ($v['about']) $annotation[$menu_name][] = [$v['name'] => "{$v['label']} ({$v['about']})"];
        }
        $result['_字段注释'] = $annotation;

        $this->success("请求成功！", $result);
    }


    /**
     * 查询协议列表
     * @OA\Get(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_agreement_list",
     *
     *
     *      @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="协议name ,选填,如传详情,不传列表 ",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/find_agreement_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/find_agreement_list
     *   api: /wxapp/public/find_agreement_list
     *   remark_name: 查询协议列表
     *
     */
    public function find_agreement_list()
    {
        $params = $this->request->param();
        if ($params['name']) {
            $result = cmf_replace_content_file_url(htmlspecialchars_decode(cmf_config($params['name'])));
        } else {
            $config = cmf_config();
            $result = [];
            foreach ($config as $k => $v) {
                if ($v['type'] == 'content') {
                    if ($v['value']) $v['value'] = cmf_replace_content_file_url(htmlspecialchars_decode($v['value']));
                    $result[$v['name']] = $v['value'];
                } else {
                    unset($result[$v['name']]);
                }
            }
        }
        $this->success("请求成功！", $result);
    }


    /**
     * 上传图片&文件
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/upload_asset",
     *
     *
     *      @OA\Parameter(
     *         name="filetype",
     *         in="query",
     *         description="默认image,其他video，audio，file",
     *         required=true,
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/upload_asset
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/upload_asset
     *   api: /wxapp/public/upload_asset
     *   remark_name: 上传图片
     *
     */
    public function upload_asset()
    {
        if ($this->request->isPost()) {
            session('user.id', 1);
            $uploader = new Upload();
            $fileType = $this->request->param('filetype', 'image');
            $uploader->setFileType($fileType);
            $result = $uploader->upload();
            if ($result === false) {
                $this->error($uploader->getError());
            } else {
                // TODO  增其它文件的处理
                $result['preview_url'] = cmf_get_image_preview_url($result["filepath"]);
                $result['url']         = cmf_get_image_url($result["filepath"]);
                $result['filename']    = $result["name"];
                $this->success('上传成功!', $result);
            }
        }
    }


    /**
     * 查询幻灯片
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *
     *     path="/wxapp/public/find_slide",
     *
     *
     * 	   @OA\Parameter(
     *         name="slide_id",
     *         in="query",
     *         description="幻灯片分类ID，默认传1，可不传",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/find_slide
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/find_slide
     *   api: /wxapp/public/find_slide
     *   remark_name: 查询幻灯片
     *
     */
    public function find_slide()
    {
        $params = $this->request->param();

        if (empty($params['slide_id'])) $params['slide_id'] = 1;

        $map   = [];
        $map[] = ['slide_id', '=', $params['slide_id']];
        $map[] = ['status', '=', 1];

        $result = Db::name('slide_item')->field("*")->where($map)->order('list_order asc')->select()->each(function ($item) {
            $item['image'] = cmf_get_asset_url($item['image']);
            return $item;
        });
        $this->success("请求成功!", $result);
    }


    /**
     * 查询导航列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_navs",
     *
     *
     *    @OA\Parameter(
     *         name="nav_id",
     *         in="query",
     *         description="导航ID 默认为1",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/find_navs
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/find_navs
     *   api: /wxapp/public/find_navs
     *   remark_name: 查询导航列表
     *
     */
    public function find_navs()
    {
        $params = $this->request->param();

        if (empty($params['nav_id'])) $params['nav_id'] = 1;

        $map   = [];
        $map[] = ['nav_id', '=', $params['nav_id']];
        $map[] = ['status', '=', 1];
        $map[] = ['parent_id', '=', 0];


        $result = Db::name("nav_menu")
            ->where($map)
            ->order('list_order asc')
            ->select()
            ->each(function ($item) {
                if ($item['icon']) $item['icon'] = cmf_get_asset_url($item['icon']);
                return $item;
            });

        $this->success("请求成功！", $result);
    }


    /**
     * H5授权登录
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/h5_login",
     *
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="state",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/h5_login
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/h5_login
     *   api: /wxapp/public/h5_login
     *   remark_name: H5授权登录
     *
     */
    public function h5_login()
    {
        $MemberModel    = new \initmodel\MemberModel();//用户管理
        $InitController = new InitController();
        $ShopOrderModel = new \initmodel\ShopOrderModel(); //订单管理   (ps:InitModel)


        $params = $this->request->param();

        if (empty($params['code'])) $this->error('code不能为空');
        if (empty($params['state'])) $this->error('state不能为空');
        $state_arr = explode('/', $params['state']);
        $http      = $state_arr[0] . '//';
        $host      = $state_arr[2];

        $WeChat = new Oauth($this->wx_config);
        $return = $WeChat->getOauthAccessToken($params['code']);
        if (empty($return)) $this->error('登陆失败!');


        $h5_access_token = $return['access_token'];
        $openid          = $return['openid'];
        $UserData        = $WeChat->getUserInfo($h5_access_token, $openid);
        Log::write('h5_login:UserData');
        Log::write($UserData);

        //邀请板块
        //$state_arr[8] , 邀请码
        $pid = 0;
        if ($state_arr[8]) {
            $pid = $MemberModel->where('invite_code', '=', $state_arr[8])->value('id');

            //检测上级是否已成为分销员
            $order_required_consume = cmf_config('order_required_consume');  //有效下单金额满足n元后成为分销员

            //无效邀请,未成为分销员
            $effective_invitation = 2;

            $map          = [];
            $map[]        = ['status', 'in', [5, 8]];
            $map[]        = ['user_id', '=', $pid];
            $total_amount = $ShopOrderModel->where($map)->sum('amount');
            if ($total_amount >= $order_required_consume) $effective_invitation = 1;
        }

        $findUserInfo = $this->getUserInfoByOpenid($openid);
        Log::write('h5_login:$findUserInfo');
        Log::write($findUserInfo);
        if (empty($findUserInfo)) {
            //向数据库插入新用户信息
            $insert['nickname']             = urldecode($UserData['nickname']);
            $insert['avatar']               = $UserData['headimgurl'];
            $insert['openid']               = $UserData['openid'];
            $insert['invite_code']          = $this->get_invite_code();
            $insert['pid']                  = $pid;
            $insert['effective_invitation'] = $effective_invitation;
            $insert['createtime']           = time();
            $insert['create_time']          = time();
            $insert['login_time']           = time();
            $insert['ip']                   = get_client_ip();
            $insert['login_city']           = $this->get_ip_to_city();

            Log::write('h5_login:$insert');
            Log::write($insert);

            $user_id = $MemberModel->strict(false)->insert($insert, true);


            //更新插入openid表
            $InitController->set_openid($user_id, $openid, 'member');
        } else {
            //数据库已存在用户,更新用户登录信息
            $update['nickname']   = urldecode($UserData['nickname']);
            $update['avatar']     = $UserData['headimgurl'];
            $update['login_time'] = time();
            $update['ip']         = get_client_ip();
            $update['login_city'] = $this->get_ip_to_city();
            if (empty($findUserInfo['pid']) && $pid && $pid != $findUserInfo['id']) {
                $update['pid']                  = $pid;
                $update['effective_invitation'] = $effective_invitation;
            } elseif ($findUserInfo['pid'] && $pid && $pid != $findUserInfo['id']) {
                //检测上级是否已被删除
                $parent_user = $MemberModel->where('id', '=', $findUserInfo['pid'])->find();
                //绑定新的上级
                if (empty($parent_user)) {
                    $update['pid']                  = $pid;
                    $update['effective_invitation'] = $effective_invitation;
                }
            }

            $MemberModel->strict(false)->where('openid', $openid)->update($update);


            //更新插入openid表
            $InitController->set_openid($findUserInfo['id'], $openid, 'member');
        }


        header('Location:' . $http . $host . '/h5/#/pages/index/index?openid=' . $openid);
    }


    /**
     * h5 静默
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/h5_login2",
     *
     *
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="state",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/h5_login2
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/h5_login2
     *   api: /wxapp/public/h5_login2
     *   remark_name: H5 静默
     *
     */
    public function h5_login2()
    {
        $params = $this->request->param();

        if (empty($params['code'])) $this->error('code不能为空');
        if (empty($params['state'])) $this->error('state不能为空');
        $state_arr = explode('/', $params['state']);
        $http      = $state_arr[0] . '//';
        $host      = $state_arr[2];


        $WeChat = new Oauth($this->wx_config);
        $return = $WeChat->getOauthAccessToken($params['code']);
        if (empty($return)) $this->error('登陆失败!');


        header('Location:' . $http . $host . '/h5/#pages/technician/technician?openid=' . $return['openid']);
    }


    /**
     * 获取公众号分享签名
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/get_js_sign",
     *
     *
     *
     * 	   @OA\Parameter(
     *         name="url",
     *         in="query",
     *         description="url",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/get_js_sign
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/get_js_sign
     *   api: /wxapp/public/get_js_sign
     *   remark_name: 获取公众号分享签名
     *
     */
    public function get_js_sign()
    {
        $url    = $this->request->param('url');
        $WeChat = new Script($this->wx_config);
        $res    = $WeChat->getJsSign(urldecode($url));
        $this->success("请求成功！", $res);
    }


    /**
     * 获取手机验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/send_sms",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/send_sms
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/send_sms
     *   api: /wxapp/public/send_sms
     *   remark_name: 获取手机验证码
     *
     */
    public function send_sms()
    {
        $phone = $this->request->param('phone');
        $phone = trim($phone);
        if (empty($phone)) $this->error("手机号不能为空！");

        $ali_sms = cmf_get_plugin_class("HuYi");
        $sms     = new $ali_sms();
        $code    = cmf_get_verification_code($phone);

        $params = ["mobile" => $phone, "code" => $code];

        cmf_verification_code_log($phone, $code);
        $result = $sms->sendMobileVerificationCode($params);
        //        $result['code'] = 0;
        //        $result['msg'] = '暂无配置';


        if ($result['code'] == 0) {
            $this->success($result['msg']);
        } else {
            $this->error($result['msg']);
        }
    }


    /**
     * 电话语音通知
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/send_voice",
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
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/send_voice
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/send_voice
     *   api: /wxapp/public/send_voice
     *   remark_name: 电话语音通知
     *
     */
    public function send_voice()
    {
        $phone = $this->request->param('phone');
        $phone = trim($phone);
        if (empty($phone)) $this->error("手机号不能为空！");

        $ali_sms = cmf_get_plugin_class("HuYi");
        $sms     = new $ali_sms();

        $params = ["mobile" => $phone, 'content' => ''];
        $result = $sms->sendVoiceMsg($params);


        if ($result['code'] == 0) {
            $this->success($result['msg']);
        } else {
            $this->error($result['msg']);
        }
    }


    /**
     * 获取 省市区
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/find_area",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/find_area
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/find_area
     *   api: /wxapp/public/find_area
     *   remark_name: 获取 省市区
     *
     */
    public function find_area()
    {
        if (cache('region_list')) {
            $area = cache('region_list');
        } else {
            $area = Db::name('region')->where('parent_id', '=', 10000000)->select()->each(function ($item, $key) {
                $item['value']    = $item['code'];
                $item['label']    = $item['name'];
                $item['extra']    = $item['id'];
                $item['children'] = Db::name("region")->where(['parent_id' => $item['id']])->select()->each(function ($item1, $key) {

                    $item1['children'] = Db::name("region")->where(['parent_id' => $item1['id']])->select()->each(function ($item2, $key) {
                        $item2['value'] = $item2['code'];
                        $item2['label'] = $item2['name'];
                        $item2['extra'] = $item2['id'];

                        return $item2;
                    });

                    $item1['value'] = $item1['code'];
                    $item1['label'] = $item1['name'];
                    $item1['extra'] = $item1['id'];

                    return $item1;
                });

                return $item;
            });
            cache("region_list", $area);
        }

        $this->success('list', $area);
    }


    /**
     * 获取 省市区 code
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/area",
     *     @OA\Response(response="200", description="An example resource"),
     *     @OA\Response(response="default", description="An example resource")
     * )
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/area
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/area
     *   api: /wxapp/public/area
     *   remark_name: 获取 省市区
     *
     */
    public function area()
    {
        $params    = $this->request->param();
        $parent_id = Db::name('region')->where('code', '=', $params['code'])->value('id');
        $area      = Db::name('region')->where('parent_id', '=', $parent_id)->select();

        $this->success('list', $area);
    }


    /**
     * 翻译 误删
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     * @throws Exception
     * @OA\Post(
     *     tags={"小程序公共模块接口"},
     *     path="/wxapp/public/translate",
     *
     *
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="value",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/translate
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/translate
     *   api: /wxapp/public/translate
     *   remark_name: 翻译 误删
     *
     */
    public function translate()
    {
        $value = $this->request->param('value');

        $translate = new \init\TranslateInit();
        $result    = $translate->translate($value);

        if (isset($result) && $result) {
            $this->success('翻译结果', $result['trans_result'][0]['dst']);
        }
    }


    /**
     * 获取超稳定 access_token
     * 该接口调用频率限制为 1万次 每分钟，每天限制调用 50万 次
     * @return mixed
     *
     *
     *   test_environment: http://make164.ikun:9090/api/wxapp/public/get_stable_access_token
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/public/get_stable_access_token
     *   api: /wxapp/public/get_stable_access_token
     *   remark_name: 获取超稳定 access_token
     *
     */
    public function get_stable_access_token()
    {
        //        $Init = new \api\wxapp\controller\InitController();
        //        $aaa  = $Init->get_estimate_commission(20);
        //        dump($aaa);
        //        exit();

        $appid  = 'wxcecfab687710cd6a';
        $secret = 'c9fee3915c658ed5472b7e5bc328b195';
        $url2   = 'https://api.weixin.qq.com/cgi-bin/stable_token';
        //小程序信息获取token
        $param['grant_type'] = 'client_credential';
        $param['appid']      = $appid;
        $param['secret']     = $secret;
        $res                 = $this->curl_post($url2, json_encode($param));
        $data                = json_decode($res, true);
        $token               = $data['access_token'];
        return $token;
    }


}
