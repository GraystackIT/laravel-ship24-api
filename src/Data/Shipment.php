<?php

declare(strict_types=1);

namespace Graystack\Ship24\Data;

class Shipment
{
    public function __construct(
        public readonly string $shipmentId,
        public readonly string $trackingNumber,
        public readonly ?string $slug,
        public readonly ?string $currentCourierId,
        public readonly ?string $currentCourierName,
        public readonly ?string $originCountryCode,
        public readonly ?string $destinationCountryCode,
        public readonly ?string $deliveryEstimate,
        public readonly ?string $statusCode,
        public readonly ?string $statusCategory,
        public readonly ?string $statusMilestone,
        /** @var string[] */
        public readonly array $courierIds,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            shipmentId: (string) ($item['shipmentId'] ?? ''),
            trackingNumber: (string) ($item['trackingNumber'] ?? ''),
            slug: isset($item['slug']) ? (string) $item['slug'] : null,
            currentCourierId: isset($item['currentCourierId']) ? (string) $item['currentCourierId'] : null,
            currentCourierName: isset($item['currentCourierName']) ? (string) $item['currentCourierName'] : null,
            originCountryCode: isset($item['originCountryCode']) ? (string) $item['originCountryCode'] : null,
            destinationCountryCode: isset($item['destinationCountryCode']) ? (string) $item['destinationCountryCode'] : null,
            deliveryEstimate: isset($item['deliveryEstimate']) ? (string) $item['deliveryEstimate'] : null,
            statusCode: isset($item['statusCode']) ? (string) $item['statusCode'] : null,
            statusCategory: isset($item['statusCategory']) ? (string) $item['statusCategory'] : null,
            statusMilestone: isset($item['statusMilestone']) ? (string) $item['statusMilestone'] : null,
            courierIds: (array) ($item['courierIds'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'shipmentId'             => $this->shipmentId,
            'trackingNumber'         => $this->trackingNumber,
            'slug'                   => $this->slug,
            'currentCourierId'       => $this->currentCourierId,
            'currentCourierName'     => $this->currentCourierName,
            'originCountryCode'      => $this->originCountryCode,
            'destinationCountryCode' => $this->destinationCountryCode,
            'deliveryEstimate'       => $this->deliveryEstimate,
            'statusCode'             => $this->statusCode,
            'statusCategory'         => $this->statusCategory,
            'statusMilestone'        => $this->statusMilestone,
            'courierIds'             => $this->courierIds,
        ];
    }
}
