<?php

namespace AndreasElia\PostmanGenerator;

class PostmanExporter
{
    public string $filename;

    public ?string $authType;

    protected array $structure;

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function setAuthType(?string $authType)
    {
        $this->authType = $authType;

        return $this;
    }

    public function getStructure()
    {
        return json_encode($this->structure);
    }
}
