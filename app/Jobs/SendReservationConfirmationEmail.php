<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ReservationConfirmation;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReservationConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Reservation $reservation)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 予約データを再読み込みして最新の状態を取得
        $this->reservation->load(['workshop.category', 'staff']);

        // メール送信
        Mail::to($this->reservation->customer_email)
            ->send(new ReservationConfirmation($this->reservation));
    }
}

