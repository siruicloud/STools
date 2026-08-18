import { net } from 'electron'
import lmdbInstance from '../lmdb/lmdbInstance'
import type { SyncConfig } from './types'

const ONLINE_SYNC_SERVER_URL = 'https://api.seaman.cc'
const PLUGIN_DATA_PREFIX = 'PLUGIN/'
const LAST_SYNC_KEY = 'SYNC/plugin-data-last-sync'

/**
 * 插件数据同步服务
 * 负责将本地插件数据同步到云端
 */
class PluginDataSyncService {
  private syncing = false

  /**
   * 检查是否已登录
   */
  private isLoggedIn(): { loggedIn: boolean; config?: SyncConfig } {
    const config = lmdbInstance.get('SYNC/config')?.data as Partial<SyncConfig> | undefined
    if (!config?.token || config.serverUrl !== ONLINE_SYNC_SERVER_URL) {
      return { loggedIn: false }
    }
    return { loggedIn: true, config: config as SyncConfig }
  }

  /**
   * 获取所有需要同步的插件数据
   */
  private getLocalPluginData(): Record<string, any> {
    const result: Record<string, any> = {}
    try {
      const docs = lmdbInstance.allDocs(PLUGIN_DATA_PREFIX)
      for (const doc of docs) {
        if (doc._id) {
          result[doc._id] = doc.data
        }
      }
    } catch (error) {
      console.warn('[PluginDataSync] 读取本地插件数据失败:', error)
    }
    return result
  }

  /**
   * 上传插件数据到云端
   */
  public async uploadPluginData(): Promise<{ success: boolean; error?: string; count?: number }> {
    if (this.syncing) {
      return { success: false, error: '正在同步中' }
    }

    this.syncing = true

    try {
      const { loggedIn, config } = this.isLoggedIn()
      if (!loggedIn || !config) {
        return { success: false, error: '未登录' }
      }

      const localData = this.getLocalPluginData()
      const items = Object.entries(localData).map(([key, value]) => ({ key, value }))

      if (items.length === 0) {
        return { success: true, count: 0 }
      }

      const response = await net.fetch(`${ONLINE_SYNC_SERVER_URL}/api/sync/data`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${config.token}`
        },
        body: JSON.stringify({ items })
      })

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        return { success: false, error: data.error || '上传失败' }
      }

      const data = await response.json()
      await this.updateLastSyncTime()

      return { success: true, count: data.syncedCount || items.length }
    } catch (error: any) {
      return { success: false, error: error.message }
    } finally {
      this.syncing = false
    }
  }

  /**
   * 从云端下载插件数据
   */
  public async downloadPluginData(): Promise<{ success: boolean; error?: string; count?: number }> {
    if (this.syncing) {
      return { success: false, error: '正在同步中' }
    }

    this.syncing = true

    try {
      const { loggedIn, config } = this.isLoggedIn()
      if (!loggedIn || !config) {
        return { success: false, error: '未登录' }
      }

      const response = await net.fetch(`${ONLINE_SYNC_SERVER_URL}/api/sync/data`, {
        headers: { Authorization: `Bearer ${config.token}` }
      })

      if (!response.ok) {
        const data = await response.json().catch(() => ({}))
        return { success: false, error: data.error || '下载失败' }
      }

      const data = await response.json()
      const remoteData = data.data || {}

      // 合并到本地
      let count = 0
      for (const [key, value] of Object.entries(remoteData)) {
        if (key.startsWith(PLUGIN_DATA_PREFIX)) {
          try {
            const existing = lmdbInstance.get(key)
            await lmdbInstance.promises.put({
              _id: key,
              _rev: existing?._rev,
              data: value
            })
            count++
          } catch (error) {
            console.warn(`[PluginDataSync] 写入 ${key} 失败:`, error)
          }
        }
      }

      await this.updateLastSyncTime()
      return { success: true, count }
    } catch (error: any) {
      return { success: false, error: error.message }
    } finally {
      this.syncing = false
    }
  }

  /**
   * 双向同步（先下载后上传）
   */
  public async sync(): Promise<{
    success: boolean
    error?: string
    uploaded?: number
    downloaded?: number
  }> {
    const downloadResult = await this.downloadPluginData()
    if (!downloadResult.success) {
      return downloadResult
    }

    const uploadResult = await this.uploadPluginData()
    if (!uploadResult.success) {
      return uploadResult
    }

    return {
      success: true,
      uploaded: uploadResult.count,
      downloaded: downloadResult.count
    }
  }

  /**
   * 更新最后同步时间
   */
  private async updateLastSyncTime(): Promise<void> {
    try {
      await lmdbInstance.promises.put({
        _id: LAST_SYNC_KEY,
        data: Date.now()
      })
    } catch (error) {
      console.warn('[PluginDataSync] 更新同步时间失败:', error)
    }
  }

  /**
   * 获取最后同步时间
   */
  public getLastSyncTime(): number {
    try {
      return lmdbInstance.get(LAST_SYNC_KEY)?.data || 0
    } catch {
      return 0
    }
  }
}

export default new PluginDataSyncService()
