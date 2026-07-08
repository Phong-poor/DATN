import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
const root = fileURLToPath(new URL('.', import.meta.url))
const backendUrl = process.env.VITE_BACKEND_URL || 'http://localhost/DATN/backend/public'

export default defineConfig({
    root,
    plugins: [vue()],
    build: {
        target: 'es2020',
        cssCodeSplit: true,
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return undefined
                    if (id.includes('vue') || id.includes('vue-router') || id.includes('vuex')) {
                        return 'vendor-vue'
                    }
                    if (id.includes('laravel-echo') || id.includes('pusher-js')) return 'vendor-realtime'
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
})
