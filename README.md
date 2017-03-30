### 配置类库（config）

> 本路由类库作用是加载解析配置文件或者指定目录下全部配置文件

#### 特性：

- 从文件或者整个目录配置文件加载解析
- 把数据（数组格式）写入到指定格式文件存储
- 修改加载后的数据（数组格式），可以把修改结果同步到原来配置文件中
- 支持ini，json，php，xml，yml格式配置文件
- 支持无限以 . 符号的方式访问配置选项
- 支持使用数组的方式访问，修改数据
- 使用缓存器可以把数据缓存起来，下次加载不会再次解析文件

#### 注意：
> 同步功能，只同步以配置文件方式加载进来的数据，如果数据不是通过文件加载方式进来的，则不会同步。

### 安装
``` php
composer require sojf/config
```

### 快速例子
创建配置文件`conf.yml` 和测试文件 `test.php`


#### conf.yml
```yml
__runtime:

  charset: UTF-8
  language: zh
  timezone: PRC
  session:
      expire: 604800
      name: N_SESSION_ID
      gc_probability: 1
      gc_divisor: 1000

  mkdir:
      session: var/session
      view_cache: var/view
      log: var/log

  include:
        - app/Lib/function.php
```

#### test.php
```php
require './vendor/autoload.php';

use Sojf\Config\Config;
use Sojf\Config\Cache\File;

// 这里修改为你配置文件的正确路径
$confPath = __DIR__ . DIRECTORY_SEPARATOR . 'conf.yml';

// 这个是缓存文件路径，可以自行修改
$cachePath = __DIR__ . DIRECTORY_SEPARATOR . 'conf.cache.data';

// 实例化缓存器
$cache = new File($cachePath);

// 实例化配置对象
$conf = new Config($confPath, $cache);

// 查看加载结果
echo $conf;

// 获取数据
$session['name'] = $conf['session.name'];
$session['expire'] =  $conf->get('session.expire');

// 获取数据时，可以设置默认值，如果获取不到会返回默认值
$default = 'just default value';
$session['default'] = $conf->get('session.null', $default);

// 还可以使用闭包当作默认值，当获取不到数据，会调用闭包
$default = function () {
    print_r('这是闭包调用');
};
$session['default'] = $conf->get('session.null', $default);

// 设置数据
$conf['new.value1'] = 'set value1';
$conf->set('new.value2', 'set value2');

// 删除数据
$conf->del('session.gc_probability');
unset($conf['session.gc_divisor']);

/*
 * 同步修改回配置文件，
 * 你会发现 session.gc_probability session.gc_divisor 不存在了
 * */
$conf->sync();

// 可以把数据保存到指定文件
$package = array(
    'package' => array(
        'issues' => 'https://github.com/sojf/config/issues',
        'source' => 'https://github.com/sojf/config'
    )
);

// 可以保存成json，ini，php，yml，xml格式的文件，只需要修改后缀即可
$conf->save($package, __DIR__ . DIRECTORY_SEPARATOR . 'package.xml');

/*
 * 最后你还会发现多出两个文件
 * package.xml 文件是$package数据的保存文件
 * conf.cache.data 文件，这个便是缓存文件，如果配置文件没有修改过，就不会再重复解析配置文件了
 * */
```

#### 执行`test.php`查看运行结果

---

### Config类

| **方法** | **参数** | **返回值** | **说明** |
| ------------ | ------------ | ------------ | ------------ |
| get | `$key, $default = null` | `mixed` | 获取数据
| set | `$key, $value` | 无 | 设置数据
| del | `$key` | 无 | 删除数组某个key
| all | 无 | `array` | 返回所有数据 

### File类（缓存器）

`Sojf\Config\Interfaces\Cache`接口的实现

| **方法** | **参数** | **返回值** | **说明** |
| ------------ | ------------ | ------------ | ------------ |
| get | `$key, $default = null` | `mixed` | 获取数据
| set | `$key, $value` | 无 | 设置数据
| add | `$data` | 无 | 追加数据
| del | `$key` | 无 | 删除数组某个key
| all | 无 | `array` | 返回所有数据 
| open | 无 | 无 | 打开缓存文件 （不用特意去调用，在构造函数中已经调用）
| close | 无 | 无 | 关闭缓存文件 （不用特意去调用，析构函数中已经调用）