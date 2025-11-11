<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 体験プログラムカテゴリモデル
 * 
 * @property int $id
 * @property string $name 体験プログラム コース種類
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class WorkshopCategory extends Model
{
    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * カテゴリに紐づく体験プログラム
     *
     * @return HasMany<Workshop>
     */
    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class, 'workshop_category_id', 'id');
    }
}
