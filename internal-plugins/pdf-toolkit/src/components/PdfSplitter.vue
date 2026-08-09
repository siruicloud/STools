<template>
  <div class="splitter-container">
    <div class="header">
      <h2>拆分 PDF</h2>
      <button class="btn-primary" @click="performSplit" :disabled="!src || !range || isSplitting">
        <span v-if="isSplitting" class="spinner"></span>
        {{ isSplitting ? '处理中...' : '提取并下载' }}
      </button>
    </div>

    <div class="content">
      <div v-if="!src" class="empty-state">
        <p>请先在左侧“查看 PDF”中打开一个文件，或者点击此处选择文件</p>
        <button class="btn-secondary" @click="$emit('open-file')">选择文件</button>
      </div>

      <div v-else class="split-form">
        <div class="file-preview">
          <span class="icon">📄</span>
          <div class="info">
            <span class="name">{{ fileName }}</span>
            <span class="pages">共 {{ totalPages }} 页</span>
          </div>
          <button class="btn-change" @click="$emit('open-file')">更换</button>
        </div>

        <div class="form-group">
          <label>提取页码范围</label>
          <input v-model="range" placeholder="例如: 1-3, 5, 8-10" class="input-range" />
          <p class="hint">使用逗号分隔，支持连字符表示范围</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { PDFDocument } from 'pdf-lib'

const props = defineProps<{
  src: string | null
  totalPages: number
}>()

defineEmits<{
  'open-file': []
}>()

const range = ref('')
const isSplitting = ref(false)
const fileName = computed(() =>
  props.src ? decodeURIComponent(props.src.split('/').pop() || 'document.pdf') : ''
)

async function performSplit() {
  if (!props.src || !range.value) return
  isSplitting.value = true

  try {
    const response = await fetch(props.src)
    const pdfBytes = await response.arrayBuffer()
    const pdf = await PDFDocument.load(pdfBytes)
    const newPdf = await PDFDocument.create()

    const pagesToExtract = parsePageRange(range.value, props.totalPages)
    if (pagesToExtract.length === 0) {
      throw new Error('无效的页码范围')
    }

    // pdf-lib pages are 0-indexed
    const indices = pagesToExtract.map((p) => p - 1)
    const copiedPages = await newPdf.copyPages(pdf, indices)
    copiedPages.forEach((page) => newPdf.addPage(page))

    const resultBytes = await newPdf.save()
    downloadBlob(resultBytes, `split_${fileName.value}`)
    window.ztools.showNotification('提取成功！文件已下载')
  } catch (e) {
    console.error('Split failed:', e)
    window.ztools.showNotification((e as Error).message || '提取失败')
  } finally {
    isSplitting.value = false
  }
}

function parsePageRange(input: string, maxPages: number): number[] {
  const pages = new Set<number>()
  const parts = input.split(',')

  for (const part of parts) {
    const trimmed = part.trim()
    if (trimmed.includes('-')) {
      const [start, end] = trimmed.split('-').map(Number)
      if (isNaN(start) || isNaN(end) || start < 1 || end > maxPages || start > end) continue
      for (let i = start; i <= end; i++) pages.add(i)
    } else {
      const p = Number(trimmed)
      if (!isNaN(p) && p >= 1 && p <= maxPages) pages.add(p)
    }
  }
  return Array.from(pages).sort((a, b) => a - b)
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
.splitter-container {
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
.btn-primary {
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  border: none;
  background: var(--primary-color, #3b82f6);
  color: white;
  display: flex;
  align-items: center;
  gap: 6px;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  justify-content: center;
}
.empty-state {
  text-align: center;
  color: var(--text-secondary, #6b7280);
  padding: 40px;
  max-width: 400px;
}
.btn-secondary {
  padding: 8px 16px;
  border: 1px solid var(--border-color, #d1d5db);
  background: var(--bg-btn, #ffffff);
  border-radius: 8px;
  cursor: pointer;
  margin-top: 12px;
}

.split-form {
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.file-preview {
  background: var(--bg-card, #ffffff);
  padding: 16px;
  border-radius: 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  align-items: center;
  gap: 16px;
}
.icon {
  font-size: 32px;
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
.pages {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
}
.btn-change {
  font-size: 12px;
  color: var(--primary-color, #3b82f6);
  background: none;
  border: none;
  cursor: pointer;
  text-decoration: underline;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: var(--text-main, #111827);
  margin-bottom: 8px;
}
.input-range {
  width: 100%;
  padding: 12px;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 8px;
  font-size: 14px;
  background: var(--bg-input, #ffffff);
  color: var(--text-main, #111827);
}
.input-range:focus {
  outline: none;
  border-color: var(--primary-color, #3b82f6);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.hint {
  margin: 8px 0 0;
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
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
  .splitter-container {
    --bg-main: #111827;
    --bg-card: #1f2937;
    --border-color: #374151;
    --text-main: #f9fafb;
    --text-secondary: #9ca3af;
    --bg-btn: #374151;
    --bg-input: #374151;
  }
}
</style>
