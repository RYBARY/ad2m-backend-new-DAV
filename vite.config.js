import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

const HMR_HOST = 'super-duper-succotash-4jrvjx6jrwgxcj7x9-5173.app.github.dev'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue(),
  ],

  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,

    // IMPORTANT: empêcher "http://0.0.0.0:5173" (Mixed Content)
    origin: `https://${HMR_HOST}`,

    cors: true,
    headers: { 'Access-Control-Allow-Origin': '*' },

    // HMR via WebSocket sécurisé (Codespaces)
    hmr: {
      protocol: 'wss',
      host: HMR_HOST,
      clientPort: 443,
    },
  },
})
