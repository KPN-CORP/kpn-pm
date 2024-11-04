<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/ico" href="{{ asset('favicon.ico') }}?v={{ config('app.version') }}" />
    <title>Performance Management</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Nucleo Icons -->
    @vite('resources/css/nucleo-icons.css')
    @vite('resources/css/nucleo-svg.css')
    <!-- Main Styling -->
    @vite('resources/css/soft-ui-dashboard-tailwind.min.css')

    <!-- Nepcha Analytics (nepcha.com) -->
    <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
    <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
  </head>

  <body class="m-0 font-sans antialiased font-normal bg-white text-start text-base leading-default text-slate-500">
    <div class="container sticky top-0 z-sticky">
      <div class="flex flex-wrap -mx-3">
        <div class="w-full max-w-full px-3 flex-0">
          <!-- Navbar -->
          <nav class="absolute top-0 left-0 right-0 z-30 flex flex-wrap items-center px-4 py-2 mx-6 my-4 shadow-soft-2xl rounded-blur bg-white/80 backdrop-blur-2xl backdrop-saturate-200 lg:flex-nowrap lg:justify-start">
            <div class="flex items-center lg:justify-between justify-center w-full p-0 pl-6 mx-auto flex-wrap-inherit">
                    <span class="text-sm overflow-hidden lg-max:max-h-0 basis-full flex lg:basis-auto whitespace-nowrap font-bold text-slate-700" > Performance Management </span>
                    <div class="py-2.375 mr-4 items-center flex">
                        <img src="{{ asset('img/logos/logokpn.png') }}?v={{ config('app.version') }}" alt="" class="h-6">
                    </div>
            </div>
            
          </nav>
        </div>
      </div>
    </div>
    <main class="mt-0 transition-all duration-200 ease-soft-in-out">
      <section>
        <div class="relative flex items-center p-0 overflow-hidden bg-center bg-cover min-h-75-screen">
          <div class="container z-10">
            <div class="flex flex-wrap mt-0 -mx-3">
              <div class="flex flex-col w-full max-w-full px-3 mx-auto md:flex-0 shrink-0 md:w-6/12 lg:w-5/12 xl:w-4/12">
                <div class="relative flex flex-col min-w-0 mt-32 break-words bg-transparent border-0 shadow-none rounded-2xl bg-clip-border">
                  <div class="p-6 pb-0 mb-0 bg-transparent border-b-0 rounded-t-2xl">
                    <h3 class="relative z-10 font-bold text-transparent bg-gradient-to-tl from-blue-600 to-cyan-400 bg-clip-text">Reset Password</h3>
                  </div>
                  <div class="flex-auto p-6">
                    <x-auth-session-status class="mb-4" :status="session('status')" />
                    <form method="POST" action="{{ route('password.store') }}">
                    @csrf
                      <x-input-label class="mb-2 ml-1 font-bold text-xs text-slate-700" for="email" :value="__('Email')" />
                      <div class="mb-4">
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <x-text-input id="email" type="email" class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow" placeholder="Email" aria-label="Email" aria-describedby="email-addon" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                      </div>
                      <x-input-label class="mb-2 ml-1 font-bold text-xs text-slate-700" for="password" :value="__('New Password')" />
                      <div class="mb-4">
                        <x-text-input id="password" type="password" class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow" name="password" :value="old('password')" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                      </div>
                      <x-input-label class="mb-2 ml-1 font-bold text-xs text-slate-700" for="password_confirmation" :value="__('Confirm Password')" />
                      <div class="mb-4">
                        <x-text-input id="password_confirmation" type="password" class="focus:shadow-soft-primary-outline text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 transition-all focus:border-fuchsia-300 focus:outline-none focus:transition-shadow" name="password_confirmation" :value="old('password')" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                      </div>
                      <div class="text-center">
                        <button type="submit" class="inline-block w-full px-6 py-3 mt-6 mb-0 font-bold text-center text-white uppercase align-middle transition-all bg-transparent border-0 rounded-lg cursor-pointer shadow-soft-md bg-x-25 bg-150 leading-pro text-xs ease-soft-in tracking-tight-soft bg-gradient-to-tl from-blue-600 to-cyan-400 hover:scale-102 hover:shadow-soft-xs active:opacity-85">{{ __('Reset Password') }}</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="w-full max-w-full px-3 lg:flex-0 shrink-0 md:w-6/12">
                <div class="absolute top-0 hidden w-3/5 h-full -mr-32 overflow-hidden -skew-x-10 -right-40 rounded-bl-xl md:block">
                  <div class="absolute inset-x-0 top-0 z-0 h-full -ml-16 bg-cover skew-x-10" style="background-image: url({{ asset('assets/img/curved-images/curved14.jpg') }}?v={{ config('app.version') }})"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
    <footer class="py-12">
      <div class="container">
        <div class="flex flex-wrap -mx-3">
          <div class="w-8/12 max-w-full px-3 mx-auto mt-1 text-center flex-0">
            <p class="mb-0 text-slate-400">
              Copyright ©
              <script>
                document.write(new Date().getFullYear());
              </script>
              KPN Performance Management by HCIS.
            </p>
          </div>
        </div>
      </div>
    </footer>
  </body>
  <!-- plugin for scrollbar  -->
  @vite('resources/js/plugins/perfect-scrollbar.min.js')
  @vite('resources/js/soft-ui-dashboard-tailwind.min.js')
</html>
