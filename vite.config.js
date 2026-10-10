import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Bó tối ưu D1: asset tĩnh (Bootstrap + Font Awesome + brand CSS)
                // được đóng gói từ node_modules thay vì tải từ CDN.
                'resources/css/vendor.css',
                'resources/js/vendor.js',
                'resources/js/marked.js',
                'resources/css/fa.css',
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
