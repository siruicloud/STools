<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * CORS 跨域中间件。
 *
 * Electron 渲染进程（file:// 协议或本地开发服务器）请求市场 API 时需要跨域支持。
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): \Webman\Http\Response
    {
        // OPTIONS 预检请求：直接返回 204
        if (strtoupper($request->method()) === 'OPTIONS') {
            $response = new Response(204);
            return $this->withCorsHeaders($response, $request);
        }

        /** @var Response $response */
        $response = $handler($request);
        return $this->withCorsHeaders($response, $request);
    }

    private function withCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->header('Origin', '*');
        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, DELETE, PATCH, HEAD, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
        $response->header('Access-Control-Max-Age', '86400');
        return $response;
    }
}
