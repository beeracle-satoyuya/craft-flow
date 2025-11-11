<?php

use function Livewire\Volt\{state, mount};
use App\Models\Workshop;

state(['workshop']);

mount(function (Workshop $workshop) {
    $this->workshop = $workshop->load(['category', 'reservations.user']);
});

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">プログラム詳細</flux:heading>

        <flux:spacer />

        <flux:button variant="primary" href="{{ route('workshops.edit', $workshop) }}" wire:navigate>
            <flux:icon.pencil variant="micro" />
            編集
        </flux:button>
    </flux:header>

    <flux:main container>
        <div class="max-w-4xl">
            <!-- プログラムステータス -->
            <div class="mb-6">
                @if ($workshop->is_active)
                    <flux:badge color="green" size="lg">有効</flux:badge>
                @else
                    <flux:badge color="zinc" size="lg">無効</flux:badge>
                @endif
            </div>

            <!-- プログラム基本情報 -->
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">基本情報</flux:heading>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">カテゴリ</div>
                        <div class="col-span-2 font-medium">{{ $workshop->category->name }}</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">プログラム名</div>
                        <div class="col-span-2 font-medium text-lg">{{ $workshop->program_name }}</div>
                    </div>

                    @if ($workshop->description)
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-sm text-zinc-500">説明</div>
                            <div class="col-span-2 whitespace-pre-wrap">{{ $workshop->description }}</div>
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">所要時間</div>
                        <div class="col-span-2 font-medium">{{ $workshop->duration_minutes }}分</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">最大受入人数</div>
                        <div class="col-span-2 font-medium">{{ $workshop->max_capacity }}名</div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-sm text-zinc-500">料金</div>
                        <div class="col-span-2 font-medium text-lg">{{ number_format($workshop->price_per_person) }}円/人</div>
                    </div>
                </div>
            </flux:card>

            <!-- 予約統計 -->
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">予約統計</flux:heading>
                
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $workshop->reservations->count() }}</div>
                        <div class="text-sm text-zinc-500">総予約数</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ $workshop->reservations->where('status', 'pending')->count() }}</div>
                        <div class="text-sm text-zinc-500">予約受付</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $workshop->reservations->where('status', 'confirmed')->count() }}</div>
                        <div class="text-sm text-zinc-500">確定</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ $workshop->reservations->where('status', 'canceled')->count() }}</div>
                        <div class="text-sm text-zinc-500">キャンセル</div>
                    </div>
                </div>
            </flux:card>

            <!-- 最近の予約 -->
            @if ($workshop->reservations->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">最近の予約</flux:heading>
                    
                    <div class="overflow-x-auto">
                        <flux:table>
                            <flux:columns>
                                <flux:column>予約日時</flux:column>
                                <flux:column>顧客名</flux:column>
                                <flux:column>人数</flux:column>
                                <flux:column>ステータス</flux:column>
                                <flux:column>登録者</flux:column>
                                <flux:column class="text-right">操作</flux:column>
                            </flux:columns>

                            <flux:rows>
                                @foreach ($workshop->reservations->sortByDesc('reservation_datetime')->take(10) as $reservation)
                                    <flux:row :key="$reservation->id">
                                        <flux:cell>
                                            {{ $reservation->reservation_datetime->format('Y/m/d H:i') }}
                                        </flux:cell>
                                        <flux:cell>
                                            <div class="font-medium">{{ $reservation->customer_name }}</div>
                                            <div class="text-xs text-zinc-500">{{ $reservation->customer_email }}</div>
                                        </flux:cell>
                                        <flux:cell>{{ $reservation->num_people }}名</flux:cell>
                                        <flux:cell>
                                            @if ($reservation->status === 'pending')
                                                <flux:badge color="yellow">予約受付</flux:badge>
                                            @elseif ($reservation->status === 'confirmed')
                                                <flux:badge color="green">確定</flux:badge>
                                            @elseif ($reservation->status === 'canceled')
                                                <flux:badge color="red">キャンセル</flux:badge>
                                            @endif
                                        </flux:cell>
                                        <flux:cell>{{ $reservation->user->name }}</flux:cell>
                                        <flux:cell class="text-right">
                                            <flux:button size="sm" variant="ghost" href="{{ route('reservations.show', $reservation) }}" wire:navigate>
                                                詳細
                                            </flux:button>
                                        </flux:cell>
                                    </flux:row>
                                @endforeach
                            </flux:rows>
                        </flux:table>
                    </div>

                    @if ($workshop->reservations->count() > 10)
                        <div class="mt-4 text-center">
                            <flux:button variant="ghost" href="{{ route('reservations.index') }}?workshop={{ $workshop->id }}" wire:navigate>
                                すべての予約を見る
                            </flux:button>
                        </div>
                    @endif
                </flux:card>
            @endif

            <!-- 戻るボタン -->
            <div class="mt-6">
                <flux:button variant="ghost" href="{{ route('workshops.index') }}" wire:navigate>
                    <flux:icon.arrow-left variant="micro" />
                    一覧に戻る
                </flux:button>
            </div>
        </div>
    </flux:main>
</div>
