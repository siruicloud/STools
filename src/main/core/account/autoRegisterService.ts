import { net } from 'electron'
import { createHash } from 'crypto'
import lmdbInstance from '../lmdb/lmdbInstance'
import { storageManager } from '../lmdb/lmdbInstance'
import pluginDeviceAPI from '../../api/plugin/device'
import { cacheUserProfile } from './userProfileStore'
import pluginDataSyncService from '../sync/pluginDataSyncService'
import type { SyncConfig } from '../sync/types'

const ONLINE_SYNC_SERVER_URL = 'https://api.seaman.cc'
const AUTO_REGISTER_FLAG = 'SYNC/auto-registered'

/**
 * 静默自动注册服务
 * 首次启动时基于 deviceId 自动生成账号并登录
 */
class AutoRegisterService {
  private registering = false

  /**
   * 检查是否需要自动注册
   * 条件：首次启动 + 未登录 + 未执行过自动注册
   */
  public async shouldAutoRegister(): Promise<boolean> {
    try {
      // 检查是否已经登录
      const config = lmdbInstance.get('SYNC/config')?.data as Partial<SyncConfig> | undefined
      if (config?.token) {
        return false
      }

      // 检查是否已经执行过自动注册（防止重复）
      const autoRegistered = lmdbInstance.get(AUTO_REGISTER_FLAG)?.data as boolean | undefined
      if (autoRegistered) {
        return false
      }

      // 检查是否是首次启动
      const state = storageManager.getInitState()
      if (!state.firstRun) {
        return false
      }

      return true
    } catch (error) {
      console.warn('[AutoRegister] 检查自动注册条件失败:', error)
      return false
    }
  }

  /**
   * 执行静默自动注册
   * 基于 deviceId 生成账号，调用后端 API 注册并登录
   */
  public async performAutoRegister(): Promise<{
    success: boolean
    error?: string
    isNew?: boolean
    username?: string
  }> {
    if (this.registering) {
      return { success: false, error: '正在注册中' }
    }

    this.registering = true

    try {
      // 获取 deviceId
      const deviceId = pluginDeviceAPI.getDeviceIdPublic()
      if (!deviceId) {
        return { success: false, error: '无法获取设备 ID' }
      }

      // 生成用户名和密码
      const username = this.generateUsername(deviceId)
      const password = this.generatePassword(deviceId)

      console.log('[AutoRegister] 开始静默注册, username:', username)

      // 调用后端 API 注册
      const result = await this.callAuthApi(username, password)
      if (!result.success) {
        return { success: false, error: result.error }
      }

      // 保存配置
      await this.saveSyncConfig({
        username,
        token: result.token!,
        refreshToken: result.refreshToken,
        deviceId
      })

      // 获取并缓存用户资料
      await this.fetchAndCacheUserProfile(result.token!)

      // 触发插件数据同步（后台执行，不阻塞启动）
      pluginDataSyncService.sync().catch((error) => {
        console.warn('[AutoRegister] 插件数据同步失败:', error)
      })

      // 标记已执行自动注册
      await lmdbInstance.promises.put({
        _id: AUTO_REGISTER_FLAG,
        data: true
      })

      console.log('[AutoRegister] 静默注册成功, username:', username, 'isNew:', result.isNew)

      return {
        success: true,
        isNew: result.isNew,
        username
      }
    } catch (error: any) {
      console.error('[AutoRegister] 静默注册失败:', error)
      return { success: false, error: error.message }
    } finally {
      this.registering = false
    }
  }

  /**
   * 基于 deviceId 生成用户名
   * 格式: device_{deviceId前16位}
   */
  private generateUsername(deviceId: string): string {
    const hash = createHash('md5').update(deviceId).digest('hex')
    return `device_${hash.substring(0, 16)}`
  }

  /**
   * 基于 deviceId 生成密码
   * 使用 deviceId + 固定盐值生成确定性密码
   */
  private generatePassword(deviceId: string): string {
    const salt = 'ztools-auto-register-salt-2024'
    return createHash('sha256')
      .update(deviceId + salt)
      .digest('hex')
      .substring(0, 32)
  }

  /**
   * 调用后端认证 API
   */
  private async callAuthApi(
    username: string,
    password: string
  ): Promise<{
    success: boolean
    error?: string
    token?: string
    refreshToken?: string
    isNew?: boolean
  }> {
    try {
      const response = await net.fetch(`${ONLINE_SYNC_SERVER_URL}/api/auth`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          uid: username,
          password
        })
      })

      const data = await response.json()

      if (!response.ok) {
        return { success: false, error: data.error || '认证失败' }
      }

      return {
        success: true,
        token: data.token,
        refreshToken: data.refreshToken,
        isNew: data.isNew
      }
    } catch (error: any) {
      return { success: false, error: error.message }
    }
  }

  /**
   * 保存同步配置
   */
  private async saveSyncConfig(params: {
    username: string
    token: string
    refreshToken?: string
    deviceId: string
  }): Promise<void> {
    const config: SyncConfig = {
      enabled: true,
      serverUrl: ONLINE_SYNC_SERVER_URL,
      token: params.token,
      refreshToken: params.refreshToken,
      syncInterval: 30,
      lastSyncTime: 0,
      deviceId: params.deviceId,
      username: params.username
    }

    await lmdbInstance.promises.put({
      _id: 'SYNC/config',
      data: config
    })

    // 切换账号存储
    storageManager.switchAccount(params.username)
  }

  /**
   * 获取用户资料并缓存到本地
   */
  private async fetchAndCacheUserProfile(token: string): Promise<void> {
    try {
      const response = await net.fetch(`${ONLINE_SYNC_SERVER_URL}/api/account/profile`, {
        headers: { Authorization: `Bearer ${token}` }
      })

      if (response.ok) {
        const data = await response.json()
        if (data.profile) {
          cacheUserProfile(data.profile)
        }
      }
    } catch (error) {
      console.warn('[AutoRegister] 获取用户资料失败:', error)
    }
  }
}

export default new AutoRegisterService()
