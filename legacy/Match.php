<?php

/**
 * Description of Match
 *
 * @author jurrien
 */
class Match {

    private $team1, $team2;
    private $round;
    private $type;
    private $pool;
    private $index;
    private $date, $time;
    private $dependend;

    /* ----------------------------------------------------------------------- */

    public function Match($t1, $t2) {
        $this->team1 = $t1;
        $this->team2 = $t2;
        $this->pool = -1;
        $this->index = 0;
        $this->type = "";
        $this->setDate("", "");
    }

    /* ----------------------------------------------------------------------- */

    public function valid() {
        if (($this->team1 == "virt") || ($this->team2 == "virt")) {
            return false;
        } else {
            return true;
        }
    }

    /* ----------------------------------------------------------------------- */

    public function getCode() {
        if($this->type == "third")
            return "third";
        else
            return $this->pool . $this->round . $this->index;
    }

    /* ----------------------------------------------------------------------- */

    /** team */
    public function getTeam1() {
        return $this->team1;
    }

    public function getTeam2() {
        return $this->team2;
    }

    public function getWinner($tourID) {
        $dbMatch = mysql_query("SELECT * FROM `match` WHERE matchCode='" . $this->getCode() . "'
             AND tourID='$tourID'");
        $match = mysql_fetch_array($dbMatch);

        if (!$match['isPlayed'])
            return false;

        if ($match['score1'] > $match['score2'])
            return $match['team1ID'];
        else if ($match['score2'] > $match['score1'])
            return $match['team2ID'];
        else
            return -1;
    }
    
    public function getLoser($tourID) {
        $dbMatch = mysql_query("SELECT * FROM `match` WHERE matchCode='" . $this->getCode() . "'
             AND tourID='$tourID'");
        $match = mysql_fetch_array($dbMatch);

        if (!$match['isPlayed'])
            return false;

        if ($match['score1'] > $match['score2'])
            return $match['team2ID'];
        else if ($match['score2'] > $match['score1'])
            return $match['team1ID'];
        else
            return -1;
    }

    public function setTeams($t1, $t2) {
        if ($t1 != "")
            $this->team1 = $t1;
        if ($t2 != "")
            $this->team2 = $t2;
    }

    /** round */
    public function setRound($round) {
        $this->round = $round;
    }

    public function getRound() {
        return $this->round;
    }

    /** type */
    public function setType($type) {
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

    /** pool */
    public function setPool($pool) {
        $this->pool = $pool;
    }

    public function getPool() {
        return $this->pool;
    }

    /** index */
    public function setIndex($index) {
        $this->index = $index;
    }

    public function getIndex() {
        return $this->index;
    }

    /** date */
    public function setDate($date, $time) {
        $this->date = $date;
        $this->time = $time;
    }

    public function getDate() {
        return array("date" => $this->date, "time" => $this->time);
    }
    
    public function setDependency($arr) {
        if($arr != null)
            $this->dependend = $arr;
    }
    
    public function getDependency() {
        if(!isset($this->dependend)) {
            $dep = array();
            if ($this->team1 >= 0)
                $dep[] = $this->team1;
            if ($this->team2 >= 0)
                $dep[] = $this->team2;
            return $dep;
        } else {
            return $this->dependend;
        }
    }

    public function printMatch() {
        echo "round $this->round: $this->team1 - $this->team2</br>";
    }

}

?>
