import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), ['VITE_', 'MIX_']);

    return {
        plugins: [
            laravel({
                input: ['resources/js/app.js', 'resources/sass/app.scss', 'resources/scss/brand.scss'],
                refresh: true,
            }),
            vue(),
        ],
        resolve: {
            alias: { vue: 'vue/dist/vue.esm.js' },
        },
        css: {
            preprocessorOptions: {
                // Bootstrap 4 still uses deprecated Sass syntax internally; report warnings from our own files only.
                scss: { quietDeps: true },
            },
        },
        server: {
            port: Number(env.VITE_PORT || 5173),
        },
        define: {
            // Preserve the existing browser-facing Mix token during the Vite migration.
            'import.meta.env.VITE_LARASOCKET_TOKEN': JSON.stringify(env.VITE_LARASOCKET_TOKEN ?? env.MIX_LARASOCKET_TOKEN ?? ''),
        },
    };
});
