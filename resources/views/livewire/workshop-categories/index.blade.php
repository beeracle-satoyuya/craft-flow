<?php

use function Livewire\Volt\{state, rules, with};
use App\Models\WorkshopCategory;

// フォーム状態
state([
    'name' => '',
    'editingId' => null,
    'editingName' => '',
]);

// バリデーションルール
rules([
    'name' => 'required|string|max:255',
    'editingName' => 'required|string|max:255',
]);

// カテゴリ一覧を取得
with(fn() => [
    'categories' => WorkshopCategory::withCount('workshops')->orderBy('name')->get(),
]);

// 新規カテゴリを作成
$create = function () {
    $this->validate(['name' => 'required|string|max:255']);

    WorkshopCategory::create([
        'name' => $this->name,
    ]);

    $this->name = '';
    session()->flash('success', 'カテゴリを追加しました。');
};

// 編集モードに切り替え
$startEdit = function ($id, $name) {
    $this->editingId = $id;
    $this->editingName = $name;
};

// 編集をキャンセル
$cancelEdit = function () {
    $this->editingId = null;
    $this->editingName = '';
};

// カテゴリを更新
$update = function ($id) {
    $this->validate(['editingName' => 'required|string|max:255']);

    $category = WorkshopCategory::findOrFail($id);
    $category->update([
        'name' => $this->editingName,
    ]);

    $this->editingId = null;
    $this->editingName = '';
    session()->flash('success', 'カテゴリを更新しました。');
};

// カテゴリを削除
$delete = function ($id) {
    $category = WorkshopCategory::findOrFail($id);
    
    // プログラムが紐づいている場合は削除不可
    if ($category->workshops()->count() > 0) {
        session()->flash('error', 'プログラムが存在するため削除できません。');
        return;
    }
    
    $category->delete();
    session()->flash('success', 'カテゴリを削除しました。');
};

?>

<div>
    <flux:header container class="bg-white border-b">
        <flux:heading size="xl" class="mb-0">プログラムカテゴリ管理</flux:heading>
    </flux:header>

    <flux:main container>
        <div class="max-w-4xl">
            <!-- 新規カテゴリ追加フォーム -->
            <flux:card class="mb-6">
                <flux:heading size="lg" class="mb-4">新規カテゴリ追加</flux:heading>
                
                <form wire:submit="create" class="flex gap-3">
                    <div class="flex-1">
                        <flux:input 
                            wire:model="name" 
                            placeholder="カテゴリ名を入力..." 
                            required 
                        />
                        <flux:error name="name" />
                    </div>
                    <flux:button type="submit" variant="primary">
                        <flux:icon.plus variant="micro" />
                        追加
                    </flux:button>
                </form>
            </flux:card>

            <!-- カテゴリ一覧 -->
            <flux:card>
                <flux:heading size="lg" class="mb-4">カテゴリ一覧</flux:heading>
                
                @if ($categories->isEmpty())
                    <div class="text-center py-12 text-zinc-500">
                        カテゴリがありません
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($categories as $category)
                            <div class="flex items-center gap-3 p-4 bg-zinc-50 rounded-lg hover:bg-zinc-100 transition">
                                @if ($editingId === $category->id)
                                    <!-- 編集モード -->
                                    <div class="flex-1 flex gap-3">
                                        <flux:input 
                                            wire:model="editingName" 
                                            class="flex-1"
                                            required 
                                        />
                                        <flux:button 
                                            size="sm" 
                                            variant="primary" 
                                            wire:click="update({{ $category->id }})"
                                        >
                                            保存
                                        </flux:button>
                                        <flux:button 
                                            size="sm" 
                                            variant="ghost" 
                                            wire:click="cancelEdit"
                                        >
                                            キャンセル
                                        </flux:button>
                                    </div>
                                @else
                                    <!-- 表示モード -->
                                    <div class="flex-1">
                                        <div class="font-medium text-lg">{{ $category->name }}</div>
                                        <div class="text-sm text-zinc-500">
                                            {{ $category->workshops_count }}件のプログラム
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button 
                                            size="sm" 
                                            variant="ghost" 
                                            wire:click="startEdit({{ $category->id }}, '{{ addslashes($category->name) }}')"
                                        >
                                            <flux:icon.pencil variant="micro" />
                                            編集
                                        </flux:button>
                                        <flux:button 
                                            size="sm" 
                                            variant="danger" 
                                            wire:click="delete({{ $category->id }})"
                                            wire:confirm="本当に削除しますか？プログラムが存在する場合は削除できません。"
                                        >
                                            <flux:icon.trash variant="micro" />
                                            削除
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </flux:card>

            <!-- 戻るボタン -->
            <div class="mt-6">
                <flux:button variant="ghost" href="{{ route('workshops.index') }}" wire:navigate>
                    <flux:icon.arrow-left variant="micro" />
                    プログラム一覧に戻る
                </flux:button>
            </div>
        </div>
    </flux:main>
</div>
