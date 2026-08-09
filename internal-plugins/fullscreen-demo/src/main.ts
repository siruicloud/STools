import { createApp } from 'vue'
import App from './App.vue'

console.log('[Fullscreen Demo] main.ts loaded')

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
    console.warn('[Fullscreen Demo] detectOS failed:', e)
  }
}

detectOS()

const app = createApp(App)
app.mount('#app')

console.log('[Fullscreen Demo] App mounted, URL:', window.location.href)

ztools.onPluginEnter((action) => {
  console.log('[Fullscreen Demo Plugin Enter]', action)

  if (action.code === 'open-fullscreen') {
    console.log('[Fullscreen Demo] Triggering fullscreen mode')

    // 1. 隐藏主搜索窗口
    console.log('[Fullscreen Demo] Hiding main window')
    window.ztools.hideMainWindow()

    // 2. 打开全屏窗口
    // 注意：由于开发模式下 pluginPath 指向 public/ 目录，
    // 而构建产物在 dist/ 目录，所以需要使用 ../dist/index.html 来正确解析路径
    try {
      console.log('[Fullscreen Demo] Calling createBrowserWindow...')
      const result = window.ztools.createBrowserWindow('../dist/index.html?mode=fullscreen', {
        fullscreen: true,
        frame: true, // 启用系统边框和标题栏
        show: true,
        backgroundColor: '#ffffff',
        webPreferences: {
          zoomFactor: 1.0
        }
      })
      console.log('[Fullscreen Demo] createBrowserWindow returned:', result)
    } catch (e) {
      console.error('[Fullscreen Demo] Failed to create window:', e)
    }
  }
})

ztools.onPluginOut(() => {
  console.log('[Fullscreen Demo Plugin Out]')
})
