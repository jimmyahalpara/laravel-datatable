<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestPost extends Model
{
    protected $table = 'test_posts';

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}