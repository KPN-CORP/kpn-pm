import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/js/app.js",
                "resources/js/head.js",
                "resources/js/layout.js",
                "resources/js/popper.min.js",
                "resources/js/tippy.min.js",
                "resources/js/pages/demo.form-wizard.js",
                "resources/js/soft-ui-dashboard-tailwind.min.js",
                "resources/js/team-goal.js",
                "resources/js/guide.js",
                "resources/js/plugins/perfect-scrollbar.min.js",
                // "resources/css/app.css",
                "resources/scss/app.scss",
                "resources/scss/icons.scss",
                "resources/css/perfect-scrollbar.css",
                "resources/css/soft-ui-dashboard-tailwind.min.css",
                "resources/css/nucleo-svg.css",
                "resources/css/nucleo-icons.css",
            ],
            refresh: true,
        }),
    ],
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                globals: {
                //    jquery: 'window.jQuery',
                   jquery: 'window.$'
                }
            }
        }
    },
    resolve: {
        alias: {
            $: "jQuery",
            Swal: path.resolve(__dirname, "node_modules/sweetalert2"),
            select2: path.resolve(__dirname, "node_modules/select2"),
        },
    },
    optimizeDeps: {
        include: ['select2'],
    },
    css: {
        preprocessorOptions: {
            css: {
                importLoaders: 1,
                modules: true,
            },
        },
    },
});
