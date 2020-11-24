<?php

namespace Inspirum\XML\Model\Values;

use Inspirum\XML\Definition\Attributes;

class Config
{
    /**
     * Attribute name for \DOMNode::attributes
     *
     * @var string
     */
    private $attributesName;

    /**
     * Attribute name for \DOMNode::nodeValue
     *
     * @var string
     */
    private $valueName;

    /**
     * Attribute name for \DOMNode::childNodes
     *
     * @var string
     */
    private $nodesName;

    /**
     * List of \DOMNode::nodeName to always return as array (if not set to full-type array response)
     *
     * @var array<int,string>
     */
    private $alwaysArray;

    /**
     * Flag to response full-type array response
     *
     * @var bool
     */
    private $fullResponse;

    /**
     * Flag to auto type-cast \DOMNode::nodeValue
     *
     * @var bool
     */
    private $autoCast;

    /**
     * Config constructor
     *
     * @param array<int,string> $alwaysArray
     * @param bool              $fullResponse
     */
    public function __construct(array $alwaysArray = [], bool $fullResponse = false)
    {
        $this->setAttributesName(Attributes::ATTRIBUTES);
        $this->setValueName(Attributes::VALUE);
        $this->setNodesName(Attributes::NODES);
        $this->setAlwaysArray($alwaysArray);
        $this->setFullResponse($fullResponse);
        $this->setAutoCast(false);
    }

    /**
     * @return array<int,string>
     */
    public function getAlwaysArray(): array
    {
        return $this->alwaysArray;
    }

    /**
     * @param array<int,string> $alwaysArray
     *
     * @return void
     */
    public function setAlwaysArray(array $alwaysArray): void
    {
        $this->alwaysArray = $alwaysArray;
    }

    /**
     * @return string
     */
    public function getAttributesName(): string
    {
        return $this->attributesName;
    }

    /**
     * @param string $attributesName
     *
     * @return void
     */
    public function setAttributesName(string $attributesName): void
    {
        $this->attributesName = $attributesName;
    }

    /**
     * @return string
     */
    public function getValueName(): string
    {
        return $this->valueName;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setNodesName(string $value): void
    {
        $this->nodesName = $value;
    }

    /**
     * @return string
     */
    public function getNodesName(): string
    {
        return $this->nodesName;
    }

    /**
     * @param string $valueName
     *
     * @return void
     */
    public function setValueName(string $valueName): void
    {
        $this->valueName = $valueName;
    }

    /**
     * @return bool
     */
    public function isAutoCast(): bool
    {
        return $this->autoCast;
    }

    /**
     * @param bool $autoCast
     *
     * @return void
     */
    public function setAutoCast(bool $autoCast = true): void
    {
        $this->autoCast = $autoCast;
    }

    /**
     * @return bool
     */
    public function isFullResponse(): bool
    {
        return $this->fullResponse;
    }

    /**
     * @param bool $fullResponse
     *
     * @return void
     */
    public function setFullResponse(bool $fullResponse = true): void
    {
        $this->fullResponse = $fullResponse;
    }
}
