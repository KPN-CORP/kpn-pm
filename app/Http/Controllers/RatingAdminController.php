<?php

namespace App\Http\Controllers;

use App\Models\MasterRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;


class RatingAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Rating';
        // $ratings = Rating::orderBy('created_at', 'desc')->get();
        $ratings = MasterRating::select('id_rating_group', 'rating_group_name', 'users.name as created_by_name', 'users.id as userId',
            DB::raw("GROUP_CONCAT(CONCAT('<b>',parameter, '</b> - ', desc_idn, ' / <i>',desc_eng, '</i>') SEPARATOR ' <br> ') as detail"))
            ->leftJoin('users', 'master_ratings.created_by', '=', 'users.id')
            ->groupBy('id_rating_group', 'rating_group_name', 'users.name', 'users.id')
            ->orderBy('rating_group_name')
            ->get();

        
        return view('pages.rating-admin.rating', [
            'link' => $link,
            'parentLink' => $parentLink,
            'userId' => $userId,
            'ratings' => $ratings,
            'userId' => $userId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Rating';
        $sublink = 'Create Rating';
        // $ratings = MasterRating::with('createdBy')->get();

        return view('pages.rating-admin.create', [
            'link' => $link,
            'parentLink' => $parentLink,
            'sublink' => $sublink,
            
            'userId' => $userId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $today = date('Y-m-d');
        
        $uuid = Str::uuid();;
        $userId = Auth::id();
        $addrange = isset($request->add_range) ? $request->add_range : 0;
        $id_rating_group = isset($request->id_rating_group) ? $request->id_rating_group : $uuid;
        
        if($request->id_rating==0){
            $model = new MasterRating();
            $model->id_rating_group     = $id_rating_group;
            $model->rating_group_name   = $request->rating_group_name;
            $model->parameter           = $request->parameter;
            $model->value               = $request->value_rating;
            $model->desc_idn            = $request->description_idn;
            $model->desc_eng            = $request->description_eng;
            $model->add_range           = $addrange;
            $model->min_range           = isset($request->min_range) ? $request->min_range : 0;
            $model->max_range           = isset($request->max_range) ? $request->max_range : 0;
            $model->created_by          = $userId;
        }else{
            $model = MasterRating::findOrFail($request->id_rating);
            $model->parameter           = $request->parameter;
            $model->value               = $request->value_rating;
            $model->desc_idn            = $request->description_idn;
            $model->desc_eng            = $request->description_eng;
            $model->add_range           = $addrange;
            $model->min_range           = isset($request->min_range) ? $request->min_range : 0;
            $model->max_range           = isset($request->max_range) ? $request->max_range : 0;
        }
        

        $model->save();
        $id = Crypt::encryptString($id_rating_group);
        Alert::success('Success');
        // return redirect()->intended(route('admratings', absolute: false));
        return redirect()->route('pages.rating-admin.update', ['id' => $id]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $userId = Auth::id();
        $parentLink = 'Settings';
        $link = 'Rating';
        $sublink = 'Create Rating';
        $id_rating_group = Crypt::decryptString($id);
        $ratings = MasterRating::where('id_rating_group', $id_rating_group)->get();
        $rating = MasterRating::where('id_rating_group', $id_rating_group)->firstOrFail();
        // dd($model);

        return view('pages.rating-admin.update', [
            'link' => $link,
            'parentLink' => $parentLink,
            'sublink' => $sublink,
            'ratings' => $ratings,
            'rating' => $rating,
            'id_rating_group' => $id_rating_group,
            'userId' => $userId,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $rating = MasterRating::findOrFail($id);

        // Kembalikan data rating dalam format JSON
        return response()->json([
            'id' => $rating->id,
            'rating_group_name' => $rating->rating_group_name,
            'id_rating_group' => $rating->id_rating_group,
            'parameter' => $rating->parameter,
            'value' => $rating->value,
            'description_idn' => $rating->desc_idn,
            'description_eng' => $rating->desc_eng,
            'add_range' => $rating->add_range,
            'min_range' => $rating->min_range,
            'max_range' => $rating->max_range
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        MasterRating::where('id_rating_group', $id)->delete();
        // $rating->delete();

        // Redirect kembali dengan pesan sukses
        return redirect()->route('admratings')->with('success', 'Rating deleted successfully.');
    }

    public function destroyDetail($id)
    {
        // MasterRating::where('id', $id)->delete();

        //cek id_rating_group yang dihapus, kemudian cek kembali ke MasterRating, apakah masih ada data yang aktif? jika ya maka akan ke redirect ke return redirect()->route('pages.rating-admin.update', ['id_rating_group' => $id_rating_group]); jika tidak ada maka akan ke redirect ke return redirect()->route('admratings')->with('success', 'Rating deleted successfully.');
        
        // return redirect()->route('admratings')->with('success', 'Rating deleted successfully.');

        // Cari data yang akan dihapus
        $rating = MasterRating::findOrFail($id);
        
        $id_rating_group = $rating->id_rating_group;

        $rating->delete();

        $activeRatings = MasterRating::where('id_rating_group', $id_rating_group)
                                    ->whereNull('deleted_at')
                                    ->exists();

        if ($activeRatings) {
            $id = Crypt::encryptString($id_rating_group);
            return redirect()->route('pages.rating-admin.update', ['id' => $id]);
        }else{
            return redirect()->route('admratings')->with('success', 'Rating deleted successfully.');
        }
    }
}
