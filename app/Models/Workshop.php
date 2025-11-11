<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 体験プログラムモデル
 * 
 * @property int $program_id
 * @property int $workshop_category_id カテゴリID
 * @property string $program_name 体験プログラム名
 * @property string|null $description 説明
 * @property int $duration_minutes 所用時間（分）
 * @property int $max_capacity 最大受入人数
 * @property int $price_per_person 料金（1人）
 * @property bool $is_active アクティブフラグ
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Workshop extends Model
{
    /**
     * 主キー
     *
     * @var string
     */
    protected $primaryKey = 'program_id';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workshop_category_id',
        'program_name',
        'description',
        'duration_minutes',
        'max_capacity',
        'price_per_person',
        'is_active',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_minutes' => 'integer',
            'max_capacity' => 'integer',
            'price_per_person' => 'integer',
        ];
    }

    /**
     * プログラムが属するカテゴリ
     *
     * @return BelongsTo<WorkshopCategory, Workshop>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkshopCategory::class, 'workshop_category_id');
    }

    /**
     * プログラムに紐づく予約
     *
     * @return HasMany<Reservation>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'program_id', 'program_id');
    }
}
