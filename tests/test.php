<?php
use PHPUnit\Framework\TestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

class test extends TestCase
{
    const Ip = "https://localhost";
    private $ip = '127.0.0.1';
    private $port = '6379';
    private $auth = 'root';
    private $cache_key = 'phpunit';
    private $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function testApi()
    {
        //验证cookie
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray([
            'cookie' => '...'
        ], 'https://localhost');
        // GET
        $res = $this->client->request('GET',self::Ip );
        // POST
        $res = $this->client->request('POST',self::Ip ,[
                'json' => [
                    'username' => 'admin','password' => '123321','randomCode' => ''
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);
        // PUT
        $res = $this->client->request('PUT',self::Ip ,[
                'json' => [
                    'oldPsd' => '123321', 'newPsd' => '123321'
                ],
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]
        );
        // DELETE
        $res = $this->client->request('DELETE',self::Ip,[
                'headers'=>['Content-Type:application/json'],
                'cookies'=>$cookieJar
            ]);

        //报告内容
        $subset = [];           //过滤返回参数
        $title = '接口名称';    //接口名称
        $url = self::Ip ;      //接口url
        $params = [];           //返回参数
        //缓存数据
        $this->collect($subset,$res,$title,$url,$params);
        //测试的最后一个接口进行输出
        $this->exportData();
        $this->assertEquals(200, $res->getStatusCode(), '');
    }

    //处理并缓存数据
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
        if ($res->getStatusCode() == 200){
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
        $redis->connect($this->ip, $this->port);
        $redis->auth($this->auth);
        $redis->select($db);
        return $redis;
    }
    //输出表格
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
