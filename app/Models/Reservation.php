<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 予約情報モデル
 * 
 * @property int $reservation_id
 * @property int $program_id 体験プログラムID
 * @property int $staff_id 登録スタッフID
 * @property string $customer_name 顧客氏名
 * @property string $customer_email 顧客メールアドレス
 * @property string $customer_phone 顧客電話番号
 * @property \Illuminate\Support\Carbon $reservation_datetime 予約日時
 * @property int $num_people 人数
 * @property string $status 予約状況
 * @property string $source 予約経路
 * @property string|null $comment コメント
 * @property array|null $options オプション
 * @property string|null $cancellation_reason キャンセル理由
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Reservation extends Model
{
    use SoftDeletes;

    /**
     * 主キー
     *
     * @var string
     */
    protected $primaryKey = 'reservation_id';

    /**
     * 複数代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'staff_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'reservation_datetime',
        'num_people',
        'status',
        'source',
        'comment',
        'options',
        'cancellation_reason',
    ];

    /**
     * キャスト定義
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reservation_datetime' => 'datetime',
            'num_people' => 'integer',
            'options' => 'array',
        ];
    }

    /**
     * 予約が属する体験プログラム
     *
     * @return BelongsTo<Workshop, Reservation>
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class, 'program_id', 'program_id');
    }

    /**
     * 予約を登録したスタッフ
     *
     * @return BelongsTo<User, Reservation>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }

    /**
     * 予約を登録したスタッフ（後方互換性のため）
     *
     * @return BelongsTo<User, Reservation>
     */
    public function user(): BelongsTo
    {
        return $this->staff();
    }
}
