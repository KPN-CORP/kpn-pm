<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts_.shared/title-meta', ['title' => $page_title])
    @yield('css')
    @include('layouts_.shared/head-css', ['mode' => $mode ?? '', 'demo' => $demo ?? ''])
    @include('layouts_.shared/header-script')
    @vite(['resources/js/head.js'])
</head>

<body>
    <div class="wrapper">

        @include('loader')
        @include('layouts_.shared/topbar')

        @include('layouts_.shared/left-sidebar')

        <!-- ============================================================== -->
        <!-- Start Page Content here -->
        <!-- ============================================================== -->

        <div class="content-page">
            <div id="content" class="content">
                <!-- Start Content-->
                @yield('content')
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>

    {{-- @include('layouts_.shared/right-sidebar') --}}
    @vite(['resources/js/app.js', 'resources/js/layout.js'])
    @include('layouts_.shared/footer-script')
    @yield('script')
    @stack('scripts')

</body>

</html>
