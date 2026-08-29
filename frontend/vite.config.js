import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  server: {
    port: 5174,
    strictPort: true,
  },
  plugins: [
    vue(),
    tailwindcss(),
  ],
})