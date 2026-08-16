<?php

namespace app\middleware;

use app\model\RateLimitStore;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 接口限流中间件（按 IP + 路径分组，固定窗口计数）。
 *
 * 三档规则（通过 .env 配置）：
 *   login：登录/注册接口（POST /api/auth）          —— 最严格
 *   write：评论/点赞/删除评论等写操作               —— 严格
 *   read ：市场列表/banner/README 等读操作          —— 宽松
 *
 * 响应超限时返回 429 + JSON（CORS 头由全局 CorsMiddleware 补全）。
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private const GROUP_LOGIN = 'login';
    private const GROUP_WRITE = 'write';
    private const GROUP_READ = 'read';

    private static array $envCache = [];

    public function __construct()
    {
        // webman bootstrap 可能因 opcache 未正确加载 dotenv，此处兜底读取 .env
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                self::$envCache[trim($k)] = trim($v);
            }
        }
    }

    public function process(Request $request, callable $handler): Response
    {
        if (!filter_var($this->env('RATE_LIMIT_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            return $handler($request);
        }

        $group = $this->classify($request);
        if ($group === null) {
            return $handler($request);
        }

        [$limit, $window] = $this->limitsFor($group);
        if ($limit <= 0) {
            return $handler($request);
        }

        $ip = $this->clientIp($request);
        $count = RateLimitStore::increment($group, $ip, $window);

        if ($count > $limit) {
            $retryAfter = $window;
            return new Response(429, [
                'Content-Type' => 'application/json; charset=utf-8',
                'Retry-After' => (string)$retryAfter,
            ], json_encode(['error' => '请求过于频繁，请稍后再试'], JSON_UNESCAPED_UNICODE));
        }

        return $handler($request);
    }

    /** 根据请求方法与路径归类；不匹配则返回 null（不限流） */
    private function classify(Request $request): ?string
    {
        $method = strtoupper($request->method());
        $path = $request->path();

        // 登录/注册
        if ($method === 'POST' && $path === '/api/auth') {
            return self::GROUP_LOGIN;
        }
        // 写操作：评论、点赞、删除
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && str_starts_with($path, '/api/market/')
        ) {
            return self::GROUP_WRITE;
        }
        // 读操作
        if ($method === 'GET') {
            return self::GROUP_READ;
        }
        return null;
    }

    /** @return array{0:int,1:int} [限制次数, 窗口秒数] */
    private function limitsFor(string $group): array
    {
        $prefix = 'RATE_LIMIT_' . strtoupper($group);
        $max = (int)$this->env($prefix . '_MAX', '0');
        $window = (int)$this->env($prefix . '_WINDOW', '60');
        return [$max, max(1, $window)];
    }

    private function clientIp(Request $request): string
    {
        $ip = $request->getRealIp();
        return $ip !== '' ? $ip : 'unknown';
    }

    private function env(string $key, string $default = ''): string
    {
        if (isset(self::$envCache[$key])) {
            return self::$envCache[$key];
        }
        if (isset($_ENV[$key])) {
            return (string)$_ENV[$key];
        }
        $value = getenv($key);
        return $value === false ? $default : (string)$value;
    }
}
