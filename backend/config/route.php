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

use Webman\Route;
use Webman\Http\Request;
use Webman\Http\Response;
use app\controller\ApiController;
use app\controller\AuthController;
use app\controller\BannerController;
use app\controller\MarketController;
use app\controller\SyncController;
use app\middleware\AuthMiddleware;

// 健康检查
Route::get('/api/hello', [ApiController::class, 'hello']);

// ━━━ 账号认证 API ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 对应客户端：wss://z-tools.top → ws://127.0.0.1:8787（syncServerUrlToHttp 转 http）
Route::post('/api/auth', [AuthController::class, 'login']);
Route::post('/api/auth/refresh', [AuthController::class, 'refresh']);
Route::get('/api/auth/captcha-config', [AuthController::class, 'captchaConfig']);
Route::get('/api/account/profile', [AuthController::class, 'profile']);
Route::put('/api/account/nickname', [AuthController::class, 'updateNickname'])->middleware([AuthMiddleware::class]);
Route::post('/api/account/avatar', [AuthController::class, 'uploadAvatar'])->middleware([AuthMiddleware::class]);

// ━━━ 插件数据同步 API ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Route::get('/api/sync/data', [SyncController::class, 'getData'])->middleware([AuthMiddleware::class]);
Route::post('/api/sync/data', [SyncController::class, 'setData'])->middleware([AuthMiddleware::class]);
Route::delete('/api/sync/data', [SyncController::class, 'deleteData'])->middleware([AuthMiddleware::class]);

// ━━━ 动态 SVG Banner ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Route::get('/banners/{name}.svg', [BannerController::class, 'svg']);

// ━━━ CORS 预检（OPTIONS）━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Route::options('/api/hello', corsFallback());
Route::options('/api/market/{path:.+}', corsFallback());
Route::options('/api/market', corsFallback());
Route::options('/api/sync/data', corsFallback());

function corsFallback(): callable
{
    return function (Request $request) {
        $origin = $request->header('Origin', '*');
        return new Response(204, [
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, PATCH, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With',
            'Access-Control-Max-Age'       => '86400',
        ]);
    };
}

// ━━━ 插件市场 API ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 对应客户端 DEFAULT_PLUGIN_MARKET_API_BASE：http://127.0.0.1:8787/api/market
// 接口格式见 docs/plugin-market-backend-spec.md 与 docs/plugin-list-format.md
Route::group('/api/market', function () {
    // 市场聚合数据（banners / categories / latest）
    Route::get('/plugins', [MarketController::class, 'index']);
    // 推荐插件
    Route::get('/plugins/recommendations', [MarketController::class, 'recommendations']);
    // 单插件最新版本检查
    Route::get('/plugins/latest', [MarketController::class, 'latest']);
    // 插件 README
    Route::get('/plugins/readme', [MarketController::class, 'readme']);
    // 评论列表
    Route::get('/plugins/comments', [MarketController::class, 'comments']);
    // 发表评论（需认证）
    Route::post('/plugins/comments', [MarketController::class, 'createComment'])
        ->middleware([AuthMiddleware::class]);
    // 点赞/取消点赞（需认证）
    Route::post('/plugins/comments/{id}/like', [MarketController::class, 'likeComment'])
        ->middleware([AuthMiddleware::class]);
    // 删除评论（需认证，仅本人）
    Route::delete('/plugins/comments/{id}', [MarketController::class, 'deleteComment'])
        ->middleware([AuthMiddleware::class]);
    // 插件版本管理（需认证）
    Route::get('/plugins/{name}/versions', [MarketController::class, 'pluginVersions']);
    Route::post('/plugins/{name}/versions', [MarketController::class, 'createPluginVersion'])
        ->middleware([AuthMiddleware::class]);
    Route::post('/plugins/{name}/versions/{version}/default', [MarketController::class, 'setDefaultVersion'])
        ->middleware([AuthMiddleware::class]);
    Route::delete('/plugins/{name}/versions/{version}', [MarketController::class, 'deletePluginVersion'])
        ->middleware([AuthMiddleware::class]);
    Route::post('/plugins/{name}/versions/{version}/download', [MarketController::class, 'trackVersionDownload']);
});



