<?php

namespace App\Message;

final class FileUploadMessage
{
    private $fileName;
    private $userId;

    public function __construct(string $fileName, int $userId)
    {
        $this->fileName = $fileName;
        $this->userId = $userId;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}