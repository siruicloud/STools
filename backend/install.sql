-- ============================================================
-- seaman plugin market MySQL install script
-- MySQL 5.7+ / 8.0+ (utf8mb4 required for Chinese)
--
-- Usage in Navicat:
--   1. Open connection, create database first (or run step 1 only)
--   2. Run this script in query window / import wizard
--   3. If database "api" already selected, skip step 1
-- ============================================================

-- STEP 1: create database (run once; skip if already created)
CREATE DATABASE IF NOT EXISTS `api`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `api`;

-- STEP 2: banners (carousel)
CREATE TABLE IF NOT EXISTS `banners` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(100) NOT NULL DEFAULT '',
  `image_url`  VARCHAR(500) NOT NULL DEFAULT '',
  `link_url`   VARCHAR(500) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_banners_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='banners';

-- STEP 3: categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(100) NOT NULL,
  `description` VARCHAR(500) NOT NULL DEFAULT '',
  `logo`        VARCHAR(500) NOT NULL DEFAULT '',
  `sort_order`  INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_categories_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='categories';

-- STEP 4: plugins
CREATE TABLE IF NOT EXISTS `plugins` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(100) NOT NULL COMMENT 'plugin unique name',
  `version`         VARCHAR(30)  NOT NULL COMMENT 'semver',
  `title`           VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'display name',
  `description`     TEXT         NOT NULL COMMENT 'description',
  `logo`            VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'logo url or data uri',
  `author`          VARCHAR(100) NOT NULL DEFAULT '',
  `homepage`        VARCHAR(500) NOT NULL DEFAULT '',
  `size`            BIGINT       NOT NULL DEFAULT 0 COMMENT 'size in bytes',
  `download_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'download count',
  `updated_at`      BIGINT       NOT NULL DEFAULT 0 COMMENT 'update ts ms',
  `published_at`    BIGINT       NOT NULL DEFAULT 0 COMMENT 'publish ts ms',
  `category_id`     INT UNSIGNED NOT NULL DEFAULT 0,
  `category_title`  VARCHAR(100) NOT NULL DEFAULT '',
  `platform`        VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'json array, empty = all',
  `readme`          MEDIUMTEXT   NOT NULL COMMENT 'readme markdown',
  `is_recommended`  TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`      INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plugins_name` (`name`),
  KEY `idx_plugins_category` (`category_id`),
  KEY `idx_plugins_recommended` (`is_recommended`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='plugins';

-- STEP 5: comments
CREATE TABLE IF NOT EXISTS `comments` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plugin_name` VARCHAR(100) NOT NULL,
  `uid`         VARCHAR(100) NOT NULL,
  `nickname`    VARCHAR(100) NOT NULL,
  `avatar_url`  VARCHAR(500) NOT NULL DEFAULT '',
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `content`     VARCHAR(2000) NOT NULL,
  `like_count`  INT NOT NULL DEFAULT 0,
  `deleted`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  BIGINT NOT NULL DEFAULT 0,
  `updated_at`  BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_comments_plugin` (`plugin_name`),
  KEY `idx_comments_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='comments';

-- STEP 6: comment likes
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `comment_id` INT UNSIGNED NOT NULL,
  `uid`        VARCHAR(100) NOT NULL,
  `created_at` BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`comment_id`, `uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='comment likes';

-- STEP 7: users
CREATE TABLE IF NOT EXISTS `users` (
  `uid`           VARCHAR(100) NOT NULL COMMENT 'user unique id',
  `nickname`      VARCHAR(100) NOT NULL,
  `avatar_url`    VARCHAR(500) NOT NULL DEFAULT '',
  `password`      VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'bcrypt hash',
  `token`         VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'access token',
  `refresh_token` VARCHAR(100) NOT NULL DEFAULT '',
  `created_at`    BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  KEY `idx_users_token` (`token`),
  KEY `idx_users_refresh_token` (`refresh_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='users';

-- DONE. Next: configure backend/.env (DB_DRIVER=mysql ...) then run:
--   php data/seed.php
