<?php
require_once '../../../videos/configuration.php';
if(!User::isAdmin()){
    die("Not Admin");
}
AVideoPlugin::loadPlugin("Chat2");
$name = "[Edit with your server name, can be any string]";
$url = "[Edit with your chat server URL, I.E. https://chat.mysite.com/]";
if(!empty($_GET['id'])){
    $chat = new Chat_channels($_GET['id']);
    $name = $chat->getName();
    $url = $chat->getUrl();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $config->getWebSiteTitle(); ?>  :: Chat2</title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
    </head>
    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
        ?>
        <div class="container">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php echo __('Instructions') ?> 
                </div>
                <div class="panel-body">
                    <div class="alert alert-info">
                        Due to the high demand for hardware caused by chat, when it starts to be very consumed by users, 
                        we decided to allow this to be separated from the main server, we will call this Chat Stand Alone (CSA).
                        CSA will always authenticate on the streamer, that is, only active users on the Streamer will be able to login. 
                        However the rest of the processes will take place on the Chat server, thus leaving the platform independent of your 
                        main video server, so your streamer will not be affected, even if the chat reaches peak usage. So each channel can have a separate chat server.
                        <br>
                        Despite being tested only on Ubuntu, we believe that Chat can work on any operating system with PHP + MySQL
                        <br>
                        Check <a href="https://tutorials.avideo.com/video/81/chat-external-server-installation" target="_blank">this video</a> how to install the chat on a separated server
                        <br>
                        Below are the instructions needed to make your chat work on other servers.
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            Chat2 must be enabled 
                            <div class="pull-right">
                                <?php echo AVideoPlugin::getSwitchButton("Chat2"); ?>
                            </div>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo $global['webSiteRootURL']; ?>plugin/API/info.php" target="_blank">API</a> must be enabled 
                            <div class="pull-right">
                                <?php echo AVideoPlugin::getSwitchButton("API"); ?>
                            </div>
                        </li>
                        <li class="list-group-item">
                            Copy all files from the Chat2 plugin to a new server
                            Feel free to use any transfer tool, for example you can use <a href="https://wiki.filezilla-project.org/Using" target="_blank">Filezilla</a> or <a href="https://winscp.net/eng/docs/guides" target="_blank">Winscp</a><br>
                            You must copy all the files from the directory <strong><?php echo $global['systemRootPath']; ?>plugin/Chat2/</strong> in your new server, 
                            usually it is on <strong>/var/www/html/Chat2/</strong>
                        </li>
                        <li class="list-group-item">
                            On the new server create a database for the Chat2: 
                            <pre><code>sudo mysql -u username -p -e "CREATE DATABASE database_name;"</code></pre>
                        </li>
                        <li class="list-group-item">
                            Install the the <strong>/path/to/Chat2/install/install.sql</strong> script.<br>
                            if you copy the Chat2 to the <strong>/var/www/html/Chat2/</strong>
                            Your script file will be on <strong>/var/www/html/Chat2/install/install.sql</strong> and your command line will be something like this
                            <pre><code>sudo mysql -u username -p database_name < /var/www/html/Chat2/install/install.sql</code></pre>
                            Or you can download the SQL script <a href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/install/install.sql" target="_blank">here</a> 
                        </li>
                        <li class="list-group-item">
                            Edit the file <strong>/path/to/Chat2/standAlone/config.php</strong> with the following code<br>
                            <pre><code><?php echo htmlentities("<?php").PHP_EOL; ?>
$mysqlHost = '[localhost]';
$mysqlUser = '[username]';
$mysqlPass = '[password]';
$mysqlDatabase = '[database_name]';
$global['systemRootPath'] = '[Edit with your server path, I.E. /var/www/html/Chat2/]';
$global['serverName'] = '<?php echo $name; ?>';
$global['webSiteRootURLStandAlone'] = '<?php echo $url; ?>';
$global['webSiteRootURL'] = '<?php echo $global['webSiteRootURL']; ?>';
$APISecret="<?php echo API::getAPISecret(); ?>";</code></pre>
                            
                        </li>
                        
                        <li class="list-group-item">
                            Make sure you create a directory <strong>/path/to/Chat2/videos/</strong> in your new chat server and give write permissions to it<br>
                            <pre><code>mkdir /var/www/html/Chat2/videos/ && chmod 777 /var/www/html/Chat2/videos/</code></pre>
                            
                        </li>
                        /var/www/html/videos/
                        <li class="list-group-item">
                            Add the server into your <a href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/View/editor.php" target="_blank"><i class="fa fa-edit"></i> External Chats</a><br>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
    </body>
</html>
