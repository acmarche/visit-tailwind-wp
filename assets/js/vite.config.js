import {defineConfig} from 'vite'
import {fileURLToPath, URL} from "node:url";
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./src", import.meta.url)),
            images: fileURLToPath(new URL("./src/public/images", import.meta.url))
        }
    },
    build: {
        watch: {
            // https://rollupjs.org/guide/en/#watch-options
        },
        rollupOptions: {
            input: {
                appFiltersAdmin: 'src/admin/adminFilters.js',
                appOffersAdmin: 'src/admin/adminOffers.js',
                appOl: 'src/ol.js',
                chartJf: 'src/chart.js',
                mapJf: 'src/map.js',
            },
            output: {
                assetFileNames: 'css/[name]-jf.css',
                entryFileNames: 'js/[name]-jf.js',
            },
        }
    }
});
