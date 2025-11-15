<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約確認</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #3b82f6;
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 14px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .reservation-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
            min-width: 140px;
            font-size: 14px;
        }
        .detail-value {
            color: #111827;
            font-size: 14px;
            flex: 1;
        }
        .highlight {
            background-color: #dbeafe;
            padding: 15px;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
            margin: 20px 0;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
        }
        @media only screen and (max-width: 600px) {
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>予約確認</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                {{ $reservation->customer_name }} 様
            </div>
            
            <div class="message">
                この度は盛岡手づくり村の体験プログラムにお申し込みいただき、誠にありがとうございます。<br>
                ご予約内容が確定いたしましたので、下記の通りご案内申し上げます。
            </div>
            
            <div class="reservation-details">
                <div class="detail-row">
                    <div class="detail-label">予約番号</div>
                    <div class="detail-value">#{{ $reservation->reservation_id }}</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">体験プログラム</div>
                    <div class="detail-value">
                        {{ $workshop->program_name }}<br>
                        <span style="color: #6b7280; font-size: 12px;">{{ $category->name }}</span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">予約日時</div>
                    <div class="detail-value">
                        <strong>{{ $reservation->reservation_datetime->format('Y年m月d日（') }}{{ ['日', '月', '火', '水', '木', '金', '土'][$reservation->reservation_datetime->dayOfWeek] }}{{ $reservation->reservation_datetime->format('）H:i') }}</strong>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">所要時間</div>
                    <div class="detail-value">約 {{ $workshop->duration_minutes }} 分</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">人数</div>
                    <div class="detail-value">{{ $reservation->num_people }} 名</div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">料金</div>
                    <div class="detail-value">
                        {{ number_format($workshop->price_per_person) }}円 × {{ $reservation->num_people }}名 = 
                        <strong style="font-size: 16px; color: #3b82f6;">{{ number_format($workshop->price_per_person * $reservation->num_people) }}円</strong>
                    </div>
                </div>
                
                @if($reservation->comment)
                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value">{{ $reservation->comment }}</div>
                </div>
                @endif
                
                <div class="detail-row">
                    <div class="detail-label">担当スタッフ</div>
                    <div class="detail-value">{{ $staff->name }}</div>
                </div>
            </div>
            
            <div class="highlight">
                <strong>当日のご案内</strong><br>
                ご予約時刻の10分前までに受付にお越しください。<br>
                ご不明な点やキャンセルのご連絡がございましたら、お気軽にお問い合わせください。
            </div>
            
            <div class="message">
                皆様のお越しを心よりお待ちしております。
            </div>
        </div>
        
        <div class="footer">
            <p><strong>盛岡手づくり村</strong></p>
            <p>このメールは送信専用です。ご返信いただいてもお答えできませんので、ご了承ください。</p>
            <p>お問い合わせは施設へ直接お電話にてお願いいたします。</p>
        </div>
    </div>
</body>
</html>

