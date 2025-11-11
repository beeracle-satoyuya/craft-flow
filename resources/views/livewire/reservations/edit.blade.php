<?php

use function Livewire\Volt\{state, rules, mount};
use App\Models\Workshop;
use App\Models\Reservation;
use App\Rules\NoDoubleBooking;

// フォーム状態
state([
    'reservation',
    'program_id' => '',
    'customer_name' => '',
    'customer_email' => '',
    'customer_phone' => '',
    'reservation_date' => '',
    'reservation_time' => '',
    'num_people' => 1,
    'status' => 'pending',
    'source' => 'web',
    'comment' => '',
    'options' => '',
    'cancellation_reason' => '',
    'workshops' => fn() => Workshop::with('category')->where('is_active', true)->get(),
]);

// 初期化
mount(function (Reservation $reservation) {
    $this->reservation = $reservation;
    
    // 認可チェック
    $this->authorize('update', $reservation);
    
    // フォームデータをセット
    $this->program_id = $reservation->program_id;
    $this->customer_name = $reservation->customer_name;
    $this->customer_email = $reservation->customer_email;
    $this->customer_phone = $reservation->customer_phone;
    $this->reservation_date = $reservation->reservation_datetime->format('Y-m-d');
    $this->reservation_time = $reservation->reservation_datetime->format('H:i');
    $this->num_people = $reservation->num_people;
    $this->status = $reservation->status;
    $this->source = $reservation->source;
    $this->comment = $reservation->comment ?? '';
    $this->options = $reservation->options ? json_encode($reservation->options, JSON_UNESCAPED_UNICODE) : '';
    $this->cancellation_reason = $reservation->cancellation_reason ?? '';
});

// 予約を更新
$update = function () {
    // 日時を結合
    $reservationDatetime = $this->reservation_date . ' ' . $this->reservation_time;
    
    // バリデーションルール（動的にダブルブッキングチェックを追加、自分自身は除外）
    $this->validate([
        'program_id' => 'required|exists:workshops,program_id',
        'customer_name' => 'required|string|max:255',
        'customer_email' => 'required|email|max:255',
        'customer_phone' => 'required|string|max:20',
        'reservation_date' => 'required|date',
        'reservation_time' => 'required',
        'num_people' => [
            'required',
            'integer',
            'min:1',
            new NoDoubleBooking(
                (int) $this->program_id,
                $reservationDatetime,
                (int) $this->num_people,
                $this->reservation->reservation_id // 自分自身を除外
            ),
        ],
        'status' => 'required|in:pending,confirmed,canceled',
        'source' => 'required|in:web,phone,walk-in,asoview,jalan',
        'comment' => 'nullable|string',
        'options' => 'nullable|string',
        'cancellation_reason' => 'nullable|string',
    ]);

    $this->reservation->update([
        'program_id' => $this->program_id,
        'customer_name' => $this->customer_name,
        'customer_email' => $this->customer_email,
        'customer_phone' => $this->customer_phone,
        'reservation_datetime' => $reservationDatetime,
        'num_people' => $this->num_people,
        'status' => $this->status,
        'source' => $this->source,
        'comment' => $this->comment,
        'options' => $this->options ? json_decode($this->options, true) : null,
        'cancellation_reason' => $this->cancellation_reason,
    ]);

    session()->flash('success', '予約を更新しました。');
    return $this->redirect(route('reservations.show', $this->reservation), navigate: true);
};

// 予約を削除
$delete = function () {
    $this->authorize('delete', $this->reservation);
    
    $this->reservation->delete();
    
    session()->flash('success', '予約を削除しました。');
    return $this->redirect(route('reservations.index'), navigate: true);
};

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">予約編集</flux:heading>
    </flux:header>

    <flux:main container>
        <div class="max-w-3xl">
            <flux:card>
                <form wire:submit="update" class="space-y-6">
                    <!-- 体験プログラム選択 -->
                    <flux:field>
                        <flux:label>体験プログラム <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="program_id" required>
                            <option value="">プログラムを選択してください</option>
                            @foreach ($workshops->groupBy('category.name') as $categoryName => $categoryWorkshops)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach ($categoryWorkshops as $workshop)
                                        <option value="{{ $workshop->program_id }}">
                                            {{ $workshop->program_name }} ({{ number_format($workshop->price_per_person) }}円/人)
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </flux:select>
                        <flux:error name="program_id" />
                    </flux:field>

                    <!-- 予約日時 -->
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>予約日 <span class="text-red-500">*</span></flux:label>
                            <flux:input type="date" wire:model="reservation_date" required />
                            <flux:error name="reservation_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label>予約時刻 <span class="text-red-500">*</span></flux:label>
                            <flux:input type="time" wire:model="reservation_time" required />
                            <flux:error name="reservation_time" />
                        </flux:field>
                    </div>

                    <!-- 人数 -->
                    <flux:field>
                        <flux:label>人数 <span class="text-red-500">*</span></flux:label>
                        <flux:input type="number" wire:model="num_people" min="1" required />
                        <flux:error name="num_people" />
                    </flux:field>

                    <!-- 顧客情報 -->
                    <flux:separator text="顧客情報" />

                    <flux:field>
                        <flux:label>氏名 <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="customer_name" placeholder="山田 太郎" required />
                        <flux:error name="customer_name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>メールアドレス <span class="text-red-500">*</span></flux:label>
                        <flux:input type="email" wire:model="customer_email" placeholder="example@example.com" required />
                        <flux:error name="customer_email" />
                    </flux:field>

                    <flux:field>
                        <flux:label>電話番号 <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="customer_phone" placeholder="090-1234-5678" required />
                        <flux:error name="customer_phone" />
                    </flux:field>

                    <!-- 予約情報 -->
                    <flux:separator text="予約情報" />

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>予約状況 <span class="text-red-500">*</span></flux:label>
                            <flux:select wire:model.live="status" required>
                                <option value="pending">予約受付</option>
                                <option value="confirmed">確定</option>
                                <option value="canceled">キャンセル</option>
                            </flux:select>
                            <flux:error name="status" />
                        </flux:field>

                        <flux:field>
                            <flux:label>予約経路 <span class="text-red-500">*</span></flux:label>
                            <flux:select wire:model="source" required>
                                <option value="web">Web</option>
                                <option value="phone">電話</option>
                                <option value="walk-in">来店</option>
                                <option value="asoview">アソビュー</option>
                                <option value="jalan">じゃらん</option>
                            </flux:select>
                            <flux:error name="source" />
                        </flux:field>
                    </div>

                    <!-- キャンセル理由（ステータスがキャンセルの場合のみ表示） -->
                    @if ($status === 'canceled')
                        <flux:field>
                            <flux:label>キャンセル理由</flux:label>
                            <flux:textarea wire:model="cancellation_reason" rows="3" />
                            <flux:error name="cancellation_reason" />
                        </flux:field>
                    @endif

                    <flux:field>
                        <flux:label>オプション（JSON形式）</flux:label>
                        <flux:textarea wire:model="options" rows="3" placeholder='{"option1": "value1"}' />
                        <flux:description>JSON形式で入力してください</flux:description>
                        <flux:error name="options" />
                    </flux:field>

                    <flux:field>
                        <flux:label>コメント</flux:label>
                        <flux:textarea wire:model="comment" rows="3" />
                        <flux:error name="comment" />
                    </flux:field>

                    <!-- ボタン -->
                    <div class="flex gap-3 justify-between pt-4">
                        <flux:button variant="danger" wire:click="delete" wire:confirm="本当に削除しますか？">
                            削除
                        </flux:button>
                        <div class="flex gap-3">
                            <flux:button variant="ghost" href="{{ route('reservations.show', $reservation) }}" wire:navigate>
                                キャンセル
                            </flux:button>
                            <flux:button type="submit" variant="primary">
                                更新
                            </flux:button>
                        </div>
                    </div>
                </form>
            </flux:card>
        </div>
    </flux:main>
</div>
