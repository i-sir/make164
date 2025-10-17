<?php

namespace init;

use cmf\lib\Storage;
use cmf\phpqrcode\QRcode;
use think\Image;

/**
 * 生成二维码
 */
class QrInit
{

    /**
     * 获取二维码
     * @param $params 参数值
     * @param $colour 颜色值 red-红色 green-绿色
     *                https://blog.csdn.net/cgwcgw_/article/details/21155229 颜色表
     * @return string
     */
    public function get_qr($params, $colour = '')
    {
        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();//获取仓库信息
        $dz           = $this->config['dir'];


        $dz_dir  = "upload/$dz/code/";//本地地址
        $dz_dir2 = "$dz/code/";//传输线上路径
        $dir     = app()->getRootPath() . "public/$dz_dir";
        $file    = md5(md5(cmf_random_string(45)) . time()) . '.jpg';
        //判断目录是否存在
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $pathname  = $dz_dir . $file;
        $pathname2 = $dz_dir2 . $file;
        //注这里的二维码颜色不能是字符串

        if (!$colour) QRcode::png($params, $pathname, 1, 10, 4, true, 0xFFFFFF, 0x000000);
        if ($colour == 'red') QRcode::png($params, $pathname, 1, 10, 4, true, 0xFFFFFF, 0x000000);
        if ($colour == 'green') QRcode::png($params, $pathname, 1, 10, 4, true, 0xFFFFFF, 0x000000);

        //上传Oss储存
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($pathname2, $pathname);//第一个上传云空间地址路径  //第二个本地绝对路径
        $this->delete_image($pathname);//删除本地图片

        return cmf_get_asset_url($pathname2);
    }


    //删除单个文件方法
    public function delete_image($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }


    /**
     * 绘画图片
     * @param $pathname 二维码图片地址
     * @return string
     * @throws \Exception
     */
    public function drawing($pathname = '', $username = '', $avatar = '', $text = '邀请您长按识别二维码，查看详情')
    {
        //仓库地址
        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();//获取仓库信息
        $dz           = $this->config['dir'];
        $date         = date('Ymd');
        $qr_path      = 'wxqrcode';//将处理图片存放的文件夹

        //将文件夹删除一下
        $this->recursiveDelete(app()->getRootPath() . "public/upload/{$dz}/{$qr_path}");

        $path_public = app()->getRootPath() . "public/upload/{$dz}/{$qr_path}/{$date}/";//默认公共路径
        if (!file_exists($path_public)) mkdir($path_public, 0777, true);


        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();


        //所要复制的背景层
        $background_image = app()->getRootPath() . "public/images/0003.png";
        $copy_images_path = $this->copy_images($background_image, "{$dz}/{$qr_path}", 1, 'png');//复制底图
        $images_path      = $copy_images_path['path'];//处理的图片路径,绝对路径
        $rel_path         = "{$dz}/{$qr_path}/{$date}/" . cmf_random_string(45) . ".png";


        //推广图
        $guest                    = cmf_get_asset_url(cmf_config('customer_acquisition_poster'));//获客海报背景图
        $guest_acquisition_poster = $path_public . cmf_random_string(45) . '.png';
        $this->curl_file_get_contents($guest, $guest_acquisition_poster);//下载推广图

        //头像
        $avatar     = 'https://oss.ausite.cn/dz000/default/20230602/8fb025339dcafcb3e2f9e34f862e1976.png';
        $avatar     = cmf_get_asset_url($avatar);//获客海报背景图
        $avatar_url = $path_public . cmf_random_string(45) . '.png';
        $this->curl_file_get_contents($avatar, $avatar_url);//下载头像
        $new_avatar = $path_public . cmf_random_string(45) . '.png';
        $avatar_img = Image::open($avatar_url);//短路径
        $avatar_img->thumb(80, 80)->round()->save($new_avatar);//将二维码压缩大小   头像圆形加上->round()


        //小程序码(本次是微信二维码) $pathname:微信二维码
        $new_pathname = $path_public . cmf_random_string(45) . '.png';
        $this->curl_file_get_contents($pathname, $new_pathname);//二维码&小程序 本地路径
        $new_path = app()->getRootPath() . "public/upload/{$dz}/{$qr_path}/{$date}/" . cmf_random_string(45) . ".png";
        $eq_img   = Image::open($new_pathname);//短路径
        $eq_img->thumb(150, 150)->save($new_path);//将二维码压缩大小


        //绘画  写logo 写文字
        $img  = Image::open($images_path);
        $font = app()->getRootPath() . "public/font/song.otf"; //字体在服务器上的绝对路径
        $img->water($guest_acquisition_poster, [0, 0])->save($images_path);//背景图+推广图
        $img->water($new_path, [460, 820])->save($images_path);//背景图+小程序码(已压缩大小)
        $img->water($new_avatar, [50, 857])->save($images_path);//背景图+用户头像
        $img->text($username, $font, 23, '#130c0e', [150, 880])->save($images_path);//生成文字
        $img->text($text, $font, 20, '#130c0e', [130, 1020])->save($images_path);//生成文字


        //上传Oss储存
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($rel_path, $images_path);//第一个上传云空间地址路径  //第二个本地绝对路径

        $this->delete_image($new_pathname);//删除二维码照片
        $this->delete_image($new_path);//删除压缩后二维码图片
        $this->delete_image($images_path); //删除背景图
        $this->delete_image($guest_acquisition_poster); //删除推广图
        $this->delete_image($avatar_url); //删除头像
        $this->delete_image($new_avatar); //删除压缩后头像

        return $rel_path;
    }


    /**
     * 生成小程序分享码
     * @param string $pathname 小程序二维码图片地址
     * @param string $invite_code 邀请码
     * @param string $invite_text 提示文本
     */
    public function applet_share($pathname = '',$invite_code='',$invite_text='')
    {
        //仓库地址
        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();
        $this->config = $this->plugin->getConfig();//获取仓库信息
        $dz           = $this->config['dir'];
        $date         = date('Ymd');
        $qr_path      = 'qrcode';//将处理图片存放的文件夹

        //将文件夹删除一下
        $this->recursiveDelete(app()->getRootPath() . "public/upload/{$dz}/{$qr_path}");

        $path_public = app()->getRootPath() . "public/upload/{$dz}/{$qr_path}/{$date}/";//默认公共路径
        if (!file_exists($path_public)) mkdir($path_public, 0777, true);


        $storage      = cmf_get_option('storage');
        $pluginClass  = cmf_get_plugin_class($storage['type']);
        $this->plugin = new $pluginClass();

        //所要复制的背景层
        $background_image = app()->getRootPath() . "public/images/applet_share.png";
        $copy_images_path = $this->copy_images($background_image, "{$dz}/{$qr_path}", 1, 'png');//复制底图
        $images_path      = $copy_images_path['path'];//处理的图片路径,绝对路径
        $rel_path         = "{$dz}/{$qr_path}/{$date}/" . cmf_random_string(45) . ".png";


        //小程序码(本次是微信二维码) $pathname:微信二维码
        $new_pathname = $path_public . cmf_random_string(45) . '.png';
        $this->curl_file_get_contents($pathname, $new_pathname);//二维码&小程序 本地路径
        $new_path = app()->getRootPath() . "public/upload/{$dz}/{$qr_path}/{$date}/" . cmf_random_string(45) . ".png";
        $eq_img   = Image::open($new_pathname);//短路径
        $eq_img->thumb(430, 430, 2)->save($new_path);//将二维码压缩大小


        //绘画  写logo 写文字
        $img = Image::open($images_path);
        $img->water($new_path, [160, 520])->save($images_path);//背景图+小程序码(已压缩大小)


        //写文字
        $font = app()->getRootPath() . "public/font/song.otf"; //字体在服务器上的绝对路径
        $img->text($invite_code, $font, 30, '#000000', [280, 1000])->save($images_path);//生成文字

        //写文字
        $img->text($invite_text, $font, 19, '#0e7003', [50, 1300])->save($images_path);//生成文字


        //上传Oss储存
        $storage = new Storage($storage['type'], $storage['storages'][$storage['type']]);
        $storage->upload($rel_path, $images_path);//第一个上传云空间地址路径  //第二个本地绝对路径
        $this->delete_image($new_pathname);//删除二维码照片
        $this->delete_image($images_path);//删除复制图片

        return $rel_path;
    }


    /**
     * 复制图片
     * @param $image_path      要复制图片路径
     * @param $image_copy_path 复制到的路径
     * @param $value           复制几份
     * @param $type            图片类型
     * @return array 返回数组格式
     */
    public function copy_images($image_path, $image_copy_path, $value, $type)
    {
        $data           = date('Ymd');
        $http_type      = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url            = $http_type . $_SERVER['SERVER_NAME'];
        $new_image_path = $_SERVER['DOCUMENT_ROOT'] . "/upload/{$image_copy_path}/{$data}";

        if (!file_exists($new_image_path)) {
            mkdir($new_image_path, 0777, true);
        }

        $path = [];
        if ($value == 1) {
            $image_name    = cmf_random_string(45);
            $filepath      = "/upload/{$image_copy_path}/{$data}/{$image_name}.{$type}";
            $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $filepath;

            copy($image_path, $absolute_path);

            $path['path']     = $absolute_path;
            $path['filepath'] = $filepath;
            $path['url']      = $url . $filepath;

        } elseif ($value > 1) {
            for ($i = 0; $i < $value; $i++) {
                $image_name    = cmf_random_string(45);
                $filepath      = "/upload/{$image_copy_path}/{$data}/{$image_name}.{$type}";
                $absolute_path = $_SERVER['DOCUMENT_ROOT'] . $filepath;

                copy($image_path, $absolute_path);

                $path[$i]['path']     = $absolute_path;
                $path[$i]['filepath'] = $filepath;
                $path[$i]['url']      = $url . $filepath;
            }
        }
        return $path;
    }


    /**
     * 下载网路图片,base64也可以保存
     * @param $url  网路路径
     * @param $path 保存本地路径 绝对路径
     * @return mixed
     */
    public function curl_file_get_contents($url, $path)
    {
        $file = file_get_contents($url);
        // 写入保存图片，指定位位和文件名
        file_put_contents($path, $file);
    }


    /**
     * 删除子文件,及文件夹
     * @param $directory 文件夹地址
     */
    function recursiveDelete($directory)
    {
        foreach (glob($directory . '/*') as $file) {
            if (is_dir($file)) {
                $this->recursiveDelete($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

}