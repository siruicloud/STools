# FastAdmin PA 插件系统 API 对接文档

> 版本：v1.0.0  
> 更新时间：2026-08-27  
> 基础URL：`https://your-domain.com`

---

## 目录

1. [概述](#概述)
2. [通用说明](#通用说明)
3. [公共接口](#公共接口)
4. [用户接口](#用户接口)
5. [插件接口](#插件接口)
6. [错误码说明](#错误码说明)
7. [调用示例](#调用示例)

---

## 概述

本文档描述 FastAdmin PA 插件系统的 API 接口规范，适用于移动端 APP、小程序、第三方系统对接。

### 功能模块

| 模块 | 功能               | 需要登录   |
| ---- | ------------------ | ---------- |
| 公共 | 轮播图             | ❌         |
| 用户 | 注册/登录/找回密码 | 部分       |
| 插件 | 列表/详情/下载     | 下载需登录 |

---

## 通用说明

### 请求格式

- 协议：HTTP/HTTPS
- 方式：GET/POST
- 编码：UTF-8
- 格式：`application/x-www-form-urlencoded` 或 `application/json`

### 响应格式

```json
{
  "code": 1, // 状态码：1成功，0失败
  "msg": "success", // 提示信息
  "data": {} // 返回数据
}
```

### 认证方式

**需要登录的接口**，在请求头中携带 Token：

```
Token: your_token_here
```

Token 通过登录接口获取，有效期 7 天。

### 缓存策略

部分接口已实现缓存，缓存时长如下：

| 接口       | 缓存时长 |
| ---------- | -------- |
| 轮播图列表 | 10分钟   |
| 插件列表   | 5分钟    |
| 插件详情   | 10分钟   |

---

## 公共接口

### 1. 轮播图列表

获取首页轮播图列表。

**请求**

```
GET /api/pa.banner/index
```

**参数**

无

**响应**

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "title": "新品上线",
      "bannerimage": "/uploads/banner/1.jpg",
      "status": 1
    }
  ]
}
```

**字段说明**

| 字段        | 类型   | 说明                             |
| ----------- | ------ | -------------------------------- |
| id          | int    | 轮播图ID                         |
| title       | string | 标题                             |
| bannerimage | string | 图片地址（相对路径，需拼接域名） |
| status      | int    | 状态：1显示，0隐藏               |

---

## 用户接口

### 2. 发送邮件验证码

发送注册或找回密码的验证码。

**请求**

```
POST /api/ems/send
```

**参数**

| 参数  | 类型   | 必填 | 说明                                         |
| ----- | ------ | ---- | -------------------------------------------- |
| email | string | ✅   | 邮箱地址                                     |
| event | string | ✅   | 事件类型：`register`注册，`resetpwd`找回密码 |

**响应**

```json
{
  "code": 1,
  "msg": "发送成功"
}
```

**错误情况**

| code | msg                          |
| ---- | ---------------------------- |
| 0    | 邮箱格式错误                 |
| 0    | 发送频繁（60秒内限发1次）    |
| 0    | 已被注册（event=register时） |
| 0    | 未注册（event=resetpwd时）   |

**验证码有效期**

- 6位数字
- 2分钟内有效

---

### 3. 用户注册

使用邮箱注册新用户。

**请求**

```
POST /api/user/register
```

**参数**

| 参数     | 类型   | 必填 | 说明               |
| -------- | ------ | ---- | ------------------ |
| username | string | ✅   | 用户名（3-30字符） |
| password | string | ✅   | 密码（6-30字符）   |
| email    | string | ✅   | 邮箱地址           |
| code     | string | ✅   | 邮箱验证码         |

**响应**

```json
{
  "code": 1,
  "msg": "注册成功",
  "data": {
    "userinfo": {
      "id": 1,
      "username": "zhangsan",
      "email": "zhangsan@example.com",
      "token": "xxxxxxxx"
    }
  }
}
```

**说明**

- 注册成功后自动返回 Token
- 邮箱验证码通过 `/api/ems/send?event=register` 获取

---

### 4. 用户登录

**请求**

```
POST /api/user/login
```

**参数**

| 参数     | 类型   | 必填 | 说明                       |
| -------- | ------ | ---- | -------------------------- |
| account  | string | ✅   | 账号（用户名/邮箱/手机号） |
| password | string | ✅   | 密码                       |

**响应**

```json
{
  "code": 1,
  "msg": "登录成功",
  "data": {
    "userinfo": {
      "id": 1,
      "username": "zhangsan",
      "email": "zhangsan@example.com",
      "token": "xxxxxxxx"
    }
  }
}
```

---

### 5. 找回密码

通过邮箱验证码重置密码。

**请求**

```
POST /api/user/resetpwd
```

**参数**

| 参数        | 类型   | 必填 | 说明               |
| ----------- | ------ | ---- | ------------------ |
| type        | string | ✅   | 类型：`email`      |
| email       | string | ✅   | 邮箱地址           |
| newpassword | string | ✅   | 新密码（6-30字符） |
| captcha     | string | ✅   | 邮箱验证码         |

**响应**

```json
{
  "code": 1,
  "msg": "重置密码成功"
}
```

---

## 插件接口

### 6. 插件列表

获取所有启用的插件列表，支持分页。

**请求**

```
GET /api/pa.plugin/index
```

**参数**

| 参数     | 类型 | 必填 | 说明             |
| -------- | ---- | ---- | ---------------- |
| page     | int  | ❌   | 页码，默认1      |
| pagesize | int  | ❌   | 每页数量，默认20 |

**响应**

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total": 20,
    "list": [
      {
        "id": 1,
        "cname": "用户签到",
        "pname": "user_checkin",
        "description": "每日签到积分奖励",
        "iconimage": "/uploads/plugin/icon.png",
        "type": 0
      }
    ]
  }
}
```

**字段说明**

| 字段        | 类型   | 说明                 |
| ----------- | ------ | -------------------- |
| id          | int    | 插件ID               |
| cname       | string | 中文名称             |
| pname       | string | 英文名称（唯一标识） |
| description | string | 插件描述             |
| iconimage   | string | 插件图标             |
| type        | int    | 类型：0普通，1功能   |

---

### 7. 插件详情

获取单个插件的详细信息及可用版本列表。

**请求**

```
GET /api/pa.plugin/detail
```

**参数**

| 参数 | 类型 | 必填 | 说明   |
| ---- | ---- | ---- | ------ |
| id   | int  | ✅   | 插件ID |

**响应**

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 5,
    "cname": "微信支付",
    "pname": "wechat_pay",
    "description": "微信支付接口集成",
    "iconimage": "/uploads/plugin/wechat_pay.png",
    "type": 1,
    "versions": [
      {
        "id": 12,
        "version": "1.2.0",
        "status_text": "正式"
      },
      {
        "id": 14,
        "version": "1.3.0-rc1",
        "status_text": "灰度"
      }
    ]
  }
}
```

**版本状态说明**

| status_text | 说明                     |
| ----------- | ------------------------ |
| 正式        | 已发布，稳定版本         |
| 灰度        | 灰度发布中，部分用户可见 |
| 体验        | 内测版本，不对外显示     |

---

### 8. 下载插件版本

获取插件版本的下载链接。

**请求**

```
POST /api/pa.plugin_version/download
```

**Headers**

```
Token: your_token_here
```

**参数**

| 参数       | 类型 | 必填 | 说明                         |
| ---------- | ---- | ---- | ---------------------------- |
| version_id | int  | ✅   | 版本ID（从插件详情接口获取） |

**响应**

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "download_url": "/plugins/wechat_pay/1.2.0.zip",
    "downloads": 157
  }
}
```

**说明**

- 需要**登录**才能下载
- 仅支持下载 **正式版** 和 **灰度版**
- `download_url` 为相对路径，需拼接域名
- 每次下载会更新 `downloads` 计数

**错误情况**

| code | msg                 |
| ---- | ------------------- |
| 0    | 请先登录            |
| 0    | 缺少参数 version_id |
| 0    | 版本不存在          |
| 0    | 该版本暂不可下载    |
| 0    | 插件已停用          |

---

## 错误码说明

### HTTP 状态码

| 状态码 | 说明       |
| ------ | ---------- |
| 200    | 请求成功   |
| 401    | 未登录     |
| 403    | 无权限     |
| 404    | 接口不存在 |
| 500    | 服务器错误 |

### 业务状态码（code）

| code | 说明                   |
| ---- | ---------------------- |
| 1    | 成功                   |
| 0    | 失败（具体原因见 msg） |

---

## 调用示例

### cURL

```bash
# 1. 发送验证码
curl -X POST https://your-domain.com/api/ems/send \
  -d "email=user@example.com" \
  -d "event=register"

# 2. 注册
curl -X POST https://your-domain.com/api/user/register \
  -d "username=zhangsan" \
  -d "password=123456" \
  -d "email=user@example.com" \
  -d "code=886432"

# 3. 登录
curl -X POST https://your-domain.com/api/user/login \
  -d "account=zhangsan" \
  -d "password=123456"

# 4. 获取插件列表
curl https://your-domain.com/api/pa.plugin/index?page=1&pagesize=10

# 5. 获取插件详情
curl https://your-domain.com/api/pa.plugin/detail?id=5

# 6. 下载插件（需登录）
curl -X POST https://your-domain.com/api/pa.plugin_version/download \
  -H "Token: your_token_here" \
  -d "version_id=12"
```

### JavaScript (Axios)

```javascript
import axios from 'axios'

const baseURL = 'https://your-domain.com'
const token = localStorage.getItem('token')

// 配置请求
const api = axios.create({
  baseURL,
  headers: token ? { Token: token } : {}
})

// 获取插件列表
async function getPluginList(page = 1, pagesize = 20) {
  const res = await api.get('/api/pa.plugin/index', {
    params: { page, pagesize }
  })
  return res.data
}

// 下载插件
async function downloadPlugin(versionId) {
  const res = await api.post('/api/pa.plugin_version/download', {
    version_id: versionId
  })
  return res.data
}
```

### Python (requests)

```python
import requests

BASE_URL = 'https://your-domain.com'

def get_plugin_list(page=1, pagesize=20):
    """获取插件列表"""
    res = requests.get(f'{BASE_URL}/api/pa.plugin/index', params={
        'page': page,
        'pagesize': pagesize
    })
    return res.json()

def download_plugin(version_id, token):
    """下载插件"""
    res = requests.post(
        f'{BASE_URL}/api/pa.plugin_version/download',
        data={'version_id': version_id},
        headers={'Token': token}
    )
    return res.json()
```

---

## 附录

### 完整接口列表

| 接口       | 方法 | 路由                            | 需登录 | 缓存   |
| ---------- | ---- | ------------------------------- | ------ | ------ |
| 轮播图列表 | GET  | /api/pa.banner/index            | ❌     | 10分钟 |
| 发送验证码 | POST | /api/ems/send                   | ❌     | ❌     |
| 用户注册   | POST | /api/user/register              | ❌     | ❌     |
| 用户登录   | POST | /api/user/login                 | ❌     | ❌     |
| 找回密码   | POST | /api/user/resetpwd              | ❌     | ❌     |
| 插件列表   | GET  | /api/pa.plugin/index            | ❌     | 5分钟  |
| 插件详情   | GET  | /api/pa.plugin/detail           | ❌     | 10分钟 |
| 下载插件   | POST | /api/pa.plugin_version/download | ✅     | ❌     |

### 技术支持

如有疑问，请联系技术支持。

---

**© 2026 FastAdmin PA Plugin System**
