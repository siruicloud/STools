import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  base: './',
  server: {
    port: 5178,
    strictPort: true,
    open: false
  },
  plugins: [
    vue(),
    {
      name: 'configure-response-headers',
      configureServer: (server) => {
        server.middlewares.use((_req, res, next) => {
          res.setHeader(
            'Content-Security-Policy',
            "default-src * 'unsafe-inline' 'unsafe-eval' data: blob: ztools-icon: file:; img-src * data: blob: ztools-icon: file:;"
          )
          next()
        })
      }
    }
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    }
  }
})
