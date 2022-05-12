<?php

namespace AndreasElia\PostmanGenerator;

class PostmanExporter
{
    public string $filename;

    public ?string $authType;

    public ?string $authToken;

    protected array $structure;

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function setAuth(?string $authType, ?string $authToken)
    {
        $this->authType = $authType;
        $this->authToken = $authToken;

        return $this;
    }

    public function getStructure()
    {
        return json_encode($this->structure);
    }
}
