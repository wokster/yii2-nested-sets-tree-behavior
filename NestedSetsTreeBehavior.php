<?php

namespace wokster\treebehavior;

use yii\base\Behavior;

class NestedSetsTreeBehavior extends Behavior
{
    /**
     * @var string
     */
    public $leftAttribute = 'lft';
    /**
     * @var string
     */
    public $rightAttribute = 'rgt';
    /**
     * @var string
     */
    public $depthAttribute = 'depth';
    /**
     * @var string
     */
    public $labelAttribute = 'name';
    /**
     * @var string
     */
    public $childrenOutAttribute = 'children';
    /**
     * @var string
     */
    public $labelOutAttribute = 'title';
    /**
     * @var string
     */
    public $hasChildrenOutAttribute = 'folder';
    /**
     * @var string
     */
    public $hrefOutAttribute = 'href';
    /**
     * @var null|callable
     */
    public $makeLinkCallable = null;

    public function tree()
    {
        $makeNode = function ($node) {
            $newData = [
                $this->labelOutAttribute => $node[$this->labelAttribute],
            ];
            if (is_callable($makeLink = $this->makeLinkCallable)) {
                $newData += [
                    $this->hrefOutAttribute => $makeLink($node),
                ];
            }
            return array_merge($node, $newData);
        };

        // Trees mapped
        $trees = array();
        $collection = $this->owner->find()->asArray()->all();

        if (count($collection) > 0) {
            foreach ($collection as &$col) $col = $makeNode($col);

            // Node Stack. Used to help building the hierarchy
            $stack = array();

            foreach ($collection as $node) {
                $item = $node;
                $item[$this->childrenOutAttribute] = array();

                // Number of stack items
                $l = count($stack);

                // Check if we're dealing with different levels
                while ($l > 0 && $stack[$l - 1][$this->depthAttribute] >= $item[$this->depthAttribute]) {
                    array_pop($stack);
                    $l--;
                }

                // Stack is empty (we are inspecting the root)
                if ($l == 0) {
                    // Assigning the root node
                    $i = count($trees);
                    $trees[$i] = $item;
                    $stack[] = &$trees[$i];
                } else {
                    // Add node to parent
                    $i = count($stack[$l - 1][$this->childrenOutAttribute]);
                    $stack[$l - 1][$this->hasChildrenOutAttribute] = true;
                    $stack[$l - 1][$this->childrenOutAttribute][$i] = $item;
                    $stack[] = &$stack[$l - 1][$this->childrenOutAttribute][$i];
                }
            }
        }

        return $trees;
    }
}