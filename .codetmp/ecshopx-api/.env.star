# 标识应用环境, 本地环境设置为local,生产环境为production
APP_ENV=production
# 调试模式, 正式环境可以关闭(true,false)
APP_DEBUG=true
# 时区不要修改
APP_TIMEZONE=PRC

# 数据库相关配置
## DB_CONNECTION默认不需要改
DB_CONNECTION=default
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# 数据库主从配置
#DB_DRIVER=master_slave
#DB_MASTER={"user":"root","password":"123455","host":"localhost","dbname":"ecx"}
#DB_SLAVES=[{"user":"root","password":"123455","host":"localhost2","dbname":"ecx"},{"user":"root","password":"123455","host":"localhost3","dbname":"ecx"}]

# REDIS配置
REDIS_CLIENT=predis
REDIS_HOST=
# 必须要设置密码
REDIS_PASSWORD=
REDIS_PORT=6379
# 如果按照服务配置，则参考config/database.php中redis部分进行配置，并注释下面这条默认项，否则读取的数据库都是 0
REDIS_DATABASE=0

# 缓存驱动, 默认使用redis
CACHE_DRIVER=redis
# 队列驱动, 默认使用redis
QUEUE_DRIVER=redis

# 文件存储。oss或者qiniu或者aws（亚马逊s3）
DISK_DRIVER=qiniu

# 七牛相关配置
## 七牛密钥配置
QINIU_ACCESS_KEY=
QINIU_SECRET_KEY=

## 图片，视频，私有文件的域名都是一样的
### DOMAIN根据区域自己选填
### 华东(z0) https://up.qiniup.com;
### 华北(z1) https://up-z1.qiniup.com;
### 华南(z2) https://up-z2.qiniup.com;
### 北美(up-na0) https://up-na0.qiniup.com;
### 东南亚(up-as0) https://up-as0.qiniup.com

## 七牛图片配置
### 根据区域自己选填链接
QINIU_IMAGE_DOMAIN=
### 七牛公有图片bucket
QINIU_IMAGE_NAME=espier-image
### 根据区域选填。华东(z0)，华北(z1)，华南(z2)，北美(up-na0)，东南亚(up-as0)
QINIU_IMAGE_REGION=z2

## 七牛视频配置
QINIU_VIDEO_DOMAIN=
### 七牛公有视频bucket
QINIU_VIDEO_NAME=espier-videos
### 根据区域选填。华东(z0)，华北(z1)，华南(z2)，北美(up-na0)，东南亚(up-as0)
QINIU_VIDEO_REGION=z2

## 七牛文件配置
QINIU__FILE_DOMAIN=
### 私有文件bucket，主要用于文件上传和下载
QINIU_FILE_NAME=espier-file

# OSS配置
## OSS密钥配置
OSS_ACCESS_KEY=
OSS_SECRET_KEY=

### 阿里云存储不同地区域名不一样，文件类的不需要cdn,根据自己的区域选填
### 列举如下:
### 华东1（杭州）: https://oss-cn-hangzhou.aliyuncs.com;
### 华东2（上海）: https://oss-cn-shanghai.aliyuncs.com;
### 华北1（青岛）: https://oss-cn-qingdao.aliyuncs.com;
### 华北2（北京）: https://oss-cn-beijing.aliyuncs.com;
### 华北 3（张家口）: https://oss-cn-zhangjiakou.aliyuncs.com;
### 华北5（呼和浩特）: https://oss-cn-huhehaote.aliyuncs.com;
### 华北6（乌兰察布）: https://oss-cn-wulanchabu.aliyuncs.com;
### 华南1（深圳）: https://oss-cn-shenzhen.aliyuncs.com;
### 华南2（河源）: https://oss-cn-heyuan.aliyuncs.com;
### 西南1（成都）: https://oss-cn-chengdu.aliyuncs.com;
### 中国（香港）: https://oss-cn-hongkong.aliyuncs.com;

## OSS文件配置
OSS_FILE_ENDPOINT=https://oss-cn-shanghai.aliyuncs.com
### OSS私有文件存储bucket
OSS_FILE_BUCKET=oss-cdn
### 是否开启cname，私有文件不需要开
OSS_FILE_IS_CNAME=false

## OSS图片配置
OSS_IMAGE_ENDPOINT=https://oss-cn-shanghai.aliyuncs.com
### OSS公有图片bucket
OSS_IMAGE_BUCKET=oss-cdn
### 是否开启cdn
OSS_IMAGE_IS_CNAME=false
### 图片cdn,开启cdn则该配置必填
OSS_IMAGE_DOMAIN=

## OSS视频配置
OSS_VIDEO_ENDPOINT=https://oss-cn-shanghai.aliyuncs.com
### OSS公有视频bucket
OSS_VIDEO_BUCKET=oss-cdn
### 是否开启cdn
OSS_VIDEO_IS_CNAME=flase
### 视频cdn,开启cdn则该配置必填
OSS_VIDEO_DOMAIN=

## 亚马逊s3配置信息
### s3密钥
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
### IAM授权时的arn，和s3密钥启用一个即可
AWS_ARN=
### 地区
AWS_REGION=
### 存储桶
AWS_BUCKET=
### 自定义域名
AWS_ENDPOINT=

# JWT 相关配置, 通过php artisan jwt:secret生成密钥，线上务必修改
JWT_SECRET=ker4H1Gp4TsddddWJMaB2SMA8Zsh3drv
JWT_TTL=7200
JWT_REFRESH_TTL=20160

# 微信第三方平台配置。本地开发环境不配置, 会影响到调用微信的相关页面, 整体不影响后台开发。
## 第三方平台APPID
WECHAT_APPID=
## 第三方平台SECRET
WECHAT_SECRET=
## 消息校验Token
WECHAT_TOKEN=
## 消息加解密Key
WECHAT_AES_KEY=
## 开启微信接口debug模式
WECHAT_DEBUG=true

# 微信支付通知回调地址
WECHAT_PAYMENT_NOTIFY=https://域名/wechatAuth/wxpay/notify

# 支付宝配置
ALIPAY_PAYMENT_NOTIFY=https://域名/api/alipay/notify
ALIPAY_PAYMENT_RETURN_PC=https://pc域名/#/user/orderList
ALIPAY_PAYMENT_RETURN_H5=https://h5域名
ALIPAY_MODE=normal

#营销中心-相关配置参数
MARKETING_CENTER_BASEURI=https://ecshopx2-ba.shopex123.com
MARKETING_CENTER_TOKEN=tafhb6kl71xlty4c
MARKETING_CENTER_APPKEY=1ojdeu8zob

# SWOOLE 配置
SERVER_HOST=0.0.0.0
SERVER_PORT=9058

# 好像没用
SERVER_OPTIONS_USER=www-data
SERVER_OPTIONS_GROUP=www-data
SERVER_OPTIONS_DAEMONIZE=false
SERVER_OPTIONS_WORKER_NUM=4

# dingo api标准配置, 根据实际场景进行配置，token需要修改
API_PREFIX=api
API_NAME="Espier API"
API_STANDARDS_TREE=vnd
API_STRICT=false
API_DEBUG=false
API_VERSION=v1
API_SUBTYPE=espier
API_CONDITIONAL_REQUEST=false
API_TOKEN=Os6Bass1oT5vig2Yod0yiT8dU0as5cIn

# RABBITMQ配置
RABBITMQ_HOST=
RABBITMQ_PORT=5672
RABBITMQ_VHOST=/
RABBITMQ_LOGIN=guest
RABBITMQ_PASSWORD=
RABBITMQ_QUEUE=default
RABBITMQ_EXCHANGE_DECLARE=true
RABBITMQ_QUEUE_DECLARE_BIND=true
RABBITMQ_QUEUE_PASSIVE=false
RABBITMQ_QUEUE_DURABLE=true
RABBITMQ_QUEUE_EXCLUSIVE=false
RABBITMQ_QUEUE_AUTODELETE=false
RABBITMQ_EXCHANGE_NAME=sweetheart
RABBITMQ_EXCHANGE_TYPE=direct
RABBITMQ_EXCHANGE_PASSIVE=false
RABBITMQ_EXCHANGE_DURABLE=true
RABBITMQ_EXCHANGE_AUTODELETE=false
RABBITMQ_ERROR_SLEEP=false

# NEO4J配置
## http协议端口是7474，bolt协议端口是7687
NEO4J_DEFAULT_PROTOCOL=http
NEO4J_DEFAULT_HOST=127.0.0.1
NEO4J_DEFAULT_PORT=7474
NEO4J_DEFAULT_USERNAME=neo4j
NEO4J_DEFAULT_PASSWORD=

# 默认小程序模板名称
DEFAULT_WEISHOP_TEMP=yykweishop

# 微商城小程序配置
## 微信模板id
YYKWEISHOP_TEMPLATE_ID=1
## 微信模板id(小程序支持直播，一般可不填)
YYKWEISHOP_TEMPLATE_ID_2=1
## 小程序自定义版本
YYKWEISHOP_VERSION=v1.0.0
## request合法域名，逗号分割
YYKWEISHOP_REQUESTDOMAIN=https://b.test.cn,https://apis.map.qq.com
## socket合法域名，逗号分割
YYKWEISHOP_WSREQUESTDOMAIN=wss://b-websocket.test.cn
## uploadFile合法域名，逗号分割,七牛，根据自己实际情况填，参照配置文件上面云存储的说明
YYKWEISHOP_UPLOADDOMAIN=https://up.qiniup.com,https://up-as0.qiniup.com,https://up-na0.qiniup.com,https://up-z1.qiniup.com,https://up-z2.qiniup.com
## uploadFile合法域名，逗号分割,阿里OSS,根据自己实际情况填，参照配置文件上面云存储的说明
#YYKWEISHOP_UPLOADDOMAIN=https://oss-cn-hangzhou.aliyuncs.com
## downloadFile合法域名，逗号分割，七牛，根据自己实际情况填，参照配置文件上面云存储的说明
YYKWEISHOP_DOWNLOADDOMAIN=https://mmbiz.qpic.cn,https://wx.qlogo.cn,https:thirdwx.qlogo.cn,https://up.qiniup.com,https://up-as0.qiniup.com,https://up-na0.qiniup.com,https://up-z1.qiniup.com,https://up-z2.qiniup.com
## downloadFile合法域名，逗号分割，阿里OSS,根据自己实际情况填，参照配置文件上面云存储的说明
#YYKWEISHOP_DOWNLOADDOMAIN=https://mmbiz.qpic.cn,https://wx.qlogo.cn,https://oss-cn-hangzhou.aliyuncs.com,https://thirdwx.qlogo.cn
## 业务域名，逗号分割
YYKWEISHOP_WEBVIEWDOMAIN=

# 核销小程序配置，没有这个业务不需要填
YYKZS_APPID=
YYKZS_APP_SECRET=

# websocket配置
WEBSOCKET_SERVER_PORT=9051
WEBSOCKET_SERVER_HOST=域名
TIPS_WS_URI=https://域名/ws:9051
TIPS_WS_KEY=

# sentry配置，报错收集
SENTRY_LARAVEL_DSN=

# 分销配置
BROKERAGE_URI=
BROKERAGE_URI_ITEM=

# OMS配置
OMS_TOKEN=
OMS_API_URL=
ERP_GY_TOKEN=
OMS_WHITE_IP=

# 默认地址模板
ADDRESS_TEMPLATE_ID=

# 日志删除时间
DEL_OPERATOR_LOGS_DATE=
# 是否saas
SYSTEM_IS_SAAS=false
# 主company_id，单账户，不是saas的配置
SYSTEM_COMPANYS_ID=1
# 设置系统主要的企业id，用于pc和h5
SYSTEM_MAIN_COMPANYS_ID=1

# 是否使用系统菜单,false则是固定菜单，true则更新storage/static/目录下的菜单
USE_SYSTEM_MENU=true
# 系统版本，standard|platform
PRODUCT_MODEL=platform

# 对接saas ERP的配置信息
STORE_KEY=59887c5ceec36fa1a9d9a72c6cdf3741
CERTI_BASE_URL=https://您的域名/
# 矩阵关系绑定API
MATRIX_REALTION_URL=https://iframe.shopex.cn/?
# 矩阵API
MATRIX_API_URL=http://matrix.ecos.shopex.cn/sync
VERIFY_APP_ID=ecos.ecshopx

# 腾讯地图接口KEY, 建议根据项目进行申请
QQMAP_KEY=PSPBZ-KQ5CW-CSGRF-ON2S4-K2HQJ-XEBQG
# 腾讯地图配置, 建议根据项目进行申请
API_TENCENT_POSITION_BASEURI=https://apis.map.qq.com
API_TENCENT_POSITION_APP_KEY=
API_TENCENT_POSITION_APP_SECRET=

# 是否需要服务号，false则需要自己绑定到开放平台
WX_OPEN_THIRD=true
# 是否需要第三方平台
WXA_NEED_OPEN_PLATFORM=true

# prism配置，默认不用动
OPENAPI_SHOPEX_URL=https://openapi.shopex.cn
PRISM_URL=http://openapi.shopex.cn/api
PRISM_KEY=a4dyatls
PRISM_SECRET=xescyjbdmzox75cfybab

# license配置，不用管
LICENSE_PRODUCTION_URL=https://service.ec-os.net/api/yyk/register
LICENSE_PRODUCT=yyk

# 文档生成配置
SWAGGER_STORAGE_DIR=apidocs
SWAGGER_DOCS_ROUTER=api-doc
SWAGGER_API_HOST=https://域名
SWAGGER_API_BASE_PATH=/api/h5app/

# 发票链接
FAPIAO_HANGXIN_API_URL=

# 神州数码OMS
OWNER_ID=      #电商编码
CODE_NAME=         #OMS 系统在电商系统中的代号名称
SYSTEM_ID=          #OMS 系统在电商系统中的代号，双方提前约定；String 类型
STOCK_ID=         #仓库ID
OMS_SECRET_KEY=    #key
PLATFORM_ID=             #电商平台在第三方 OMS 系统中的 ID
REQUEST_URL=http://域名/interface_HttpServiceEC/DoAction.aspx

# pc端扫码登录
PC_WXCODE_LOGIN=

# H5地址
H5_BASE_URL=
# H5域名后缀
H5_DOMAIN_SUFFIX=
# PC域名后缀
PC_DOMAIN_SUFFIX=

# 汇付配置信息，HFPAY_IS_OPEN(值为true、false)true启用汇付支付渠道其他支付渠道则隐藏; HFPAY_NOTIFY_URL 异步推送地址, 事例:https://ecshopx.shopex123.com/api/third/hfpay/notify
HFPAY_IS_OPEN=
HFPAY_NOTIFY_URL=

#营销中心-相关配置参数
MARKETING_CENTER_BASEURI=https://域名
MARKETING_CENTER_TOKEN=
MARKETING_CENTER_APPKEY=


#达达同城配送
DADA_APP_KEY=
DADA_APP_SECRET=
DADA_IS_ONLINE=true
API_BASE_URL=https://域名/api/
AMAP_LBS_KEY=

# 外部请求默认uri
EXTERNAL_BASEURI=

# 随机盐
RAND_SALT=tEJOIkPoKPWu3dfa

# 平台短信相关参数，项目不需要配置
SMS_ENTID=
SMS_ENTPWD=
SMS_LICENCE=
SMS_SOURCE=
SMS_SECRET=

# 店务自建应用
DIS_WORKWECHAT_H5_BASEURI=http://域名/pages/index
DIS_WORKWECHAT_H5_AUTHURI=http://域名/pages/auth/index
