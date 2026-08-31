import { SYNC_SERVER_URL } from '@/config/env'

export const ONLINE_SYNC_SERVER_URL = SYNC_SERVER_URL
export const ACCOUNT_CHANGED_EVENT = 'ztools-account-changed'

export interface ZToolsLoginPayload {
  account: string
  password: string
}

export interface ZToolsRegisterPayload {
  username: string
  email: string
  password: string
  code: string
}

export interface ZToolsSendCodePayload {
  email: string
  event: 'register' | 'resetpwd'
}

export interface ZToolsLoginResult {
  username: string
}

export interface DefaultDataImportPromptHandlers {
  confirm: (options: {
    title?: string
    message: string
    type?: 'info' | 'warning' | 'danger'
    confirmText?: string
    cancelText?: string
  }) => Promise<boolean>
  success?: (message: string) => void
  error?: (message: string) => void
}

export function notifyAccountChanged(): void {
  window.dispatchEvent(new CustomEvent(ACCOUNT_CHANGED_EVENT))
}

export async function sendEmailCode(payload: ZToolsSendCodePayload): Promise<void> {
  const result = await window.ztools.internal.syncSendEmailCode({
    serverUrl: ONLINE_SYNC_SERVER_URL,
    email: payload.email,
    event: payload.event
  })
  if (!result.success) {
    throw new Error(result.error || '发送验证码失败')
  }
}

export async function registerZToolsAccount(
  payload: ZToolsRegisterPayload
): Promise<ZToolsLoginResult> {
  const registerResult = await window.ztools.internal.syncRegister({
    serverUrl: ONLINE_SYNC_SERVER_URL,
    username: payload.username,
    email: payload.email,
    password: payload.password,
    code: payload.code
  })
  if (!registerResult.success || !registerResult.token) {
    throw new Error(registerResult.error || '注册失败')
  }

  await window.ztools.internal.syncSaveConfig({
    enabled: true,
    serverUrl: ONLINE_SYNC_SERVER_URL,
    token: registerResult.token,
    refreshToken: '',
    syncInterval: 30,
    username: registerResult.username || payload.username
  })

  notifyAccountChanged()
  return {
    username: registerResult.username || payload.username
  }
}

export async function loginZToolsAccount(payload: ZToolsLoginPayload): Promise<ZToolsLoginResult> {
  const loginResult = await window.ztools.internal.syncLogin({
    serverUrl: ONLINE_SYNC_SERVER_URL,
    account: payload.account,
    password: payload.password
  })
  if (!loginResult.success || !loginResult.token) {
    throw new Error(loginResult.error || '登录失败')
  }

  const configResult = await window.ztools.internal.syncGetConfig()
  const currentConfig = configResult.success ? configResult.config : null
  await window.ztools.internal.syncSaveConfig({
    enabled: Boolean(currentConfig?.enabled),
    serverUrl: ONLINE_SYNC_SERVER_URL,
    token: loginResult.token,
    refreshToken: '',
    syncInterval: currentConfig?.syncInterval || 30,
    username: loginResult.username || payload.account
  })

  notifyAccountChanged()
  return {
    username: loginResult.username || payload.account
  }
}

export async function promptDefaultDataImportAfterLogin(
  handlers: DefaultDataImportPromptHandlers
): Promise<void> {
  const statusResult = await window.ztools.internal.syncGetDefaultImportStatus()
  if (!statusResult.success || !statusResult.status?.pending) return

  const confirmed = await handlers.confirm({
    title: '导入本机数据',
    message: `检测到未登录状态下已有 ${statusResult.status.defaultDocCount} 条本机数据。是否导入到当前登录账号？\n\n导入后，这些数据会参与当前账号的云同步。`,
    type: 'info',
    confirmText: '导入到当前账号',
    cancelText: '不导入'
  })

  if (!confirmed) {
    const skipped = await window.ztools.internal.syncSkipDefaultImport()
    if (!skipped.success) {
      handlers.error?.(skipped.error || '保存导入选择失败')
    }
    return
  }

  const imported = await window.ztools.internal.syncImportDefaultData()
  if (!imported.success) {
    handlers.error?.(imported.error || '导入本机数据失败')
    return
  }

  handlers.success?.(`已导入 ${imported.result?.importedDocs || 0} 条本机数据`)
  notifyAccountChanged()
}
