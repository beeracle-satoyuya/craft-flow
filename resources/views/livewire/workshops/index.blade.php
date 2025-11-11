<?php

use function Livewire\Volt\{with, state};
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Builder;

// 検索とフィルタリング用の状態
state(['search' => '', 'categoryFilter' => '', 'activeFilter' => '']);

// 体験プログラム一覧データを取得
with(fn() => [
    'workshops' => Workshop::query()
        ->with(['category', 'reservations'])
        ->when($this->search, function (Builder $query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
        })
        ->when($this->categoryFilter, function (Builder $query) {
            $query->where('workshop_category_id', $this->categoryFilter);
        })
        ->when($this->activeFilter !== '', function (Builder $query) {
            $query->where('is_active', $this->activeFilter === '1');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(20),
    'categories' => \App\Models\WorkshopCategory::orderBy('name')->get(),
]);

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">体験プログラム管理</flux:heading>

        <flux:spacer />

        <flux:button variant="primary" href="{{ route('workshops.create') }}" wire:navigate>
            <flux:icon.plus variant="micro" />
            新規プログラム
        </flux:button>
    </flux:header>

    <flux:main container>
        <!-- 検索とフィルター -->
        <div class="mb-6 flex gap-4">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="プログラム名、説明で検索..." />
            </div>
            <div class="w-48">
                <flux:select wire:model.live="categoryFilter">
                    <option value="">すべてのカテゴリ</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div class="w-40">
                <flux:select wire:model.live="activeFilter">
                    <option value="">すべて</option>
                    <option value="1">有効</option>
                    <option value="0">無効</option>
                </flux:select>
            </div>
        </div>

        <!-- プログラム一覧 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($workshops as $workshop)
                <flux:card class="flex flex-col">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <flux:heading size="lg" class="mb-1">{{ $workshop->program_name }}</flux:heading>
                            <flux:badge color="zinc" size="sm">{{ $workshop->category->name }}</flux:badge>
                        </div>
                        <div class="ml-2">
                            @if ($workshop->is_active)
                                <flux:badge color="green">有効</flux:badge>
                            @else
                                <flux:badge color="zinc">無効</flux:badge>
                            @endif
                        </div>
                    </div>

                    @if ($workshop->description)
                        <p class="text-sm text-zinc-600 mb-4 line-clamp-2">
                            {{ $workshop->description }}
                        </p>
                    @endif

                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">所要時間</span>
                            <span class="font-medium">{{ $workshop->duration_minutes }}分</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">最大人数</span>
                            <span class="font-medium">{{ $workshop->max_capacity }}名</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">料金</span>
                            <span class="font-medium text-lg">{{ number_format($workshop->price_per_person) }}円/人</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">予約数</span>
                            <span class="font-medium">{{ $workshop->reservations->count() }}件</span>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-auto pt-4 border-t">
                        <flux:button size="sm" variant="ghost" href="{{ route('workshops.show', $workshop) }}" wire:navigate class="flex-1">
                            詳細
                        </flux:button>
                        <flux:button size="sm" variant="ghost" href="{{ route('workshops.edit', $workshop) }}" wire:navigate class="flex-1">
                            編集
                        </flux:button>
                    </div>
                </flux:card>
            @empty
                <div class="col-span-full text-center py-12 text-zinc-500">
                    体験プログラムがありません
                </div>
            @endforelse
        </div>

        <!-- ページネーション -->
        @if ($workshops->hasPages())
            <div class="mt-6">
                {{ $workshops->links() }}
            </div>
        @endif
    </flux:main>
</div>
