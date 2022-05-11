<?php

namespace AndreasElia\PostmanGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Config\Repository;
use AndreasElia\PostmanGenerator\PostmanExporter;

class ExportPostmanCommand extends Command
{
    protected $signature = 'export:postman
                        {--bearer= : The bearer token to use on your endpoints}
                        {--basic= : The basic auth to use on your endpoints}';

    protected $description = 'Automatically generate a Postman collection for your API routes';

    protected Router $router;

    protected array $config;

    protected array $structure;

    public function handle(Router $router, Repository $config): void
    {
        $this->router = $router;
        $this->config = $config['api-postman'];

        $filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            $this->config['filename']
        );

        $mapper = (new PostmanExporter)
            ->setFilename($filename);

        Storage::disk($this->config['disk'])
            ->put('postman/'.$filename, json_encode($this->structure));

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }
}
