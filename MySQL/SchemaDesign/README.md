<h1>范式和反范式</h1>
1.第一范式：所有的域都应该是原子性的，即数据表的每一列都是不可分割的原子数据项。<br />
2.第二范式：在第一范式的基础上，要求实体的属性完全依赖于主关键字，而不能只依赖于主关键字的一部分。即数据表的每一列都要完全依赖于主键。<br />
3.第三范式：在第二范式的基础上，任何非主属性不得依赖于其他非主属性，即数据表的每一列必须直接依赖于主键，不能存在传递依赖。<br />

<h2>范式的优点和缺点：</h2>
优点：范式化的更新操作通常比反范式化要快。<br />
1.当数据较好的范式化时，只有很少或者没有重复数据，所以只需要修改更少的数据。<br />
2.范式化的表通常更小，可以更好地放在内存里，所以执行操作会更快。<br />
3.很少有多余的数据意味着检索列表数据时更少需要DISTINCT或者GROUP BY语句。<br />

缺点：范式化设计的数据表通常需要关联。稍微复杂一点的查询语句在符合范式的schema上都可能需要至少一次关联，也许更多。这不但代价昂贵，也可能使一些索引策略无效。<br />

<h2>反范式的优点和缺点：</h2>
优点：数据都在一张表中，可以很好的避免关联。


<h2>混用范式化和反范式化：</h2>
常用的反范式化数据的方法是复制或者缓存，在不同的表中存储相同的特定的列。在MySQL5.0和更新版本可以使用触发器更新缓存值。


<h1>加快ALTER TABLE的操作速度</h1>
ALTER TABLE操作的性能对于大表来说是个大问题，MySQL执行大部分修改表结构操作的方法是用新的结构创建一个空表，从旧表中查出所有数据插入新表，然后删除旧表。<br /><br />

MySQL5.1和更新版本中包含了一些类型的“在线”操作的支持，这些功能不需要在整个操作过程中锁表。<br />
例如：有两种方法可以改变或者删除一个列的默认值<br />
第一种：通过ALTER COLUMN操作来改变列的默认值，这个语句会直接修改.frm文件而不涉及表数据，所以这个操作是非常快的。<br />
第二种：直接修改.frm文件(官方无文档，不知具体的影响)<br />
对于移除一个列的AUTO_INCREMENT属性、增加/移除/更改ENUM等操作，可以尝试使用替换.frm文件的方法。步骤如下：<br />
1.创建一张相同结构的空表，进行必要的修改。<br />
2.执行FLUSH TABLES WITH READ LOCK。这将会关闭所有正在使用的表，并且禁止任何表被打开。<br />
3.交换.frm文件<br />
4.执行UNLOCK TABLES来释放第二步的读锁。<br />