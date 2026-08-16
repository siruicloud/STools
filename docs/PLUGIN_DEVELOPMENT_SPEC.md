# ZTools 插件开发规范指南 (v1.0)

本文档定义了 ZTools 插件的标准项目结构、配置规范及 API 使用指南。遵循此规范可确保插件在 ZTools 环境中稳定运行，并支持热重载开发与分发。

## 1. 项目结构

一个标准的 ZTools 插件应包含以下目录结构：

```text
my-plugin/
├── public/                  # 静态资源目录
│   ├── plugin.json          # [核心] 插件配置文件
│   └── logo.png             # 插件图标 (建议 128x128 png)
├── src/                     # 源代码目录
│   ├── main.ts              # 入口文件 (生命周期管理)
│   ├── App.vue              # 根组件
│   └── components/          # 子组件
├── index.html               # HTML 模板
├── package.json             # 项目依赖
├── vite.config.ts           # 构建配置
└── tsconfig.json            # TypeScript 配置
```

## 2. 核心配置 (`plugin.json`)

`plugin.json` 是插件的身份证，必须放置在 `public/` 目录下。

### 2.1 基础字段

| 字段      | 类型   | 必填 | 说明                                           |
| --------- | ------ | ---- | ---------------------------------------------- |
| `name`    | string | 是   | 插件唯一标识（英文，小写连字符），如 `my-tool` |
| `title`   | string | 是   | 显示名称，如 `我的工具`                        |
| `version` | string | 是   | 语义化版本号，如 `1.0.0`                       |
| `main`    | string | 是   | 生产环境入口文件，通常为 `index.html`          |
| `logo`    | string | 是   | 图标路径，相对于 `plugin.json`                 |

### 2.2 开发模式配置

为了实现开发时的热重载，必须配置 `development` 字段：

```json
{
  "main": "../dist/index.html",
  "development": {
    "main": "http://localhost:5180"
  }
}
```

- **`main`**: 生产模式下，ZTools 会加载此路径。如果是内置插件，通常指向 `../dist/index.html`；如果是第三方插件包，指向 `index.html`。
- **`development.main`**: 开发模式下，ZTools 会优先使用此 URL 加载插件。

### 2.3 功能与命令 (`features`)

定义插件提供的功能及触发方式：

```json
"features": [
  {
    "code": "main-feature",
    "explain": "功能描述",
    "icon": "logo.png",
    "cmds": ["我的工具", "My Tool"]
  }
]
```

**`cmds` 匹配类型说明**：

- **字符串**: 文本匹配，支持拼音搜索。
- **对象 (`type: "files"`)**: 匹配拖入的文件。
- **对象 (`type: "regex"`)**: 正则匹配用户输入。
- **对象 (`type: "img"`)**: 匹配粘贴的图片。

## 3. 代码规范

### 3.1 生命周期管理

在 `src/main.ts` 中处理插件的进入与退出：

```typescript
import { createApp } from 'vue'
import App from './App.vue'

// 1. 初始化 Vue 应用
const app = createApp(App)
app.mount('#app')

// 2. 监听进入事件
ztools.onPluginEnter((action) => {
  console.log('插件被唤起', action)

  // 根据 action.code 执行不同逻辑
  if (action.code === 'main-feature') {
    // 设置窗口高度
    window.ztools.setExpendHeight(500)
  }
})

// 3. 监听退出事件
ztools.onPluginOut(() => {
  console.log('插件被隐藏')
  // 清理定时器、全局变量等
})
```

### 3.2 常用 API 清单

通过全局 `window.ztools` 对象调用：

| API                      | 说明             | 示例                                           |
| ------------------------ | ---------------- | ---------------------------------------------- |
| `setExpendHeight(h)`     | 调整插件窗口高度 | `ztools.setExpendHeight(400)`                  |
| `hideMainWindow()`       | 隐藏主搜索框     | `ztools.hideMainWindow()`                      |
| `showNotification()`     | 显示系统通知     | `ztools.showNotification('操作成功')`          |
| `dbPut(key, val)`        | 异步存储数据     | `await ztools.dbPut('config', {theme:'dark'})` |
| `dbGet(key)`             | 异步获取数据     | `const c = await ztools.dbGet('config')`       |
| `shellOpenExternal(url)` | 打开外部链接     | `ztools.shellOpenExternal('https://...')`      |

## 4. 构建配置 (`vite.config.ts`)

必须配置 CSP 头以支持 ZTools 的图标协议 (`ztools-icon://`)：

```typescript
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  base: './',
  plugins: [
    vue(),
    {
      name: 'csp-header',
      configureServer: (server) => {
        server.middlewares.use((_req, res, next) => {
          res.setHeader(
            'Content-Security-Policy',
            "default-src * 'unsafe-inline' 'unsafe-eval' data: blob: ztools-icon: file:; img-src * data: blob: ztools-icon: file:;"
          )
          next()
        })
      }
    }
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) }
  }
})
```

## 5. 调试与发布

### 5.1 本地调试

1.  在插件目录运行 `pnpm dev` 启动 Vite 服务。
2.  在 ZTools 设置中添加该插件的 `plugin.json` 路径（开发模式）。
3.  修改代码保存后，ZTools 窗口会自动刷新。

### 5.2 打包发布

1.  运行 `pnpm build` 生成 `dist/` 目录。
2.  使用 `ztools-plugin-cli` 工具将 `dist/` 和 `public/` 打包为 `.zpx` 文件。
3.  用户下载 `.zpx` 后拖入 ZTools 即可安装。

---

## 6. 高级功能：全屏独立窗口

对于需要沉浸式体验的插件（如演示、大屏展示），可以创建独立的全屏窗口。

### 6.1 创建全屏窗口

使用 `window.ztools.createBrowserWindow` API：

```typescript
// 1. 隐藏主搜索窗口
window.ztools.hideMainWindow()

// 2. 创建全屏窗口
// 注意：路径是相对于插件根目录的，开发模式下如果产物在 dist，可能需要写 ../dist/index.html
window.ztools.createBrowserWindow('index.html?mode=fullscreen', {
  fullscreen: true, // 开启全屏
  frame: false, // 无边框（可选）
  transparent: true, // 透明背景（可选）
  webPreferences: {
    zoomFactor: 1.0
  }
})
```

### 6.2 退出全屏与恢复

在全屏窗口关闭时，记得恢复主窗口：

```typescript
// 在 Vue 组件中
function exitFullscreen() {
  window.close() // 关闭当前全屏窗口
  window.ztools.showMainWindow() // 恢复主搜索窗口
}
```

### 6.3 注意事项

- **模式区分**：建议通过 URL 参数（如 `?mode=fullscreen`）区分普通模式和全屏模式，复用同一套代码。
- **路径问题**：确保 `createBrowserWindow` 传入的 HTML 路径正确，否则会显示空白。

## 7. 插件打包与加密

### 7.1 .zpx 格式说明

ZTools 使用 `.zpx` 作为插件分发格式。

- **结构**: ASAR 归档文件 + Gzip 压缩。
- **安全性**: 具有一定的混淆保护，防止普通用户直接查看源码。
- **优势**: 支持随机读取，加载速度快，ZTools 原生支持拖拽安装。

### 7.2 打包方法

使用官方脚手架工具进行打包：

```bash
npx @ztools-center/ztools-plugin-cli pack
```

---

## 8. UI/UX 规范

为了保持 ZTools 整体界面的统一性和专业性，插件开发需遵循以下 UI/UX 规范。

### 8.1 禁止使用 Emoji

**规则**：插件界面中**严禁使用 Emoji 表情符号**（如 📄, 🖼️, ✅, ❌ 等）。
**原因**：

- **显示不一致**：不同操作系统（macOS/Windows/Linux）对 Emoji 的渲染样式差异巨大，破坏界面美感。
- **不专业**：Emoji 会降低工具软件的专业感。

### 8.2 使用标准图标 (SVG)

**规则**：所有图标必须使用 **SVG 矢量图标**。
**推荐方案**：

- 使用开源图标库（如 [Lucide](https://lucide.dev/), [Heroicons](https://heroicons.com/), [Feather](https://feathericons.com/)）。
- 保持线条风格统一（通常使用 2px 描边，圆角端点）。

**示例代码**：

```vue
<!-- ❌ 错误：使用 Emoji -->
<span>📄 打开文件</span>

<!-- ✅ 正确：使用 SVG -->
<span class="icon-wrapper">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
    <polyline points="14 2 14 8 20 8"/>
  </svg>
  <span>打开文件</span>
</span>
```

### 8.3 颜色与主题适配

- **跟随系统**：使用 CSS 变量或媒体查询 `@media (prefers-color-scheme: dark)` 适配深色模式。
- **主色调**：尽量使用 ZTools 的主题色（可通过 CSS 变量获取，如 `--primary-color`），保持视觉连贯。

---

> **⚠️ 注意事项**：
>
> 1.  **路径问题**：内置插件的 `main` 路径通常是 `../dist/index.html`，因为 `plugin.json` 在 `public/` 下，而产物在 `dist/` 下。
> 2.  **沙盒限制**：插件运行在 WebContentsView 中，无法直接访问 Node.js API（如 `fs`），文件操作需通过 `ztools` API 代理。
