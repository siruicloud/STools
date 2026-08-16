# 插件打包完整流程指南

本文档说明 seaman 插件包的完整打包、混淆、安装与发布流程。

## 1. 插件包格式（SPK）

| 项目       | 说明                                                                   |
| ---------- | ---------------------------------------------------------------------- |
| 扩展名     | `.spk`                                                                 |
| 内部格式   | ASAR 归档（类 tar，支持随机访问）                                      |
| 压缩       | Brotli 压缩（打包器自动完成）                                          |
| 旧格式兼容 | 安装器自动识别 gzip 格式的旧 `.zpx` 包（基于 magic bytes，与后缀无关） |

### 包内结构

```
your-plugin.spk
├── plugin.json          # 插件配置（入口、功能指令、logo）
├── index.html           # UI 插件入口
├── index.js             # 渲染进程代码（已混淆）
├── preload.js           # preload 脚本（保持明文）
├── logo.png             # 插件图标
└── node_modules/        # 依赖（可选，不混淆）
```

## 2. 源码混淆策略

打包时主进程 `packZpx()` 会对包内 **JS 文件做自动混淆**（javascript-obfuscator）：

| 文件类型                 | 处理                                              |
| ------------------------ | ------------------------------------------------- |
| 渲染层 JS（index.js 等） | ✅ 自动混淆（变量名改写为十六进制、字符串数组化） |
| `preload.js`             | ❌ **跳过**（保持明文，确保 API 行为稳定可调试）  |
| `node_modules/`          | ❌ 跳过（依赖体积大且常被共享）                   |

**混淆参数**（`src/main/utils/zpxArchive.ts`）：

```typescript
{
  compact: true,                 // 压缩输出
  stringArray: true,             // 字符串数组化
  stringArrayThreshold: 0.3,     // 30% 字符串进入数组
  identifierNamesGenerator: 'hexadecimal', // 变量名改 _0x 形式
  renameGlobals: false,          // 保留全局函数名（避免破坏外部引用）
  controlFlowFlattening: false,  // 不启用控制流平坦化（保持性能）
  deadCodeInjection: false       // 不注入死代码
}
```

> 这是"简单混淆"：提高阅读难度、防一般用户提取，**不阻止专业逆向**（与 uTools 的保护水平相当，但多了混淆层）。需要更强保护请考虑数字签名方案。

## 3. 打包流程（开发者工具内）

1. 开发插件：`pnpm dev` 启动应用，进入「设置 → 开发中插件」
2. 绑定插件项目目录（需含 `plugin.json`）
3. 点击「导出插件包」→ 选择保存位置
4. 主进程 `packZpx()` 自动完成：
   ```
   复制源目录到临时副本
     → 混淆副本内 JS（跳过 preload/node_modules）
     → ASAR 打包
     → Brotli 压缩
     → 输出 .spk 文件
   ```
5. 源项目目录**不会被修改**（混淆在临时副本进行）

## 4. 命令行手动打包

如需在 CI 或脚本中打包，可调用主进程工具：

```bash
# 方式一：使用项目内工具（需在 Electron 环境）
# 通过 vitest / electron 环境调用 src/main/utils/zpxArchive.ts 的 packZpx()

# 方式二：手动等价流程（与打包器一致）
mkdir -p /tmp/spk-build
cp -r dist/* /tmp/spk-build/        # 构建产物
cp plugin.json /tmp/spk-build/      # 插件配置
# 混淆 JS（跳过 preload.js）：
npx javascript-obfuscator /tmp/spk-build --output /tmp/spk-build --compact true
# ASAR 打包：
npx asar pack /tmp/spk-build plugin.asar
# Brotli 压缩：
brotli -c plugin.asar > your-plugin.spk
rm -rf /tmp/spk-build plugin.asar
```

## 5. 安装插件

### 方式一：文件安装

1. 打开 seaman 设置 →「已安装插件」
2. 点击「安装本地插件」
3. 选择 `.spk` 文件（文件对话框过滤 `spk`/`zip`）

### 方式二：双击打开

- macOS：双击 `.spk` 文件 → 系统文件关联 → 打开插件安装页
- Windows：双击 `.spk` → 同上

### 方式三：市场安装

1. 插件市场搜索插件 → 点击安装
2. 市场接口返回 `zpxDownloadUrl` → 下载 `.spk` → 自动检测 SPK/ZIP 格式 → 安装

### 安装校验

安装时主进程自动：

- 检测文件格式（Brotli/gzip/ZIP magic bytes）
- 读取 `plugin.json` 校验配置有效性
- 自动处理 `unpack` 规则（`.node` 原生模块自动解包）

## 6. 发布到插件市场

1. 更新版本号：`plugin.json` 的 `version` 字段（`x.y.z` 格式）
2. 打包 `.spk`（见上文）
3. 上传到市场后端（私有部署的 `POST /api/market/plugins` 或市场管理后台）
4. 市场列表展示所需字段：`name`、`version`、`title`、`description`、`logo`、`size`、`downloadCount` 等

## 7. 格式与安全对比

| 维度     | seaman (.spk)        | uTools (.upx)           |
| -------- | -------------------- | ----------------------- |
| 内部格式 | ASAR + Brotli 压缩   | ASAR（无压缩）          |
| 加密     | 无                   | 无                      |
| 源码混淆 | ✅ 自动（渲染层 JS） | 无（preload 强制明文）  |
| 签名     | 未实现（可扩展）     | 无                      |
| 防提取   | 中（混淆提高门槛）   | 低（直接 asar extract） |

## 8. 常见问题

**Q: 打包后插件无法使用？**

- 检查是否依赖了 `preload.js` 中的全局变量（preload 不混淆，但渲染层变量名已改写）
- 确认插件没有依赖全局函数名跨文件调用（`renameGlobals: false` 保留顶层，但模块内部已混淆）

**Q: 旧 .zpx 包还能安装吗？**

- 能。安装器基于 magic bytes 检测格式（gzip/brotli/zip），与后缀无关

**Q: 源目录会被混淆污染吗？**

- 不会。混淆在临时副本进行，打包完成后自动清理
