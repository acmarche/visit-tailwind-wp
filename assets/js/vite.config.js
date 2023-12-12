import {fileURLToPath, URL} from 'node:url'

import {defineConfig} from 'vite'
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
       /* rollupOptions: {
            input: {
                // appFiltersAdmin: 'src/admin/adminFilters.js',
                main: 'src/main.js',
                appOffersAdmin: 'src/admin/adminOffers.js',
                // chartJf: 'src/chart.js',
                //  mapJf: 'src/map.js',
            },
            output: {
                assetFileNames: 'css/[name]-jf.css',
                entryFileNames: 'js/[name]-jf.js',
            },
        }*/
    }
})
