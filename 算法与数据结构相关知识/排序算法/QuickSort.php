<?php 

namespace Algorithm;

/**
 * 快速排序
 * 核心思想：先从数列中随机取出一个基准值，将比这个数小的放入到左子数列，
 * 比这个大的放入到右子数列中，按上述步骤重复操作左右两个子数列，直到每
 * 个区间只有一个数。
 * 
 * 平均时间复杂度：O(N*logN)
 */
class QuickSort 
{
    public function index($arr)
    {
        $len = count($arr);
        if ($len <= 1) {
            return $arr;
        }
        
        $left = [];
        $right = [];
        $baseNum = $arr[0];

        for ($i=1; $i < $len ; $i++) { 
            if ($arr[$i] < $baseNum) {
                $left[] = $arr[$i];
            } else {
                $right[] = $arr[$i];
            }
        }
        $left = $this->index($left);
        $right = $this->index($right);

        return array_merge($left, [$baseNum], $right);
    }
}

$a = [3, 5, 2, 7, 9, 16];
$res = (new QuickSort())->index($a);

print_r($res);