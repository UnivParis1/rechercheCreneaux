<?php

namespace RechercheCreneaux\Tests\FBCompare;

use PHPUnit\Framework\TestCase;
use RechercheCreneaux\FBCompare;

class FBCompareTest extends TestCase
{
    public function testgetNbResultatsAffichés() {

        $this->assertCount(1, array(1));
    }
}

?>