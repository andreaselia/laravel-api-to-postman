<?php

namespace AndreasElia\PostmanGenerator\Commands;

use AndreasElia\PostmanGenerator\Authentication\AuthenticationMethod;
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
    protected $description = "Automatically generate a Postman collection for your API routes";

    protected ?AuthenticationMethod $authenticationMethod = null;

    public function handle(Exporter $exporter): void
    {
        $filename = str_replace(
            ["{timestamp}", "{app}"],
            [date("Y_m_d_His"), Str::snake(config("app.name"))],
            config("api-postman.filename")
        );

        $collectionName = str_replace(
            "{app}",
            config("app.name"),
            config("api-postman.collection_name")
        );

        $this->resolveAuth();

        config()->set("api-postman.authentication", [
            "method" => $this->authenticationMethod?->prefix() ?? null,
            "token" => $this->authenticationMethod?->getToken() ?? null,
        ]);

        $exporter
            ->to($filename)
            ->collectionName($collectionName)
            ->export();

        Storage::disk(config("api-postman.disk"))
            ->put("postman/" . $filename, $exporter->getOutput());

        $this->info("Postman Collection Exported: " . storage_path("app/postman/" . $filename));
    }

    protected function resolveAuth(): void
    {
        $optionDefault = config("api-postman.authentication.method");
        $tokenDefault = config("api-postman.authentication.token");

        $option = $this->option("bearer")
            ? "bearer"
            : ($this->option("basic")
                ? "basic"
                : $optionDefault);

        if ($option === "bearer") {
            $this->authenticationMethod = new \AndreasElia\PostmanGenerator\Authentication\Bearer(
                $this->option("bearer") ?: $tokenDefault
            );
        }

        if ($option === "basic") {
            $this->authenticationMethod = new \AndreasElia\PostmanGenerator\Authentication\Basic(
                $this->option("basic") ?: $tokenDefault
            );
        }
    }
}
