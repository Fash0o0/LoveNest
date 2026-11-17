# LoveNest

LoveNest 基于 [Ki 的 LikeGirl v5.2.0](https://gitee.com/kiCode111/like-girl-v5.2.0) 修改，采用 [GNU AGPL v3](LICENSE)。

## 运行

1. 准备 PHP 与 MySQL。启用 mysqli 和 DOM。图片上传需要带 WebP 支持的 GD。天气功能需要 cURL 与 OpenSSL。
2. 为测试环境准备 LikeGirl v5.2.0 的初始数据库，或导入你自己有权使用的数据库备份。点滴多图字段缺失时，按迁移文件说明执行 migrations/ 下的对应迁移。
3. 将 admin/Config_DB.example.php 复制为 admin/Config_DB.php，并填入本机数据库账号、密码与站点安全码。上传图片需要 images/ 可写。
4. 如需实时天气，将 weather-config.example.php 复制为 weather-config.local.php，填入和风天气 API 主机、项目与凭据 ID、地点信息。把 Ed25519 私钥放在 Web 根目录外，并通过 QWEATHER_PRIVATE_KEY_PATH 指向它。
5. 本地站点请仅监听回环地址。使用 PHP 内置开发服务器时，需通过路由脚本阻止访问凭据、SQL 备份和运行时数据，因为它不会读取 .htaccess。

## 修改记录

- 2026-10-24 整理点滴时间轴和多图上传，加入相关数据库迁移。
- 2026-11-17 整理 LoveNest 源码发布，将站点配置、天气凭据和私人数据留在部署环境。
