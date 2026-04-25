<?php

namespace AndreasElia\PostmanGenerator\Commands;

use AndreasElia\PostmanGenerator\Authentication\Basic;
use AndreasElia\PostmanGenerator\Authentication\Bearer;
use AndreasElia\PostmanGenerator\Exporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportPostmanCommand extends Command
{
    /** @var string */
    protected $signature = 'export:postman
                            {--bearer= : The bearer token to use on your endpoints}
                            {--basic= : The basic auth to use on your endpoints}
                            {--filename= : The name of the file to be saved}
                            {--body-mode= : The mode of the request body (formdata, json, auto)}
                            {--body-format= : The format of the request body (urlencoded, json)}';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    public function handle(): void
    {
        if ($this->option('filename')) {
            config()->set('api-postman.filename', $this->option('filename'));
        }

        if ($this->option('body-mode')) {
            config()->set('api-postman.body_mode', $this->option('body-mode'));
        }

        if ($this->option('body-format')) {
            config()->set('api-postman.body_format', $this->option('body-format'));
        }

        $exporter = app(Exporter::class);

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
                    return new Bearer($this->option('bearer'));
                }

                if (filled($this->option('basic'))) {
                    return new Basic($this->option('basic'));
                }

                return null;
            }))
            ->export();

        Storage::disk(config('api-postman.disk'))
            ->put('postman/'.$filename, $exporter->getOutput());

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }
}
