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

        if (filled($this->option('bearer')) || filled($this->option('basic'))) {
            config()->set('api-postman.authentication', [
                'method' => filled($this->option('bearer')) ? 'bearer' : 'basic',
                'token' => filled($this->option('bearer')) ? $this->option('bearer') : $this->option('basic'),
            ]);
        }

        if (filled(config('api-postman.authentication.method')) && blank(config('api-postman.authentication.token'))) {
            $this->warn('No authentication token configured, the {{token}} collection variable will be used instead.');
        }

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

        $disk = Storage::disk(config('api-postman.disk'));

        $disk->put('postman/'.$filename, $exporter->getOutput());

        $this->info('Postman Collection Exported: '.$disk->path('postman/'.$filename));
    }
}
