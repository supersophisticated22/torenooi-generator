<?php

/**
 * Description of PoolTemplate
 *
 * @author Jurrien
 */
class PoolTemplate
{
    private $teamList;

    private $roundNumber;

    public function __construct()
    {
        $this->roundNumber = 0;
    }

    public function createTamplate(array $listA, array $listB)
    {
        $j = 0;
        $this->teamList = [];
        $num = (count($listA) > count($listB)) ? count($listA) : count($listB);
        for ($i = 0; $i < $num; $i++) {
            if (isset($listA[$i])) {
                $this->teamList[] = $listA[$i];
            }
            if (isset($listB[$i])) {
                $this->teamList[] = $listB[$i];
            }
        }
    }

    public function nextRound()
    {
        $this->roundNumber++;
    }

    public function resetRoundCounter()
    {
        $this->roundNumber = 0;
    }

    public function getMatches()
    {
        if (! isset($this->teamList)) {
            throw new Exception('Team list is not set.');
        }

        if (count($this->teamList) == 5) {
            return $this->createFromTemaplte5();
        } else {
            throw new Exception('There is no template for a pool of '.count($this->teamList).' teams');
        }
    }

    private function createFromTemaplte5()
    {
        $matches = [];
        $matches['t1'] = [];
        $matches['t2'] = [];
        switch ($this->roundNumber) {
            case 0:
                $matches['t1'][] = $this->teamList[1];
                $matches['t1'][] = $this->teamList[3];
                $matches['t2'][] = $this->teamList[0];
                $matches['t2'][] = $this->teamList[2];
                break;
            case 1:
                $matches['t1'][] = $this->teamList[4];
                $matches['t1'][] = $this->teamList[2];
                $matches['t2'][] = $this->teamList[0];
                $matches['t2'][] = $this->teamList[1];
                break;
            case 2:
                $matches['t1'][] = $this->teamList[3];
                $matches['t1'][] = $this->teamList[0];
                $matches['t2'][] = $this->teamList[4];
                $matches['t2'][] = $this->teamList[2];
                break;
            case 3:
                $matches['t1'][] = $this->teamList[4];
                $matches['t1'][] = $this->teamList[0];
                $matches['t2'][] = $this->teamList[1];
                $matches['t2'][] = $this->teamList[3];
                break;
            case 4:
                $matches['t1'][] = $this->teamList[2];
                $matches['t1'][] = $this->teamList[1];
                $matches['t2'][] = $this->teamList[4];
                $matches['t2'][] = $this->teamList[3];
                $this->resetRoundCounter();
                break;
            default: throw new Exception('There is no next round!');
        }

        return $matches;
    }
}
