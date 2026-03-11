<?php

/**
 * Description of HalfCompetition
 *
 * @author jurrien
 */
class HalfCompetition extends TournamentType {

    /** @var Pool array */
    protected $poolList;

    /** @var TreeNode */
    private $finalTree;
    private $myMatchList;

    /* ----------------------------------------------------------------------- */

    public function HalfCompetition() {
        $this->TournamentType();
        $this->poolList = array();
        $this->finalTree = array();
    }

    /* ----------------------------------------------------------------------- */

    public function genMatches() {
        $this->genPools();
        $this->finalTree = $this->genFinals();
        $this->myMatchList = $this->genMatcheList();
    }

    /* ----------------------------------------------------------------------- */

    public function getMatchList() {
        return $this->myMatchList;
    }

    /* ----------------------------------------------------------------------- */

    public function updateNextRound() {
        /** check if the complete poule round is played */
        $poolRound = $this->myConnection->getRestTableContant("match", false, null, "tourID='$this->myTourID' AND isPlayed='0' AND pool>=0", "");
        if (mysql_num_rows($poolRound) > 0) {
            /** thus there are still matches to play */
            return false;
        }

        $this->genMatches();
        $fr = $this->finalTree->getLastRound();
        $fr = $this->finalTree->getRound($fr[0]->getMyDepth() - 1);
        //var_dump($fr[0]);
        $fround = $fr[0]->getMatch()->getRound();

//        die($fr[0]->getMatch()->getRound() . "</br>");
        $i = -8;
        while (mysql_num_rows(mysql_query("SELECT pool AS round FROM
                `match` WHERE tourID='$this->myTourID' AND pool=$i")) == 0) {
            $i++;
        }
        $round = $i * -1;

        $rankPerPool = array();
        for ($i = 0; $i < count($this->poolList); $i++) {
            $rankPerPool[$i] = $this->getRank($i);

            $rank = $rankPerPool[$i];
//            $r = mysql_fetch_array(mysql_query("SELECT min(pool) AS round" . 
//                " FROM `match` WHERE tourID='$this->myTourID'"));
//            $round = $r['round'] * -1;
            $matchCode = "-$round$fround";
//            echo "$round - $matchCode$i</br>";
//            die("</br></br>$matchCode");
//            die("</br></br>SELECT min(pool) AS round" . 
//                " FROM `match` WHERE tourID='$this->myTourID'");

            $match1 = $this->myConnection->getRestTableContant("match", false, null, "tourID='$this->myTourID' AND matchCode='$matchCode" . $i . "'", "");
            $firstMatch = mysql_fetch_array($match1);

            $match2 = $this->myConnection->getRestTableContant("match", false, null, "tourID='$this->myTourID' AND matchCode='$matchCode" . (count($this->poolList) - $i - 1) . "'", "");
            $secondMatch = mysql_fetch_array($match2);

            if (!$firstMatch['isChanged1']) {
//                echo "1not changed </br>";
                $this->myConnection->updateElement("match", $firstMatch['ID'], array("team1ID" => $rank[0]));
            }
            if (!$secondMatch['isChanged2']) {
//                echo "2not changed </br>";
                $this->myConnection->updateElement("match", $secondMatch['ID'], array("team2ID" => $rank[1]));
            }
        }

        //echo $this->finalTree->getTreeDepth() . " = depth </br>";
        $succes = $this->finalTree->updateNextRound($this->myTourID);
        if (mysql_num_rows(mysql_query("SELECT pool AS round FROM
                `match` WHERE tourID='$this->myTourID' AND pool<0 AND `isPlayed`=0")) <= 3) {
            $final = mysql_fetch_array(mysql_query("SELECT withFinal FROM `tournament` WHERE ID='$this->myTourID'"));
            if ($final['withFinal'] == "2") {
                $m = mysql_fetch_array(mysql_query("SELECT ID FROM `match` WHERE tourID='$this->myTourID' AND pool=-1000"));
                $matches = $this->finalTree->getRound(1);
                if($matches[0]->getMatch() != NULL) {
                    $loser1 = $matches[0]->getMatch()->getLoser($this->myTourID);
                    $loser2 = $matches[1]->getMatch()->getLoser($this->myTourID);
                } else {
                    $loser1 = $rankPerPool[0][2];
                    $loser2 = $rankPerPool[0][3];
                }
                $this->myConnection->updateElement("match", $m['ID'], array("team1ID" => $loser1, "team2ID" => $loser2));
            }
        }

        if ($succes)
            return true;
        else
            return -1;
    }

    /* ----------------------------------------------------------------------- */

    private function getRank($pool) {
        /** get rank */
        $matchList = $this->myConnection->getRestTableContant("match", false, null, "tourID='$this->myTourID' AND pool=$pool", "");
        $tour = $this->myConnection->getOneElement("tournament", $this->myTourID);
        $sport = $this->myConnection->getOneElement("sport", $tour['sportID']);

        $pWin = $sport['win'];
        $pDraw = $sport['draw'];
        $pLost = $sport['lost'];

        $teamList = $this->poolList[$pool]->getTeamList();

        $pointList = array();
        for ($i = 0; $i < count($teamList); $i++)
            $pointList[$i] = 0;

        while ($match = mysql_fetch_array($matchList)) {
            if (!$match['isPlayed'])
                continue;

            if ($match['score1'] > $match['score2']) {
                $pointList[array_search($match['team1ID'], $teamList)] += $pWin;
                $pointList[array_search($match['team2ID'], $teamList)] += $pLost;
            } else if ($match['score2'] > $match['score1']) {
                $pointList[array_search($match['team2ID'], $teamList)] += $pWin;
                $pointList[array_search($match['team1ID'], $teamList)] += $pLost;
            } else {
                $pointList[array_search($match['team1ID'], $teamList)] += $pDraw;
                $pointList[array_search($match['team2ID'], $teamList)] += $pDraw;
            }
            
            $pointList[array_search($match['team1ID'], $teamList)] += (($match['score1'] * 2) - $match['score2']) / 20000;
            $pointList[array_search($match['team2ID'], $teamList)] += (($match['score2'] * 2) - $match['score1']) / 20000;
        }

        $list = array();
        for ($i = 0; $i < count($teamList); $i++) {
            $list[$teamList[$i]] = $pointList[$i];
        }
        arsort($list);

        $rank = array();
        foreach ($list as $team => $points) {
            $rank[] = $team;
        }

        return $rank;
    }

    /* ----------------------------------------------------------------------- */

    public function genPools() {
        $tour = $this->myConnection->getOneElement("tournament", $this->myTourID);

        for ($i = 0; $i < $tour['numPool']; $i++) {
            $this->poolList[$i] = new Pool();
            $this->poolList[$i]->withConnection($this->myConnection)
                    ->withTourID($this->myTourID)
                    ->withPoolNum($i)
                    ->reqTeamList();
        }
    }

    /* ----------------------------------------------------------------------- */

    private function genMatcheList() {
        $tour = $this->myConnection->getOneElement("tournament", $this->myTourID);

        $pList = $this->genPoolMatchList($tour);

        /** find last round */
        $lRound = 0;
        foreach ($pList as $match) {
            if ($lRound < $match->getRound()) {
                $lRound = $match->getRound();
            }
        }

        if ($tour['withFinal'] >= 1) {
            $fList = $this->genFinalMatchList($lRound);
            return array_merge($pList, $fList);
        } else {
            return $pList;
        }
    }

    /* ----------------------------------------------------------------------- */

    protected function genPoolMatchList($tour) {
        $switchFirstMatch = false;

        /** calculate per pool the number of matches */
        $numMatches = array();
        foreach ($this->poolList as $num => $pool) {
            $numPerPool = $pool->getNumMatchPerRound();
            $numMatches[$num] = ((2 * $numPerPool) - 1);
        }

        /** generate the matches */
        $list = array();

        foreach ($this->poolList as $num => $pool) {
            for ($i = 0; $i < $numMatches[$num]; $i++) {
                $matchlist = $pool->getMatches($switchFirstMatch);
//                if($tour['type'] == 0)
//                    $switchFirstMatch = ($switchFirstMatch == false);
                foreach ($matchlist['t1'] as $t => $team) {
                    $match = new Match($matchlist['t1'][$t], $matchlist['t2'][$t]);
//                    echo $matchlist['t1'][$t] . " - " . $matchlist['t2'][$t] . "</br>";
                    $match->setRound($i);
                    $match->setType("pool");
                    $match->setPool($pool->getPoolNum());
                    $match->setIndex($t);
                    if ($match->valid()) {
                        $list[] = $match;
                    }
                }
                $pool->rotateList();
            }
        }

        $mpp = array();
        foreach ($list as $match) {
            if (!isset($mpp[$match->getPool()]))
                $mpp[$match->getPool()] = array();
            $mpp[$match->getPool()][] = $match;
        }

        $mC = 0;
        $pC = 0;
        $mmlist = array();
        while ($mC < count($list)) {
            foreach ($mpp as $pool) {
                if (isset($pool[$pC])) {
                    $mmlist[] = $pool[$pC];
                    $mC++;
                }
            }
            $pC++;
        }

        return $mmlist;
    }

    /* ----------------------------------------------------------------------- */

    private function genFinalMatchList($round) {
        $currRound = $round + 1;
        $td = $this->finalTree->getTreeDepth();
        $list = array();

        for ($i = $td - 1; $i >= 0; $i--) {
            $treeRound = $this->finalTree->getRound($i);
            $index = 0;
            foreach ($treeRound as $match) {
//                echo "make match with depth: " . $match->getMyDepth() . "and index" . $match->getMyIndex() . "</br>";
                $match->setMatch(new Match(0, 0));
                $match->getMatch()->setRound($currRound);
                $match->getMatch()->setType("final");
                $match->getMatch()->setPool(-1 * ($i + 1));
                $match->getMatch()->setIndex($index);
                $list[] = $match->getMatch();
                $index++;
            }
            $currRound++;
        }

        $firstFinal = $this->finalTree->getRound($td - 1);

        for ($i = 0; $i < count($this->poolList); $i++) {
            $j = count($this->poolList) - $i - 1;
            $dep = $match->getMatch()->getRound() . $i . $j;

            $firstFinal[$i]->getMatch()->setDependency(array($dep));
        }

        $this->finalTree->setMatchDependency();

        return $list;
    }

    /* ----------------------------------------------------------------------- */

    private function genFinals() {
        $treeDepth = (log(count($this->poolList)) / log(2)) + 1;

        $treeRoot = new TreeNode($treeDepth, 0, 0, 0);

        return $treeRoot;
    }

}

?>
