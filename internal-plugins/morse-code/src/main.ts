import { createApp, nextTick } from 'vue'
import App from './App.vue'
import { encode, decode } from './utils/morse'

function detectOS(): void {
  try {
    if (window.ztools.isWindows()) {
      document.documentElement.classList.add('os-windows')
    } else if (window.ztools.isMacOS()) {
      document.documentElement.classList.add('os-mac')
    } else {
      document.documentElement.classList.add('os-linux')
    }
  } catch (e) {
    console.warn('[Morse Code] detectOS failed:', e)
  }
}

detectOS()

const app = createApp(App)
app.mount('#app')

console.log('[Morse Code] App mounted successfully')

function setInitialHeight(): void {
  try {
    window.ztools.setExpendHeight(500)
    console.log('[Morse Code] Initial height set to 500')
  } catch (e) {
    console.warn('[Morse Code] setExpendHeight failed:', e)
    nextTick(() => setTimeout(setInitialHeight, 100))
  }
}

setInitialHeight()

ztools.onPluginEnter((action) => {
  console.log('[Morse Code Plugin Enter]', action)

  const { code, payload } = action

  if (code === 'morse-encode' || code === 'morse-quick-encode') {
    if (payload && typeof payload === 'string') {
      const result = encode(payload)
      if (result.success) {
        window.ztools.copyText(result.result)
        window.ztools.showNotification(
          `莫斯加密成功: ${result.result.substring(0, 50)}${result.result.length > 50 ? '...' : ''}`
        )
        window.ztools.setExpendHeight(100)
        window.ztools.outPlugin(true)
        return
      }
    }
  } else if (code === 'morse-decode' || code === 'morse-quick-decode') {
    if (payload && typeof payload === 'string') {
      const result = decode(payload)
      if (result.success) {
        window.ztools.copyText(result.result)
        window.ztools.showNotification(
          `莫斯解密成功: ${result.result.substring(0, 50)}${result.result.length > 50 ? '...' : ''}`
        )
        window.ztools.setExpendHeight(100)
        window.ztools.outPlugin(true)
        return
      }
    }
  }

  window.ztools.setExpendHeight(500)
})

ztools.onPluginOut(() => {
  console.log('[Morse Code Plugin Out]')
})
