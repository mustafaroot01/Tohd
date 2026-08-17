<?php

namespace App\Console\Commands;

use App\Actions\Games\ValidateGameAction;
use App\Models\Game;
use Illuminate\Console\Command;

class ValidateGamesCommand extends Command
{
    protected $signature = 'app:validate-games';
    protected $description = 'التحقق من صحة بنية وإعدادات جميع الألعاب المنشورة في النظام';

    public function handle(ValidateGameAction $validator): int
    {
        $games = Game::all();
        $this->info("بدء فحص عدد ({$games->count()}) لعبة...");

        $errorsCount = 0;
        foreach ($games as $game) {
            try {
                $validator->execute($game);
                $this->line("<info>✔ [{$game->code}]</info> {$game->name} - صالحة");
            } catch (\Throwable $e) {
                $errorsCount++;
                $this->error("✖ [{$game->code}] {$game->name} - خطأ: {$e->getMessage()}");
            }
        }

        if ($errorsCount > 0) {
            $this->warn("اكتمل الفحص مع وجود ({$errorsCount}) أخطاء.");

            return Command::FAILURE;
        }

        $this->info('جميع الألعاب صالحة بنسبة 100%!');

        return Command::SUCCESS;
    }
}
