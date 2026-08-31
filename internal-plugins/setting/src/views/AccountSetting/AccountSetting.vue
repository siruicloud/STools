<script setup lang="ts">
import defaultAvatar from '@/assets/image/default.png'
import { useToast } from '@/components'
import {
  ONLINE_SYNC_SERVER_URL,
  loginZToolsAccount,
  registerZToolsAccount,
  sendEmailCode,
  notifyAccountChanged,
  promptDefaultDataImportAfterLogin
} from '@/composables/useZToolsAccount'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

interface AccountProfileCache {
  uid: string
  nickname?: string
  email?: string
  avatarUrl?: string
  inviteCode?: string
  updatedAt: number
}

const router = useRouter()
const { success, error, warning } = useToast()

const isLoggedIn = ref(false)
const username = ref('')
const nickname = ref('')
const email = ref('')
const avatar = ref(defaultAvatar)
const inviteCode = ref('')
const loadingProfile = ref(true)
const editingNickname = ref(false)
const nicknameInput = ref('')
const updatingNickname = ref(false)

type AuthMode = 'login' | 'register'
const authMode = ref<AuthMode>('login')

const loginForm = ref({ account: '', password: '' })
const registerForm = ref({ username: '', email: '', password: '', code: '' })
const submitting = ref(false)

const sendingCode = ref(false)
const codeCountdown = ref(0)
let codeTimer: ReturnType<typeof setInterval> | null = null

const displayName = computed(() => nickname.value || username.value || '用户')

onMounted(() => {
  void loadAccount()
})

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
      email: typeof cached.email === 'string' ? cached.email : '',
      avatarUrl: typeof cached.avatarUrl === 'string' ? cached.avatarUrl : '',
      inviteCode: typeof cached.inviteCode === 'string' ? cached.inviteCode : '',
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
      email: profile.email || '',
      avatarUrl: profile.avatarUrl || '',
      inviteCode: profile.inviteCode || '',
      updatedAt: profile.updatedAt || Date.now()
    })
  } catch {}
}

function applyProfile(profile: AccountProfileCache | null, fallbackUid: string): void {
  username.value = profile?.uid || fallbackUid
  nickname.value = profile?.nickname || ''
  email.value = profile?.email || ''
  avatar.value = profile?.avatarUrl || defaultAvatar
  inviteCode.value = profile?.inviteCode || ''
}

async function loadAccount(): Promise<void> {
  loadingProfile.value = true

  try {
    const result = await window.ztools.internal.syncGetConfig()
    const config = result.success ? result.config : null
    const uid = config?.username || ''
    const loggedIn = Boolean(config?.token && config.serverUrl === ONLINE_SYNC_SERVER_URL && uid)
    if (!loggedIn) {
      isLoggedIn.value = false
      loadingProfile.value = false
      return
    }

    isLoggedIn.value = true
    const cachedProfile = await readCachedProfile(uid)
    applyProfile(cachedProfile, uid)
    await refreshProfile(uid)
  } catch (err: unknown) {
    console.error('加载个人中心失败:', err)
    isLoggedIn.value = false
  } finally {
    loadingProfile.value = false
  }
}

async function refreshProfile(expectedUid: string): Promise<void> {
  try {
    const result = await window.ztools.internal.syncGetAccountProfile()
    if (!result.success || !result.profile) return

    const profile = {
      uid: result.profile.uid || expectedUid,
      nickname: result.profile.nickname || '',
      email: result.profile.email || '',
      avatarUrl: result.profile.avatarUrl || '',
      inviteCode: result.profile.inviteCode || '',
      updatedAt: Date.now()
    }
    if (profile.uid !== expectedUid) return

    applyProfile(profile, expectedUid)
    await writeCachedProfile(profile)
  } catch {}
}

async function changeAvatar(): Promise<void> {
  const selected = await window.ztools.internal.selectImageFile()
  if (!selected.success || !selected.path) {
    if (selected.error) error(selected.error)
    return
  }

  const uploaded = await window.ztools.internal.syncUploadAccountAvatar(selected.path)
  if (!uploaded.success || !uploaded.profile) {
    error(uploaded.error || '头像上传失败')
    return
  }

  const profile = {
    uid: uploaded.profile.uid || username.value,
    nickname: uploaded.profile.nickname || nickname.value,
    email: uploaded.profile.email || email.value,
    avatarUrl: uploaded.profile.avatarUrl || '',
    updatedAt: Date.now()
  }
  applyProfile(profile, username.value)
  await writeCachedProfile(profile)
  notifyAccountChanged()
  success('账号头像已更新')
}

async function logout(): Promise<void> {
  try {
    await window.ztools.internal.syncStopAutoSync()
    await window.ztools.internal.syncSaveConfig({
      enabled: false,
      serverUrl: ONLINE_SYNC_SERVER_URL,
      token: '',
      refreshToken: '',
      syncInterval: 30,
      username: ''
    })

    notifyAccountChanged()
    success('已退出登录')
    await router.replace({ name: 'GeneralSetting' })
  } catch (err: unknown) {
    error(err instanceof Error ? err.message : '退出登录失败')
  }
}

function startEditNickname(): void {
  nicknameInput.value = nickname.value || username.value
  editingNickname.value = true
}

function cancelEditNickname(): void {
  editingNickname.value = false
  nicknameInput.value = ''
}

async function saveNickname(): Promise<void> {
  const newNickname = nicknameInput.value.trim()
  if (!newNickname) {
    warning('昵称不能为空')
    return
  }

  if (newNickname === nickname.value) {
    editingNickname.value = false
    return
  }

  try {
    updatingNickname.value = true

    const config = await window.ztools.internal.syncGetConfig()
    if (!config.success || !config.config?.token) {
      error('未登录，无法修改昵称')
      return
    }

    const result = await window.ztools.internal.syncUpdateNickname({
      serverUrl: config.config.serverUrl,
      token: config.config.token,
      nickname: newNickname
    })
    if (!result.success || !result.profile) {
      error(result.error || '更新昵称失败')
      return
    }

    const profile = {
      uid: result.profile.uid || username.value,
      nickname: result.profile.nickname || newNickname,
      email: result.profile.email || email.value,
      avatarUrl: result.profile.avatarUrl || avatar.value,
      updatedAt: Date.now()
    }
    applyProfile(profile, username.value)
    await writeCachedProfile(profile)
    editingNickname.value = false
    notifyAccountChanged()
    success('昵称已更新')
  } catch (err: unknown) {
    error(err instanceof Error ? err.message : '更新昵称失败')
  } finally {
    updatingNickname.value = false
  }
}

async function handleSendCode(): Promise<void> {
  if (!registerForm.value.email) {
    warning('请先输入邮箱')
    return
  }

  if (sendingCode.value || codeCountdown.value > 0) return

  sendingCode.value = true
  try {
    await sendEmailCode({ email: registerForm.value.email, event: 'register' })
    success('验证码已发送')
    codeCountdown.value = 60
    codeTimer = setInterval(() => {
      codeCountdown.value--
      if (codeCountdown.value <= 0) {
        if (codeTimer) {
          clearInterval(codeTimer)
          codeTimer = null
        }
      }
    }, 1000)
  } catch (err: unknown) {
    error(err instanceof Error ? err.message : '发送验证码失败')
  } finally {
    sendingCode.value = false
  }
}

async function handleLogin(): Promise<void> {
  if (!loginForm.value.account || !loginForm.value.password) {
    warning('请填写账号和密码')
    return
  }

  submitting.value = true
  try {
    await loginZToolsAccount({
      account: loginForm.value.account,
      password: loginForm.value.password
    })
    success('登录成功')
    await promptDefaultDataImportAfterLogin({ confirm, success, error })
    await loadAccount()
    notifyAccountChanged()
  } catch (err: unknown) {
    error(err instanceof Error ? err.message : '登录失败')
  } finally {
    submitting.value = false
  }
}

async function handleRegister(): Promise<void> {
  if (!registerForm.value.username) {
    warning('请输入用户名')
    return
  }
  if (!registerForm.value.email) {
    warning('请输入邮箱')
    return
  }
  if (!registerForm.value.password) {
    warning('请输入密码')
    return
  }
  if (!registerForm.value.code) {
    warning('请输入验证码')
    return
  }

  submitting.value = true
  try {
    await registerZToolsAccount({
      username: registerForm.value.username,
      email: registerForm.value.email,
      password: registerForm.value.password,
      code: registerForm.value.code
    })
    success('注册成功')
    await promptDefaultDataImportAfterLogin({ confirm, success, error })
    await loadAccount()
    notifyAccountChanged()
  } catch (err: unknown) {
    error(err instanceof Error ? err.message : '注册失败')
  } finally {
    submitting.value = false
  }
}

function switchToRegister(): void {
  authMode.value = 'register'
}

function switchToLogin(): void {
  authMode.value = 'login'
}

async function copyInviteCode(): Promise<void> {
  if (!inviteCode.value) return
  try {
    await window.ztools.copyText(inviteCode.value)
    success('邀请码已复制')
  } catch {
    error('复制失败')
  }
}
</script>

<template>
  <div class="content-panel">
    <div v-if="loadingProfile" class="loading-state">加载中...</div>
    <div v-else class="account-page">
      <!-- 未登录状态 -->
      <template v-if="!isLoggedIn">
        <section class="login-section">
          <div class="login-header">
            <div class="login-logo">
              <img src="/logo.png" alt="" />
            </div>
            <div class="login-heading">
              <strong>{{ authMode === 'login' ? '登录' : '注册' }}</strong>
              <span>账号同步数据与设置</span>
            </div>
          </div>

          <!-- 登录表单 -->
          <form v-if="authMode === 'login'" class="login-form" @submit.prevent="handleLogin">
            <label>
              <span>账号</span>
              <input
                v-model.trim="loginForm.account"
                type="text"
                autocomplete="username"
                placeholder="用户名/邮箱"
                maxlength="50"
              />
            </label>
            <label>
              <span>密码</span>
              <input
                v-model="loginForm.password"
                type="password"
                autocomplete="current-password"
                placeholder="8-20位密码"
                minlength="8"
                maxlength="20"
              />
            </label>
          </form>

          <!-- 注册表单 -->
          <form v-else class="login-form" @submit.prevent="handleRegister">
            <label>
              <span>用户名</span>
              <input
                v-model.trim="registerForm.username"
                type="text"
                placeholder="3-30字符"
                minlength="3"
                maxlength="30"
              />
            </label>
            <label>
              <span>邮箱</span>
              <input
                v-model.trim="registerForm.email"
                type="email"
                autocomplete="email"
                placeholder="输入邮箱"
                maxlength="50"
              />
            </label>
            <label>
              <span>验证码</span>
              <div class="input-with-button">
                <input
                  v-model.trim="registerForm.code"
                  type="text"
                  placeholder="6位验证码"
                  maxlength="6"
                />
                <button
                  type="button"
                  class="code-btn"
                  :disabled="sendingCode || codeCountdown > 0 || !registerForm.email"
                  @click="handleSendCode"
                >
                  {{
                    codeCountdown > 0 ? `${codeCountdown}s` : sendingCode ? '发送中' : '获取验证码'
                  }}
                </button>
              </div>
            </label>
            <label>
              <span>密码</span>
              <input
                v-model="registerForm.password"
                type="password"
                autocomplete="new-password"
                placeholder="8-20位密码"
                minlength="8"
                maxlength="20"
              />
            </label>
          </form>

          <button
            type="button"
            class="login-btn"
            :disabled="submitting"
            @click="authMode === 'login' ? handleLogin() : handleRegister()"
          >
            {{ submitting ? '提交中...' : authMode === 'login' ? '登录' : '注册' }}
          </button>

          <div class="auth-switch">
            <span v-if="authMode === 'login'">
              没有账号？
              <button type="button" class="link-btn" @click="switchToRegister">立即注册</button>
            </span>
            <span v-else>
              已有账号？
              <button type="button" class="link-btn" @click="switchToLogin">立即登录</button>
            </span>
          </div>
        </section>
      </template>

      <!-- 已登录状态 -->
      <template v-else>
        <!-- 用户信息卡片 -->
        <section class="user-card">
          <button class="avatar-wrapper" type="button" @click="changeAvatar">
            <img class="user-avatar" :src="avatar" alt="" />
            <div class="avatar-edit-overlay">
              <span>更换</span>
            </div>
          </button>
          <div class="user-info">
            <h1 class="user-name">{{ displayName }}</h1>
            <p class="user-desc">云同步账号</p>
          </div>
        </section>

        <!-- 基本信息 -->
        <section class="info-section">
          <h2 class="section-title">基本信息</h2>
          <div class="info-list">
            <div class="info-row">
              <span class="info-label">昵称</span>
              <div v-if="!editingNickname" class="info-action">
                <span class="info-value">{{ nickname || username }}</span>
                <button type="button" class="action-link" @click="startEditNickname">修改</button>
              </div>
              <div v-else class="edit-form">
                <input
                  v-model="nicknameInput"
                  type="text"
                  placeholder="输入昵称"
                  maxlength="50"
                  class="edit-input"
                  @keyup.enter="saveNickname"
                  @keyup.esc="cancelEditNickname"
                />
                <button
                  type="button"
                  class="btn-save"
                  :disabled="updatingNickname"
                  @click="saveNickname"
                >
                  {{ updatingNickname ? '保存中...' : '保存' }}
                </button>
                <button
                  type="button"
                  class="btn-cancel"
                  :disabled="updatingNickname"
                  @click="cancelEditNickname"
                >
                  取消
                </button>
              </div>
            </div>
            <div class="info-row">
              <span class="info-label">邮箱</span>
              <span class="info-value">{{ email || '未绑定' }}</span>
            </div>
            <div v-if="inviteCode" class="info-row">
              <span class="info-label">邀请码</span>
              <div class="info-action">
                <span class="info-value invite-code">{{ inviteCode }}</span>
                <button type="button" class="action-link" @click="copyInviteCode">复制</button>
              </div>
            </div>
          </div>
        </section>

        <!-- 账号操作 -->
        <section class="actions-section">
          <button type="button" class="logout-btn" @click="logout">
            <span class="i-z-back" />
            退出登录
          </button>
        </section>
      </template>
    </div>
  </div>
</template>

<style scoped>
.content-panel {
  height: 100%;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 20px;
  background: var(--bg-color);
}

.account-page {
  width: min(100%, 820px);
  margin: 0 auto;
}

.loading-state {
  display: grid;
  min-height: 160px;
  place-items: center;
  color: var(--text-secondary);
  font-size: 13px;
}

.login-section {
  padding: 20px 0 28px;
}

.login-header {
  display: flex;
  align-items: center;
  gap: 18px;
  padding-bottom: 28px;
}

.login-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: var(--hover-bg);
  overflow: hidden;
}

.login-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.login-heading {
  display: grid;
  gap: 5px;
}

.login-heading strong {
  color: var(--text-color);
  font-size: 20px;
}

.login-heading span {
  color: var(--text-secondary);
  font-size: 13px;
}

.login-form {
  display: grid;
  gap: 16px;
}

.login-form label {
  display: grid;
  gap: 8px;
  color: var(--text-secondary);
  font-size: 13px;
}

.login-form input {
  box-sizing: border-box;
  width: 100%;
  height: 44px;
  border: 1px solid var(--divider-color);
  border-radius: 8px;
  background: var(--control-bg);
  color: var(--text-color);
  outline: none;
  padding: 0 14px;
  font-size: 14px;
}

.login-form input::placeholder {
  color: var(--text-secondary);
}

.login-form input:focus {
  border-color: var(--primary-color);
}

.input-with-button {
  display: flex;
  gap: 10px;
}

.input-with-button input {
  flex: 1;
}

.code-btn {
  flex-shrink: 0;
  height: 44px;
  padding: 0 14px;
  border: 1px solid var(--primary-color);
  border-radius: 8px;
  background: transparent;
  color: var(--primary-color);
  font-size: 13px;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.code-btn:hover:not(:disabled) {
  background: color-mix(in srgb, var(--primary-color) 10%, transparent);
}

.code-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.login-btn {
  margin-top: 8px;
  width: 100%;
  height: 44px;
  border: 0;
  border-radius: 8px;
  background: var(--primary-color);
  color: var(--text-on-primary);
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: opacity 0.2s;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.9;
}

.login-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.auth-switch {
  margin-top: 16px;
  text-align: center;
  color: var(--text-secondary);
  font-size: 13px;
}

.link-btn {
  background: none;
  border: none;
  color: var(--primary-color);
  cursor: pointer;
  font-size: 13px;
  padding: 0;
}

.link-btn:hover {
  text-decoration: underline;
}

.profile-overview,
.profile-info,
.usage-section,
.profile-actions {
  display: none;
}

/* 用户卡片 */
.user-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px;
  margin-bottom: 24px;
  background: var(--card-bg, var(--hover-bg));
  border-radius: 16px;
  border: 1px solid var(--divider-color);
}

.avatar-wrapper {
  position: relative;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  cursor: pointer;
  border: 0;
  padding: 0;
  background: transparent;
}

.user-avatar {
  width: 100%;
  height: 100%;
  object-fit: cover;
  background: var(--hover-bg);
}

.avatar-edit-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  font-size: 13px;
  opacity: 0;
  transition: opacity 0.2s;
}

.avatar-wrapper:hover .avatar-edit-overlay {
  opacity: 1;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  margin: 0 0 4px;
  color: var(--text-color);
  font-size: 22px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-desc {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
}

/* 区块标题 */
.section-title {
  margin: 0 0 12px;
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* 信息列表 */
.info-section {
  margin-bottom: 24px;
}

.info-list {
  background: var(--card-bg, var(--hover-bg));
  border-radius: 12px;
  border: 1px solid var(--divider-color);
  overflow: hidden;
}

.info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid var(--divider-color);
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  color: var(--text-secondary);
  font-size: 14px;
}

.info-value {
  color: var(--text-color);
  font-size: 14px;
}

.invite-code {
  font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', monospace;
  letter-spacing: 0.5px;
}

.info-action {
  display: flex;
  align-items: center;
  gap: 12px;
}

.action-link {
  background: none;
  border: none;
  color: var(--primary-color);
  cursor: pointer;
  font-size: 13px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: background 0.2s;
}

.action-link:hover {
  background: color-mix(in srgb, var(--primary-color) 10%, transparent);
}

/* 编辑表单 */
.edit-form {
  display: flex;
  align-items: center;
  gap: 8px;
}

.edit-input {
  height: 36px;
  padding: 0 12px;
  border: 1px solid var(--divider-color);
  border-radius: 6px;
  background: var(--control-bg);
  color: var(--text-color);
  font-size: 14px;
  outline: none;
  min-width: 160px;
}

.edit-input:focus {
  border-color: var(--primary-color);
}

.btn-save {
  height: 36px;
  padding: 0 16px;
  border: 0;
  border-radius: 6px;
  background: var(--primary-color);
  color: var(--text-on-primary);
  font-size: 13px;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-save:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-save:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-cancel {
  height: 36px;
  padding: 0 16px;
  border: 1px solid var(--divider-color);
  border-radius: 6px;
  background: var(--hover-bg);
  color: var(--text-color);
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-cancel:hover:not(:disabled) {
  background: var(--active-bg);
}

.btn-cancel:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* 账号操作 */
.actions-section {
  padding-top: 8px;
}

.logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: var(--danger-bg, #fdecef);
  color: var(--danger-color, #d03050);
  border: 0;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  transition: opacity 0.2s;
}

.logout-btn:hover {
  opacity: 0.85;
}

@media (prefers-color-scheme: dark) {
  .logout-btn {
    background: rgba(208, 48, 80, 0.15);
    color: #ff8098;
  }

  .user-card,
  .info-list {
    background: var(--card-bg, rgba(255, 255, 255, 0.03));
  }
}

@media (max-width: 760px) {
  .edit-form {
    flex-wrap: wrap;
  }

  .edit-input {
    width: 100%;
  }
}
</style>
