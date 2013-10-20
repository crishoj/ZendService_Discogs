<?php

namespace ZendTest\Discogs;

use Zend\Http;
//use ZendService\Discogs;

class Condition
{
    const CON_MINT              = "Mint (M)";
    const CON_NEAR_MINT         = "Near Mint (NM or M-)";
    const CON_VERY_GOOD_PLUS    = "Very Good Plus (VG+)";
    const CON_VERY_GOOD         = "Very Good (VG)";
    const CON_GOOD_PLUS         = "Good Plus (G+)";
    const CON_GOOD              = "Good (G)";
    const CON_FAIR              = "Fair (F)";
    const CON_POOR              = "Poor (P)";
    //Sleeve Condition Only
    const CON_GENERIC           = "Generic";
    const CON_NOT_GRADED        = "Not Graded";
    const CON_NO_COVER          = "No Cover";

    public static function getConditions() {
        return ([
            Condition::CON_MINT, Condition::CON_NEAR_MINT, Condition::CON_VERY_GOOD_PLUS, Condition::CON_VERY_GOOD,
            Condition::CON_GOOD_PLUS, Condition::CON_GOOD, Condition::CON_FAIR, Condition::CON_POOR
        ]);
    }

    public static function getSleeveConditions() {
        return Condition::getConditions() + [Condition::CON_GENERIC, Condition::CON_NOT_GRADED, Condition::CON_NO_COVER];
    }
}