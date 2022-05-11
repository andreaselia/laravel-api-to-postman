<?php

namespace AndreasElia\PostmanGenerator;

class PostmanExporter
{
    public string $filename;

    protected array $structure;

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function getStructure()
    {
        return json_encode($this->structure);
    }
}
