<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'mood',
        'journal_date',
    ];

    protected $casts = [
        'journal_date' => 'date:Y-m-d',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(JournalPhoto::class);
    }
}
