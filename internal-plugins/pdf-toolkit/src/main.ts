import { createApp } from 'vue'
import App from './App.vue'

console.log('[PDF Toolkit] main.ts loaded')

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
    console.warn('[PDF Toolkit] detectOS failed:', e)
  }
}

detectOS()

const app = createApp(App)
app.mount('#app')

console.log('[PDF Toolkit] App mounted')

ztools.onPluginEnter((action: any) => {
  console.log('[PDF Toolkit Plugin Enter]', action)

  // 如果是通过文件匹配进入，直接打开文件
  if (action.code === 'pdf-files' && action.payload) {
    const files = Array.isArray(action.payload) ? action.payload : [action.payload]
    window.dispatchEvent(new CustomEvent('ztools-load-pdf', { detail: { files } }))
    return
  }

  // 如果是合并模式，打开多文件选择
  if (action.code === 'merge-pdf') {
    window.dispatchEvent(new CustomEvent('ztools-trigger-action', { detail: { action: 'merge' } }))
    return
  }

  // 默认打开文件选择
  if (action.code === 'convert-pdf') {
    window.dispatchEvent(
      new CustomEvent('ztools-trigger-action', { detail: { action: 'convert' } })
    )
    return
  }

  if (action.code === 'open-pdf') {
    window.dispatchEvent(new CustomEvent('ztools-trigger-action', { detail: { action: 'open' } }))
  }
})

ztools.onPluginOut(() => {
  console.log('[PDF Toolkit Plugin Out]')
})
