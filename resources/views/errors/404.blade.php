<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet" />
    <link href="{{ asset('css/sb-admin-2.css') }}?v={{ config('app.version') }}" rel="stylesheet" />
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-center vh-100">
            <div class="form-group text-center">
                <div class="mb-3">
                    <img @style('width: 50%') src="{{ asset('img/page-not-found.svg') }}?v={{ config('app.version') }}" alt="Page Not Found">
                </div>
                <h1 class="h3 mb-5">Page Not Found!</h1>
                <a href="/" class="btn btn-primary px-4" >Return</a>
            </div>
        </div>
    </div>
</body>
</html>
