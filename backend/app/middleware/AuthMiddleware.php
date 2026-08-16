<?php

namespace app\middleware;

use app\model\MarketDb;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * Bearer Token 认证中间件。
 *
 * 从 Authorization: Bearer <token> 解析令牌并查询用户，
 * 通过后将用户信息挂载到 $request->user 供控制器使用。
 * 认证失败返回 401（客户端 PluginMarketAuthRequiredError 约定 "需要登录后操作"）。
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): \Webman\Http\Response
    {
        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return new Response(401, ['Content-Type' => 'application/json; charset=utf-8'], json_encode(['error' => '需要登录后操作'], JSON_UNESCAPED_UNICODE));
        }

        $user = MarketDb::findUserByToken($token);
        if ($user === null) {
            return new Response(401, ['Content-Type' => 'application/json; charset=utf-8'], json_encode(['error' => '需要登录后操作'], JSON_UNESCAPED_UNICODE));
        }

        $request->user = $user;
        return $handler($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return null;
        }
        $token = trim($m[1]);
        return $token === '' ? null : $token;
    }
}
