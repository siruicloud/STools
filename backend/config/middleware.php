<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use app\middleware\CorsMiddleware;
use app\middleware\RateLimitMiddleware;

// 全局中间件：CORS 跨域支持（Electron 渲染进程请求市场 API 必需）
return [
    '' => [
        CorsMiddleware::class,
        RateLimitMiddleware::class,
    ],
];