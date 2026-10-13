<?php

declare(strict_types=1);

namespace TranquilTools\FormBuilder\Fields;

class Files extends File
{
    protected string $type = 'file';

    /** @var list<array{key: string, name: string, size?: int, url?: string}>|null */
    protected ?array $currentFiles = null;

    public function __construct()
    {
        $this->multiple();
    }

    /**
     * @param  array<int|string, array{key?: int|string, name?: string, size?: int, url?: string}>|string|null  $files
     */
    public function current(array|string|null $files, ?string $name = null): static
    {
        if (! is_array($files)) {
            $this->currentFiles = null;
            unset($this->attributes['currentFiles']);

            return parent::current($files, $name);
        }

        parent::current(null);

        $this->currentFiles = array_map(
            fn (array $file, int|string $index): array => $this->describeCurrentFile($file, $index),
            $files,
            array_keys($files),
        );

        if ($this->currentFiles === []) {
            $this->currentFiles = null;
            unset($this->attributes['currentFiles']);

            return $this;
        }

        $this->attributes['currentFiles'] = $this->currentFiles;

        return $this;
    }

    /**
     * @return list<string>|null
     */
    protected function removableKeys(): ?array
    {
        if (is_null($this->currentFiles)) {
            return null;
        }

        return array_column($this->currentFiles, 'key');
    }

    /**
     * @param  array{key?: int|string, name?: string, size?: int, url?: string}  $file
     * @return array{key: string, name: string, size?: int, url?: string}
     */
    private function describeCurrentFile(array $file, int|string $index): array
    {
        $url = isset($file['url']) && $file['url'] !== '' ? (string) $file['url'] : null;

        $description = [
            'key' => (string) ($file['key'] ?? $index),
            'name' => (string) ($file['name'] ?? (is_null($url) ? '' : basename((string) parse_url($url, PHP_URL_PATH)))),
        ];

        if (isset($file['size'])) {
            $description['size'] = (int) $file['size'];
        }

        if (! is_null($url)) {
            $description['url'] = $url;
        }

        return $description;
    }
}
