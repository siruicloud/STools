<?php

namespace app\controller;

use app\model\MarketDb;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 账号认证 API 控制器。
 *
 * 与 ZTools 客户端 src/main/api/renderer/sync.ts 的请求格式兼容：
 * - POST /api/auth             登录/注册（{ username, password, captchaVerifyParam? } → { token, refreshToken, isNew }）
 * - POST /api/auth/refresh     刷新令牌（{ refreshToken } → { token, refreshToken }）
 * - GET  /api/auth/captcha-config  验证码配置（私有部署关闭验证码）
 * - GET  /api/account/profile  用户资料（Bearer token 认证）
 */
class AuthController
{
    /**
     * POST /api/auth — 登录或注册
     */
    public function login(Request $request): Response
    {
        $body = $this->parseJsonBody($request);
        if ($body === null) {
            return json(['error' => '请求体必须是合法 JSON'], 400);
        }

        $username = trim((string)($body['username'] ?? ''));
        $password = (string)($body['password'] ?? '');

        if ($username === '' || $password === '') {
            return json(['error' => '用户名和密码不能为空'], 400);
        }
        if (mb_strlen($username) > 64 || mb_strlen($password) > 128) {
            return json(['error' => '用户名或密码过长'], 400);
        }

        $user = MarketDb::findUserByUsername($username);
        $isNew = false;

        if ($user === null) {
            // 自动注册
            $user = MarketDb::registerUser($username, MarketDb::hashPassword($password));
            $isNew = true;
        } elseif (!MarketDb::verifyPassword($password, (string)($user['password'] ?? ''))) {
            return json(['error' => '用户名或密码错误'], 401);
        }

        $token = MarketDb::generateToken();
        $refreshToken = MarketDb::generateToken();
        MarketDb::updateUserTokens((string)$user['uid'], $token, $refreshToken);

        return json([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'isNew' => $isNew,
            'username' => $username,
        ]);
    }

    /**
     * POST /api/auth/refresh — 刷新令牌
     */
    public function refresh(Request $request): Response
    {
        $body = $this->parseJsonBody($request);
        $refreshToken = trim((string)($body['refreshToken'] ?? ''));

        if ($refreshToken === '') {
            return json(['error' => 'refreshToken 不能为空'], 400);
        }

        $user = MarketDb::findUserByRefreshToken($refreshToken);
        if ($user === null) {
            return json(['error' => 'refreshToken 无效'], 401);
        }

        $token = MarketDb::generateToken();
        $newRefreshToken = MarketDb::generateToken();
        MarketDb::updateUserTokens((string)$user['uid'], $token, $newRefreshToken);

        return json([
            'token' => $token,
            'refreshToken' => $newRefreshToken,
        ]);
    }

    /**
     * GET /api/auth/captcha-config — 验证码配置（私有部署关闭验证码）
     */
    public function captchaConfig(Request $request): Response
    {
        return json([
            'success' => true,
            'config' => ['enabled' => false],
        ]);
    }

    /**
     * GET /api/account/profile — 用户资料（Bearer token）
     */
    public function profile(Request $request): Response
    {
        $user = $this->authUser($request);
        if ($user === null) {
            return json(['error' => '未登录'], 401);
        }
        return json([
            'success' => true,
            'profile' => [
                'uid' => (string)$user['uid'],
                'nickname' => (string)$user['nickname'],
                'avatarUrl' => (string)($user['avatar_url'] ?? ''),
            ],
        ]);
    }

    // ━━━ 内部工具 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function authUser(Request $request): ?array
    {
        $header = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return null;
        }
        return MarketDb::findUserByToken(trim($m[1]));
    }

    private function parseJsonBody(Request $request): ?array
    {
        $raw = $request->rawBody();
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
