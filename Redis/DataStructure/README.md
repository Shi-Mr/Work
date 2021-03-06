<h1>数据结构-本段内容来源于《Redis设计与实现》</h1>

<h2>一、简单动态字符串</h2>
在Redis中里面，C字符串只会作为字符串字面量用在一些无需对字符串值进行修改的地方，比如打印日志。当需要的不仅仅是一个字符串字面量时，
Redis就会使用SDS来表示字符值，比如在Redis数据库里面，包含字符串值得键值对在底层都是SDS实现的。<br /><br />

每个sds.h/sdshdr结构表示一个SDS的值：<br />
```
struct sdshdr {
    int len; // 记录buf数组中已使用字节的数量，等于SDS所保存字符串的长度。
    int free; // 记录buff数组中未使用字节的数量。
    char buf[]; // 字节数组，用于保存字符串。
} listNode
```
SDS遵循C字符串以空字符串结尾的惯例，保存空字符的1字节空间不计算在SDS的len属性里面，并且为空字符串分配额外的1字节空间，
以及添加空字符到字符串末尾等操作，都是由SDS函数自动完成的。所以这个空字符对于SDS的使用者来说是完全透明的。遵循空字符结尾这一惯例的好处是，
SDS可以直接重用一部分C字符串函数库里面的函数。

<h3>SDS与C字符串的区别</h3>
1.常数复杂度获取字符串长度。<br />
C字符串本身并不记录自身的长度信息，所以为了获取一个C字符串的长度，程序必须遍历整个字符串，对遇到的每个字符串进行计数，直到遇到代表字符串结尾的空字符串为止，这个操作的复杂度为O(N)。<br />
SDS在len属性中记录了SDS本身的长度，所以获取一个SDS长度的复杂度仅为O(1)。<br /><br />

设置和更新SDS的长度的工作由SDS的API在执行时自动完成的，使用SDS无须进行任何手动修改长度的工作。通过使用SDS而不是C字符串，
Redis将获取字符串长度所需的复杂度从O(N)降低到了O(1)，这确保了获取字符串长度的工作不会成为Redis的性能瓶颈。<br />

2.杜绝缓冲区溢出。<br />
C字符串不记录自身长度带来的另一个问题是容易造成缓冲区溢出。举个例子，目前内存中有两个紧邻着的C字符串s1和s2，现在需要对S1的内容进行修改，
但是却粗心的忘了为s1分配足够的内存空间，那么在s1的长度增加的情况下，s1的数据将溢出到s2所在的空间中，导致s2保存的内容被意外的修改。<br />
SDS的空间分配策略完全杜绝了发生缓冲区溢出的可能性：当SDS API需要对SDS进行修改时，API会先检查SDS的空间是否满足修改所需的要求，如果不满足的话，
API会自动将SDS的空间扩展至执行修改所需的大小，然后才执行实际的修改操作，所以使用SDS既不需要手动修改SDS的空间大小，也不会出现前面所说的缓冲区溢出。<br />

3.减少修改字符串时带来的内存重分配次数。<br />
一个包含了N个字符的C字符串来说，这个C字符串的底层实现总是一个N+1个字符长的数组(额外的一个字符空间用来保存空字符)。
因为C字符串的长度和底层数组的长度之间存在着这种关联性，所以每次增长或者缩短一个C字符串，程序都要对保存这个C字符串的数组进行依次内存重分配操作。
在一般程序中，如果修改字符串长度的情况不太常出现，那么每次修改都需要执行一次内存重分配是可以接受的，但是Redis作为数据库，经常用于速度要求严苛，
数据被频繁修改的场合，这会对性能造成一定的影响，这也是采用SDS的原因。<br />

SDS实现了空间预分配和惰性空间释放两种优化策略。<br />
<h4>空间预分配</h4>
空间预分配用于优化SDS字符串的增长操作：当SDS的API对一个SDS进行修改，并且需要对SDS进行空间扩展的时候，程序不仅会为SDS分配修改所必需的空间，还会为SDS分配额外未使用的空间。<br />
(1)修改后的SDS长度(也就是len属性的值)将小于1MB，那么程序分配和len属性同样大小的未使用空间，这时len属性和free属性的值相等。<br />
(2)修改后的SDS长度大于1M，那么程序会分配1M的未使用空间。<br />
通过空间预分配策略，Redis可以减少连续执行字符串增长操作所需的内存重分配次数。将次数从必定N次降低到最多N次。<br />

<h4>惰性空间释放</h4>
惰性空间释放用于优化SDS字符串的缩短操作：当SDS的API需要缩短SDS保存的字符串时，程序并不立即使用内存重分配来回收缩短后多出来的字节，而是使用free属性将这些字节的数量记录起来，并等待将来使用。<br />
SDS提供了相应的API，让我们可以在有需要的时候，真正的释放SDS的未使用空间，所以不用担心惰性空间释放策略会造成内存浪费。<br />

4.二进制安全。<br />
C字符串中的字符必须符合某种编码，并且除了字符串的末尾之外，字符串里面不能包含空字符，否则最先被程序读入的空字符将被误认为是字符串结尾，
这些限制使得C字符串只能保存文本数据，而不能保存像图片、音频这样的二进制数据。<br />
虽然数据库一般用于保存文本数据，但是保存二进制数据的场景也不少见，因此为了确保Redis可以适用各种不同的使用场景，SDS的所有API都是二进制安全的，
所有API都会以处理二进制的方式来处理SDS存放在buf数组里的数据，程序不会对其中的数据做任何限制、过滤、或者假设，数据在写入时是什么样，它被读取时就是什么样。
SDS适用len属性的值而不是空字符来判断字符串是否结束。<br />

5.兼容部分C字符串函数。<br />

<h2>二、链表</h2>
链表提供了高效的节点重排能力，以及顺序性的节点访问方式，并且可以通过增删节点来灵活的调整链表的长度。<br />
链表在Redis中的应用非常广泛，比如列表键的底层实现之一就是链表。当一个列表键包含了数量比较多的元素，又或者列表中包含的元素都是比较长的字符串时，
Redis就会使用链表作为列表键的底层实现。<br /><br />

每一个链表节点使用一个adlist.h/listNode结构来表示：
```
typedef struct listNode {
    struct listNode *prev; // 前置节点
    struct listNode *next; // 后置节点
    void *value; //节点的值
}
```
多个listNode可以通过prev和next指针组成双端链表。<br />
使用adlist.h/list来持有链表的话，操作会更方便。
```
typedef struct list {
    listNode *head; // 表头节点
    listNode *tail; // 表尾节点
    unsigned long len; // 链表所包含的节点数量
    void *(*dup) (void *ptr); // 节点值复制函数
    void *(*free) (void *ptr); // 节点值释放函数
    int (*match) (void *ptr, void *key); // 节点值对比函数
} list
```
list结构为链表提供了表头指针head、表尾指针tail，以及链表长度计算器len，而dup、free和match成员则是用于实现多态链表所需的类型特定函数。<br />

<h3>链表特性</h3>
1.双端：链表节点带有prev和next指针，获取某个节点的前置节点和后置节点的复杂度都是O(1)。<br />
2.无环：表头结点的prev指针和表尾节点的next指针都指向了NULL，对链表的访问以NULL为终点。<br />
3.表头指针和表尾指针：通过head指针和tail指针，获取表头节点和表尾节点的复杂度为O(1)。<br />
4.带链表长度计数器：使用list结构中的len属性对list持有的链表节点进行计数，程序获取链表中节点数量的复杂度为O(1)。<br />
5.多态：链表节点使用void*指针来保存节点值，并且可以通过list结构的dup、free、match三个属性为节点值设置类型特定函数，所以链表可以用于保存各种不同类型的值。

<h2>三、字典</h2>
字典又称符号表、关联数组或映射，是一种保存键值对的抽象数据结构。<br />
字典在Redis中的应用非常广泛，Redis的数据库就是使用字典作为底层实现的对数据库的增删改查操作也是构建在对字典的操作之上。

<h3>字典的实现</h3>
1.哈希表：<br />
哈希表由dict.h/dictht结构定义：
```
typedef struct dictht {
    dictEntry **table; // 哈希表数组
    unsigned long size; // 哈希表大小
    unsigned long sizemask; // 哈希表大小掩码，用于计算索引值，总是等于size-1
    unsigned long used; // 该哈希表已有节点的数量
} dictht
```
table属性是一个数组，数组中的每一个元素都是一个指向dict.h/dictEntry结构的指针，每个dictEntry结构保存着一个键值对。<br />
size属性记录了哈希表的大小，也即是table数组的大小。<br />
used属性记录了哈希表目前已有节点的数量。<br />
sizemask属性的值总是等于size-1，这个属性与哈希值一起决定一个键应该被放到table数组的哪个索引上。

2.哈希表节点：<br />
哈希表节点使用dictEntry结构表示，每个dictEntry结构都保存着一个键值对：
```
typedef struct dictEntry {
    void *key; // 键
    // 值
    union {
        void *val;
        uint64_t u64;
        int64_t s64;
    } v;
    struct dictEntry *next;
} dictEntry;
```
key属性保存着键值对中的键，而v属性则保存着键值对中的值，其中键值对的值可以是一个指针，或者是一个uint64_t整数，又或者是一个int64_t整数。<br />
next属性指向另一个哈希表节点的指针，这个指针可以将多个哈希值相同的键值对连接在一起，以此来解决键值对冲突的问题。<br />

3.字典：<br />
Redis的字典由dict.h/dict结构来表示：
```
typedef struct dict {
    dictType *type; // 类型特定函数
    void *privdata; // 私有数据
    dictht ht[2]; // 哈希表
    int trehashidx; // rehash索引，当rehash不再进行时，值为-1
} dict
```
type属性和privdata属性是针对不同类型的键值对，为创建多态字典而设置的：
type属性是一个指向dictType结构的指针，每个dictType结构保存了一簇用于操作特定类型键值对的函数，Redis会为用途不同的字典设置不同的类型特定函数。<br />
privdata属性则保存了需要传给那些类型特定函数的可选参数。
```
typedef struct dictType {
    unsigned int (*hashFunction) (const void *key); // 计算哈希值的函数
    void *(*keyDup)(void *privdata, const void *key); // 复制键的函数
    void *(*valDup)(void *privdata, const void *obj); // 复制值的函数
    int (*keyCompare) (void *privdata, const void *key1, const void *key2); // 对比键的函数
    void (*keyDestructor)(void *privdata, void *key) // 销毁键的函数
    void (*valDestructor)(void *privdata, void *obj); // 销毁值的函数
} dictType
```

ht属性是一个包含两个项的数组，数组中的每个项都是一个dictht哈希表，一般情况下，字典只使用ht\[0]哈希表，ht\[1]哈希表只会在对ht\[0]哈希表进行rehash时使用。<br />
除了ht\[1]之外，另一个和rehash有关的属性就是rehashidx，它记录了rehash目前的记录，如果目前没有在进行rehash，那么他的值为-1。<br />

<h3>哈希算法</h3>
当要将一个新的键值对添加到字典里面时，程序需要根据键值对的键计算出哈希值和索引值，然后在根据索引值，将包含新键值对的哈希表节点放到哈希数组的指定索引上面。<br />
Redis计算哈希值和索引值的方法：<br />
使用字典设置的哈希函数，计算键key的哈希值<br />
hash = dict->type->hashFunction(key);<br /><br />

使用哈希表的sizemask属性和哈希值，计算出索引值<br />
根据情况不同，ht\[x]可以是ht\[0]或者ht\[1]<br />
index = hash & dict->ht\[x].sizemask;<br />

Redis使用MurmurHash2算法来计算键的哈希值。<br /><br />

<h3>解决键冲突</h3>
当有两个或以上数量的键被分配到了哈希表数组的同一个索引上面时，我们称这些键发生了冲突。<br />
Redis的哈希表使用链地址法来解决键冲突，每个哈希表节点都有一个next指针，多个哈希表节点可以用next指针构成一个单向链表连接起来，这就解决了键冲突的问题。<br />
因为dictEntry节点组成的链表没有指向链表表尾的指针，所以为了速度考虑，程序总是将新节点添加到链表的表头位置(复杂度为O(1))，排在其他已有节点的前面。<br />

<h3>rehash</h3>
步骤：<br />
1.为字典的ht\[1]哈希表分配空间，这个哈希表的空间大小取决于要执行的操作，以及ht\[0]当前包含的键值对数量(也即是ht\[0].used属性的值)：<br />
如果执行的是扩展操作，那么ht\[1]的大小为第一个大于等于ht\[0].used*2的2的n次幂；<br />
如果执行得是收缩操作，那么ht\[1]的大小为第一个大于等于ht\[0].used的2的n次幂。<br />
2.将保存在ht\[0]中的所有键值对rehash到ht\[1]上面，rehash指的是重新计算键的哈希值和索引值，然后将键值对放置到ht\[1]哈希表的指定位置上。<br />
3.当ht\[0]包含的所有键值对都迁移到ht\[1]之后(ht\[0]变为空表)，释放ht\[0]，将ht\[1]设置为ht\[0]，并在ht\[1]新创建一个空白哈希表，为下一次rehash做好准备。<br /><br />

触发条件：<br />
当以下条件中的任意一个被满足时，程序会自动开始对哈希表执行扩展操作：<br />
1.服务器目前没有在执行BGSAVE命令或者BGREWRITEAOF命令，并且哈希表的负载因子大于等于1。<br />
2.服务器目前正在执行BGSAVE命令或者BGREWRITEAOF命令，并且哈希表的负载因子大于等于5。<br />
其中哈希表的负载因子可以通过公式：<br />
负载因子 = 哈希表已保存节点数量 / 哈希表大小<br />
load_factor = ht\[0].used / ht\[0].size<br /><br />

当哈希表的负载因子小于0.1时，程序自动开始对哈希表执行收缩操作。

<h4>渐进式rehash</h4>
1.为ht\[1]分配空间，让字典同时持有ht\[0]和ht\[1]两个哈希表。<br />
2.在字典中维持着一个索引计数器变量rehashidx，并将它的值设置为0，表示rehash工作正式开始。<br />
3.在rehash进行期间，每次对字典执行添加、删除、查找或者更新操作时，程序除了执行指定的操作以外，
还会顺带将ht\[0]哈希表在rehashidx索引上的所有键值对rehash到ht\[1]，当rehash工作完成之后，程序将rehashidx属性的值增一。<br />
4.随着字典操作的不断执行，最终在某个时间点上，ht\[0]的所有键值对都会被rehash至ht\[1]，这时程序将rehashidx属性的值设为-1，表示rehash操作已完成。<br /><br />

在渐进式rehash期间，字典的删除、查找、更新等操作会在两个哈希表上进行。要在字典中查找一个键的话，程序会先在ht\[0]里面进行查找，
如果没有找到的话，就会继续在ht\[1]里面找；新添加的键一律会保存到ht\[1]中，ht\[0]不在不在进行任何添加操作。

<h2>四、跳跃表</h2>
跳跃表是一种有序的数据结构，他通过在每个节点中维持多个指向其他节点的指针，从而达到快速访问节点的目的。<br />
跳跃表支持平均O(log N)、最坏O(N)复杂度的节点查找，还可以通过顺序性操作来批量处理节点。<br /><br />

Redis使用跳跃表作为有序集合的底层实现之一，如果一个有序集合包含的元素数量比较多，又或者元素成员是比较长的字符串时，Redis就会使用跳跃表作为有序集合键的底层实现。<br />
Redis只在两个地方用到了跳跃表，一个是实现有序集合键，另一个是在集群节点中用作内部数据结构。除此之外，跳跃表在Redis中没有其他用途。<br /><br />

Redis的跳跃表右redis.h/zskiplistNode和redis.h/zskiplist两个结构定义，其中zskiplistNode结构用于跳跃表节点，
而zskiplist结构则用于保存跳跃表节点的相关信息，比如节点的数量，以及指向表头节点和表尾节点的指针等。<br />

<h3>跳跃表的实现</h3>
<h4>跳跃表：</h4>
靠多个跳跃表节点就可以组成一个跳跃表。<br />
通过一个zskiplist结构来持有这些节点，程序可以更方便的对整个跳跃表进行处理，比如快速访问跳跃表的表头节点和表尾节点，
或者快速的获取跳跃表节点的数量(也即是跳跃表的长度)等信息。
```
typedef struct zskiplist {
    structz skiplistNode *header, *tail; // 表头节点、表尾节点
    unsigned long length; // 表中节点的数量
    int level; // 表中层数最大的节点的额层数
} zskiplist;
```
header和tail指针分别指向跳跃表的表头和表尾节点，通过这两个指针，程序定位表头节点和表尾节点的复杂度为O(1)。通过使用length属性
来记录节点的数量，程序可以在O(1)复杂度内返回跳跃表的长度。<br />
level属性则用于在O(1)复杂度内获取跳跃表中层高最大的那个节点的层数量，注意表头节点的层高并不计算在内。

<h4>跳跃表节点：</h4>
```
typedef struct zskiplistNode {
    struct zskiplistNode *backward; // 后退指针
    double score; // 分值
    robj *obj; // 成员对象
    // 层
    struct zskiplistLevel {
        struct zskiplistNode *forward; // 前进指针
        unsigned int span; // 跨度
    } level[];
} zskiplistNode;
```
1.层：<br />
跳跃表节点的level数组可以包含多个元素，每个元素都包含一个指向其他节点的指针，程序可以通过这些层来加快访问其他节点的速度，
一般来说，层的数量越多，访问其他节点的速度节越快。<br />
每次新创建一个新跳跃表节点的时候，程序会根据幂次定律(越大的数出现的概率越小)随机生成一个介于1和32之间的值作为level数组的大小，这个大小就是层的高度。<br /><br />

2.前进指针：<br />
每个层都有一个指向表尾方向的前进指针，用于从表头向表尾方向访问节点。<br /><br />

3.跨度：<br />
层的跨度用于记录两个节点之间的距离：<br />
两个节点之间的跨度越大，它们相距的就越远。<br />
指向NULL的所有前进指针的跨度都为0，因为他们没有连向任何节点。<br /><br />
遍历操作只使用前进指针就可以完成，跨度实际上就是用来计算排位的：在查找某个节点的过程中，将沿途访问过的所有层的跨度累计起来，
得到的结果就是目标节点在跳跃表中的排位。<br /><br />

4.后退指针：<br />
节点的后退指针用于从表尾向表头方向访问节点：跟可以一次跳过多个节点的前进指针不同，因为每一个节点的只有一个后退指针，所以每次只能后退到前一个节点。<br /><br />

5.分值和成员：<br />
节点的分值是一个double类型的浮点数，跳跃表中的所有节点都按分值从小到大来排序。<br />
节点的成员对象是一个指针，他指向一个字符串对象，而字符串对象则保存着一个SDS值。<br />
在同一个跳跃表中，各个节点保存的成员对象必须是唯一的，但是多个节点保存的分值却可以是相同的：分值相同的节点将按照成员对象在字典序
中的大小来进行排序，成员对象较小的节点会排在前面(靠近表头的方向)，而成员对象较大的节点则会排在后面(靠近表尾的方向)。

<h2>五、整数集合</h2>
整数集合是集合键的底层实现之一，当一个集合只包含整数值元素，并且这个集合的元素数量不多时，Redis就会使用整数集合作为集合键的底层实现。<br />

每个intset.h/intset结构表示一个整数集合：
```
typedef struct intset {
    uint32_t encoding; // 编码方式
    uint32_t length; // 集合包含的元素数量
    int8_t contents[]; // 保存元素的数组，虽然属性声明为int8_t类型，但实际上contents数组并不保存任何int8_t类型的值，这取决于encoding属性值。
}
```
contents数组是整数集合的底层实现：整数集合的每一个元素都是contents数组的一个数组项，各个项在数组中按值的大小从小到大有序的排列，
并且数组中不包含任何重复项。<br />
length属性记录了整数集合包含的元素数量，也即是contents数组的长度。

<h3>升级</h3>
每当我们要将一个新元素添加到整数集合里面，并且新元素的类型比整数集合现有所有元素的类型都要长时，整数集合需先进行升级，
然后才能将新元素添加到整数集合里面。<br /><br />

步骤：<br />
1.根据新元素类型，扩展整数集合底层数组的空间大小，并将新元素分配空间。<br />
2.将底层数组现有所有元素都转换成与新元素相同的类型，并将类型转换后的元素放置到正确的位置上面，而且在放置元素的过程中，需要继续维持底层数组的有序性不变。<br />
3.将新元素添加到底层数组里面。<br /><br />
因为每次升级都有可能引起升级，而每次升级都需要对底层数组中的所有元素进行类型转换，所以向整数集合添加新元素的时间复杂度为O(N)。

升级的好处：<br />
1.提升灵活性：自动升级底层数组得策略不会出现类型错误。<br />
2.节约内存：升级操作只会在有需要的时候进行，不需要时只会选择相对较小的类型进行数据保存。

<h3>降级</h3>
暂不支持降级。

<h2>六、压缩列表</h2>
压缩列表时列表键和哈希键的底层实现之一。当一个列表间中包含少量列表项，并且每一个列表项要么是小整数值，要么就是长度比较短的字符串，
那么Redis就会使用压缩列表来做列表键的底层实现；当一个哈希键只包含少量的键值对，并且每个键值对的键和值要么就是小整数值，要么就是
长度比较短的字符串，那么Redis就会使用压缩列表来做哈希键的底层实现。<br /><br />

压缩列表时为了节约内存而开发的，是由一系列特殊编码的连续内存块组成的顺序型数据结构。一个压缩列表可以包含任意多个节点，每个节点可以保存一个字节数组或者一个整数值。<br /><br />

<h3>压缩列表的实现</h3>
<h4>压缩列表</h4>
zlbytes：类型uint32_t，4字节，记录整个压缩列表占用的内存字节数，在对压缩列表进行内存重分配，或者计算zlend的位置时使用。<br />
zltail：uint32_t，4字节，记录压缩列表表尾节点距离压缩列表的起始地址由多少个字节，通过这个偏移量，程序无须遍历整个压缩列表就可以确定表尾节点的位置。<br />
zllen：uint16_t：2字节，记录压缩列表包含的节点数量，当这个属性的值小于UINT16_MAX(65535)时，这个属性的值就是压缩列表包含节点的数量；
当这个值等于UINT16_MAX时，节点的真实数量需要遍历整个压缩列表的节点才能计算得出。<br />
entryX：列表节点，不定，节点的长度由节点保存的内容决定。<br />
zlend：uint8_t，1字节，特殊值0xFF(十进制255)，用于标记压缩列表的末端。<br />

<h4>压缩列表节点的构成</h4>
每个压缩列表节点可以保存一个字节数组或者一个整数值。<br /><br />

字节数组可以是以下三种长度之一：<br />
长度小于等于63字节的字节数组；<br />
长度小于等于16383字节的字节数组；<br />
长度小于等于429496725字节的字节数组；<br /><br />

整数值则可以是以下六种长度之一：<br />
4位长，介于0至12之间的无符号整数；<br />
1字节长的有符号整数；<br />
3字节长的有符号整数；<br />
int16_t类型整数；<br />
int32_t类型整数；<br />
int64_t类型整数；<br /><br />

每个压缩列表节点都由previous_entry_length、encoing、content三个部分组成。<br />
1.previous_entry_length属性：<br />
长度可以是1字节或者5字节。如果前一个节点的长度小于254字节，那么previous_entry_length属性的长度为1字节，前一节点的长度就保存在这一个字节里面。<br />
如果前一节点的长度大于等于254字节，那么previous_entry_length属性的长度为5字节，其中属性的第一字节会被设置为0xFE，
而之后的四个字节则用于保存前一节点的长度。<br /><br />

2.encoding属性：<br />
节点的encoding属性记录了节点的content属性所保存数据的类型和长度：<br />
一字节、两字节或者五字节长，值的最高位为00、01或者10的是字节数组编码：这种编码表示节点的content属性保存着字节数组，数组的长度由编码除去最高两位之后的其他位记录。<br />
一字节长，值的最高位以11开头的是整数编码：这种编码表示节点的content属性保存着整数值，整数值的类型和长度由编码除去最高两位之后的其他位记录。<br /><br />

3.content属性：<br />
该属性负责保存节点的值，节点值可以是一个字节数组或者整数，值的类型和长度由节点的encoding属性决定的。

<h4>连锁更新</h4>
因为每一个节点的previous_entry_length属性都记录了前一个节点的长度，有的需要1字节即可，有的需要五字节。这时假设在一个压缩列表中，
有多个连续的、长度介于250字节到253字节之间的节点e1到eN，因为字节长度都小于254字节，所以记录这些节点长度只需要1字节的previous_entry_length属性，
这时将一个长度大于等于254字节的新节点new设置为压缩列表的表头结点，那么new便成为了e1的前置节点，e1的 previous_entry_length属性需要从
原来的1字节扩展为5字节长，这样e1的长度也大于了254，进而引起e2发生扩展行为，这样向后传递，发生了连锁反应。<br /><br />

除了更新可能会引起连锁反应以外，删除节点可能也会引发连锁反应。

