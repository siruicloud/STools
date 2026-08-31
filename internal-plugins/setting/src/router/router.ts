import { createRouter, createWebHashHistory } from 'vue-router'
import type { RouteRecord, RouteRecordRaw } from 'vue-router'

/**
 * 菜单项
 */
interface IMenuItem {
  label: string
  icon: string
}

/**
 * 路由菜单项类型
 */
export type MenuRouterItemType = (RouteRecord | RouteRecordRaw) & {
  meta?: {
    menu?: IMenuItem
  }
}

// @unocss-include
const homeRoutes: MenuRouterItemType[] = [
  {
    path: '/',
    redirect: '/plugins'
  },
  {
    path: '/plugins',
    name: 'Plugins',
    component: () => import('@/views/PluginsSetting/PluginsSetting.vue'),
    meta: {
      menu: {
        label: '已安装插件',
        icon: 'i-z-plugin'
      }
    }
  },
  {
    path: '/market',
    name: 'Market',
    component: () => import('@/views/PluginMarketSetting/PluginMarketSetting.vue'),
    meta: {
      menu: {
        label: '插件市场',
        icon: 'i-z-store'
      }
    }
  },
  {
    path: '/generalSetting',
    name: 'GeneralSetting',
    component: () => import('@/views/GeneralSetting/GeneralSetting.vue')
  },
  {
    path: '/shortcuts',
    name: 'Shortcuts',
    component: () => import('@/views/ShortcutsSetting/ShortcutsSetting.vue')
  },
  {
    path: '/providers',
    name: 'Providers',
    component: () => import('@/views/ProvidersSetting/ProvidersSetting.vue')
  },
  {
    path: '/mcpService',
    name: 'McpService',
    component: () => import('@/views/McpServiceSetting/McpServiceSetting.vue')
  },
  {
    path: '/data',
    name: 'Data',
    component: () => import('@/views/DataSetting/DataSetting.vue')
  },
  {
    path: '/allCommands',
    name: 'AllCommands',
    component: () => import('@/views/AllCommandsSetting/AllCommandsSetting.vue')
  },
  {
    path: '/localLaunch',
    name: 'LocalLaunch',
    component: () => import('@/views/LocalLaunchSetting/LocalLaunchSetting.vue')
  },
  {
    path: '/sync',
    name: 'Sync',
    component: () => import('@/views/SyncSetting/SyncSetting.vue')
  },
  {
    path: '/debug',
    name: 'Debug',
    component: () => import('@/views/DebugSetting/DebugSetting.vue')
  },
  {
    path: '/httpService',
    name: 'HttpService',
    component: () => import('@/views/HttpServiceSetting/HttpServiceSetting.vue')
  },
  {
    path: '/about',
    name: 'About',
    component: () => import('@/views/AboutSetting/AboutSetting.vue')
  },
  {
    path: '/account',
    name: 'Account',
    component: () => import('@/views/AccountSetting/AccountSetting.vue')
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/views/NotificationCenter/NotificationCenter.vue')
  },
  {
    path: '/pluginInstaller',
    name: 'PluginInstaller',
    component: () => import('@/views/PluginInstaller/PluginInstaller.vue')
  }
]

export const router = createRouter({
  history: createWebHashHistory(),
  routes: homeRoutes
})
