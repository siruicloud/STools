<?php

namespace app\controller;

use app\model\MarketDb;
use support\Request;
use support\Response;

/**
 * 插件市场 API 控制器。
 *
 * 响应格式与 ZTools 客户端 src/main/api/renderer/pluginMarket.ts 的解析逻辑完全兼容：
 * - GET /plugins                         → { banners, categories, latest }
 * - GET /plugins/recommendations         → { items }
 * - GET /plugins/latest?name=&platform=  → { available, plugin? } / { available:false, reason }
 * - GET /plugins/readme?name=            → Markdown 文本
 * - GET /plugins/comments                → { items, page }
 * - POST /plugins/comments               → 新评论对象（需认证）
 * - POST /plugins/comments/{id}/like     → { liked, likeCount }（需认证）
 * - DELETE /plugins/comments/{id}        → { success: true }（需认证）
 */
class MarketController
{
    private const MAX_LIMIT = 100;

    /**
     * GET /plugins — 市场聚合数据（首页所需全部数据）
     */
    public function index(Request $request): Response
    {
        $limit = $this->limit((string)$request->get('limit', '12'));
        $platform = (string)$request->get('platform', '');

        // 分类（含分类下插件，客户端从 categories[].plugins 收集全量插件）
        $categories = [];
        foreach (MarketDb::getCategories() as $cat) {
            $plugins = MarketDb::filterByPlatform($cat['plugins'], $platform);
            if (empty($plugins)) {
                continue;
            }
            $categories[] = [
                'id' => (int)$cat['id'],
                'title' => (string)$cat['title'],
                'description' => (string)$cat['description'],
                'logo' => (string)$cat['logo'],
                'plugins' => array_map([MarketDb::class, 'formatPlugin'], $plugins),
            ];
        }

        $latest = MarketDb::filterByPlatform(MarketDb::latestPlugins($limit), $platform);

        // banner image_url 为相对路径（如 /banners/xxx.svg）时，拼接完整 URL 供渲染进程 <img> 直接加载
        $host = (string)$request->header('Host', '');
        $proto = (string)$request->header('X-Forwarded-Proto', 'http');
        $scheme = $proto === 'https' ? 'https' : 'http';
        $base = $host !== '' ? "{$scheme}://{$host}" : '';
        $banners = array_map(
            fn(array $b) => [
                'title' => (string)$b['title'],
                'imageUrl' => $this->absoluteUrl($base, (string)$b['image_url']),
                'linkUrl' => (string)$b['link_url'],
            ],
            MarketDb::getBanners()
        );

        return json([
            'banners' => $banners,
            'categories' => $categories,
            'latest' => array_map([MarketDb::class, 'formatPlugin'], $latest),
        ]);
    }

    /**
     * GET /plugins/recommendations — 推荐插件
     */
    public function recommendations(Request $request): Response
    {
        $limit = $this->limit((string)$request->get('limit', '12'));
        $platform = (string)$request->get('platform', '');
        $items = MarketDb::filterByPlatform(MarketDb::recommendedPlugins($limit), $platform);
        return json(['items' => array_map([MarketDb::class, 'formatPlugin'], $items)]);
    }

    /**
     * GET /plugins/latest — 单个插件的最新版本检查
     */
    public function latest(Request $request): Response
    {
        $name = trim((string)$request->get('name', ''));
        $platform = (string)$request->get('platform', '');

        $plugin = MarketDb::getPluginByName($name);
        if ($plugin === null) {
            return json(['available' => false, 'reason' => 'not_found']);
        }

        if (!$this->supportsPlatform($plugin, $platform)) {
            return json(['available' => false, 'reason' => 'unsupported_platform']);
        }

        return json(['available' => true, 'plugin' => MarketDb::formatPlugin($plugin)]);
    }

    /**
     * GET /plugins/readme — 插件 README
     * 客户端 getRemotePluginReadme 期望 JSON 格式 { content }
     */
    public function readme(Request $request): Response
    {
        $name = trim((string)$request->get('name', ''));
        $plugin = MarketDb::getPluginByName($name);
        if ($plugin === null) {
            return json(['error' => '插件不存在'], 404);
        }
        return json(['content' => (string)($plugin['readme'] ?? '')]);
    }

    /**
     * GET /plugins/comments — 评论分页列表
     */
    public function comments(Request $request): Response
    {
        $pluginName = trim((string)$request->get('pluginName', ''));
        if ($pluginName === '') {
            return json(['error' => 'pluginName 不能为空'], 400);
        }

        $page = max(1, (int)$request->get('page', 1));
        $pageSize = min(self::MAX_LIMIT, max(1, (int)$request->get('pageSize', 20)));
        $anchorId = max(0, (int)$request->get('anchorId', 0));
        $uid = isset($request->user) ? (string)$request->user['uid'] : null;

        return json(MarketDb::getComments($pluginName, $page, $pageSize, $anchorId, $uid));
    }

    /**
     * POST /plugins/comments — 发表评论（需认证）
     */
    public function createComment(Request $request): Response
    {
        $body = $this->parseJsonBody($request);
        if ($body === null) {
            return json(['error' => '请求体必须是合法 JSON'], 400);
        }

        $pluginName = trim((string)($body['pluginName'] ?? ''));
        $content = trim((string)($body['content'] ?? ''));
        $parentId = isset($body['parentId']) && $body['parentId'] !== null ? (int)$body['parentId'] : null;

        if ($pluginName === '') {
            return json(['error' => 'pluginName 不能为空'], 400);
        }
        if ($content === '') {
            return json(['error' => '评论内容不能为空'], 400);
        }
        if (mb_strlen($content) > 2000) {
            return json(['error' => '评论内容不能超过 2000 字'], 400);
        }
        if ($parentId !== null) {
            $parent = MarketDb::getCommentById($parentId);
            if ($parent === null || $parent['deleted']) {
                return json(['error' => '父评论不存在'], 400);
            }
        }

        $user = $request->user;
        $comment = MarketDb::createComment(
            $pluginName,
            (string)$user['uid'],
            (string)$user['nickname'],
            (string)($user['avatar_url'] ?? ''),
            $content,
            $parentId
        );

        return json($comment, 201);
    }

    /**
     * POST /plugins/comments/{id}/like — 点赞/取消点赞（需认证）
     */
    public function likeComment(Request $request, string $id): Response
    {
        $commentId = (int)$id;
        if (MarketDb::getCommentById($commentId) === null) {
            return json(['error' => '评论不存在'], 404);
        }
        return json(MarketDb::toggleLike($commentId, (string)$request->user['uid']));
    }

    /**
     * DELETE /plugins/comments/{id} — 删除评论（需认证，仅本人）
     */
    public function deleteComment(Request $request, string $id): Response
    {
        $commentId = (int)$id;
        $deleted = MarketDb::softDeleteComment($commentId, (string)$request->user['uid']);
        if (!$deleted) {
            $comment = MarketDb::getCommentById($commentId);
            if ($comment === null) {
                return json(['error' => '评论不存在'], 404);
            }
            return json(['error' => '无权删除该评论'], 403);
        }
        return json(['success' => true]);
    }

    /**
     * GET /api/market/plugins/{name}/versions — 插件版本列表
     */
    public function pluginVersions(Request $request, string $name): Response
    {
        $plugin = MarketDb::getPluginByName($name);
        if ($plugin === null) {
            return json(['error' => '插件不存在'], 404);
        }
        $versions = array_map(
            fn(array $v) => [
                'id' => (int)$v['id'],
                'version' => (string)$v['version'],
                'downloadUrl' => (string)$v['download_url'],
                'downloadCount' => (int)$v['download_count'],
                'size' => (int)$v['size'],
                'changelog' => (string)$v['changelog'],
                'isDefault' => (int)$v['is_default'] === 1,
                'createdAt' => (int)$v['created_at'],
                'updatedAt' => (int)$v['updated_at'],
            ],
            MarketDb::getPluginVersions($name)
        );
        return json(['items' => $versions]);
    }

    /**
     * POST /api/market/plugins/{name}/versions — 上传/新增版本
     * Body: { version, downloadUrl, size?, changelog?, isDefault? }
     */
    public function createPluginVersion(Request $request, string $name): Response
    {
        $plugin = MarketDb::getPluginByName($name);
        if ($plugin === null) {
            return json(['error' => '插件不存在'], 404);
        }
        $body = $this->parseJsonBody($request);
        $version = trim((string)($body['version'] ?? ''));
        $downloadUrl = trim((string)($body['downloadUrl'] ?? ''));

        if ($version === '' || $downloadUrl === '') {
            return json(['error' => 'version 和 downloadUrl 不能为空'], 400);
        }
        if (!preg_match('/^\d+\.\d+\.\d+([-+][\w.-]+)?$/', $version)) {
            return json(['error' => '版本号格式无效，应为 x.y.z'], 400);
        }

        MarketDb::upsertPluginVersion([
            'plugin_name' => $name,
            'version' => $version,
            'download_url' => $downloadUrl,
            'size' => (int)($body['size'] ?? 0),
            'changelog' => (string)($body['changelog'] ?? ''),
            'is_default' => !empty($body['isDefault']),
        ]);

        return json(['success' => true]);
    }

    /**
     * POST /api/market/plugins/{name}/versions/{version}/default — 设为默认版本
     */
    public function setDefaultVersion(Request $request, string $name, string $version): Response
    {
        if (MarketDb::getPluginByName($name) === null) {
            return json(['error' => '插件不存在'], 404);
        }
        try {
            MarketDb::setDefaultPluginVersion($name, $version);
        } catch (\RuntimeException $e) {
            return json(['error' => $e->getMessage()], 400);
        }
        return json(['success' => true]);
    }

    /**
     * DELETE /api/market/plugins/{name}/versions/{version} — 删除版本（默认版本不可删）
     */
    public function deletePluginVersion(Request $request, string $name, string $version): Response
    {
        try {
            $deleted = MarketDb::deletePluginVersion($name, $version);
        } catch (\RuntimeException $e) {
            return json(['error' => $e->getMessage()], 400);
        }
        if (!$deleted) {
            return json(['error' => '版本不存在'], 404);
        }
        return json(['success' => true]);
    }

    /**
     * POST /api/market/plugins/{name}/versions/{version}/download — 下载计数 +1
     */
    public function trackVersionDownload(Request $request, string $name, string $version): Response
    {
        MarketDb::incrementVersionDownload($name, $version);
        return json(['success' => true]);
    }

    // ━━━ 内部工具 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    private function limit(string $value): int
    {
        return min(self::MAX_LIMIT, max(1, (int)$value));
    }

    /** 平台兼容性检查：插件未声明平台视为全平台可用 */
    private function supportsPlatform(array $plugin, string $platform): bool
    {
        if ($platform === '') {
            return true;
        }
        $raw = (string)($plugin['platform'] ?? '');
        if ($raw === '') {
            return true;
        }
        $platforms = json_decode($raw, true);
        if (!is_array($platforms)) {
            return true;
        }
        return in_array(strtolower($platform), array_map('strtolower', $platforms), true);
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

    /** 相对路径拼接为完整 URL；已是绝对地址（http/https/data:）则原样返回 */
    private function absoluteUrl(string $base, string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (preg_match('#^(https?://|data:)#i', $path) === 1) {
            return $path;
        }
        return $base !== '' ? $base . $path : $path;
    }
}
