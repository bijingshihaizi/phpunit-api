<?php
require __DIR__.'/../common/Helper.php';
use PHPUnit\Framework\TestCase;
use Common\helper;

class test extends TestCase
{

    const Ip = "https://ipcs.iov-smart.net/";

    public function testVerifyCode()
    {
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/verify-code'
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
            self::Ip . 'zeus/api/v1/login',
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
            self::Ip . 'zeus/api/v1/profile',
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
            self::Ip . 'zeus/api/v1/logout',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['message']);
    }

    /**
     * 修改密码
     * @depends testVerifyCode
     */
    public function testPassword($code){
        $helper = new Helper();
        $oldPsd = '123321';
        $res = $helper->putUrl(
            self::Ip . 'zeus/api/v1/password', //url
            ['oldPsd' => $oldPsd, 'newPsd' => '123321'], //params
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆组列表
     * @depends testVerifyCode
     */
    public function testDepartments($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/departments',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array ('id','name','pid');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 根据车辆ID数组查询车辆
     * @depends testVerifyCode
     */
    public function testQueryVehicles($code){
        $helper = new Helper();
        $ids = array();
        $res = $helper->postUrl(
            self::Ip . 'zeus/api/v1/query-vehicles',
            $ids,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('<vehicles>');   //接口文档没具体说返回值字段
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 模糊查询车辆
     * @depends testVerifyCode
     */
    public function testVeicles($code){
        $queryString = '';
        $areaCode = '';
        $depId = '';
        $helper = new Helper();
        $url = self::Ip . 'zeus/api/v1/vehicles';
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
        $subset = array('<vehicles>');   //接口文档没具体说返回值字段
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆加入监控
     * @depends testVerifyCode
     */
    public function testTrace($code){
        $helper = new Helper();
        $ids = array();
        $res = $helper->postUrl(
            self::Ip . 'zeus/api/v1/vehicles/trace',
            $ids,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆历史轨迹
     * @depends testVerifyCode
     */
    public function testIdTrace($code){
        $plate_no = '';   //string 车牌号
        $start_time = ''; //timestamp 开始时间
        $end_time = '';   //timestamp 结束时间
        $min_speed = '';  //int       最低速度
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/track?&plateNo=' . $plate_no . '&startTime=' . $start_time . '&endTime=' . $end_time .'&minSpeed=' . $min_speed,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 实时视频  查询车辆实时视频参数
     * @depends testVerifyCode
     */
    public function testRealTime($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/real-time',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆控制  油电控制
     * @depends testVerifyCode
     */
    public function testFuelControl($code){
        $fuel_status = array();
        $helper = new Helper();
        $res = $helper->putUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/fuel-control',
            $fuel_status,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆控制  获取车辆油电状态
     * @depends testVerifyCode
     */
    public function testFuelStatsu($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/fuel-status',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('fuelStatus','controlStatus');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 车辆控制  获取用户油电操作日志
     * @depends testVerifyCode
     */
    public function testFuelControlLog($code){
        $start_time = '';
        $end_time = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/fuel-control-log?
            &startTime=' . $start_time .
            '&endTime=' . $end_time,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('plateNo','controlStatus','createTime','userName');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  获取报警类型
     * @depends testVerifyCode
     */
    public function testGAlertTypes($code){
        $url = self::Ip . 'zeus/api/v1/alert-types';
        $type = '';
        $level = '';
        if (!empty($type) || !empty($level)){
            $url = $url .'?&type=' .$type .'&level=' . $level;
        }
        $helper = new Helper();
        $res = $helper->getUrl(
            $url,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','name','level','type','rule','createTime','expire_time');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  创建区域报警
     * @depends testVerifyCode
     */
    public function testPAlertTypes($code){
        $arr = array('name'=>'','type'=>'','rule'=>'');
        $helper = new Helper();
        $res = $helper->postUrl(
            self::Ip . 'zeus/api/v1/alert-types',
            $arr,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','name','level','type','rule','createTime','expire_time');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  删除区域报警
     * @depends testVerifyCode
     */
    public function testDAlertTypes($code){
        $helper = new Helper();
        $res = $helper->delUrl(
            self::Ip . 'zeus/api/v1/alert-types/:id',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  获取正在发生的报警信息
     * @depends testVerifyCode
     */
    public function testCurrentAlerts($code){
        $depld = '';
        $alert_type_id = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/current-alerts',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  报警趋势
     * @depends testVerifyCode
     */
    public function testAlertTrend($code){
        $dep_ids = '';
        $type_id = '';
        $district = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/alerts/trend?&depIds='.$dep_ids.'&typeId='.$type_id.'&district='.$district.'&areaCode'.$area_code,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('time','count','DepTrend'=>['depId','depName','ratio','trends']);
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  总报警排行
     * @depends testVerifyCode
     */
    public function testAlertRank($code){
        $start_time = '';
        $end_time = '';
        $top = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/alerts-rank?&startTime='.$start_time.'&endTime='.$end_time.'&top='.$top.'&areaCode'.$area_code,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('DepAlertRatio'=>['depId','depName','ratio']);
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 报警报表  获取车辆报警历史
     * @depends testVerifyCode
     */
    public function testAlerts($code){
        $vid = '';
        $depid = '';
        $alert_typeid = '';
        $start_time = '';
        $end_time = '';
        if (!empty($vid)){
            $url = self::Ip . 'zeus/api/v1/alerts?&vId='.$vid.'&alertTypeId='.$alert_typeid.'&startTime='.$start_time.'&endTime'.$end_time;
        }else{
            $url = self::Ip . 'zeus/api/v1/alerts?&depId='.$depid.'&alertTypeId='.$alert_typeid.'&startTime='.$start_time.'&endTime'.$end_time;
        }
        $helper = new Helper();
        $res = $helper->getUrl(
            $url,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('id','vId','plateNo','depId','depName','alertType','alertPosition','endPosition','alertValue','alertUnit','gps');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 轨迹播放  获取车辆轨迹信息
     * @depends testVerifyCode
     */
    public function testTrips($code){
        $start_time = '';
        $end_time = '';
        $vid = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            $url = self::Ip . 'zeus/api/v1/alerts?&vId='.$vid.'&startTime='.$start_time.'&endTime='.$end_time,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','plateNo','depId','depName','daySegments');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 区域查车  根据区域和事件查询车辆
     * @depends testVerifyCode
     */
    public function testSearchVehicles($code){
        $start_time = '';
        $end_time = '';
        $shapes = [];
        $depid = '';
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . 'zeus/api/v1/search-vehicles',
            ['startTime'=>$start_time,'endTime'=>$end_time,'shapes'=>$shapes,'depId'=>$depid],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('depId','depName','counts');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 区域查车  获取车辆指定时间内的轨迹
     * @depends testVerifyCode
     */
    public function testVehiclesTrips($code){
        $start_time = '';
        $end_time = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            $url = self::Ip . 'zeus/api/v1/vehicles/:id/trips?&startTime='.$start_time.'&endTime='.$end_time,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','plateNo','depId','depName','trips');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 音视频  实时播放摄像头信息
     * @depends testVerifyCode
     */
    public function testLive($code){
        $index = '';
        $type = '';
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . 'zeus/api/v1/vehicles/:id/live',
            ['index'=>$index,'type'=>$type],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('source','name','index');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 音视频  停止实时播放
     * @depends testVerifyCode
     */
    public function testStopLive($code){
        $channel = '';
        $helper = new Helper();
        $res = $helper->postUrl(
            $url = self::Ip . 'zeus/api/v1/vehicles/:id/stop-live',
            ['channel'=>$channel],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  获取服务器上存储的视频文件
     * @depends testVerifyCode
     */
    public function testVideos($code){
        $type = '';
        $start_time = '';
        $end_time = '';
        $channel = '';
        $storage_type = '';
        $url = self::Ip . 'zeus/api/v1/vehicles/:id/videos?&type='.$type.'&startTime='.$start_time.'&endTime='.$end_time;
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
        $subset = array('id','startTime','endTime','uploadTime','fileType','channel','size','storageType','path','downloadUrl','uploadType');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  获取文件上传状态
     * @depends testVerifyCode
     */
    public function testFileTaskId($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/file-tasks/:id',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('status','errorMessage');   // errorMessage 可选参数
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  （暂停/继续/取消）文件上传
     * @depends testVerifyCode
     */
    public function testFileTasksStatus($code){
        $action = '';
        $helper = new Helper();
        $res = $helper->putUrl(
            self::Ip . 'zeus/api/v1/file-tasks/:id/status',
            ['action'=>$action],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('status','errorMessage','path','downloadUrl');   // status 可选参数
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  通知终端回放视频
     * @depends testVerifyCode
     */
    public function testPlayVideo($code){
        $file_id = ''; //id
        $start_time = '';
        $type = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/play-video?&fileId='.$file_id.'&startTime='.$start_time.'&type='.$type,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('source','name','index');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  终端视频回放控制
     * @depends testVerifyCode
     */
    public function testPlayStatus($code){
        $file_id = '';
        $action = '';
        $helper = new Helper();
        $res = $helper->putUrl(
            self::Ip . 'zeus/api/v1/vehicles/:id/play-status`',
            ['fileId'=>$file_id,'action'=>$action],
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        // {
        //     message: "视频暂停成功"
        //  }
        $this->assertEquals("200", "200", $res['msg']);
    }

    /**
     * 视频回放  获取全部车辆位置信息
     * @depends testVerifyCode
     */
    public function testAllVehicle($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/locations/all-vehicle',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('vId','plateNo','depId','lat','lng','isMove','isAlert','areaCode');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  获取车辆位置信息缓存文件
     * @depends testVerifyCode
     */
    public function testCacheVehicles($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/caches/vehicles',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('filePath');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  获取行政区域
     * @depends testVerifyCode
     */
    public function testAreaCodes($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/area-codes',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','name');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 视频回放  获取行政区域
     * @depends testVerifyCode
     */
    public function testStatistics($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/statistics/dep-vehicles',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('depId','depName','count','ratio');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 入网车辆排行——按区域统计
     * @depends testVerifyCode
     */
    public function testAreaRank($code){
        $depid = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/statistics/dep-vehicles/area-rank?&depId='.$depid.'&areaCode='.$area_code,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','areaName','count','ratio');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 入网车辆排行——当日报警排行榜
     * @depends testVerifyCode
     */
    public function testStatisticsCurrentAlerts($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/statistics/current-alerts',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','areaName','count','ratio');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 单个组织当日报警排行——按区域
     * @depends testVerifyCode
     */
    public function testStatisticsAreaRank($code){
        $depid = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/statistics/current-alerts?&depId='.$depid.'&areaCode='.$area_code,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','areaName','vehicleCount','ratio');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 单个组织当日报警排行——获取报警详情
     * @depends testVerifyCode
     */
    public function testCurrentAlertsDetail($code){
        $depid = '';
        $alert_type_id = '';
        $area_code = '';
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/current-alerts/detail&depId='.$depid.'&alertTypeId='.$alert_type_id.'&areaCode='.$area_code,
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $subset = array('areaCode','areaName','counts');
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

    /**
     * 单个组织当日报警排行——获取单个报警详情
     * @depends testVerifyCode
     */
    public function testAlertId($code){
        $helper = new Helper();
        $res = $helper->getUrl(
            self::Ip . 'zeus/api/v1/alerts/:id',
            [
                'cookie: IPCS-SESSIONID='.$code[2]
            ]
        );
        $this->assertEquals("200", $res['status'], $res['msg']);
    }

}

