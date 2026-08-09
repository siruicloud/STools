<template>
  <div class="app-root" :class="{ 'is-fullscreen': isFullscreen }">
    <!-- 全屏模式 UI -->
    <div v-if="isFullscreen" class="fullscreen-layout">
      <!-- 顶部操作栏 -->
      <header class="top-bar">
        <span class="title">全屏演示模式</span>
      </header>

      <!-- 内容区域 -->
      <main class="content-area">
        <div class="card">
          <h2>🌕 欢迎使用全屏模式</h2>
          <p>这是一个无边框的全屏窗口，适合沉浸式体验。</p>
          <div class="stats">
            <div class="stat-item">
              <span class="label">状态</span>
              <span class="value active">运行中</span>
            </div>
            <div class="stat-item">
              <span class="label">模式</span>
              <span class="value">Fullscreen</span>
            </div>
          </div>
        </div>

        <div class="card">
          <h3>功能演示</h3>
          <ul>
            <li>✅ 独立窗口，不干扰主搜索界面</li>
            <li>✅ 自定义 UI，无边框设计</li>
            <li>✅ 支持 Vue 组件和样式</li>
          </ul>
        </div>
      </main>
    </div>

    <!-- 普通模式 UI -->
    <div v-else class="normal-content">
      <h3>全屏演示插件</h3>
      <p>请在搜索框输入“全屏演示”或“打开全屏”来唤起全屏窗口。</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isFullscreen = ref(false)

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  isFullscreen.value = params.get('mode') === 'fullscreen'
  console.log('[Fullscreen Demo] isFullscreen:', isFullscreen.value)
})
</script>

<style>
html,
body {
  margin: 0;
  padding: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.app-root {
  width: 100%;
  height: 100%;
}

/* 全屏布局 */
.fullscreen-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #1a1a1a;
  color: #ffffff;
}

/* 顶部操作栏 */
.top-bar {
  height: 48px;
  background: #2c2c2c;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border-bottom: 1px solid #333;
  -webkit-app-region: drag; /* 允许拖拽窗口 */
}

.top-bar .title {
  font-weight: 600;
  font-size: 14px;
  color: #aaa;
}

/* 内容区域 */
.content-area {
  flex: 1;
  padding: 24px;
  display: flex;
  gap: 24px;
  overflow-y: auto;
}

.card {
  background: #2c2c2c;
  border-radius: 12px;
  padding: 24px;
  flex: 1;
  min-width: 300px;
}

.card h2 {
  margin-top: 0;
  font-size: 24px;
  color: #4a90d9;
}

.card h3 {
  margin-top: 0;
  font-size: 18px;
  color: #888;
}

.stats {
  display: flex;
  gap: 24px;
  margin-top: 24px;
}

.stat-item {
  display: flex;
  flex-direction: column;
}

.stat-item .label {
  font-size: 12px;
  color: #888;
  text-transform: uppercase;
}

.stat-item .value {
  font-size: 20px;
  font-weight: bold;
}

.stat-item .value.active {
  color: #50c878;
}

ul {
  padding-left: 20px;
  line-height: 1.8;
}

li {
  margin-bottom: 8px;
}

/* 普通模式 */
.normal-content {
  padding: 2rem;
  text-align: center;
  color: #333;
}

@media (prefers-color-scheme: dark) {
  .normal-content {
    color: #e0e0e0;
    background: #1e1e1e;
  }
}
</style>
