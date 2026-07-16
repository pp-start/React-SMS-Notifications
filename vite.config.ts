import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig(({ mode }) => {

    const env = loadEnv(mode, process.cwd(), '')

    const appPath = env.VITE_APP_PATH

    return {
        plugins: [react()],
        server: {
            proxy: {

                [appPath]: {
                    target: 'http://localhost',
                    changeOrigin: true,
                    rewrite: path => path,
                },
            },
        },
    }
})