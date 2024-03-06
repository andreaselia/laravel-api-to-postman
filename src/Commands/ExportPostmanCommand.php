<?php

namespace AndreasElia\PostmanGenerator\Commands;

use AndreasElia\PostmanGenerator\Exporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportPostmanCommand extends Command
{
    /** @var string */
    protected $signature = 'export:postman
                            {--bearer= : The bearer token to use on your endpoints}
                            {--basic= : The basic auth to use on your endpoints}';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    public function handle(Exporter $exporter): void
    {
        $filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            config('api-postman.filename')
        );

        config()->set('api-postman.authentication', [
            'method' => $this->option('bearer') ? 'bearer' : ($this->option('basic') ? 'basic' : null),
            'token' => $this->option('bearer') ?? $this->option('basic') ?? null,
        ]);

        $exporter
            ->to($filename)
            ->setAuthentication(value(function () {
                if (filled($this->option('bearer'))) {
                    return new \AndreasElia\PostmanGenerator\Authentication\Bearer($this->option('bearer'));
                }

                if (filled($this->option('basic'))) {
                    return new \AndreasElia\PostmanGenerator\Authentication\Basic($this->option('basic'));
                }

                return null;
            }))
            ->export();

        Storage::disk(config('api-postman.disk'))
            ->put('postman/'.$filename, $exporter->getOutput());

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }
}
