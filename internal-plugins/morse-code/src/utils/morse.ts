// 国际莫斯密码对照表
const MORSE_CODE_MAP: Record<string, string> = {
  // 字母
  A: '.-',
  B: '-...',
  C: '-.-.',
  D: '-..',
  E: '.',
  F: '..-.',
  G: '--.',
  H: '....',
  I: '..',
  J: '.---',
  K: '-.-',
  L: '.-..',
  M: '--',
  N: '-.',
  O: '---',
  P: '.--.',
  Q: '--.-',
  R: '.-.',
  S: '...',
  T: '-',
  U: '..-',
  V: '...-',
  W: '.--',
  X: '-..-',
  Y: '-.--',
  Z: '--..',
  // 数字
  '0': '-----',
  '1': '.----',
  '2': '..---',
  '3': '...--',
  '4': '....-',
  '5': '.....',
  '6': '-....',
  '7': '--...',
  '8': '---..',
  '9': '----.',
  // 标点符号
  '.': '.-.-.-',
  ',': '--..--',
  '?': '..--..',
  "'": '.----.',
  '!': '-.-.--',
  '/': '-..-.',
  '(': '-.--.',
  ')': '-.--.-',
  '&': '.-...',
  ':': '---...',
  ';': '-.-.-.',
  '=': '-...-',
  '+': '.-.-.',
  '-': '-....-',
  _: '..--.-',
  '"': '.-..-.',
  $: '...-..-',
  '@': '.--.-.',
  // 中文空格用特殊符号
  ' ': '/'
}

// 反向映射表（莫斯密码 → 字符）
const REVERSE_MORSE_MAP: Record<string, string> = {}
for (const [char, morse] of Object.entries(MORSE_CODE_MAP)) {
  REVERSE_MORSE_MAP[morse] = char
}

export interface MorseResult {
  success: boolean
  result: string
  error?: string
}

/**
 * 将文本编码为莫斯密码
 * @param text - 要编码的文本
 * @returns 编码结果
 */
export function encode(text: string): MorseResult {
  if (!text || text.trim().length === 0) {
    return { success: false, result: '', error: '请输入要加密的文本' }
  }

  try {
    const upperText = text.toUpperCase()
    const morseChars: string[] = []

    for (const char of upperText) {
      if (MORSE_CODE_MAP[char]) {
        morseChars.push(MORSE_CODE_MAP[char])
      } else if (char === ' ') {
        morseChars.push('/')
      } else {
        // 不支持的字符用 ? 代替
        morseChars.push('..--..')
      }
    }

    return {
      success: true,
      result: morseChars.join(' ')
    }
  } catch (error) {
    return {
      success: false,
      result: '',
      error: `编码失败: ${error instanceof Error ? error.message : '未知错误'}`
    }
  }
}

/**
 * 将莫斯密码解码为文本
 * @param morse - 要解码的莫斯密码
 * @returns 解码结果
 */
export function decode(morse: string): MorseResult {
  if (!morse || morse.trim().length === 0) {
    return { success: false, result: '', error: '请输入要解密的莫斯密码' }
  }

  try {
    // 验证输入是否只包含有效的莫斯密码字符
    const validChars = /^[\.\-\/\s]+$/
    if (!validChars.test(morse)) {
      return {
        success: false,
        result: '',
        error: '无效的莫斯密码格式，只支持 . - / 和空格'
      }
    }

    const morseWords = morse.trim().split(/\s+/)
    const decodedChars: string[] = []

    for (const word of morseWords) {
      if (word === '/') {
        decodedChars.push(' ')
      } else if (REVERSE_MORSE_MAP[word]) {
        decodedChars.push(REVERSE_MORSE_MAP[word])
      } else {
        // 无法识别的莫斯密码用 ? 代替
        decodedChars.push('?')
      }
    }

    return {
      success: true,
      result: decodedChars.join('')
    }
  } catch (error) {
    return {
      success: false,
      result: '',
      error: `解码失败: ${error instanceof Error ? error.message : '未知错误'}`
    }
  }
}

/**
 * 获取完整的莫斯密码对照表
 * @returns 对照表对象
 */
export function getMorseCodeTable(): Record<string, string> {
  return { ...MORSE_CODE_MAP }
}

/**
 * 播放莫斯密码音频（使用 Web Audio API）
 * @param morse - 莫斯密码字符串
 * @param speed - 速度（毫秒），默认 100ms
 */
export async function playMorseAudio(morse: string, speed: number = 100): Promise<void> {
  if (!window.AudioContext && !(window as any).webkitAudioContext) {
    throw new Error('浏览器不支持 Web Audio API')
  }

  const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext
  const audioContext = new AudioContextClass()

  const frequency = 600 // 音调频率 (Hz)

  for (const char of morse) {
    if (char === '.') {
      await playTone(audioContext, frequency, speed)
    } else if (char === '-') {
      await playTone(audioContext, frequency, speed * 3)
    } else if (char === ' ' || char === '/') {
      await sleep(speed * 3)
    }
    // 字符间间隔
    await sleep(speed)
  }

  audioContext.close()
}

function playTone(audioContext: AudioContext, frequency: number, duration: number): Promise<void> {
  return new Promise((resolve) => {
    const oscillator = audioContext.createOscillator()
    const gainNode = audioContext.createGain()

    oscillator.connect(gainNode)
    gainNode.connect(audioContext.destination)

    oscillator.frequency.value = frequency
    oscillator.type = 'sine'

    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration / 1000)

    oscillator.start(audioContext.currentTime)
    oscillator.stop(audioContext.currentTime + duration / 1000)

    setTimeout(resolve, duration)
  })
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms))
}
