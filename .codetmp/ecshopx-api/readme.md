# ECShopX

## 介绍

ECShopX是一套多终端平台商城解决方案，采用前后端分离，后端基于lumen,前端基于Vue,小程序基于taro。

## 要求
 - php >= 7.4
 - lumen = 8.3
 - mysql >= 5.7
 - redis >= 4.0

## 安装
    composer install

## 配置`.env`
* 修改数据库配置
* 修改redis配置
* 修改其他配置

## 生成APP_KEY
    php artisan key:generate

### 更新数据库
    php artisan doctrine:migrations:migrate

### 启动服务
通过`php server`启动
    
    php -S 127.0.0.1:9058 -t public
