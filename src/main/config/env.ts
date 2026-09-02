/**
 * 环境配置 - 统一管理所有服务域名
 *
 * 自动根据运行模式选择环境：
 * - 打包后（生产环境）: 使用 PROD_CONFIG
 * - 开发模式（未打包）: 使用 DEV_CONFIG
 */

import { app } from 'electron'

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
 * 自动检测当前运行环境
 * - app.isPackaged === true: 打包后的生产环境
 * - app.isPackaged === false: 开发模式
 */
const CURRENT_ENV: 'prod' | 'test' | 'dev' = app.isPackaged ? 'prod' : 'dev'

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
