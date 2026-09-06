<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'file_path',
        'file_name',
    ];

    protected $appends = ['url'];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function getUrlAttribute(): string
    {
        return route('photos.show', $this->id);
    }
}
