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
        $testId = getenv("FBCompareTestId");
        $path = "tests/FBCompareTest-$testId";

        $stdEnv = unserialize(file_get_contents("$path/stdenv.json"));
        $fbParams = unserialize(file_get_contents("$path/fbparams.json"));
        $fbForm = unserialize(file_get_contents("$path/fbform.json"));

        $reFbForm = new \ReflectionObject($fbForm);

        $fbUsers = $reFbForm->getProperty('fbUsers')->getValue($fbForm);
        $creneauxGenerated = $reFbForm->getProperty('creneauxGenerated')->getValue($fbForm);

        $fbCompareRef = $fbForm->getFbCompare();

        $nbResultatsAffichésRef = $fbCompareRef->getNbResultatsAffichés();
        $creneauxFinauxArrayRef = $fbCompareRef->getArrayCreneauxAffiches();
        if (count($creneauxFinauxArrayRef) == 0) throw new \Exception('ref count $creneauxFinauxArrayRef == 0. Test foireux à la base');

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