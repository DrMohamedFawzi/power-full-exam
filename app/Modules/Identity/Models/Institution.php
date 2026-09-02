<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Support\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_email',
        'logo_path',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Teacher->value);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', Role::Student->value);
    }
}
