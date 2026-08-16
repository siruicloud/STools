<?php

/**
 * 插件市场种子数据脚本（幂等，可重复执行）。
 *
 * 用法：php data/seed.php
 * 生成约 40 个插件、5 个分类、10 个用户、每插件 3-8 条评论。
 */

use app\model\MarketDb;

require __DIR__ . '/../vendor/autoload.php';

$db = MarketDb::connection();
$now = (int)round(microtime(true) * 1000);
$day = 86400000;

// ━━━ 分类 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$categories = [
    ['id' => 1, 'title' => '效率工具', 'description' => '提升日常工作效率的实用工具'],
    ['id' => 2, 'title' => '开发工具', 'description' => '面向开发者的编码辅助工具'],
    ['id' => 3, 'title' => '设计工具', 'description' => '设计与视觉相关工具'],
    ['id' => 4, 'title' => '系统工具', 'description' => '系统增强与监控工具'],
    ['id' => 5, 'title' => 'AI 工具', 'description' => '集成大模型能力的智能插件'],
];

$stmt = $db->prepare(
    MarketDb::insertIgnore() . ' INTO categories (id, title, description, logo, sort_order) VALUES (?, ?, ?, ?, ?)'
);
foreach ($categories as $i => $cat) {
    $stmt->execute([$cat['id'], $cat['title'], $cat['description'], '', $i]);
}

// ━━━ 用户池（10 个测试账号）━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$users = [
    ['uid' => 'u_admin', 'nickname' => 'Zing', 'token' => 'test-token-admin', 'password' => 'test123456'],
    ['uid' => 'u_alice', 'nickname' => 'Alice', 'token' => 'test-token-alice', 'password' => 'test123456'],
    ['uid' => 'u_bob', 'nickname' => 'Bob', 'token' => 'test-token-bob', 'password' => 'test123456'],
    ['uid' => 'u_carol', 'nickname' => 'Carol', 'token' => 'test-token-carol', 'password' => 'test123456'],
    ['uid' => 'u_dave', 'nickname' => 'Dave', 'token' => 'test-token-dave', 'password' => 'test123456'],
    ['uid' => 'u_eve', 'nickname' => 'Eve', 'token' => 'test-token-eve', 'password' => 'test123456'],
    ['uid' => 'u_frank', 'nickname' => 'Frank', 'token' => 'test-token-frank', 'password' => 'test123456'],
    ['uid' => 'u_grace', 'nickname' => 'Grace', 'token' => 'test-token-grace', 'password' => 'test123456'],
    ['uid' => 'u_henry', 'nickname' => 'Henry', 'token' => 'test-token-henry', 'password' => 'test123456'],
    ['uid' => 'u_ivy', 'nickname' => 'Ivy', 'token' => 'test-token-ivy', 'password' => 'test123456'],
];
foreach ($users as $u) {
    MarketDb::createUser($u['uid'], $u['nickname'], '', $u['token']);
    $user = MarketDb::findUserByUsername($u['nickname']);
    if ($user !== null && empty($user['password'])) {
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE uid = ?');
        $stmt->execute([MarketDb::hashPassword($u['password']), $u['uid']]);
    }
}

// ━━━ 插件数据（40 个）━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// 字段：name, version, title, description, author, size, downloadCount, categoryId, categoryTitle,
//       platform, isRecommended, sortOrder, daysAgo(发布于N天前)
$plugins = [
    // ── 效率工具 (1) ──
    ['morse-code', '1.0.0', '莫斯密码', '莫斯密码加解密工具，支持加密、解密、对照表查看，一键复制结果。', 'Zing', 51200, 1520, 1, '效率工具', '["darwin","win32"]', 1, 1, 5],
    ['clipboard-manager', '2.0.0', '剪贴板增强', '剪贴板历史记录、搜索、图片预览、跨设备同步，支持富文本和文件。', 'Productivity', 153600, 21000, 1, '效率工具', '["darwin","win32"]', 1, 2, 120],
    ['markdown-preview', '2.3.0', 'Markdown 预览', '实时 Markdown 预览、导出 PDF/HTML、支持数学公式和流程图。', 'WriterPro', 102400, 18000, 1, '效率工具', '["darwin","win32"]', 0, 3, 250],
    ['batch-rename', '1.2.0', '批量重命名', '文件批量重命名，支持序号、日期、正则替换、大小写转换等规则。', 'FileTools', 38400, 9800, 1, '效率工具', '["darwin","win32"]', 1, 4, 45],
    ['qrcode-generator', '2.1.0', '二维码生成', '文本、网址、WiFi、名片一键生成二维码，支持自定义颜色和 Logo。', 'QRStudio', 25600, 15000, 1, '效率工具', '["darwin","win32","linux"]', 0, 5, 80],
    ['pomodoro', '1.0.3', '番茄钟', '番茄工作法计时器，自定义时长、休息提醒、专注统计。', 'FocusLab', 20480, 6200, 1, '效率工具', '["darwin","win32"]', 0, 6, 30],
    ['timestamp-converter', '1.4.0', '时间戳转换', 'Unix 时间戳与日期互转，支持毫秒、秒、多时区显示。', 'DevUtils', 15360, 24000, 1, '效率工具', '["darwin","win32","linux"]', 0, 7, 200],
    ['base-converter', '1.1.0', '进制转换', '2/8/10/16 进制互转，支持浮点数、大数、负数补码。', 'MathTools', 12800, 7800, 1, '效率工具', '["darwin","win32"]', 0, 8, 60],
    ['todo-list', '1.3.0', '待办清单', '轻量待办管理，支持优先级、截止日期、分类标签、番茄钟联动。', 'PlanBox', 30720, 8900, 1, '效率工具', '["darwin","win32"]', 0, 9, 25],
    ['file-organizer', '1.0.5', '文件整理', '按规则自动整理桌面和下载目录，按类型/日期/关键词分类归档。', 'CleanDesk', 40960, 5100, 1, '效率工具', '["darwin","win32"]', 0, 10, 15],

    // ── 开发工具 (2) ──
    ['json-formatter', '2.1.0', 'JSON 格式化', 'JSON 格式化、压缩、校验工具，支持树形视图和语法高亮，一键转换 XML/YAML。', 'DevTeam', 128000, 8900, 2, '开发工具', '["darwin","win32"]', 1, 1, 30],
    ['regex-helper', '1.0.5', '正则助手', '正则表达式编写、测试、解释工具，内置常用表达式库，支持高亮匹配。', 'CodeMaster', 81920, 4200, 2, '开发工具', '["darwin","win32"]', 0, 2, 200],
    ['http-debugger', '1.6.0', 'HTTP 调试', 'HTTP 请求调试工具，支持 GET/POST/PUT/DELETE、请求头、参数、Cookie 管理。', 'NetLab', 65536, 13000, 2, '开发工具', '["darwin","win32"]', 1, 3, 90],
    ['api-doc', '1.2.0', 'API 文档', '接口文档浏览与搜索，支持 OpenAPI/Swagger 导入，离线阅读。', 'ApiPilot', 51200, 4300, 2, '开发工具', '["darwin","win32","linux"]', 0, 4, 40],
    ['git-helper', '1.1.0', 'Git 助手', '常用 Git 操作快捷入口，提交信息模板、分支管理、状态一目了然。', 'DevOps', 35840, 6700, 2, '开发工具', '["darwin","win32"]', 0, 5, 55],
    ['code-snippets', '2.0.1', '代码片段', '代码片段管理，支持多语言、语法高亮、标签分类、快捷插入。', 'SnippetHub', 28672, 5400, 2, '开发工具', '["darwin","win32","linux"]', 0, 6, 75],
    ['base64-tool', '1.0.2', 'Base64 编解码', '文本/文件 Base64 编码解码，支持 URL-safe、HEX、ASCII 模式。', 'CryptoKit', 10240, 11000, 2, '开发工具', '["darwin","win32"]', 0, 7, 150],
    ['uuid-generator', '1.0.0', 'UUID 生成', '批量生成 UUID v4/v7，支持大写、无连字符、NIL 模式，一键复制。', 'DevUtils', 8192, 16000, 2, '开发工具', '["darwin","win32","linux"]', 0, 8, 180],
    ['sql-formatter', '1.3.0', 'SQL 格式化', 'SQL 语句格式化、美化、压缩，支持 MySQL/PostgreSQL/SQLite 方言。', 'DataWorks', 32768, 4900, 2, '开发工具', '["darwin","win32"]', 0, 9, 65],

    // ── 设计工具 (3) ──
    ['color-picker-pro', '1.5.2', '取色器 Pro', '屏幕取色、颜色转换、调色板管理，支持 HEX/RGB/HSL 格式，历史颜色记录。', 'DesignLab', 76800, 5600, 3, '设计工具', '["darwin","win32"]', 0, 1, 60],
    ['wallpaper-switcher', '1.2.0', '壁纸切换', '自动轮换桌面壁纸，支持本地目录、在线图源、定时切换。', 'Visuals', 24576, 3400, 3, '设计工具', '["darwin","win32"]', 0, 2, 35],
    ['icon-generator', '1.0.4', '图标生成', '根据文本或图片快速生成应用图标，支持多种尺寸导出。', 'IconStudio', 38912, 5200, 3, '设计工具', '["darwin","win32"]', 0, 3, 28],
    ['font-preview', '1.1.0', '字体预览', '快速预览系统字体，支持自定义文本、字号、字重对比。', 'TypeFoundry', 18432, 2900, 3, '设计工具', '["darwin","win32"]', 0, 4, 20],
    ['image-compressor', '2.2.0', '图片压缩', '批量压缩图片，支持 PNG/JPG/WebP，可自定义质量与尺寸。', 'PixelLab', 45056, 8200, 3, '设计工具', '["darwin","win32","linux"]', 0, 5, 95],
    ['gradient-generator', '1.0.1', '渐变生成器', '渐变配色生成器，预设经典渐变，支持 CSS 代码一键复制。', 'ColorWorks', 14336, 6100, 3, '设计工具', '["darwin","win32"]', 0, 6, 50],
    ['svg-editor', '1.0.3', 'SVG 编辑器', 'SVG 矢量图编辑预览，支持路径简化、色彩调整、代码导出。', 'VectorLab', 60416, 2700, 3, '设计工具', '["darwin","win32"]', 0, 7, 18],

    // ── 系统工具 (4) ──
    ['system-monitor', '3.0.1', '系统监控', 'CPU、内存、磁盘、网络实时监控，支持悬浮窗和告警，历史数据导出。', 'SysAdmin', 204800, 12000, 4, '系统工具', '["darwin","win32"]', 0, 1, 90],
    ['process-manager', '1.4.0', '进程管理', '查看和结束系统进程，支持按 CPU/内存排序、进程详情、强制退出。', 'SysAdmin', 51200, 7300, 4, '系统工具', '["darwin","win32"]', 0, 2, 70],
    ['disk-cleaner', '1.1.0', '磁盘清理', '扫描并清理缓存、临时文件、大文件，释放磁盘空间，安全预览。', 'StoragePro', 58368, 9500, 4, '系统工具', '["darwin","win32"]', 1, 3, 42],
    ['window-manager', '1.2.1', '窗口管理', '窗口置顶、透明度调节、快速排列布局，多显示器支持。', 'WinTools', 34816, 4800, 4, '系统工具', '["darwin","win32"]', 0, 4, 33],
    ['screen-recorder', '2.0.0', '屏幕录制', '屏幕区域录制为 GIF/视频，支持光标高亮、麦克风混音。', 'CaptureOne', 90112, 6800, 4, '系统工具', '["darwin","win32"]', 0, 5, 58],
    ['battery-monitor', '1.0.2', '电池管理', '电池健康度、循环次数、充放电状态监控，低电量提醒。', 'PowerWatch', 21504, 3800, 4, '系统工具', '["darwin"]', 0, 6, 26],

    // ── AI 工具 (5) ──
    ['ai-translator', '1.2.0', 'AI 翻译', '基于大模型的智能翻译，支持多语言、上下文理解、专业术语，一键复制。', 'AI Lab', 307200, 34000, 5, 'AI 工具', '["darwin","win32","linux"]', 1, 1, 150],
    ['ai-writer', '1.0.4', 'AI 写作', 'AI 辅助写作，支持续写、改写、润色、扩写，多种风格模板。', 'AI Lab', 256000, 12800, 5, 'AI 工具', '["darwin","win32"]', 1, 2, 22],
    ['ai-chat', '2.1.0', 'AI 对话', '多模型 AI 对话助手，支持上下文记忆、历史会话、Markdown 渲染。', 'NexusAI', 384000, 25600, 5, 'AI 工具', '["darwin","win32","linux"]', 1, 3, 12],
    ['ai-summarizer', '1.0.1', 'AI 总结', '长文自动总结要点，支持网页、文档、PDF，生成摘要与关键词。', 'NexusAI', 204800, 8900, 5, 'AI 工具', '["darwin","win32"]', 0, 4, 8],
    ['ai-image-generator', '1.1.0', 'AI 图片生成', '文字描述生成图片，支持多种风格，尺寸自定义，历史作品管理。', 'ArtMind', 409600, 9800, 5, 'AI 工具', '["darwin","win32"]', 0, 5, 16],
    ['ai-code-completion', '1.0.0', 'AI 代码补全', '基于大模型的代码补全与生成，支持主流语言，离线模型可选。', 'CodeGenius', 512000, 15600, 5, 'AI 工具', '["darwin","win32","linux"]', 0, 6, 6],
];

$stmt = $db->prepare(
    MarketDb::insertIgnore() . ' INTO plugins
     (name, version, title, description, logo, author, homepage, size, download_count, updated_at, published_at,
      category_id, category_title, platform, readme, is_recommended, sort_order)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
foreach ($plugins as $p) {
    [$name, $version, $title, $desc, $author, $size, $download, $catId, $catTitle, $platform, $rec, $sort, $daysAgo] = $p;
    $published = $now - $day * $daysAgo;
    $updated = $now - $day * max(1, (int)($daysAgo * 0.3));
    $platforms = implode(' / ', json_decode($platform, true));
    $readme = "# {$title}\n\n{$desc}\n\n## 使用说明\n\n- 在搜索框输入「{$title}」或相关关键词唤起\n- 支持快捷键操作与右键菜单\n\n## 平台支持\n\n- {$platforms}\n\n## 关于\n\n作者：{$author}  |  版本：{$version}";
    $stmt->execute([
        $name, $version, $title, $desc, '', $author, "https://github.com/example/{$name}",
        $size, $download, $updated, $published,
        $catId, $catTitle, $platform, $readme, $rec, $sort,
    ]);
}

// ━━━ 评论（每插件 3-8 条，幂等）━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
$commentTemplates = [
    '非常好用的工具，强烈推荐！',
    '界面简洁，响应速度快，体验很好。',
    '功能很实用，已经加入日常使用清单。',
    '更新很勤快，作者用心了。',
    '第一次用就爱上了，赞一个！',
    '功能全面，就是希望能支持更多快捷键。',
    '解决了我的大问题，效率提升明显。',
    '稳定流畅，几乎没有遇到 bug。',
    '设计很精致，细节到位。',
    '希望增加云同步功能，换设备太麻烦了。',
    '用了两周，非常满意。',
    '文档清晰，上手零门槛。',
    '对比同类工具，这个最顺手。',
    '有个小建议：如果能支持自定义主题就更好了。',
    '免费且强大，必须五星好评。',
];

$getCount = $db->prepare('SELECT COUNT(*) FROM comments WHERE plugin_name = ?');
$getPlugin = $db->prepare('SELECT name FROM plugins');
$getPlugin->execute();
$pluginNames = $getPlugin->fetchAll(PDO::FETCH_COLUMN);

$batch = [];

foreach ($pluginNames as $name) {
    $getCount->execute([$name]);
    $existing = (int)$getCount->fetchColumn();
    $target = min(8, max(3, (strlen($name) % 6) + 3));
    if ($existing >= $target) {
        continue;
    }
    mt_srand(crc32($name));
    $used = [];
    $added = 0;
    $toAdd = $target - $existing;
    while ($added < $toAdd && count($used) < count($commentTemplates)) {
        $content = $commentTemplates[mt_rand(0, count($commentTemplates) - 1)];
        if (isset($used[$content])) {
            continue;
        }
        $used[$content] = true;
        $user = $users[mt_rand(0, count($users) - 1)];
        $daysAgo = mt_rand(0, 20);
        $ts = $now - $day * $daysAgo - mt_rand(0, 3600000);
        $likes = mt_rand(0, 25);
        $batch[] = [$name, $user['uid'], $user['nickname'], '', $content, $likes, $ts, $ts];
        $added++;
    }
}
mt_srand();

// 批量插入评论（单条 SQL 多 VALUES，减少远程数据库网络往返）
if (!empty($batch)) {
    $values = [];
    $params = [];
    foreach ($batch as $row) {
        $values[] = '(?, ?, ?, ?, NULL, ?, ?, 0, ?, ?)';
        foreach ($row as $v) {
            $params[] = $v;
        }
    }
    $sql = 'INSERT INTO comments (plugin_name, uid, nickname, avatar_url, parent_id, content, like_count, deleted, created_at, updated_at)
            VALUES ' . implode(', ', $values);
    $db->prepare($sql)->execute($params);
}

// ━━━ 轮播图（image_url 为动态 SVG 相对路径，由 /banners/{name}.svg 生成）━━━
// banners 为展示数据，无外键依赖，直接重建保证幂等且无重复
$banners = [
    ['title' => '新插件上架', 'imageUrl' => '/banners/new-plugins.svg', 'linkUrl' => ''],
    ['title' => 'AI 工具推荐', 'imageUrl' => '/banners/ai-tools.svg', 'linkUrl' => ''],
    ['title' => '效率工具精选', 'imageUrl' => '/banners/productivity.svg', 'linkUrl' => ''],
    ['title' => '开发工具精选', 'imageUrl' => '/banners/dev-tools.svg', 'linkUrl' => ''],
    ['title' => '设计工具精选', 'imageUrl' => '/banners/design-tools.svg', 'linkUrl' => ''],
    ['title' => '系统工具精选', 'imageUrl' => '/banners/system-tools.svg', 'linkUrl' => ''],
];
$db->exec('DELETE FROM banners');
$stmt = $db->prepare('INSERT INTO banners (title, image_url, link_url, sort_order) VALUES (?, ?, ?, ?)');
foreach ($banners as $i => $b) {
    $stmt->execute([$b['title'], $b['imageUrl'], $b['linkUrl'], $i]);
}

// ━━━ 统计输出 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo "✅ 种子数据导入完成\n";
echo "   分类: " . (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn() . "\n";
echo "   插件: " . (int)$db->query('SELECT COUNT(*) FROM plugins')->fetchColumn() . "\n";
echo "   轮播图: " . (int)$db->query('SELECT COUNT(*) FROM banners')->fetchColumn() . "\n";
echo "   用户: " . (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() . "\n";
echo "   评论: " . (int)$db->query('SELECT COUNT(*) FROM comments')->fetchColumn() . "\n";

$perCat = $db->query(
    'SELECT c.title, COUNT(p.id) AS cnt FROM categories c LEFT JOIN plugins p ON p.category_id = c.id GROUP BY c.id ORDER BY c.id'
)->fetchAll();
foreach ($perCat as $row) {
    echo "     - {$row['title']}: {$row['cnt']} 个插件\n";
}
