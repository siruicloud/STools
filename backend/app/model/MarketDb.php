<?php

namespace app\model;

use PDO;
use RuntimeException;

/**
 * 插件市场数据访问层。
 *
 * 默认使用 SQLite（零额外依赖，首次连接自动建表）；
 * 生产环境可切换 MySQL：设置环境变量 DB_DRIVER=mysql 并配置连接信息，
 * 表结构通过 install.sql 创建，所有时间戳均为毫秒（与客户端 PluginMarketPlugin 约定一致）。
 */
class MarketDb
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::isMysql()
                ? self::connectMysql()
                : self::connectSqlite();
        }
        return self::$pdo;
    }

    /** 是否使用 MySQL（通过环境变量 DB_DRIVER=mysql 开启） */
    public static function isMysql(): bool
    {
        self::loadDotenv();
        return self::env('DB_DRIVER') === 'mysql';
    }

    /**
     * 兼容 CLI 场景（php data/seed.php 不走 webman bootstrap）：
     * 环境变量未设置时尝试加载项目根目录 .env。
     */
    private static function loadDotenv(): void
    {
        if (self::env('DB_DRIVER') !== null) {
            return;
        }
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!is_file($envFile)) {
            return;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key !== '' && self::env($key) === null) {
                $_ENV[$key] = $value;
                if (function_exists('putenv')) {
                    putenv("{$key}={$value}");
                }
            }
        }
    }

    /** 读取环境变量：优先 $_ENV，其次 getenv（兼容 PHP 8.2 CLI 的差异） */
    private static function env(string $key): ?string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string)$_ENV[$key];
        }
        $value = getenv($key);
        return $value === false ? null : (string)$value;
    }

    /** 当前驱动的 INSERT IGNORE 语法（SQLite: INSERT OR IGNORE / MySQL: INSERT IGNORE） */
    public static function insertIgnore(): string
    {
        return self::isMysql() ? 'INSERT IGNORE' : 'INSERT OR IGNORE';
    }

    private static function connectSqlite(): PDO
    {
        $dbPath = self::dbPath();
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA foreign_keys = ON;');
        self::ensureSchema($pdo);
        return $pdo;
    }

    private static function connectMysql(): PDO
    {
        $host = self::env('DB_HOST') ?: '127.0.0.1';
        $port = self::env('DB_PORT') ?: '3306';
        $name = self::env('DB_NAME') ?: 'api';
        $user = self::env('DB_USER') ?: 'root';
        $pass = self::env('DB_PASS') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ]);
        return $pdo;
    }

    /** 数据库文件路径：backend/data/market.db */
    public static function dbPath(): string
    {
        return dirname(__DIR__, 2) . '/data/market.db';
    }

    /** 测试环境重置连接（供 seed 脚本使用） */
    public static function reset(): void
    {
        self::$pdo = null;
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS banners (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT DEFAULT '',
  image_url TEXT NOT NULL,
  link_url TEXT DEFAULT '',
  sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT DEFAULT '',
  logo TEXT DEFAULT '',
  sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS plugins (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  version TEXT NOT NULL,
  title TEXT DEFAULT '',
  description TEXT DEFAULT '',
  logo TEXT DEFAULT '',
  author TEXT DEFAULT '',
  homepage TEXT DEFAULT '',
  size INTEGER DEFAULT 0,
  download_count INTEGER DEFAULT 0,
  updated_at INTEGER DEFAULT 0,
  published_at INTEGER DEFAULT 0,
  category_id INTEGER DEFAULT 0,
  category_title TEXT DEFAULT '',
  platform TEXT DEFAULT '',          -- JSON 数组，如 ["darwin","win32"]；空表示全平台
  readme TEXT DEFAULT '',
  is_recommended INTEGER DEFAULT 0,
  sort_order INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  plugin_name TEXT NOT NULL,
  uid TEXT NOT NULL,
  nickname TEXT NOT NULL,
  avatar_url TEXT DEFAULT '',
  parent_id INTEGER DEFAULT NULL,
  content TEXT NOT NULL,
  like_count INTEGER DEFAULT 0,
  deleted INTEGER DEFAULT 0,
  created_at INTEGER DEFAULT 0,
  updated_at INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS comment_likes (
  comment_id INTEGER NOT NULL,
  uid TEXT NOT NULL,
  created_at INTEGER DEFAULT 0,
  PRIMARY KEY (comment_id, uid)
);

CREATE TABLE IF NOT EXISTS users (
  uid TEXT PRIMARY KEY,
  nickname TEXT NOT NULL,
  avatar_url TEXT DEFAULT '',
  password TEXT DEFAULT '',
  token TEXT DEFAULT '',
  refresh_token TEXT DEFAULT '',
  created_at INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_plugins_category ON plugins(category_id);
CREATE INDEX IF NOT EXISTS idx_plugins_recommended ON plugins(is_recommended);
CREATE INDEX IF NOT EXISTS idx_comments_plugin ON comments(plugin_name);
SQL);

        // 兼容旧库：补充新增列（ALTER TABLE 不报错即成功）
        foreach (['password' => "TEXT DEFAULT ''", 'refresh_token' => "TEXT DEFAULT ''"] as $col => $def) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN {$col} {$def}");
            } catch (\Throwable) {
                // 列已存在
            }
        }
    }

    // ━━━ 插件 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /** 全量插件列表 */
    public static function allPlugins(): array
    {
        return self::connection()
            ->query('SELECT * FROM plugins ORDER BY sort_order ASC, id ASC')
            ->fetchAll();
    }

    public static function getPluginByName(string $name): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM plugins WHERE name = ? LIMIT 1')
        ;
        $stmt->execute([$name]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** 最新上架插件（按 published_at 倒序） */
    public static function latestPlugins(int $limit): array
    {
        $stmt = self::connection()->prepare(
            'SELECT * FROM plugins ORDER BY published_at DESC, id DESC LIMIT ' . max(1, (int)$limit)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** 推荐插件 */
    public static function recommendedPlugins(int $limit): array
    {
        $stmt = self::connection()->prepare(
            'SELECT * FROM plugins WHERE is_recommended = 1 ORDER BY download_count DESC, id ASC LIMIT '
            . max(1, (int)$limit)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ━━━ 分类 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /** 全部分类（含该分类下的插件列表） */
    public static function getCategories(): array
    {
        $pdo = self::connection();
        $categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
        $stmt = $pdo->prepare('SELECT * FROM plugins WHERE category_id = ? ORDER BY sort_order ASC, id ASC');

        foreach ($categories as &$category) {
            $stmt->execute([$category['id']]);
            $category['plugins'] = $stmt->fetchAll();
        }
        unset($category);
        return $categories;
    }

    public static function getBanners(): array
    {
        return self::connection()
            ->query('SELECT * FROM banners ORDER BY sort_order ASC, id ASC')
            ->fetchAll();
    }

    // ━━━ 评论 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /**
     * 分页查询评论。
     * @return array{items: array, page: array{page:int,pageSize:int,total:int}}
     */
    public static function getComments(string $pluginName, int $page = 1, int $pageSize = 20, int $anchorId = 0, ?string $uid = null): array
    {
        $pdo = self::connection();
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));

        // anchorId 定位：找到 anchor 所在位置，重新计算页码
        if ($anchorId > 0) {
            $offsetStmt = $pdo->prepare(
                'SELECT COUNT(*) AS cnt FROM comments WHERE plugin_name = ? AND id < ?'
            );
            $offsetStmt->execute([$pluginName, $anchorId]);
            $anchorOffset = (int)$offsetStmt->fetchColumn();
            $page = intdiv($anchorOffset, $pageSize) + 1;
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE plugin_name = ?');
        $countStmt->execute([$pluginName]);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $pageSize;
        $stmt = $pdo->prepare(
            'SELECT * FROM comments WHERE plugin_name = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $pluginName, PDO::PARAM_STR);
        $stmt->bindValue(2, $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[] = self::formatComment($row, $uid);
        }

        return [
            'items' => $items,
            'page' => ['page' => $page, 'pageSize' => $pageSize, 'total' => $total],
        ];
    }

    public static function getCommentById(int $id, ?string $uid = null): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM comments WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : self::formatComment($row, $uid);
    }

    public static function createComment(string $pluginName, string $uid, string $nickname, string $avatarUrl, string $content, ?int $parentId = null): array
    {
        $pdo = self::connection();
        $now = (int)round(microtime(true) * 1000);
        $stmt = $pdo->prepare(
            'INSERT INTO comments (plugin_name, uid, nickname, avatar_url, parent_id, content, like_count, deleted, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?)'
        );
        $stmt->execute([$pluginName, $uid, $nickname, $avatarUrl, $parentId, $content, $now, $now]);
        $comment = self::getCommentById((int)$pdo->lastInsertId(), $uid);
        if ($comment === null) {
            throw new RuntimeException('评论创建失败');
        }
        return $comment;
    }

    /** 软删除评论（仅本人可删，由调用方校验 uid） */
    public static function softDeleteComment(int $id, string $uid): bool
    {
        $stmt = self::connection()->prepare(
            'UPDATE comments SET deleted = 1, updated_at = ? WHERE id = ? AND uid = ?'
        );
        $stmt->execute([(int)round(microtime(true) * 1000), $id, $uid]);
        return $stmt->rowCount() > 0;
    }

    /**
     * 切换点赞状态。
     * @return array{liked: bool, likeCount: int}
     */
    public static function toggleLike(int $commentId, string $uid): array
    {
        $pdo = self::connection();
        $exists = self::hasLiked($commentId, $uid);

        if ($exists) {
            $del = $pdo->prepare('DELETE FROM comment_likes WHERE comment_id = ? AND uid = ?');
            $del->execute([$commentId, $uid]);
            $pdo->prepare('UPDATE comments SET like_count = MAX(0, like_count - 1) WHERE id = ?')->execute([$commentId]);
            $liked = false;
        } else {
            $ins = $pdo->prepare(self::insertIgnore() . ' INTO comment_likes (comment_id, uid, created_at) VALUES (?, ?, ?)');
            $ins->execute([$commentId, $uid, (int)round(microtime(true) * 1000)]);
            $pdo->prepare('UPDATE comments SET like_count = like_count + 1 WHERE id = ?')->execute([$commentId]);
            $liked = true;
        }

        $stmt = $pdo->prepare('SELECT like_count FROM comments WHERE id = ?');
        $stmt->execute([$commentId]);
        $likeCount = (int)($stmt->fetchColumn() ?: 0);

        return ['liked' => $liked, 'likeCount' => $likeCount];
    }

    public static function hasLiked(int $commentId, string $uid): bool
    {
        $stmt = self::connection()->prepare('SELECT 1 FROM comment_likes WHERE comment_id = ? AND uid = ?');
        $stmt->execute([$commentId, $uid]);
        return $stmt->fetchColumn() !== false;
    }

    // ━━━ 用户 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public static function findUserByToken(string $token): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM users WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findUserByUsername(string $username): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM users WHERE uid = ? OR nickname = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** 注册新用户，返回用户行 */
    public static function registerUser(string $username, string $password): array
    {
        $pdo = self::connection();
        $uid = 'u_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
        $stmt = $pdo->prepare(
            'INSERT INTO users (uid, nickname, avatar_url, password, token, refresh_token, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $uid,
            $username,
            '',
            $password,
            self::generateToken(),
            self::generateToken(),
            (int)round(microtime(true) * 1000),
        ]);
        return self::findUserByUsername($username);
    }

    /** 更新用户 token / refresh_token（登录时轮换） */
    public static function updateUserTokens(string $uid, string $token, string $refreshToken): void
    {
        $stmt = self::connection()->prepare(
            'UPDATE users SET token = ?, refresh_token = ? WHERE uid = ?'
        );
        $stmt->execute([$token, $refreshToken, $uid]);
    }

    /** 按 refresh_token 查找用户 */
    public static function findUserByRefreshToken(string $refreshToken): ?array
    {
        $stmt = self::connection()->prepare('SELECT * FROM users WHERE refresh_token = ? LIMIT 1');
        $stmt->execute([$refreshToken]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function createUser(string $uid, string $nickname, string $avatarUrl, string $token): void
    {
        $stmt = self::connection()->prepare(
            self::insertIgnore() . ' INTO users (uid, nickname, avatar_url, token, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$uid, $nickname, $avatarUrl, $token, (int)round(microtime(true) * 1000)]);
    }

    /** 生成随机 token（hex 40 字符） */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(20));
    }

    /** 密码哈希（bcrypt） */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /** 验证密码 */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return $hash !== '' && password_verify($password, $hash);
    }

    // ━━━ 内部工具 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    /** 将数据库行转换为客户端 PluginMarketCommentItem 结构 */
    private static function formatComment(array $row, ?string $currentUid = null): array
    {
        $parent = null;
        if (!empty($row['parent_id'])) {
            $stmt = self::connection()->prepare(
                'SELECT id, uid, nickname, avatar_url, content, deleted, created_at FROM comments WHERE id = ?'
            );
            $stmt->execute([$row['parent_id']]);
            $p = $stmt->fetch();
            if ($p !== false) {
                $parent = [
                    'id' => (int)$p['id'],
                    'uid' => (string)$p['uid'],
                    'nickname' => (string)$p['nickname'],
                    'avatarUrl' => (string)$p['avatar_url'],
                    'content' => (string)$p['content'],
                    'deleted' => (bool)$p['deleted'],
                    'createdAt' => (int)$p['created_at'],
                ];
            }
        }

        return [
            'id' => (int)$row['id'],
            'pluginName' => (string)$row['plugin_name'],
            'uid' => (string)$row['uid'],
            'nickname' => (string)$row['nickname'],
            'avatarUrl' => (string)$row['avatar_url'],
            'parentId' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
            'parent' => $parent,
            'content' => (string)$row['content'],
            'likeCount' => (int)$row['like_count'],
            'liked' => $currentUid !== null && self::hasLiked((int)$row['id'], $currentUid),
            'deleted' => (bool)$row['deleted'],
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ];
    }

    /** 数据库行 → 客户端 PluginMarketPlugin 结构 */
    public static function formatPlugin(array $row): array
    {
        return [
            'name' => (string)$row['name'],
            'version' => (string)$row['version'],
            'title' => (string)$row['title'],
            'description' => (string)$row['description'],
            'logo' => (string)$row['logo'],
            'author' => (string)$row['author'],
            'homepage' => (string)$row['homepage'],
            'size' => (int)$row['size'],
            'downloadCount' => (int)$row['download_count'],
            'updatedAt' => (int)$row['updated_at'],
            'publishedAt' => (int)$row['published_at'],
            'categoryId' => (int)$row['category_id'],
            'categoryTitle' => (string)$row['category_title'],
        ];
    }

    /** 按平台过滤插件列表；插件未声明 platform 视为全平台可用 */
    public static function filterByPlatform(array $plugins, ?string $platform): array
    {
        if (!$platform) {
            return $plugins;
        }
        $platform = strtolower($platform);
        return array_values(array_filter($plugins, function (array $p) use ($platform) {
            $raw = (string)($p['platform'] ?? '');
            if ($raw === '') {
                return true;
            }
            $platforms = json_decode($raw, true);
            if (!is_array($platforms)) {
                return true;
            }
            return in_array($platform, array_map('strtolower', $platforms), true);
        }));
    }
}
