<?php

namespace App\DTOs\Event;

class EventDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $templateId,
        public readonly int $orderId,
        /** Null hasta que la pareja la capture en el wizard. */
        public readonly ?\DateTimeInterface $eventDate = null,
        public readonly array $features = [],
        public readonly bool $isActive = true
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        return new self(
            userId: $userId,
            templateId: (int) $data['template_id'],
            orderId: (int) $data['order_id'],
            eventDate: match (true) {
                empty($data['event_date']) => null,
                $data['event_date'] instanceof \DateTimeInterface => $data['event_date'],
                default => new \DateTime($data['event_date']),
            },
            features: $data['features'] ?? [],
            isActive: (bool) ($data['is_active'] ?? true)
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'template_id' => $this->templateId,
            'order_id' => $this->orderId,
            'event_date' => $this->eventDate?->format('Y-m-d H:i:s'),
            'features' => (object) $this->features,
            'is_active' => $this->isActive,
        ];
    }
}