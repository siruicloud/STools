<script setup lang="ts">
import { AdaptiveIcon } from '@/components'
import type { Plugin } from '../types'

defineProps<{
  plugin: Plugin
  installingPlugin: string | null
  canUpgrade: boolean
}>()

defineEmits<{
  (e: 'click'): void
  (e: 'open'): void
  (e: 'upgrade'): void
}>()
</script>
<template>
  <div class="card plugin-card" :title="plugin.description" @click="$emit('click')">
    <div class="plugin-icon">
      <AdaptiveIcon
        :src="plugin.logo ?? ''"
        class="plugin-logo-img"
        alt="icon"
        loading="lazy"
        decoding="async"
        draggable="false"
      />
    </div>
    <div class="plugin-info">
      <div class="plugin-name">{{ plugin.title || plugin.name }}</div>
      <div class="plugin-description" :title="plugin.description">
        {{ plugin.description }}
      </div>
    </div>
    <div class="plugin-action">
      <template v-if="plugin.installed">
        <button
          v-if="canUpgrade"
          class="icon-btn upgrade-btn"
          :title="`升级到 v${plugin.version}`"
          :disabled="installingPlugin === plugin.name"
          @click.stop="$emit('upgrade')"
        >
          <div v-if="installingPlugin === plugin.name" class="btn-loading">
            <div class="spinner"></div>
          </div>
          <svg
            v-else
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M12 19V5"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M5 12L12 5L19 12"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M5 21H19"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </button>
        <button v-else class="icon-btn open-btn" title="打开" @click.stop="$emit('open')">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polygon points="5 3 19 12 5 21 5 3"></polygon>
          </svg>
        </button>
      </template>
    </div>
  </div>
</template>

<style scoped>
.plugin-card {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 0;
}

.plugin-card:hover {
  background: var(--hover-bg);
  transform: translateX(2px);
}

.plugin-icon {
  flex-shrink: 0;
  margin-right: 12px;
}

.plugin-logo-img {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  object-fit: cover;
}

.plugin-info {
  flex: 1;
  min-width: 0;
}

.plugin-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.plugin-description {
  font-size: 12px;
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.plugin-action {
  flex-shrink: 0;
  margin-left: 10px;
}

/* 图标按钮颜色样式 */
.open-btn {
  color: var(--primary-color);
}

.open-btn:hover:not(:disabled),
.upgrade-btn:hover:not(:disabled) {
  background: var(--primary-light-bg);
}

.upgrade-btn {
  color: var(--primary-color);
}

/* 按钮 loading 状态 */
.btn-loading {
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid transparent;
  border-top-color: currentColor;
  border-right-color: currentColor;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
