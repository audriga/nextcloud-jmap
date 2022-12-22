<?php

namespace OCA\JMAP\JMAP\Contact;

use JsonSerializable;

class ContactInformation implements JsonSerializable
{
    private $type;
    private $label;
    private $value;
    private $isDefault;


    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        // TODO: Possibly do value checking of the parameter, since only enum values are allowed for this property
        $this->type = $type;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getIsDefault()
    {
        return $this->isDefault;
    }

    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    }

    public function jsonSerialize()
    {
        return (object)[
            "type" => $this->getType(),
            "label" => $this->getLabel(),
            "value" => $this->getValue(),
            "isDefault" => $this->getIsDefault()
            ];
    }
}
