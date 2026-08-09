# 版本同步策略

> 定期同步官方更新，保持最小冲突

---

## 📋 目录

- [同步策略概述](#同步策略概述)
- [同步频率](#同步频率)
- [同步流程](#同步流程)
- [冲突处理](#冲突处理)
- [自动化脚本](#自动化脚本)
- [最佳实践](#最佳实践)

---

## 同步策略概述

### 核心原则

**定期同步 + 最小改动 + 零冲突**

```
主仓库改动（最小化）：
├── package.json（仅修改 name）
└── LICENSE（法律要求）

商业功能隔离：
└── custom-plugins/（独立开发）

同步结果：
└── 几乎零冲突 ✅
```

---

### 为什么需要同步？

**必须同步的原因**：

1. ✅ **获取最新功能**：官方持续添加新功能
2. ✅ **获取安全补丁**：修复安全漏洞
3. ✅ **保持兼容性**：插件 API 可能变化
4. ✅ **获取性能优化**：性能改进

---

### 同步风险分析

| 风险类型     | 传统方案（修改主仓库）    | 最小化改动方案         |
| ------------ | ------------------------- | ---------------------- |
| **合并冲突** | ⚠️ 高风险（多个文件冲突） | ✅ 极低（仅 2 个文件） |
| **功能破坏** | ⚠️ 可能（修改了核心代码） | ✅ 极低（未修改核心）  |
| **插件失效** | ⚠️ 可能（API 变化）       | ✅ 低（插件独立）      |
| **维护成本** | ⚠️ 高（每次需解决冲突）   | ✅ 低（几乎无冲突）    |

---

## 同步频率

### 推荐频率：每周一次

**建议时间**：周一上午

**原因**：

- 官方通常在工作日发布更新
- 周末有时间测试验证
- 周一合并，一周时间适应

---

### 同步时间表

```
周一：
├─ 上午：同步官方更新
├─ 下午：测试验证
└─ 晚上：推送合并

周二：
└─ 观察运行状态

周三：
└─ 如有问题，快速修复

每周重复此流程
```

---

### 特殊情况

**需要立即同步的场景**：

1. **安全补丁**：官方发布安全更新
2. **重大 Bug**：影响核心功能
3. **API 破坏性变更**：插件可能失效

**判断标准**：

```bash
# 查看官方更新日志
git log HEAD..upstream/main --oneline

# 如果包含以下关键词，立即同步：
# - "security"
# - "fix critical bug"
# - "breaking change"
```

---

## 同步流程

### 标准流程（自动化）

```bash
# 1. 运行同步脚本
./scripts/sync-upstream.sh

# 脚本会自动执行：
# - 拉取官方更新
# - 显示更新日志
# - 确认合并
# - 自动测试
# - 验证插件
```

---

### 手动流程（详细步骤）

#### 第 1 步：拉取官方最新代码

```bash
# 1. 确保 upstream 已配置
git remote -v
# 输出应包含：
# upstream  https://github.com/ZToolsCenter/ZTools.git (fetch)

# 如果没有，添加 upstream
git remote add upstream https://github.com/ZToolsCenter/ZTools.git

# 2. 拉取最新代码
git fetch upstream

# 3. 查看更新日志
git log HEAD..upstream/main --oneline --decorate
```

---

#### 第 2 步：创建同步分支

```bash
# 1. 确保在 main 分支
git checkout main

# 2. 拉取最新代码
git pull origin main

# 3. 创建同步分支
git checkout -b sync/ztools-v3.0.3
```

---

#### 第 3 步：合并官方更新

```bash
# 合并 upstream/main
git merge upstream/main

# 如果没有冲突，会自动提交
# 如果有冲突，继续下一步
```

---

#### 第 4 步：解决冲突（如果有）

**冲突极少的文件**：

```
可能冲突的文件：
└── package.json（仅 name 字段）
```

**解决步骤**：

```bash
# 1. 查看冲突文件
git status

# 2. 打开冲突文件
# 例如：package.json

# 3. 手动解决冲突
# <<<<<<< HEAD
#   "name": "your-product",      ← 保留你的
# =======
#   "name": "ZTools",             ← 官方的
# >>>>>>> upstream/main

# 保留你的修改：
{
  "name": "your-product",  // ✅ 保留
  "version": "3.0.3",      // ✅ 使用官方的
  // ... 其他字段使用官方的
}

# 4. 标记冲突已解决
git add package.json

# 5. 提交解决
git commit -m "merge: 解决 package.json 冲突"
```

---

#### 第 5 步：测试验证

```bash
# 1. 安装依赖
pnpm install

# 2. 类型检查
pnpm typecheck

# 3. 构建
pnpm build

# 4. 运行测试
pnpm test

# 5. 手动测试
pnpm dev

# 测试清单：
# ✅ 应用正常启动
# ✅ 官方功能正常
# ✅ 插件正常加载
# ✅ 数据库正常
```

---

#### 第 6 步：推送到远程

```bash
# 1. 推送同步分支
git push origin sync/ztools-v3.0.3

# 2. 创建 Pull Request
gh pr create \
  --base main \
  --head sync/ztools-v3.0.3 \
  --title "同步 ZTools v3.0.3 更新" \
  --body "同步官方最新版本"

# 3. 审查通过后合并 PR
# 在 GitHub 上操作

# 4. 删除同步分支
git branch -d sync/ztools-v3.0.3
git push origin --delete sync/ztools-v3.0.3
```

---

#### 第 7 步：更新插件（如果需要）

**如果官方更新了插件 API**：

```bash
# 1. 检查 API 变化
git diff HEAD..upstream/main -- src/main/api/plugin/

# 2. 查看更新日志
cat CHANGELOG.md

# 3. 更新插件代码（如果需要）
cd custom-plugins/your-plugin
# 根据 API 变化修改代码

# 4. 测试插件
pnpm dev
# 在 STools 中测试插件功能
```

---

## 冲突处理

### 冲突类型分析

| 冲突类型         | 可能性 | 原因             | 解决难度             |
| ---------------- | ------ | ---------------- | -------------------- |
| **package.json** | ⚠️ 低  | 官方修改 version | ✅ 简单（保留 name） |
| **LICENSE**      | ❌ 无  | 官方不会修改     | ✅ 无需处理          |
| **其他文件**     | ❌ 无  | 我们未修改       | ✅ 无冲突            |

---

### 冲突解决策略

#### 策略 1：保留最小改动

```json
// package.json 冲突解决

// 官方的（upstream/main）：
{
  "name": "ZTools",
  "version": "3.0.3",
  "dependencies": { ... }
}

// 你的（HEAD）：
{
  "name": "your-product",
  "version": "3.0.2",
  "dependencies": { ... }
}

// 最终结果（合并后）：
{
  "name": "your-product",      // ✅ 保留你的（品牌标识）
  "version": "3.0.3",          // ✅ 使用官方的（最新版本）
  "dependencies": { ... }      // ✅ 使用官方的（最新依赖）
}
```

---

#### 策略 2：使用 Git 工具

```bash
# 使用 git checkout 解决冲突
# 保留你的 name，其他用官方的

git checkout --ours package.json      # 使用你的版本
# 手动编辑 package.json，更新 version 为官方的

# 或使用 merge 工具
git mergetool package.json
```

---

### 预防冲突的最佳实践

1. ✅ **仅修改必要字段**：

   ```json
   // ✅ 好的做法
   {
     "name": "your-product"  // 仅修改 name
   }

   // ❌ 避免
   {
     "name": "your-product",
     "version": "1.0.0",     // 不要修改
     "dependencies": { ... }  // 不要修改
   }
   ```

2. ✅ **商业功能放在插件**：

   ```bash
   # 所有商业功能在插件目录开发
   custom-plugins/your-product-enterprise/
   ```

3. ✅ **定期同步**：
   ```bash
   # 每周同步，避免积累大量更新
   ./scripts/sync-upstream.sh
   ```

---

## 自动化脚本

### 完整同步脚本

```bash
#!/bin/bash
# scripts/sync-upstream.sh

set -e

echo "📥 同步官方最新更新"

# ============================================
# 1. 拉取官方更新
# ============================================

echo ""
echo "拉取官方最新代码..."
git fetch upstream

# ============================================
# 2. 显示更新日志
# ============================================

echo ""
echo "官方更新日志："
git log HEAD..upstream/main --oneline --decorate --color

# 统计更新数量
UPDATE_COUNT=$(git log HEAD..upstream/main --oneline | wc -l | tr -d ' ')
echo ""
echo "发现 $UPDATE_COUNT 个更新"

# ============================================
# 3. 检查是否需要同步
# ============================================

if [ "$UPDATE_COUNT" -eq 0 ]; then
    echo "✅ 已经是最新版本，无需同步"
    exit 0
fi

# ============================================
# 4. 确认合并
# ============================================

echo ""
echo "⚠️  合并前请确保："
echo "  1. 已提交当前所有更改"
echo "  2. 已备份重要数据"
echo ""
read -p "确认合并官方更新？(y/n) " -n 1 -r
echo

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ 取消合并"
    exit 0
fi

# ============================================
# 5. 创建同步分支
# ============================================

BRANCH_NAME="sync/ztools-$(date +%Y%m%d)"
echo ""
echo "创建同步分支：$BRANCH_NAME"
git checkout -b "$BRANCH_NAME"

# ============================================
# 6. 合并官方更新
# ============================================

echo ""
echo "合并官方更新..."

if git merge upstream/main --no-edit; then
    echo "✅ 合并成功（无冲突）"
else
    echo ""
    echo "⚠️  检测到冲突，需要手动解决"
    echo ""
    echo "冲突文件："
    git status --short | grep "^UU\|^AA\|^DD"

    echo ""
    echo "请手动解决冲突后，执行："
    echo "  git add ."
    echo "  git commit -m 'merge: 解决冲突'"
    echo "  git push origin $BRANCH_NAME"

    exit 1
fi

# ============================================
# 7. 测试验证
# ============================================

echo ""
echo "开始测试验证..."

# 安装依赖
echo "安装依赖..."
pnpm install --frozen-lockfile

# 类型检查
echo "类型检查..."
pnpm typecheck

# 构建
echo "构建应用..."
pnpm build

# 测试
echo "运行测试..."
if pnpm test; then
    echo ""
    echo "✅ 测试通过"
else
    echo ""
    echo "❌ 测试失败"
    echo "请检查并修复问题"
    exit 1
fi

# ============================================
# 8. 推送同步分支
# ============================================

echo ""
echo "推送同步分支..."
git push origin "$BRANCH_NAME"

# ============================================
# 9. 创建 Pull Request
# ============================================

echo ""
read -p "是否创建 Pull Request？(y/n) " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]; then
    gh pr create \
        --base main \
        --head "$BRANCH_NAME" \
        --title "同步官方更新（$UPDATE_COUNT 个提交）" \
        --body "## 同步内容

同步 ZTools 官方最新更新。

### 更新数量
- $UPDATE_COUNT 个提交

### 测试验证
- ✅ 类型检查通过
- ✅ 构建成功
- ✅ 测试通过

### 冲突情况
- 无冲突（或已手动解决）"

    echo ""
    echo "✅ Pull Request 已创建"
    echo "请在 GitHub 上审查并合并"
fi

echo ""
echo "========================================"
echo "🎉 同步流程完成！"
echo "========================================"
```

---

### 使用说明

```bash
# 1. 确保脚本可执行
chmod +x scripts/sync-upstream.sh

# 2. 运行脚本
./scripts/sync-upstream.sh

# 3. 脚本会自动：
# - 拉取官方更新
# - 显示更新日志
# - 确认合并
# - 创建同步分支
# - 合并更新
# - 测试验证
# - 推送分支
# - 创建 Pull Request
```

---

## 最佳实践

### 1. 同步前检查清单

```bash
# 执行同步前，确认：
✅ 已提交当前所有更改
✅ 已推送到远程仓库
✅ 已备份重要数据（可选）
✅ 时间充足（预留 1 小时）
✅ 网络连接稳定
```

---

### 2. 同步后验证清单

```bash
# 同步后，验证以下内容：
✅ 应用正常启动
✅ 类型检查通过
✅ 构建成功
✅ 测试通过
✅ 插件正常加载
✅ 数据库正常工作
✅ 配置文件正常
✅ 快捷键正常
```

---

### 3. 版本号管理

```bash
# 同步后的版本号
{
  "name": "your-product",
  "version": "3.0.3"  // ← 与官方版本一致
}

# 如果有定制改动，可以添加后缀
{
  "name": "your-product",
  "version": "3.0.3-custom.1"
}
```

---

### 4. 文档更新

同步后更新以下文档：

```bash
# 1. CHANGELOG.md
## [3.0.3] - 2026-08-06
### Synced
- 同步 ZTools v3.0.3 官方更新
- 新增功能：...

# 2. README.md（如果需要）
- 基于 ZTools 版本：v3.0.3

# 3. 文档（如果有 API 变化）
docs/PLUGIN_DEVELOPMENT.md
```

---

### 5. 插件兼容性检查

```bash
# 同步后检查插件兼容性

# 1. 查看 API 变化
git diff HEAD~1 HEAD -- src/main/api/plugin/

# 2. 查看类型定义变化
git diff HEAD~1 HEAD -- ztools-api-types/

# 3. 测试插件
cd custom-plugins/your-plugin
pnpm dev

# 4. 检查控制台是否有警告或错误
```

---

## 故障排除

### 问题 1：合并冲突过多

**症状**：

- 多个文件冲突
- 无法自动合并

**原因**：

- 修改了主仓库多个文件
- 长时间未同步

**解决**：

```bash
# 方案 1：回退到官方版本，重新应用改动
git checkout upstream/main -- .
# 手动重新应用你的改动（仅 name 和 LICENSE）

# 方案 2：创建新分支，从头开始
git checkout -b fresh-start upstream/main
# 仅修改必要的 2 个文件
```

---

### 问题 2：插件失效

**症状**：

- 插件无法加载
- API 调用失败

**原因**：

- 官方更新了插件 API
- 类型定义变化

**解决**：

```bash
# 1. 查看 API 变化
git diff HEAD~1 HEAD -- src/main/api/plugin/

# 2. 查看官方文档
cat CLAUDE.md | grep -A 20 "插件 API"

# 3. 更新插件代码
cd custom-plugins/your-plugin
# 根据 API 变化修改代码

# 4. 更新类型定义
pnpm install @ztools-center/ztools-api-types@latest
```

---

### 问题 3：构建失败

**症状**：

- `pnpm build` 报错
- 类型检查失败

**原因**：

- 依赖版本冲突
- TypeScript 版本不兼容

**解决**：

```bash
# 1. 清理并重新安装
rm -rf node_modules pnpm-lock.yaml
pnpm install

# 2. 更新依赖
pnpm update

# 3. 检查 TypeScript 版本
pnpm list typescript

# 4. 重新构建
pnpm build
```

---

## 总结

### 核心要点

1. ✅ **定期同步**：每周一次，周一上午
2. ✅ **最小改动**：仅修改 2 个文件
3. ✅ **自动化脚本**：使用脚本简化流程
4. ✅ **测试验证**：同步后必须测试
5. ✅ **版本管理**：保持与官方版本一致

---

### 优势总结

| 方面           | 优势         |
| -------------- | ------------ |
| **冲突风险**   | ✅ 几乎为零  |
| **维护成本**   | ✅ 极低      |
| **同步时间**   | ✅ 5-10 分钟 |
| **测试工作量** | ✅ 低        |
| **版本一致性** | ✅ 高        |

---

### 下一步

- 阅读 [架构方案](./ARCHITECTURE.md)
- 阅读 [插件开发指南](./PLUGIN_DEVELOPMENT.md)
- 设置自动化脚本
- 开始定期同步

---

**祝你同步顺利！** 🚀
