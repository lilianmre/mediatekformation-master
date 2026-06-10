<?php

namespace App\Tests\Entity;

use App\Entity\EtatAvancement;
use PHPUnit\Framework\TestCase;

class EtatAvancementTest extends TestCase
{
    public function testLibelle(): void
    {
        self::assertSame('Non commencée', EtatAvancement::NON_COMMENCEE->libelle());
        self::assertSame('En cours', EtatAvancement::EN_COURS->libelle());
        self::assertSame('Terminée', EtatAvancement::TERMINEE->libelle());
    }

    public function testSuivant(): void
    {
        self::assertSame(EtatAvancement::EN_COURS, EtatAvancement::NON_COMMENCEE->suivant());
        self::assertSame(EtatAvancement::TERMINEE, EtatAvancement::EN_COURS->suivant());
        self::assertNull(EtatAvancement::TERMINEE->suivant());
    }

    public function testPeutTransitionnerVersEtatCourant(): void
    {
        self::assertTrue(EtatAvancement::NON_COMMENCEE->peutTransitionnerVers(EtatAvancement::NON_COMMENCEE));
        self::assertTrue(EtatAvancement::EN_COURS->peutTransitionnerVers(EtatAvancement::EN_COURS));
        self::assertTrue(EtatAvancement::TERMINEE->peutTransitionnerVers(EtatAvancement::TERMINEE));
    }

    public function testPeutTransitionnerVersEtatSuivant(): void
    {
        self::assertTrue(EtatAvancement::NON_COMMENCEE->peutTransitionnerVers(EtatAvancement::EN_COURS));
        self::assertTrue(EtatAvancement::EN_COURS->peutTransitionnerVers(EtatAvancement::TERMINEE));
    }

    public function testNePeutPasSauterUneEtape(): void
    {
        self::assertFalse(EtatAvancement::NON_COMMENCEE->peutTransitionnerVers(EtatAvancement::TERMINEE));
    }

    public function testNePeutPasRevenirEnArriere(): void
    {
        self::assertFalse(EtatAvancement::EN_COURS->peutTransitionnerVers(EtatAvancement::NON_COMMENCEE));
        self::assertFalse(EtatAvancement::TERMINEE->peutTransitionnerVers(EtatAvancement::EN_COURS));
        self::assertFalse(EtatAvancement::TERMINEE->peutTransitionnerVers(EtatAvancement::NON_COMMENCEE));
    }
}
