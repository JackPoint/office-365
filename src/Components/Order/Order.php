<?php declare(strict_types = 1);

namespace SandwaveIo\Office365\Components\Order;

use DateTime;
use SandwaveIo\Office365\Components\AbstractComponent;
use SandwaveIo\Office365\Entity\OrderModifyQuantity;
use SandwaveIo\Office365\Entity\OrderSummary;
use SandwaveIo\Office365\Entity\Terminate as TerminateEntity;
use SandwaveIo\Office365\Exception\Office365Exception;
use SandwaveIo\Office365\Helper\EntityHelper;
use SandwaveIo\Office365\Helper\XmlHelper;
use SandwaveIo\Office365\Library\Client\WebApiClientInterface;
use SandwaveIo\Office365\Library\Router\RouterInterface;
use SandwaveIo\Office365\Response\OrderSummaryResponse;
use SandwaveIo\Office365\Response\QueuedResponse;
use SandwaveIo\Office365\Transformer\OrderModifyQuantityBuilder;
use SandwaveIo\Office365\Transformer\OrderSummaryBuilder;
use SandwaveIo\Office365\Transformer\TerminateDataBuilder;

final class Order extends AbstractComponent
{
    const ORDER_PREFIX = 'OID';

    public CloudLicense $cloudLicense;

    public function __construct(WebApiClientInterface $client, RouterInterface $router)
    {
        parent::__construct($client, $router);
        $this->cloudLicense = new CloudLicense($client, $router);
    }

    /**
     * @throws Office365Exception
     */
    public function modify(
        int $orderId,
        int $quantity,
        bool $isDelta = false,
        string $partnerReference = ''
    ): QueuedResponse {
        $modification = EntityHelper::deserialize(
            OrderModifyQuantity::class,
            OrderModifyQuantityBuilder::build(...func_get_args())
        );

        try {
            $document = EntityHelper::serialize($modification);
        } catch (\Exception $e) {
            throw new Office365Exception(self::class . ':modify unable to process order quantity modification', 0, $e);
        }

        $route = $this->getRouter()->get('order_modify');
        $response = $this->getClient()->request($route->method(), $route->url(), $document);
        $body = $response->getBody()->getContents();
        $xml = XmlHelper::loadXML($body);

        if ($xml === null) {
            throw new Office365Exception(self::class . ':modify create xml is null');
        }

        return EntityHelper::deserializeXml(QueuedResponse::class, $body);
    }

    /**
     * @throws Office365Exception
     */
    public function terminate(
        string $orderId,
        \DateTime $desiredTerminateDate,
        bool $terminateAsSoonAsPossible,
        string $partnerReference = ''
    ): QueuedResponse {
        $terminationData = TerminateDataBuilder::build(
            ... func_get_args()
        );

        $terminate = EntityHelper::deserialize(TerminateEntity::class, $terminationData);

        try {
            $document = EntityHelper::serialize($terminate);
        } catch (\Exception $e) {
            throw new Office365Exception(self::class . ':terminate unable to create terminate entity', 0, $e);
        }

        $route = $this->getRouter()->get('terminate_order');
        $response = $this->getClient()->request($route->method(), $route->url(), $document);
        $body = $response->getBody()->getContents();
        $xml = XmlHelper::loadXML($body);

        if ($xml === null) {
            throw new Office365Exception(self::class . ':terminate xml is null');
        }

        return EntityHelper::deserializeXml(QueuedResponse::class, $body);
    }

    /**
     * @throws Office365Exception
     */
    public function summary(
        ?int $customerId,
        ?string $orderState,
        ?string $productGroup,
        ?string $productName,
        ?DateTime $dateActiveFrom,
        ?DateTime $dateActiveTo,
        ?DateTime $dateModifiedFrom,
        ?DateTime $dateModifiedTo,
        ?string $label,
        ?string $attribute,
        ?int $skip,
        ?int $take
    ): OrderSummaryResponse {
        $summaryData = OrderSummaryBuilder::build(
            ... func_get_args()
        );

        $summary = EntityHelper::deserialize(OrderSummary::class, $summaryData);

        try {
            $document = EntityHelper::serialize($summary);
        } catch (\Exception $e) {
            throw new Office365Exception(self::class . ':summary unable to create summary entity', 0, $e);
        }

        $route = $this->getRouter()->get('order_summary');
        $response = $this->getClient()->request($route->method(), $route->url(), $document);
        $body = $response->getBody()->getContents();
        $xml = XmlHelper::loadXML($body);

        if ($xml === null) {
            throw new Office365Exception(self::class . ':summary xml is null');
        }

        return EntityHelper::deserializeXml(OrderSummaryResponse::class, $body);
    }
}
