<template>
  <div class="merger-container">
    <div class="header">
      <h2>合并 PDF</h2>
      <div class="actions">
        <button class="btn-secondary" @click="addFiles">
          <span class="icon">➕</span> 添加文件
        </button>
        <button class="btn-primary" @click="performMerge" :disabled="files.length < 2 || isMerging">
          <span v-if="isMerging" class="spinner"></span>
          {{ isMerging ? '处理中...' : '合并并下载' }}
        </button>
      </div>
    </div>

    <div class="content">
      <div v-if="files.length === 0" class="empty-list">
        <p>请点击“添加文件”选择需要合并的 PDF</p>
      </div>

      <div v-else class="file-list">
        <div v-for="(file, index) in files" :key="file.path" class="file-item">
          <span class="index">{{ index + 1 }}</span>
          <div class="info">
            <span class="name">{{ file.name }}</span>
            <span class="size">{{ formatSize(file.size) }}</span>
          </div>
          <div class="item-actions">
            <button class="btn-move" @click="moveFile(index, -1)" :disabled="index === 0">↑</button>
            <button
              class="btn-move"
              @click="moveFile(index, 1)"
              :disabled="index === files.length - 1"
            >
              ↓
            </button>
            <button class="btn-remove" @click="removeFile(index)">✕</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { PDFDocument } from 'pdf-lib'

interface PdfFile {
  path: string
  name: string
  size: number
  data?: Uint8Array
}

const files = ref<PdfFile[]>([])
const isMerging = ref(false)

async function addFiles() {
  const paths = window.ztools.showOpenDialog({
    title: '选择要合并的 PDF',
    filters: [{ name: 'PDF', extensions: ['pdf'] }],
    properties: ['openFile', 'multiSelections']
  })

  if (paths) {
    for (const path of paths) {
      if (!files.value.find((f) => f.path === path)) {
        // We need to read the file data now because we can't read it later easily in sandbox
        // However, reading large files into memory might be heavy.
        // pdf-lib can load from Uint8Array.
        // Let's try to fetch it as arrayBuffer.
        try {
          const response = await fetch(`file://${path}`)
          const buffer = await response.arrayBuffer()
          files.value.push({
            path,
            name: path.split('/').pop() || path,
            size: buffer.byteLength,
            data: new Uint8Array(buffer)
          })
        } catch (e) {
          console.error('Failed to load file:', path, e)
          window.ztools.showNotification(`无法加载文件: ${path.split('/').pop()}`)
        }
      }
    }
  }
}

function removeFile(index: number) {
  files.value.splice(index, 1)
}

function moveFile(index: number, direction: number) {
  const newIndex = index + direction
  if (newIndex >= 0 && newIndex < files.value.length) {
    const item = files.value.splice(index, 1)[0]
    files.value.splice(newIndex, 0, item)
  }
}

function formatSize(bytes: number) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

async function performMerge() {
  if (files.value.length < 2) return
  isMerging.value = true

  try {
    const mergedPdf = await PDFDocument.create()

    for (const file of files.value) {
      if (!file.data) continue
      const pdf = await PDFDocument.load(file.data)
      const copiedPages = await mergedPdf.copyPages(pdf, pdf.getPageIndices())
      copiedPages.forEach((page) => mergedPdf.addPage(page))
    }

    const pdfBytes = await mergedPdf.save()
    downloadBlob(pdfBytes, 'merged.pdf')
    window.ztools.showNotification('合并成功！文件已下载到“下载”文件夹')
  } catch (e) {
    console.error('Merge failed:', e)
    window.ztools.showNotification('合并失败: ' + (e as Error).message)
  } finally {
    isMerging.value = false
  }
}

function downloadBlob(data: Uint8Array, filename: string) {
  const blob = new Blob([data as BlobPart], { type: 'application/pdf' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
</script>

<style scoped>
.merger-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--bg-main, #f3f4f6);
}

.header {
  padding: 20px 24px;
  background: var(--bg-card, #ffffff);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

h2 {
  margin: 0;
  font-size: 18px;
  color: var(--text-main, #111827);
}
.actions {
  display: flex;
  gap: 12px;
}

.btn-primary,
.btn-secondary {
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
  border: none;
}
.btn-primary {
  background: var(--primary-color, #3b82f6);
  color: white;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-secondary {
  background: var(--bg-btn, #ffffff);
  border: 1px solid var(--border-color, #d1d5db);
  color: var(--text-main, #111827);
}
.btn-secondary:hover {
  background: var(--bg-hover, #f3f4f6);
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
}
.empty-list {
  text-align: center;
  color: var(--text-secondary, #6b7280);
  padding: 40px;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 800px;
  margin: 0 auto;
}
.file-item {
  background: var(--bg-card, #ffffff);
  padding: 16px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid var(--border-color, #e5e7eb);
}

.index {
  width: 28px;
  height: 28px;
  background: var(--bg-tag, #f3f4f6);
  color: var(--text-secondary, #6b7280);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
}

.info {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.name {
  font-weight: 500;
  color: var(--text-main, #111827);
  margin-bottom: 4px;
}
.size {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}

.item-actions {
  display: flex;
  gap: 4px;
}
.btn-move,
.btn-remove {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  font-size: 14px;
  color: var(--text-secondary, #6b7280);
}
.btn-move:hover,
.btn-remove:hover {
  background: var(--bg-hover, #f3f4f6);
  color: var(--text-main, #111827);
}
.btn-remove:hover {
  color: #ef4444;
}
.btn-move:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-color-scheme: dark) {
  .merger-container {
    --bg-main: #111827;
    --bg-card: #1f2937;
    --border-color: #374151;
    --text-main: #f9fafb;
    --text-secondary: #9ca3af;
    --bg-hover: #374151;
    --bg-tag: #374151;
    --bg-btn: #374151;
  }
}
</style>
