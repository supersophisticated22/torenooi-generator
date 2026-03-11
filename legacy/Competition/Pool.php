<?php

require __DIR__.'/PoolTemplate.php';

/**
 * Description of Pool
 *
 * @author jurrien
 */
class Pool
{
    private $teamlistA;

    private $teamlistB;

    private $myConnection;

    private $myTourID;

    private $poolNum;

    private $switchMatchOne;

    /** @var PoolTemplate */
    private $template;

    /* ----------------------------------------------------------------------- */
    public function Pool()
    {
        $this->teamlistA = [];
        $this->teamlistB = [];
        $this->switchMatchOne = false;
    }

    /* ----------------------------------------------------------------------- */
    public function reqTeamList()
    {
        $teams = $this->myConnection->getRestTableContant('teamlist', false, null,
            "tourID='$this->myTourID' AND pool='$this->poolNum'", '');

        $teamlist = [];
        while ($t = mysql_fetch_array($teams)) {
            $teamlist[] = $t['teamID'];
        }

        $listNum = 0;
        foreach ($teamlist as $t) {
            if ($listNum == 0) {
                $this->teamlistA[] = $t;
                $listNum = 1;
            } else {
                $this->teamlistB[] = $t;
                $listNum = 0;
            }
        }

        if ((count($this->teamlistA) + count($this->teamlistB)) == 5) {
            $this->template = new PoolTemplate;
            $this->template->createTamplate($this->teamlistA, $this->teamlistB);
        } else {
            $this->template = null;
        }

        if ((count($teamlist) % 2) != 0) {
            $this->teamlistB[] = 'virt';
        }
    }

    /* ----------------------------------------------------------------------- */
    public function rotateList()
    {
        if ($this->template) {
            $this->template->nextRound();

            return;
        }

        $tmp = $this->teamlistB[0];

        if (count($this->teamlistA) == 1) {
            return;
        }

        for ($i = 0; $i < count($this->teamlistB) - 1; $i++) {
            $this->teamlistB[$i] = $this->teamlistB[$i + 1];
        }

        // / Poul fixen
        $this->teamlistB[count($this->teamlistB) - 1]
            = $this->teamlistA[count($this->teamlistA) - 1];

        for ($i = count($this->teamlistA) - 1; $i > 1; $i--) {
            $this->teamlistA[$i] = $this->teamlistA[$i - 1];
        }

        $this->teamlistA[1] = $tmp;
    }

    /* ----------------------------------------------------------------------- */
    public function swapTeamLists()
    {
        $tmp = $this->teamlistA;
        $this->teamlistA = $this->teamlistB;
        $this->teamlistB = $tmp;
        $this->switchMatchOne = false;
    }

    /* ----------------------------------------------------------------------- */
    public function getMatches()
    {
        if ($this->template) {
            return $this->template->getMatches();
        }

        $matches = [];
        $matches['t1'] = $this->teamlistA;
        $matches['t2'] = $this->teamlistB;

        if ($this->switchMatchOne) {
            $tmp = $matches['t1'][0];
            $matches['t1'][0] = $matches['t2'][0];
            $matches['t2'][0] = $tmp;
        }

        $this->switchMatchOne = ($this->switchMatchOne == false);

        return $matches;
    }

    /* ----------------------------------------------------------------------- */
    public function getNumMatchPerRound()
    {
        return count($this->teamlistA);
    }

    /* ----------------------------------------------------------------------- */
    public function getTeamList()
    {
        return array_merge($this->teamlistA, $this->teamlistB);
    }

    /* ----------------------------------------------------------------------- */
    public function getPoolNum()
    {
        return $this->poolNum;
    }

    /* ----------------------------------------------------------------------- */
    public function withConnection($conn)
    {
        $this->myConnection = $conn;

        return $this;
    }

    public function withTourID($id)
    {
        $this->myTourID = $id;

        return $this;
    }

    public function withPoolNum($num)
    {
        $this->poolNum = $num;

        return $this;
    }
}
