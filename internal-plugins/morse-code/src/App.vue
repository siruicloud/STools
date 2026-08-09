<template>
  <div class="morse-container">
    <div class="header">
      <h1>莫斯密码工具</h1>
      <p class="subtitle">加密 · 解密 · 对照表</p>
    </div>

    <!-- 模式切换 -->
    <div class="mode-tabs">
      <button :class="['tab-btn', { active: mode === 'encode' }]" @click="mode = 'encode'">
        加密
      </button>
      <button :class="['tab-btn', { active: mode === 'decode' }]" @click="mode = 'decode'">
        解密
      </button>
      <button :class="['tab-btn', { active: mode === 'table' }]" @click="mode = 'table'">
        对照表
      </button>
    </div>

    <!-- 加密模式 -->
    <div v-if="mode === 'encode'" class="content-section">
      <div class="input-group">
        <label>输入文本</label>
        <textarea
          v-model="inputText"
          placeholder="请输入要加密的文本（支持字母、数字、标点）"
          rows="4"
          @input="handleEncode"
        ></textarea>
      </div>

      <div class="actions">
        <button class="btn btn-primary" @click="handleEncode" :disabled="!inputText">加密</button>
        <button class="btn btn-secondary" @click="copyResult" :disabled="!encodeResult">
          复制结果
        </button>
        <button class="btn btn-secondary" @click="clearEncode">清空</button>
      </div>

      <div v-if="encodeResult" class="result-box">
        <label>加密结果</label>
        <div class="morse-output">{{ encodeResult }}</div>
      </div>

      <div v-if="encodeError" class="error-box">
        {{ encodeError }}
      </div>
    </div>

    <!-- 解密模式 -->
    <div v-if="mode === 'decode'" class="content-section">
      <div class="input-group">
        <label>输入莫斯密码</label>
        <textarea
          v-model="morseInput"
          placeholder="请输入莫斯密码（使用 . - / 和空格，例如：.... . .-.. .-.. --- / .-- --- .-. .-.. -..）"
          rows="4"
          @input="handleDecode"
        ></textarea>
      </div>

      <div class="actions">
        <button class="btn btn-primary" @click="handleDecode" :disabled="!morseInput">解密</button>
        <button class="btn btn-secondary" @click="copyDecoded" :disabled="!decodeResult">
          复制结果
        </button>
        <button class="btn btn-secondary" @click="clearDecode">清空</button>
      </div>

      <div v-if="decodeResult" class="result-box">
        <label>解密结果</label>
        <div class="text-output">{{ decodeResult }}</div>
      </div>

      <div v-if="decodeError" class="error-box">
        {{ decodeError }}
      </div>
    </div>

    <!-- 对照表模式 -->
    <div v-if="mode === 'table'" class="content-section table-section">
      <div class="morse-table">
        <div class="table-header">
          <span>字符</span>
          <span>莫斯密码</span>
          <span>字符</span>
          <span>莫斯密码</span>
        </div>
        <div v-for="(row, index) in tableRows" :key="index" class="table-row">
          <span class="char">{{ row[0]?.char || '' }}</span>
          <span class="morse">{{ row[0]?.morse || '' }}</span>
          <span class="char">{{ row[1]?.char || '' }}</span>
          <span class="morse">{{ row[1]?.morse || '' }}</span>
        </div>
      </div>
    </div>

    <!-- 底部提示 -->
    <div class="footer">
      <p>提示：加密/解密结果会自动复制到剪贴板</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { encode, decode, getMorseCodeTable } from './utils/morse'

const mode = ref<'encode' | 'decode' | 'table'>('encode')

// 加密相关
const inputText = ref('')
const encodeResult = ref('')
const encodeError = ref('')

// 解密相关
const morseInput = ref('')
const decodeResult = ref('')
const decodeError = ref('')

// 对照表
const morseTable = getMorseCodeTable()

// 将对照表转换为二维数组（每行 2 对）
const tableRows = computed(() => {
  const entries = Object.entries(morseTable)
    .filter(([char]) => char !== ' ') // 过滤空格
    .map(([char, morse]) => ({ char, morse }))

  const rows: Array<Array<{ char: string; morse: string }>> = []
  for (let i = 0; i < entries.length; i += 2) {
    rows.push([entries[i], entries[i + 1] || null])
  }
  return rows
})

// 加密处理
function handleEncode(): void {
  if (!inputText.value.trim()) {
    encodeResult.value = ''
    encodeError.value = ''
    return
  }

  const result = encode(inputText.value)
  if (result.success) {
    encodeResult.value = result.result
    encodeError.value = ''
  } else {
    encodeResult.value = ''
    encodeError.value = result.error || '加密失败'
  }
}

// 解密处理
function handleDecode(): void {
  if (!morseInput.value.trim()) {
    decodeResult.value = ''
    decodeError.value = ''
    return
  }

  const result = decode(morseInput.value)
  if (result.success) {
    decodeResult.value = result.result
    decodeError.value = ''
  } else {
    decodeResult.value = ''
    decodeError.value = result.error || '解密失败'
  }
}

// 复制加密结果
function copyResult(): void {
  if (encodeResult.value) {
    window.ztools.copyText(encodeResult.value)
    window.ztools.showNotification('加密结果已复制到剪贴板')
  }
}

// 复制解密结果
function copyDecoded(): void {
  if (decodeResult.value) {
    window.ztools.copyText(decodeResult.value)
    window.ztools.showNotification('解密结果已复制到剪贴板')
  }
}

// 清空加密
function clearEncode(): void {
  inputText.value = ''
  encodeResult.value = ''
  encodeError.value = ''
}

// 清空解密
function clearDecode(): void {
  morseInput.value = ''
  decodeResult.value = ''
  decodeError.value = ''
}
</script>

<style scoped>
.morse-container {
  padding: 16px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: var(--text-color, #333);
  background: var(--bg-color, #fff);
  min-height: 100%;
}

.header {
  text-align: center;
  margin-bottom: 16px;
}

.header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.subtitle {
  margin: 4px 0 0;
  font-size: 12px;
  color: var(--text-secondary, #666);
}

.mode-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}

.tab-btn {
  flex: 1;
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  background: var(--bg-secondary, #f5f5f5);
  color: var(--text-color, #333);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: var(--bg-hover, #e8e8e8);
}

.tab-btn.active {
  background: var(--primary-color, #4a90d9);
  color: #fff;
}

.content-section {
  margin-bottom: 16px;
}

.input-group {
  margin-bottom: 12px;
}

.input-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary, #666);
}

textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
  background: var(--input-bg, #fff);
  color: var(--text-color, #333);
  font-size: 14px;
  font-family: 'Courier New', monospace;
  resize: vertical;
  box-sizing: border-box;
}

textarea:focus {
  outline: none;
  border-color: var(--primary-color, #4a90d9);
  box-shadow: 0 0 0 2px rgba(74, 144, 217, 0.2);
}

.actions {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
}

.btn {
  flex: 1;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary-color, #4a90d9);
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover, #357abd);
}

.btn-secondary {
  background: var(--bg-secondary, #f5f5f5);
  color: var(--text-color, #333);
}

.btn-secondary:hover:not(:disabled) {
  background: var(--bg-hover, #e8e8e8);
}

.result-box {
  background: var(--result-bg, #f0f7ff);
  border: 1px solid var(--result-border, #b3d9ff);
  border-radius: 6px;
  padding: 12px;
}

.result-box label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 500;
  color: var(--text-secondary, #666);
}

.morse-output {
  font-family: 'Courier New', monospace;
  font-size: 16px;
  color: var(--primary-color, #4a90d9);
  word-break: break-all;
  line-height: 1.6;
}

.text-output {
  font-size: 16px;
  color: var(--text-color, #333);
  word-break: break-all;
  line-height: 1.6;
}

.error-box {
  background: var(--error-bg, #fff2f0);
  border: 1px solid var(--error-border, #ffccc7);
  border-radius: 6px;
  padding: 12px;
  color: var(--error-color, #ff4d4f);
  font-size: 13px;
}

/* 对照表样式 */
.table-section {
  max-height: 400px;
  overflow-y: auto;
}

.morse-table {
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
  overflow: hidden;
}

.table-header {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr 2fr;
  background: var(--bg-secondary, #f5f5f5);
  padding: 8px 12px;
  font-weight: 600;
  font-size: 13px;
  color: var(--text-secondary, #666);
  border-bottom: 1px solid var(--border-color, #ddd);
}

.table-row {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr 2fr;
  padding: 6px 12px;
  border-bottom: 1px solid var(--border-color, #eee);
  font-size: 14px;
}

.table-row:last-child {
  border-bottom: none;
}

.table-row:hover {
  background: var(--bg-hover, #f5f5f5);
}

.char {
  font-weight: 600;
  color: var(--text-color, #333);
}

.morse {
  font-family: 'Courier New', monospace;
  color: var(--primary-color, #4a90d9);
}

.footer {
  text-align: center;
  padding-top: 12px;
  border-top: 1px solid var(--border-color, #eee);
}

.footer p {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary, #999);
}

/* 暗色模式适配 */
@media (prefers-color-scheme: dark) {
  .morse-container {
    --text-color: #e0e0e0;
    --text-secondary: #999;
    --bg-color: #1e1e1e;
    --bg-secondary: #2d2d2d;
    --bg-hover: #3d3d3d;
    --input-bg: #2d2d2d;
    --border-color: #404040;
    --result-bg: #1a2a3a;
    --result-border: #2a4a6a;
    --error-bg: #2a1a1a;
    --error-border: #4a2a2a;
    --error-color: #ff6b6b;
    --primary-color: #5ba0e0;
    --primary-hover: #4a8fd0;
  }
}
</style>
