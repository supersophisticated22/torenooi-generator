<?php

/* * *
 * This class is part of the tree builder, depending
 * on the place of the node it is going to spone one
 * or two other nodes which are 'attached' to this
 * one.
 *
 * @author jurrien
 */

class TreeNode
{
    private $totalTreeDepth;

    private $myDepth;

    private $levelIndex;

    protected $node1;

    protected $node2;

    private $myMatch;

    /** ----------------------------------------------------------------------- */
    public function TreeNode($treeDepth, $currDepth, $index, $additional)
    {
        $this->totalTreeDepth = $treeDepth;
        $this->myDepth = $currDepth;
        $this->levelIndex = $index;
        $this->myMatch = null;

        if ($currDepth < $treeDepth) {
            $indexOffs = $this->levelIndex * 2;
            $this->node1 = new TreeNode($treeDepth, $currDepth + 1,
                $indexOffs, $additional);
            $this->node2 = new TreeNode($treeDepth, $currDepth + 1,
                $indexOffs + 1, $additional);
        } else {
            $indexOffs = $this->levelIndex * 2;
            if (($indexOffs < ($additional)) && ($currDepth == $treeDepth)) {
                $this->node1 = new TreeNode($treeDepth, $currDepth + 1,
                    $indexOffs, $additional);
            }
            if (($indexOffs + 1) < $additional) {
                $this->node2 = new TreeNode($treeDepth, $currDepth + 1,
                    $indexOffs + 1, $additional);
            }
        }
    }

    /** ----------------------------------------------------------------------- */
    public function updateNextRound($tourID)
    {
        $ret_val1 = $ret_val2 = true;
        $team1 = $team2 = 0;
        $update_sql1 = $update_sql2 = '';

        $match = $this->getDBMatch($tourID);
        if ($match && $match['isPlayed']) {
            return true;
        }

        if (isset($this->node1)) {
            $ret_val1 = $this->node1->updateNextRound($tourID);
            if ($this->node1->getMatch() != null) {
                $team1 = $this->node1->getMatch()->getWinner($tourID);
                if ($team1 == -1) {
                    return false;
                } elseif ($team1 != false) {
                    $update_sql1 = "team1ID='$team1'";
                }
            }
        }

        if (isset($this->node2)) {
            $ret_val2 = $this->node2->updateNextRound($tourID);
            if ($this->node2->getMatch() != null) {
                $team2 = $this->node2->getMatch()->getWinner($tourID);
                if ($team2 == -1) {
                    return false;
                } elseif ($team2 != false) {
                    $update_sql2 = "team2ID='$team2'";
                }
            }
        }

        if (($update_sql1 != '') && ($update_sql2 != '')) {
            $update_sql1 = $update_sql1.', ';
        }

        if (($update_sql1 != '') || ($update_sql1 != '')) {
            mysql_query("UPDATE `match` SET $update_sql1 $update_sql2
                 WHERE matchCode='".$this->myMatch->getCode()."'
                        AND tourID='$tourID'");
        }

        return $ret_val1 & $ret_val2;
    }

    /** ----------------------------------------------------------------------- */
    public function setMatch($match)
    {
        if ($match->getTeam1() == '') {
            $match->setTeams(-1 * ($this->myDepth + 1), $match->getTeam2());
        }

        if ($match->getTeam2() == '') {
            $match->setTeams($match->getTeam1(), -1 * ($this->myDepth + 1));
        }

        $this->myMatch = $match;
    }

    /** -----------------------------------------------------------------------
     *
     * @return Match
     */
    public function getMatch()
    {
        if (isset($this->myMatch)) {
            return $this->myMatch;
        } else {
            return null;
        }
    }

    /** ----------------------------------------------------------------------- */
    public function getDBMatch($tourID)
    {
        if (! isset($this->myMatch)) {
            return null;
        }

        $con = new Connection;
        $match = $con->getRestTableContant('match', false, null, "tourID='$tourID' AND matchcode='".$this->myMatch->getCode()."'", '');

        return mysql_fetch_array($match);
    }

    /** ----------------------------------------------------------------------- */
    public function setMatchDependency()
    {
        $dep1 = [];
        $dep2 = [];

        if (isset($this->node1)) {
            $dep1 = $this->node1->setMatchDependency();
        } else {
            if (isset($this->myMatch)) {
                $dep1 = $this->myMatch->getDependency();
            }
        }

        if (isset($this->node2)) {
            $dep2 = $this->node2->setMatchDependency();
        } else {
            if (isset($this->myMatch)) {
                $dep2 = $this->myMatch->getDependency();
            }
        }
        if ($dep2 === false) {
            $dep2 = $this->myMatch->getDependency();
        }

        if (isset($this->myMatch) && (array_merge($dep1, $dep2) != [])) {
            //            echo "set: " . $this->myMatch->printMatch();
            //            var_dump($this->myMatch->getDependency());
            $this->myMatch->setDependency(array_merge($dep1, $dep2));
        }

        if (isset($this->myMatch)) {
            return $this->myMatch->getDependency();
        } else {
            return [];
        }
    }

    /** ----------------------------------------------------------------------- */
    public function getTreeDepth()
    {
        return $this->totalTreeDepth;
    }

    /** ----------------------------------------------------------------------- */
    public function getMyDepth()
    {
        return $this->myDepth;
    }

    /** ----------------------------------------------------------------------- */
    public function getMyIndex()
    {
        return $this->levelIndex;
    }

    /** -----------------------------------------------------------------------
     *
     * @param  int  $round
     * @return TreeNode array
     */
    public function getRound($round)
    {
        if ($this->myDepth >= $round) {
            return [$this];
        } else {
            if (isset($this->node2)) {
                return array_merge($this->node1->getRound($round), $this->node2->getRound($round));
            } elseif (isset($this->node1)) {
                return $this->node1->getRound($round);
            } else {
                return [];
            }
        }
    }

    /** ----------------------------------------------------------------------- */
    public function getLastRound()
    {
        if (isset($this->node1) && isset($this->node2)) {
            return array_merge($this->node1->getLastRound(), $this->node2->getLastRound());
        } elseif (isset($this->node1)) {
            return array_merge($this->node1->getLastRound(), [$this]);
        } else {
            return [$this];
        }
    }
}
