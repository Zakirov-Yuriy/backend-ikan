<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/* Жилой комплекс: вкладка и подпись группы в списках квартир */
class Complex extends Model
{
    protected $fillable = ['slug', 'name', 'sort_order'];

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class)->orderBy('sort_order')->orderBy('id');
    }
}
