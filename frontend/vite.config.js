import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  server: {
    // No Docker Desktop (Windows/macOS) o bind mount não repassa eventos de
    // arquivo para o container; o docker-compose.dev.yml liga o polling.
    watch: process.env.VITE_USE_POLLING === 'true' ? { usePolling: true, interval: 300 } : undefined,
  },
})
