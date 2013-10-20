<?php

namespace ZendTest\Discogs;

use Zend\Http;
use Zend\Json\JsonSerializanble as JsonSerializanble;
use ZendService\Discogs;
use ZendService\Discogs\Response as DiscogsResponse;
require_once 'tests/TestConfiguration.php';
require_once 'library/ZendService/Discogs/Condition.php';
//class_alias('Zend\Json\JsonSerializable', 'JsonSerializable');


class Release
{
    const STATUS_DRAFT = "Draft";
    const STATUS_SALE = "For Sale";

    public $id;
    public $condition; // String, Required
    public $price; // Float, Required
    public $sleeveCondition; // String, Optional
    public $comments; // String, Optional
    public $status = STATUS_SALE; // String, Optional


    public function __construct ($id, $condition, $price, $sleeveCondition = null, $comment = null, $status = null) {
        if (is_string($condition) && in_array($condition, Condition::getConditions()))
            $this->condition = $condition;

        if (is_float($price))
            $this->price = $price;
        elseif (is_integer($price))
            $this->price = (double)$price;
        if (is_string($sleeveCondition) && in_array($sleeveCondition, Condition::getSleeveConditions()))
            $this->sleeveCondition = $sleeveCondition;

        $this->id = $id;
        $this->comments = $comment;
        $this->status = $status;
    }

    public function jsonEncodeRelease() {
        return json_encode(get_object_vars($this));
    }
}