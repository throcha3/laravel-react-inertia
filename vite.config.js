import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.jsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        dedupe: ['@inertiajs/react']
    },
    ssr: {
        noExternal: [
            "@mui/material",
            "@mui/utils",
            "@mui/base",
            "@mui/icons-material",
            "@mui/system",
            "@mui/styled-engine",
        ],
    },
});
