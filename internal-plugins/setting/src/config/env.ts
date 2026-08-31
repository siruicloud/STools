/**
 * 环境配置 - 统一管理所有服务域名
 *
 * 修改此文件即可切换开发/测试/生产环境
 */

export interface EnvConfig {
  /** 同步服务域名 (登录、账号、数据同步) */
  syncServerUrl: string
}

// ==================== 环境配置 ====================

/** 生产环境 */
const PROD_CONFIG: EnvConfig = {
  syncServerUrl: 'https://aide.smdoc.top'
}

/** 测试环境 */
const TEST_CONFIG: EnvConfig = {
  syncServerUrl: 'http://local.plugin.com'
}

/** 开发环境 */
const DEV_CONFIG: EnvConfig = {
  syncServerUrl: 'http://localhost:3000'
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
