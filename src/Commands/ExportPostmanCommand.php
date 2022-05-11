<?php

namespace AndreasElia\PostmanGenerator\Commands;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\FormRequest;
use AndreasElia\PostmanGenerator\PostmanMapper;
use Illuminate\Validation\ValidationRuleParser;

class ExportPostmanCommand extends Command
{
    /** @var string */
    protected $signature = 'export:postman
                        {--bearer= : The bearer token to use on your endpoints}
                        {--basic= : The basic auth to use on your endpoints}';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    /** @var \Illuminate\Routing\Router */
    protected $router;

    /** @var array */
    protected $structure;

    /** @var array */
    protected $config;

    /** @var null */
    protected $filename;

    /** @var string */
    private $token;

    /** @var string */
    private $authType;

    /** @var array */
    private const AUTH_OPTIONS = [
        'bearer',
        'basic',
    ];

    /** @var \Illuminate\Validation\Validator */
    private $validator;

    public function __construct(Router $router, Repository $config)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = $config['api-postman'];
    }

    public function handle(): void
    {
        $filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            $this->config['filename']
        );

        $mapper = (new PostmanMapper)
            ->setFilename($filename);

        Storage::disk($this->config['disk'])
            ->put('postman/'.$filename, json_encode($this->structure));

        $this->info('Postman Collection Exported: '.storage_path('app/postman/'.$filename));
    }
}
