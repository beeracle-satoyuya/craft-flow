<?php

use function Livewire\Volt\{with, state};
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;

// 検索とフィルタリング用の状態
state(['search' => '', 'statusFilter' => '']);

// 予約一覧データを取得
with(fn() => [
    'reservations' => Reservation::query()
        ->with(['workshop.category', 'user'])
        ->when($this->search, function (Builder $query) {
            $query->where(function (Builder $q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_email', 'like', '%' . $this->search . '%')
                    ->orWhere('customer_phone', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->statusFilter, function (Builder $query) {
            $query->where('status', $this->statusFilter);
        })
        ->orderBy('reservation_datetime', 'desc')
        ->paginate(20)
]);

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 新規予約ボタン（目立つ位置） -->
        <div class="mb-6">
            <flux:button variant="primary" href="{{ route('reservations.create') }}" wire:navigate size="lg" class="w-full sm:w-auto">
                <flux:icon.plus variant="micro" class="mr-2" />
                新しい予約を登録する
            </flux:button>
        </div>

        <!-- 検索とフィルター -->
        <div class="mb-6 flex gap-4">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="顧客名、メール、電話番号で検索..." />
            </div>
            <div class="w-48">
                <flux:select wire:model.live="statusFilter">
                    <option value="">すべての状態</option>
                    <option value="pending">予約受付</option>
                    <option value="confirmed">確定</option>
                    <option value="canceled">キャンセル</option>
                </flux:select>
            </div>
        </div>

        <!-- 予約一覧テーブル -->
        <flux:card>
            <div class="overflow-x-auto">
                <flux:table>
                    <flux:columns>
                        <flux:column>予約日時</flux:column>
                        <flux:column>顧客名</flux:column>
                        <flux:column>プログラム</flux:column>
                        <flux:column>人数</flux:column>
                        <flux:column>ステータス</flux:column>
                        <flux:column>予約経路</flux:column>
                        <flux:column>登録者</flux:column>
                        <flux:column class="text-right">操作</flux:column>
                    </flux:columns>

                    <flux:rows>
                        @forelse ($reservations as $reservation)
                            <flux:row :key="$reservation->reservation_id">
                                <flux:cell>
                                    {{ $reservation->reservation_datetime->format('Y/m/d H:i') }}
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $reservation->customer_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $reservation->customer_email }}</div>
                                </flux:cell>
                                <flux:cell>
                                    <div class="font-medium">{{ $reservation->workshop->program_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $reservation->workshop->category->name }}</div>
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
                                <flux:cell>
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
                                </flux:cell>
                                <flux:cell>{{ $reservation->staff->name }}</flux:cell>
                                <flux:cell class="text-right">
                                    <flux:button size="sm" variant="ghost" href="{{ route('reservations.show', $reservation) }}" wire:navigate>
                                        詳細
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" href="{{ route('reservations.edit', $reservation) }}" wire:navigate>
                                        編集
                                    </flux:button>
                                </flux:cell>
                            </flux:row>
                        @empty
                            <flux:row>
                                <flux:cell colspan="8" class="text-center py-12">
                                    <div class="text-zinc-500 mb-4">
                                        <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="mt-2 text-sm font-medium">予約がまだありません</p>
                                        <p class="mt-1 text-sm text-zinc-400">上の「新しい予約を登録する」ボタンから予約を作成できます</p>
                                    </div>
                                </flux:cell>
                            </flux:row>
                        @endforelse
                    </flux:rows>
                </flux:table>
            </div>

            <!-- ページネーション -->
            @if ($reservations->hasPages())
                <div class="mt-4 px-6 pb-6">
                    {{ $reservations->links() }}
                </div>
            @endif
        </flux:card>
    </div>
</div>

