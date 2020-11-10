<?php

namespace Inspirum\XML\Model\Values;

use Inspirum\XML\Definition\Config as DefConfig;

class Config
{
    private $alwaysArray;
    private $attributePrefix;
    private $textContent;
    private $autoCast;
    private $full;

    public function __construct(
        array $alwaysArray = [],
        string $attributePrefix = null,
        string $textContent = null
    ) {
        $this->setAlwaysArray($alwaysArray);
        $this->setAttributePrefix($attributePrefix !== null ? $attributePrefix : DefConfig::ATTRIBUTES);
        $this->setTextContent($textContent !== null ? $textContent : DefConfig::VALUE);
        $this->setAutoCast(false);
        $this->setFull(false);
    }

    /**
     * @return array
     */
    public function getAlwaysArray(): array
    {
        return $this->alwaysArray;
    }

    /**
     * @param array $alwaysArray
     */
    public function setAlwaysArray(array $alwaysArray): void
    {
        $this->alwaysArray = $alwaysArray;
    }

    /**
     * @return mixed|string
     */
    public function getAttributePrefix()
    {
        return $this->attributePrefix;
    }

    /**
     * @param mixed|string $attributePrefix
     */
    public function setAttributePrefix(string $attributePrefix): void
    {
        $this->attributePrefix = $attributePrefix;
    }

    /**
     * @return mixed|string
     */
    public function getTextContent()
    {
        return $this->textContent;
    }

    /**
     * @param mixed|string $textContent
     */
    public function setTextContent(string $textContent): void
    {
        $this->textContent = $textContent;
    }

    /**
     * @return mixed
     */
    public function getAutoCast()
    {
        return $this->autoCast;
    }

    /**
     * @param mixed $autoCast
     */
    public function setAutoCast($autoCast): void
    {
        $this->autoCast = $autoCast;
    }

    /**
     * @return mixed
     */
    public function getFull()
    {
        return $this->full;
    }

    /**
     * @param mixed $full
     */
    public function setFull(bool $full = true): void
    {
        $this->full = $full;
    }
}
