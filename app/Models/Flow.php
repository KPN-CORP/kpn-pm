<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flow extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_transaction',
        'flow_name',
        'description',
        'assignments',
        'initiator',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assignments' => 'array',
        'initiator'   => 'array',
    ];

    /**
     * Flow aktif untuk satu module transaction.
     * Satu module bisa punya beberapa flow (mis. Propose 360_2025 dan
     * Proposed360_2026); yang dipakai adalah yang terbaru (id terbesar).
     * Dulu ->first() tanpa urutan selalu mengambil flow lama, sehingga
     * perubahan assignment di flow baru tidak berpengaruh.
     */
    public static function forModule(string $moduleTransaction): ?self
    {
        return static::where('module_transaction', $moduleTransaction)
            ->orderByDesc('id')
            ->first();
    }
}
