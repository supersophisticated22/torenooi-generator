<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FullCompetition
 *
 * @author Jurrien
 */
class FullCompetition extends HalfCompetition
{
    public function genPoolMatchList($tour)
    {
        $firstHalf = parent::genPoolMatchList($tour);

        foreach ($this->poolList as $pool) {
            $pool->swapTeamLists();
        }

        $secondHalf = parent::genPoolMatchList($tour);

        /** find last round */
        $lastRound = 0;
        foreach ($firstHalf as $match) {
            if ($lastRound < $match->getRound()) {
                $lastRound = $match->getRound();
            }
        }
        $lastRound++;

        /** update round number in second half */
        foreach ($secondHalf as $match) {
            $match->setRound($match->getRound() + $lastRound);
        }

        return array_merge($firstHalf, $secondHalf);
    }
}
