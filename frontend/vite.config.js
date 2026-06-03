import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
const root = fileURLToPath(new URL('.', import.meta.url))

export default defineConfig({
    root,
    plugins: [vue()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined
                    if (id.includes('vue') || id.includes('vue-router') || id.includes('vuex')) {
                        return 'vendor-vue'
                    }
                    if (id.includes('sweetalert2')) return 'vendor-swal'
                    if (id.includes('leaflet')) return 'vendor-map'
                    if (id.includes('xlsx')) return 'vendor-xlsx'
                    if (id.includes('axios')) return 'vendor-http'
                    return 'vendor'
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
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
            },
            '/storage': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
                secure: false,
            }
        }
    }
})
