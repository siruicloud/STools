<template>
  <BaseDialog
    :visible="visible"
    title="Seaman Team 账号注册/登录"
    subtitle="同步数据、插件评论和个人配置"
    max-width="430px"
    @update:visible="emit('update:visible', $event)"
    @close="emit('cancel')"
  >
    <template #icon>
      <div class="login-logo">
        <img src="/logo.png" alt="" />
      </div>
    </template>

    <form class="login-form" @submit.prevent="handleSubmit">
      <label>
        <span>邮箱</span>
        <input
          v-model.trim="form.username"
          type="email"
          autocomplete="email"
          placeholder="输入邮箱"
          maxlength="50"
        />
      </label>
      <label>
        <span>密码</span>
        <input
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          placeholder="8-20位密码"
          minlength="8"
          maxlength="20"
        />
      </label>
      <div v-if="passwordStrength.label" class="password-strength" aria-live="polite">
        <span class="strength-bar" :class="`strength-${passwordStrength.level}`"></span>
        <span class="strength-label">{{ passwordStrength.label }}</span>
      </div>
    </form>
    <AliyunCaptcha ref="captchaRef" />

    <template #footer>
      <button type="button" class="btn-secondary" @click="handleCancel">取消</button>
      <button type="button" class="btn-primary" :disabled="loading" @click="handleSubmit">
        {{ loading ? '提交中...' : '注册/登录' }}
      </button>
    </template>
  </BaseDialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { BaseDialog } from '../BaseDialog'
import AliyunCaptcha from './AliyunCaptcha.vue'

interface Props {
  visible: boolean
  username?: string
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  username: '',
  loading: false
})

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (
    e: 'submit',
    value: { username: string; password: string; captchaVerifyParam?: string },
    controls: {
      resolve: () => void
      reject: (error: unknown) => void
    }
  ): void
  (e: 'cancel'): void
}>()

const captchaRef = ref<InstanceType<typeof AliyunCaptcha> | null>(null)
const form = reactive({
  username: '',
  password: ''
})

const passwordStrength = computed(() => {
  const pwd = form.password
  if (!pwd) return { level: 0, label: '' }
  let score = 0
  if (pwd.length >= 8) score++
  if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd)) score++
  if (/\d/.test(pwd)) score++
  if (/[^A-Za-z0-9]/.test(pwd)) score++
  if (score <= 1) return { level: 1, label: '密码强度：弱' }
  if (score === 2) return { level: 2, label: '密码强度：中' }
  return { level: 3, label: '密码强度：强' }
})

watch(
  () => props.visible,
  (visible) => {
    if (!visible) return
    form.username = props.username || ''
    form.password = ''
  },
  { immediate: true }
)

watch(
  () => props.username,
  (username) => {
    if (props.visible && !form.username) {
      form.username = username || ''
    }
  }
)

const handleSubmit = async (): Promise<void> => {
  if (props.loading) return
  const submit = (captchaVerifyParam?: string): Promise<void> =>
    new Promise<void>((resolve, reject) => {
      emit(
        'submit',
        {
          username: form.username,
          password: form.password,
          captchaVerifyParam
        },
        { resolve, reject }
      )
    })

  try {
    if (!form.username || !form.password) {
      await submit()
      return
    }

    const execute =
      captchaRef.value?.execute ??
      ((business: (captchaVerifyParam?: string) => Promise<void>) => business())
    await execute(submit)
  } catch {
    // 登录结果由父组件的 toast 展示；这里不重复提示。
  }
}

const handleCancel = (): void => {
  emit('cancel')
  emit('update:visible', false)
}
</script>

<style scoped>
.login-form {
  display: grid;
  gap: 16px;
}

.password-strength {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: -8px;
}

.strength-bar {
  width: 48px;
  height: 4px;
  border-radius: 2px;
  background: var(--divider-color, rgba(142, 167, 174, 0.22));
  position: relative;
  overflow: hidden;
}

.strength-bar::after {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 0;
  border-radius: 2px;
  transition:
    width 0.2s ease,
    background-color 0.2s ease;
}

.strength-1::after {
  width: 33%;
  background: #d03050;
}

.strength-2::after {
  width: 66%;
  background: #d89614;
}

.strength-3::after {
  width: 100%;
  background: #10b981;
}

.strength-label {
  font-size: 12px;
  color: var(--text-secondary);
}

.login-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  flex: 0 0 auto;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.72);
  border-radius: 50%;
  background: rgba(19, 24, 26, 0.94);
  box-shadow:
    0 10px 26px rgba(30, 62, 72, 0.22),
    0 0 0 4px rgba(255, 255, 255, 0.24);
}

.login-logo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.login-form label {
  display: grid;
  gap: 8px;
  color: rgba(61, 72, 76, 0.82);
  font-size: 13px;
  font-weight: 500;
}

.login-form input {
  box-sizing: border-box;
  width: 100%;
  height: 44px;
  border: 1px solid rgba(142, 167, 174, 0.38);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.72);
  color: var(--text-primary, #222222);
  outline: none;
  padding: 0 14px;
  font-size: 14px;
  transition:
    border-color 0.18s ease,
    background-color 0.18s ease,
    box-shadow 0.18s ease;
  box-shadow:
    inset 0 1px 0 rgba(255, 255, 255, 0.7),
    0 1px 2px rgba(42, 73, 84, 0.05);
}

.login-form input::placeholder {
  color: rgba(83, 95, 101, 0.55);
}

.login-form input:focus {
  border-color: var(--primary-color);
  background: rgba(255, 255, 255, 0.9);
  box-shadow:
    0 0 0 4px color-mix(in srgb, var(--primary-color) 16%, transparent),
    inset 0 1px 0 rgba(255, 255, 255, 0.82);
}

.btn-primary,
.btn-secondary {
  min-width: 86px;
  min-height: 40px;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  padding: 9px 18px;
  font-size: 14px;
  transition:
    transform 0.16s ease,
    box-shadow 0.16s ease,
    background-color 0.16s ease,
    opacity 0.16s ease;
}

.btn-primary {
  background: var(--primary-color);
  color: #fff;
  box-shadow: 0 10px 24px color-mix(in srgb, var(--primary-color) 26%, transparent);
}

.btn-primary:not(:disabled):hover,
.btn-secondary:hover {
  transform: translateY(-1px);
}

.btn-primary:not(:disabled):active,
.btn-secondary:active {
  transform: translateY(0);
}

.btn-primary:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.btn-secondary {
  border: 1px solid rgba(255, 255, 255, 0.58);
  background: color-mix(in srgb, var(--primary-color) 11%, rgba(255, 255, 255, 0.72));
  color: rgba(39, 57, 56, 0.88);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.62);
}

@media (prefers-color-scheme: dark) {
  .login-form label {
    color: rgba(224, 235, 237, 0.82);
  }

  .login-form input {
    border-color: rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.08);
    color: rgba(245, 248, 249, 0.94);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
  }

  .login-form input::placeholder {
    color: rgba(220, 228, 230, 0.48);
  }

  .login-form input:focus {
    background: rgba(255, 255, 255, 0.12);
  }

  .btn-secondary {
    border-color: rgba(255, 255, 255, 0.12);
    background: color-mix(in srgb, var(--primary-color) 16%, rgba(255, 255, 255, 0.08));
    color: rgba(235, 244, 244, 0.9);
  }
}
</style>
