<?php

declare(strict_types=1);

namespace Greenrivers\PimcoreIntegration\Model\Queue\Category;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

class Publisher
{
    public const TOPIC_NAME = 'greenrivers.pimcore.category';

    /**
     * Publisher constructor.
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly PublisherInterface  $publisher,
        private readonly SerializerInterface $serializer
    )
    {
    }

    /**
     * @param array $categoryData
     * @return void
     */
    public function publish(array $categoryData): void
    {
        $this->publisher->publish(self::TOPIC_NAME, $this->serializer->serialize($categoryData));
    }
}
