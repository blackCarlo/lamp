# Docker LAMP PHP 5.6

基于 `php:5.6-apache` 的单容器 LAMP 环境，包含：

- Apache 2.4
- PHP 5.6
- MariaDB 10.1
- PHP 扩展：`mysql`、`mysqli`、`pdo_mysql`

## 使用

```bash
docker compose up -d --build
```

访问：

- Web 首页：http://127.0.0.1:8080/
- PHP 信息：http://127.0.0.1:8080/info.php
- MySQL 连接测试：http://127.0.0.1:8080/mysqli.php

## 默认数据库

`docker-compose.yml` 中默认配置：

- root 密码：`root`
- 数据库：`lamp`
- 用户名：`lamp`
- 密码：`lamp`
- MySQL 容器端口：`3306`
- MySQL 宿主机端口：`${MYSQL_HOST_PORT:-33060}`

数据库数据保存在 Docker volume `lamp_mysql_data` 中。

## 常用命令

```bash
docker compose ps
docker compose logs -f
docker exec -it lamp-php56 bash
docker exec -it lamp-php56 mysql -uroot -proot
docker compose down
```
