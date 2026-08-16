import { app, session } from 'electron'
import { X509Certificate } from 'crypto'
import fs from 'fs'
import path from 'path'

/**
 * 信任私有自签 CA（seaman Private CA）。
 *
 * 私有部署的 api.seaman.cc 使用自签 CA 签发的长期证书，
 * Electron net.request 默认走系统信任链会拒绝该证书。
 * 此处通过 setCertificateVerifyProc 仅放行由 seaman CA 签发的服务器证书，
 * 其余主机保持默认校验，避免整体关闭证书验证带来的安全风险。
 */
const PRIVATE_CA_FILENAME = 'seaman-ca.crt'

let privateCa: X509Certificate | null = null

function loadPrivateCa(): X509Certificate | null {
  if (privateCa !== null) return privateCa
  try {
    const candidates = [
      path.join(process.resourcesPath, 'certs', PRIVATE_CA_FILENAME),
      path.join(app.getAppPath(), 'resources', 'certs', PRIVATE_CA_FILENAME),
      path.join(__dirname, '..', '..', '..', '..', 'resources', 'certs', PRIVATE_CA_FILENAME)
    ]
    const file = candidates.find((p) => fs.existsSync(p))
    if (!file) {
      console.warn('[TrustedCert] 未找到私有 CA 证书文件，私有域名证书将不被信任')
      return null
    }
    const pem = fs.readFileSync(file, 'utf-8')
    privateCa = new X509Certificate(pem)
    console.log('[TrustedCert] 私有 CA 已加载:', privateCa.subject)
    return privateCa
  } catch (error) {
    console.error('[TrustedCert] 加载私有 CA 失败:', error)
    return null
  }
}

/** 安装证书信任处理器（在 app ready 后调用一次） */
export function installPrivateCaTrust(): void {
  const ca = loadPrivateCa()
  if (!ca) return

  const sess = session.defaultSession
  sess.setCertificateVerifyProc((request, callback) => {
    try {
      // request.certificate.data 为服务器叶子证书（DER base64）
      if (request.certificate && request.certificate.data) {
        const leafPem = `-----BEGIN CERTIFICATE-----\n${request.certificate.data}\n-----END CERTIFICATE-----`
        const leaf = new X509Certificate(leafPem)
        // 校验叶子证书是否由我们的私有 CA 签发（且 CA 自身是自签根）
        if (leaf.checkIssued(ca) && ca.checkIssued(ca)) {
          callback(0)
          return
        }
      }
    } catch {
      // 证书解析失败则走默认校验
    }
    callback(-3)
  })

  console.log('[TrustedCert] 私有 CA 信任已安装')
}
