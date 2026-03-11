<?php

require_once __DIR__ . '/../../utils/dbTable.php';

class PlayOff extends TournamentType {

    /** @var Match array */
    private $matchList;
    private $teamList;

    private function createMatch($t1, $t2, $r, $n) {
        for ($i = 0; $i < $n; $i++) {
            $match = new Match($t1, $t2);
            $match->setPool($r);
            $match->setRound($r);
            if ($t1 > 0)
                $match->setDependency(array($t1, $t2));
            else
                $match->setDependency(array_merge(array(-1, -1000), range(-500, -508), $this->teamList));
            $this->matchList[] = $match;

            $tmp = $t1;
            $t1 = $t2;
            $t2 = $tmp;
        }
    }

    private function setTeamList() {
        $dbtTeam = new dbTable("teamlist");
        $dbtTeam->newColumn("team", "teamID")->where("tourID='$this->myTourID'");
        $teamResult = mysql_query($dbtTeam->buildSQL(false));

        $this->teamList = array();
        while ($t = mysql_fetch_array($teamResult)) {
            $this->teamList[] = $t['team'];
        }
    }

    public function genMatches() {
        $this->setTeamList();
        $tour = $this->myConnection->getOneElement("tournament", $this->myTourID);
        $num = array(1, 3, 5, 7);

        /* create round 1 and 2 */
        $r1L = 0;
        $r1R = 3;
        $r2L = 1;
        $r2R = 2;
        for ($i = 0; $i < $num[$tour['bestOf']]; $i++) {
            /* Create matches */
            $this->createMatch($this->teamList[$r1L], $this->teamList[$r1R], -500 - $i, 1);
            $this->createMatch($this->teamList[$r2L], $this->teamList[$r2R], -500 - $i, 1);

            /* Swap teams */
            $tmp = $r1L;
            $r1L = $r1R;
            $r1R = $tmp;

            $tmp = $r2L;
            $r2L = $r2R;
            $r2R = $tmp;
        }

        /* Crete finals and 3rd place matches */
        $this->createMatch(-1, -1, -1, $num[$tour['bestOf']]);
        $this->createMatch(-2, -2, -1000, $num[$tour['bestOf']]);
    }

    public function getMatchList() {
        return $this->matchList;
    }

    private function updateTeam($team, $match, $home) {
        $dbtMatches = new dbTable("match");
        $dbtMatches->newColumn("ID")->where("tourID='$this->myTourID'")
                ->where("pool='$match'");
        $matchList = $this->myConnection->query($dbtMatches->buildSQL());

        $tNr = 1;
        if (!$home) {
            $tNr = 2;
        }
        while ($m = mysql_fetch_array($matchList)) {
            $this->myConnection->updateElement("match", $m['ID'], array(
                "team$tNr" . "ID" => $team
            ));
            $tNr = ($tNr == 1) ? 2 : 1;
        }
    }

    public function updateNextRound() {
        /*                   1, 3, 5, 7 */
        $bestOfTable = array(1, 2, 3, 4);

        $this->setTeamList();
        $tour = $this->myConnection->getOneElement("tournament", $this->myTourID);

        $dbtMatches = new dbTable("match");
        $dbtMatches->newColumn("team1", "team1ID")->newColumn("team2", "team2ID")
                ->newColumn("score1")->newColumn("score2")
                ->where("tourID='$this->myTourID'")->where("(pool<=-500 AND pool>=-508)");
//        die($dbtMatches->buildSQL(false));
        $matches = $this->myConnection->query($dbtMatches->buildSQL(false));

        $rank = array();
        foreach ($this->teamList as $team)
            $rank[$team] = 0.0;

        while ($m = mysql_fetch_array($matches)) {
            if ($m['score1'] > $m['score2'])
                $rank[$m['team1']] += 1 + (($m['score1'] - $m['score2']) / 10000);
            else if ($m['score1'] < $m['score2'])
                $rank[$m['team2']] += 1 + (($m['score2'] - $m['score1']) / 10000);
        }

        $success = -1;
        if ($rank[$this->teamList[0]] >= $bestOfTable[$tour['bestOf']]) {
            $this->updateTeam($this->teamList[0], -1, true);
            $this->updateTeam($this->teamList[3], -1000, false);
            $success = true;
        } else if ($rank[$this->teamList[3]] >= $bestOfTable[$tour['bestOf']]) {
            $this->updateTeam($this->teamList[3], -1, true);
            $this->updateTeam($this->teamList[0], -1000, false);
            $success = true;
        }

        if ($rank[$this->teamList[1]] >= $bestOfTable[$tour['bestOf']]) {
            $this->updateTeam($this->teamList[1], -1, false);
            $this->updateTeam($this->teamList[2], -1000, true);
            $success = true;
        } else if ($rank[$this->teamList[2]] >= $bestOfTable[$tour['bestOf']]) {
            $this->updateTeam($this->teamList[2], -1, false);
            $this->updateTeam($this->teamList[1], -1000, true);
            $success = true;
        }

        return $success;
    }

}

?>
