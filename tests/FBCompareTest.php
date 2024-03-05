<?php

namespace RechercheCreneaux\Tests\FBCompare;

use PHPUnit\Framework\TestCase;
use RechercheCreneaux\FBCompare;
use RechercheCreneaux\FBForm;
use League\Period\Sequence as Sequence;
use RechercheCreneaux\FBUtils;

class FBCompareTest extends TestCase
{
    public function testgetNbResultatsAffichés() {
        $stdEnv = unserialize(file_get_contents('tests/stdenv.json'));
        $fbParams = unserialize(file_get_contents('tests/fbparams.json'));
        $fbForm = unserialize(file_get_contents('tests/fbform.json'));

        $reFbForm = new \ReflectionObject($fbForm);

        $fbUsers = $reFbForm->getProperty('fbUsers')->getValue($fbForm);
        $creneauxGenerated = $reFbForm->getProperty('creneauxGenerated')->getValue($fbForm);

        $fbCompareRef = $fbForm->getFbCompare();

        $nbResultatsAffichésRef = $fbCompareRef->getNbResultatsAffichés();
        $creneauxFinauxArrayRef = $fbCompareRef->getArrayCreneauxAffiches();

        $fbParams->nbcreneaux = 1;
        $fbCompareNew = new FBCompare($fbUsers, $creneauxGenerated, $stdEnv->dtz, $fbParams->nbcreneaux);
        $fbForm->setFbCompare($fbCompareNew);

        $fbCompareNew = $fbForm->getFbCompare();

        $nbResultatsAffichésNew = $fbCompareNew->getNbResultatsAffichés();
        $creneauxFinauxArrayNew = $fbCompareNew->getArrayCreneauxAffiches();

        $testRef = json_encode(FBUtils::createSequenceFromArrayPeriods($creneauxFinauxArrayRef));
        $testNew = json_encode(FBUtils::createSequenceFromArrayPeriods($creneauxFinauxArrayNew));

        $this->assertSame($testRef, $testNew);
    }
}

?>