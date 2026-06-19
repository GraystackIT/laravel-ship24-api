<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/** Delivery window and service details from the Shipment object. */
class Delivery
{
    /**
     * @param string|null $estimatedDeliveryDate        ISO 8601 or logistics date-time
     * @param string|null $courierEstimatedFrom         Start of the courier's estimated window
     * @param string|null $courierEstimatedTo           End of the courier's estimated window
     * @param string|null $service                      Name of the logistics service/product
     * @param string|null $signedBy                     Name of the person who signed on delivery
     */
    public function __construct(
        public readonly ?string $estimatedDeliveryDate,
        public readonly ?string $courierEstimatedFrom,
        public readonly ?string $courierEstimatedTo,
        public readonly ?string $service,
        public readonly ?string $signedBy,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        $courierEst = isset($item['courierEstimatedDeliveryDate']) && is_array($item['courierEstimatedDeliveryDate'])
            ? $item['courierEstimatedDeliveryDate']
            : [];

        return new self(
            estimatedDeliveryDate: isset($item['estimatedDeliveryDate']) ? (string) $item['estimatedDeliveryDate'] : null,
            courierEstimatedFrom: isset($courierEst['from']) ? (string) $courierEst['from'] : null,
            courierEstimatedTo: isset($courierEst['to']) ? (string) $courierEst['to'] : null,
            service: isset($item['service']) ? (string) $item['service'] : null,
            signedBy: isset($item['signedBy']) ? (string) $item['signedBy'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'estimatedDeliveryDate'       => $this->estimatedDeliveryDate,
            'courierEstimatedDeliveryDate' => [
                'from' => $this->courierEstimatedFrom,
                'to'   => $this->courierEstimatedTo,
            ],
            'service'  => $this->service,
            'signedBy' => $this->signedBy,
        ];
    }
}
