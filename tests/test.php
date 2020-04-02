<?php
require __DIR__.'/../common/Helper.php';
use PHPUnit\Framework\TestCase;
use Common\helper;

class test extends TestCase
{

    const Ip = "https://ipcs.iov-smart.net/zeus/api/v1";

    public function testVerifyCode()
    {
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/verify-code'
        );
        preg_match('/IPCS-SESSIONID=(.*?);/',$res,$m);
        $sessionId = $m[1];
        $redis = new Redis();
        $redis->connect('39.105.152.25', 6379); //连接Redis
        $redis->auth('pprt123'); //密码验证
        $redis->select(30);//选择数据库2
        $a = $redis->hGetAll( "spring:session:sessions:".$sessionId); //设置测试key
        $this->assertEquals(1, 1, '生成验证码失败');
        preg_match('/([a-zA-Z0-9]{4})/', $a['sessionAttr:random_validate_code'], $res);
        var_dump(array_merge($m,$res));
        return array_merge($m,$res);
    }

    /**
     * 登录
     * @depends testVerifyCode
     */
    public function testLogin($code)
    {
        $helper = new Helper();
        $res = $helper->postUrl(
            self::Ip . '/login',
            [
                'username' => 'admin',
                'password' => '123321',
                'randomCode' => $code[2]
            ]
            ,
            [
                'Content-Type:application/json',
                'cookie: IPCS-SESSIONID='.$code[1]
            ]
        );
        $token = $res['data'];
        $this->assertEquals(200, $res['status'], $res['message']);
        return $token;
    }

    /**
     * 获取用户信息
     * @depends testVerifyCode
     */
    public function testProfile($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/profile',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $res = json_decode($res,true);
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 用户退出
     * @depends testVerifyCode
     */
    public function testLogout($code){
        $helper = new Helper();
        $res = $helper->delUrl(
            self::Ip . '/logout',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 修改密码
     * @depends testVerifyCode
     */
    public function testPassword($code){
        $helper = new Helper();
        $oldPsd = '123321';
        $res = $helper->putUrl(
            self::Ip . '/password', //url
            ['oldPsd' => $oldPsd, 'newPsd' => '123321'], //params
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 车辆组列表
     * @depends testVerifyCode
     */
    public function testDepartments($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/departments',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array ('id','name','pid');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 根据车辆ID数组查询车辆
     * @depends testVerifyCode
     */
    public function testQueryVehicles($code){
        $helper = new Helper();
        $ids = array('275728'); //必填参数
        $res = $helper->postUrl(
            self::Ip . '/query-vehicles',
            $ids,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','channels','position','depId','depName','driverName','updateTime','fuelStatus');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 模糊查询车辆
     * @depends testVerifyCode
     */
    public function testVeicles($code){
        $queryString = '京';  //可为空
        $areaCode = '';     //可为空
        $depId = '';        //可为空
        $helper = new Helper();
        $url = self::Ip . '/vehicles';
        if (!empty($queryString)){
            $queryStrings = '&queryString='.$queryString ?? '';
        }elseif (!empty($areaCode)){
            $areaCodes = '&areaCode='.$areaCode ?? '';
        }elseif (!empty($depId)){
            $depIds = '&depId='.$depId ?? '';
        }
        if (!empty($queryString) || !empty($areaCode) || !empty($depId)){
            $url = $url . '?' . $queryStrings . $areaCodes . $depIds;
        }
        $res = $helper->getUrl(
            $url,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','channels','position','depId','depName','driverName','updateTime','fuelStatus');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

//    /**
//     * 实时视频  查询车辆实时视频参数
//     * @depends testVerifyCode
//     */
//    public function testRealTime($code){
//        $helper = new Helper();
//        $res = $helper->getUrl(
//            self::Ip . '/vehicles/:id/real-time',
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }

    /**
     * 车辆控制  油电控制
     * @depends testVerifyCode
     */
    public function testFuelControl($code){
        $fuel_status = 1;
        $helper = new Helper();
        $res = $helper->putUrl(
            self::Ip . '/vehicles/275728/fuel-control',
            $fuel_status,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 车辆控制  获取车辆油电状态
     * @depends testVerifyCode
     */
    public function testFuelStatsu($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/vehicles/275728/fuel-status',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('fuelStatus','controlStatus');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 车辆控制  获取用户油电操作日志
     * @depends testVerifyCode
     */
    public function testFuelControlLog($code){
        $start_time = '0';
        $end_time = '1585725963776';
        $helper = new Helper();

        //拼接请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $helper->getUrl(
            self::Ip . '/vehicles/275728/fuel-control-log?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('plateNo','controlStatus','createTime','userName');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  获取报警类型
     * @depends testVerifyCode
     */
    public function testGAlertTypes($code){
        $url = self::Ip . '/alert-types';
        $type = '';     //可为空
        $level = '';    //可为空
        $http_query = [];
        if (!empty($type)){
            $http_query['type'] = $type;
        }
        if (!empty($level)) {
            $http_query['level'] = $level;
        }
        $helper = new Helper();
        $res = $helper->getUrl(
            $url.'?'.http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','name','level','type','rule','createTime','expire_time');
        $data = $res['data'][0];
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  创建区域报警
     * @depends testVerifyCode
     */
    public function testPAlertTypes($code){
        $arr = array('name'=>'unit测试','type'=>'fence','rule'=>['type'=>1,'circle'=>['radius'=>'430','lat'=>'39','lng'=>'116']]);
        $helper = new Helper();
        $res = $helper->postUrl(
            self::Ip . '/alert-types',
            $arr,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','name','rule','createTime');
        $this->assertEquals(200, $res['status'], $res['message']);
        return $res['data']['id'];
    }

    /**
     * 报警报表  删除区域报警
     * @depends testVerifyCode
     * @param $code
     * @depends testPAlertTypes
     * @param $id
     */
    public function testDAlertTypes($code,$id){
        $helper = new Helper();
        $res = $helper->delUrl(
            self::Ip . '/alert-types/'.$id,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  获取正在发生的报警信息
     * @depends testVerifyCode
     */
    public function testCurrentAlerts($code){
        $depld = '-1';
        $alert_type_id = '-1';
        $area_code = '110000';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/current-alerts',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','depId','vehicleType','driverName','lng','lat');
        $data = $res['data'][0];
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  获取指定车辆当前的告警信息
     * @depends testVerifyCode
     */
    public function testIdCurrentAlerts($code){
        $depld = '-1';
        $alert_type_id = '-1';
        $area_code = '110000';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . ' vehicles/275728/current-alerts',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','alerts','plateNo','driverName','depId','alertTime','depName','vehicleType','plateColor');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  报警趋势
     * @depends testVerifyCode
     */
    public function testAlertTrend($code){
        $dep_ids = '6,4,7,5';
        $type_id = '1';
        $district = '30';
        $area_code = '110000';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'depIds' => $dep_ids,
            'typeId' => $type_id,
            'district' => $district,
            'areaCode' => $area_code,
        ];
        $res = $helper->getUrl(
            self::Ip . '/alerts/trend?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('depId','depName','ratio','trends');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  总报警排行
     * @depends testVerifyCode
     */
    public function testAlertRank($code){
        $start_time = '1582992000000';
        $end_time = '1585670399999';
        $top = '4';
        $area_code = '110000';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
            'top' => $top,
            'areaCode' => $area_code,
        ];
        $res = $helper->getUrl(
            self::Ip . '/alerts-rank?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('depId','depName','ratio');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 报警报表  获取车辆报警历史
     * @depends testVerifyCode
     */
    public function testAlerts($code){
        $vid = '275728';
        $depid = '';
        $alert_typeid = '-1';
        $start_time = '1585670400000';
        $end_time = '1585756799999';

        //组装请求参数
        $http_query = [
            'alertTypeId' => $alert_typeid,
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        if (!empty($vid)){
            $http_query['vId'] = $vid;
            $url = self::Ip . '/alerts?' . http_build_query($http_query);
        }else{
            $http_query['depId'] = $depid;
            $url = self::Ip . '/alerts?' . http_build_query($http_query);
        }
        $helper = new Helper();
        $res = $helper->getUrl(
            $url,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        //无报警情况$res['data'] == null
        if ($res['data'] !== ''){
            $subset = array('id','vId','plateNo','depId','depName','alertType','alertPosition','endPosition','alertValue','alertUnit','gps');
        }
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 轨迹播放  获取车辆轨迹信息
     * @depends testVerifyCode
     */
    public function testTrips($code){
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $vid = '275728';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'vId' => $vid,
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $helper->getUrl(
            $url = self::Ip . '/alerts?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','plateNo','depId','depName','daySegments');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 区域查车  根据区域和事件查询车辆
     * @depends testVerifyCode
     */
    public function testSearchVehicles($code){
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $shapes = ['type'=>1,'circle'=>['radius'=>'900','lat'=>'39','lng'=>'116']];
        $depid = '-1';
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . '/search-vehicles',
            ['startTime'=>$start_time,'endTime'=>$end_time,'shapes'=>$shapes,'depId'=>$depid],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        //有可能有0下标 $subset_data
        $subset_data = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','videoChannelNum','videoChannelName','depId','depName','driverName','updateTime','fuelStatus');
        $subset_mate = array('depId','depName','counts');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 区域查车  获取车辆指定时间内的轨迹
     * @depends testVerifyCode
     */
    public function testVehiclesTrips($code){
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $helper->getUrl(
            $url = self::Ip . '/vehicles/275728/trips?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','plateNo','depId','depName','trips');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 音视频  实时播放摄像头信息
     * @depends testVerifyCode
     */
    public function testLive($code){
        $index = '2';
        $type = 'hls';
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . '/vehicles/275728/live',
            ['index'=>$index,'type'=>$type],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('source','name','index');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 音视频  停止实时播放
     * @depends testVerifyCode
     */
    public function testStopLive($code){
        $channel = 0;
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . '/vehicles/275728/stop-live',
            ['channel'=>$channel],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 视频回放  获取服务器上存储的视频文件
     * @depends testVerifyCode
     */
    public function testVideos($code){
        $type = '1';
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $channel = '1';
        $storage_type = '';

        //组装请求参数
        $http_query = [
            'type' => $type,
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $url = self::Ip . '/vehicles/275728/videos?' . http_build_query($http_query);
        if (!empty($channel)){
            $url .= '&channel='.$channel;
        }
        if (!empty($storage_type)){
            $url .= '&storageType='.$storage_type;
        }
        $helper = new Helper();
        $res = $helper->getUrl(
            $url,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        //$res['data'][0] data下有可能为空 表示无视频
        $subset = array('id','startTime','endTime','uploadTime','fileType','channel','size','path','downloadUrl','uploadType');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 视频回放  获取服务器上存储的视频文件
     * @depends testVerifyCode
     */
    public function testUploadVideo($code){
        $file_id = '65345';
        $helper = new Helper();
        $res = $helper->postUrl(
            self::Ip . 'vehicles/275728/upload-video',
            ['fileId'=>$file_id],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('taskId','status');
        $this->assertEquals(200, $res['status'], $res['message']);
        return $file_id;
    }

    /**
     * 视频回放  获取文件上传状态
     * @depends testVerifyCode
     * @param $code
     * @depends testUploadVideo
     * @param $id
     */
    public function testFileTaskId($code,$id){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/file-tasks/'.$id,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('status');   // errorMessage 可选参数
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 视频回放  （暂停/继续/取消）文件上传
     * @depends testVerifyCode
     * @param $code
     * @depends testUploadVideo
     * @param $id
     */
    public function testFileTasksStatus($code,$id){
        $action = 'cancel';
        $helper = new Helper();
        $res = $helper->putUrl(
            self::Ip . '/file-tasks/'.$id.'/status',
            ['action'=>$action],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('status');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 视频回放  通知终端回放视频
     * @depends testVerifyCode
     * @param $code
     * @depends testUploadVideo
     * @param $id
     */
    public function testPlayVideo($code,$id){
        $file_id = $id;
        $start_time = '0';      //参数不准确
        $type = 'hls';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'fileId' => $file_id,
            'startTime' => $start_time,
            'type' => $type,
        ];
        $res = $helper->getUrl(
            self::Ip . '/vehicles/'.$file_id.'/play-video?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('source','name','index');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

//    /**
//     * 视频回放  终端视频回放控制
//     * @depends testVerifyCode
//     */
//    public function testPlayStatus($code){
//        $file_id = '';
//        $action = '';
//        $helper = new Helper();
//        $res = $helper->putUrl(
//            self::Ip . '/vehicles/:id/play-status`',
//            ['fileId'=>$file_id,'action'=>$action],
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        // {
//        //     message: "视频暂停成功"
//        //  }
//        $this->assertEquals(200, 200, $res['message']);
//    }
//
//    /**
//     * 视频回放  获取全部车辆位置信息
//     * @depends testVerifyCode
//     */
//    public function testAllVehicle($code){
//        $helper = new Helper();
//        $res = $helper->getUrl(
//            self::Ip . '/locations/all-vehicle',
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $subset = array('vId','plateNo','depId','lat','lng','isMove','isAlert','areaCode');
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }

    /**
     * 视频回放  获取车辆位置信息缓存文件
     * @depends testVerifyCode
     */
    public function testCacheVehicles($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/caches/vehicles',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('filePath');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

    /**
     * 视频回放  获取行政区域
     * @depends testVerifyCode
     */
    public function testAreaCodes($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . '/area-codes',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        //$res['data'][0]
        $subset = array('areaCode','name');
        $this->assertEquals(200, $res['status'], $res['message']);
    }
//
//    /**
//     * 视频回放  获取行政区域
//     * @depends testVerifyCode
//     */
//    public function testStatistics($code){
//        $helper = new Helper();
//        $res = $helper->getUrl(
//            self::Ip . '/statistics/dep-vehicles',
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $subset = array('depId','depName','count','ratio');
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }
//
//    /**
//     * 入网车辆排行——按区域统计
//     * @depends testVerifyCode
//     */
//    public function testAreaRank($code){
//        $depid = '';
//        $area_code = '';
//        $helper = new Helper();
//
//        //组装请求参数
//        $http_query = [
//            'depId' => $depid,
//            'areaCode' => $area_code,
//        ];
//        $res = $helper->getUrl(
//            self::Ip . '/statistics/dep-vehicles/area-rank?' . http_build_query($http_query),
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $subset = array('areaCode','areaName','count','ratio');
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }
//
//    /**
//     * 入网车辆排行——当日报警排行榜
//     * @depends testVerifyCode
//     */
//    public function testStatisticsCurrentAlerts($code){
//        $helper = new Helper();
//        $res = $helper->getUrl(
//            self::Ip . '/statistics/current-alerts',
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $subset = array('areaCode','areaName','count','ratio');
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }
//
//    /**
//     * 单个组织当日报警排行——按区域
//     * @depends testVerifyCode
//     */
//    public function testStatisticsAreaRank($code){
//        $depid = '';
//        $area_code = '';
//        $helper = new Helper();
//
//        //组装请求参数
//        $http_query = [
//            'depId' => $depid,
//            'areaCode' => $area_code,
//        ];
//        $res = $helper->getUrl(
//            self::Ip . '/statistics/current-alerts?' . http_build_query($http_query),
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $subset = array('areaCode','areaName','vehicleCount','ratio');
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }

    /**
     * 单个组织当日报警排行——获取报警详情
     * @depends testVerifyCode
     */
    public function testCurrentAlertsDetail($code){
        $depid = '-1';
        $alert_type_id = '-1';
        $area_code = '110000';
        $helper = new Helper();

        //组装请求参数
        $http_query = [
            'depId' => $depid,
            'areaCode' => $area_code,
            'alertTypeId' => $alert_type_id,
        ];
        $res = $helper->getUrl(
            self::Ip . '/current-alerts/detail?' . http_build_query($http_query),
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','areaName','counts');
        $this->assertEquals(200, $res['status'], $res['message']);
    }

//    /**
//     * 单个组织当日报警排行——获取单个报警详情
//     * @depends testVerifyCode
//     */
//    public function testAlertId($code){
//        $helper = new Helper();
//        $res = $helper->getUrl(
//            self::Ip . '/alerts/:id',
//            [
//                'cookie: IPCS-SESSIONID='.$code[2]
//            ]
//        );
//        $this->assertEquals(200, $res['status'], $res['message']);
//    }

}

