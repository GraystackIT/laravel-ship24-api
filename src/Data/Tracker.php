<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/**
 * Represents a Ship24 tracker resource.
 */
class Tracker
{
    /**
     * @param string      $trackerId            Ship24 internal tracker ID
     * @param string      $trackingNumber       Parcel tracking number
     * @param string|null $shipmentReference    Caller-provided reference
     * @param string|null $clientTrackerId      Caller-provided unique identifier
     * @param string[]    $courierCode          Courier codes assigned to this tracker
     * @param string      $createdAt            ISO 8601 creation timestamp
     * @param bool        $isSubscribed         Whether the tracker is active
     * @param bool|null   $isTracked            Whether new data is being fetched
     * @param string|null $activeUntilDatetime  Legacy field, may be absent in current responses
     */
    public function __construct(
        public readonly string $trackerId,
        public readonly string $trackingNumber,
        public readonly ?string $shipmentReference,
        public readonly ?string $clientTrackerId,
        public readonly array $courierCode,
        public readonly string $createdAt,
        public readonly bool $isSubscribed,
        public readonly ?bool $isTracked,
        public readonly ?string $activeUntilDatetime,
    ) {}

    /**
     * @param  array<string, mixed> $item
     * @return self
     */
    public static function fromArray(array $item): self
    {
        $courierCode = $item['courierCode'] ?? [];

        return new self(
            trackerId: (string) ($item['trackerId'] ?? ''),
            trackingNumber: (string) ($item['trackingNumber'] ?? ''),
            shipmentReference: isset($item['shipmentReference']) ? (string) $item['shipmentReference'] : null,
            clientTrackerId: isset($item['clientTrackerId']) ? (string) $item['clientTrackerId'] : null,
            courierCode: is_array($courierCode) ? $courierCode : (array) $courierCode,
            createdAt: (string) ($item['createdAt'] ?? ''),
            isSubscribed: (bool) ($item['isSubscribed'] ?? false),
            isTracked: isset($item['isTracked']) ? (bool) $item['isTracked'] : null,
            activeUntilDatetime: isset($item['activeUntilDatetime']) ? (string) $item['activeUntilDatetime'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'trackerId'           => $this->trackerId,
            'trackingNumber'      => $this->trackingNumber,
            'shipmentReference'   => $this->shipmentReference,
            'clientTrackerId'     => $this->clientTrackerId,
            'courierCode'         => $this->courierCode,
            'createdAt'           => $this->createdAt,
            'isSubscribed'        => $this->isSubscribed,
            'isTracked'           => $this->isTracked,
            'activeUntilDatetime' => $this->activeUntilDatetime,
        ];
    }
}
