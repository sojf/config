## Sojf Config Component

### PHP配置类

- 支持从文件加载配置
- 支持从目录加载所有配置文件
- 支持把配置项写入到指定文件
- 支持同步新的配置到配置文件
- 支持ini，json，php，xml，yml格式配置文件
- 支持无限以 . 符号的方式访问配置选项
- 支持缓存配置数据

### 注意
> 同步只同步以配置文件方式加载进来数据，如果某些配置不是已文件加载方式，则不会同步。规则是同步更新或者新增的数据。

### 安装
``` php
composer require sojf/config
```

### db.yml
```yml
user: root
password: root
```


### 使用方法
``` php
require 'vendor/autoload.php';

#可以实例化时候加载配置文件或目录
#$conf = new \Sojf\Config\Config('path/to/db.yml');

$conf = new \Sojf\Config\Config();

#也可以加载整个目录所有配置文件,目录不存在会报错
//$conf->load(__DIR__ . '/conf');

$conf->load('path/to/db.yml');

#设置
$conf->set('db.dirver', 'mysql');
$conf->set('db.port', 3306);
$conf->set('db.firewall.allow.host', '127.0.1');

#同步
$conf->sync();

#获取
$db = $conf->get('db');
$user = $conf->get('db.user');
$allow = $conf->get('db.firewall.allow.host');

#遍历
foreach ($conf as $key => $value) {

    echo "key: $key";
    var_dump($value);
}

#删除
$conf->del('db');
$conf->del('db.user');
$conf->del('db.firewall.allow.host');


#创建新的配置

$conf->set('other1', 'other1');
$conf->set('other2', 'other2');
$conf->set('other3', 'other3');

$conf->set('user.say', '你好!');
$conf->set('user.name', 'sojf');
$conf->set('user.age', 19);
$conf->set('user.money', 12.88);
$conf->set('user.vip', true);
$conf->set('user.online', false);

#保存user配置
$conf->save(__DIR__ . '/user.xml', $conf->get('user));
$conf->save(__DIR__ . '/user.json', $conf->get('user'));

#保存所有配置
$conf->save(__DIR__ . '/user.yml', $conf->all());
$conf->save(__DIR__ . '/user.php', $conf->all());
$conf->save(__DIR__ . '/user.ini', $conf->all());
```

