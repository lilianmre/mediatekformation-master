<?php

namespace App\Tests\Entity;

use App\Entity\EtatAvancement;
use App\Entity\Inscription;
use PHPUnit\Framework\TestCase;

class InscriptionTest extends TestCase
{
    public function testConstructeurInitialiseDateInscription(): void
    {
        $inscription = new Inscription();

        self::assertInstanceOf(\DateTimeInterface::class, $inscription->getDateInscription());
        self::assertEqualsWithDelta(
            (new \DateTime())->getTimestamp(),
            $inscription->getDateInscription()->getTimestamp(),
            5
        );
    }

    public function testEtatParDefaut(): void
    {
        $inscription = new Inscription();

        self::assertSame(EtatAvancement::NON_COMMENCEE, $inscription->getEtat());
        self::assertNull($inscription->getDateValidation());
    }

    public function testSetEtatTermineeRenseigneDateValidation(): void
    {
        $inscription = new Inscription();

        $inscription->setEtat(EtatAvancement::TERMINEE);

        self::assertSame(EtatAvancement::TERMINEE, $inscription->getEtat());
        self::assertInstanceOf(\DateTimeInterface::class, $inscription->getDateValidation());
    }

    public function testSetEtatNonTermineeEffaceDateValidation(): void
    {
        $inscription = new Inscription();
        $inscription->setEtat(EtatAvancement::TERMINEE);

        $inscription->setEtat(EtatAvancement::EN_COURS);

        self::assertNull($inscription->getDateValidation());
    }
}
