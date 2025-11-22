<?php

use function Livewire\Volt\{state, rules, mount};
use App\Models\Workshop;
use App\Models\Reservation;
use App\Models\User;
use App\Rules\NoDoubleBooking;
use App\Jobs\SendReservationConfirmationEmail;
use Livewire\Attributes\Validate;

// フォーム状態
state([
    'program_id' => '',
    'staff_id' => fn() => auth()->id(),
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
    'users' => fn() => User::orderBy('name')->get(),
]);

// 予約を保存
$save = function () {
    // 日時を結合
    $reservationDatetime = $this->reservation_date . ' ' . $this->reservation_time;
    
    // バリデーションルール（動的にダブルブッキングチェックを追加）
    $this->validate([
        'program_id' => 'required|exists:workshops,program_id',
        'staff_id' => 'required|exists:users,id',
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
                (int) $this->num_people
            ),
        ],
        'status' => 'required|in:pending,confirmed,canceled',
        'source' => 'required|in:web,phone,walk-in,asoview,jalan',
        'comment' => 'nullable|string',
        'options' => 'nullable|string',
        'cancellation_reason' => 'nullable|string',
    ]);

    $reservation = Reservation::create([
        'program_id' => $this->program_id,
        'staff_id' => $this->staff_id,
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

    // ステータスが「確定」の場合、予約確認メールを送信
    if ($reservation->status === 'confirmed') {
        SendReservationConfirmationEmail::dispatch($reservation);
    }

    session()->flash('success', '予約を登録しました。');
    return $this->redirect(route('reservations.show', $reservation), navigate: true);
};

?>

<div>
    <flux:header container class="bg-white border-b">
        <div class="flex items-center gap-4">
            <a href="{{ route('reservations.index') }}" wire:navigate class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-zinc-700 dark:text-zinc-300">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
            <flux:heading size="xl" class="mb-0">新規予約登録</flux:heading>
        </div>
    </flux:header>

    <flux:main container>
        <div class="max-w-3xl">
            <flux:card>
                <form wire:submit="save" class="space-y-6">
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

                    <!-- 登録スタッフ選択 -->
                    <flux:field>
                        <flux:label>登録スタッフ <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="staff_id" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="staff_id" />
                    </flux:field>

                    <!-- 予約日時 -->
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>予約日 <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="reservation_date" data-date-picker required placeholder="日付を選択してください" />
                            <flux:error name="reservation_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label>予約時刻 <span class="text-red-500">*</span></flux:label>
                            <flux:input type="text" wire:model="reservation_time" data-time-picker required placeholder="時刻を選択してください" />
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
                    <div class="flex gap-3 justify-end pt-4">
                        <flux:button variant="ghost" href="{{ route('reservations.index') }}" wire:navigate>
                            キャンセル
                        </flux:button>
                        <flux:button type="submit" variant="primary">
                            予約を登録
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    </flux:main>
</div>
