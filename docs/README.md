# STools 商业化文档索引

> 最小化改动官方框架 + 插件独立开发 + 定期同步更新

---

## 📚 文档导航

### 核心文档（必读）

1. **[架构方案](./ARCHITECTURE.md)**
   - 架构概述和设计原则
   - 目录结构详解
   - 开发流程指南
   - 最佳实践
   - **适合**：理解整体架构设计

2. **[插件开发指南](./PLUGIN_DEVELOPMENT.md)**
   - 快速开始（5 分钟上手）
   - 插件结构说明
   - API 完整参考
   - 测试调试方法
   - 构建发布流程
   - **适合**：开发商业插件

3. **[版本同步策略](./SYNC_STRATEGY.md)**
   - 同步策略概述
   - 同步频率和时间表
   - 详细同步流程
   - 冲突处理方法
   - 自动化脚本使用
   - **适合**：定期同步官方更新

---

### 原有文档（参考）

4. **[HTTP API 文档](./http-api.md)**
   - HTTP 接口说明
   - API 端点定义

5. **[Provider 开发指南](./provider-development-guide.md)**
   - Provider 接口开发
   - 第三方服务集成

6. **[同步实现文档](./sync-implementation.md)**
   - 数据同步实现
   - WebDAV 配置

---

## 🚀 快速开始

### 第 1 步：理解架构

```bash
# 阅读架构方案
cat docs/ARCHITECTURE.md
```

**核心要点**：

- 主仓库仅修改 2 个文件（最小化改动）
- 商业功能全部在 custom-plugins/ 开发
- 每周同步官方更新（零冲突）

---

### 第 2 步：开发插件

```bash
# 阅读插件开发指南
cat docs/PLUGIN_DEVELOPMENT.md

# 创建第一个插件
mkdir -p custom-plugins/my-first-plugin
cd custom-plugins/my-first-plugin
# 按照文档创建 plugin.json、package.json 等文件

# 启动开发
cd ../..
./scripts/dev-plugin.sh my-first-plugin
```

---

### 第 3 步：同步更新

```bash
# 阅读同步策略
cat docs/SYNC_STRATEGY.md

# 执行同步脚本
./scripts/sync-upstream.sh
```

---

## 📋 文档结构

```
docs/
├── README.md                    # 本文档（索引）
├── ARCHITECTURE.md              # 架构方案（核心）
├── PLUGIN_DEVELOPMENT.md         # 插件开发指南（核心）
├── SYNC_STRATEGY.md             # 同步策略说明（核心）
├── http-api.md                  # HTTP API 文档
├── provider-development-guide.md # Provider 开发指南
└── sync-implementation.md       # 同步实现文档
```

---

## 🎯 按场景查找文档

### 场景 1：我想理解整体架构

**推荐阅读顺序**：

1. [架构方案](./ARCHITECTURE.md) - 第 1-3 章
2. [插件开发指南](./PLUGIN_DEVELOPMENT.md) - 第 2 章（插件结构）
3. [同步策略](./SYNC_STRATEGY.md) - 第 1 章（概述）

---

### 场景 2：我想开发一个新插件

**推荐阅读顺序**：

1. [插件开发指南](./PLUGIN_DEVELOPMENT.md) - 快速开始
2. [插件开发指南](./PLUGIN_DEVELOPMENT.md) - API 参考
3. [架构方案](./ARCHITECTURE.md) - 最佳实践

---

### 场景 3：我想同步官方更新

**推荐阅读顺序**：

1. [同步策略](./SYNC_STRATEGY.md) - 同步流程
2. [同步策略](./SYNC_STRATEGY.md) - 冲突处理
3. [架构方案](./ARCHITECTURE.md) - 故障排除

---

### 场景 4：我遇到了问题

**推荐阅读顺序**：

1. [架构方案](./ARCHITECTURE.md) - 故障排除
2. [插件开发指南](./PLUGIN_DEVELOPMENT.md) - 测试调试
3. [同步策略](./SYNC_STRATEGY.md) - 故障排除

---

## 💡 文档特点

### 1. **实战导向**

所有文档都包含：

- ✅ 详细的代码示例
- ✅ 完整的命令行操作
- ✅ 真实场景的解决方案

---

### 2. **最小化改动**

所有文档遵循核心原则：

- ✅ 主仓库改动最小（仅 2 个文件）
- ✅ 商业功能隔离（custom-plugins/）
- ✅ 定期同步更新（零冲突）

---

### 3. **自动化优先**

提供完整的自动化脚本：

- ✅ 插件开发脚本（`dev-plugin.sh`）
- ✅ 插件构建脚本（`build-plugin.sh`）
- ✅ 同步更新脚本（`sync-upstream.sh`）

---

## 🔗 相关资源

### 项目根目录文档

- `README.md` - 项目说明
- `CLAUDE.md` - ZTools 完整技术文档
- `PLUGIN_DEVELOPMENT.md` - 快速开发指南
- `CHANGELOG.md` - 更新日志

---

### 子模块文档

- `ztools-api-types/` - 插件 API 类型定义
- `internal-plugins/setting/` - 复杂 UI 插件示例
- `internal-plugins/system/` - 无界面插件示例

---

## 📞 获取帮助

### 文档问题

如果文档中有不清楚的地方：

1. 检查 [架构方案](./ARCHITECTURE.md) 的故障排除章节
2. 查看 [插件开发指南](./PLUGIN_DEVELOPMENT.md) 的常见问题
3. 参考 [同步策略](./SYNC_STRATEGY.md) 的故障排除

---

### 技术问题

如果遇到技术问题：

1. 查看 `CLAUDE.md` 官方文档
2. 检查 `ztools-api-types/` API 类型定义
3. 参考 `internal-plugins/` 内置插件示例

---

## 🎉 开始你的开发之旅

**推荐路线图**：

```
第 1 天：
├─ 阅读 ARCHITECTURE.md（理解架构）
└─ 阅读 PLUGIN_DEVELOPMENT.md 前 3 章（上手插件）

第 2-3 天：
├─ 创建第一个插件
└─ 熟悉核心 API

第 1 周：
├─ 完善插件功能
└─ 学习同步策略

后续：
├─ 定期同步官方更新（每周）
└─ 持续迭代插件功能
```

---

## 📝 文档更新

文档会随着项目演进持续更新：

- **架构变更**：更新 ARCHITECTURE.md
- **API 变化**：更新 PLUGIN_DEVELOPMENT.md
- **同步策略调整**：更新 SYNC_STRATEGY.md

---

## 🙏 反馈

如果你发现文档有误或需要补充：

1. 在项目中创建 Issue
2. 提交 Pull Request
3. 联系维护者

---

**祝你开发愉快！** 🚀
