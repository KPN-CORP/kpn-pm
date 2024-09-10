import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: [
            
                "resources/css/app.css",
                "resources/js/app.js",
                // "resources/scss/app.scss",
                // "resources/scss/icons.scss",

                // "node_modules/daterangepicker/daterangepicker.css",
                // "node_modules/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css",
                // "node_modules/fullcalendar/main.min.css",
                // "node_modules/quill/dist/quill.core.css",
                // "node_modules/quill/dist/quill.snow.css",
                // "node_modules/quill/dist/quill.bubble.css",
                // "node_modules/jquery-toast-plugin/dist/jquery.toast.min.css",
                // "node_modules/select2/dist/css/select2.min.css",
                // "node_modules/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.css",
                // "node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css",
                // "node_modules/bootstrap-timepicker/css/bootstrap-timepicker.min.css",
                // "node_modules/flatpickr/dist/flatpickr.min.css",
                // "node_modules/sweetalert2/src/sweetalert2.scss",

                // "resources/js/app.js",
                // "resources/js/head.js",
                // "resources/js/layout.js",
                // "resources/js/pages/component.chat.js",
                // "resources/js/pages/demo.apex-mixed.js",

                // "node_modules/jquery/dist/jquery.js",
                // "node_modules/daterangepicker/moment.min.js",
                // "node_modules/dragula/dist/dragula.min.js",
                // "node_modules/jquery-toast-plugin/dist/jquery.toast.min.js",
                // "node_modules/jquery.rateit/scripts/jquery.rateit.min.js",
                // "node_modules/select2/dist/js/select2.min.js",
                // "node_modules/jquery-toast-plugin/src/jquery.toast.js",
                // "node_modules/sweetalert2/dist/sweetalert2.js",

                // "typeahead.js/dist/typeahead.bundle.js",
                // "typeahead.js/dist/typeahead.bundle.min.js",
                // "jquery.rateit/scripts/jquery.rateit.js",

                // Dashboard js
                // "resources/js/pages/demo.dashboard.js",
                // "resources/js/pages/demo.dashboard-analytics.js",

                // Apps js
                // "resources/js/pages/demo.calendar.js",
                // "resources/js/pages/component.dragula.js",
                // "resources/js/pages/component.dragula.js",

                // Email js
                // "resources/js/pages/demo.inbox.js",

                // Pages
                // "resources/js/pages/demo.profile.js",

                // Task js
                // "resources/js/pages/demo.tasks.js",
                // "resources/js/pages/component.fileupload.js",

                // Extended ui js
                // "resources/js/pages/component.rating.js",
                // "resources/js/pages/component.dragula.js",
                // "resources/js/pages/component.range-slider.js",
                // "resources/js/pages/component.rating.js",

                // Widgets js
                // "resources/js/pages/demo.widgets.js",
                // "resources/js/pages/component.todo.js",

                // Icons js
                // "resources/js/pages/demo.bootstrapicons.js",
                // "resources/js/pages/demo.remixicons.js",

                // Apex Chart
                // "resources/js/pages/demo.apex-area.js",
                // "resources/js/pages/demo.apex-bar.js",
                // "resources/js/pages/demo.apex-boxplot.js",
                // "resources/js/pages/demo.apex-bubble.js",
                // "resources/js/pages/demo.apex-candlestick.js",
                // "resources/js/pages/demo.apex-column.js",
                // "resources/js/pages/demo.apex-heatmap.js",
                // "resources/js/pages/demo.apex-line.js",
                // "resources/js/pages/demo.apex-heatmap.js",
                // "resources/js/pages/demo.apex-heatmap.js",
                // "resources/js/pages/demo.apex-polar-area.js",
                // "resources/js/pages/demo.apex-radar.js",
                // "resources/js/pages/demo.apex-scatter.js",
                // "resources/js/pages/demo.apex-scatter.js",
                // "resources/js/pages/demo.apex-scatter.js",
                // "resources/js/pages/demo.apex-treemap.js",

                // Charts Js
                // "resources/js/pages/demo.chartjs-area.js",
                // "resources/js/pages/demo.chartjs-bar.js",
                // "resources/js/pages/demo.chartjs-line.js",
                // "resources/js/pages/demo.chartjs-other.js",

                // Forms Js
                // "resources/js/pages/demo.form-advanced.js",

                // 'resources/js/pages/demo.timepicker.js',
                // "resources/js/pages/demo.quilljs.js",
                // "resources/js/pages/component.fileupload.js",
                "resources/js/pages/demo.form-wizard.js",

                // Tables js
                // "resources/js/pages/demo.datatable-init.js",

                // Maps Js
                // "resources/js/pages/demo.google-maps.js",
                // "resources/js/pages/demo.vector-maps.js",
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
            ],
        }),
    ],
    build: {
        sourcemap: false,
        rollupOptions: {
            output: {
                globals: {
                   jquery: 'window.jQuery',
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
          // Add any CSS preprocessor options here if needed
        }
    },
});
