<?php
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

class test extends TestCase
{
    const Ip = "https://ipcs.iov-smart.net/zeus/api/v1";
    private $ip = '39.105.152.25';
    private $port = '6379';
    private $auth = 'pprt123';
    private $cache_key = 'phpunit:ipcs';
    private $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function testVerifyCode()
    {
        $res = $this->client->request('GET',self::Ip . '/verify-code');
        $headers = $res->getHeader('Set-Cookie');
        preg_match('/IPCS-SESSIONID=(.*?);/',$headers[0],$m);
        $redis = $this->cache(30);
        $a = $redis->hGetAll( "spring:session:sessions:".$m[1]); //设置测试key
        $this->assertEquals(200, $res->getStatusCode(), '');
        preg_match('/([a-zA-Z0-9]{4})/', $a['sessionAttr:random_validate_code'], $n);
        $subset = [];
        $title = '获取验证码';
        $url = self::Ip . '/verify-code';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200, $res->getStatusCode(), '');
        return array_merge($m,$n);
    }

    /**
     * 登录
     * @depends testVerifyCode
     */
    public function testLogin($code)
    {
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'POST',
            self::Ip . '/login',
            [
                'json' => [
                    'username' => 'admin','password' => '123321','randomCode' => $code[2]
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '用户登录';
        $url = self::Ip . '/login';
        $params = ['username' => 'admin','password' => '123321','randomCode' => $code[2]];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200, $res->getStatusCode(), '');
    }

    /**
     * 获取用户信息
     * @depends testVerifyCode
     */
    public function testProfile($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request('GET',self::Ip . '/profile', [
            'headers'=>
                [
                    'Content-Type:application/json'
                ],
            'cookies' => $cookieJar
        ]);
        $subset = array('loginTime','roleName','name','mobile','id','username');
        $title = '获取用户信息';
        $url = self::Ip . '/profile';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200, $res->getStatusCode(), '');
    }

    /**
     * 修改密码
     * @depends testVerifyCode
     */
    public function testPassword($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request('PUT',
            self::Ip . '/password',
            [
                'json' => [
                    'oldPsd' => '123321', 'newPsd' => '123321'
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]
        );
        $subset = [];
        $title = '修改密码';
        $url = self::Ip . '/password';
        $params = ['oldPsd' => '123321', 'newPsd' => '123321'];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 车辆组列表
     * @depends testVerifyCode
     */
    public function testDepartments($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request('GET',self::Ip . '/departments',[
            'headers'=>
                [
                    'Content-Type:application/json'
                ],
            'cookies' => $cookieJar
        ]);
        $subset = array ('id','name','pid');
        $title = '车辆组列表';
        $url = self::Ip . '/departments';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 根据车辆ID数组查询车辆
     * @depends testVerifyCode
     */
    public function testQueryVehicles($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'POST',
            self::Ip . '/query-vehicles',
            [
                'json' => [
                    'ids' => [275728,126094]
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','channels','position','depId','depName','driverName','updateTime','fuelStatus');
        $title = '根据车辆ID数组查询车辆';
        $url = self::Ip . '/query-vehicles';
        $params = ['ids' => [275728,126094]];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 模糊查询车辆
     * @depends testVerifyCode
     */
    public function testVehicles($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $queryString = '%E4%BA%AC';  //可为空
        $areaCode = '';     //可为空
        $depId = '';        //可为空
        $url = self::Ip . '/vehicles';
        if (!empty($queryString)){
            $queryStrings = 'queryString='.$queryString ?? '';
        }elseif (!empty($areaCode)){
            $areaCodes = '&areaCode='.$areaCode ?? '';
        }elseif (!empty($depId)){
            $depIds = '&depId='.$depId ?? '';
        }
        if (!empty($queryString) || !empty($areaCode) || !empty($depId)){
            $url = $url . '?' . $queryStrings . $areaCodes . $depIds;
        }
        $res = $this->client->request('GET',$url,[
            'headers'=>
                [
                    'Content-Type:application/json'
                ],
            'cookies' => $cookieJar
        ]);
        $subset = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','channels','position','depId','depName','driverName','updateTime','fuelStatus');
        $title = '模糊查询车辆';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 车辆控制  油电控制
     * @depends testVerifyCode
     */
    public function testFuelControl($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'PUT',
            self::Ip . '/vehicles/275728/fuel-control',
            [
                'json' => [
                    'fuelStatus' => 1
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '油电控制';
        $url = self::Ip . '/vehicles/275728/fuel-control';
        $params = ['fuelStatus' => 1];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 车辆控制  获取车辆油电状态
     * @depends testVerifyCode
     */
    public function testFuelStatus($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'GET',
            self::Ip . '/vehicles/275728/fuel-status',
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('fuelStatus','controlStatus');
        $title = '获取车辆油电状态';
        $url = self::Ip . '/vehicles/275728/fuel-status';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 车辆控制  获取用户油电操作日志
     * @depends testVerifyCode
     */
    public function testFuelControlLog($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $start_time = '0';
        $end_time = '1586329748711';

        //拼接请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/vehicles/275728/fuel-control-log?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('plateNo','controlStatus','createTime','userName');
        $title = '获取用户油电操作日志';
        $url = self::Ip . '/vehicles/275728/fuel-control-log?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  获取报警类型
     * @depends testVerifyCode
     */
    public function testGAlertTypes($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
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
        $res = $this->client->request(
            'GET',
            $url.'?'.http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('id','name','level','type','rule','createTime','expireTime');
        $title = '获取报警类型';
        $url = $url.'?'.http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  创建区域报警
     * @depends testVerifyCode
     */
    public function testPAlertTypes($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'POST',
            self::Ip . '/alert-types',
            [
                'json' => [
                    'name'=>'unit测试','type'=>'fence','rule'=>['type'=>1,'circle'=>['radius'=>'430','lat'=>'39','lng'=>'116']]
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('id','name','rule','createTime');
        $title = '创建区域报警';
        $url = self::Ip . '/alert-types';
        $params = ['name'=>'unit测试','type'=>'fence','rule'=>['type'=>1,'circle'=>['radius'=>'430','lat'=>'39','lng'=>'116']]];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
        $datas = json_decode($res->getBody()->getContents(),true)['data'];
        return $datas['id'];
    }

    /**
     * 报警报表  删除区域报警
     * @depends testVerifyCode
     * @param $code
     * @depends testPAlertTypes
     * @param $id
     */
    public function testDAlertTypes($code,$id){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'DELETE',
            self::Ip . '/alert-types/'.$id,
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '删除区域报警';
        $url = self::Ip . '/alert-types/'.$id;
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  获取正在发生的报警信息
     * @depends testVerifyCode
     */
    public function testCurrentAlerts($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $depid = '-1';
        $alert_type_id = '-1';
        $area_code = '110000';
        $http_query = [
            'depId' => $depid,
            'alertTypeId' =>$alert_type_id,
            'areaCode' => $area_code
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/current-alerts?'.http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('vId','depId','vehicleType','driverName','lng','lat');
        $title = '获取正在发生的报警信息';
        $url = self::Ip . '/current-alerts?'.http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  获取指定车辆当前的告警信息
     * @depends testVerifyCode
     */
    public function testIdCurrentAlerts($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'GET',
            self::Ip . '/vehicles/275728/current-alerts',
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('vid','alerts','plateNo','driverName','depId','depName','vehicleType','plateColor');
        $title = '获取指定车辆当前的告警信息';
        $url = self::Ip . '/vehicles/275728/current-alerts';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  报警趋势
     * @depends testVerifyCode
     */
    public function testAlertTrend($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $dep_ids = '6,4,7,5';
        $type_id = '1';
        $district = 30;
        $area_code = '110000';

        //组装请求参数
        $http_query = [
            'depIds' => $dep_ids,
            'typeId' => $type_id,
            'district' => $district,
            'areaCode' => $area_code,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/alerts/trend?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('depId','depName','ratio','trends');
        $title = '报警趋势';
        $url = self::Ip . '/alerts/trend?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  总报警排行
     * @depends testVerifyCode
     */
    public function testAlertRank($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $start_time = '1582992000000';
        $end_time = '1585670399999';
        $top = 4;
        $area_code = '110000';

        //组装请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
            'top' => $top,
            'areaCode' => $area_code,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/alerts-rank?' . http_build_query($http_query),

            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('depId','depName','ratio');
        $title = '总报警排行';
        $url = self::Ip . '/alerts-rank?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 报警报表  获取车辆报警历史
     * @depends testVerifyCode
     */
    public function testAlerts($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
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
        $res = $this->client->request(
            'GET',
            $url,
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '获取车辆报警历史';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 轨迹播放  获取车辆轨迹信息
     * @depends testVerifyCode
     */
    public function testTrips($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $vid = '275728';

        //组装请求参数
        $http_query = [
            'vId' => $vid,
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/trips?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('vId','plateNo','depId','depName','daySegments');
        $title = '获取车辆轨迹信息';
        $url = self::Ip . '/trips?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 区域查车  根据区域和事件查询车辆
     * @depends testVerifyCode
     */
    public function testSearchVehicles($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $start_time = '1585670400000';
        $end_time = '1585756799999';
        $shapes = ['type'=>1,'circle'=>['radius'=>'900','lat'=>'39','lng'=>'116']];
        $depid = '-1';
        $res = $this->client->request(
            'POST',
            self::Ip . '/search-vehicles',
            [
                'json'=> ['startTime'=>$start_time,'endTime'=>$end_time,'shapes'=>$shapes,'depId'=>$depid],

                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        //有可能有0下标 $subset_data
        $subset = array('id','vehicleType','plateNo','plateColor','simNo','vin','online','status','videoChannelNum','videoChannelNames','depId','depName','driverName','updateTime','fuelStatus');
        $title = '根据区域和事件查询车辆';
        $url = self::Ip . '/search-vehicles';
        $params = ['startTime'=>$start_time,'endTime'=>$end_time,'shapes'=>$shapes,'depId'=>$depid];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 区域查车  获取车辆指定时间内的轨迹
     * @depends testVerifyCode
     */
    public function testVehiclesTrips($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $start_time = '1585670400000';
        $end_time = '1585756799999';

        //组装请求参数
        $http_query = [
            'startTime' => $start_time,
            'endTime' => $end_time,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/vehicles/275728/trips?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('vId','plateNo','depId','depName','trips');
        $title = '获取车辆指定时间内的轨迹';
        $url = self::Ip . '/vehicles/275728/trips?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 音视频  实时播放摄像头信息
     * @depends testVerifyCode
     */
    public function testLive($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $index = '2';
        $type = 'hls';
        $res = $this->client->request(
            'POST',
            self::Ip . '/vehicles/275728/live',
            [
                'json' => ['index'=>$index,'type'=>$type],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('source','name','index');
        $title = '实时播放摄像头信息';
        $url = self::Ip . '/vehicles/275728/live';
        $params = ['index'=>$index,'type'=>$type];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 音视频  停止实时播放
     * @depends testVerifyCode
     */
    public function testStopLive($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'POST',
            self::Ip . '/vehicles/275728/stop-live',
            [
                'json' => ['index'=>1],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '停止实时播放';
        $url = self::Ip . '/vehicles/275728/stop-live';
        $params = ['index'=>1];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 视频回放  获取服务器上存储的视频文件
     * @depends testVerifyCode
     */
    public function testVideos($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
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
        $res = $this->client->request(
            'GET',
            $url,
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);

        //$res['data'][0] data下有可能为空 表示无视频
        $subset = [];
        $title = '获取服务器上存储的视频文件';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 视频回放  通知终端向服务器上传视频
     * @depends testVerifyCode
     */
    public function testUploadVideo($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $file_id = '71594';
        $res = $this->client->request(
            'POST',
            self::Ip . '/vehicles/275728/upload-video',
            [
                'json'=>['fileId'=>$file_id],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('taskId','status');
        $title = '通知终端向服务器上传视频';
        $url = self::Ip . '/vehicles/275728/upload-video';
        $params = ['fileId'=>$file_id];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
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
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'GET',
            self::Ip . '/file-tasks/'.$id,
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('status');   // errorMessage 可选参数
        $title = '获取文件上传状态';
        $url = self::Ip . '/file-tasks/'.$id;
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
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
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'PUT',
            self::Ip . '/file-tasks/'.$id.'/status',
            [
                'json'=>['action'=>$action],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '视频回放  （暂停/继续/取消）文件上传';
        $url = self::Ip . '/file-tasks/'.$id.'/status';
        $params = ['action'=>$action];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 视频回放  通知终端回放视频
     * @depends testVerifyCode
     * @param $code
     * @depends testUploadVideo
     * @param $id
     */
    public function testPlayVideo($code,$id){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $file_id = $id;
        $start_time = '0';      //参数不准确
        $type = 'hls';

        //组装请求参数
        $http_query = [
            'fileId' => $file_id,
            'startTime' => $start_time,
            'type' => $type,
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/vehicles/275728/play-video?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('source','name','index');
        $title = '通知终端回放视频';
        $url = self::Ip . '/vehicles/275728/play-video?' . http_build_query($http_query);
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 视频回放  获取车辆位置信息缓存文件
     * @depends testVerifyCode
     */
    public function testCacheVehicles($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'GET',
            self::Ip . '/caches/vehicles',
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('filePath');
        $title = '获取车辆位置信息缓存文件';
        $url = self::Ip . '/caches/vehicles';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 视频回放  获取行政区域
     * @depends testVerifyCode
     */
    public function testAreaCodes($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'GET',
            self::Ip . '/area-codes',
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('areaCode','name');
        $title = '获取行政区域';
        $url = self::Ip . '/area-codes';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 单个组织当日报警排行——获取报警详情
     * @depends testVerifyCode
     */
    public function testCurrentAlertsDetail($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $depid = '-1';
        $alert_type_id = '-1';
        $area_code = '110000';

        //组装请求参数
        $http_query = [
            'depId' => $depid,
            'areaCode' => $area_code,
            'alertTypeId' => $alert_type_id
        ];
        $res = $this->client->request(
            'GET',
            self::Ip . '/current-alerts/detail?' . http_build_query($http_query),
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = array('id','vId','plateNo','depId','depName','alertType','alertPosition','endPosition','alertValue','alertUnit','gps');
        $title = '单个组织当日报警排行——获取报警详情';
        $url = self::Ip . '/current-alerts/detail?' . http_build_query($http_query);
        $params = ['depId' => $depid,'areaCode' => $area_code,'alertTypeId' => $alert_type_id];
        $this->collect($subset,$res,$title,$url,$params);
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    /**
     * 用户退出
     * @depends testVerifyCode
     */
    public function testLogout($code){
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'IPCS-SESSIONID' => $code[1]
        ], 'ipcs.iov-smart.net');
        $res = $this->client->request(
            'DELETE',
            self::Ip . '/logout',
            [
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        $subset = [];
        $title = '用户退出';
        $url = self::Ip . '/logout';
        $params = [];
        $this->collect($subset,$res,$title,$url,$params);
        $this->exportData();
        $this->assertEquals(200,$res->getStatusCode(), '');
    }

    public function collect($subset,$res,$title,$url,$params){
        $msg = '';
        $data = json_decode($res->getBody()->getContents(),true);
        if (!empty($subset)){
            foreach ($subset as $v) {
                if (!empty($data['data'][0])){
                    if (array_key_exists($v, $data['data'][0]) == false) {
                        $msg .= $v . ' not exist!'. PHP_EOL;
                    }
                }else{
                    if (array_key_exists($v, $data['data']) == false) {
                        $msg .= $v . ' not exist!'. PHP_EOL;
                    }
                }
            }
        }
        if ($res->getStatusCode()){
            $data['message'] = 'success';
        }
        $excelData = [
            $title,
            $url,
            json_encode($params),
            json_encode($subset),
            json_encode($data['data'], JSON_UNESCAPED_UNICODE),
            $data['message'] . PHP_EOL . $msg
        ];
        $redis = $this->cache(7);
        $redis->rpush($this->cache_key, json_encode($excelData));
    }

    public function cache($db){
        $redis = new Redis();
        $redis->connect($this->ip, $this->port); //连接Redis
        $redis->auth($this->auth); //密码验证
        $redis->select($db);//选择数据库2
        return $redis;
    }

    private function exportData()
    {
        //取出来缓存数据，并立即进行删除操作
        $redis = $this->cache(7);
        $testReport = $redis->lrange($this->cache_key, 0, -1);
        $redis->del($this->cache_key);
        if (empty($testReport)) {
            return false;
        }
        $spreadSheet = new Spreadsheet();
        //单元格宽度
        $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(30);
        //行高
        $spreadSheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(80);
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('API接口测试报告');

        //title
        $title = [
            '序号', '接口名称', 'URL', '请求参数', '过滤参数', '返回参数', '测试结果'
        ];
        //列码
        $titleLetter = range('A', 'G');

        //先设置title
        foreach ($titleLetter as $key => $value) {
            $workSheet->setCellValue($value . '1', $title[$key]);
        }

        //循环将内容赋值对应的单元格
        $i = 2;
        foreach ($testReport as $key => $value) {
            $value = json_decode($value, true);
            $workSheet->setCellValue('A' . $i, $i - 1);
            foreach ($value as $k => $v) {
                $workSheet->setCellValue($titleLetter[$k + 1] . $i, $v);
                //样式设置 - 单元格背景颜色
                if ($k == count($value) - 1) {
                    if (strpos(rtrim($v),'成功') !== false || rtrim($v) == '' || strpos(rtrim($v),'success') !== false) {
                        $workSheet->getStyle($titleLetter[$k + 1] . $i)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('00FF00');
                    } else {
                        $workSheet->getStyle($titleLetter[$k + 1] . $i)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('FF0000');
                    }
                }
            }
            $i++;
        }

        //设置单元格水平、垂直居中
        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
        ];
        $end = $i - 1;
        $workSheet->getStyle('A1:G' . $end)->getAlignment()->setWrapText(true);
        $workSheet->getStyle('A1:G' . $end)->applyFromArray($styleArray);

        //样式设置 - 边框
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => 'FF0000'],
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ],
        ];
        $workSheet->getStyle('A1:G' . $end)->applyFromArray($styleArray);

        $writer = new Xls($spreadSheet);
        //在test目录下创建record/PhpunitReport/对应日期目录
        $dir = './PhpunitReport/' . date("Y-m-d");
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $writer->save($dir . '/ipcsTest.xls');
        // csv
        @unlink($dir . '/ipcsTest.csv');
        $fp = fopen($dir . '/ipcsTest.csv', 'w');
        unset($title[0]);
        fputcsv($fp, $title);
        foreach ($testReport as $value) {
            $value = json_decode($value, true);
            fputcsv($fp, $value);
        }
        fclose($fp);
    }
}
