<?php

namespace api\wxapp\controller;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;
use WeChat\Contracts\BasicWeChat;
use WeChat\Media;

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------


/**
 * 微信通知处理基本类 Class BasicPushEvent
 * ackage:
 * WeChat\Contracts
 *
 * 住: 公众号配置当前服务器ip否则获取到token报错
 */
class SendTempMsgController extends AuthController
{
    public const QR_ACTION_NAME     = 'QR_STR_SCENE';//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
    public const QR_LIMIT_STR_SCENE = 'QR_LIMIT_STR_SCENE';//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
    public const QR_EXPIRE_SECONDS  = 60;//该二维码有效时间，以秒为单位。 最大不超过2592000（即30天），此字段如果不填，则默认有效期为60秒。


    public function initialize()
    {
        parent::initialize();//初始化方法

        $plugin_config        = cmf_get_option('weipay');
        $this->wx_system_type = 'wx_mp';//默认 读配置可手动修改


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
            'appid'             => $appid,
            'appsecret'         => $appsecret,
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
     * 将公众号的official_openid存入member表中   可以在用户授权登录成功后操作
     */
    public function update_official_openid()
    {
        $gzh_list = Db::name('member_gzh')->select();
        foreach ($gzh_list as $k => $v) {
            Db::name('member')->where('unionid', '=', $v['unionid'])
                ->update(['official_openid' => $v['openid'], 'update_time' => time()]);
        }
    }


    /**
     * 字符串处理 大于n个字符显示省略号
     * @param $str   字符
     * @param $limit 长度
     * @return string
     */
    function processString($str = '', $limit = 18)
    {
        if (mb_strlen($str) > $limit) {
            return mb_substr($str, 0, $limit) . '..';
        }
        return $str;
    }

    /**
     * 公众号配置下 给微信配置域名  关注或取消关注执行
     * 公众号操作,获取用户信息,获取unionid   公众号自动回复等功能
     * @return void
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \think\db\exception\DbException
     *
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/find_official
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/find_official
     *   api: /wxapp/send_temp_msg/find_official
     *
     * 相关文档:https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Passive_user_reply_message.html
     */
    public function find_official()
    {
        $signature = $_GET["signature"];//微信 返回
        $timestamp = $_GET["timestamp"];//微信 返回
        $nonce     = $_GET["nonce"];//微信 返回
        $token     = $this->wx_config['token'];//配置
        $tmpArr    = array($token, $timestamp, $nonce);


        //处理数据格式
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        Log::write('get_params');
        Log::write($_GET);
        Log::write('tmpStr');
        Log::write($tmpStr);


        $WeChat      = new \WeChat\Contracts\BasicPushEvent($this->wx_config);
        $getReceive  = $WeChat->getReceive();//获取公众号推送对象
        $openid      = $WeChat->getOpenid();//获取当前用户openid
        $getToOpenid = $WeChat->getToOpenid();//获取当前推送公众号
        $getMsgType  = $WeChat->getMsgType();//获取当前推送消息类型

        Log::write('getReceive');
        Log::write($getReceive);
        Log::write('openid');
        Log::write($openid);
        Log::write('getToOpenid');
        Log::write($getToOpenid);
        Log::write('getMsgType');
        Log::write($getMsgType);


        //拿到access_token
        $access_token = $this->get_stable_access_token();
        Log::write('access_token');
        Log::write($access_token);


        //关注或取消关注操作
        if ($getMsgType == 'event') {
            if ($access_token && $openid) {
                //获取用户信息 拿到unionid
                $user_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
                Log::write('user_url');
                Log::write($user_url);
                $result = $this->getSslPage($user_url);
                $result = json_decode($result, true);
                Log::write('userinfo');
                Log::write($result);

                if (empty($result['unionid']) || empty($result['openid'])) {
                    Log::write('userinfo:error');
                    Log::write($result);
                }


                if ($result['unionid']) {
                    //插入公众号用户表
                    Db::name('member_gzh')->where(['openid' => $result['openid']])->delete();
                    Db::name('member_gzh')->insert([
                        'ext'         => $result,
                        'openid'      => $result['openid'],
                        'unionid'     => $result['unionid'],
                        'create_time' => time(),
                    ]);


                    //更新用户表&这里的unionid是请求接口拿到的,所以会慢必须判断存在了在执行下面操作
                    $this->update_official_openid();
                }
            }
        }


        //主动发信息
        if ($getMsgType == 'text') {
            Log::write('Content');
            Log::write($getReceive['Content']);

            if ($getReceive['Content'] == '登录') {
                //用户发送登录文字,回复指定关键字
            }


            //应用名称
            $app_name = cmf_config('app_name');
            //域名
            $domain_name              = cmf_config('domain_name');
            $address_official_account = $domain_name . '/h5';
            //跳转公众号
            $aLink     = '<a target="_blank" href="' . $address_official_account . '">点我下单</a>';
            $send_data = [
                'ToUserName'   => $openid,
                'FromUserName' => $getToOpenid,
                'CreateTime'   => time(),
                'MsgType'      => 'text',
                'Content'      => "欢迎关注{$app_name},{$aLink}",
            ];
            $send      = $WeChat->reply($send_data, true);
            //想要回复信息,必须执行一下
            exit($send);
        }


        //点击事件,发送文字消息
        if ($getMsgType == 'event') {
            Log::write('Content');
            Log::write($getReceive['Content']);

            //key值
            if ($getReceive['EventKey'] == 'PlatformIntroduction') $content = '敬请期待';
            if ($getReceive['EventKey'] == 'ContactService') $content = "联系电话:022-58099879\n工作时间:周一至周五 10:00-17:00";

            $send_data = [
                'ToUserName'   => $openid,
                'FromUserName' => $getToOpenid,
                'CreateTime'   => time(),
                'MsgType'      => 'text',
                'Content'      => $content,
            ];
            $send      = $WeChat->reply($send_data, true);

            //想要回复信息,必须执行一下
            //if ($content) exit($send);
        }

        //用于验证设置的token,关联公众号后台,给公众号返回的结果
        if ($tmpStr == $signature) {
            ob_clean();
            echo $_GET['echostr'];
            Log::write('find_official_token_echostr');
            Log::write($_GET);
            exit();
        } else {
            echo '失败';
            exit;
        }
    }


    /**
     * 创建公众号 临时二维码
     * 可带参数
     * 用户扫码后,进入公众号会携带参数以及ticket
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/qrcode_create
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/qrcode_create
     *   api: /wxapp/send_temp_msg/qrcode_create
     *
     * 相关文档:
     * https://developers.weixin.qq.com/doc/offiaccount/Account_Management/Generating_a_Parametric_QR_Code.html
     */
    public function qrcode_create()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$this->get_stable_access_token()}";


        //$params['expire_seconds'] = self::QR_EXPIRE_SECONDS;//(如果永久类型,此字段不传)该二维码有效时间，以秒为单位。 最大不超过2592000（即30天），此字段如果不填，则默认有效期为60秒。
        $params['action_name'] = self::QR_LIMIT_STR_SCENE;//二维码类型，QR_SCENE为临时的整型参数值，QR_STR_SCENE为临时的字符串参数值，QR_LIMIT_SCENE为永久的整型参数值，QR_LIMIT_STR_SCENE为永久的字符串参数值
        $params['action_info'] = ['scene' => ['scene_str' => 'lalalalal' . cmf_order_sn()]];

        $result = $this->http_request($url, json_encode($params));
        $result = json_decode($result, true);


        $get_image = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket={$result['ticket']}";

        $result1 = $this->getSslPage($get_image);

        //$result['ticket'] 和 scene_str存入数据库,如有人关注公众号扫码,或扫码操作

        $this->success('请求链接,获取临时二维码', $get_image);
        //        header("Location: $get_image");
        //        exit();
    }


    /**
     * 发送模板消息
     * 参考文档 https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html
     * @param string  $openid
     * @param integer $template_id 模板ID
     * @param array   $data        模板数据
     * @param array   $pagepath    小程序路径
     * @param int     $type        消息模板类型 1:模板消息 2:订阅消息
     * @param string  $url         消息模板跳转url，可不填
     * @param string  $color       主题字体颜色
     * @return  string
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/sendTempMsg
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/sendTempMsg
     *   api: /wxapp/send_temp_msg/sendTempMsg
     *
     */
    public function sendTempMsg($openid = '', $template_id = '', $send_data = [], $pagepath = 'pages/index/index', $type = 1, $urls = '', $color = '#173177')
    {
        //$template_id = '*******';

        //        $send_data = [
        //            'first'    => ['value' => '您好，您有一个订单'],
        //            'keyword1' => ['value' => cmf_get_order_sn()],
        //            'keyword2' => ['value' => date('Y-m-d H:i:s')],
        //            'keyword3' => ['value' => '张三'],
        //            'keyword4' => ['value' => '测试地址'],
        //        ];


        $miniprogram  = ['appid' => $this->wx_config['wx_mini_appid'], 'pagepath' => $pagepath];
        $access_token = $this->get_stable_access_token();//获取token
        //模板消息
        $template_data = $this->_sendTempMsg($openid, $template_id, $urls, $color, $send_data, []);
        //模板消息类型
        $type   = ($type == 1) ? 'send' : 'subscribe';
        $url    = "https://api.weixin.qq.com/cgi-bin/message/template/{$type}?access_token={$access_token}";
        $result = $this->http_request($url, $template_data);
        $result = json_decode($result, true);


        Log::write("wxSendTempMsg");
        Log::write("发送公众号提醒:{$template_id}");
        Log::write("通知人:{$openid}");
        Log::write("通知内容:");
        Log::write($send_data);


        Log::write("通知结果:");
        Log::write($result);
        return $result;


        //        if ($result['errcode'] == 0) {
        //            return true;
        //        } else {
        //            //失败返回错误信息
        //            return $result;
        //        }


        //        if ($result['errcode'] == 0) {
        //            return '发送成功';
        //        } else {
        //            //失败返回错误信息
        //            return $result;
        //        }
    }


    /**
     * 测试使用
     * @param string $openid
     * @param string $template_id
     * @param array  $send_data
     * @param string $pagepath
     * @param int    $type
     * @param string $urls
     * @param string $color
     *
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/send_temp_msg_test
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/send_temp_msg_test
     *   api: /wxapp/send_temp_msg/send_temp_msg_test
     *
     */
    public function send_temp_msg_test($openid = '', $template_id = '', $send_data = [], $pagepath = 'pages/index/index', $type = 1, $urls = '', $color = '#173177')
    {
        $openid      = '**********';
        $template_id = '*******';
        $urls        = cmf_get_domain() . "/h5/#/pages/technician/orderDetail?orderNum=1111";
        $send_data   = [
            'first'    => ['value' => '您好，您有一个订单'],
            'keyword1' => ['value' => cmf_order_sn(4)],
            'keyword2' => ['value' => date('Y-m-d H:i:s')],
            'keyword3' => ['value' => '张三'],
            'keyword4' => ['value' => '测试地址'],
        ];


        $miniprogram  = ['appid' => $this->wx_config['wx_mini_appid'], 'pagepath' => $pagepath];
        $access_token = $this->get_stable_access_token();//获取token

        //模板消息
        $template_data = $this->_sendTempMsg($openid, $template_id, $urls, $color, $send_data, $miniprogram);

        //模板消息类型
        $type   = ($type == 1) ? 'send' : 'subscribe';
        $url    = "https://api.weixin.qq.com/cgi-bin/message/template/{$type}?access_token={$access_token}";
        $result = $this->http_request($url, $template_data);
        $result = json_decode($result, true);

        Log::write("wxSendTempMsgTest");
        Log::write("发送公众号提醒Test:{$template_id}");
        Log::write("通知人Test:{$openid}");
        Log::write("通知内容Test:");
        Log::write($send_data);


        Log::write("通知结果Test:");
        Log::write($result);

        $this->success('', $result);
    }


    /**
     * 增加菜单   用浏览器直接访问下 然后就可以直接更新了
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/addWeixinMenu
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/addWeixinMenu
     *   api: /wxapp/send_temp_msg/addWeixinMenu
     *
     */
    public function addWeixinMenu()
    {
        $access_token = $this->get_stable_access_token();//获取token
        $jsonmenu     = '{
                 "button":[
                 {	
                      "type":"view",
                      "name":"拓客文章",
                      "url":"https://dz264.aulod.com/h5/#/"
                  },
                  {	
                      "type":"view",
                      "name":"我的房源",
                      "url":"https://dz264.aulod.com/h5/#/pages/house/index"
                  },
                  {
                           "name":"关于我们",
                           "sub_button":[{
                               "type":"click",
                               "name":"平台介绍",
                               "key":"PlatformIntroduction"
                            },
                            {
                               "type":"click",
                               "name":"联系客服",
                               "key":"ContactService"
                            }]
                  }
				  {
					 "type":"miniprogram",
					 "name":"快汇收",
					 "url":"http://mp.weixin.qq.com",
					 "appid":"wxaf99fcb7d5c489df",
					 "pagepath":"pages/index/index"
				 }]
             }';
        $url          = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $access_token;
        $result       = $this->http_request($url, $jsonmenu);
        dump($result);
    }


    /**
     * 获取微信资源信息,图片,视频,音频
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/batch_get_material
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/batch_get_material
     *   api: /wxapp/send_temp_msg/batch_get_material
     *
     */
    public function batch_get_material()
    {
        $Media = new Media($this->wx_config);
        dump($Media->batchGetMaterial('video'));
        exit();
    }


    /**
     * 处理发送模板消息
     * @param $openid
     * @param $template_id
     * @param $url
     * @param $color
     * @param $data
     * @param $miniprogram
     * @return false|string
     */
    private function _sendTempMsg($openid, $template_id, $url, $color, $data, $miniprogram)
    {
        //模板消息
        $template_data = [
            'touser'      => $openid, //用户openid
            'template_id' => $template_id, //在公众号下配置的模板id
            'miniprogram' => $miniprogram,
            'url'         => $url, //点击模板消息会跳转的链接
            'color'       => $color,//消息字体颜色，不填默认为黑色
            'data'        => $data,
        ];
        $template_data = json_encode($template_data);
        return $template_data;
    }


    /**
     * 公众号,发送模板消息稳定获取token
     * 请求:https://api.weixin.qq.com/cgi-bin/stable_token
     * 获取公众号全局后台接口调用凭据，有效期最长为7200s，开发者需要进行妥善保存；
     * 有两种调用模式: 1. 普通模式，access_token 有效期内重复调用该接口不会更新 access_token，绝大部分场景下使用该模式；2. 强制刷新模式，会导致上次获取的 access_token 失效，并返回新的 access_token；
     * 该接口调用频率限制为 1万次 每分钟，每天限制调用 50w 次；
     * 与获取Access token获取的调用凭证完全隔离，互不影响。该接口仅支持 POST JSON 形式的调用；
     *
     *   test_environment: http://makeTemplate.ikun/api/wxapp/send_temp_msg/get_stable_access_token
     *   official_environment: https://ljh.wxselling.net/api/wxapp/send_temp_msg/get_stable_access_token
     *   api: /wxapp/send_temp_msg/get_stable_access_token
     *
     *  只针对公众号
     */
    public function get_stable_access_token()
    {
        //获取插件格式,(注意公众号,和小程序的appid)
        $BasicWeChat  = new BasicWeChat($this->wx_config);
        $access_token = $BasicWeChat->getAccessToken();
        return $access_token;


        $token = Cache::get('get_stable_access_token');
        if (!$token) {
            $appid  = $this->wx_config['wx_mp_appid'];
            $secret = $this->wx_config['wx_mp_appsecret'];
            $url2   = 'https://api.weixin.qq.com/cgi-bin/stable_token';
            //小程序信息获取token
            $param['grant_type'] = 'client_credential';
            $param['appid']      = $appid;
            $param['secret']     = $secret;
            $result              = $this->http_request($url2, json_encode($param));
            $result              = json_decode($result, true);
            $token               = $result['access_token'];
            Cache::set('get_stable_access_token', $token, 7000);
        }

        return $token;
    }


    /**
     * post请求
     * @param      $url
     * @param null $data
     * @return bool|string
     */
    private function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    /**
     * get请求
     * @param $url
     * @return bool|string
     */
    function getSslPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}