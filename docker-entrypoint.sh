#!/bin/bash
set -e

# MySQL 使用的 Unix Socket 路径
MYSQL_SOCKET="/var/run/mysqld/mysqld.sock"

# root 密码
# 优先使用 MYSQL_ROOT_PASSWORD
# 如果没有设置，则兼容 MYSQL_PASS
# 如果两者都没有设置，则默认使用 root
MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-${MYSQL_PASS:-root}}"

# 可选：首次初始化时创建的数据库名
MYSQL_DATABASE="${MYSQL_DATABASE:-}"

# 可选：首次初始化时创建的普通数据库用户名
MYSQL_USER="${MYSQL_USER:-}"

# 可选：首次初始化时创建的普通数据库用户密码
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"

# 创建 MySQL 数据目录和运行目录
mkdir -p /var/lib/mysql /var/run/mysqld

# 确保目录权限属于 mysql 用户
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# 转义 SQL 字符串中的单引号
# 例如 abc'def 会变成 abc''def
sql_escape()
{
    printf "%s" "$1" | sed "s/'/''/g"
}

# 转义 SQL 标识符中的反引号
# 用于数据库名，例如 my`db 会变成 my``db
sql_identifier_escape()
{
    printf "%s" "$1" | sed 's/`/``/g'
}

# 等待 MySQL 启动完成
# 参数 $1 是临时 MySQL 进程 PID
# 这个函数会同时检查：
# 1. MySQL 进程是否还活着
# 2. Socket 文件是否存在
# 3. MySQL 是否能执行 SELECT 1
wait_for_mysql()
{
    local pid="$1"
    local i

    for i in $(seq 1 60); do
        # 如果 MySQL 进程提前退出，说明启动失败，不继续傻等
        if [ -n "$pid" ] && ! kill -0 "$pid" 2>/dev/null; then
            echo "[ERROR] MySQL process exited before startup completed." >&2
            return 1
        fi

        # 首次初始化阶段 root 还没有设置密码，所以这里不带密码连接
        if [ -S "${MYSQL_SOCKET}" ] && mysql --socket="${MYSQL_SOCKET}" -uroot -e "SELECT 1" >/dev/null 2>&1; then
            return 0
        fi

        sleep 1
    done

    echo "[ERROR] MySQL did not start within 60 seconds." >&2
    return 1
}

# 判断是否已经初始化过数据库
# /var/lib/mysql/mysql 目录存在，说明已有数据库数据
# MYSQL_ROOT_PASSWORD / MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD
# 只会在首次初始化时生效
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "[INFO] Initializing MySQL data directory..."

    # 初始化 MySQL 数据目录
    # 老版本 MySQL/MariaDB 通常使用 mysql_install_db
    # 新版本 MySQL 可能使用 mysqld --initialize-insecure
    if command -v mysql_install_db >/dev/null 2>&1; then
        mysql_install_db --user=mysql --datadir=/var/lib/mysql >/dev/null
    else
        mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql
    fi

    # 临时启动 MySQL
    # --skip-networking 表示不监听 TCP 端口，只允许通过本地 socket 初始化
    mysqld_safe --skip-networking --socket="${MYSQL_SOCKET}" &

    # 保存临时 MySQL 进程 PID
    pid="$!"

    # 等待 MySQL 真正可用
    echo "[INFO] Waiting for MySQL startup..."
    wait_for_mysql "$pid"

    # 转义 root 密码，避免密码中包含单引号导致 SQL 出错
    MYSQL_ROOT_PASSWORD_ESCAPED="$(sql_escape "$MYSQL_ROOT_PASSWORD")"

    echo "[INFO] Securing root account..."

    # 删除匿名用户
    # 删除默认 test 数据库
    # 设置 root@localhost 密码
    # 创建 root@'%'，允许从容器外连接 MySQL
    #
    # 注意：
    # root@'%' 适合本地测试、靶场、开发环境
    # 生产环境不建议开放 root 远程登录
    mysql --socket="${MYSQL_SOCKET}" -uroot <<EOSQL
DELETE FROM mysql.user WHERE User='';
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\_%';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD_ESCAPED}' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '${MYSQL_ROOT_PASSWORD_ESCAPED}' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOSQL

    # 如果设置了 MYSQL_DATABASE，则创建对应数据库
    if [ -n "$MYSQL_DATABASE" ]; then
        MYSQL_DATABASE_ESCAPED="$(sql_identifier_escape "$MYSQL_DATABASE")"

        echo "[INFO] Creating database ${MYSQL_DATABASE}..."

        mysql --socket="${MYSQL_SOCKET}" -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOSQL
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE_ESCAPED}\` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
EOSQL
    fi

    # 如果设置了 MYSQL_USER，并且用户不是 root，则创建普通业务用户
    if [ -n "$MYSQL_USER" ] && [ "$MYSQL_USER" != "root" ]; then

        # 创建普通用户时必须提供 MYSQL_PASSWORD
        if [ -z "$MYSQL_PASSWORD" ]; then
            echo "[ERROR] MYSQL_PASSWORD is required when MYSQL_USER is set." >&2

            # 出错前关闭临时 MySQL
            mysqladmin --socket="${MYSQL_SOCKET}" -uroot -p"${MYSQL_ROOT_PASSWORD}" shutdown

            exit 1
        fi

        # 转义用户名和密码
        MYSQL_USER_ESCAPED="$(sql_escape "$MYSQL_USER")"
        MYSQL_PASSWORD_ESCAPED="$(sql_escape "$MYSQL_PASSWORD")"

        echo "[INFO] Creating application user ${MYSQL_USER}..."

        # 如果指定了数据库，则只授权该用户访问指定数据库
        if [ -n "$MYSQL_DATABASE" ]; then
            mysql --socket="${MYSQL_SOCKET}" -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOSQL
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE_ESCAPED}\`.* TO '${MYSQL_USER_ESCAPED}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD_ESCAPED}';
GRANT ALL PRIVILEGES ON \`${MYSQL_DATABASE_ESCAPED}\`.* TO '${MYSQL_USER_ESCAPED}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD_ESCAPED}';
FLUSH PRIVILEGES;
EOSQL
        else
            # 如果没有指定数据库，则授权该用户访问所有数据库
            # 注意：适合测试环境，生产环境建议限制权限范围
            mysql --socket="${MYSQL_SOCKET}" -uroot -p"${MYSQL_ROOT_PASSWORD}" <<EOSQL
GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_USER_ESCAPED}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD_ESCAPED}';
GRANT ALL PRIVILEGES ON *.* TO '${MYSQL_USER_ESCAPED}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD_ESCAPED}';
FLUSH PRIVILEGES;
EOSQL
        fi
    fi

    # 初始化完成后关闭临时 MySQL
    mysqladmin --socket="${MYSQL_SOCKET}" -uroot -p"${MYSQL_ROOT_PASSWORD}" shutdown

    # 等待临时 MySQL 进程退出，忽略非 0 返回
    wait "$pid" || true

    echo "[INFO] MySQL initialized."
else
    # 已存在数据库数据目录，跳过初始化
    # 这通常表示使用了 Docker volume，例如 mysql-data:/var/lib/mysql
    echo "[INFO] Existing /var/lib/mysql detected, skip initialization."

    # 提醒：这些环境变量只在第一次初始化数据库时生效
    echo "[WARN] MYSQL_ROOT_PASSWORD / MYSQL_DATABASE / MYSQL_USER / MYSQL_PASSWORD only apply on first initialization."

    # 提醒：已有数据目录时，修改环境变量不会自动修改旧数据库用户和密码
    echo "[WARN] Existing /var/lib/mysql detected, environment variable changes will not modify existing users."
fi

# 执行容器主命令
# 例如 supervisord、mysqld_safe、apache2-foreground 等
exec "$@"
