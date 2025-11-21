<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Smalot\PdfParser\Parser;

class ParsePdfText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdf:extract-text {file : PDFファイルのパス}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PDFファイルからテキストコンテンツを抽出して表示します';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');

        // ファイルの存在確認
        if (! file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");

            return self::FAILURE;
        }

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            $this->info('=== PDFテキストコンテンツ ===');
            $this->line('');
            $this->line($text);
            $this->line('');
            $this->info('=== テキスト抽出完了 ===');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("PDFのパース中にエラーが発生しました: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
