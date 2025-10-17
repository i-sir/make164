<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: wuwu <15093565100@163.com>
// +----------------------------------------------------------------------
namespace api\wxapp\controller;

use cmf\controller\RestBaseController;
use think\App;
use think\facade\Db;

header('Access-Control-Allow-Origin:*');
// 响应类型
header('Access-Control-Allow-Methods:*');
// 响应头设置
header('Access-Control-Allow-Headers:*');

error_reporting(0);

class AuthController extends RestBaseController
{

    public $user_info;
    public $user_id;
    public $openid;

    /**
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function initialize()
    {
        //账号到期全局拦截设置
        $app_expiration_time = cmf_config('app_expiration_time');//开发应用到期时间
        if (isset($app_expiration_time) && !empty($app_expiration_time) && strtotime($app_expiration_time) < time()) {
            $this->error("账号已到期,请联系管理员！");
        }


        $openid = $this->request->param('openid');//技师
        if (empty($openid)) $openid = $this->request->header('openid');//用户


        $this->openid = $openid;
        $user_info    = $this->getUserInfoByOpenid($openid);
        if (!empty($user_info)) {
            $this->user_info = $user_info;
            $this->user_id   = $user_info['id'];
        }


        // token 验证
        $token = $this->request->header('token');
        if (empty($token)) $token = $this->request->param('token');
        if (!empty($token)) {
            $this->token = $token;
            $userToken   = Db::name("user_token")->where('token', $token)->find();
            if ($userToken) {
                $MemberInit = new \init\MemberInit();//用户管理
                $map        = [];
                $map[]      = ['id', '=', $userToken['user_id']];
                $user_info  = $MemberInit->get_find($map);
                if (!empty($user_info) && $userToken['expire_time'] > time()) {
                    $this->user_info = $user_info;
                    $this->user_id   = $user_info['id'];
                }
            }
        }
    }

    // 接口日志
    public function __construct()
    {
        parent::__construct();

        self::add_log();
    }


    public function checkAuth()
    {
        if (empty($this->user_id)) $this->error('请先授权登录');
    }


    /**
     * 根据openid获取用户user_info   注:同步MemberModel
     * @param $openid
     * @param $where openid为空 条件筛选[]
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoByOpenid($openid, $where = null)
    {
        if (empty($openid)) return false;

        $MemberInit     = new \init\MemberInit();//用户管理
        $TechnicianInit = new \init\TechnicianInit();//技师管理    (ps:InitController)

        $map   = [];
        $map[] = ['openid', '=', $openid];


        $openid_info = Db::name('openid')->where($map)->order('id desc')->find();
        if ($openid_info['identity_type'] == 'technician') {
            $result                  = $TechnicianInit->get_find($map, ['field' => '*']);
            if (empty($result)) return false;
            if ($result['is_block'] == 1) $this->error('账号异常!');
            $result['identity_type'] = 'technician';
        }
        if ($openid_info['identity_type'] == 'member') {
            $result                  = $MemberInit->get_my_info($map, ['field' => '*']);
            if (empty($result)) return false;
            $result['identity_type'] = 'member';
        }



        return $result;
    }


    /**
     * 根据id获取用户user_info  注:同步MemberModel
     * @param $user_id 用户id
     * @param $where   id为空 条件筛选[]
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoById($user_id, $where = null)
    {
        if (empty($user_id)) return false;

        $MemberInit = new \init\MemberInit();//用户管理
        $map        = [];
        $map[]      = ['id', '=', $user_id];
        $result     = $MemberInit->get_find($map);
        return $result;
    }


    /**
     * 获取唯一单号,或者唯一code
     * @param $table_name 表名
     * @param $field_name 字段名
     * @param $length     长度 订单号类型,默认16位,原有长度-6位
     * @param $type       1:数子 2:数字+字母
     */
    public function get_only_num($table_name, $field_name = 'order_num', $length = 8, $type = 1)
    {
        if ($type == 1) $only_num = cmf_order_sn($length - 6);//订单号,默认16位,原有长度-6位
        if ($type == 2) $only_num = cmf_random_string($length);
        $is = Db::name("$table_name")->where($field_name, '=', $only_num)->count();
        if ($is) $this->get_only_num($table_name);
        return $only_num;
    }


    /**
     * 获取邀请码
     * @param $table_name 表名
     * @param $field_name 字段名
     * @param $length     长度 订单号类型,默认16位,原有长度-6位
     * @param $type       1:数子 2:数字+字母
     */
    public function get_invite_code($table_name = 'member', $field_name = 'invite_code', $length = 8)
    {
        $characters       = '123456789abcdefghjkmnpqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString     = '';

        for ($i = 0; $i < $length; $i++) {
            $randomIndex  = rand(0, $charactersLength - 1);
            $randomString .= $characters[$randomIndex];
        }


        $is = Db::name("member")->where($field_name, '=', $randomString)->count();
        if ($is) $this->get_invite_code($table_name);
        return $randomString;
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


    /**
     * 创建文件夹
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    public function is_mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;

        if (!$this->is_mkdirs(dirname($dir), $mode)) return FALSE;

        return @mkdir($dir, $mode);
    }


    /**
     * 复制图片
     * @param $file_path 要复制图片路径
     * @param $copy_path 复制到的路径
     * @param $value     复制几份
     * @param $type      图片类型
     * @return array 返回数组格式
     */
    public function copy_file($file_path, $copy_path, $name)
    {
        $absolute_path = $copy_path . $name;

        copy($file_path, $absolute_path);

        return $absolute_path;
    }


    /**
     * post请求
     * @param       $url
     * @param array $data
     * @return bool|string
     */
    public function curl_post($url, $data = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    /**
     * 处理上传数组图片
     * @param $images
     * @return void
     */
    public function setImages($images = [])
    {
        $result = implode(',', $images);
        if (empty($result)) return null;

        return $result;
    }


    /**
     * 获取图片 数组 短路径
     * @param $images 图片
     * @param $field  返回字段
     * @return array
     */
    public function getImages($images = '', $field = 'images')
    {
        $images = explode(",", $images);
        if (empty($result)) return null;
        return $images;
    }


    /**
     * 获取图片 数组 全路径
     * @param $images 图片
     * @param $field  返回字段
     * @return array
     */
    public function getImagesUrl($images = '', $field = 'images')
    {
        $images = explode(",", $images);

        if (is_array($images)) {
            for ($i = 0; $i < count($images); $i++) {
                $url[$i] = cmf_get_asset_url($images[$i]);
            }
            return $url;
        }

        return null;
    }


    /**
     * 处理上传内容数组打散字符串
     * @param $params
     * @return void
     */
    public function setParams($params = [], $separator = ',')
    {
        $result = implode($separator, $params);
        if (empty($result)) return null;
        return $result;
    }


    /**
     * 处理上传内容数组打散字符串
     * @param $params
     * @return void
     */
    public function getParams($params = '', $separator = ',')
    {
        $result = explode($separator, $params);
        if (empty($result)) return null;
        return $result;
    }


    /**
     * 地址转换为坐标
     */
    public function search_address($param)
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $url             = "https://apis.map.qq.com/ws/geocoder/v1/?key={$tencent_map_key}&address={$param}";
        $result          = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     * 坐标转换地址
     */
    public function reverse_address($params)
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $url             = "https://apis.map.qq.com/ws/geocoder/v1/?location={$params}&key={$tencent_map_key}&get_poi=1";
        $result          = file_get_contents($url);
        return json_decode($result, true);
    }


    /**
     *求两个已知经纬度之间的距离,单位为米
     * @param lng1,lng2 经度
     * @param lat1,lat2 纬度
     * @return float 距离，单位米
     * @edit www.jbxue.com
     **/
    function getdistance($lng1, $lat1, $lng2, $lat2)
    {
        // 将角度转为狐度
        $radLat1 = deg2rad($lat1);// deg2rad()函数将角度转换为弧度
        $radLat2 = deg2rad($lat2);
        $radLng1 = deg2rad($lng1);
        $radLng2 = deg2rad($lng2);
        $a       = $radLat1 - $radLat2;
        $b       = $radLng1 - $radLng2;
        $s       = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137 * 1000;
        return $s;
    }


    /**
     * 根据ip转地区信息
     * @return mixed|null
     */
    public function get_ip_to_city()
    {
        //用于地图转经纬度,经纬度转地址,腾讯地图key
        $tencent_map_key = cmf_config('tencent_map_key');
        $onlineip        = get_client_ip();
        $url             = file_get_contents("https://apis.map.qq.com/ws/location/v1/ip?ip={$onlineip}&key={$tencent_map_key}");
        $res1            = json_decode($url, true);
        $data            = $res1;
        if (isset($data['result']['ad_info']) && !empty($data['result']['ad_info'])) {
            return $data['result']['ad_info']['city'];
        } else {
            return null;
        }
    }


    /**
     * 获取时间区间值
     * @param $begin      开始时间 2022-11-1 15:53:55
     * @param $end        结束时间 2022-11-1 15:53:55
     * @param $Field      筛选时间字段名
     * @return array   [$beginField, 'between', [$beginTime, $endTime]];
     */
    public function getBetweenTime($begin = '', $end = '', $Field = 'create_time')
    {
        $where[] = [$Field, 'between', [0, 999999999999]];

        if (!empty($begin)) {
            unset($where);
            $beginTime = strtotime($begin);//默认 00:00:00
            $where[]   = [$Field, 'between', [$beginTime, 999999999999]];
        }

        if (!empty($end)) {
            unset($where);
            $strlen = strlen($end);
            if ($strlen > 10) $endTime = strtotime($end);//传入 年月日,时分秒不用转换
            if ($strlen <= 10) $endTime = strtotime($end . '23:59:59');//传入 年月日,年月  拼接时分秒
            $where[] = [$Field, 'between', [0, $endTime]];
        }

        if (!empty($begin) && !empty($end)) {
            unset($where);
            $beginTime = strtotime($begin);
            $strlen    = strlen($end);
            if ($strlen > 10) $endTime = strtotime($end);
            if ($strlen <= 10) $endTime = strtotime($end . '23:59:59');//传入 年月日,年月  拼接时分秒
            $where = [$Field, 'between', [$beginTime, $endTime]];
        }
        return $where;
    }


    // 接口日志
    protected function add_log()
    {
        // 屏蔽一些方法、模块（api 或 admin）控制器+方法
        $NoController = [
            'wxapp/public/index',
            'wxapp/public/index1',
        ];

        $user_id = $this->user_id;
        // 判断是否为 HTTPS 请求
        $isHttps   = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'));
        $http_type = $isHttps ? 'https://' : 'http://';

        // 获取客户端 IP 地址
        $onlineip = get_client_ip();

        // 获取用户名，优先从 username 获取，若不存在则从 nickname 获取
        $user_name = $this->user_info['username'] ?? $this->user_info['nickname'];

        // 获取当前请求的 URL
        $menu_name = $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        // 替换 URL 中的特定部分
        $menu_name = str_replace("api.php?s=", "api/", $menu_name);
        // 解析 URL 获取参数部分
        $url_detail = $this->getParams($menu_name, '&');
        $menu_name  = $url_detail[0];

        // 获取请求的路径信息（控制器和方法）
        $pathinfo = request()->pathinfo();

        // 如果当前请求路径在屏蔽列表中，不记录日志
        if (in_array($pathinfo, $NoController)) return false;

        // 获取请求的参数
        $params = $this->request->param();
        if (isset($this->openid) && $this->openid) {
            // 将 openid 加入参数中
            $params['openid'] = $this->openid;
        }

        // 将参数编码为 JSON 字符串
        $param = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // 构建访问的完整 URL
        $visit_url = $url_detail[0] . '?' . http_build_query($params);

        // 日志文件数据
        $array_log = [
            'admin_id'    => $user_id,
            'ip'          => $onlineip,
            'admin_name'  => $this->filterEmoji($user_name),
            'date'        => date('Y-m-d H:i:s'),
            'create_time' => time(),
            'menu_name'   => $menu_name,
            'param'       => $param,
            'visit_url'   => $visit_url,
            'openid'      => $this->openid,
            'type'        => 2,
        ];

        // 日志文件保留天数，默认值为 10 天
        $log_file_days = cmf_config('log_file_days') ?? 10;

        // 指定日志文件目录
        $logDirectory = CMF_ROOT . 'data/journal/';

        // 获取当前日期时间戳
        $currentDate = time();

        // 计算要删除的日期时间戳
        $deleteDate = $currentDate - ($log_file_days * 86400);

        // 获取日志文件列表
        $files = scandir($logDirectory);

        // 遍历日志文件列表
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                // 获取文件的最后修改时间
                $fileLastModified = filemtime($logDirectory . '/' . $file);
                // 如果文件的最后修改时间早于要删除的日期时间戳，则删除文件
                if ($fileLastModified < $deleteDate) {
                    unlink($logDirectory . '/' . $file);
                }
            }
        }

        // 写入文件中 & 保存前 10 天的日志
        $filename = CMF_ROOT . 'data/journal/';
        if (!is_dir($filename)) {
            // 创建目录如果不存在
            mkdir($filename, 0755, true);
        }
        $file_hwnd = fopen($filename . date('Y-m-d') . ".log", "a+");
        fwrite($file_hwnd, json_encode($array_log) . "\r\n");
        fclose($file_hwnd);


        // 插入日志数据到数据库
        Db::name('base_admin_log')->insert($array_log);


    }

    //去除昵称的表情问题
    function filterEmoji($str)
    {
        $str = preg_replace_callback('/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
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

}
