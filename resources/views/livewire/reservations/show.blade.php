<?php

use function Livewire\Volt\{state, mount};
use App\Models\Reservation;

state(['reservation']);

mount(function (Reservation $reservation) {
    $this->reservation = $reservation->load(['workshop.category', 'user']);
    
    // 認可チェック
    $this->authorize('view', $reservation);
});

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">予約詳細</flux:heading>

        <flux:spacer />

        <flux:button variant="primary" href="{{ route('reservations.edit', $reservation) }}" wire:navigate>
            <flux:icon.pencil variant="micro" />
            編集
        </flux:button>
    </flux:header>

    <flux:main container>
        <div class="max-w-3xl">
            <!-- 予約ステータス -->
            <div class="mb-6">
                @if ($reservation->status === 'pending')
                    <flux:badge color="yellow" size="lg">予約受付</flux:badge>
                @elseif ($reservation->status === 'confirmed')
                    <flux:badge color="green" size="lg">確定</flux:badge>
                @elseif ($reservation->status === 'canceled')
                    <flux:badge color="red" size="lg">キャンセル</flux:badge>
                @endif
            </div>

            <!-- 予約情報 -->
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">予約情報</flux:heading>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">予約日時</div>
                        <div class="col-span-2 font-medium">
                            {{ $reservation->reservation_datetime->format('Y年m月d日 H:i') }}
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">体験プログラム</div>
                        <div class="col-span-2">
                            <div class="font-medium">{{ $reservation->workshop->program_name }}</div>
                            <div class="text-sm text-zinc-500">{{ $reservation->workshop->category->name }}</div>
                            <div class="text-sm text-zinc-600 mt-1">
                                所要時間: {{ $reservation->workshop->duration_minutes }}分 / 
                                料金: {{ number_format($reservation->workshop->price_per_person) }}円/人
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">人数</div>
                        <div class="col-span-2 font-medium">{{ $reservation->num_people }}名</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">合計金額</div>
                        <div class="col-span-2 font-medium text-lg">
                            {{ number_format($reservation->workshop->price_per_person * $reservation->num_people) }}円
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">予約経路</div>
                        <div class="col-span-2">
                            @if ($reservation->source === 'web')
                                Web
                            @elseif ($reservation->source === 'phone')
                                電話
                            @elseif ($reservation->source === 'walk-in')
                                来店
                            @elseif ($reservation->source === 'asoview')
                                アソビュー
                            @elseif ($reservation->source === 'jalan')
                                じゃらん
                            @else
                                {{ $reservation->source }}
                            @endif
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- 顧客情報 -->
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">顧客情報</flux:heading>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">氏名</div>
                        <div class="col-span-2 font-medium">{{ $reservation->customer_name }}</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">メールアドレス</div>
                        <div class="col-span-2">
                            <a href="mailto:{{ $reservation->customer_email }}" class="text-blue-600 hover:underline">
                                {{ $reservation->customer_email }}
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">電話番号</div>
                        <div class="col-span-2">
                            <a href="tel:{{ $reservation->customer_phone }}" class="text-blue-600 hover:underline">
                                {{ $reservation->customer_phone }}
                            </a>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- 追加情報 -->
            @if ($reservation->comment || $reservation->options || $reservation->cancellation_reason)
                <flux:card class="mb-6">
                    <flux:heading size="lg" class="mb-4">追加情報</flux:heading>
                    
                    <div class="space-y-4">
                        @if ($reservation->options)
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-zinc-500">オプション</div>
                                <div class="col-span-2">
                                    <pre class="text-sm bg-zinc-50 p-3 rounded">{{ json_encode($reservation->options, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        @endif

                        @if ($reservation->comment)
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-zinc-500">コメント</div>
                                <div class="col-span-2 whitespace-pre-wrap">{{ $reservation->comment }}</div>
                            </div>
                        @endif

                        @if ($reservation->cancellation_reason)
                            <div class="grid grid-cols-3 gap-4">
                                <div class="text-sm text-zinc-500">キャンセル理由</div>
                                <div class="col-span-2 text-red-600 whitespace-pre-wrap">{{ $reservation->cancellation_reason }}</div>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif

            <!-- システム情報 -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">システム情報</flux:heading>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">登録者</div>
                        <div class="col-span-2">{{ $reservation->staff->name }}</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">登録日時</div>
                        <div class="col-span-2">{{ $reservation->created_at->format('Y年m月d日 H:i') }}</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">更新日時</div>
                        <div class="col-span-2">{{ $reservation->updated_at->format('Y年m月d日 H:i') }}</div>
                    </div>
                </div>
            </flux:card>

            <!-- 戻るボタン -->
            <div class="mt-6">
                <flux:button variant="ghost" href="{{ route('reservations.index') }}" wire:navigate>
                    <flux:icon.arrow-left variant="micro" />
                    一覧に戻る
                </flux:button>
            </div>
        </div>
    </flux:main>
</div>
