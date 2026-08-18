<?php

namespace app\controller;

use app\model\MarketDb;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 插件数据同步 API 控制器。
 *
 * 提供用户插件数据的云端同步功能：
 * - GET  /api/sync/data      获取用户所有同步数据
 * - POST /api/sync/data      上传/更新同步数据
 * - DELETE /api/sync/data    删除指定同步数据
 */
class SyncController
{
    /**
     * GET /api/sync/data — 获取用户所有同步数据
     */
    public function getData(Request $request): Response
    {
        $user = $this->authUser($request);
        if ($user === null) {
            return json(['error' => '未登录'], 401);
        }

        $data = MarketDb::getUserSyncData((string)$user['uid']);
        return json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/sync/data — 上传/更新同步数据
     * Body: { items: [{ key: string, value: any }] }
     */
    public function setData(Request $request): Response
    {
        $user = $this->authUser($request);
        if ($user === null) {
            return json(['error' => '未登录'], 401);
        }

        $body = $this->parseJsonBody($request);
        if ($body === null) {
            return json(['error' => '请求体必须是合法 JSON'], 400);
        }

        $items = $body['items'] ?? [];
        if (!is_array($items) || empty($items)) {
            return json(['error' => 'items 必须是数组且不能为空'], 400);
        }

        $syncedCount = 0;
        foreach ($items as $item) {
            if (!isset($item['key']) || !is_string($item['key'])) {
                continue;
            }
            $key = $item['key'];
            $value = $item['value'] ?? null;

            if (strlen($key) > 255) {
                continue;
            }

            MarketDb::setUserSyncData(
                (string)$user['uid'],
                $key,
                $value,
            );
            $syncedCount++;
        }

        return json([
            'success' => true,
            'syncedCount' => $syncedCount,
        ]);
    }

    /**
     * DELETE /api/sync/data — 删除指定同步数据
     * Body: { keys: string[] }
     */
    public function deleteData(Request $request): Response
    {
        $user = $this->authUser($request);
        if ($user === null) {
            return json(['error' => '未登录'], 401);
        }

        $body = $this->parseJsonBody($request);
        if ($body === null) {
            return json(['error' => '请求体必须是合法 JSON'], 400);
        }

        $keys = $body['keys'] ?? [];
        if (!is_array($keys) || empty($keys)) {
            return json(['error' => 'keys 必须是数组且不能为空'], 400);
        }

        $deletedCount = 0;
        foreach ($keys as $key) {
            if (is_string($key) && strlen($key) <= 255) {
                MarketDb::deleteUserSyncData((string)$user['uid'], $key);
                $deletedCount++;
            }
        }

        return json([
            'success' => true,
            'deletedCount' => $deletedCount,
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
