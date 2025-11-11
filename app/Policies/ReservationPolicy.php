<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * 予約情報のアクセス制御ポリシー
 */
class ReservationPolicy
{
    /**
     * 予約一覧の閲覧権限
     * 認証済みユーザーは全員閲覧可能
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 予約詳細の閲覧権限
     * 認証済みユーザーは全員閲覧可能
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return true;
    }

    /**
     * 予約の作成権限
     * 認証済みユーザーは全員作成可能
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 予約の更新権限
     * 認証済みユーザーは全員更新可能
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return true;
    }

    /**
     * 予約の削除権限
     * 認証済みユーザーは全員削除可能
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return true;
    }

    /**
     * 予約の復元権限
     * 認証済みユーザーは全員復元可能
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        return true;
    }

    /**
     * 予約の完全削除権限
     * 認証済みユーザーは全員完全削除可能
     */
    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return true;
    }
}
