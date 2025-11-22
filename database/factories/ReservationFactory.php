<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ステータスをランダムに選択（確定60%, 予約受付25%, キャンセル15%）
        $status = $this->faker->randomElement([
            'confirmed', 'confirmed', 'confirmed', 'confirmed', 'confirmed', 'confirmed',  // 60%
            'pending', 'pending', 'pending',  // 25%
            'canceled', 'canceled',  // 15%
        ]);

        // 流入経路をランダムに選択
        $source = $this->faker->randomElement([
            'web', 'web', 'web', 'web', 'web', 'web', 'web',  // 35%
            'phone', 'phone', 'phone', 'phone', 'phone',  // 25%
            'walk-in', 'walk-in', 'walk-in',  // 15%
            'asoview', 'asoview', 'asoview',  // 15%
            'jalan', 'jalan',  // 10%
        ]);

        // 過去1年間のランダムな日時
        $reservationDatetime = $this->faker->dateTimeBetween('-1 year', 'now');

        // キャンセル理由（キャンセルの場合のみ）
        $cancellationReasons = [
            '急な用事のため',
            '体調不良',
            '日程変更希望',
            '他の予定と重複',
            '人数変更のため一旦キャンセル',
            '天候不良のため',
            '交通機関の遅延',
            '家族の都合',
        ];

        // コメント（30%の確率で追加）
        $comments = [
            '初めての体験です。楽しみにしています。',
            '子供連れでも大丈夫でしょうか？',
            '駐車場はありますか？',
            'アレルギーがあるので事前にご相談させてください。',
            '作品は当日持ち帰れますか？',
            '写真撮影は可能ですか？',
            '体験時間の延長は可能でしょうか？',
            '予約変更の可能性があります。',
            null, null, null, null, null, null, null,  // 70%はコメントなし
        ];

        return [
            'program_id' => Workshop::inRandomOrder()->first()->program_id,
            'staff_id' => User::inRandomOrder()->first()->id,
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->unique()->safeEmail(),
            'customer_phone' => '0' . $this->faker->numerify('##-####-####'),
            'reservation_datetime' => $reservationDatetime,
            'num_people' => $this->faker->numberBetween(1, 5),
            'status' => $status,
            'source' => $source,
            'comment' => $this->faker->randomElement($comments),
            'options' => null,
            'cancellation_reason' => $status === 'canceled' ? $this->faker->randomElement($cancellationReasons) : null,
            'created_at' => $reservationDatetime,
            'updated_at' => $reservationDatetime,
        ];
    }
}




