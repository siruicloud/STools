<?php

namespace app\model;

use PDO;

/**
 * 限流计数器存储（基于现有数据库连接，SQLite/MySQL 通用）。
 *
 * 固定窗口计数：key = {group}:{ip}:{window}，窗口变化时自动重置。
 * 表结构首次使用时自动创建（幂等）。
 */
class RateLimitStore
{
    /** 记录当前窗口内某分组的请求数；返回累加后的计数 */
    public static function increment(string $group, string $ip, int $windowSeconds): int
    {
        $pdo = MarketDb::connection();
        self::ensureTable($pdo);

        $now = time();
        $window = intdiv($now, max(1, $windowSeconds));
        $key = "{$group}:{$ip}:{$window}";
        $expiresAt = $now + max(60, $windowSeconds * 2);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT count FROM rate_limits WHERE rkey = ? FOR UPDATE'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();

            if ($row === false) {
                $stmt = $pdo->prepare(
                    'INSERT INTO rate_limits (rkey, count, expires_at, updated_at) VALUES (?, 1, ?, ?)'
                );
                $stmt->execute([$key, $expiresAt, $now]);
                $count = 1;
            } else {
                $count = (int)$row['count'] + 1;
                $stmt = $pdo->prepare(
                    'UPDATE rate_limits SET count = ?, expires_at = ?, updated_at = ? WHERE rkey = ?'
                );
                $stmt->execute([$count, $expiresAt, $now, $key]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // 概率清理过期记录（1%），避免表无限膨胀
        if (random_int(1, 100) === 1) {
            self::cleanup($pdo);
        }

        return $count;
    }

    private static function ensureTable(PDO $pdo): void
    {
        if (MarketDb::isMysql()) {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS `rate_limits` (
                  `rkey` VARCHAR(255) NOT NULL,
                  `count` INT UNSIGNED NOT NULL DEFAULT 0,
                  `expires_at` BIGINT NOT NULL DEFAULT 0,
                  `updated_at` BIGINT NOT NULL DEFAULT 0,
                  PRIMARY KEY (`rkey`),
                  KEY `idx_rate_limits_expires` (`expires_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        } else {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS rate_limits (
                  rkey TEXT PRIMARY KEY,
                  count INTEGER NOT NULL DEFAULT 0,
                  expires_at INTEGER NOT NULL DEFAULT 0,
                  updated_at INTEGER NOT NULL DEFAULT 0
                )'
            );
        }
    }

    private static function cleanup(PDO $pdo): void
    {
        $stmt = $pdo->prepare('DELETE FROM rate_limits WHERE expires_at < ?');
        $stmt->execute([time()]);
    }
}
