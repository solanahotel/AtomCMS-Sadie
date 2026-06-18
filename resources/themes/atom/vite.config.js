import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import tailwindcss from "tailwindcss";
export default defineConfig({
    plugins: [
        laravel({
            input: [
                path.resolve(__dirname, "css/app.scss"),
                path.resolve(__dirname, "js/app.js"),
                path.resolve(__dirname, "js/wallet/wallet-auth.js"),
                "resources/js/global.js",
                "resources/css/global.scss",
            ],
        }),
        {
            name: "blade",
            handleHotUpdate({ file, server }) {
                if (file.endsWith(".blade.php")) {
                    server.ws.send({
                        type: "full-reload",
                        path: "*",
                    });
                }
            },
        },
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "js/app.js"),
        },
    },
    css: {
        postcss: {
            plugins: [
                tailwindcss({
                    config: path.resolve(__dirname, "tailwind.config.js"),
                }),
            ],
        },
    },
});