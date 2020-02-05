<?php

namespace Algorithm\BinaryTree;

class InorderByRecursive
{
    public function traversal(TreeNode $treeNode, $level = 0)
    {
        if ($treeNode->value == null)
        {
            return;
        }

        if ($treeNode->leftChildren)
        {
            $this->traversal($treeNode->leftChildren, $level+1);
        }
        
        echo str_repeat('---', $level) . $treeNode->value . PHP_EOL;

        if ($treeNode->rightChildren)
        {
            $this->traversal($treeNode->rightChildren, $level+1);
        }
    }
}