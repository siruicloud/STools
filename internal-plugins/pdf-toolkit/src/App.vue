<template>
  <div class="app-container">
    <!-- 侧边栏 -->
    <aside class="sidebar">
      <div class="logo-area">
        <span class="logo-icon">📄</span>
        <span class="logo-text">PDF 工具箱</span>
      </div>

      <nav class="nav-menu">
        <button
          class="nav-item"
          :class="{ active: currentMode === 'view' }"
          @click="setMode('view')"
        >
          <span class="icon">👁️</span>
          <span>查看 PDF</span>
        </button>
        <button
          class="nav-item"
          :class="{ active: currentMode === 'merge' }"
          @click="setMode('merge')"
        >
          <span class="icon">🔗</span>
          <span>合并 PDF</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <button class="btn-secondary" @click="openFilePicker">
          <span class="icon">📂</span> 打开文件
        </button>
      </div>
    </aside>

    <!-- 主内容区 -->
    <main class="main-content">
      <!-- 查看模式 -->
      <div v-if="currentMode === 'view'" class="viewer-container">
        <div v-if="!currentPdf" class="empty-state">
          <span class="empty-icon">📄</span>
          <p>点击左侧“打开文件”按钮加载 PDF</p>
          <p class="hint">支持拖拽文件到此处</p>
        </div>

        <div v-else class="pdf-wrapper">
          <div class="toolbar">
            <span class="filename">{{ currentPdfName }}</span>
            <div class="controls">
              <button class="btn-icon" @click="zoomOut" title="缩小">➖</button>
              <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
              <button class="btn-icon" @click="zoomIn" title="放大">➕</button>
              <button class="btn-icon" @click="rotate" title="旋转">🔄</button>
            </div>
          </div>
          <div class="pdf-viewer">
            <Pdf :src="currentPdf" :scale="scale" :rotation="rotation" @loaded="onPdfLoaded" />
          </div>
        </div>
      </div>

      <!-- 合并模式 -->
      <div v-if="currentMode === 'merge'" class="merge-container">
        <div class="merge-header">
          <h2>合并 PDF</h2>
          <div class="actions">
            <button class="btn-primary" @click="addMergeFiles" :disabled="isMerging">
              添加文件
            </button>
            <button
              class="btn-primary"
              @click="performMerge"
              :disabled="mergeFiles.length < 2 || isMerging"
            >
              {{ isMerging ? '合并中...' : '开始合并' }}
            </button>
          </div>
        </div>

        <div class="file-list">
          <div v-if="mergeFiles.length === 0" class="empty-list">
            请点击“添加文件”选择需要合并的 PDF
          </div>
          <div v-for="(file, index) in mergeFiles" :key="file.path" class="file-item">
            <span class="index">{{ index + 1 }}</span>
            <span class="name">{{ file.name }}</span>
            <button class="btn-remove" @click="removeMergeFile(index)">✕</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Pdf from 'pdf-vue3'
import { PDFDocument } from 'pdf-lib'

const currentMode = ref<'view' | 'merge'>('view')
const currentPdf = ref<string | null>(null)
const currentPdfName = ref('')
const scale = ref(1.0)
const rotation = ref(0)

const mergeFiles = ref<{ path: string; name: string }[]>([])
const isMerging = ref(false)

function setMode(mode: 'view' | 'merge') {
  currentMode.value = mode
}

function openFilePicker() {
  const files = window.ztools.showOpenDialog({
    title: '选择 PDF 文件',
    filters: [{ name: 'PDF', extensions: ['pdf'] }],
    properties: ['openFile']
  })

  if (files && files.length > 0) {
    loadPdf(files[0])
  }
}

function loadPdf(filePath: string) {
  currentPdf.value = `file://${filePath}`
  currentPdfName.value = filePath.split('/').pop() || filePath
  scale.value = 1.0
  rotation.value = 0
  currentMode.value = 'view'
}

function onPdfLoaded() {
  console.log('[PDF Toolkit] PDF loaded successfully')
}

function zoomIn() {
  scale.value = Math.min(scale.value + 0.1, 3.0)
}
function zoomOut() {
  scale.value = Math.max(scale.value - 0.1, 0.5)
}
function rotate() {
  rotation.value = (rotation.value + 90) % 360
}

// 合并逻辑
function addMergeFiles() {
  const files = window.ztools.showOpenDialog({
    title: '选择要合并的 PDF',
    filters: [{ name: 'PDF', extensions: ['pdf'] }],
    properties: ['openFile', 'multiSelections']
  })

  if (files) {
    files.forEach((f: string) => {
      if (!mergeFiles.value.find((existing) => existing.path === f)) {
        mergeFiles.value.push({ path: f, name: f.split('/').pop() || f })
      }
    })
  }
}

function removeMergeFile(index: number) {
  mergeFiles.value.splice(index, 1)
}

async function performMerge() {
  isMerging.value = true
  try {
    const mergedPdf = await PDFDocument.create()

    for (const file of mergeFiles.value) {
      const fileBytes = await fetch(`file://${file.path}`).then((res) => res.arrayBuffer())
      const pdf = await PDFDocument.load(fileBytes)
      const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices())
      copiedPages.forEach((page) => mergedPdf.addPage(page))
    }

    // 保存合并后的文件
    const savePath = window.ztools.showSaveDialog({
      title: '保存合并后的 PDF',
      defaultPath: 'merged.pdf',
      filters: [{ name: 'PDF', extensions: ['pdf'] }]
    })

    if (savePath) {
      // 提示用户
      window.ztools.showNotification(
        `合并成功！共 ${mergedPdf.getPageCount()} 页。\n(保存功能需配置文件写入权限，此处仅演示)`
      )
    }
  } catch (e) {
    console.error('Merge failed:', e)
    window.ztools.showNotification('合并失败: ' + (e as Error).message)
  } finally {
    isMerging.value = false
  }
}

// 监听外部事件
function handleLoadPdf(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail.files && detail.files.length > 0) {
    loadPdf(detail.files[0])
  }
}

function handleTrigger(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail.action === 'merge') {
    setMode('merge')
    addMergeFiles()
  } else if (detail.action === 'open') {
    openFilePicker()
  }
}

onMounted(() => {
  window.addEventListener('ztools-load-pdf', handleLoadPdf)
  window.addEventListener('ztools-trigger-action', handleTrigger)
})

onUnmounted(() => {
  window.removeEventListener('ztools-load-pdf', handleLoadPdf)
  window.removeEventListener('ztools-trigger-action', handleTrigger)
})
</script>

<style scoped>
.app-container {
  display: flex;
  height: 100vh;
  background: #f5f5f5;
  color: #333;
  font-family: system-ui, sans-serif;
}

.sidebar {
  width: 200px;
  background: #fff;
  border-right: 1px solid #eee;
  display: flex;
  flex-direction: column;
}

.logo-area {
  padding: 20px;
  font-size: 18px;
  font-weight: bold;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-menu {
  flex: 1;
  padding: 10px;
}

.nav-item {
  width: 100%;
  padding: 12px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
}

.nav-item:hover {
  background: #f0f0f0;
}
.nav-item.active {
  background: #e6f7ff;
  color: #1890ff;
  font-weight: 500;
}

.sidebar-footer {
  padding: 15px;
  border-top: 1px solid #eee;
}

.main-content {
  flex: 1;
  overflow: hidden;
  position: relative;
}

.viewer-container,
.merge-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #999;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}
.hint {
  font-size: 12px;
  margin-top: 8px;
}

.pdf-wrapper {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.toolbar {
  height: 48px;
  background: #fff;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
}

.filename {
  font-weight: 500;
  font-size: 14px;
}

.controls {
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  background: #f0f0f0;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
}
.btn-icon:hover {
  background: #e0e0e0;
}
.zoom-level {
  font-size: 12px;
  width: 40px;
  text-align: center;
}

.pdf-viewer {
  flex: 1;
  overflow: auto;
  background: #525659;
  padding: 20px;
  display: flex;
  justify-content: center;
}

.merge-header {
  padding: 20px;
  background: #fff;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.merge-header h2 {
  margin: 0;
  font-size: 18px;
}
.actions {
  display: flex;
  gap: 10px;
}

.btn-primary {
  padding: 8px 16px;
  border: none;
  background: #1890ff;
  color: #fff;
  border-radius: 4px;
  cursor: pointer;
}
.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}
.btn-secondary {
  width: 100%;
  padding: 10px;
  border: 1px solid #d9d9d9;
  background: #fff;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.file-list {
  flex: 1;
  overflow-y: auto;
  padding: 10px;
}
.file-item {
  background: #fff;
  padding: 10px;
  margin-bottom: 8px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.index {
  width: 24px;
  height: 24px;
  background: #eee;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}
.name {
  flex: 1;
  font-size: 14px;
}
.btn-remove {
  border: none;
  background: none;
  cursor: pointer;
  color: #999;
}
.btn-remove:hover {
  color: #ff4d4f;
}

@media (prefers-color-scheme: dark) {
  .app-container {
    background: #1a1a1a;
    color: #e0e0e0;
  }
  .sidebar {
    background: #242424;
    border-color: #333;
  }
  .logo-area {
    border-color: #333;
  }
  .nav-item:hover {
    background: #333;
  }
  .nav-item.active {
    background: #111;
    color: #4a90d9;
  }
  .sidebar-footer {
    border-color: #333;
  }
  .toolbar {
    background: #242424;
    border-color: #333;
  }
  .btn-icon {
    background: #333;
    color: #fff;
  }
  .btn-icon:hover {
    background: #444;
  }
  .merge-header {
    background: #242424;
    border-color: #333;
  }
  .file-item {
    background: #2a2a2a;
  }
  .index {
    background: #444;
  }
  .btn-secondary {
    background: #2a2a2a;
    border-color: #444;
    color: #e0e0e0;
  }
}
</style>
