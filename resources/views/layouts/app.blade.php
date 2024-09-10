<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <link rel="icon" type="image/ico" href="{{ asset('storage/img/favicon.ico') }}">
    <title>@yield('title')</title>
    @include('link')
  </head>

  <body id="page-top" class="sidebar-toggled">
    <!-- Page Wrapper -->
    <div id="wrapper">

@include('layouts.navbar.sidebar')
<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        @include('layouts.navbar.nav')
        {{ $content }}
    </div>
</div>

</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
  <i class="fas fa-angle-up"></i>
</a>

    @include('script')
    @include('sweetalert::alert')
</body>
</html>