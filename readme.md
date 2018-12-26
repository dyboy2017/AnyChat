## AnyChat

AnyChat是一款在线匿名聊天交友的软件，目前支持Android客户端和WEB端，WEB端采用自适应应对各客户端的屏幕大小适应问题，基于socket协议实现实时传输消息，服务端基于PHP语言编写，前端使用HTML+Javascript+CSS3，具有良好的UI界面。

- author:	DYBOY
- time:		2018-12-18

## 特色功能：

- 匿名群聊，每个用户生成随机ID
- 聊天背景刷新切换，拒绝视觉疲劳
- 上线、收到消息提示音，不错过重要消息
- 服务器只做消息转发，不做任何存储
- 会话加密，保护个人隐私

## Online：

http://chat.top15.cn

## Github:

https://github.com/dyboy2017/AnyChat

## More About：

https://blog.dyboy.cn/develop/102.html

## Preview:

![界面预览图](https://upload-images.jianshu.io/upload_images/6661013-8679e4814175b74a.png)

## Usage:

1. 在有PHP（版本>5.3 均可）软件的主机上，在命令终端下运行: `php server_anychat.php`
2. 双击访问同目录下的 `index.html`，或者放到网页运行环境下的 `www` 目录，访问主机IP或者域名即可
3. 修改说明，若在本地环境运行需要修改 `index.html`中javascript部分的ws连接地址，改为本地的即可，不修改默认链连接远程服务器(`chat.dyboy.cn`)

## 更新记录:

2019-12-20：
- V2.0
- 上线提示音
- 用户信息
- 提升各终端兼容
- 优化界面

2018-12-18:
- V1.0
- 实现基本功能
