<h1>二叉树建立</h1>
<h2>节点结构</h2>
<pre>
class TreeNode {
    public $value;
    public $leftChildren;
    public $rightChildren;
    __construct($value) {
        $this->value = $value;
    }
}
</pre>

<h1>二叉树遍历</h1>
遍历是对树的一种最基本的运算，而二叉树的遍历就是按照一定的规则和顺序走遍二叉树所有的节点，使每个节点都被访问一次，
而且只被访问一次。

<h2>1:先序遍历(NLR)</h2>
先序遍历是指首先访问根，在先序遍历左子树，最后先序遍历右子树。(中、左、右的顺序)。
<h3>递归方式</h3>
1.判断当前节点的值是否为空。如果为空，直接返回。<br>
2.打印当前节点的值。<br>
3.循环调用本方法，传入的值分别为当前节点的左、右孩子节点。

<h3>非递归方式</h3>
1.首先申请一个新的栈，记为stack。<br>
2.然后将头节点head压入stack中。<br>
3.每次从stack中弹出栈顶节点，记为curNode，然后打印curNode节点的值。如果curNode右孩子不为空的话，将curNode的右孩子先压入stack中。
最后如果curNode的左孩子不为空的话，将curNode的左孩子压入stack中。<br>
4.不断重复步骤3，直到stack为空，全部过程结束。<br>


<h2>2:中序遍历(LNR)</h2>
中序遍历是指首先中序遍历左子树，再访问根，最后中序遍历右子树。(左、中、右的顺序)。
<h3>递归方式</h3>
1.判断当前节点的值是否为空，如果为空，直接返回。<br>
2.调用本身，参数为本节点的左孩子节点。<br>
3.打印当前节点的值。<br>
4.调用本身，参数为本节点的右孩子节点。<br>

<h3>非递归方式</h3>
1.申请一个新的栈，记为stack，申请一个变量curNode，初始时令curNode等于头节点。<br>
2.先把curNode节点压入栈中，对以curNode节点为头的整棵子树来说，依次把整棵树的左边界压入栈中，即不断地令curNode=curNode->leftChildren，
然后重复步骤2。<br>
3.不断重复步骤2，直到发现curNode为空，此时从stack中弹出一个节点，记为node。打印node的值，并让curNode=node->rightChildren，然后继续重复步骤2。<br>
4.当stack为空并且curNode为空时，整个过程结束。


<h2>3:后序遍历(LRN)</h2>
后序遍历是指首先后序遍历左子树，再后序遍历右子树，最后访问根。(左、右、中的顺序)。
<h3>递归方式</h3>
1.判断当前节点的值是否为空。如果为空，直接返回。<br>
2.循环调用本方法，传入的值分别为当前节点的左、右孩子节点。
3.打印当前节点的值。<br>

<h3>非递归方式</h3>
<h4>方式一：使用两个栈</h4>
1.申请一个栈，记为s1，然后将头节点压入s1中。<br>
2.从s1中弹出的节点记为cur，然后先把cur的左孩子压入s1中，再cur的右孩子压入s1中。<br>
3.在整个过程中，每一个从s1中弹出的节点都放入第二个栈s2中。<br>
4.不断重复步骤2和步骤3，直到s1为空，过程停止。<br>
5.从s2中依次弹出节点并打印，打印的顺序就是后序遍历的顺序了。

<h4>方式二：使用一个栈</h4>
1.申请一个栈，记为stack，将头节点压入stack，同时设置两个变量h和c。在整个流程中，h代表最近一个弹出并打印的的节点，
c代表当前stack的栈顶节点，初始时令h为头节点，c为空。<br>
2.每次令c等于当前stack的栈顶节点，但是不从stack中弹出节点，此时分为以下三种情况：<br>
(1)如果c的左孩子不为空，并且h不等于c的左孩子，也不等于c的右孩子，则把c的左孩子压入stack中。<br>
(2)如果情况1不成立，并且c的右孩子不为空，并且h不等于c的右孩子，则把c的右孩子压入stack中。<br>
(3)如果情况1和情况2都不成立，那么从stack中弹出c并打印，然后令h等于c。<br>
3.一直重复步骤2，直到stack为空，过程停止。


<h2>4.层级遍历</h2>
1.申请一个队列，记为q1，然后将头节点放入q1中。同时设置两个变量lineNode和nlineNode，在整个流程中，lineNode表示正在打印的当前行的最右节点，
nlineNode表示下一行的最右节点，初始时令lineNode和nlineNode都为头节点<br>
2.从q1中弹出的节点记为curNode，打印curNode，并将curNode的左、右孩子节点依次放入q1中。同时令nlineNode依次等于curNode的左右孩子节点。<br>
3.判断curNode是否等于lineNode，如果等于，则令lineNode等于nlineNode。
4.重复步骤3和步骤4，直到q1为空，过程停止。<br>

<h1>特殊二叉树</h1>
<h2>1.平衡二叉树(AVL树)</h2>
1.空树是平衡二叉树
2.如果一棵树不为空，并且其中所有的子树都满足各自的左子树与右子树的高度差都不超过1.<br/>

<h3>判断一棵树是否是平衡二叉树</h3>
1.判断左子树是否是平衡二叉树，如果左子树为false，则直接返回false。<br />
2.记录左子树最深到哪一层，记为LH。<br/>
3.判断右子树是否为平衡二叉树，如果右子树不是平衡二叉树，返回false。<br />
4.记录右子树最深到哪一层，记为RH。<br />
5.如果左、右子树都是平衡二叉树，则比较LH和RH的绝对值差是否大于1。如果大于1，则直接返回false。否则返回LH和RH中较大的一个。<br>

<h2>2.搜索二叉树</h2>
1.每棵子树头节点的值都比各自左子树上的所有节点值要大，也都比各自右子树上的所有节点值要小。<br />
2.搜索二叉树按照中序遍历得到的序列一定是从小到大排列的。<br />
3.红黑树、平衡搜索二叉树(AVL树)等，其实都是搜索二叉树的不同体现。

<h3>判断一棵树是否是搜索二叉树</h3>
中序遍历过程中，判断当前节点值是否大于上一个节点值。

<h2>3.完全二叉树</h2>
除最后一层外，其他每一层的节点数都是满的，最后一层如果也满了，
则是一个满二叉树，也是完全二叉树，最后一层如果不满，但缺少的节点全部集中在右边，那也是一棵完全二叉树。<br />

满二叉树的层数记为L，节点数记为N，则N等于2的L次幂减1，L等于log以2为底n+1。
<h3>判断一棵树是否是完全二叉树</h3>
1.采用按层遍历二叉树的方式，从每层的左边向右边依次遍历所有的节点。<br />
2.如果当前节点有右孩子，但没有左孩子，直接返回false。<br />
3.如果当前节点并不是左右孩子全有，那之后的节点必须都为叶节点，否则返回false。<br />
4.遍历过程中如果不返回false，遍历结束后返回true即可。<br />