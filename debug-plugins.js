const fs = require('fs')
const path = require('path')

const logFile = '/tmp/ztools-plugin-debug.log'

function log(msg) {
  const timestamp = new Date().toISOString()
  const line = `[${timestamp}] ${msg}\n`
  fs.appendFileSync(logFile, line)
  console.log(line.trim())
}

// 清空日志文件
fs.writeFileSync(logFile, '')
log('日志文件已创建')

module.exports = { log }
