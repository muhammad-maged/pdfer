<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CreatePilot extends Command
{
    protected $signature = 'pdfer:pilot {name} {--quota=1000} {--logo= : Path to a PNG logo}';
    protected $description = 'Create a pilot account and display its API key once';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $quota = filter_var($this->option('quota'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        if ($name === '' || mb_strlen($name) > 120 || $quota === false) {
            $this->error('Provide a pilot name and a monthly quota between 1 and 10,000.');
            return self::FAILURE;
        }

        $logoBase64 = null;
        if ($logoPath = $this->option('logo')) {
            if (!is_file($logoPath) || !is_readable($logoPath) || filesize($logoPath) > 256 * 1024) {
                $this->error('Provide a readable PNG logo no larger than 256 KB.');
                return self::FAILURE;
            }
            $logo = file_get_contents($logoPath);
            $image = $logo === false ? false : @getimagesizefromstring($logo);
            if (!$image || $image[2] !== IMAGETYPE_PNG || $image[0] > 2048 || $image[1] > 2048) {
                $this->error('Provide a PNG logo with dimensions no larger than 2048 × 2048.');
                return self::FAILURE;
            }
            $logoBase64 = base64_encode($logo);
        }

        $key = 'pdfer_'.bin2hex(random_bytes(32));
        DB::table('pilots')->insert([
            'name' => $name,
            'key_hash' => hash('sha256', $key),
            'monthly_quota' => $quota,
            'active' => true,
            'logo_base64' => $logoBase64,
        ]);

        $this->line($key);
        return self::SUCCESS;
    }
}
