<template>
  <div class="converter-container">
    <div class="header">
      <h2>格式转换</h2>
      <div class="tabs">
        <button class="tab" :class="{ active: mode === 'pdf2img' }" @click="mode = 'pdf2img'">
          PDF 转图片
        </button>
        <button class="tab" :class="{ active: mode === 'img2pdf' }" @click="mode = 'img2pdf'">
          图片 转 PDF
        </button>
      </div>
    </div>

    <div class="content">
      <!-- PDF to Image -->
      <div v-if="mode === 'pdf2img'" class="panel">
        <div v-if="!pdfFile" class="drop-zone" @drop="onPdfDrop" @dragover.prevent>
          <span class="icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
              <polyline points="14 2 14 8 20 8" />
              <line x1="16" y1="13" x2="8" y2="13" />
              <line x1="16" y1="17" x2="8" y2="17" />
              <polyline points="10 9 9 9 8 9" />
            </svg>
          </span>
          <p>拖拽 PDF 文件到此处</p>
          <button class="btn-primary" @click="selectPdf">选择文件</button>
        </div>

        <div v-else class="workspace">
          <div class="file-info">
            <span class="name">{{ pdfFile.name }}</span>
            <button class="btn-text" @click="pdfFile = null">更换</button>
          </div>

          <div class="options">
            <label>格式</label>
            <select v-model="imgFormat" class="select">
              <option value="image/png">PNG (清晰)</option>
              <option value="image/jpeg">JPG (体积小)</option>
            </select>
          </div>

          <button class="btn-primary full-width" @click="convertPdfToImg" :disabled="isConverting">
            {{ isConverting ? '转换中...' : '开始转换' }}
          </button>
          <p v-if="convertStatus" class="status">{{ convertStatus }}</p>
        </div>
      </div>

      <!-- Image to PDF -->
      <div v-if="mode === 'img2pdf'" class="panel">
        <div v-if="imageFiles.length === 0" class="drop-zone" @drop="onImgDrop" @dragover.prevent>
          <span class="icon">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <polyline points="21 15 16 10 5 21" />
            </svg>
          </span>
          <p>拖拽图片到此处</p>
          <button class="btn-primary" @click="selectImages">选择图片</button>
        </div>

        <div v-else class="workspace">
          <div class="image-list">
            <div v-for="(img, idx) in imageFiles" :key="idx" class="img-item">
              <img :src="img.preview" />
              <span class="name">{{ img.name }}</span>
              <button class="btn-remove" @click="removeImage(idx)">✕</button>
            </div>
          </div>

          <div class="options">
            <label>页面大小</label>
            <select v-model="pageSize" class="select">
              <option value="fit">适应图片</option>
              <option value="A4">A4 纸张</option>
            </select>
          </div>

          <button class="btn-primary full-width" @click="convertImgToPdf" :disabled="isConverting">
            {{ isConverting ? '生成中...' : '生成 PDF' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import { PDFDocument } from 'pdf-lib'

// Configure worker
GlobalWorkerOptions.workerSrc =
  'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.10.38/pdf.worker.min.mjs'

const mode = ref<'pdf2img' | 'img2pdf'>('pdf2img')
const isConverting = ref(false)
const convertStatus = ref('')

// PDF to Image State
const pdfFile = ref<{ name: string; path: string } | null>(null)
const imgFormat = ref('image/png')

// Image to PDF State
const imageFiles = ref<{ name: string; path: string; preview: string }[]>([])
const pageSize = ref('fit')

function selectPdf() {
  const files = window.ztools.showOpenDialog({
    title: '选择 PDF',
    filters: [{ name: 'PDF', extensions: ['pdf'] }],
    properties: ['openFile']
  })
  if (files?.[0]) {
    pdfFile.value = { name: files[0].split('/').pop() || 'file.pdf', path: files[0] }
  }
}

function onPdfDrop(e: DragEvent) {
  e.preventDefault()
  // In a real app we'd handle DataTransfer, but here we rely on dialog for simplicity in this context
  selectPdf()
}

async function convertPdfToImg() {
  if (!pdfFile.value) return
  isConverting.value = true
  convertStatus.value = '正在加载 PDF...'

  try {
    const response = await fetch(`file://${pdfFile.value.path}`)
    const arrayBuffer = await response.arrayBuffer()
    const pdf = await getDocument({ data: arrayBuffer }).promise

    convertStatus.value = `共 ${pdf.numPages} 页，开始渲染...`

    for (let i = 1; i <= pdf.numPages; i++) {
      convertStatus.value = `正在转换第 ${i}/${pdf.numPages} 页...`
      const page = await pdf.getPage(i)
      const viewport = page.getViewport({ scale: 2.0 }) // 2x scale for quality
      const canvas = document.createElement('canvas')
      canvas.width = viewport.width
      canvas.height = viewport.height
      const ctx = canvas.getContext('2d')!

      await page.render({ canvasContext: ctx, viewport }).promise

      // Download
      canvas.toBlob(
        (blob) => {
          if (blob) {
            const url = URL.createObjectURL(blob)
            const a = document.createElement('a')
            a.href = url
            a.download = `${pdfFile.value!.name.replace('.pdf', '')}_page_${i}.${imgFormat.value === 'image/png' ? 'png' : 'jpg'}`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            URL.revokeObjectURL(url)
          }
        },
        imgFormat.value,
        0.9
      )
    }
    convertStatus.value = '转换完成！文件已下载'
    window.ztools.showNotification('PDF 转图片成功')
  } catch (e) {
    console.error(e)
    convertStatus.value = '转换失败，请检查文件'
    window.ztools.showNotification('转换失败')
  } finally {
    isConverting.value = false
  }
}

function selectImages() {
  const files = window.ztools.showOpenDialog({
    title: '选择图片',
    filters: [{ name: 'Images', extensions: ['png', 'jpg', 'jpeg', 'webp'] }],
    properties: ['openFile', 'multiSelections']
  })
  if (files) {
    files.forEach((f) => {
      imageFiles.value.push({
        name: f.split('/').pop() || 'image',
        path: f,
        preview: `file://${f}`
      })
    })
  }
}

function onImgDrop(_e: DragEvent) {
  selectImages()
}
function removeImage(idx: number) {
  imageFiles.value.splice(idx, 1)
}

async function convertImgToPdf() {
  if (imageFiles.value.length === 0) return
  isConverting.value = true

  try {
    const pdfDoc = await PDFDocument.create()

    for (const img of imageFiles.value) {
      const response = await fetch(img.path.startsWith('file://') ? img.path : `file://${img.path}`)
      const bytes = await response.arrayBuffer()
      let image
      if (img.name.toLowerCase().endsWith('.png')) {
        image = await pdfDoc.embedPng(bytes)
      } else {
        image = await pdfDoc.embedJpg(bytes)
      }

      let page
      if (pageSize.value === 'A4') {
        // A4 dimensions in points (595.28 x 841.89)
        page = pdfDoc.addPage([595.28, 841.89])
        const { width, height } = page.getSize()
        const imgDims = image.scale(1)
        const scale = Math.min(width / imgDims.width, height / imgDims.height)
        page.drawImage(image, {
          x: (width - imgDims.width * scale) / 2,
          y: (height - imgDims.height * scale) / 2,
          width: imgDims.width * scale,
          height: imgDims.height * scale
        })
      } else {
        // Fit to image size
        page = pdfDoc.addPage([image.width, image.height])
        page.drawImage(image, { x: 0, y: 0, width: image.width, height: image.height })
      }
    }

    const pdfBytes = await pdfDoc.save()
    const blob = new Blob([pdfBytes as BlobPart], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'converted_images.pdf'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    window.ztools.showNotification('图片转 PDF 成功')
  } catch (e) {
    console.error(e)
    window.ztools.showNotification('转换失败')
  } finally {
    isConverting.value = false
  }
}
</script>

<style scoped>
.converter-container {
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
.tabs {
  display: flex;
  gap: 4px;
  background: var(--bg-tag, #f3f4f6);
  padding: 4px;
  border-radius: 8px;
}
.tab {
  padding: 6px 16px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  color: var(--text-secondary, #6b7280);
  font-size: 13px;
  font-weight: 500;
}
.tab.active {
  background: var(--bg-card, #ffffff);
  color: var(--text-main, #111827);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
  display: flex;
  justify-content: center;
}
.panel {
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.drop-zone {
  border: 2px dashed var(--border-color, #d1d5db);
  border-radius: 12px;
  padding: 40px;
  text-align: center;
  background: var(--bg-card, #ffffff);
  color: var(--text-secondary, #6b7280);
}
.icon {
  font-size: 48px;
  display: block;
  margin-bottom: 12px;
}

.workspace {
  background: var(--bg-card, #ffffff);
  padding: 20px;
  border-radius: 12px;
  border: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.file-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}
.name {
  font-weight: 500;
  color: var(--text-main, #111827);
}
.btn-text {
  background: none;
  border: none;
  color: var(--primary-color, #3b82f6);
  cursor: pointer;
  font-size: 13px;
}

.options {
  display: flex;
  align-items: center;
  gap: 12px;
}
.select {
  flex: 1;
  padding: 8px;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 6px;
  background: var(--bg-input, #ffffff);
  color: var(--text-main, #111827);
}
.btn-primary {
  padding: 10px;
  background: var(--primary-color, #3b82f6);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.full-width {
  width: 100%;
}
.status {
  text-align: center;
  font-size: 13px;
  color: var(--text-secondary, #6b7280);
  margin: 0;
}

.image-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
  max-height: 300px;
  overflow-y: auto;
}
.img-item {
  position: relative;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  overflow: hidden;
}
.img-item img {
  width: 100%;
  height: 100px;
  object-fit: cover;
}
.img-item .name {
  display: block;
  padding: 4px;
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  background: var(--bg-card, #ffffff);
}
.btn-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.6);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

@media (prefers-color-scheme: dark) {
  .converter-container {
    --bg-main: #111827;
    --bg-card: #1f2937;
    --border-color: #374151;
    --text-main: #f9fafb;
    --text-secondary: #9ca3af;
    --bg-tag: #374151;
    --bg-input: #374151;
  }
}
</style>
