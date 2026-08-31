import { httpGet } from '../../utils/httpRequest.js'
import databaseAPI from '../shared/database'
import {
  PluginMarketAuthRequiredError,
  PluginMarketAuthMode,
  getPluginMarketApiBase,
  requestPluginMarket
} from './pluginMarketConfig'

// ━━━ Mock Data Toggle ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
/** 设置为 true 启用本地模拟数据（用于开发/测试插件市场流程）；false 走真实后端请求 */
const USE_MOCK_DATA = false

const MOCK_PLUGINS: PluginMarketPlugin[] = [
  {
    name: 'morse-code',
    version: '1.0.0',
    title: '莫斯密码',
    description:
      '莫斯密码加解密工具，支持加密、解密、对照表查看，一键复制结果。支持快速加密粘贴的文本，快速解密粘贴的莫斯密码。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%234A90D9'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EM%3C/text%3E%3C/svg%3E",
    author: 'Zing',
    homepage: 'https://github.com/example/morse-code',
    size: 51200,
    publishedAt: Date.now() - 86400000 * 5,
    updatedAt: Date.now() - 86400000 * 2,
    downloadCount: 1520,
    categoryId: 1,
    categoryTitle: '效率工具'
  },
  {
    name: 'json-formatter',
    version: '2.1.0',
    title: 'JSON 格式化',
    description: 'JSON 格式化、压缩、校验工具，支持树形视图和语法高亮，一键转换 XML/YAML。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%23FF6B6B'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EJ%3C/text%3E%3C/svg%3E",
    author: 'DevTeam',
    homepage: 'https://github.com/example/json-formatter',
    size: 128000,
    publishedAt: Date.now() - 86400000 * 30,
    updatedAt: Date.now() - 86400000 * 1,
    downloadCount: 8900,
    categoryId: 2,
    categoryTitle: '开发工具'
  },
  {
    name: 'color-picker-pro',
    version: '1.5.2',
    title: '取色器 Pro',
    description: '屏幕取色、颜色转换、调色板管理，支持 HEX/RGB/HSL 格式，历史颜色记录。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%2350C878'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EC%3C/text%3E%3C/svg%3E",
    author: 'DesignLab',
    homepage: 'https://github.com/example/color-picker',
    size: 76800,
    publishedAt: Date.now() - 86400000 * 60,
    updatedAt: Date.now() - 86400000 * 10,
    downloadCount: 5600,
    categoryId: 3,
    categoryTitle: '设计工具'
  },
  {
    name: 'system-monitor',
    version: '3.0.1',
    title: '系统监控',
    description: 'CPU、内存、磁盘、网络实时监控，支持悬浮窗和告警，历史数据导出。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%23FFA500'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3ES%3C/text%3E%3C/svg%3E",
    author: 'SysAdmin',
    homepage: 'https://github.com/example/sys-monitor',
    size: 204800,
    publishedAt: Date.now() - 86400000 * 90,
    updatedAt: Date.now() - 86400000 * 3,
    downloadCount: 12000,
    categoryId: 4,
    categoryTitle: '系统工具'
  },
  {
    name: 'clipboard-manager',
    version: '2.0.0',
    title: '剪贴板增强',
    description: '剪贴板历史记录、搜索、图片预览、跨设备同步，支持富文本和文件。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%239370DB'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EC%3C/text%3E%3C/svg%3E",
    author: 'Productivity',
    homepage: 'https://github.com/example/clipboard',
    size: 153600,
    publishedAt: Date.now() - 86400000 * 120,
    updatedAt: Date.now() - 86400000 * 4,
    downloadCount: 21000,
    categoryId: 1,
    categoryTitle: '效率工具'
  },
  {
    name: 'ai-translator',
    version: '1.2.0',
    title: 'AI 翻译',
    description: '基于大模型的智能翻译，支持多语言、上下文理解、专业术语，一键复制。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%2300CED1'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EA%3C/text%3E%3C/svg%3E",
    author: 'AI Lab',
    homepage: 'https://github.com/example/ai-translator',
    size: 307200,
    publishedAt: Date.now() - 86400000 * 150,
    updatedAt: Date.now() - 86400000 * 5,
    downloadCount: 34000,
    categoryId: 5,
    categoryTitle: 'AI 工具'
  },
  {
    name: 'regex-helper',
    version: '1.0.5',
    title: '正则助手',
    description: '正则表达式编写、测试、解释工具，内置常用表达式库，支持高亮匹配。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%23FF1493'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3ER%3C/text%3E%3C/svg%3E",
    author: 'CodeMaster',
    homepage: 'https://github.com/example/regex',
    size: 81920,
    publishedAt: Date.now() - 86400000 * 200,
    updatedAt: Date.now() - 86400000 * 6,
    downloadCount: 4200,
    categoryId: 2,
    categoryTitle: '开发工具'
  },
  {
    name: 'markdown-preview',
    version: '2.3.0',
    title: 'Markdown 预览',
    description: '实时 Markdown 预览、导出 PDF/HTML、支持数学公式和流程图。',
    logo: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='%2332CD32'/%3E%3Ctext x='50' y='60' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EM%3C/text%3E%3C/svg%3E",
    author: 'WriterPro',
    homepage: 'https://github.com/example/markdown',
    size: 102400,
    publishedAt: Date.now() - 86400000 * 250,
    updatedAt: Date.now() - 86400000 * 7,
    downloadCount: 18000,
    categoryId: 1,
    categoryTitle: '效率工具'
  }
]

const MOCK_COMMENTS: Record<string, PluginMarketCommentItem[]> = {
  'morse-code': [
    {
      id: 1,
      pluginName: 'morse-code',
      uid: 'u1',
      nickname: 'UserA',
      content: '很好用的工具，赞！',
      likeCount: 10,
      liked: false,
      createdAt: Date.now() - 100000,
      updatedAt: Date.now() - 100000
    },
    {
      id: 2,
      pluginName: 'morse-code',
      uid: 'u2',
      nickname: 'UserB',
      content: '希望能支持音频播放莫斯密码。',
      likeCount: 5,
      liked: false,
      createdAt: Date.now() - 50000,
      updatedAt: Date.now() - 50000
    },
    {
      id: 3,
      pluginName: 'morse-code',
      uid: 'u3',
      nickname: 'UserC',
      content: '对照表很全，复制功能很方便。',
      likeCount: 3,
      liked: false,
      createdAt: Date.now() - 20000,
      updatedAt: Date.now() - 20000
    }
  ],
  'json-formatter': [
    {
      id: 4,
      pluginName: 'json-formatter',
      uid: 'u4',
      nickname: 'DevD',
      content: '树形视图太棒了，调试必备。',
      likeCount: 15,
      liked: false,
      createdAt: Date.now() - 150000,
      updatedAt: Date.now() - 150000
    }
  ],
  'color-picker-pro': [
    {
      id: 5,
      pluginName: 'color-picker-pro',
      uid: 'u5',
      nickname: 'DesignerE',
      content: '取色非常准，调色板管理很实用。',
      likeCount: 8,
      liked: false,
      createdAt: Date.now() - 300000,
      updatedAt: Date.now() - 300000
    }
  ]
}

const MOCK_BANNERS = [
  {
    title: '莫斯密码插件上线',
    imageUrl:
      "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='200'%3E%3Crect width='800' height='200' fill='%234A90D9'/%3E%3Ctext x='400' y='110' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EMorse Code Plugin Released%3C/text%3E%3C/svg%3E",
    linkUrl: ''
  },
  {
    title: 'AI 翻译插件更新',
    imageUrl:
      "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='200'%3E%3Crect width='800' height='200' fill='%2300CED1'/%3E%3Ctext x='400' y='110' font-size='40' fill='white' text-anchor='middle' font-family='sans-serif'%3EAI Translator Updated%3C/text%3E%3C/svg%3E",
    linkUrl: ''
  }
]

const MOCK_CATEGORIES: MarketCategoryResponse[] = [
  {
    id: 1,
    title: '效率工具',
    description: '提升日常工作效率的实用插件',
    plugins: MOCK_PLUGINS.filter((p) => p.categoryTitle === '效率工具')
  },
  {
    id: 2,
    title: '开发工具',
    description: '开发者专属的编码、调试、格式化工具',
    plugins: MOCK_PLUGINS.filter((p) => p.categoryTitle === '开发工具')
  },
  {
    id: 3,
    title: '设计工具',
    description: '取色、排版、图像处理等设计辅助插件',
    plugins: MOCK_PLUGINS.filter((p) => p.categoryTitle === '设计工具')
  },
  {
    id: 4,
    title: '系统工具',
    description: '系统监控、清理、优化等底层工具',
    plugins: MOCK_PLUGINS.filter((p) => p.categoryTitle === '系统工具')
  },
  {
    id: 5,
    title: 'AI 工具',
    description: '集成大模型能力的智能插件',
    plugins: MOCK_PLUGINS.filter((p) => p.categoryTitle === 'AI 工具')
  }
]

// ━━━ Types ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/** 插件市场中单个插件的描述信息（来自 ZTools 线上市场 API） */
export type PluginMarketPlugin = {
  name: string
  version: string
  title?: string
  description?: string
  logo?: string
  author?: string
  homepage?: string
  size?: number
  downloadCount?: number
  updatedAt?: number
  publishedAt?: number
  categoryId?: number | null
  categoryTitle?: string
  [key: string]: unknown
}

/** 市场首页轮播图项 */
type PluginMarketBannerItem = {
  /** 轮播图图片 URL */
  image: string
  /** 点击跳转链接 */
  url?: string
}

/** 分类详情页的布局区域配置 */
type PluginMarketCategoryLayoutSection = {
  /** 区域类型：list / fixed / random */
  type: string
  /** 支持模板字符串如 '${title}系列，共${count}个工具' */
  title?: string
  count?: number
  plugins?: string[]
}

/** 插件市场分类（构建后的视图数据） */
type PluginMarketStorefrontCategory = {
  key: string
  title: string
  description?: string
  icon?: string
  /** 该分类下的插件对象列表（已按平台过滤） */
  plugins: PluginMarketPlugin[]
}

/** 插件市场首页的单个布局区域（联合类型） */
type PluginMarketStorefrontSection =
  | {
      type: 'banner'
      key: string
      items: PluginMarketBannerItem[]
      height?: number
    }
  | {
      type: 'navigation'
      key: string
      title?: string
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
      type: 'fixed' | 'random'
      key: string
      title?: string
      plugins: PluginMarketPlugin[]
    }

/** 插件市场完整的首页视图数据 */
type PluginMarketStorefront = {
  /** 首页布局区域列表（按顺序渲染） */
  sections: PluginMarketStorefrontSection[]
  /** 所有分类的详细信息，以 key 为索引 */
  categories: Record<string, PluginMarketStorefrontCategory>
  /** 各分类详情页的布局配置 */
  categoryLayouts: Record<string, PluginMarketCategoryLayoutSection[]>
}

type MarketBannerResponse = {
  title?: string
  imageUrl?: string
  linkUrl?: string
}

type MarketCategoryResponse = {
  id?: number
  title?: string
  description?: string
  logo?: string
  plugins?: PluginMarketPlugin[]
}

type MarketPluginsResponse = {
  banners?: MarketBannerResponse[]
  categories?: MarketCategoryResponse[]
  latest?: PluginMarketPlugin[]
}

type PluginMarketCommentItem = {
  id: number
  pluginName: string
  uid: string
  nickname: string
  avatarUrl?: string
  parentId?: number | null
  parent?: PluginMarketCommentParent | null
  content: string
  likeCount: number
  liked: boolean
  deleted?: boolean
  createdAt: number
  updatedAt: number
}

type PluginMarketCommentParent = {
  id: number
  uid: string
  nickname: string
  avatarUrl?: string
  content: string
  deleted: boolean
  createdAt: number
}

type PluginMarketCommentPage = {
  items: PluginMarketCommentItem[]
  page: {
    page: number
    pageSize: number
    total: number
  }
}

/** fetchPluginMarket 的返回结果 */
export type PluginMarketResult = {
  success: boolean
  /** 全量插件列表（原始数据，未按平台过滤） */
  data?: PluginMarketPlugin[]
  /** 构建好的首页视图数据（平台已过滤） */
  storefront?: PluginMarketStorefront
  error?: string
}

export type PluginMarketLatestResult = {
  available: boolean
  reason?: 'not_found' | 'unsupported_platform'
  plugin?: PluginMarketPlugin
}

type PluginMarketLatestResponse = {
  available?: boolean
  reason?: 'not_found' | 'unsupported_platform'
  plugin?: PluginMarketPlugin
}

type PluginMarketLatestCacheEntry = {
  expiresAt: number
  result: PluginMarketLatestResult
}

// ━━━ Constants ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/** storefront 视图数据在 LMDB 中的缓存键 */
const PLUGIN_MARKET_STOREFRONT_CACHE_KEY = 'plugin-market-storefront'
/** storefront 指纹在 LMDB 中的缓存键，用于判断缓存是否失效 */
const PLUGIN_MARKET_STOREFRONT_FINGERPRINT_CACHE_KEY = 'plugin-market-storefront-fingerprint'
const PLUGIN_MARKET_RECOMMEND_LIMIT = 12
const PLUGIN_MARKET_LATEST_CACHE_MS = 5 * 60 * 1000
const PLUGIN_MARKET_LATEST_UNAVAILABLE_CACHE_MS = 60 * 1000

// ━━━ PluginMarketAPI ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

/**
 * 插件市场 API。
 * 负责从 ZTools 线上市场获取插件列表、缓存管理和首页 storefront 视图数据构建。
 */
export class PluginMarketAPI {
  private latestPluginCache = new Map<string, PluginMarketLatestCacheEntry>()
  private latestPluginRequests = new Map<string, Promise<PluginMarketLatestResult>>()

  /**
   * 获取插件市场列表。
   * 缓存策略：
   * 1. 优先请求线上聚合 API
   * 2. 网络失败时降级使用本地缓存
   * @returns 插件列表和可选的 storefront 视图数据
   */
  public async fetchPluginMarket(): Promise<PluginMarketResult> {
    if (USE_MOCK_DATA) {
      return this.getMockPluginMarketResult()
    }

    const getCachedResult = (): PluginMarketResult | null => {
      const cachedData = databaseAPI.dbGet('plugin-market-data')
      if (!Array.isArray(cachedData)) {
        return null
      }

      const storefrontFingerprint = databaseAPI.dbGet(
        PLUGIN_MARKET_STOREFRONT_FINGERPRINT_CACHE_KEY
      )
      const cachedStorefront = databaseAPI.dbGet(PLUGIN_MARKET_STOREFRONT_CACHE_KEY)
      const currentFingerprint = this.getPluginMarketFingerprint(cachedData)
      const storefront =
        storefrontFingerprint === currentFingerprint && cachedStorefront
          ? cachedStorefront
          : undefined

      return {
        success: true,
        data: cachedData,
        ...(storefront ? { storefront } : {})
      }
    }

    try {
      const marketApiBase = getPluginMarketApiBase()
      const timestamp = Date.now()
      const platform = process.platform

      console.log('[Plugins] 从 ZTools 插件市场获取列表...')

      const [marketResponse, recommendations] = await Promise.all([
        httpGet(
          `${marketApiBase}/plugin/market?limit=${PLUGIN_MARKET_RECOMMEND_LIMIT}&platform=${encodeURIComponent(platform)}&t=${timestamp}`
        ),
        this.fetchPluginMarketRecommendations(PLUGIN_MARKET_RECOMMEND_LIMIT).catch((error) => {
          console.warn('[Plugins] 获取推荐插件失败，将仅使用市场聚合数据:', error)
          return []
        })
      ])

      const marketData = this.parseMarketPluginsResponse(marketResponse.data)
      const plugins = this.collectPlugins(marketData)
      const storefront = this.buildPluginMarketStorefront(marketData, recommendations)
      const pluginMarketFingerprint = this.getPluginMarketFingerprint(plugins)

      databaseAPI.dbPut('plugin-market-version', String(timestamp))
      databaseAPI.dbPut('plugin-market-data', plugins)
      databaseAPI.dbPut(PLUGIN_MARKET_STOREFRONT_CACHE_KEY, storefront)
      databaseAPI.dbPut(PLUGIN_MARKET_STOREFRONT_FINGERPRINT_CACHE_KEY, pluginMarketFingerprint)

      return { success: true, data: plugins, storefront }
    } catch (error: unknown) {
      console.error('[Plugins] 获取插件市场列表失败:', error)
      try {
        const cachedResult = getCachedResult()
        if (cachedResult) {
          console.log('[Plugins] 获取失败，降级使用本地缓存')
          return cachedResult
        }
      } catch {
        // ignore
      }
      return { success: false, error: error instanceof Error ? error.message : '获取失败' }
    }
  }

  /**
   * 获取单个插件在当前平台可用的市场最新版本，并合并并发请求及短期缓存结果。
   * @param pluginName 插件唯一名称
   * @param platform 目标运行平台
   * @returns 市场可用状态和最新插件元数据
   * @throws 当插件名无效或市场请求失败时抛出错误
   */
  public async fetchLatestPlugin(
    pluginName: string,
    platform = process.platform
  ): Promise<PluginMarketLatestResult> {
    if (USE_MOCK_DATA) {
      return this.getMockLatestPlugin(pluginName)
    }

    const normalizedName = pluginName.trim()
    if (!normalizedName) {
      throw new Error('插件名称不能为空')
    }

    const cacheKey = `${platform}:${normalizedName}`
    const cached = this.latestPluginCache.get(cacheKey)
    if (cached && cached.expiresAt > Date.now()) {
      return cached.result
    }

    const pending = this.latestPluginRequests.get(cacheKey)
    if (pending) return pending

    // 同一插件的同时检查共享一次网络请求，避免频繁切换视图造成重复查询。
    const request = this.loadLatestPlugin(normalizedName, platform).then((result) => {
      const ttl = result.available
        ? PLUGIN_MARKET_LATEST_CACHE_MS
        : PLUGIN_MARKET_LATEST_UNAVAILABLE_CACHE_MS
      this.latestPluginCache.set(cacheKey, { expiresAt: Date.now() + ttl, result })
      return result
    })
    this.latestPluginRequests.set(cacheKey, request)
    try {
      return await request
    } finally {
      if (this.latestPluginRequests.get(cacheKey) === request) {
        this.latestPluginRequests.delete(cacheKey)
      }
    }
  }

  /**
   * 请求服务端的单插件最新版本接口并校验响应结构。
   * @param pluginName 插件唯一名称
   * @param platform 目标运行平台
   * @returns 服务端返回的市场可用状态和插件元数据
   * @throws 当响应声明可用却缺少有效插件信息时抛出错误
   */
  private async loadLatestPlugin(
    pluginName: string,
    platform: string
  ): Promise<PluginMarketLatestResult> {
    const query = new URLSearchParams({ name: pluginName })
    if (platform) query.set('platform', platform)

    const response = await requestPluginMarket(`/plugins/latest?${query.toString()}`)
    const data = (
      typeof response.data === 'string' ? JSON.parse(response.data) : response.data
    ) as PluginMarketLatestResponse
    if (!data?.available) {
      return { available: false, reason: data?.reason }
    }
    if (!data.plugin?.name || !data.plugin.version) {
      throw new Error('市场最新版本响应无效')
    }
    return { available: true, plugin: data.plugin }
  }

  public async fetchPluginMarketRecommendations(
    limit = PLUGIN_MARKET_RECOMMEND_LIMIT
  ): Promise<PluginMarketPlugin[]> {
    if (USE_MOCK_DATA) {
      return this.getMockRecommendations(limit)
    }

    const marketApiBase = getPluginMarketApiBase()
    const timestamp = Date.now()
    const platform = process.platform
    const response = await httpGet(
      `${marketApiBase}/plugins/recommendations?limit=${limit}&platform=${encodeURIComponent(platform)}&t=${timestamp}`
    )
    const data = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
    const items = Array.isArray(data?.items) ? data.items : []
    return items.filter((plugin: PluginMarketPlugin) => !!plugin?.name)
  }

  /**
   * 获取插件评论列表，并可让服务端返回包含指定评论的分页。
   * @param pluginName 插件唯一名称。
   * @param page 请求页码。
   * @param pageSize 每页数量。
   * @param anchorId 需要定位的评论标识；不定位时传 0。
   * @returns 评论列表请求结果。
   */
  public async fetchComments(
    pluginName: string,
    page = 1,
    pageSize = 20,
    anchorId = 0
  ): Promise<{
    success: boolean
    data?: PluginMarketCommentPage
    error?: string
    authRequired?: boolean
  }> {
    if (USE_MOCK_DATA) {
      const all = MOCK_COMMENTS[pluginName] || []
      const start = (page - 1) * pageSize
      const items = all.slice(start, start + pageSize)
      return {
        success: true,
        data: {
          items,
          page: { page, pageSize, total: all.length }
        }
      }
    }

    try {
      const query = new URLSearchParams({
        pluginName,
        page: String(page),
        pageSize: String(pageSize)
      })
      if (anchorId > 0) query.set('anchorId', String(anchorId))
      const response = await requestPluginMarket(`/plugins/comments?${query.toString()}`)
      return { success: true, data: this.parseCommentPage(response.data) }
    } catch (error: unknown) {
      return this.commentError(error, '评论加载失败')
    }
  }

  public async createComment(input: {
    pluginName: string
    content: string
    parentId?: number | null
  }): Promise<{
    success: boolean
    data?: PluginMarketCommentItem
    error?: string
    authRequired?: boolean
  }> {
    if (USE_MOCK_DATA) {
      const newComment: PluginMarketCommentItem = {
        id: Date.now(),
        pluginName: input.pluginName,
        uid: 'mock-user',
        nickname: '当前用户',
        content: input.content,
        likeCount: 0,
        liked: false,
        createdAt: Date.now(),
        updatedAt: Date.now()
      }
      if (!MOCK_COMMENTS[input.pluginName]) MOCK_COMMENTS[input.pluginName] = []
      MOCK_COMMENTS[input.pluginName].unshift(newComment)
      return { success: true, data: newComment }
    }

    try {
      const response = await requestPluginMarket(
        '/plugins/comments',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(input)
        },
        PluginMarketAuthMode.REQUIRED
      )
      return { success: true, data: this.parseCommentItem(response.data) }
    } catch (error: unknown) {
      return this.commentError(error, '评论发布失败')
    }
  }

  public async toggleCommentLike(commentId: number): Promise<{
    success: boolean
    data?: { liked: boolean; likeCount: number }
    error?: string
    authRequired?: boolean
  }> {
    if (USE_MOCK_DATA) {
      for (const list of Object.values(MOCK_COMMENTS)) {
        const c = list.find((item) => item.id === commentId)
        if (c) {
          c.liked = !c.liked
          c.likeCount += c.liked ? 1 : -1
          return { success: true, data: { liked: c.liked, likeCount: c.likeCount } }
        }
      }
      return { success: false, error: '评论不存在' }
    }

    try {
      const response = await requestPluginMarket(
        `/plugins/comments/${commentId}/like`,
        {
          method: 'POST'
        },
        PluginMarketAuthMode.REQUIRED
      )
      const data = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
      return {
        success: true,
        data: {
          liked: Boolean(data?.liked),
          likeCount: Number(data?.likeCount || 0)
        }
      }
    } catch (error: unknown) {
      return this.commentError(error, '操作失败')
    }
  }

  public async deleteComment(
    commentId: number
  ): Promise<{ success: boolean; error?: string; authRequired?: boolean }> {
    if (USE_MOCK_DATA) {
      for (const list of Object.values(MOCK_COMMENTS)) {
        const idx = list.findIndex((item) => item.id === commentId)
        if (idx !== -1) {
          list.splice(idx, 1)
          return { success: true }
        }
      }
      return { success: false, error: '评论不存在' }
    }

    try {
      await requestPluginMarket(
        `/plugins/comments/${commentId}`,
        {
          method: 'DELETE'
        },
        PluginMarketAuthMode.REQUIRED
      )
      return { success: true }
    } catch (error: unknown) {
      return this.commentError(error, '删除失败')
    }
  }

  /**
   * 生成插件列表的指纹字符串。
   * 用于判断缓存的 storefront 是否需要重新构建（插件名称/版本/平台变化时失效）。
   * @param plugins - 全量插件列表
   * @returns 排序后的指纹字符串
   */
  private getPluginMarketFingerprint(plugins: PluginMarketPlugin[]): string {
    return plugins
      .map((plugin) => `${plugin?.name || ''}:${plugin?.version || ''}`)
      .sort()
      .join('|')
  }

  private parseMarketPluginsResponse(value: unknown): MarketPluginsResponse {
    const raw = typeof value === 'string' ? JSON.parse(value) : value
    if (!raw || typeof raw !== 'object') return {}

    const data = raw as { code?: number; data?: unknown }
    if (data.code === 1 && data.data) {
      return data.data as MarketPluginsResponse
    }
    return raw as MarketPluginsResponse
  }

  private parseCommentPage(value: unknown): PluginMarketCommentPage {
    const data = typeof value === 'string' ? JSON.parse(value) : value
    const page = (data as PluginMarketCommentPage)?.page || { page: 1, pageSize: 20, total: 0 }
    const items = Array.isArray((data as PluginMarketCommentPage)?.items)
      ? (data as PluginMarketCommentPage).items.map((item) => this.parseCommentItem(item))
      : []
    return { items, page }
  }

  private parseCommentItem(value: unknown): PluginMarketCommentItem {
    const item = (typeof value === 'string' ? JSON.parse(value) : value) as PluginMarketCommentItem
    return {
      id: Number(item?.id || 0),
      pluginName: String(item?.pluginName || ''),
      uid: String(item?.uid || ''),
      nickname: String(item?.nickname || ''),
      avatarUrl: String(item?.avatarUrl || ''),
      parentId: item?.parentId == null ? null : Number(item.parentId),
      parent: item?.parent ? this.parseCommentParent(item.parent) : null,
      content: String(item?.content || ''),
      likeCount: Number(item?.likeCount || 0),
      liked: Boolean(item?.liked),
      deleted: Boolean(item?.deleted),
      createdAt: Number(item?.createdAt || 0),
      updatedAt: Number(item?.updatedAt || 0)
    }
  }

  private parseCommentParent(value: unknown): PluginMarketCommentParent {
    const item = (
      typeof value === 'string' ? JSON.parse(value) : value
    ) as PluginMarketCommentParent
    return {
      id: Number(item?.id || 0),
      uid: String(item?.uid || ''),
      nickname: String(item?.nickname || ''),
      avatarUrl: String(item?.avatarUrl || ''),
      content: String(item?.content || ''),
      deleted: Boolean(item?.deleted),
      createdAt: Number(item?.createdAt || 0)
    }
  }

  private commentError(
    error: unknown,
    fallback: string
  ): { success: false; error: string; authRequired?: boolean } {
    if (error instanceof PluginMarketAuthRequiredError) {
      return { success: false, error: error.message, authRequired: true }
    }
    return { success: false, error: error instanceof Error ? error.message : fallback }
  }

  // ━━━ Mock Methods ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  private getMockPluginMarketResult(): PluginMarketResult {
    const plugins = MOCK_PLUGINS
    const storefront = this.buildMockStorefront()

    databaseAPI.dbPut('plugin-market-version', String(Date.now()))
    databaseAPI.dbPut('plugin-market-data', plugins)
    databaseAPI.dbPut(PLUGIN_MARKET_STOREFRONT_CACHE_KEY, storefront)
    databaseAPI.dbPut(
      PLUGIN_MARKET_STOREFRONT_FINGERPRINT_CACHE_KEY,
      this.getPluginMarketFingerprint(plugins)
    )

    return { success: true, data: plugins, storefront }
  }

  private buildMockStorefront(): PluginMarketStorefront {
    const sections: PluginMarketStorefrontSection[] = [
      {
        type: 'banner',
        key: 'home-banner',
        items: MOCK_BANNERS.map((b) => ({ image: b.imageUrl, url: b.linkUrl })),
        height: 200
      },
      {
        type: 'navigation',
        key: 'category-nav',
        categories: MOCK_CATEGORIES.map((c) => ({
          key: String(c.id),
          title: c.title || '',
          description: c.description,
          icon: undefined,
          showDescription: true,
          pluginCount: c.plugins?.length || 0
        }))
      },
      {
        type: 'fixed',
        key: 'latest-plugins',
        title: '最新上架',
        plugins: MOCK_PLUGINS.slice(0, 4)
      },
      {
        type: 'random',
        key: 'recommended-plugins',
        title: '编辑推荐',
        plugins: [...MOCK_PLUGINS].sort(() => Math.random() - 0.5).slice(0, 4)
      }
    ]

    const categories: Record<string, PluginMarketStorefrontCategory> = {}
    const categoryLayouts: Record<string, PluginMarketCategoryLayoutSection[]> = {}

    for (const cat of MOCK_CATEGORIES) {
      const key = String(cat.id)
      categories[key] = {
        key,
        title: cat.title || '',
        description: cat.description,
        plugins: cat.plugins || []
      }
      categoryLayouts[key] = [
        {
          type: 'list',
          title: `${cat.title}系列，共${cat.plugins?.length || 0}个工具`,
          plugins: cat.plugins?.map((p) => p.name) || []
        }
      ]
    }

    return { sections, categories, categoryLayouts }
  }

  private async getMockLatestPlugin(pluginName: string): Promise<PluginMarketLatestResult> {
    const plugin = MOCK_PLUGINS.find((p) => p.name === pluginName)
    if (!plugin) {
      return { available: false, reason: 'not_found' }
    }
    return { available: true, plugin }
  }

  private getMockRecommendations(limit: number): PluginMarketPlugin[] {
    return MOCK_PLUGINS.slice(0, limit)
  }

  private collectPlugins(marketData: MarketPluginsResponse): PluginMarketPlugin[] {
    const byName = new Map<string, PluginMarketPlugin>()
    const pushPlugin = (plugin?: PluginMarketPlugin): void => {
      if (!plugin?.name) return
      byName.set(plugin.name, plugin)
    }

    for (const category of marketData.categories || []) {
      for (const plugin of category.plugins || []) {
        pushPlugin(plugin)
      }
    }

    for (const plugin of marketData.latest || []) {
      pushPlugin(plugin)
    }

    return [...byName.values()]
  }

  /**
   * 构建插件市场首页的 storefront 视图数据。
   * 将线上聚合 API 的 banners/categories/latest/recommendations 转换为渲染端可直接使用的首页结构。
   */
  private buildPluginMarketStorefront(
    marketData: MarketPluginsResponse,
    recommendations: PluginMarketPlugin[]
  ): PluginMarketStorefront {
    const categoriesList = Array.isArray(marketData.categories) ? marketData.categories : []
    const latest = Array.isArray(marketData.latest) ? marketData.latest : []

    const categories: Record<string, PluginMarketStorefrontCategory> = {}
    const navigationCategories: Array<{
      key: string
      title: string
      description?: string
      icon?: string
      showDescription: boolean
      pluginCount: number
    }> = []

    for (const category of categoriesList) {
      const key = this.categoryKey(category)
      const plugins = (category.plugins || []).filter((plugin) => !!plugin?.name)
      if (plugins.length === 0) continue

      categories[key] = {
        key,
        title: category.title || key,
        description: category.description,
        icon: category.logo,
        plugins
      }
      navigationCategories.push({
        key,
        title: category.title || key,
        description: category.description,
        icon: category.logo,
        showDescription: true,
        pluginCount: plugins.length
      })
    }

    const sections: PluginMarketStorefrontSection[] = []
    const bannerItems = (marketData.banners || [])
      .map((banner) => ({
        image: banner.imageUrl || '',
        url: banner.linkUrl || undefined
      }))
      .filter((item) => !!item.image)
    if (bannerItems.length > 0) {
      sections.push({ type: 'banner', key: 'banner-0', items: bannerItems, height: 160 })
    }

    if (navigationCategories.length > 0) {
      sections.push({
        type: 'navigation',
        key: 'navigation-0',
        title: '插件分类',
        categories: navigationCategories
      })
    }

    if (latest.length > 0) {
      sections.push({
        type: 'fixed',
        key: 'latest-0',
        title: '最新发布',
        plugins: latest
      })
    }

    const randomPlugins = recommendations.filter((plugin) => !!plugin?.name)
    if (randomPlugins.length > 0) {
      sections.push({
        type: 'random',
        key: 'recommendations-0',
        title: '探索发现',
        plugins: randomPlugins
      })
    }

    return {
      sections,
      categories,
      categoryLayouts: { default: [{ type: 'list' }] }
    }
  }

  private categoryKey(category: MarketCategoryResponse): string {
    if (typeof category.id === 'number' && category.id > 0) {
      return String(category.id)
    }
    return String(category.title || 'category').trim() || 'category'
  }
}
