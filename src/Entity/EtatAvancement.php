<?php

namespace App\Entity;

/**
 * Enumération représentant l'état d'avancement d'une inscription à une formation.
 */
enum EtatAvancement: string
{
    case NON_COMMENCEE = 'non_commencee';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';

    /**
     * Retourne le libellé lisible de l'état d'avancement.
     *
     * @return string
     */
    public function libelle(): string
    {
        return match ($this) {
            self::NON_COMMENCEE => 'Non commencée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
        };
    }

    /**
     * Retourne l'état suivant dans la progression, ou null si l'état est terminal.
     *
     * @return self|null
     */
    public function suivant(): ?self
    {
        return match ($this) {
            self::NON_COMMENCEE => self::EN_COURS,
            self::EN_COURS => self::TERMINEE,
            self::TERMINEE => null,
        };
    }

    /**
     * Indique si la transition de cet état vers $etat est autorisée :
     * seul le passage à l'état suivant (ou le maintien de l'état courant) est permis.
     *
     * @param self $etat
     * @return bool
     */
    public function peutTransitionnerVers(self $etat): bool
    {
        return $etat === $this || $etat === $this->suivant();
    }
}
