import {fileURLToPath, URL} from 'node:url'
import {defineConfig} from 'vite'
import resolve from 'node:path'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [
        vue(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
            images: fileURLToPath(new URL("./src/public/images", import.meta.url))
        }
    },
    build: {
        watch: {
            // https://rollupjs.org/configuration-options/#watch
        },
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'index.html'),
                nested: resolve(__dirname, 'index2.html'),
            }, output: {
                assetFileNames: 'css/[name]-jf.css',
                entryFileNames: 'js/[name]-jf.js',
            },
        }
    }
})