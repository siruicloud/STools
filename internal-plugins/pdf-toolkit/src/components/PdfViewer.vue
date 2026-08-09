<template>
  <div class="viewer-container">
    <div v-if="!src" class="empty-state" @drop="onDrop" @dragover.prevent>
      <div class="empty-content">
        <span class="empty-icon">📄</span>
        <h3>暂无 PDF 文件</h3>
        <p>点击左侧“打开文件”或拖拽文件到此处</p>
        <button class="btn-primary" @click="$emit('open-file')">选择文件</button>
      </div>
    </div>

    <div v-else class="pdf-wrapper">
      <!-- Toolbar -->
      <div class="toolbar">
        <div class="file-info">
          <span class="filename">{{ fileName }}</span>
          <span class="page-info" v-if="numPages">共 {{ numPages }} 页</span>
        </div>
        <div class="controls">
          <button class="btn-icon" @click="zoomOut" title="缩小">➖</button>
          <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
          <button class="btn-icon" @click="zoomIn" title="放大">➕</button>
          <div class="divider"></div>
          <button class="btn-icon" @click="rotate" title="旋转">🔄</button>
          <button class="btn-icon" @click="$emit('close')" title="关闭">✕</button>
        </div>
      </div>

      <!-- PDF Content -->
      <div class="pdf-content">
        <Pdf :src="src" :scale="scale" :rotation="rotation" @loaded="onLoaded" @error="onError" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Pdf from 'pdf-vue3'

const props = defineProps<{
  src: string | null
}>()

const emit = defineEmits<{
  'open-file': []
  close: []
}>()

const scale = ref(1.0)
const rotation = ref(0)
const numPages = ref(0)
const fileName = ref('')

function onLoaded(pdf: any) {
  numPages.value = pdf.numPages
  // Extract filename from file:// URL
  if (props.src) {
    fileName.value = decodeURIComponent(props.src.split('/').pop() || 'document.pdf')
  }
  // Notify parent about page count
  window.dispatchEvent(new CustomEvent('ztools-pdf-pages', { detail: { pages: pdf.numPages } }))
}

function onError(err: any) {
  console.error('PDF load error:', err)
  window.ztools.showNotification('PDF 加载失败，请检查文件是否损坏')
}

function zoomIn() {
  scale.value = Math.min(scale.value + 0.25, 4.0)
}
function zoomOut() {
  scale.value = Math.max(scale.value - 0.25, 0.5)
}
function rotate() {
  rotation.value = (rotation.value + 90) % 360
}

function onDrop(e: DragEvent) {
  e.preventDefault()
  const files = e.dataTransfer?.files
  if (files && files.length > 0 && files[0].name.endsWith('.pdf')) {
    // In a real app we'd handle the file object, but here we rely on ztools dialog
    emit('open-file')
  }
}
</script>

<style scoped>
.viewer-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--bg-main, #f3f4f6);
}

.empty-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed var(--border-color, #d1d5db);
  margin: 20px;
  border-radius: 12px;
  background: var(--bg-card, #ffffff);
}

.empty-content {
  text-align: center;
  color: var(--text-secondary, #6b7280);
}

.empty-icon {
  font-size: 64px;
  display: block;
  margin-bottom: 16px;
}
h3 {
  margin: 0 0 8px;
  color: var(--text-main, #111827);
}
p {
  margin: 0 0 24px;
}

.btn-primary {
  padding: 10px 20px;
  background: var(--primary-color, #3b82f6);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.pdf-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.toolbar {
  height: 56px;
  background: var(--bg-card, #ffffff);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.filename {
  font-weight: 600;
  color: var(--text-main, #111827);
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.page-info {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
  background: var(--bg-tag, #f3f4f6);
  padding: 2px 8px;
  border-radius: 4px;
}

.controls {
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  font-size: 18px;
  color: var(--text-secondary, #4b5563);
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-icon:hover {
  background: var(--bg-hover, #f3f4f6);
  color: var(--text-main, #111827);
}
.zoom-level {
  font-size: 13px;
  width: 50px;
  text-align: center;
  font-weight: 500;
  color: var(--text-main, #111827);
}
.divider {
  width: 1px;
  height: 24px;
  background: var(--border-color, #e5e7eb);
  margin: 0 4px;
}

.pdf-content {
  flex: 1;
  overflow: auto;
  display: flex;
  justify-content: center;
  padding: 24px;
  background: #525659;
}

@media (prefers-color-scheme: dark) {
  .viewer-container {
    --bg-main: #111827;
    --bg-card: #1f2937;
    --border-color: #374151;
    --text-main: #f9fafb;
    --text-secondary: #9ca3af;
    --bg-hover: #374151;
    --bg-tag: #374151;
  }
}
</style>
