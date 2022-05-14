<?php

namespace AndreasElia\PostmanGenerator\Commands;

use AndreasElia\PostmanGenerator\PostmanExporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportPostmanCommand extends Command
{
    protected $signature = 'export:postman
                        {--bearer= : The bearer token to use on your endpoints}
                        {--basic= : The basic auth to use on your endpoints}';

    protected $description = 'Automatically generate a Postman collection for your API routes';

    public function handle(PostmanExporter $exporter): void
    {
        $filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            config('api-postman.filename')
        );

        $exporter
            ->setFilename($filename)
            ->export();

        Storage::disk(config('api-postman.disk'))
            ->put('postman/'.$filename, $exporter->getStructure());

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }
}
