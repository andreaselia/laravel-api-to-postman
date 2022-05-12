<?php

namespace AndreasElia\PostmanGenerator\Commands;

use AndreasElia\PostmanGenerator\PostmanExporter;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportPostmanCommand extends Command
{
    protected $signature = 'export:postman
                        {--bearer= : The bearer token to use on your endpoints}
                        {--basic= : The basic auth to use on your endpoints}';

    protected $description = 'Automatically generate a Postman collection for your API routes';

    protected array $config = [];

    protected array $authOptions = [
        'bearer',
        'basic',
    ];

    public function handle(Router $router, Repository $config): void
    {
        $this->config = $config['api-postman'];

        $filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            $this->config['filename']
        );

        $auth = $this->getAuth();

        $exporter = (new PostmanExporter)
            ->setFilename($filename)
            ->setAuth($auth['type'], $auth['token']);

        Storage::disk($this->config['disk'])
            ->put('postman/'.$filename, $exporter->getStructure());

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }

    protected function getAuth()
    {
        foreach ($this->authTypes as $authType) {
            if ($token = $this->option($authType)) {
                return [
                    'type' => $authType,
                    'token' => $token ?? null,
                ];
            }
        }

        return [
            'type' => null,
            'token' => null,
        ];
    }
}
