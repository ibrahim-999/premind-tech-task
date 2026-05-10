import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'node:path'
import fs from 'node:fs'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  const certsDir = path.resolve(process.cwd(), 'certs')
  const certPath = path.join(certsDir, 'premind.crt')
  const keyPath = path.join(certsDir, 'premind.key')
  const certsExist = fs.existsSync(certPath) && fs.existsSync(keyPath)
  const httpsEnabled = env.VITE_HTTPS !== 'false' && certsExist

  return {
    plugins: [react()],
    resolve: {
      alias: {
        '@': path.resolve(process.cwd(), 'src'),
      },
    },
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      https: httpsEnabled
        ? {
            cert: fs.readFileSync(certPath),
            key: fs.readFileSync(keyPath),
          }
        : undefined,
      hmr: {
        host: env.VITE_HMR_HOST || 'localhost',
      },
    },
  }
})
