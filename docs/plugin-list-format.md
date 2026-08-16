# ZTools 插件列表格式规范

本文档描述 ZTools 中各类"插件列表"的数据格式，涵盖：

1. **插件市场列表**（远程市场 API 返回、本地 LMDB 缓存）
2. **已安装插件列表**（本地安装记录）
3. **内置插件列表**（随包内置，代码硬编码）
4. **开发插件注册表**（开发中项目登记）
5. **插件清单 plugin.json**（所有列表字段的源头）

适用读者：插件开发者、插件市场后端开发者、客户端功能维护者。

---

## 1. 列表总览

| 列表类型       | 数据来源       | 存储位置                           | 核心类型                        |
| -------------- | -------------- | ---------------------------------- | ------------------------------- |
| 市场插件列表   | 远程市场 API   | LMDB（`plugin-market-data` 等）    | `PluginMarketPlugin[]`          |
| 已安装插件列表 | 用户安装/导入  | LMDB（`plugins` 键）               | `PluginInstallRecord[]`         |
| 内置插件列表   | 代码硬编码     | `src/main/core/internalPlugins.ts` | `BUNDLED_INTERNAL_PLUGIN_NAMES` |
| 开发插件注册表 | 开发者登记     | LMDB（开发注册表键）               | `DevProjectRegistry`            |
| 插件清单       | 插件目录内文件 | `<plugin-dir>/public/plugin.json`  | `PluginManifestSnapshot`        |

### 数据流向

```text
插件作者编写 plugin.json（清单）
        │
        ├── 发布到市场服务端 ──► GET /api/market/plugins ──► PluginMarketPlugin[] ──► LMDB 缓存
        │
        └── 用户安装 ──► PluginInstallRecord[] ──► LMDB (ZTOOLS/plugins)
```

---

## 2. 插件清单 plugin.json（源头格式）

每个插件目录下都有一个 `plugin.json`（UI 插件位于 `public/plugin.json`），它是所有列表数据的字段源头。

### 2.1 字段定义

| 字段          | 类型                                  | 必填 | 说明                                                 |
| ------------- | ------------------------------------- | ---- | ---------------------------------------------------- |
| `$schema`     | string                                | 否   | JSON Schema 校验地址（开发工具用）                   |
| `name`        | string                                | 是   | 插件唯一标识（英文，全局唯一）                       |
| `title`       | string                                | 是   | 显示名称                                             |
| `version`     | string                                | 是   | 语义化版本号，格式 `x.y.z`                           |
| `description` | string                                | 否   | 插件描述                                             |
| `author`      | string                                | 否   | 作者                                                 |
| `homepage`    | string                                | 否   | 主页链接                                             |
| `logo`        | string                                | 否   | 图标路径（相对路径或 URL）                           |
| `main`        | string                                | 否\* | UI 插件入口文件（如 `index.html`）；无界面插件不声明 |
| `preload`     | string                                | 否   | preload 脚本路径                                     |
| `platform`    | string[]                              | 否   | 支持平台，如 `["win32", "darwin", "linux"]`          |
| `development` | `{ main?: string }`                   | 否   | 开发模式专用入口（如 `http://localhost:5177`）       |
| `features`    | `Feature[]`                           | 否   | 功能列表（见 §2.3）                                  |
| `providers`   | `Record<string, ProviderDeclaration>` | 否   | Provider 声明（翻译/OCR 等，见 §2.4）                |
| `tools`       | `Record<string, ToolDeclaration>`     | 否   | MCP 工具声明（无界面插件专用）                       |

> \* `main` 与 `development.main` 至少其一存在时视为 UI 插件；两者都无则为无界面插件。

### 2.2 完整示例（UI 插件）

```json
{
  "$schema": "node_modules/@ztools-center/ztools-api-types/resource/ztools.schema.json",
  "name": "setting",
  "title": "设置",
  "description": "ZTools 内置设置插件",
  "author": "Zing",
  "version": "1.0.0",
  "main": "index.html",
  "logo": "logo.png",
  "development": {
    "main": "http://localhost:5177"
  },
  "features": [
    {
      "code": "ui.router?router=GeneralSetting",
      "explain": "应用设置",
      "icon": "logo.png",
      "cmds": ["通用设置"]
    },
    {
      "code": "function.plugin-market-search",
      "explain": "插件市场搜索",
      "icon": "logo.png",
      "cmds": [
        {
          "type": "over",
          "label": "插件市场搜索",
          "minLength": 1,
          "maxLength": 50
        }
      ]
    }
  ]
}
```

### 2.3 Feature 结构

| 字段       | 类型                         | 必填 | 说明                                   |
| ---------- | ---------------------------- | ---- | -------------------------------------- |
| `code`     | string                       | 是   | 功能代码（如 `ui.router?router=xxx`）  |
| `explain`  | string                       | 否   | 功能说明                               |
| `icon`     | string                       | 否   | 功能图标                               |
| `cmds`     | `Array<string \| CmdObject>` | 否   | 触发指令（支持拼音搜索）               |
| `mainPush` | boolean                      | 否   | 是否 mainPush 功能（插件主动推送结果） |
| `platform` | string \| string[]           | 否   | 平台限制                               |
| `mainHide` | boolean                      | 否   | 是否在主界面隐藏                       |

### 2.4 cmds 指令 6 种类型

| 类型     | 形式                                                                                | 说明                     |
| -------- | ----------------------------------------------------------------------------------- | ------------------------ |
| 字符串   | `"截图"` 或 `["截图", "Screenshot"]`                                                | 文本指令，支持拼音搜索   |
| `regex`  | `{ type: 'regex', match, label?, minLength? }`                                      | 正则匹配用户输入         |
| `over`   | `{ type: 'over', label?, exclude?, minLength?, maxLength? }`                        | 匹配任意文本（可设排除） |
| `img`    | `{ type: 'img', label? }`                                                           | 匹配粘贴的图片           |
| `files`  | `{ type: 'files', label?, fileType?, extensions?, match?, minLength?, maxLength? }` | 匹配粘贴的文件           |
| `window` | `{ type: 'window', label?, match: { app?, title?, className? } }`                   | 匹配当前活动窗口         |

**files 类型示例**（安装插件：仅接受 `.zpx`/`.zip` 文件）：

```json
{
  "type": "files",
  "label": "安装插件",
  "fileType": "file",
  "match": "/\\.(zpx|zip)$/i",
  "maxLength": 1
}
```

**window 类型示例**：

```json
{
  "type": "window",
  "label": "窗口操作",
  "match": {
    "app": ["chrome", "safari"],
    "title": ".*编辑器"
  }
}
```

### 2.5 Provider 声明示例

```json
{
  "name": "provider-example",
  "title": "Provider 示例",
  "version": "1.0.0",
  "main": "index.html",
  "preload": "preload.js",
  "logo": "logo.png",
  "providers": {
    "translation": {
      "type": "translation",
      "label": "示例翻译",
      "description": "Mock 翻译 provider"
    },
    "ocr": {
      "type": "ocr",
      "label": "示例 OCR",
      "description": "Mock OCR provider"
    }
  }
}
```

---

## 3. 插件市场列表格式

### 3.1 数据来源

- **远程 API 基址**：`https://z-tools.top/api/market`（常量 `DEFAULT_PLUGIN_MARKET_API_BASE`）
- **端点**：`GET /plugins`（聚合数据）、`GET /plugins/recommendations`、`GET /plugins/latest`、`GET /plugins/comments`、`GET /plugins/readme`
- **本地缓存**：LMDB 键 `plugin-market-data`（插件列表）、`plugin-market-version`（时间戳）、`plugin-market-storefront`（首页视图数据）
- 认证：需要登录的接口携带 `Authorization: Bearer <token>`，401 时自动刷新并重试

### 3.2 PluginMarketPlugin（市场插件项）

> 定义位置：`src/main/api/renderer/pluginMarket.ts`

```typescript
export type PluginMarketPlugin = {
  name: string // 插件唯一标识（必填）
  version: string // 语义化版本号（必填）
  title?: string // 显示标题
  description?: string // 描述
  logo?: string // 图标 URL 或 Data URI
  author?: string // 作者
  homepage?: string // 主页链接
  size?: number // 安装包大小（字节）
  downloadCount?: number // 下载次数
  updatedAt?: number // 更新时间戳（毫秒）
  publishedAt?: number // 发布时间戳（毫秒）
  categoryId?: number | null // 分类 ID
  categoryTitle?: string // 分类名称
  [key: string]: unknown // 扩展字段（允许服务端附加自定义字段）
}
```

**JSON 示例**：

```json
{
  "name": "morse-code",
  "version": "1.0.0",
  "title": "莫斯密码",
  "description": "莫斯密码加解密工具，支持加密、解密、对照表查看，一键复制结果。",
  "logo": "data:image/svg+xml,...",
  "author": "Zing",
  "homepage": "https://github.com/example/morse-code",
  "size": 51200,
  "downloadCount": 1520,
  "publishedAt": 1700000000000,
  "updatedAt": 1700000000000,
  "categoryId": 1,
  "categoryTitle": "效率工具"
}
```

### 3.3 市场首页视图数据 PluginMarketStorefront

```typescript
type PluginMarketStorefront = {
  sections: PluginMarketStorefrontSection[] // 首页布局区域（按顺序渲染）
  categories: Record<string, PluginMarketStorefrontCategory> // 分类详情（key 为索引）
  categoryLayouts: Record<string, PluginMarketCategoryLayoutSection[]> // 分类页布局
}
```

**sections 区域类型（联合类型）**：

| type         | 结构                                                                          | 说明             |
| ------------ | ----------------------------------------------------------------------------- | ---------------- |
| `banner`     | `{ image, url? }[]`，可选 `height`                                            | 轮播图           |
| `navigation` | 分类数组：`{ key, title, description?, icon?, showDescription, pluginCount }` | 分类导航         |
| `fixed`      | `{ key, title?, plugins: PluginMarketPlugin[] }`                              | 固定插件列表     |
| `random`     | `{ key, title?, plugins: PluginMarketPlugin[] }`                              | 随机推荐插件列表 |

### 3.4 市场 API 返回包装

```typescript
export type PluginMarketResult = {
  success: boolean
  data?: PluginMarketPlugin[] // 全量插件列表（原始数据，未按平台过滤）
  storefront?: PluginMarketStorefront // 构建好的首页视图数据（平台已过滤）
  error?: string
}
```

---

## 4. 已安装插件列表格式

### 4.1 存储

- **位置**：LMDB 数据库（`ZTOOLS/` 命名空间）
- **键名**：`plugins`
- **读写**：`databaseAPI.dbGet('plugins')` / `databaseAPI.dbPut('plugins', plugins)`

### 4.2 PluginInstallRecord（安装记录）

> 定义位置：`src/main/api/renderer/pluginDevelopmentRegistry.ts`

```typescript
export type PluginInstallRecord = {
  name?: string // 插件唯一名称
  title?: string // 显示名称
  version?: string // 版本号
  description?: string // 描述
  author?: string // 作者
  homepage?: string // 主页
  logo?: string // logo 路径
  main?: string // 入口文件（相对路径）
  preload?: string // preload 脚本
  features?: any[] // 功能列表
  path?: string // 插件本地目录路径
  isDevelopment?: boolean // 是否为开发模式安装
  installedAt?: string // 安装时间（ISO 格式）
}
```

### 4.3 PluginItem（UI 侧展示模型）

> 定义位置：`internal-plugins/setting/src/components/common/PluginDetail/types.ts`

```typescript
export interface PluginItem {
  name: string // 插件名
  title: string // 标题
  version?: string // 版本
  description?: string // 描述
  logo?: string // 图标
  features?: PluginFeature[] // 功能列表
  installed?: boolean // 是否已安装
  isDevelopment?: boolean // 是否开发版
  localVersion?: string // 本地版本
  path?: string // 本地路径
  size?: number // 文件大小
  downloadCount?: number // 下载次数
  author?: string // 作者
  homepage?: string // 主页
}

export interface PluginFeature {
  code: string // 功能码
  name?: string // 功能名
  explain?: string // 功能说明
  icon?: string // 功能图标
  cmds?: any[] // 触发指令数组（6 种类型，见 §2.4）
  mainPush?: boolean // 是否 mainPush 功能
}
```

> 注意区分：`PluginInstallRecord` 是**持久化存储格式**，`PluginItem` 是**渲染层展示模型**（由存储格式 + 市场信息合并而来）。

---

## 5. 内置插件列表格式

### 5.1 定义

> 定义位置：`src/main/core/internalPlugins.ts`

```typescript
export const BUNDLED_INTERNAL_PLUGIN_NAMES = [
  'setting',
  'system',
  'morse-code',
  'fullscreen-demo',
  'pdf-toolkit'
] as const

export const INTERNAL_API_PLUGIN_NAMES = [
  ...BUNDLED_INTERNAL_PLUGIN_NAMES,
  'ztools-developer-plugin__dev',
  'ztools-developer-plugin'
] as const
```

### 5.2 说明

| 列表                            | 含义                                                     |
| ------------------------------- | -------------------------------------------------------- |
| `BUNDLED_INTERNAL_PLUGIN_NAMES` | 随包内置插件，启动时自动装载                             |
| `INTERNAL_API_PLUGIN_NAMES`     | 允许调用 `window.ztools.internal` 的插件（含自定义扩展） |

### 5.3 加载路径

- **开发环境**：`<project-root>/internal-plugins/<name>`
- **生产环境**：`<resourcesPath>/app.asar.unpacked/internal-plugins/<name>`

---

## 6. 开发插件注册表格式

### 6.1 DevProjectRecord（单个开发项目）

> 定义位置：`src/main/api/renderer/pluginDevelopmentRegistry.ts`

```typescript
export type DevProjectRecord = {
  name: string // 项目名称（与 plugin.json 的 name 一致）
  configSnapshot: PluginManifestSnapshot // plugin.json 快照（配置文件不可用时的兜底信息）
  addedAt: string // 首次登记时间（ISO 格式）
  updatedAt: string // 最近更新时间（ISO 格式）
  sortOrder: number // 排序序号，数值越小越靠前
  projectPath: string | null // 项目目录绝对路径
  configPath: string | null // plugin.json 绝对路径
  status: DevProjectBindingStatus // 绑定状态
  lastValidatedAt: string // 最近一次校验时间
  lastError?: string // 最近一次校验错误信息
}
```

### 6.2 绑定状态

```typescript
export type DevProjectBindingStatus =
  | 'ready' // 配置有效，可正常使用
  | 'config_missing' // plugin.json 文件缺失
  | 'invalid_config' // plugin.json 内容无效（缺少 name、name 不匹配等）
  | 'unbound' // 项目未绑定有效路径
```

### 6.3 PluginManifestSnapshot（清单快照）

`plugin.json` 的字段子集，仅保留注册表需要的字段：

```typescript
export type PluginManifestSnapshot = {
  name?: string
  title?: string
  version?: string
  description?: string
  author?: string
  homepage?: string
  logo?: string
  preload?: string
  features?: any[]
  development?: { main?: string } // 开发模式专用配置
  platform?: string[] // 可运行平台列表
}
```

---

## 7. 附：LMDB 键名速查

| 键名                                          | 内容                     |
| --------------------------------------------- | ------------------------ |
| `ZTOOLS/plugins`                              | 已安装插件列表           |
| `ZTOOLS/plugin-market-data`                   | 市场插件列表（原始数据） |
| `ZTOOLS/plugin-market-version`                | 市场数据缓存时间戳       |
| `ZTOOLS/plugin-market-storefront`             | 市场首页视图数据         |
| `ZTOOLS/plugin-market-storefront-fingerprint` | 首页缓存指纹             |
| `ZTOOLS/dev-plugin-registry`                  | 开发插件注册表           |

---

## 8. 相关文件索引

| 文件                                                 | 作用                              |
| ---------------------------------------------------- | --------------------------------- |
| `src/main/api/renderer/pluginMarket.ts`              | 市场数据获取、类型定义、Mock 数据 |
| `src/main/api/renderer/pluginMarketConfig.ts`        | 市场 API 基址与认证               |
| `src/main/api/renderer/pluginDevelopmentRegistry.ts` | 安装记录/清单快照/开发注册表类型  |
| `src/main/api/renderer/plugins.ts`                   | 插件安装/删除/列表管理            |
| `src/main/core/internalPlugins.ts`                   | 内置插件名称列表                  |
| `internal-plugins/setting/public/plugin.json`        | plugin.json 完整示例              |
| `docs/plugin-market-backend-spec.md`                 | 市场后端接口规范（服务端视角）    |

> 补充说明：`ztools-api-types/` 子模块（对外发布的插件 API 类型）在仓库中尚未初始化，其 `resource/ztools.schema.json` 为 plugin.json 的完整 JSON Schema 校验定义。
