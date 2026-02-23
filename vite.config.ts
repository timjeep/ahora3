import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
//import i18n from 'laravel-vue-i18n/vite';
import { defineConfig } from 'vite';

export default defineConfig(({ command }) => {
  const isDev = command === 'serve';

  return {
    plugins: [
      laravel({
        input: ['resources/js/app.ts'],
        ssr: 'resources/js/ssr.ts',
        refresh: true,
      }),
      tailwindcss(),

      // If your Vite dev server is in a container that might not have `php`,
      // keep this disabled in dev. (Build in laravel container will still run it.)
      !isDev && wayfinder({ formVariants: true }),

      //i18n(),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        },
      }),
    ].filter(Boolean),

    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,

      // IMPORTANT: allow Laravel origin to load modules
      cors: {
        origin: 'http://localhost:86',
        credentials: true,
      },

      // 🔑 this is the missing piece: tells Vite the *public* URL
      origin: 'http://localhost:5176',

      // HMR should also point at the published port
      hmr: {
        host: 'localhost',
        clientPort: 5176,
        // OPTIONAL but often helps when behind mappings
        protocol: 'ws',
      },

      watch: {
        usePolling: true,
        interval: 250,
      },
    },
  };
});
