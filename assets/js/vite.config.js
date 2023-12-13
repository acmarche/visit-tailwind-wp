import {fileURLToPath, URL} from 'node:url'
import {defineConfig} from 'vite'
import {resolve} from 'path'
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
                categoryFilters: resolve(__dirname, 'index.html'),
                categoryOffers: resolve(__dirname, 'index2.html'),
            }, output: {
                assetFileNames: 'css/pivot.css',
                entryFileNames: 'js/[name].js',
            },
        }
    }
})