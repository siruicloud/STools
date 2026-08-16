# 插件开发指南

> 商业功能独立开发最佳实践

---

## 📋 目录

- [快速开始](#快速开始)
- [插件结构](#插件结构)
- [开发流程](#开发流程)
- [API 参考](#api-参考)
- [测试调试](#测试调试)
- [构建发布](#构建发布)

---

## 快速开始

### 5 分钟创建第一个插件

```bash
# 1. 创建插件目录
mkdir -p custom-plugins/hello-world
cd custom-plugins/hello-world

# 2. 创建基础文件
cat > plugin.json << 'EOF'
{
  "name": "hello-world",
  "title": "Hello World",
  "description": "我的第一个插件",
  "author": "Your Name",
  "version": "1.0.0",
  "main": "index.html",
  "logo": "logo.png",
  "features": [
    {
      "code": "hello",
      "explain": "打招呼",
      "icon": "logo.png",
      "cmds": ["你好", "Hello"]
    }
  ]
}
EOF

# 3. 创建 package.json
cat > package.json << 'EOF'
{
  "name": "hello-world-plugin",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build"
  },
  "dependencies": {
    "vue": "^3.5.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^6.0.0",
    "vite": "^7.0.0"
  }
}
EOF

# 4. 创建 Vite 配置
cat > vite.config.ts << 'EOF'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  base: './',
  server: {
    port: 5177,
    strictPort: true
  }
})
EOF

# 5. 创建 HTML 入口
cat > index.html << 'EOF'
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hello World</title>
</head>
<body>
  <div id="app"></div>
  <script type="module" src="/src/main.ts"></script>
</body>
</html>
EOF

# 6. 创建源码目录
mkdir -p src

cat > src/main.ts << 'EOF'
import { createApp } from 'vue'
import App from './App.vue'

createApp(App).mount('#app')

window.ztools.onPluginEnter((action) => {
  console.log('Hello World plugin entered!', action)
})
EOF

cat > src/App.vue << 'EOF'
<template>
  <div class="hello-world">
    <h1>Hello World!</h1>
    <p>欢迎使用我的第一个插件</p>
  </div>
</template>

<script setup lang="ts">
</script>

<style scoped>
.hello-world {
  padding: 20px;
  text-align: center;
}
</style>
EOF

# 7. 安装依赖并启动
pnpm install
pnpm dev
```

---

## 插件结构

### 标准目录结构

```
your-plugin/
├── plugin.json          # 插件配置（必需）
├── package.json         # 依赖管理
├── vite.config.ts       # Vite 配置
├── tsconfig.json        # TypeScript 配置
├── index.html           # 入口 HTML
├── public/              # 静态资源
│   ├── logo.png         # 插件图标
│   └── icons/           # 功能图标
└── src/                 # 源代码
    ├── main.ts          # 入口脚本（生命周期）
    ├── App.vue          # 主组件
    ├── components/      # 组件
    ├── composables/     # 组合式函数
    ├── stores/          # 状态管理
    ├── features/        # 功能模块
    └── utils/           # 工具函数
```

---

### plugin.json 详解

```json
{
  "$schema": "node_modules/@ztools-center/ztools-api-types/resource/ztools.schema.json",
  "name": "your-plugin", // 插件唯一标识（小写连字符）
  "title": "Your Plugin", // 插件显示名称
  "description": "插件描述", // 详细说明
  "author": "Your Name", // 作者信息
  "version": "1.0.0", // 插件版本
  "main": "index.html", // 入口文件
  "logo": "logo.png", // 插件图标

  "homepage": "https://...", // 主页地址（可选）
  "repository": {
    // 仓库信息（可选）
    "type": "git",
    "url": "https://github.com/..."
  },

  "development": {
    // 开发配置
    "main": "http://localhost:5177" // 开发服务器地址
  },

  "features": [
    // 功能入口列表
    {
      "code": "main-feature", // 功能代码
      "explain": "主要功能", // 功能说明
      "icon": "logo.png", // 功能图标
      "cmds": ["关键词1", "关键词2"] // 触发关键词
    }
  ]
}
```

---

### 功能入口类型

#### 1. 文本关键词

```json
{
  "code": "search",
  "explain": "搜索功能",
  "icon": "search.png",
  "cmds": ["搜索", "Search", "查找"]
}
```

**触发**：用户输入"搜索"、"Search" 等关键词

---

#### 2. 正则匹配

```json
{
  "code": "url-open",
  "explain": "打开网址",
  "icon": "link.png",
  "cmds": [
    {
      "type": "regex",
      "match": "/^https?:\\/\\/[^\\s/$.?#]\\S+$/",
      "label": "打开网址",
      "minLength": 3
    }
  ]
}
```

**触发**：用户输入 URL（如 `https://example.com`）

---

#### 3. 文件匹配

```json
{
  "code": "image-process",
  "explain": "处理图片",
  "icon": "image.png",
  "cmds": [
    {
      "type": "files",
      "label": "处理图片",
      "fileType": "image",
      "match": "/\\.(jpg|png|gif)$/i",
      "maxLength": 10
    }
  ]
}
```

**触发**：用户粘贴或选择图片文件

---

#### 4. 窗口上下文

```json
{
  "code": "copy-path",
  "explain": "复制路径",
  "icon": "folder.png",
  "cmds": [
    {
      "type": "window",
      "label": "复制路径",
      "match": {
        "app": ["Finder.app", "explorer.exe"],
        "className": ["CabinetWClass"]
      }
    }
  ]
}
```

**触发**：当 Finder 或 Explorer 窗口激活时

---

#### 5. 任意文本匹配

```json
{
  "code": "translate",
  "explain": "翻译文本",
  "icon": "translate.png",
  "cmds": [
    {
      "type": "over",
      "label": "翻译",
      "minLength": 1,
      "maxLength": 1000,
      "exclude": ["password", "secret"]
    }
  ]
}
```

**触发**：用户输入任意文本（排除敏感词）

---

## 开发流程

### 生命周期管理

```typescript
// src/main.ts

import { createApp } from 'vue'
import App from './App.vue'

const app = createApp(App)
app.mount('#app')

// ============================================
// 插件进入事件（重要）
// ============================================

window.ztools.onPluginEnter(async (action) => {
  console.log('Plugin entered:', action)

  // action 包含：
  // - code: 功能代码（来自 plugin.json）
  // - type: 触发类型（text/regex/files/img/window/over）
  // - payload: 用户输入内容

  // 根据功能代码分发
  switch (action.code) {
    case 'search':
      await handleSearch(action.payload)
      break
    case 'url-open':
      await openUrl(action.payload)
      break
    case 'image-process':
      await processImages(action.payload)
      break
    default:
      console.warn('Unknown code:', action.code)
  }
})

// ============================================
// 插件离开事件（清理资源）
// ============================================

window.ztools.onPluginLeave(() => {
  console.log('Plugin leaving...')

  // 清理工作：
  // - 取消未完成的请求
  // - 保存临时数据
  // - 重置状态

  cleanup()
})

// ============================================
// 功能处理函数
// ============================================

async function handleSearch(query: string) {
  // 设置子输入框（实时搜索）
  window.ztools.setSubInput(
    {
      placeholder: '输入搜索关键词...',
      value: query
    },
    (text) => {
      console.log('用户输入:', text)
      // 实时搜索逻辑
    }
  )

  // 设置窗口高度
  window.ztools.setExpendHeight(400)
}

async function openUrl(url: string) {
  // 使用 Shell 打开
  window.ztools.shellOpenExternal(url)

  // 显示通知
  window.ztools.showNotification({
    title: '已打开',
    body: url
  })

  // 隐藏窗口
  window.ztools.hideWindow()
}

async function processImages(files: Array<{ path: string }>) {
  console.log('Processing images:', files)

  // 处理图片
  for (const file of files) {
    // 你的处理逻辑
  }

  // 完成通知
  window.ztools.showNotification({
    title: '处理完成',
    body: `已处理 ${files.length} 张图片`
  })
}

function cleanup() {
  // 清理逻辑
}
```

---

### 状态管理（Pinia）

```typescript
// src/stores/pluginStore.ts

import { defineStore } from 'pinia'

export const usePluginStore = defineStore('plugin', {
  state: () => ({
    searchQuery: '',
    results: [] as any[],
    loading: false
  }),

  actions: {
    async search(query: string) {
      this.loading = true
      this.searchQuery = query

      try {
        // 执行搜索
        const results = await this.fetchResults(query)
        this.results = results

        // 更新窗口高度
        window.ztools.setExpendHeight(100 + results.length * 50)
      } catch (error) {
        console.error('Search failed:', error)
        window.ztools.showNotification({
          title: '搜索失败',
          body: '请重试'
        })
      } finally {
        this.loading = false
      }
    },

    async fetchResults(query: string) {
      // 你的搜索逻辑
      return []
    }
  }
})
```

---

### 组件开发

```vue
<!-- src/components/SearchResults.vue -->

<template>
  <div class="search-results">
    <!-- 加载状态 -->
    <div v-if="loading" class="loading">加载中...</div>

    <!-- 结果列表 -->
    <div v-else-if="results.length > 0" class="results-list">
      <div
        v-for="(item, index) in results"
        :key="item.id"
        class="result-item"
        @click="selectItem(item)"
      >
        <div class="item-title">{{ item.title }}</div>
        <div class="item-desc">{{ item.description }}</div>
      </div>
    </div>

    <!-- 空状态 -->
    <div v-else class="empty-state">
      <p>没有找到结果</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { usePluginStore } from '../stores/pluginStore'

const store = usePluginStore()

const loading = computed(() => store.loading)
const results = computed(() => store.results)

function selectItem(item: any) {
  console.log('Selected:', item)
  // 处理选中逻辑
}
</script>

<style scoped>
.search-results {
  padding: 16px;
}

.result-item {
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  cursor: pointer;
}

.result-item:hover {
  background: #f9fafb;
}
</style>
```

---

## API 参考

### 核心 API

#### 生命周期

```typescript
// 监听插件进入
window.ztools.onPluginEnter((action) => {
  // action: { code, type, payload }
})

// 监听插件离开
window.ztools.onPluginLeave(() => {
  // 清理资源
})
```

---

#### UI 控制

```typescript
// 设置窗口高度
window.ztools.setExpendHeight(400)

// 设置子输入框
window.ztools.setSubInput(
  {
    placeholder: '请输入...',
    value: ''
  },
  (text) => {
    console.log('用户输入:', text)
  }
)

// 隐藏窗口
window.ztools.hideWindow()
```

---

#### 数据持久化

```typescript
// 写入数据（自动添加命名空间）
await window.ztools.dbPut('config', { theme: 'dark' })

// 读取数据
const config = await window.ztools.dbGet('config')

// 删除数据
await window.ztools.dbRemove('config')

// 批量操作
await window.ztools.dbBatch([
  { type: 'put', key: 'key1', value: 'value1' },
  { type: 'put', key: 'key2', value: 'value2' }
])
```

---

#### 通知和对话框

```typescript
// 显示通知
window.ztools.showNotification({
  title: '通知标题',
  body: '通知内容'
})

// 显示对话框
const result = await window.ztools.showMessageBox({
  type: 'question',
  title: '确认',
  message: '确定要删除吗？',
  buttons: ['确定', '取消']
})
```

---

#### 剪贴板

```typescript
// 读取文本
const text = await window.ztools.getClipboardText()

// 写入文本
await window.ztools.setClipboardText('复制的内容')

// 读取文件列表
const files = await window.ztools.getClipboardFiles()

// 写入文件列表
await window.ztools.setClipboardFiles(['/path/to/file1', '/path/to/file2'])
```

---

#### Shell 操作

```typescript
// 打开外部链接
window.ztools.shellOpenExternal('https://example.com')

// 打开文件路径
window.ztools.shellOpenPath('/path/to/file')

// 执行 Shell 匯令
const result = await window.ztools.shellExecute({
  command: 'ls',
  args: ['-la'],
  cwd: '/path/to/dir'
})
```

---

### 高级 API

#### AI 对话

```typescript
// 流式对话
const stream = await window.ztools.aiChatCompletion({
  messages: [
    { role: 'system', content: '你是一个有用的助手' },
    { role: 'user', content: '你好' }
  ],
  model: 'gpt-4',
  stream: true
})

// 读取流式响应
const reader = stream.getReader()
while (true) {
  const { done, value } = await reader.read()
  if (done) break

  const chunk = new TextDecoder().decode(value)
  const data = JSON.parse(chunk)

  if (data.choices?.[0]?.delta?.content) {
    const content = data.choices[0].delta.content
    // 更新 UI
  }
}
```

---

#### 浏览器自动化

```typescript
// 启动浏览器
await window.ztools.zbrowserLaunch({
  url: 'https://example.com'
})

// 查询元素
const text = await window.ztools.zbrowserQuery({
  selector: 'h1'
})

// 点击元素
await window.ztools.zbrowserClick({
  selector: 'button.submit'
})

// 输入文本
await window.ztools.zbrowserType({
  selector: 'input[name="username"]',
  text: 'user123'
})
```

---

## 测试调试

### 开发模式调试

```bash
# 1. 启动插件开发服务器
cd custom-plugins/your-plugin
pnpm dev

# 2. 启动 STools 主程序（另一个终端）
cd ../..
pnpm dev

# 3. 在 STools 中输入关键词触发插件
# 4. 按 Cmd+Option+I（macOS）或 Ctrl+Shift+I（Windows）打开 DevTools
```

---

### 日志调试

```typescript
// 使用 console.log（会显示在 DevTools）
console.log('Debug info:', data)

// 使用 electron-log（会写入文件）
window.ztools.log.info('Info message')
window.ztools.log.error('Error message', error)
window.ztools.log.warn('Warning message')

// 查看日志文件
// macOS: ~/Library/Logs/STools/main.log
// Windows: %USERPROFILE%\AppData\Roaming\STools\logs\main.log
```

---

### 单元测试

```typescript
// tests/search.test.ts

import { describe, it, expect, vi } from 'vitest'
import { usePluginStore } from '../src/stores/pluginStore'

describe('Search Feature', () => {
  it('should search correctly', async () => {
    const store = usePluginStore()

    // Mock API
    window.ztools.dbGet = vi.fn().mockResolvedValue({ results: [] })

    await store.search('test query')

    expect(store.searchQuery).toBe('test query')
    expect(store.loading).toBe(false)
  })
})
```

---

## 构建发布

### 构建流程

```bash
# 1. 构建 Vue 应用
cd custom-plugins/your-plugin
pnpm build

# 2. 打包为 .spk 文件
# 使用构建脚本
../../scripts/build-plugin.sh your-plugin

# 输出：your-plugin.spk
```

---

### 手动打包

```bash
# 1. 创建临时目录
mkdir -p /tmp/zpx-build

# 2. 复制构建产物
cp -r dist/* /tmp/zpx-build/

# 3. 复制 plugin.json
cp plugin.json /tmp/zpx-build/

# 4. 复制静态资源
cp -r public/* /tmp/zpx-build/

# 5. 打包为 asar
npx asar pack /tmp/zpx-build plugin.asar

# 6. 压缩为 .spk（gzip）
gzip -c plugin.asar > your-plugin.spk

# 7. 清理
rm -rf /tmp/zpx-build plugin.asar
```

---

### 发布清单

```bash
# 1. 更新版本号
npm version patch  # 1.0.0 → 1.0.1

# 2. 更新 CHANGELOG
# 编辑 CHANGELOG.md

# 3. 构建
pnpm build

# 4. 打包
../../scripts/build-plugin.sh your-plugin

# 5. 测试安装
# 在 STools 中手动安装 your-plugin.spk

# 6. 提交代码
git add .
git commit -m "feat: 发布插件 v1.0.1"

# 7. 打标签
git tag -a plugin-v1.0.1 -m "Plugin Release v1.0.1"

# 8. 推送
git push origin main --tags

# 9. 创建 GitHub Release
gh release create plugin-v1.0.1 \
  your-plugin.spk \
  --title "Plugin v1.0.1" \
  --notes "更新内容：..."
```

---

## 最佳实践

### 1. 命名规范

```typescript
// ✅ 插件命名：小写连字符
'your-plugin-name'

// ✅ 功能代码：小写连字符
;('team-management', 'workflow-automation')

// ✅ 组件命名：PascalCase
;(TeamManagement.vue, WorkflowBuilder.vue)

// ✅ 文件命名：camelCase
;(useTeamData.ts, teamStore.ts)
```

---

### 2. 性能优化

```typescript
// ✅ 节流搜索
import { debounce } from 'lodash-es'

const debouncedSearch = debounce((query: string) => {
  // 执行搜索
}, 300)

// ✅ 虚拟列表（大量数据）
import { useVirtualList } from '@vueuse/core'

const { list, containerProps, wrapperProps } = useVirtualList(largeList, { itemHeight: 50 })

// ✅ 懒加载组件
const LazyComponent = defineAsyncComponent(() => import('./components/HeavyComponent.vue'))
```

---

### 3. 错误处理

```typescript
// ✅ 完善的错误处理
try {
  const result = await riskyOperation()
  if (!result) {
    // 处理空值
    return
  }
  // 处理结果
} catch (error) {
  console.error('Operation failed:', error)
  window.ztools.showNotification({
    title: '错误',
    body: '操作失败，请重试'
  })
}
```

---

### 4. 类型安全

```typescript
// ✅ 使用 TypeScript 类型
interface SearchResult {
  id: string
  title: string
  description: string
}

async function search(query: string): Promise<SearchResult[]> {
  const results = await window.ztools.dbGet<SearchResult[]>('results')
  return results || []
}
```

---

## 常见问题

### Q1: 插件无法加载？

**检查**：

1. `plugin.json` 格式是否正确
2. 开发服务器是否启动（http://localhost:5177）
3. 端口是否被占用

---

### Q2: API 调用失败？

**检查**：

1. ZTools 版本是否支持该 API
2. 查看 `ztools-api-types/index.d.ts` 确认 API 签名
3. 查看控制台错误信息

---

### Q3: 构建失败？

**检查**：

1. 依赖是否安装完整（`pnpm install`）
2. TypeScript 类型是否正确
3. Vite 配置是否正确

---

## 总结

### 核心要点

1. ✅ 使用标准目录结构
2. ✅ 正确配置 plugin.json
3. ✅ 管理好生命周期
4. ✅ 使用 TypeScript 确保类型安全
5. ✅ 遵循最佳实践

---

### 下一步

- 阅读 [架构方案](./ARCHITECTURE.md)
- 开始开发你的商业插件
- 定期同步官方更新

---

**祝你开发愉快！** 🚀
