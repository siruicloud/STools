<template>
  <div class="sidebar">
    <div class="logo-area">
      <div class="logo-icon">
        <svg
          viewBox="0 0 24 24"
          width="24"
          height="24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="16" y1="13" x2="8" y2="13"></line>
          <line x1="16" y1="17" x2="8" y2="17"></line>
          <polyline points="10 9 9 9 8 9"></polyline>
        </svg>
      </div>
      <span class="logo-text">PDF 工具箱</span>
    </div>

    <nav class="nav-menu">
      <button
        class="nav-item"
        :class="{ active: modelValue === 'view' }"
        @click="$emit('update:modelValue', 'view')"
      >
        <span class="nav-icon">👁️</span>
        <span>查看 PDF</span>
      </button>
      <button
        class="nav-item"
        :class="{ active: modelValue === 'merge' }"
        @click="$emit('update:modelValue', 'merge')"
      >
        <span class="nav-icon">🔗</span>
        <span>合并 PDF</span>
      </button>
      <button
        class="nav-item"
        :class="{ active: modelValue === 'split' }"
        @click="$emit('update:modelValue', 'split')"
      >
        <span class="nav-icon">✂️</span>
        <span>拆分 PDF</span>
      </button>
      <button
        class="nav-item"
        :class="{ active: modelValue === 'convert' }"
        @click="$emit('update:modelValue', 'convert')"
      >
        <span class="nav-icon">🔄</span>
        <span>格式转换</span>
      </button>
    </nav>

    <div class="sidebar-footer">
      <button class="btn-open" @click="$emit('open-file')">
        <span class="nav-icon">📂</span> 打开文件
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: 'view' | 'merge' | 'split' | 'convert'
}>()

defineEmits<{
  'update:modelValue': [value: 'view' | 'merge' | 'split' | 'convert']
  'open-file': []
}>()
</script>

<style scoped>
.sidebar {
  width: 220px;
  background: var(--bg-sidebar, #ffffff);
  border-right: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  flex-direction: column;
  height: 100%;
}

.logo-area {
  padding: 24px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.logo-icon {
  width: 32px;
  height: 32px;
  background: var(--primary-color, #3b82f6);
  color: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-text {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-main, #111827);
}

.nav-menu {
  flex: 1;
  padding: 16px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: var(--text-secondary, #4b5563);
  transition: all 0.2s;
  text-align: left;
}

.nav-item:hover {
  background: var(--bg-hover, #f3f4f6);
  color: var(--text-main, #111827);
}

.nav-item.active {
  background: var(--primary-light, #eff6ff);
  color: var(--primary-color, #3b82f6);
}

.nav-icon {
  font-size: 16px;
}

.sidebar-footer {
  padding: 16px;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

.btn-open {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--border-color, #d1d5db);
  background: var(--bg-btn, #ffffff);
  color: var(--text-main, #111827);
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-open:hover {
  background: var(--bg-hover, #f3f4f6);
  border-color: var(--primary-color, #3b82f6);
  color: var(--primary-color, #3b82f6);
}

@media (prefers-color-scheme: dark) {
  .sidebar {
    --bg-sidebar: #1f2937;
    --border-color: #374151;
    --text-main: #f9fafb;
    --text-secondary: #9ca3af;
    --bg-hover: #374151;
    --primary-light: #1e3a8a;
    --bg-btn: #374151;
  }
}
</style>
