<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/**
 * Represents a shipment object from the Ship24 API tracking result.
 */
class Shipment
{
    /**
     * @param string|null   $shipmentId             Ship24 internal shipment ID
     * @param string|null   $statusCode             Normalised status code
     * @param string|null   $statusCategory         Status category
     * @param string|null   $statusMilestone        Milestone slug
     * @param string|null   $originCountryCode      ISO 3166-1 alpha-2 origin country
     * @param string|null   $destinationCountryCode ISO 3166-1 alpha-2 destination country
     * @param Delivery|null $delivery               Delivery window and service details
     * @param array<int, array<string, string>> $trackingNumbers All tracking numbers linked to this shipment
     * @param Recipient|null $recipient             Recipient address details
     */
    public function __construct(
        public readonly ?string $shipmentId,
        public readonly ?string $statusCode,
        public readonly ?string $statusCategory,
        public readonly ?string $statusMilestone,
        public readonly ?string $originCountryCode,
        public readonly ?string $destinationCountryCode,
        public readonly ?Delivery $delivery,
        public readonly array $trackingNumbers,
        public readonly ?Recipient $recipient,
    ) {}

    /**
     * @param  array<string, mixed> $item
     * @return self
     */
    public static function fromArray(array $item): self
    {
        $delivery = isset($item['delivery']) && is_array($item['delivery'])
            ? Delivery::fromArray($item['delivery'])
            : null;

        $recipient = isset($item['recipient']) && is_array($item['recipient'])
            ? Recipient::fromArray($item['recipient'])
            : null;

        $trackingNumbers = array_values(
            array_filter((array) ($item['trackingNumbers'] ?? []), static fn ($v) => is_array($v))
        );

        return new self(
            shipmentId: isset($item['shipmentId']) ? (string) $item['shipmentId'] : null,
            statusCode: isset($item['statusCode']) ? (string) $item['statusCode'] : null,
            statusCategory: isset($item['statusCategory']) ? (string) $item['statusCategory'] : null,
            statusMilestone: isset($item['statusMilestone']) ? (string) $item['statusMilestone'] : null,
            originCountryCode: isset($item['originCountryCode']) ? (string) $item['originCountryCode'] : null,
            destinationCountryCode: isset($item['destinationCountryCode']) ? (string) $item['destinationCountryCode'] : null,
            delivery: $delivery,
            trackingNumbers: $trackingNumbers,
            recipient: $recipient,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'shipmentId'             => $this->shipmentId,
            'statusCode'             => $this->statusCode,
            'statusCategory'         => $this->statusCategory,
            'statusMilestone'        => $this->statusMilestone,
            'originCountryCode'      => $this->originCountryCode,
            'destinationCountryCode' => $this->destinationCountryCode,
            'delivery'               => $this->delivery?->toArray(),
            'trackingNumbers'        => $this->trackingNumbers,
            'recipient'              => $this->recipient?->toArray(),
        ];
    }
}
