<?php declare(strict_types = 1);

namespace SandwaveIo\Office365\Helper;

use DOMException;
use SandwaveIo\Office365\Entity\EntityInterface;
use JMS\Serializer\SerializerBuilder;
use SandwaveIo\Office365\Enum\RequestAction;
use LaLit\Array2XML;
use SandwaveIo\Office365\Transformer\ClassTransformer;

final class EntityHelper
{
    public static function serialize(EntityInterface $entity): string
    {
        $serializer = SerializerBuilder::create()->build();

        return $serializer->serialize($entity, 'xml');
    }

    /**
     * @param array<mixed> $data
     * @return mixed
     */
    public static function deserialize(string $class, array $data)
    {
        $xml = self::toXML($data, RequestAction::NEW_CUSTOMER_REQUEST_V1);

        $serializer = SerializerBuilder::create()
            ->addMetadataDir('./config/serializer')
            ->build();

        return $serializer->deserialize($xml, $class, 'xml');
    }

    /**
     * @return false|string
     * @throws DOMException
     */
    public static function prepare(string $action, EntityInterface $entity)
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        $root = $doc->createElement($action);
        $doc->appendChild($root);

        $docCustomer = new \DOMDocument();
        $docCustomer->loadXML(self::serialize($entity), LIBXML_NOWARNING);

        $xpath = new \DOMXPath($docCustomer);
        $properties = $xpath->query('//result/*');

        if (!$properties instanceof \DOMNodeList) {
            return false;
        }

        foreach ($properties as $property) {
            $node = $doc->importNode($property, true);
            $doc->documentElement->appendChild($node);
        }

        return $doc->saveXML();
    }

    public static function toXML(array $data, string $action): string
    {
        return (Array2XML::createXML($action, $data))->saveXML();
    }

    public static function createFromXML(string $xml): EntityInterface
    {
        $xml = simplexml_load_string($xml);

        $className = ClassTransformer::transform($xml->getName());

        return EntityHelper::deserialize($className,  (array) $xml);
    }
}
