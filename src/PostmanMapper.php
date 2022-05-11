<?php

namespace AndreasElia\PostmanGenerator;

class PostmanMapper
{
    public string $filename;

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }
}
