import { fileURLToPath, URL } from 'node:url'
import { readFileSync, writeFileSync, existsSync, mkdirSync, copyFileSync, createReadStream } from 'node:fs'
import { resolve } from 'node:path'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import Inspector from 'vite-plugin-vue-inspector'

// https://vite.dev/config/
const root = fileURLToPath(new URL('.', import.meta.url))

export default defineConfig(({ mode, command }) => {
  const env = loadEnv(mode, root, '')
  const backendUrl = env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'
  const publicUrl = String(env.VITE_PUBLIC_URL || 'http://localhost:5173').replace(/\/+$/, '')

  const hostingMetadataPlugin = {
    name: 'hosting-metadata',
    closeBundle() {
      for (const file of ['robots.txt', 'sitemap.xml']) {
        const outputPath = resolve(root, 'dist', file)
        if (existsSync(outputPath)) {
          const content = readFileSync(outputPath, 'utf8').replaceAll('__SITE_URL__', publicUrl)
          writeFileSync(outputPath, content)
        }
      }
    },
  }
  const ocrLanguageSources = ['eng', 'vie'].map(language => ({
    language,
    source: resolve(root, 'node_modules', '@tesseract.js-data', language, '4.0.0', `${language}.traineddata.gz`),
  }))
  const localOcrLanguagePlugin = {
    name: 'local-ocr-language-data',
    configureServer(server) {
      ocrLanguageSources.forEach(({ language, source }) => {
        server.middlewares.use(`/tessdata/${language}.traineddata.gz`, (_request, response) => {
          response.setHeader('Content-Type', 'application/gzip')
          createReadStream(source).pipe(response)
        })
      })
    },
    closeBundle() {
      const targetDirectory = resolve(root, 'dist', 'tessdata')
      mkdirSync(targetDirectory, { recursive: true })
      ocrLanguageSources.forEach(({ language, source }) => {
        copyFileSync(source, resolve(targetDirectory, `${language}.traineddata.gz`))
      })
    },
  }

  return {
    root,
    plugins: [
      vue(),
      hostingMetadataPlugin,
      localOcrLanguagePlugin,
      command === 'serve'
        ? Inspector({
            launchEditor: 'code',
            toggleButtonVisibility: 'never',
            disableInspectorOnEditorOpen: true,
          })
        : null,
    ].filter(Boolean),
    build: {
        target: 'es2020',
        cssCodeSplit: true,
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined
                    if (id.includes('@vladmandic/face-api')) return 'vendor-face-api'
                    if (id.includes('lucide-vue-next')) return 'vendor-icons'
                    if (id.includes('vue') || id.includes('vue-router') || id.includes('vuex')) {
                        return 'vendor-vue'
                    }
                    if (id.includes('laravel-echo') || id.includes('pusher-js')) return 'vendor-realtime'
                    if (id.includes('sweetalert2')) return 'vendor-swal'
                    if (id.includes('leaflet')) return 'vendor-map'
                    if (id.includes('xlsx')) return 'vendor-xlsx'
                    if (id.includes('axios')) return 'vendor-http'
                    // Để Vite giữ thư viện chỉ dùng ở route động trong chính chunk
                    // của route đó, tránh bắt mọi trang tải một vendor khổng lồ.
                    return undefined
                }
            }
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src',
                import.meta.url))
        }
    },
    server: {
        proxy: {
            '/api': {
                target: backendUrl,
                changeOrigin: true,
                secure: false,
            },
            '/storage': {
                target: backendUrl,
                changeOrigin: true,
                secure: false,
            }
        }
    }
  }
})
