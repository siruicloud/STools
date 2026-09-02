/**
 * 环境配置 - 统一管理所有服务域名
 *
 * 修改此文件即可切换开发/测试/生产环境
 */

export interface EnvConfig {
  /** 同步服务域名 (登录、账号、数据同步) */
  syncServerUrl: string
  /** 插件市场 API 基础地址 */
  pluginMarketApiBase: string
  /** 是否允许导入本地插件 (生产环境应禁用) */
  allowLocalPluginImport: boolean
}

// ==================== 环境配置 ====================

/** 生产环境 */
const PROD_CONFIG: EnvConfig = {
  syncServerUrl: 'https://aide.smdoc.top',
  pluginMarketApiBase: 'https://aide.smdoc.top/api/pa',
  allowLocalPluginImport: false
}

/** 测试环境 */
const TEST_CONFIG: EnvConfig = {
  syncServerUrl: 'http://local.plugin.com',
  pluginMarketApiBase: 'http://local.plugin.com/api/pa',
  allowLocalPluginImport: true
}

/** 开发环境 */
const DEV_CONFIG: EnvConfig = {
  syncServerUrl: 'http://localhost:3000',
  pluginMarketApiBase: 'http://localhost:3000/api/pa',
  allowLocalPluginImport: true
}

// ==================== 当前环境 ====================

/**
 * 当前使用的环境配置
 *
 * 修改此值即可切换环境：
 * - 'prod' - 生产环境
 * - 'test' - 测试环境
 * - 'dev'  - 开发环境
 */
const CURRENT_ENV: 'prod' | 'test' | 'dev' = 'prod'

// ==================== 导出配置 ====================

const ENV_MAP: Record<string, EnvConfig> = {
  prod: PROD_CONFIG,
  test: TEST_CONFIG,
  dev: DEV_CONFIG
}

/** 当前环境配置 */
export const currentConfig = ENV_MAP[CURRENT_ENV]

/** 同步服务域名 (用于登录、账号、数据同步) */
export const SYNC_SERVER_URL = currentConfig.syncServerUrl

/** 插件市场 API 基础地址 */
export const PLUGIN_MARKET_API_BASE = currentConfig.pluginMarketApiBase

/** 是否允许导入本地插件 */
export const ALLOW_LOCAL_PLUGIN_IMPORT = currentConfig.allowLocalPluginImport

/** HTTP 形式的同步服务域名 */
export function syncServerUrlToHttp(serverUrl: string): string {
  return serverUrl.replace(/^ws:\/\//, 'http://').replace(/^wss:\/\//, 'https://')
}

/** WebSocket 形式的同步服务域名 */
export function syncServerUrlToWs(serverUrl: string): string {
  return serverUrl.replace(/^http:\/\//, 'ws://').replace(/^https:\/\//, 'wss://')
}
