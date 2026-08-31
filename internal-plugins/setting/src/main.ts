import { createApp } from 'vue'
import App from './App.vue'
import './assets/styles'
import { router } from './router'
import { dispatchZtoolsCodeEvent, initZtoolsBaseEventHandler } from '@/events'
// 单独导入注册事件
import './events/allCodeEvent'

window.ztools.internal.onPluginsChanged(() => {
  console.log('[main.ts] 收到 plugins-changed 事件，准备转发 DOM 事件')
  window.dispatchEvent(new CustomEvent('plugins-changed'))
  console.log('[main.ts] DOM 事件已发送')
})

ztools.onPluginEnter((action) => {
  // 将 utools 事件派发根据不同的 code 进行派发出去
  console.log('[插件事件: onPluginEnter]', action)
  dispatchZtoolsCodeEvent(action, router)
})

ztools.onPluginOut(() => {
  if (router.currentRoute.value.name === 'Plugins') {
    return
  }

  void router.replace({ name: 'Plugins' }).catch((error) => {
    console.error('[插件事件: onPluginOut] 重置设置页路由失败', error)
  })
})

// 检测操作系统并添加类名到 html 元素
function detectOS(): void {
  if (window.ztools.isWindows()) {
    document.documentElement.classList.add('os-windows')
  } else if (window.ztools.isMacOS()) {
    document.documentElement.classList.add('os-mac')
  } else {
    document.documentElement.classList.add('os-linux')
  }
}

// 在应用初始化前检测操作系统
detectOS()

const app = createApp(App)
// 注册路由
app.use(router)

app.mount('#app')

// 注册 ztools 关键字跳转事件
initZtoolsBaseEventHandler()
