# ZTools 插件市场后端接口规范

本文档定义了 ZTools 客户端与插件市场服务端交互的 API 规范。
如需自行搭建私有插件市场，请严格遵循此规范实现后端服务。

## 1. 基础配置

### 1.1 Base URL

客户端默认请求地址：

```text
https://your-domain.com/api/market
```

> 注：客户端代码中可通过修改 `DEFAULT_PLUGIN_MARKET_API_BASE` 常量切换地址。

### 1.2 认证机制 (Authentication)

部分接口需要用户登录态。

- **Header**: `Authorization: Bearer <token>`
- **Token 刷新**: 客户端支持通过 `refreshToken` 自动刷新 `token`。
- **错误码**: 未登录或 Token 失效时返回 `401 Unauthorized`。

### 1.3 通用响应格式

```json
{
  "success": true,
  "data": { ... },
  "error": null
}
```

若 `success` 为 `false`，则 `error` 字段包含错误信息。

---

## 2. 数据模型 (Data Models)

### 2.1 PluginMarketPlugin (插件信息)

| 字段            | 类型   | 必填 | 说明                    |
| --------------- | ------ | ---- | ----------------------- |
| `name`          | string | 是   | 插件唯一标识（英文）    |
| `version`       | string | 是   | 语义化版本号 (如 1.0.0) |
| `title`         | string | 否   | 显示名称                |
| `description`   | string | 否   | 简短描述                |
| `logo`          | string | 否   | 图标 URL 或 Data URI    |
| `author`        | string | 否   | 作者名                  |
| `homepage`      | string | 否   | 主页链接                |
| `size`          | number | 否   | 文件大小 (Bytes)        |
| `downloadCount` | number | 否   | 下载次数                |
| `updatedAt`     | number | 否   | 更新时间戳 (ms)         |
| `publishedAt`   | number | 否   | 发布时间戳 (ms)         |
| `categoryId`    | number | 否   | 分类 ID                 |
| `categoryTitle` | string | 否   | 分类名称                |

### 2.2 MarketBannerResponse (轮播图)

| 字段       | 类型   | 说明     |
| ---------- | ------ | -------- |
| `title`    | string | 标题     |
| `imageUrl` | string | 图片 URL |
| `linkUrl`  | string | 跳转链接 |

### 2.3 MarketCategoryResponse (分类)

| 字段          | 类型                 | 说明               |
| ------------- | -------------------- | ------------------ |
| `id`          | number               | 分类 ID            |
| `title`       | string               | 分类名称           |
| `description` | string               | 描述               |
| `logo`        | string               | 图标               |
| `plugins`     | PluginMarketPlugin[] | 该分类下的插件列表 |

---

## 3. API 接口详情

### 3.1 获取市场聚合数据

获取首页所需的所有数据（轮播图、分类、最新插件等）。

- **URL**: `GET /plugins`
- **Query Params**:
  - `limit`: number (推荐数量)
  - `platform`: string (如 'darwin', 'win32')
  - `t`: number (时间戳，用于防缓存)

**Response**:

```json
{
  "banners": [
    /* MarketBannerResponse[] */
  ],
  "categories": [
    /* MarketCategoryResponse[] */
  ],
  "latest": [
    /* PluginMarketPlugin[] */
  ]
}
```

### 3.2 获取推荐插件

- **URL**: `GET /plugins/recommendations`
- **Query Params**:
  - `limit`: number
  - `platform`: string
  - `t`: number

**Response**:

```json
{
  "items": [
    /* PluginMarketPlugin[] */
  ]
}
```

### 3.3 获取指定插件最新版本

用于检查更新或下载。

- **URL**: `GET /plugins/latest`
- **Query Params**:
  - `name`: string (插件名)
  - `platform`: string

**Response**:

```json
{
  "available": true,
  "plugin": {
    /* PluginMarketPlugin */
  }
}
```

_若不可用：`{ "available": false, "reason": "not_found" }`_

### 3.4 获取插件评论列表

- **URL**: `GET /plugins/comments`
- **Query Params**:
  - `pluginName`: string
  - `page`: number
  - `pageSize`: number
  - `anchorId`: number (可选，定位特定评论)

**Response**:

```json
{
  "items": [
    {
      "id": 1,
      "pluginName": "morse-code",
      "uid": "user_123",
      "nickname": "UserA",
      "avatarUrl": "...",
      "content": "很好用！",
      "likeCount": 10,
      "liked": false,
      "parentId": null,
      "parent": null,
      "createdAt": 1700000000000,
      "updatedAt": 1700000000000
    }
  ],
  "page": {
    "page": 1,
    "pageSize": 20,
    "total": 100
  }
}
```

### 3.5 发表评论

- **URL**: `POST /plugins/comments`
- **Auth**: Required
- **Body**:

```json
{
  "pluginName": "morse-code",
  "content": "评论内容",
  "parentId": null
}
```

**Response**: `PluginMarketCommentItem` (新创建的评论对象)

### 3.6 点赞/取消点赞

- **URL**: `POST /plugins/comments/{id}/like`
- **Auth**: Required

**Response**:

```json
{
  "liked": true,
  "likeCount": 11
}
```

### 3.7 删除评论

- **URL**: `DELETE /plugins/comments/{id}`
- **Auth**: Required

**Response**:

```json
{
  "success": true
}
```

---

## 4. 错误处理

| HTTP Status | 含义              | 处理建议                  |
| ----------- | ----------------- | ------------------------- |
| 200         | 成功              | 正常解析 data             |
| 400         | 请求参数错误      | 提示用户检查输入          |
| 401         | 未授权/Token 失效 | 触发客户端 Token 刷新流程 |
| 403         | 权限不足          | 提示用户无权操作          |
| 404         | 资源不存在        | 提示插件或评论不存在      |
| 500         | 服务器错误        | 提示稍后重试              |

## 5. 部署注意事项

1. **CORS**: 必须允许 Electron 渲染进程跨域请求（允许 `*` 或特定 `file://` 协议）。
2. **静态资源**: `logo` 和 `imageUrl` 建议托管在 CDN 上，确保高可用。
3. **平台过滤**: 接口应根据 `platform` 参数过滤不兼容的插件（如 Windows 专属插件不应返回给 macOS 用户）。
4. **版本号比较**: 客户端使用语义化版本比较，请确保 `version` 字段符合 `x.y.z` 格式。
