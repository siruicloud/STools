<script setup lang="ts">
import defaultAvatar from '@/assets/image/default.png'
import {
  ACCOUNT_CHANGED_EVENT,
  ONLINE_SYNC_SERVER_URL,
  notifyAccountChanged
} from '@/composables/useZToolsAccount'
import { MenuRouterItemType } from '@/router'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()
const route = useRoute()

const menuRoutes = ref<MenuRouterItemType[]>([] as MenuRouterItemType[])
const loggedIn = ref(false)
const username = ref('')
const nickname = ref('')
const avatar = ref(defaultAvatar)
const showSettingsMenu = ref(false)
let accountLoadVersion = 0
let stopSyncStatusListener: (() => void) | null = null

interface AccountProfileCache {
  uid: string
  nickname?: string
  avatarUrl?: string
  updatedAt: number
}

const displayName = computed(() => nickname.value || username.value || 'seaman 用户')

// 设置菜单项
const settingsMenus = [
  { name: 'Account', label: '个人中心', icon: 'i-z-user' },
  { name: 'GeneralSetting', label: '通用设置', icon: 'i-z-settings' },
  { name: 'Data', label: '我的数据', icon: 'i-z-database' },
  { name: 'Sync', label: '数据同步', icon: 'i-z-cloud' },
  { name: 'About', label: '关于', icon: 'i-z-info' }
]

// 个人中心相关路由
const accountRoutes = ['Account', 'GeneralSetting', 'Data', 'Sync', 'About']

// 监听路由变化，离开个人中心路由时恢复主菜单
watch(
  () => route.name,
  (name) => {
    if (!accountRoutes.includes(name as string)) {
      showSettingsMenu.value = false
    }
  }
)

/**
 * 切换右侧设置页面。
 * @param item 要切换到的菜单路由项
 * @returns 无返回值
 */
const setActiveMenu = (item: MenuRouterItemType): void => {
  showSettingsMenu.value = false
  router.replace({ name: item.name })
}

/**
 * 切换到设置页面。
 * @param item 设置菜单项
 * @returns 无返回值
 */
const setActiveSetting = (item: { name: string }): void => {
  router.replace({ name: item.name })
}

/**
 * 返回主菜单（插件市场）。
 * @returns 无返回值
 */
const backToMain = (): void => {
  showSettingsMenu.value = false
  router.replace({ name: 'Market' })
}

// 自动加载路由
const autoLoadRouter = (): void => {
  menuRoutes.value = router
    .getRoutes()
    .filter((item) => item.meta)
    .filter((item) => item.path.split('/').length <= 2)
    .filter((item) => item.meta.menu) as MenuRouterItemType[]
}

onMounted(() => {
  autoLoadRouter()
  void loadAccount()
  window.addEventListener(ACCOUNT_CHANGED_EVENT, handleAccountChanged)
  stopSyncStatusListener =
    window.ztools.internal.onSyncStatusChanged?.((payload = {}) => {
      if (payload.credentialsInvalidated) {
        void loadAccount()
      }
    }) || null
})

onBeforeUnmount(() => {
  window.removeEventListener(ACCOUNT_CHANGED_EVENT, handleAccountChanged)
  stopSyncStatusListener?.()
  stopSyncStatusListener = null
})

function handleAccountChanged(): void {
  void loadAccount()
}

async function loadAccount(): Promise<void> {
  const version = (accountLoadVersion += 1)
  try {
    const result = await window.ztools.internal.syncGetConfig()
    const config = result.success ? result.config : null
    const uid = config?.username || ''
    const isLoggedIn = Boolean(config?.token && config.serverUrl === ONLINE_SYNC_SERVER_URL && uid)
    if (version !== accountLoadVersion) return

    loginUsername.value = uid
    if (isLoggedIn) {
      const cachedProfile = await readCachedProfile(uid)
      if (version !== accountLoadVersion) return

      loggedIn.value = true
      applyProfile(cachedProfile, uid)
      void refreshProfile(uid, version)
    } else {
      clearAccountState()
    }
  } catch {
    if (version === accountLoadVersion) clearAccountState()
  }
}

function profileCacheKey(uid: string): string {
  return `account-profile-cache:${uid}`
}

async function readCachedProfile(uid: string): Promise<AccountProfileCache | null> {
  if (!uid) return null
  try {
    const cached = await window.ztools.internal.dbGet(profileCacheKey(uid))
    if (!cached || typeof cached !== 'object') return null
    return {
      uid: typeof cached.uid === 'string' ? cached.uid : uid,
      nickname: typeof cached.nickname === 'string' ? cached.nickname : '',
      avatarUrl: typeof cached.avatarUrl === 'string' ? cached.avatarUrl : '',
      updatedAt: Number(cached.updatedAt || 0)
    }
  } catch {
    return null
  }
}

async function writeCachedProfile(profile: AccountProfileCache): Promise<void> {
  if (!profile.uid) return
  try {
    await window.ztools.internal.dbPut(profileCacheKey(profile.uid), {
      uid: profile.uid,
      nickname: profile.nickname || '',
      avatarUrl: profile.avatarUrl || '',
      updatedAt: profile.updatedAt || Date.now()
    })
  } catch {
    // 缓存失败不影响账号主流程。
  }
}

function applyProfile(
  profile: AccountProfileCache | null,
  fallbackUid: string = username.value
): void {
  username.value = profile?.uid || fallbackUid
  nickname.value = profile?.nickname || ''
  avatar.value = profile?.avatarUrl || defaultAvatar
}

/**
 * 清理当前账号展示状态，并在当前页面失效时回到通用设置。
 * @returns 无返回值
 */
function clearAccountState(): void {
  // 登录态失效后离开账号页面，避免继续展示过期账号信息。
  if (route.name === 'Account') {
    void router.replace({ name: 'GeneralSetting' })
  }
  loggedIn.value = false
  username.value = ''
  nickname.value = ''
  avatar.value = defaultAvatar
}

async function refreshProfile(
  expectedUid: string,
  version: number = accountLoadVersion
): Promise<void> {
  try {
    const result = await window.ztools.internal.syncGetAccountProfile()
    if (version !== accountLoadVersion) return
    if (result.success && result.profile) {
      const profile = {
        uid: result.profile.uid || expectedUid,
        nickname: result.profile.nickname || '',
        avatarUrl: result.profile.avatarUrl || '',
        updatedAt: Date.now()
      }
      if (profile.uid !== expectedUid) return
      applyProfile(profile, expectedUid)
      await writeCachedProfile(profile)
    }
  } catch {
    // 远端 profile 拉取失败时保留本地缓存展示，避免头像和昵称闪回默认值。
  }
}

/**
 * 跳转到个人中心页面。
 * @returns 无返回值
 */
function openAccount(): void {
  showSettingsMenu.value = true
  void router.replace({ name: 'Account' })
}
</script>

<template>
  <!-- 左侧菜单 -->
  <div class="settings-sidebar">
    <!-- 默认菜单：插件市场、已安装插件 -->
    <div v-if="!showSettingsMenu" class="menu-list">
      <div
        v-for="menuRoute in menuRoutes"
        :key="menuRoute.name"
        class="menu-item"
        :class="{ active: route.name === menuRoute.name }"
        @click="setActiveMenu(menuRoute)"
      >
        <div :class="menuRoute.meta?.menu?.icon ?? ''" class="menu-icon" style="font-size: 18px" />
        <span class="menu-label">{{ menuRoute.meta?.menu?.label ?? '' }}</span>
      </div>
    </div>

    <!-- 个人中心菜单：设置项 -->
    <div v-else class="menu-list">
      <div
        v-for="item in settingsMenus"
        :key="item.name"
        class="menu-item"
        :class="{ active: route.name === item.name }"
        @click="setActiveSetting(item)"
      >
        <div :class="item.icon" class="menu-icon" style="font-size: 18px" />
        <span class="menu-label">{{ item.label }}</span>
      </div>
    </div>

    <div
      class="sidebar-footer"
      :class="{ active: showSettingsMenu ? false : route.name === 'Account' }"
    >
      <!-- 主菜单：显示个人中心 -->
      <button
        v-if="!showSettingsMenu"
        class="account-dock"
        :class="{ active: route.name === 'Account' }"
        type="button"
        @click="openAccount"
      >
        <img v-if="loggedIn" class="account-avatar" :src="avatar" alt="" />
        <div v-else class="account-avatar account-placeholder">
          <div class="i-z-cloud" />
        </div>
        <div class="account-info">
          <strong>{{ loggedIn ? displayName : '个人中心' }}</strong>
          <span>{{ loggedIn ? '查看个人中心' : '管理账号与设置' }}</span>
        </div>
      </button>
      <!-- 个人中心菜单：显示插件市场 -->
      <button v-else class="account-dock" type="button" @click="backToMain">
        <div class="account-avatar account-placeholder">
          <div class="i-z-store" />
        </div>
        <div class="account-info">
          <strong>插件市场</strong>
          <span>返回主菜单</span>
        </div>
      </button>
    </div>
  </div>
</template>

<style scoped>
/* 左侧菜单 */
.settings-sidebar {
  display: flex;
  flex-direction: column;
  width: 200px;
  height: 100%;
  border-right: 1px solid var(--divider-color);
  min-height: 0;
}

.menu-list {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding-bottom: 10px;
  padding: 8px;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  gap: 10px;
  margin-bottom: 6px;
  cursor: pointer;
  transition: all 0.2s;
  color: var(--text-color);
  border-radius: 8px;
}

.menu-item:last-child {
  margin-bottom: 0;
}

.menu-item:hover {
  background: var(--hover-bg);
}

.menu-item.active {
  background: var(--active-bg);
  color: var(--primary-color);
  font-weight: 500;
}

.account-dock {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--text-color);
  cursor: pointer;
  padding: 10px;
  text-align: left;
  transition: all 0.2s;
}

.sidebar-footer {
  display: flex;
  align-items: center;
  gap: 6px;
  margin: 0 8px 8px;
  border-radius: 8px;
}

.sidebar-footer.active {
  background: var(--active-bg);
}

.sidebar-footer:not(.active):hover {
  background: var(--hover-bg);
}

.account-dock:hover {
  background: transparent;
  border-color: color-mix(in srgb, var(--primary-color) 35%, var(--divider-color));
}

.account-dock.active {
  background: transparent;
  color: var(--primary-color);
}

.account-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  background: var(--hover-bg);
}

.account-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-color);
  font-size: 18px;
}

.account-info {
  min-width: 0;
  display: grid;
  gap: 2px;
}

.account-info strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
}

.account-info span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--text-secondary);
  font-size: 12px;
}
</style>
