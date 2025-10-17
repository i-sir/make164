<?php

namespace api\wxapp\validate;

use think\Validate;


class TechnicianValidate extends Validate
{
    protected $rule = [
        'nickname'             => 'require',
        'phone'                => 'require',
        'avatar'               => 'require',
        'province'             => 'require',
        'city'                 => 'require',
        'county'               => 'require',
        'address'              => 'require',
        'lnglat'               => 'require',
//        'qualifications_image' => 'require',
    ];

    protected $message = [
        'nickname.require'             => '姓名不能为空!',
        'phone.require'                => '联系电话不能为空!',
        'avatar.require'               => '请上传头像!',
        'province.require'             => '省未选择!',
        'city.require'                 => '市未选择!',
        'county.require'               => '区未选择!',
        'address.require'              => '详细地址未选择!',
        'lnglat.require'               => '定位信息错误!',
//        'qualifications_image.require' => '资质照片未上传!',
    ];
}