<h1>事务</h1>
事务就是一组原子性的SQL查询，或者说是一个独立的工作单元。

<h2>ACID特性：</h2>
原子性(atomicity)：一个事务必须被视为一个不可分割的最小工作单元，整个事务中的所有操作要么全部提交成功，要么全部失败回滚，对于一个事务来说，不可能只执行其中的一部分操作，这就是事务的原子性。<br />
一致性(consistency)：数据库总是从一个一致性的状态转到另一个一致性的状态。<br />
隔离性(isolation)：通常来说，一个事务所做的修改在最终提交以前，对其他事务是不可见的。<br />
持久性(durability)：一旦事务提交，则其所做的修改就会永久的保存到数据库中。此时就是系统崩溃，修改的数据也不会丢失。<br />

<h3>事务出现的问题：</h3>
脏读：读取到了其他事务未提交的数据。<br />
不可重复读：开启事务A，事务A修改了第一行数据，这是事务B查询了第一行数据，因为事务A未提交，所以B查询的仍然是老数据，
此时事务A进行提交并且成功，而B再次查询时发现数据已经变了，这种情况就是不可重复读。<br />
幻读：当某个事务在读取某个范围内的记录时，另一个事务又在该范围内插入了新的记录，当之前的事务再次读取该范围记录时，返现多了一行，这种情况就是幻读。


<h2>隔离级别：</h2>
READ UNCOMMITTED(读未提交)：事务中的修改，即使没有提交，对其他事务也都是可见的。<br />
READ COMMITTED(读已提交)：只能读取到已经提交的事务所做的修改。解决了脏读。<br />
REPEATABLE READ(可重复读)：同一个事务中多次读取同样记录的结果是一致的。MySQL默认隔离级别。解决了不可重复读。<br />
SERIALIZABLE(可串行化)：最高的隔离级别。通过强制事务串行执行，避免了幻读问题。该级别会在读取的每一条数据上面都加锁，
所以可能导致大连的超时和锁争用的为标题。很少用这个级别。解决了幻读，但是目前MySQL通过多版本并发控制去解决幻读的问题。

<h2>死锁问题：</h2>
指两个或者多个事务在同一资源上相互占用，并请求锁定对方占用的资源，从而导致恶性循环的问题<br /><br />

InnoDB目前处理死锁的方法是将持有最少行级排他锁的事务进行回滚。