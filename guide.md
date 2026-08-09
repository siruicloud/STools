# ZTools 二次开发规范指南

## 1. 项目概述

### 1.1 项目简介

ZTools 是一个跨平台 (macOS/Windows) 应用启动器和插件平台，类似 Alfred/Raycast。

**技术栈**: Electron 41 + Vue 3 + TypeScript + Pinia + LMDB + WebContentsView

**核心能力**:

- 拼音搜索、插件系统（UI/无界面）
- 剪贴板管理、超级面板、分离窗口
- MCP Server、AI 集成、ZBrowser 浏览器自动化
- 离线翻译、悬浮球、网页快开

### 1.2 二次开发目标

在**不修改主框架**的前提下，通过插件系统扩展功能，同时能够持续跟随官方版本更新。

---

## 2. 架构设计

### 2.1 整体架构

```
你的工作流
├── ZTools-Fork/              # 主仓库 Fork（仅用于同步官方更新）
│   ├── src/                  # 不修改！
│   ├── internal-plugins/     # 不修改！
│   └── .git/config           # 配置 upstream
│
├── ztools-my-plugins/        # 你的插件仓库（独立 Git 仓库）
│   ├── plugins/
│   │   ├── plugin-1/
│   │   ├── plugin-2/
│   │   └── ...
│   └── package.json          # monorepo 配置
│
└── ztools-custom-build/      # 构建脚本仓库（可选）
    ├── build.sh              # 自动化构建脚本
    └── config.json           # 品牌定制配置
```

### 2.2 核心原则

| 原则             | 说明                                         |
| ---------------- | -------------------------------------------- |
| **主仓库零修改** | 不修改 src/ 下的任何代码，确保可同步官方更新 |
| **插件独立仓库** | 所有自定义功能通过插件实现，独立版本管理     |
| **自动化构建**   | 使用脚本处理构建、打包、发布流程             |
| **清晰版本号**   | 版本号体现与官方版本的关系                   |

---

## 3. 环境搭建

### 3.1 Fork 官方仓库

```bash
# 1. 在 GitHub Fork https://github.com/ZToolsCenter/ZTools

# 2. 克隆并配置 upstream
git clone https://github.com/YOUR_USERNAME/ZTools.git ZTools-Fork
cd ZTools-Fork
git remote add upstream https://github.com/ZToolsCenter/ZTools.git
git fetch upstream

# 3. 验证
git remote -v
# 输出：
# origin    https://github.com/YOUR_USERNAME/ZTools.git (fetch/push)
# upstream  https://github.com/ZToolsCenter/ZTools.git (fetch)
```

### 3.2 创建插件仓库

```bash
# 1. 在 GitHub 创建 ztools-my-plugins 仓库

# 2. 克隆并初始化
git clone https://github.com/YOUR_USERNAME/ztools-my-plugins.git
cd ztools-my-plugins

# 3. 初始化 monorepo
cat > package.json << 'EOF'
{
  "name": "ztools-my-plugins",
  "private": true,
  "workspaces": ["plugins/*"],
  "scripts": {
    "build:all": "pnpm -r build",
    "dev:all": "pnpm -r dev"
  }
}
EOF

# 4. 安装依赖
pnpm install
```

### 3.3 安装依赖

```bash
# 进入 ZTools-Fork
cd ZTools-Fork

# 安装依赖（可能需要编译原生模块）
pnpm install

# 首次构建
pnpm typecheck
pnpm build
```

---

## 4. 插件开发规范

### 4.1 插件目录结构

```
plugins/my-plugin/
├── public/
│   ├── plugin.json          # 插件配置
│   └── preload/             # 可选：插件专属 preload
├── src/
│   ├── main.ts              # 入口文件
│   ├── App.vue              # 根组件
│   └── components/          # 子组件
├── index.html               # HTML 模板
├── vite.config.ts           # Vite 配置
├── package.json             # 插件依赖
└── tsconfig.json            # TypeScript 配置
```

### 4.2 plugin.json 配置

```json
{
  "name": "my-plugin",
  "title": "我的插件",
  "description": "自定义功能插件",
  "author": "Your Name",
  "version": "1.0.0",
  "main": "index.html",
  "logo": "logo.png",
  "features": [
    {
      "code": "my-feature",
      "explain": "我的功能",
      "icon": "logo.png",
      "cmds": ["我的功能", "My Feature"]
    }
  ]
}
```

### 4.3 命令匹配类型

`plugin.json` 的 `features[].cmds` 支持 6 种类型：

| 类型     | 说明                                | 存储位置                   |
| -------- | ----------------------------------- | -------------------------- |
| 字符串   | 功能指令，支持拼音搜索              | `commands` 数组（Fuse.js） |
| `regex`  | 正则匹配用户输入                    | `regexCommands` 数组       |
| `over`   | 匹配任意文本（可设排除规则）        | `regexCommands` 数组       |
| `img`    | 匹配粘贴的图片                      | `regexCommands` 数组       |
| `files`  | 匹配粘贴的文件（可限制类型/扩展名） | `regexCommands` 数组       |
| `window` | 匹配当前活动窗口（应用名/窗口标题） | `regexCommands` 数组       |

### 4.4 插件 API 使用

```typescript
// src/main.ts
import { createApp } from 'vue'
import App from './App.vue'

// 生命周期管理
window.ztools.onPluginEnter((action) => {
  console.log('插件进入:', action)
  // action.code: 功能代码
  // action.type: 匹配类型
  // action.payload: 匹配内容
})

window.ztools.onPluginLeave(() => {
  console.log('插件离开')
  // 清理资源
})

// 数据库操作
await window.ztools.dbPut('config', { theme: 'dark' })
const config = await window.ztools.dbGet('config')

// UI 控制
window.ztools.setExpendHeight(400) // 设置窗口高度
window.ztools.hideWindow() // 隐藏窗口

// 通知
window.ztools.showNotification({
  title: '提示',
  body: '操作成功'
})

createApp(App).mount('#app')
```

### 4.5 Vite 配置

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: './',
  build: {
    outDir: 'dist',
    assetsDir: 'assets'
  },
  server: {
    port: 5177,
    strictPort: true
  }
})
```

---

## 5. 同步官方更新

### 5.1 同步流程

```bash
# 1. 获取官方更新
cd ZTools-Fork
git fetch upstream

# 2. 查看更新内容
git log HEAD..upstream/main --oneline

# 3. 合并更新
git merge upstream/main

# 4. 解决冲突（如果有）
# 注意：如果修改了 src/ 下的代码，可能会产生冲突
# 推荐：保持 src/ 干净，避免冲突

# 5. 推送更新
git push origin main
```

### 5.2 自动化同步脚本

```bash
#!/bin/bash
# sync-official.sh

set -e

echo "🔄 同步官方更新..."

cd ZTools-Fork

# 获取最新代码
git fetch upstream

# 显示更新内容
echo "📋 更新内容:"
git log HEAD..upstream/main --oneline

# 合并更新
git merge upstream/main

# 检查 API 变化
if git diff HEAD~1..HEAD -- src/main/api/plugin/ | grep -q .; then
  echo "⚠️ 检测到插件 API 变化，请检查兼容性！"
fi

echo "✅ 同步完成！"
```

### 5.3 API 变更检查清单

同步后需要检查：

- [ ] 插件 API 是否有破坏性变更
- [ ] 插件 preload 接口是否变化
- [ ] 数据库命名空间是否变化
- [ ] 插件生命周期事件是否变化
- [ ] 窗口管理 API 是否变化

---

## 6. 构建与发布

### 6.1 插件构建

```bash
# 构建单个插件
cd plugins/my-plugin
pnpm build

# 构建所有插件
cd ztools-my-plugins
pnpm build:all
```

### 6.2 主程序构建

```bash
cd ZTools-Fork

# 类型检查
pnpm typecheck

# 构建源码
pnpm build

# 打包应用
pnpm build:mac    # macOS
pnpm build:win    # Windows
pnpm build:linux  # Linux
```

### 6.3 自动化构建脚本

```bash
#!/bin/bash
# build-all.sh

set -e

echo "🔨 开始构建..."

# 1. 构建插件
echo "📦 构建插件..."
cd ztools-my-plugins
pnpm build:all

# 2. 构建主程序
echo "🔧 构建主程序..."
cd ../ZTools-Fork
pnpm typecheck
pnpm build

# 3. 打包
echo "📀 打包应用..."
pnpm build:mac

echo "✅ 构建完成！"
```

---

## 7. 版本号策略

### 7.1 推荐版本号格式

```
主程序：{官方版本}-custom.{自定义版本号}

示例：
- 官方 v3.0.2 → 你的 v3.0.2-custom.1
- 官方 v3.0.3 → 你的 v3.0.3-custom.1
- 基于官方 v3.0.3 第二次构建 → v3.0.3-custom.2

插件：独立版本管理
- my-plugin v1.0.0
- my-plugin v1.1.0（功能更新）
- my-plugin v2.0.0（破坏性变更）
```

### 7.2 版本号修改注意事项

如果要将版本号改为 `1.0.0`：

**技术影响**：

- ✅ 编译构建不受影响
- ⚠️ 可能触发数据迁移逻辑
- ⚠️ 与官方版本关系混乱
- ⚠️ 自动更新机制可能冲突

**推荐做法**：

```json
{
  "version": "3.0.2-custom.1"  // 清晰表明基于官方版本
}

// 或
{
  "version": "1.0.0-ztools-3.0.2"  // 表明自定义版本+基于官方版本
}
```

---

## 8. 开发工作流

### 8.1 日常开发

```bash
# 终端 1：启动插件开发
cd ztools-my-plugins
pnpm --filter my-plugin dev

# 终端 2：启动 ZTools 开发环境
cd ZTools-Fork
pnpm dev

# 在 ZTools 中测试插件（自动加载 http://localhost:5177）
```

### 8.2 调试技巧

| 修改类型       | 生效方式    | 调试方法        |
| -------------- | ----------- | --------------- |
| 插件 UI        | Vite 热重载 | 浏览器 DevTools |
| 插件 preload   | 重启应用    | console.log     |
| 主程序渲染进程 | 热重载      | 浏览器 DevTools |
| 主进程         | 重启应用    | electron-log    |

### 8.3 性能优化

```typescript
// 节流搜索
import { debounce } from 'lodash-es'

const debouncedSearch = debounce(async (query: string) => {
  // 执行搜索
}, 300)

// 动态调整窗口高度
function updateHeight(itemCount: number) {
  const baseHeight = 100
  const itemHeight = 50
  const maxHeight = 600

  const calculatedHeight = baseHeight + itemCount * itemHeight
  const finalHeight = Math.min(calculatedHeight, maxHeight)

  window.ztools.setExpendHeight(finalHeight)
}
```

---

## 9. 最佳实践

### 9.1 命名空间隔离

```typescript
// ✅ 好的做法：使用命名空间
const NAMESPACE = 'MY_PLUGIN/'

await window.ztools.dbPut(NAMESPACE + 'settings', {
  theme: 'dark'
})

// ❌ 避免：直接使用键名（可能冲突）
await window.ztools.dbPut('settings', { ... })
```

### 9.2 错误处理

```typescript
// ✅ 好的做法：完善的错误处理
try {
  const result = await window.ztools.dbGet('my-key')
  if (!result) {
    // 处理空值情况
    return
  }
  // 处理数据
} catch (error) {
  console.error('数据库操作失败:', error)
  window.ztools.showNotification({
    title: '错误',
    body: '操作失败，请重试'
  })
}
```

### 9.3 内存管理

```typescript
// ✅ 好的做法：清理监听器
let resizeHandler: Function | null = null

window.ztools.onPluginEnter(() => {
  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler)
  }
  resizeHandler = handleResize
  window.addEventListener('resize', resizeHandler)
})

window.ztools.onPluginLeave(() => {
  if (resizeHandler) {
    window.removeEventListener('resize', resizeHandler)
    resizeHandler = null
  }
})
```

### 9.4 跨平台兼容

```typescript
// ✅ 好的做法：检查平台兼容性
if (window.ztools.getPlatform() === 'win32') {
  const result = await window.ztools.screenCapture()
} else {
  window.ztools.showNotification({
    title: '不支持',
    body: '此功能仅在 Windows 上可用'
  })
}
```

---

## 10. 故障排除

### 10.1 常见问题

| 问题         | 原因           | 解决方案                       |
| ------------ | -------------- | ------------------------------ |
| 插件不加载   | preload 未重启 | 重启应用                       |
| API 调用失败 | 类型不匹配     | 检查 ztools-api-types          |
| 合并冲突     | 修改了 src/    | 恢复干净代码                   |
| 构建失败     | 依赖问题       | pnpm install --frozen-lockfile |

### 10.2 合并冲突解决

```bash
# 1. 查看冲突文件
git status

# 2. 恢复官方版本（推荐）
git checkout upstream/main -- src/conflicted-file.ts

# 3. 重新合并
git merge upstream/main

# 4. 提交
git commit -m "Merge upstream changes"
```

### 10.3 API 不兼容处理

```bash
# 1. 查看 API 变化
cd ZTools-Fork
git diff upstream/main...HEAD -- src/main/api/plugin/

# 2. 更新插件代码
cd ztools-my-plugins
pnpm install @ztools-center/ztools-api-types@latest

# 3. 测试插件
pnpm --filter my-plugin test
```

---

## 11. 扩展策略对比

| 扩展方式       | 修改主框架 | 难度     | 灵活性     | 分发能力   | 推荐度     |
| -------------- | ---------- | -------- | ---------- | ---------- | ---------- |
| **内置插件**   | ❌ 否      | ⭐⭐     | ⭐⭐⭐⭐⭐ | ⭐⭐⭐     | ✅✅✅✅✅ |
| **第三方插件** | ❌ 否      | ⭐       | ⭐⭐⭐⭐   | ⭐⭐⭐⭐⭐ | ✅✅✅✅   |
| **主题定制**   | ⚠️ 轻微    | ⭐       | ⭐⭐       | ⭐⭐       | ✅✅✅     |
| **配置扩展**   | ❌ 否      | ⭐       | ⭐⭐       | ⭐⭐⭐     | ✅✅✅     |
| **扩展 API**   | ⚠️ 是      | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐         | ⚠️ 谨慎    |

---

## 12. 参考资源

### 12.1 关键文件

| 修改目标 | 关键文件                       | 行数 |
| -------- | ------------------------------ | ---- |
| 插件系统 | `managers/pluginManager.ts`    | 1882 |
| 窗口管理 | `managers/windowManager.ts`    | 1133 |
| 剪贴板   | `managers/clipboardManager.ts` | 860  |
| 指令管理 | `api/renderer/commands.ts`     | 1099 |
| 插件市场 | `api/renderer/plugins.ts`      | 1761 |
| 原生接口 | `core/native/index.ts`         | 1222 |
| 数据库   | `core/lmdb/`                   | -    |

### 12.2 开发命令

```bash
pnpm dev              # 启动开发（主进程 + setting 内置插件并行热重载）
pnpm dev:main         # 仅启动主进程
pnpm dev:setting      # 仅启动 setting 内置插件开发服务器
pnpm typecheck        # 全部类型检查（node + web）
pnpm typecheck:node   # 主进程 + preload 类型检查
pnpm typecheck:web    # 渲染进程类型检查
pnpm build            # 编译源码（含类型检查和 setting 构建）
pnpm build:mac        # 打包 macOS
pnpm build:win        # 打包 Windows
pnpm build:linux      # 打包 Linux
pnpm build:unpack     # 打包但不生成安装包（调试用）
pnpm test             # 运行测试 (vitest)
pnpm test:watch       # 测试观察模式
pnpm sync-api-types   # 同步 ztools-api-types 子模块类型
```

### 12.3 注意事项

- 项目有两套独立的 preload（主程序用 `src/preload/index.ts` vs 插件用 `resources/preload.js`）
- `resources/preload.js` 不经过 Vite 构建，修改后需重启应用
- `src/preload/index.ts` 底部的类型声明需要与 `src/renderer/src/env.d.ts` 保持同步
- 优先使用 `style.css` 中的通用控件类（.btn .input .select .toggle .card）
- 内置插件 setting 是独立 Vue 项目（`internal-plugins/setting/`）

---

## 13. 总结

### 13.1 核心原则

- ✅ **主仓库零修改** → 完美同步官方更新
- ✅ **插件独立仓库** → 灵活开发测试
- ✅ **自动化脚本** → 一键构建发布
- ✅ **清晰版本管理** → 易于维护

### 13.2 工作流总览

```
开发：插件仓库 → 测试 → 构建
同步：官方更新 → 检查 → 合并
发布：构建 → 打标签 → GitHub Release
```

### 13.3 适用场景

- ✅ 企业内部工具定制
- ✅ 个人增强功能开发
- ✅ 行业解决方案插件
- ❌ 核心架构修改（需直接 Fork）

---

_本文档基于 ZTools 3.0.2 版本编写，适用于二次开发场景。_
