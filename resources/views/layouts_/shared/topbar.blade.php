<!-- ========== Topbar Start ========== -->
<div class="navbar-custom shadow-none p-0" style="z-index: 999;">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-lg-2 gap-1">

            <!-- Topbar Brand Logo -->
            <div class="logo-topbar d-none">
                <!-- Logo light -->
                <a href="{{ Url('/') }}" class="logo-light">
                    <span class="logo-lg">
                        <img src="{{ asset('storage/img/logo.png') }}" alt="logo">
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('storage/img/logo-sm.png') }}" alt="small logo">
                    </span>
                </a>

                <!-- Logo Dark -->
                <a href="{{ Url('/') }}" class="logo-dark">
                    <span class="logo-lg">
                        <img src="{{ asset('storage/img/logo-dark.png') }}" alt="dark logo">
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('storage/img/logo-sm.png') }}" alt="small logo">
                    </span>
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="ri-menu-2-fill"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>
            
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
            <li class="dropdown d-none">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="ri-search-line fs-22"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-animated dropdown-lg p-0">
                    <form class="p-3">
                        <input type="search" class="form-control" placeholder="Search ..." aria-label="Recipient's username">
                    </form>
                </div>
            </li>

            <li class="d-none">
                <div class="nav-link" id="light-dark-mode" data-bs-toggle="tooltip" data-bs-placement="left" title="Theme Mode">
                    <i class="ri-moon-line fs-22"></i>
                </div>
            </li>


            <li class="d-none">
                <a class="nav-link" href="" data-toggle="fullscreen">
                    <i class="ri-fullscreen-line fs-22"></i>
                </a>
            </li>
            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <img src="{{ asset('storage/img/flags/' . (session('locale') ? session('locale') : 'en') . '.jpg')}}" alt="user-image" class="me-0 me-sm-1" height="12">
                    <span class="align-middle d-none d-lg-inline-block">{{ session('locale') == 'id' ? 'Bahasa' : 'English' }}</span> <i class="ri-arrow-down-s-line d-none d-sm-inline-block align-middle"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated" style="">
                    <a href="{{ route('language.switch', ['locale' => 'en']) }}" class="dropdown-item"><img src="{{ asset('storage/img/flags/en.jpg') }}" alt="user-image" class="me-0 me-sm-1" height="12">
                        <span class="align-middle d-none d-lg-inline-block">English</span>
                    </a>
                    <a href="{{ route('language.switch', ['locale' => 'id']) }}" class="dropdown-item">
                        <img src="{{ asset('storage/img/flags/id.jpg') }}" alt="user-image" class="me-1" height="12"> <span class="align-middle">Bahasa</span>
                    </a>
                </div>
            </li>

            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="account-user-avatar">
                        <img src="{{ asset('storage/img/profiles/user.png') }}" alt="user-image" width="32" class="rounded-circle">
                    </span>
                    <span class="d-flex flex-column gap-1">
                        <h5 class="my-0">
                            {{ auth()->user()->name }}
                        </h5>
                        <h6 class="my-0 fw-normal">{{ auth()->user()->employee_id }}</h6>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown mt-1">

                    <!-- item-->
                    <a href="{{ route('second', ['auth', 'lock-screen']) }}" class="dropdown-item d-none">
                        <i class="ri-key-2-fill fs-18 align-middle me-1"></i>
                        <span>Change Password</span>
                    </a>

                    <!-- item-->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a onclick="event.preventDefault(); this.closest('form').submit();" class="dropdown-item">
                            <i class="ri-logout-box-line fs-18 align-middle me-1"></i>
                            <span>Logout</span>
                        </a>
                    </form>
                </div>
            </li>
        </ul>
    </div>
    <div class="container-fluid" style="background-color: #f2f2f7;">
        <div class="page-title-box mx-2">
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item">{{ $parentLink }}</li>
                    <li class="breadcrumb-item active">{{ $link }}</li>
                </ol>
            </div>
            <h4 class="page-title">{{ $link }}</h4>
        </div>
    </div>
</div>
                
<!-- ========== Topbar End ========== -->
