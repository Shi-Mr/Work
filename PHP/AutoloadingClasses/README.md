<h1>类的自动加载</h1>
1.在编写面向对象的程序时，我们需要给每一个类建立一个PHP文件，这样带来了一个烦恼就是：每次在一个文件开头都需要include很多文件。<br /><br />
2.在PHP5中，引入了新的机制，类的自动加载。也就是说新建PHP文件后，不需要再开头include，当使用当前文件中尚未被定义的类或者接口时，会自动加载这些类。<br /><br />
3.php通过spl_autoload_register()函数可实现自动加载，通过该函数可以注册任意数量的自动加载器。通过自动加载器，脚本引擎在PHP出错失败前有了最后一个机会加载所需要的类。<br /><br />

