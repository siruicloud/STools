# 插件市场数据结构说明

本文档描述 ZTools 插件市场的核心数据结构，包括 Banner 轮播图、插件列表、首页视图等。

## 目录

- [Banner 数据结构](#banner-数据结构)
- [插件数据结构](#插件数据结构)
- [首页视图数据结构](#首页视图数据结构)
- [分类数据结构](#分类数据结构)
- [评论数据结构](#评论数据结构)
- [API 响应类型](#api-响应类型)

---

## Banner 数据结构

### PluginMarketBannerItem

轮播图项，用于首页 Banner 展示。

```typescript
type PluginMarketBannerItem = {
  /** 轮播图图片 URL（必填） */
  image: string
  /** 点击跳转链接（可选） */
  url?: string
}
```

**前端使用示例**：

```vue
<template>
  <div class="banner">
    <img :src="banner.image" alt="" />
    <button @click="handleClick(banner.url)">点击跳转</button>
  </div>
</template>

<script setup>
function handleClick(url) {
  if (url) {
    window.ztools.shellOpenExternal(url)
  }
}
</script>
```

### MarketBannerResponse

市场 API 返回的 Banner 原始数据格式。

```typescript
type MarketBannerResponse = {
  /** Banner 标题（可选，用于辅助说明） */
  title?: string
  /** Banner 图片 URL */
  imageUrl?: string
  /** 点击跳转链接 */
  linkUrl?: string
}
```

**转换关系**：

```typescript
// MarketBannerResponse → PluginMarketBannerItem
const bannerItem: PluginMarketBannerItem = {
  image: response.imageUrl || '',
  url: response.linkUrl || undefined
}
```

---

## 插件数据结构

### PluginMarketPlugin

插件市场中单个插件的描述信息，来自 ZTools 线上市场 API。

```typescript
type PluginMarketPlugin = {
  /** 插件唯一标识符（必填） */
  name: string
  /** 插件版本号（必填，如 "1.0.0"） */
  version: string
  /** 插件显示名称（可选） */
  title?: string
  /** 插件描述（可选） */
  description?: string
  /** 插件 Logo URL 或 base64 数据（可选） */
  logo?: string
  /** 插件作者（可选） */
  author?: string
  /** 插件主页 URL（可选） */
  homepage?: string
  /** 插件包大小（字节，可选） */
  size?: number
  /** 下载次数（可选） */
  downloadCount?: number
  /** 更新时间（时间戳，毫秒） */
  updatedAt?: number
  /** 发布时间（时间戳，毫秒） */
  publishedAt?: number
  /** 分类 ID（可选） */
  categoryId?: number | null
  /** 分类名称（可选） */
  categoryTitle?: string
  /** 其他扩展字段（允许服务端返回额外信息） */
  [key: string]: unknown
}
```

**字段说明**：

| 字段            | 类型             | 必填 | 说明                                     |
| --------------- | ---------------- | ---- | ---------------------------------------- |
| `name`          | `string`         | ✅   | 插件唯一标识，用于安装、更新、数据库隔离 |
| `version`       | `string`         | ✅   | 语义化版本号，用于升级判断               |
| `title`         | `string`         | ❌   | 用户可见的显示名称                       |
| `description`   | `string`         | ❌   | 插件功能简介                             |
| `logo`          | `string`         | ❌   | 支持图片 URL 或 base64 Data URL          |
| `author`        | `string`         | ❌   | 插件开发者名称                           |
| `homepage`      | `string`         | ❌   | 项目主页或源码地址                       |
| `size`          | `number`         | ❌   | 插件包体积（字节），用于显示下载大小     |
| `downloadCount` | `number`         | ❌   | 累计下载次数                             |
| `updatedAt`     | `number`         | ❌   | 最后更新时间戳（毫秒）                   |
| `publishedAt`   | `number`         | ❌   | 首次发布时间戳（毫秒）                   |
| `categoryId`    | `number \| null` | ❌   | 所属分类 ID                              |
| `categoryTitle` | `string`         | ❌   | 所属分类名称                             |

**前端扩展字段**：

在前端渲染时，插件数据会被扩展为以下结构：

```typescript
// 内置插件 setting 中的扩展类型
interface Plugin extends PluginMarketPlugin {
  /** 是否已安装 */
  installed: boolean
  /** 已安装插件的本地路径（安装后填充） */
  path?: string
  /** 本地安装版本（用于升级对比） */
  localVersion?: string
}
```

---

## 首页视图数据结构

### PluginMarketStorefront

插件市场完整的首页视图数据，包含布局区域、分类详情和布局配置。

```typescript
type PluginMarketStorefront = {
  /** 首页布局区域列表（按顺序渲染） */
  sections: PluginMarketStorefrontSection[]
  /** 所有分类的详细信息，以 key 为索引 */
  categories: Record<string, PluginMarketStorefrontCategory>
  /** 各分类详情页的布局配置 */
  categoryLayouts: Record<string, PluginMarketCategoryLayoutSection[]>
}
```

**数据流向**：

```
API 响应 → buildPluginMarketStorefront() → PluginMarketStorefront
                                              ↓
                                      前端渲染首页
```

### PluginMarketStorefrontSection

首页布局区域，是一个**联合类型**，支持 4 种区块类型。

```typescript
type PluginMarketStorefrontSection =
  | {
      /** Banner 轮播图区块 */
      type: 'banner'
      /** 区块唯一标识 */
      key: string
      /** 轮播图项列表 */
      items: PluginMarketBannerItem[]
      /** Banner 高度（像素，可选） */
      height?: number
    }
  | {
      /** 分类导航区块 */
      type: 'navigation'
      key: string
      /** 区块标题（可选） */
      title?: string
      /** 分类卡片列表 */
      categories: Array<{
        key: string
        title: string
        description?: string
        icon?: string
        showDescription: boolean
        pluginCount: number
      }>
    }
  | {
      /** 固定插件列表区块 */
      type: 'fixed'
      key: string
      title?: string
      /** 插件列表（已按平台过滤） */
      plugins: PluginMarketPlugin[]
    }
  | {
      /** 随机推荐插件区块 */
      type: 'random'
      key: string
      title?: string
      plugins: PluginMarketPlugin[]
    }
```

**区块类型说明**：

| 类型         | 用途                   | 可点击元素               |
| ------------ | ---------------------- | ------------------------ |
| `banner`     | 轮播图广告位           | 图片点击跳转 URL         |
| `navigation` | 分类导航入口           | 分类卡片点击进入分类详情 |
| `fixed`      | 固定插件列表           | 插件卡片点击进入详情     |
| `random`     | 随机推荐插件（可刷新） | 插件卡片点击进入详情     |

**渲染示例**：

```vue
<template>
  <div class="storefront">
    <template v-for="section in sections" :key="section.key">
      <!-- Banner 区块 -->
      <div v-if="section.type === 'banner'" class="banner">
        <img v-for="item in section.items" :src="item.image" />
      </div>

      <!-- 分类导航区块 -->
      <div v-else-if="section.type === 'navigation'" class="navigation">
        <CategoryCard v-for="cat in section.categories" :key="cat.key" />
      </div>

      <!-- 插件列表区块 -->
      <div v-else-if="section.type === 'fixed' || section.type === 'random'">
        <PluginCard v-for="plugin in section.plugins" :key="plugin.name" />
      </div>
    </template>
  </div>
</template>
```

---

## 分类数据结构

### PluginMarketStorefrontCategory

插件市场分类详情。

```typescript
type PluginMarketStorefrontCategory = {
  /** 分类唯一标识 */
  key: string
  /** 分类显示名称 */
  title: string
  /** 分类描述（可选） */
  description?: string
  /** 分类图标 URL 或 base64（可选） */
  icon?: string
  /** 该分类下的插件列表（已按平台过滤） */
  plugins: PluginMarketPlugin[]
}
```

### PluginMarketCategoryLayoutSection

分类详情页的布局配置，用于自定义分类页展示方式。

```typescript
type PluginMarketCategoryLayoutSection = {
  /** 区域类型：list（列表） / fixed（固定） / random（随机） */
  type: string
  /** 区域标题（支持模板字符串） */
  title?: string
  /** 插件数量限制 */
  count?: number
  /** 固定插件名称列表（type 为 fixed 时使用） */
  plugins?: string[]
}
```

**模板字符串示例**：

```typescript
const layout: PluginMarketCategoryLayoutSection = {
  type: 'list',
  title: '${title}系列，共${count}个工具' // 替换为 "效率工具系列，共 5 个工具"
}
```

### MarketCategoryResponse

市场 API 返回的分类原始数据。

```typescript
type MarketCategoryResponse = {
  /** 分类 ID */
  id?: number
  /** 分类名称 */
  title?: string
  /** 分类描述 */
  description?: string
  /** 分类 Logo */
  logo?: string
  /** 该分类下的插件列表 */
  plugins?: PluginMarketPlugin[]
}
```

---

## 评论数据结构

### PluginMarketCommentItem

插件评论项。

```typescript
type PluginMarketCommentItem = {
  /** 评论 ID */
  id: number
  /** 所属插件名称 */
  pluginName: string
  /** 评论者用户 ID */
  uid: string
  /** 评论者昵称 */
  nickname: string
  /** 评论者头像 URL（可选） */
  avatarUrl?: string
  /** 父评论 ID（回复时使用，可选） */
  parentId?: number | null
  /** 父评论信息（嵌套回复时使用） */
  parent?: PluginMarketCommentParent | null
  /** 评论内容 */
  content: string
  /** 点赞数 */
  likeCount: number
  /** 当前用户是否已点赞 */
  liked: boolean
  /** 是否已删除 */
  deleted?: boolean
  /** 创建时间（时间戳） */
  createdAt: number
  /** 更新时间（时间戳） */
  updatedAt: number
}
```

### PluginMarketCommentParent

评论中被回复的父评论信息。

```typescript
type PluginMarketCommentParent = {
  id: number
  uid: string
  nickname: string
  avatarUrl?: string
  content: string
  deleted: boolean
  createdAt: number
}
```

### PluginMarketCommentPage

评论分页数据。

```typescript
type PluginMarketCommentPage = {
  /** 评论列表 */
  items: PluginMarketCommentItem[]
  /** 分页信息 */
  page: {
    page: number
    pageSize: number
    total: number
  }
}
```

---

## API 响应类型

### PluginMarketResult

`fetchPluginMarket()` 的返回结果。

```typescript
type PluginMarketResult = {
  /** 请求是否成功 */
  success: boolean
  /** 全量插件列表（原始数据，未按平台过滤） */
  data?: PluginMarketPlugin[]
  /** 构建好的首页视图数据（平台已过滤） */
  storefront?: PluginMarketStorefront
  /** 错误信息（失败时） */
  error?: string
}
```

### PluginMarketLatestResult

检查单个插件最新版本的返回结果。

```typescript
type PluginMarketLatestResult = {
  /** 当前平台是否有可用更新 */
  available: boolean
  /** 不可用原因（available 为 false 时） */
  reason?: 'not_found' | 'unsupported_platform'
  /** 最新版本插件信息（available 为 true 时） */
  plugin?: PluginMarketPlugin
}
```

### MarketPluginsResponse

市场聚合 API 的原始响应格式。

```typescript
type MarketPluginsResponse = {
  /** Banner 列表 */
  banners?: MarketBannerResponse[]
  /** 分类列表（包含插件） */
  categories?: MarketCategoryResponse[]
  /** 最新发布插件列表 */
  latest?: PluginMarketPlugin[]
}
```

---

## 数据转换流程

### 1. API 响应 → Storefront 视图数据

```typescript
// pluginMarket.ts 中的转换逻辑
function buildPluginMarketStorefront(
  marketData: MarketPluginsResponse,
  recommendations: PluginMarketPlugin[]
): PluginMarketStorefront {
  // 1. 构建 categories 映射
  const categories: Record<string, PluginMarketStorefrontCategory> = {}
  for (const category of marketData.categories || []) {
    const key = String(category.id)
    categories[key] = {
      key,
      title: category.title || key,
      description: category.description,
      icon: category.logo,
      plugins: (category.plugins || []).filter((p) => !!p?.name)
    }
  }

  // 2. 构建 sections 列表
  const sections: PluginMarketStorefrontSection[] = []

  // Banner 区块
  if (marketData.banners?.length) {
    sections.push({
      type: 'banner',
      key: 'banner-0',
      items: marketData.banners.map((b) => ({
        image: b.imageUrl || '',
        url: b.linkUrl
      })),
      height: 160
    })
  }

  // 分类导航区块
  // ...

  // 最新发布区块
  if (marketData.latest?.length) {
    sections.push({
      type: 'fixed',
      key: 'latest-0',
      title: '最新发布',
      plugins: marketData.latest
    })
  }

  return { sections, categories, categoryLayouts: { default: [{ type: 'list' }] } }
}
```

### 2. 插件数据添加安装状态

```typescript
// PluginMarketSetting.vue 中的处理逻辑
function enrichPlugins(
  marketPlugins: MarketPlugin[],
  installedPlugins: InstalledPlugin[]
): Plugin[] {
  return marketPlugins.map((plugin) => {
    const installed = installedPlugins.find((item) => item.name === plugin.name)
    return {
      ...plugin,
      installed: !!installed,
      path: installed?.path,
      localVersion: installed?.version
    }
  })
}
```

---

## 缓存策略

### Storefront 缓存

- **缓存键**：`plugin-market-storefront`
- **指纹键**：`plugin-market-storefront-fingerprint`
- **失效条件**：插件名称或版本变化时指纹失效

```typescript
// 指纹计算示例
function getPluginMarketFingerprint(plugins: PluginMarketPlugin[]): string {
  return plugins
    .map((plugin) => `${plugin?.name || ''}:${plugin?.version || ''}`)
    .sort()
    .join('|')
}
```

### 最新版本缓存

- **缓存时长**：
  - 可用更新：5 分钟
  - 不可用：1 分钟
- **并发控制**：相同插件的并发请求共享同一网络请求

---

## 使用示例

### 完整首页渲染流程

```vue
<script setup>
import { ref, onMounted } from 'vue'

const storefrontSections = ref([])
const storefrontCategories = ref({})

async function fetchMarket() {
  const result = await window.ztools.internal.fetchPluginMarket()

  if (result.success && result.storefront) {
    storefrontSections.value = result.storefront.sections
    storefrontCategories.value = result.storefront.categories
  }
}

onMounted(fetchMarket)
</script>

<template>
  <div class="market">
    <template v-for="section in storefrontSections" :key="section.key">
      <Banner v-if="section.type === 'banner'" :items="section.items" />
      <Navigation v-else-if="section.type === 'navigation'" :categories="section.categories" />
      <PluginList v-else :plugins="section.plugins" :title="section.title" />
    </template>
  </div>
</template>
```

---

## 相关文件

- 数据类型定义：`src/main/api/renderer/pluginMarket.ts`
- 前端使用示例：`internal-plugins/setting/src/views/PluginMarketSetting/PluginMarketSetting.vue`
- 缓存管理：`src/main/api/shared/database.ts`
