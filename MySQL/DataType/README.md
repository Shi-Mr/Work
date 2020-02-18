<h1>整型</h1>
1.MySQL中有两种类型的数字：整数和实数。<br />

2.整数分为以下几种类型：TINYINT、SMALLINT、MEDIUMINT、INT、BIGINT，分别使用8、16、24、32、64位存储空间，
存储的值的范围从负的2的N-1次方到2的N-1次方减1，N是存储空间的位数。<br />

3.整数可选UNSIGNED属性，不允许负值，大致使正数的上限提高一倍。<br />

4.MySQL可以为整数类型指定宽度，这不是限制值得合法范围，只是用来规定在交互工具中用来显示字符的个数。

<h1>浮点型</h1>
1.实数是带有小数部分的数字，不只是为了存储小数部分，也可以使用DECIMAL存储比BIGINT还大的整数。<br />

2.MySQL即支持精确类型，也支持不精确类型。<br />

3.DECIMAL用来存储精确的小数，该种类型代价高，影响列的空间消耗。不建议使用，如果使用精确的数据，比如财务数据，
可以使用BIGINT代替DECIMAL，将需要存储的货币单位根据小数的位数乘以相应的倍数即可。<br />

4.FLOAT使用4个字节，DOUBLE使用8个字节。MySQL内部使用DOUBLE作为内部浮点计算的类型。<br />

<h1>字符型</h1>
<h2>VARCHAR和CHAR</h2>

1.VARCHAR类型用来存储可变长字符串，是最常见的字符串数据类型。他比CHAR要节省空间，
因为他仅使用必要的空间（例如，越短的字符串使用越少的空间）。VARCHAR需要使用1或2个额外字节记录字符串的长度，
如果列的最大长度小于或等于255字节，则使用1个字节表示，否则使用两个字节表示。<br />

2.VARCHAR节省了空间，所以对性能有了一定的帮助。但是由于行是变长的，在UPDATE时可能使行变的比原来更长，这就导致需要做额外的工作。
如果一个行占用的空间增长，并且在页内没有更多的空间可以存储，在这种情况下，不同的存储引擎的处理方式是不一样的。<br />

3.下列场景使用VARCHAR是合适的：字符串列的最大长度比平均长度大很多；列的更新很少，所以碎片不是问题；使用了像UTF-8这样复杂的字符集，
每个字符都使用不同的字节数进行存储。<br />

4.CHAR类型是定长的，MySQL总会根据定义的字符串长度分配足够的空间。当存储CHAR值的时候，MySQL会删除所有的末尾空格。<br />

5.CHAR适合存储很短的字符串，或者所有值都接近于同一个长度，对于经常变更的数据，CHAR也比VARCHAR好，因为定长的CHAR不容易产生碎片。<br />

6.BINARY和VARBINARY，存储的都是二进制字符串，跟普通字符串非常相似，但是二进制字符串存储的是字节码而不是字符。
填充也不一样：MySQL填充BINARY采用的是\0（零字节）而不是空格，在检索时也不会去掉填充值。<br />

7.进行字符比较时，二进制的优势不仅仅是体现在大小写敏感上。MySQL比较BINARY字符串时，每次按一个字节，并且根据该字节的数值进行比较。
因此二进制比较比字符比较简单的多，所以也就更快。<br />

<h2>BLOB和TEXT</h2>

1.为存储更大的数据而设计的字符串数据类型，分别采用二进制和字符串方式存储。<br />

数据类型家族：<br />
字符类型：TINYTEXT、SAMLLTEXT、TEXT、MEDIUMTEXT、LONGTEXT<br />
二进制类型：TINYBLOB、SMALLBLOB、BLOB、MEDIUMBLOB、LONGBLOB<br />

2.MySQL把每一个BLOB和TEXT值当作一个独立的对象处理。存储引擎在存储时通常会做特殊处理。当存储的值特别大的时候，
InnoDB会使用专门的外部存储区域来进行存储，此时每个值在行内需要1~4个字节存储一个指针，指向外部存储区域。<br />

3.两者之间仅有的区别在于BLOB类型存储的是二进制数据，没有排序规则或字符集。<br />

4.MySQL对BLOB和TEXT进行排序时，只对每个列的最前max_sort_length字节而不是整个字符串进行排序，如果只需要排序前面一小部分字符，
则可以减小max_sort_length的配置，或者使用ORDER BY SUSTRING(column, length)<br />


<h1>枚举型</h1>

1.有时候可以使用枚举类型代替常用的字符串类型，枚举列可以把一些不重复的字符串存储成一个预定义的集合。MySQL存储枚举时非常紧凑，
会根据列表值的数量压缩到一个或两个字节中。MySQL会在内部将每个值在列表中的位置保存为整数，并且子.frm文件中保存数字-字符串映射关系的查找表。<br />

2.枚举最不好的地方是，字符串的列表是固定的，添加或者删除字符串必须使用ALTER TABLE，因此对于一系列未来可能会改变的字符串，
使用枚举不是一个好主意，除非接受只在列表末尾添加元素，这样在MySQL5.1中就不需要重建整个表来完成修改。<br />

3.还有一点在连表时，CHAR和VARCHAR列与枚举列进行关联可能会比直接关联CHAR/VARCHAR列更慢。<br />

4.枚举列因为存储的是整数，所以表空间的大小大约会缩小1/3，同时，相应的主键也会缩小，进而其他二级索引也会变的更小。<br />

<h1>DATETIME和TIMESTAMP</h1>
1.MySQL支持的最小时间粒度为秒，MariaDB支持的最小时间粒度为微。<br />

2.DATETIME：这个类型能保存大范围的值，从1001到9999年，精度为秒。他把日期和时间封装到格式为YYYYMMDDHHMMSS的整数中，与时区无关。
使用8个字节的存储空间。默认情况下，MySQL以一种可排序的、无歧义的格式显示DATETIME值。这是ANSI标准定义的日期和时间表示方法。<br />

3.TIMESTAMP：保存了从1970年1月1日午夜以来的秒数，他和Unix时间戳相同。TIMESTAMP只使用了4个字节的存储空间，
因此他的范围比DATETIME小的多：只能从1970年到2038年。<br />

4.MySQL提供了FROM_UNIXTIME()函数把UNIX时间戳转换为日期，并提供了UNIX_TIMESTAMP()函数把日期转换为Unix时间戳。<br />

5.TIMESTAMP显示的值依赖于时区。MySQL服务器、操作系统、以及客户端连接都有时区设置。TIMESTAMP列默认为NOT NULL，这和其他数据类型不一样。<br />

6.除了特殊行为之外，通常应该尽量使用TIMESTAMP，因为他比DATETIME的空间效率更高。，有时候人们会将Unix时间戳存储为整数值。<br />