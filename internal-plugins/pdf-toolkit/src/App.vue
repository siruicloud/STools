<template>
  <div class="app-container">
    <Sidebar v-model="currentMode" @open-file="openFilePicker" />

    <main class="main-content">
      <PdfViewer
        v-if="currentMode === 'view'"
        :src="currentPdf"
        @open-file="openFilePicker"
        @close="currentPdf = null"
      />
      <PdfMerger v-else-if="currentMode === 'merge'" />
      <PdfSplitter
        v-else-if="currentMode === 'split'"
        :src="currentPdf"
        :total-pages="totalPages"
        @open-file="openFilePicker"
      />
      <PdfConverter v-else-if="currentMode === 'convert'" />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import Sidebar from './components/Sidebar.vue'
import PdfViewer from './components/PdfViewer.vue'
import PdfMerger from './components/PdfMerger.vue'
import PdfSplitter from './components/PdfSplitter.vue'
import PdfConverter from './components/PdfConverter.vue'

const currentMode = ref<'view' | 'merge' | 'split' | 'convert'>('view')
const currentPdf = ref<string | null>(null)
const totalPages = ref(0)

function openFilePicker() {
  const files = window.ztools.showOpenDialog({
    title: '选择 PDF 文件',
    filters: [{ name: 'PDF', extensions: ['pdf'] }],
    properties: ['openFile']
  })

  if (files && files.length > 0) {
    currentPdf.value = `file://${files[0]}`
    currentMode.value = 'view'
  }
}

function handleLoadPdf(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail.files && detail.files.length > 0) {
    currentPdf.value = `file://${detail.files[0]}`
    currentMode.value = 'view'
  }
}

function handleTrigger(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail.action === 'merge') {
    currentMode.value = 'merge'
  } else if (detail.action === 'convert') {
    currentMode.value = 'convert'
  } else if (detail.action === 'open') {
    openFilePicker()
  }
}

function handlePageCount(event: Event) {
  const detail = (event as CustomEvent).detail
  if (detail.pages) {
    totalPages.value = detail.pages
  }
}

onMounted(() => {
  window.addEventListener('ztools-load-pdf', handleLoadPdf)
  window.addEventListener('ztools-trigger-action', handleTrigger)
  window.addEventListener('ztools-pdf-pages', handlePageCount)
})

onUnmounted(() => {
  window.removeEventListener('ztools-load-pdf', handleLoadPdf)
  window.removeEventListener('ztools-trigger-action', handleTrigger)
  window.removeEventListener('ztools-pdf-pages', handlePageCount)
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
  font-family:
    -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.app-container {
  display: flex;
  height: 100vh;
  background: var(--bg-main, #f3f4f6);
  color: var(--text-main, #111827);
}

.main-content {
  flex: 1;
  overflow: hidden;
  position: relative;
}

@media (prefers-color-scheme: dark) {
  .app-container {
    --bg-main: #111827;
    --text-main: #f9fafb;
  }
}
</style>
