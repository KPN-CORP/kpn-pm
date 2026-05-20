<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementImportTransaction extends Model
{
    use HasFactory;

    protected $table = 'kpi_achievement_import_transactions'; // Nama tabel

    protected $fillable = [
        'success',
        'error',
        'detail_error',
        'file_uploads',
        'submit_by',
    ];

    /**
     * Relasi ke tabel `users` (jika ada).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'submit_by');
    }
}
