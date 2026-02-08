<?php

// app/Models/Meeting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'date',
        'location',
        'status',
        'organizer_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    // Relations
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class);
    }
}
