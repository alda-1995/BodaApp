<?php

namespace App\DTOs\SystemSection;

use App\Http\Requests\Event\UpdateGiftRegistrySectionRequest;

class SystemSectionDTO
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly int $order,
        public readonly bool $isGlobal,
        public readonly bool $isActive,
        public readonly string $type,
        public readonly array $schema,
        public readonly string $parent,
    ) {}

    public static function fromRequest(UpdateGiftRegistrySectionRequest $request): self
    {
        return new self(
            key: $request->validated('key'),
            title: $request->validated('title'),
            order: (int) $request->validated('order'),
            type: $request->validated('type', 'repeater'),
            parent: $request->validated('parent', 'gift_registry'),
            isGlobal: $request->boolean('is_global'),
            isActive: $request->boolean('is_active'),
            schema: $request->validated('schema', [])
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'order' => $this->order,
            'is_global' => $this->isGlobal,
            'is_active' => $this->isActive,
            'schema' => json_encode($this->schema, JSON_UNESCAPED_UNICODE),
            'type' => $this->type,
            'parent' => $this->parent,
        ];
    }
}