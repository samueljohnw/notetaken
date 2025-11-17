<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public static function generate(string $name): self
    {
        return self::create([
            'name' => $name,
            'token' => hash('sha256', Str::random(40)),
        ]);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
