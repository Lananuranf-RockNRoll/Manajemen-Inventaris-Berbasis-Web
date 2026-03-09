import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    vueJsx(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  build: {
    // Generate source maps only in development
    sourcemap: false,
    // Improve chunk splitting for better caching
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-vue':    ['vue', 'vue-router', 'pinia'],
          'vendor-ui':     ['lucide-vue-next'],
          'vendor-charts': ['chart.js', 'vue-chartjs'],
          'vendor-http':   ['axios'],
        },
      },
    },
  },
  server: {
    port: 5173,
    proxy: {
      // Proxy API calls to Laravel during local dev (alternative to CORS)
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
