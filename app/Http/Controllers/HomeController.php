<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    function index() {
        return redirect('home');
    }

    /**
     * Root URL. Dulu closure di routes/web.php — dipindah ke controller
     * supaya route bisa di-cache (route:cache tidak bisa serialize closure).
     */
    public function landing()
    {
        return redirect('goals');
    }

    /**
     * Fallback 404. Dulu closure di routes/web.php.
     */
    public function notFound()
    {
        // Catatan: sengaja tetap balas HTTP 200 seperti closure aslinya.
        // Ini semestinya 404 — tapi diperbaiki terpisah, bukan di perubahan
        // performa ini.
        return view('errors.404');
    }

    /**
     * Preview template email reminder schedule. Dulu closure di routes/web.php.
     */
    public function testEmail()
    {
        $messages = '<p>This is a test message with <strong>bold</strong> text.</p>';
        $name = 'John Doe';

        return view('email.reminderschedule', compact('messages', 'name'));
    }
    function home() {
        $link = 'home';
        // $data = Employee::orderBy("name")->get();
        // $data = Employee::orderBy('name')->paginate(10);
        // $data = Employee::withTrashed()->orderBy('name')->paginate(10); // mengambil semua data berikut yg di delete
        // $data = Employee::onlyTrashed()->orderBy('name')->paginate(10); // hanya mengambil yg di delete
        return view('pages.home', [
            'link' => $link
        ]);
    }

    function starter() {
        $link = 'starter';

        return view('pages.starter', [
            'link' => $link
        ]);
    }

    public function secondLevel(Request $request, $first, $second)
    {
        if ($first == "assets")
            return redirect('home');


    return view($first .'.'. $second);
    }

    public function thirdLevel(Request $request, $first, $second, $third)
    {
        if ($first == "assets")
            return redirect('home');


    return view($first .'.'. $second .'.'. $third);
    }

    public function root(Request $request, $first)
    {

        $mode = $request->query('mode');
        $demo = $request->query('demo');
     
        if ($first == "assets")
            return redirect('home');

        return view($first);
    }

}
