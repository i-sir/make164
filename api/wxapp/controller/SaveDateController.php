<?php

namespace api\wxapp\controller;

/**
 * @ApiController(
 *     "name"                    =>"SaveDate",
 *     "name_underline"          =>"save_date",
 *     "controller_name"         =>"SaveDate",
 *     "table_name"              =>"save_date",
 *     "remark"                  =>"预约时间管理"
 *     "api_url"                 =>"/api/wxapp/save_date/index",
 *     "author"                  =>"",
 *     "create_time"             =>"2024-08-30 16:18:12",
 *     "version"                 =>"1.0",
 *     "use"                     => new \api\wxapp\controller\SaveDateController();
 *     "test_environment"        =>"http://make164.ikun:9090/api/wxapp/save_date/index",
 *     "official_environment"    =>"https://dzam164.wxselling.net/api/wxapp/save_date/index",
 * )
 */


use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;


error_reporting(0);


class SaveDateController extends AuthController
{

    public function initialize()
    {
        //预约时间管理

        //parent::initialize();
    }


    /**
     * 获取时间列表
     * @OA\Post(
     *     tags={"预约时间管理"},
     *     path="/wxapp/save_date/find_date_list",
     *
     *
     *    @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="天 2024-09-02",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *         )
     *     ),
     *
     *
     *
     *    @OA\Parameter(
     *         name="technician_id",
     *         in="query",
     *         description="技师id",
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
     *   test_environment: http://make164.ikun:9090/api/wxapp/save_date/find_date_list
     *   official_environment: https://dzam164.wxselling.net/api/wxapp/save_date/find_date_list
     *   api:  /wxapp/save_date/find_date_list
     *   remark_name: 获取时间列表
     *
     */
    public function find_date_list()
    {
        // 获取请求参数和技师ID
        $params                  = $this->request->param();
        //$params['technician_id'] = 3; 

        // 获取系统配置项
        $startTime       = cmf_config('the_earliest_appointment_time');
        $endTime         = cmf_config('latest_appointment_time');
        $interval        = cmf_config('interval_time');
        $taxiTime        = cmf_config('taxi_time');
        $appointmentDays = cmf_config('appointment_date');

        // 获取选择的日期，如果未指定则默认为当前日期
        $startDate = $params['day'] ?? date('Y-m-d');

        // 初始化存储结果的数组
        $weekTime = [];

        // 循环生成从 startDate 开始的指定天数的预约时间
        for ($i = 0; $i < $appointmentDays; $i++) {
            $date        = date('Y-m-d', strtotime("+$i days", strtotime($startDate)));
            $dailyResult = $this->generateReservationTimes($date, $startTime, $endTime, $interval, $params['technician_id'], $taxiTime, $i);
            $weekTime[]  = $dailyResult;
        }

        // 返回成功信息，包含生成的预约时间列表
        $this->success('请求成功', ['weekTime' => $weekTime]);
    }

    /**
     * 生成并检测可预约的时间段
     */
    public function generateReservationTimes($date, $startTime = '09:00', $endTime = '18:00', $interval = 30, $technicianId, $taxiTime = 0, $i)
    {
        // 获取当前日期和当前时间戳
        $currentDate      = date('Y-m-d');
        $currentTimestamp = time();

        // 计算打车时间缓冲区的时间戳，即下单必须n分钟之后的时间点
        $taxiBufferTimestamp = strtotime("+$taxiTime minutes", $currentTimestamp);

        // 根据配置的最早时间和时间间隔，对齐到最接近的整点时间段
        $startDateTime = $this->alignToNearestInterval($date, $startTime, $interval, $currentDate, $currentTimestamp);

        // 初始化存储时间段的数组
        $times = [];

        // 将计算后的开始时间转换为时间戳
        $currentSlotTimestamp = strtotime("$date $startDateTime");
        // 将配置的结束时间转换为时间戳
        $endSlotTimestamp = strtotime("$date $endTime");

        // 循环生成每个时间段
        while ($currentSlotTimestamp <= $endSlotTimestamp) {
            // 格式化日期、时间和周几
            $formattedDate = date('Y-m-d', $currentSlotTimestamp);
            $formattedTime = date('H:i', $currentSlotTimestamp);
            $dayOfWeek     = $this->getChineseDayOfWeek(date('w', $currentSlotTimestamp));

            // 检查当前时间段是否已过
            $isPast = $date === $currentDate && $currentSlotTimestamp < $currentTimestamp;
            // 检查当前时间段是否在打车时间缓冲区内
            $isWithinTaxiBuffer = $currentSlotTimestamp < $taxiBufferTimestamp;

            // 如果时间段既不过去也不在打车时间缓冲区内，则进一步检查是否已被预约
            $isAvailable = !$isPast && !$isWithinTaxiBuffer && $this->checkAvailability($currentSlotTimestamp, $interval, $technicianId);

            // 如果当前日期还未初始化到 times 数组中，则初始化该日期
            if (!isset($times[$formattedDate])) {
                $times[$formattedDate] = [
                    'day'      => date('m-d', $currentSlotTimestamp),
                    'data'     => $formattedDate,
                    'is_open'  => false,
                    'timeList' => [],
                    'week'     => $dayOfWeek,
                ];
            }

            // 将当前时间段的信息加入该日期的时间段列表
            $times[$formattedDate]['timeList'][] = [
                'date'         => $formattedDate,
                'time'         => $formattedTime,
                'day_of_week'  => $dayOfWeek,
                'datetime'     => "$formattedDate $formattedTime",
                'is_available' => $isAvailable,
            ];

            // 如果这是第一天且第一个时间段可预约，设置 is_open 为 true
            if ($i == 0 && !$times[$formattedDate]['is_open'] && $isAvailable) {
                $times[$formattedDate]['is_open'] = true;
            }

            // 计算下一个时间段的时间戳
            $currentSlotTimestamp = strtotime("+$interval minutes", $currentSlotTimestamp);
        }

        // 返回按天分组的时间段列表
        return $times[$formattedDate];
    }


    /**
     * 对齐到最近的整点时间段
     *
     * @param string $date               选择的日期
     * @param string $startTime          最早时间
     * @param int    $interval           时间间隔，单位为分钟
     * @param string $currentDate        当前日期
     * @param int    $referenceTimestamp 参考时间戳（考虑到打车时间）
     * @return string 对齐后的开始时间，格式为 'H:i'
     */
    public function alignToNearestInterval($date, $startTime, $interval, $currentDate, $referenceTimestamp)
    {
        // 将开始时间转换为时间戳
        $startTimestamp = strtotime("$date $startTime");

        // 如果选择日期是今天且开始时间小于参考时间戳（例如当前时间或打车时间缓冲），则对齐到参考时间戳
        if ($date === $currentDate && $startTimestamp < $referenceTimestamp) {
            $startTimestamp = $referenceTimestamp;
        }

        // 获取分钟数并对齐到最近的时间间隔
        $minutes        = date('i', $startTimestamp);
        $alignedMinutes = ceil($minutes / $interval) * $interval;

        // 生成对齐后的时间字符串
        $alignedTime = date('H:', $startTimestamp) . str_pad($alignedMinutes, 2, '0', STR_PAD_LEFT);

        // 如果分钟数超过60，则调整到下一个整点
        if ($alignedMinutes >= 60) {
            $alignedTime = date('H:00', strtotime('+1 hour', $startTimestamp));
        }

        return $alignedTime;
    }

    /**
     * 检查给定时间段是否可以预约
     *
     * @param int $timestamp    当前时间戳
     * @param int $interval     时间间隔，单位为分钟
     * @param int $technicianId 技师的ID
     * @return bool 是否可预约
     */
    public function checkAvailability($timestamp, $interval, $technicianId)
    {
        $ShopOrderSaveModel = new \initmodel\ShopOrderSaveModel(); //技师已约时间   (ps:InitModel)

        // 计算当前时间段的结束时间戳
        $endTimestamp = strtotime("+$interval minutes", $timestamp);

        $map   = [];
        $map[] = ['begin_time', '<', $endTimestamp];
        $map[] = ['end_time', '>', $timestamp];
        $map[] = ['technician_id', '=', $technicianId];
        $map[] = ['status', '=', 1];

        // 查询数据库，检查该技师在此时间段是否已有预约
        $conflictCount = $ShopOrderSaveModel->where($map)->count();

        // 如果没有冲突记录，则可以预约
        return $conflictCount === 0;
    }

    /**
     * 获取中文周几
     *
     * @param int $dayOfWeek 数字表示的周几（0=周日, 1=周一, ..., 6=周六）
     * @return string 中文表示的周几
     */
    public function getChineseDayOfWeek($dayOfWeek)
    {
        $days = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
        return $days[$dayOfWeek];
    }

    //测试最早预约时间
    public function test()
    {
        $r = $this->findEarliestTechnicianDate(3);
        $this->success('', $r);
    }

    /**
     * 获取某个技师的最早可预约时间
     *
     * @param int    $technicianId 技师ID
     * @param string $date         指定日期，格式为 'Y-m-d'，默认为今天
     * @return array 最早可预约时间的信息
     */
    public function findEarliestTechnicianDate($technicianId, $date = '')
    {
        // 获取系统配置
        $startTime = cmf_config('the_earliest_appointment_time'); // 预约最早时间
        $endTime   = cmf_config('latest_appointment_time');       // 预约最晚时间
        $interval  = cmf_config('interval_time');                 // 时间间隔（分钟）
        $taxiTime  = cmf_config('taxi_time');                     // 打车时间（分钟）

        // 当前日期
        $date = $date ?? date('Y-m-d');

        // 计算考虑打车时间后的当前时间
        $currentTimestamp = time() + $taxiTime * 60;
        $currentTime      = date('H:i', $currentTimestamp);

        // 对齐到最近的时间间隔
        $alignedTime = $this->alignToNearestIntervalTechnician($currentTime, $interval);

        // 确保对齐后的时间不早于系统配置的最早时间
        $alignedTime = max($alignedTime, $startTime);

        // 从对齐后的时间开始检查
        $currentSlotTimestamp = strtotime("$date $alignedTime");

        // 查找当天最早的可预约时间段
        while ($currentSlotTimestamp <= strtotime("$date $endTime")) {
            // 检查当前时间段是否已被预约
            $isAvailable = $this->checkAvailability($currentSlotTimestamp, $interval, $technicianId);

            if ($isAvailable) {
                // 如果时间段可预约，返回时间信息
                return $this->convertToRelativeDate($currentSlotTimestamp);
            }

            // 增加时间间隔，检查下一个时间段
            $currentSlotTimestamp = strtotime("+$interval minutes", $currentSlotTimestamp);
        }

        // 如果没有找到可预约时间段
        return $this->convertToRelativeDate(time());
    }


    /**
     * 对齐到最近的时间间隔(获取技师最早预约时间)
     *
     * @param string $currentTime 当前时间
     * @param int    $interval    时间间隔，单位为分钟
     * @return string 对齐后的时间，格式为 'H:i'
     */
    public function alignToNearestIntervalTechnician($currentTime, $interval)
    {
        $minutes        = date('i', strtotime($currentTime));
        $alignedMinutes = ceil($minutes / $interval) * $interval;

        if ($alignedMinutes >= 60) {
            // 如果对齐后的分钟数超过60，则调整到下一个小时的整点
            return date('H:00', strtotime('+1 hour', strtotime($currentTime)));
        } else {
            // 正常对齐到最近的间隔
            return date('H:', strtotime($currentTime)) . str_pad($alignedMinutes, 2, '0', STR_PAD_LEFT);
        }
    }


    /**
     * 将指定的日期时间转换为相对日期格式
     *
     * @param string $datetime 要转换的日期时间，格式为 时间戳
     * @return string 转换后的相对日期格式
     */
    public function convertToRelativeDate($timestamp)
    {
        // 获取当前日期的时间戳
        $currentDate      = date('Y-m-d');
        $currentTimestamp = strtotime($currentDate);

        // 获取指定日期的日期部分
        $datePart = date('Y-m-d', $timestamp);

        // 判断日期是今天、明天、后天还是其他
        if ($datePart === $currentDate) {
            // 今天
            return '最早可预约今天 ' . date('H:i', $timestamp);
        } elseif ($datePart === date('Y-m-d', strtotime('+1 day', $currentTimestamp))) {
            // 明天
            return '最早可预约明天 ' . date('H:i', $timestamp);
        } elseif ($datePart === date('Y-m-d', strtotime('+2 days', $currentTimestamp))) {
            // 后天
            return '最早可预约后天 ' . date('H:i', $timestamp);
        } else {
            // 其他日期
            return date('Y-m-d H:i', $timestamp);
        }
    }

}