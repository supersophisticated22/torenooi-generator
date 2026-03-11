<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DKOTreeNode
 *
 * @author Jurrien
 */
class DKOTreeNode extends TreeNode
{
    public function withNode1($node)
    {
        $this->node1 = $node;

        return $this;
    }

    public function withNode2($node)
    {
        $this->node2 = $node;

        return $this;
    }
}
