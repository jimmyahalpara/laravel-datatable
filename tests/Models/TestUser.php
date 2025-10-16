<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestUser extends Model
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'email',
        'age',
        'city',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}