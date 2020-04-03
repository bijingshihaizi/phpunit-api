# IPCS-PHPUNIT使用方式

##### 1.克隆项目，进入到项目根目录

##### 2.docker build -t  unit . 编译镜像

##### 3.docker run -p 22:22 unit镜像

##### 4.进入容器/home/unit目录， 运行

##### 		phpunit tests/test.php 或者自定义的unit测试文件 。

##### 5.测试成功后，根目录自动生成PhpunitReport文件下面生成测试报告。