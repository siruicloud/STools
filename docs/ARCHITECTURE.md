# STools 商业化开发架构方案

> 最小化改动官方框架 + 插件独立开发 + 定期同步更新

---

## 📋 目录

- [架构概述](#架构概述)
- [设计原则](#设计原则)
- [目录结构](#目录结构)
- [开发流程](#开发流程)
- [版本同步](#版本同步)
- [最佳实践](#最佳实践)
- [故障排除](#故障排除)

---

## 架构概述

### 核心策略

**最小化改动 + 插件隔离 + 定期同步**

```
主仓库（STools/）
├── 官方代码：保持原样，不做修改 ✅
├── 品牌元素：仅修改必要字段 ✅
└── 商业功能：全部在独立插件中开发 ✅

插件仓库（custom-plugins/）
├── 独立开发环境（Vite + Vue 3）✅
├── 独立依赖管理（package.json）✅
└── 独立构建流程（.spk 打包）✅

同步策略
├── 定期同步官方更新（每周）✅
├── 零冲突合并（改动最小）✅
└── 自动测试验证 ✅
```

---

## 设计原则

### 1. 最小化改动原则

**主仓库仅修改 2 个文件**：

```bash
# ✅ 允许修改
package.json    # 仅修改 name 字段（品牌标识）
LICENSE         # 添加你的版权声明（法律要求）

# ❌ 禁止修改
src/            # 官方源码
internal-plugins/ # 官方插件
README.md       # 保持官方原样
其他配置文件     # 保持官方原样
```

**原因**：

- 减少与官方代码的差异
- 降低合并冲突风险
- 便于同步官方更新

---

### 2. 插件隔离原则

**所有商业功能在独立插件中开发**：

```
custom-plugins/
└── your-product-enterprise/
    ├── plugin.json        # 插件配置
    ├── package.json       # 独立依赖
    ├── src/               # 业务代码
    │   ├── main.ts
    │   └── App.vue
    └── index.html         # 入口文件
```

**优势**：

- 商业代码完全隔离
- 独立开发测试
- 灵活定价策略（免费版 + 付费插件）

---

### 3. 定期同步原则

**每周同步官方更新**：

```bash
# 同步流程
git fetch upstream          # 拉取官方更新
git merge upstream/main     # 合并到主分支
pnpm install                # 更新依赖
pnpm build && pnpm test     # 自动测试
```

**优势**：

- 获取最新功能和安全补丁
- 保持与官方版本兼容
- 零冲突（因为改动最小）

---

## 目录结构

### 完整目录树

```
STools/
├── src/                            # 官方源码（不修改）✅
│   ├── main/                       # 主进程
│   │   ├── api/                    # API 模块
│   │   ├── core/                   # 核心功能
│   │   └── managers/               # 管理器
│   ├── preload/                    # Preload 脚本
│   └── renderer/                   # 渲染进程
│
├── internal-plugins/               # 官方内置插件（不修改）✅
│   ├── system/                     # 系统插件
│   │   └── public/plugin.json      # 14 个系统功能
│   └── setting/                    # 设置插件
│       └── public/plugin.json      # 12 个设置功能
│
├── custom-plugins/                 # 你的插件（商业功能）✅
│   └── your-product-enterprise/    # 企业版插件
│       ├── plugin.json             # 插件配置
│       ├── package.json            # 独立依赖
│       ├── index.html              # 入口文件
│       └── src/                    # 业务代码
│           ├── main.ts             # 入口脚本
│           ├── App.vue             # 主组件
│           ├── features/           # 功能模块
│           │   ├── licensing/      # 许可证验证
│           │   ├── team/           # 团队协作
│           │   └── automation/     # 工作流自动化
│           └── utils/              # 工具函数
│
├── scripts/                        # 管理脚本 ✅
│   ├── dev-plugin.sh               # 开发插件
│   ├── build-plugin.sh             # 构建插件
│   └── sync-upstream.sh            # 同步官方更新
│
├── docs/                           # 文档目录 ✅
│   ├── ARCHITECTURE.md             # 架构方案（本文档）
│   ├── PLUGIN_DEVELOPMENT.md       # 插件开发指南
│   └── SYNC_STRATEGY.md            # 同步策略说明
│
├── package.json                    # 仅修改 name ✅
├── LICENSE                         # 已更新版权 ✅
├── PLUGIN_DEVELOPMENT.md           # 快速开发指南
└── .gitignore                      # Git 忽略配置
```

---

### 关键目录说明

#### 1. `src/` - 官方源码（不修改）

**包含**：

- 主进程代码（Electron Main Process）
- 渲染进程代码（Vue 3 应用）
- Preload 脚本
- 核心 API 实现

**原则**：

- ❌ 禁止修改任何文件
- ✅ 可以阅读理解代码
- ✅ 可以添加注释（不提交）

---

#### 2. `internal-plugins/` - 官方插件（不修改）

**包含**：

- `system/` - 系统工具插件（14 个功能）
- `setting/` - 设置管理插件（12 个功能）

**原则**：

- ❌ 禁止修改任何文件
- ✅ 可以参考学习
- ✅ 可以调用其 API

---

#### 3. `custom-plugins/` - 你的插件（商业功能）

**这是核心开发目录**：

```bash
custom-plugins/
└── your-product-enterprise/        # 企业版插件
    ├── plugin.json                 # 插件配置（定义功能入口）
    ├── package.json                # 独立依赖（Vue, Vite 等）
    ├── vite.config.ts              # Vite 配置
    ├── index.html                  # 入口 HTML
    ├── public/                     # 静态资源
    │   └── logo.png
    └── src/                        # 源代码
        ├── main.ts                 # 入口脚本（生命周期管理）
        ├── App.vue                 # 主组件
        ├── components/             # 组件
        ├── features/               # 功能模块
        │   ├── licensing/          # 许可证系统
        │   ├── team/               # 团队协作
        │   └── automation/         # 工作流自动化
        ├── composables/            # 组合式函数
        ├── stores/                 # 状态管理（Pinia）
        └── utils/                  # 工具函数
```

---

#### 4. `scripts/` - 管理脚本

**包含**：

```bash
scripts/
├── dev-plugin.sh       # 启动插件开发服务器
├── build-plugin.sh     # 构建插件为 .spk 文件
└── sync-upstream.sh    # 同步官方更新
```

**使用**：

```bash
# 开发插件
./scripts/dev-plugin.sh your-product-enterprise

# 构建插件
./scripts/build-plugin.sh your-product-enterprise

# 同步官方更新
./scripts/sync-upstream.sh
```

---

## 开发流程

### 1. 环境准备

```bash
# 1. 确保在项目根目录
cd /Users/wanglei/Documents/app/STools

# 2. 安装主程序依赖
pnpm install

# 3. 安装插件依赖
cd custom-plugins/your-product-enterprise
pnpm install
cd ../..

# 4. 启动主程序（后台运行）
pnpm dev &

# 5. 启动插件开发服务器
./scripts/dev-plugin.sh your-product-enterprise
```

---

### 2. 插件开发

#### 创建新插件

```bash
# 1. 创建插件目录
mkdir -p custom-plugins/my-new-plugin

# 2. 复制插件模板
cp -r custom-plugins/your-product-enterprise/* custom-plugins/my-new-plugin/

# 3. 修改 plugin.json
cd custom-plugins/my-new-plugin
# 编辑 plugin.json，修改 name、title、features 等

# 4. 开发插件
cd ../..
./scripts/dev-plugin.sh my-new-plugin
```

---

#### 插件配置文件（plugin.json）

```json
{
  "name": "your-product-enterprise",
  "title": "Your Product Enterprise",
  "description": "企业版商业功能",
  "author": "Your Company",
  "version": "1.0.0",
  "main": "index.html",
  "logo": "logo.png",
  "features": [
    {
      "code": "team-management",
      "explain": "团队管理",
      "icon": "logo.png",
      "cmds": ["团队管理", "Team Management"]
    },
    {
      "code": "workflow-automation",
      "explain": "工作流自动化",
      "icon": "logo.png",
      "cmds": ["自动化", "Automation"]
    }
  ]
}
```

**字段说明**：

- `name`: 插件唯一标识（小写，连字符分隔）
- `title`: 插件显示名称
- `features`: 功能入口列表
  - `code`: 功能代码（与代码中的 switch-case 匹配）
  - `cmds`: 触发关键词（支持拼音搜索）

---

#### 插件生命周期

```typescript
// src/main.ts

import { createApp } from 'vue'
import App from './App.vue'

createApp(App).mount('#app')

// 监听插件进入事件
window.ztools.onPluginEnter(async (action) => {
  console.log('Plugin entered:', action)

  // 根据 action.code 分发到不同功能
  switch (action.code) {
    case 'team-management':
      await handleTeamManagement(action)
      break
    case 'workflow-automation':
      await handleWorkflowAutomation(action)
      break
    default:
      console.warn('Unknown code:', action.code)
  }
})

// 监听插件离开事件（清理资源）
window.ztools.onPluginLeave(() => {
  console.log('Plugin leaving...')
  cleanup()
})
```

---

#### 核心 API 使用

```typescript
// 1. 数据持久化（自动添加命名空间）
await window.ztools.dbPut('config', { theme: 'dark' })
const config = await window.ztools.dbGet('config')

// 2. 显示通知
window.ztools.showNotification({
  title: '操作成功',
  body: '已保存配置'
})

// 3. 设置子输入框
window.ztools.setSubInput(
  {
    placeholder: '搜索...',
    value: ''
  },
  (text) => {
    console.log('用户输入:', text)
  }
)

// 4. 隐藏窗口
window.ztools.hideWindow()

// 5. 设置窗口高度
window.ztools.setExpendHeight(400)

// 6. 剪贴板操作
const text = await window.ztools.getClipboardText()
await window.ztools.setClipboardText('复制的内容')

// 7. AI 对话（需配置 AI 模型）
const stream = await window.ztools.aiChatCompletion({
  messages: [{ role: 'user', content: '你好' }],
  model: 'gpt-4'
})

// 8. 浏览器自动化
await window.ztools.zbrowserLaunch({ url: 'https://example.com' })
```

---

### 3. 构建和发布

#### 构建插件

```bash
# 构建并打包为 .spk 文件
./scripts/build-plugin.sh your-product-enterprise

# 输出：
# your-product-enterprise.spk
```

---

#### 安装插件

```bash
# 方法 1：在应用中安装
# 1. 打开 STools
# 2. 输入："已安装插件"
# 3. 点击"安装本地插件"
# 4. 选择 your-product-enterprise.spk 文件

# 方法 2：自动安装（开发环境）
# 修改 plugin.json，添加：
{
  "development": {
    "main": "http://localhost:5177"
  }
}
# 启动开发服务器后，插件会自动加载
```

---

#### 发布流程

```bash
# 1. 更新版本号
cd custom-plugins/your-product-enterprise
npm version patch  # 1.0.0 → 1.0.1

# 2. 更新 CHANGELOG
# 编辑 CHANGELOG.md

# 3. 构建插件
cd ../..
./scripts/build-plugin.sh your-product-enterprise

# 4. 提交代码
git add .
git commit -m "feat: 发布插件 v1.0.1"
git tag -a plugin-v1.0.1 -m "Plugin Release v1.0.1"
git push origin main --tags

# 5. 创建 GitHub Release
gh release create plugin-v1.0.1 \
  your-product-enterprise.spk \
  --title "Plugin v1.0.1" \
  --notes "更新内容..."
```

---

## 版本同步

### 同步策略

**频率**：每周同步一次（推荐周一）

**原因**：

- 获取最新功能
- 获取安全补丁
- 保持兼容性

---

### 同步流程

```bash
# 1. 拉取官方最新代码
git fetch upstream

# 2. 查看更新日志
git log HEAD..upstream/main --oneline

# 3. 创建同步分支
git checkout -b sync/ztools-v3.0.3

# 4. 合并官方更新
git merge upstream/main

# 5. 解决冲突（如果有）
# 由于改动最小，冲突极少

# 6. 测试验证
pnpm install
pnpm build
pnpm test

# 7. 创建 PR
git push origin sync/ztools-v3.0.3
# GitHub 上创建 PR → main

# 8. 审查通过后合并
```

---

### 冲突解决

**冲突极少的原因**：

- 主仓库仅修改 2 个文件
- 商业功能在独立插件中
- 官方更新不会覆盖插件目录

**如果出现冲突**：

```bash
# 冲突文件通常是：
package.json

# 解决方法：
# 1. 保留你的 name 字段
# 2. 更新其他字段为官方版本
# 3. 重新提交
git add package.json
git commit -m "merge: 解决 package.json 冲突"
```

---

### 自动化同步脚本

```bash
#!/bin/bash
# scripts/sync-upstream.sh

set -e

echo "📥 同步官方更新"

git fetch upstream

echo ""
echo "官方更新日志："
git log HEAD..upstream/main --oneline --decorate

echo ""
read -p "确认合并官方更新？(y/n) " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    git merge upstream/main --no-edit

    echo ""
    echo "✅ 合并完成"
    echo ""
    echo "开始测试验证..."

    pnpm install
    pnpm build

    if pnpm test; then
        echo ""
        echo "✅ 测试通过"
        echo ""
        echo "请手动测试插件功能："
        echo "  ./scripts/dev-plugin.sh your-product-enterprise"
    else
        echo ""
        echo "❌ 测试失败，请检查"
    fi
else
    echo "❌ 取消合并"
fi
```

---

## 最佳实践

### 1. 数据隔离

**使用命名空间**：

```typescript
// ✅ 好的做法
const NAMESPACE = 'YOUR_PRODUCT/'

await window.ztools.dbPut(NAMESPACE + 'config', data)
const config = await window.ztools.dbGet(NAMESPACE + 'config')

// ❌ 避免
await window.ztools.dbPut('config', data) // 可能与其他插件冲突
```

---

### 2. 错误处理

```typescript
// ✅ 完善的错误处理
try {
  const result = await window.ztools.dbGet('key')
  if (!result) {
    // 处理空值
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

---

### 3. 性能优化

```typescript
// ✅ 节流搜索
import { debounce } from 'lodash-es'

const debouncedSearch = debounce(async (query: string) => {
  // 执行搜索
}, 300)

// 在输入框使用
<input @input="debouncedSearch($event.target.value)" />
```

---

### 4. 类型安全

```typescript
// ✅ 使用 TypeScript 类型
interface PluginConfig {
  theme: 'light' | 'dark'
  language: string
  autoSave: boolean
}

async function loadConfig(): Promise<PluginConfig> {
  const config = await window.ztools.dbGet<PluginConfig>('config')
  return config || defaultConfig
}
```

---

### 5. 响应式高度管理

```typescript
// ✅ 动态调整窗口高度
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

### 6. 命名规范

```typescript
// ✅ 插件命名
custom -
  plugins / your -
  product -
  enterprise / // 小写连字符
    // ✅ 功能代码命名
    'team-management' // 小写连字符
;('workflow-automation')

// ✅ 组件命名
TeamManagement.vue // PascalCase
WorkflowBuilder.vue

// ✅ 文件命名
useTeamData.ts // camelCase (composables)
teamStore.ts // camelCase (stores)
```

---

## 故障排除

### 问题 1：插件无法加载

**症状**：

- 插件列表中看不到插件
- 控制台报错："Plugin not found"

**原因**：

- plugin.json 配置错误
- 开发服务器未启动
- 端口被占用

**解决**：

```bash
# 1. 检查 plugin.json 格式
cat custom-plugins/your-product-enterprise/plugin.json | jq .

# 2. 检查开发服务器
curl http://localhost:5177

# 3. 检查端口占用
lsof -i :5177

# 4. 重启开发服务器
./scripts/dev-plugin.sh your-product-enterprise
```

---

### 问题 2：同步冲突

**症状**：

- `git merge upstream/main` 报错
- 提示 package.json 冲突

**原因**：

- 官方修改了 package.json
- 你的改动与官方冲突

**解决**：

```bash
# 1. 查看冲突
git status

# 2. 手动解决冲突
# 编辑 package.json，保留你的 name，其他用官方的

# 3. 提交解决
git add package.json
git commit -m "merge: 解决 package.json 冲突"
```

---

### 问题 3：插件 API 调用失败

**症状**：

- `window.ztools.xxx is not a function`
- 控制台报错 API 不存在

**原因**：

- API 版本不匹配
- ZTools 版本过低

**解决**：

```bash
# 1. 检查 ZTools 版本
cat package.json | grep version

# 2. 查看官方 API 文档
cat CLAUDE.md

# 3. 更新到最新版本
git fetch upstream
git merge upstream/main

# 4. 检查 API 类型定义
cat ztools-api-types/index.d.ts
```

---

### 问题 4：构建失败

**症状**：

- `pnpm build` 报错
- 提示依赖缺失

**原因**：

- 依赖未安装
- 版本冲突

**解决**：

```bash
# 1. 清理并重新安装
rm -rf node_modules pnpm-lock.yaml
pnpm install

# 2. 检查插件依赖
cd custom-plugins/your-product-enterprise
pnpm install

# 3. 更新依赖
pnpm update

# 4. 重新构建
cd ../..
pnpm build
```

---

## 参考资源

### 官方文档

- `CLAUDE.md` - ZTools 完整技术文档
- `ztools-api-types/` - 插件 API 类型定义
- `internal-plugins/setting/` - 复杂 UI 插件示例
- `internal-plugins/system/` - 无界面插件示例

---

### 相关文档

- [插件开发指南](./PLUGIN_DEVELOPMENT.md)
- [版本同步策略](./SYNC_STRATEGY.md)
- [商业化指南](../commercial-guide.md)

---

## 总结

### 核心要点

1. ✅ **主仓库最小改动**（仅 2 个文件）
2. ✅ **商业功能独立插件**（custom-plugins/）
3. ✅ **定期同步更新**（每周，零冲突）
4. ✅ **遵循最佳实践**（命名规范、类型安全）

---

### 优势总结

| 方面         | 优势                   |
| ------------ | ---------------------- |
| **维护成本** | ✅ 极低（改动最小）    |
| **同步冲突** | ✅ 几乎为零            |
| **开发效率** | ✅ 插件独立开发        |
| **合规性**   | ✅ 完全符合 MIT 许可证 |
| **灵活性**   | ✅ 独立定价策略        |

---

### 下一步

1. 阅读 [插件开发指南](./PLUGIN_DEVELOPMENT.md)
2. 开始开发你的第一个商业插件
3. 定期同步官方更新
4. 发布并收费

---

**祝你商业化成功！** 🚀
