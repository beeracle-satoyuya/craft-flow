<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Reservation;
use App\Models\Workshop;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * ダブルブッキング防止バリデーションルール
 * 
 * 同じ時間帯・同じ体験プログラムで定員を超える予約を防止
 */
class NoDoubleBooking implements ValidationRule
{
    /**
     * コンストラクタ
     *
     * @param int $programId 体験プログラムID
     * @param string $reservationDatetime 予約日時
     * @param int $numPeople 予約人数
     * @param int|null $excludeReservationId 除外する予約ID（編集時）
     */
    public function __construct(
        private int $programId,
        private string $reservationDatetime,
        private int $numPeople,
        private ?int $excludeReservationId = null
    ) {}

    /**
     * バリデーションを実行
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 体験プログラムを取得
        $workshop = Workshop::find($this->programId);
        
        if (!$workshop) {
            $fail('選択された体験プログラムが見つかりません。');
            return;
        }

        // 予約日時をパース
        try {
            $startTime = Carbon::parse($this->reservationDatetime);
        } catch (\Exception $e) {
            $fail('予約日時の形式が正しくありません。');
            return;
        }

        // 終了時刻を計算
        $endTime = $startTime->copy()->addMinutes($workshop->duration_minutes);

        // 重複する予約を検索
        $overlappingReservations = Reservation::where('program_id', $this->programId)
            ->where('status', '!=', 'canceled') // キャンセル済みは除外
            ->when($this->excludeReservationId, function ($query) {
                $query->where('reservation_id', '!=', $this->excludeReservationId);
            })
            ->get()
            ->filter(function ($reservation) use ($startTime, $endTime, $workshop) {
                // 既存予約の開始・終了時刻を計算
                $existingStart = $reservation->reservation_datetime;
                $existingEnd = $existingStart->copy()->addMinutes($workshop->duration_minutes);
                
                // 時間帯の重複判定
                // 新規予約: [startTime, endTime]
                // 既存予約: [existingStart, existingEnd]
                // 重複条件: startTime < existingEnd かつ endTime > existingStart
                return $startTime->lt($existingEnd) && $endTime->gt($existingStart);
            });

        // 重複する予約の人数合計を計算
        $totalPeople = $overlappingReservations->sum('num_people');
        
        // 新しい予約人数を加算
        $totalWithNew = $totalPeople + $this->numPeople;

        // 定員チェック
        if ($totalWithNew > $workshop->max_capacity) {
            $available = $workshop->max_capacity - $totalPeople;
            $fail(
                "この時間帯は既に予約が入っており、定員を超えてしまいます。\n" .
                "現在の予約人数: {$totalPeople}名 / 定員: {$workshop->max_capacity}名\n" .
                "追加可能人数: " . max(0, $available) . "名まで"
            );
        }
    }
}
