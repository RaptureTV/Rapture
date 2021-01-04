<?php
require_once '../../../videos/configuration.php';
AVideoPlugin::loadPlugin("Chat2");
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $config->getWebSiteTitle(); ?>  :: Chat2</title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $global['webSiteRootURL']; ?>view/css/DataTables/datatables.min.css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>view/js/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css"/>
    </head>
    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
        ?>
        <div class="container-fluid">
            <div class="panel panel-default">
                <div class="panel-heading"><?php echo __('Chat2') ?> 
                    <div class="pull-right">
                        <?php echo AVideoPlugin::getSwitchButton("Chat2"); ?>
                    </div>
                </div>
                <div class="panel-body">
                    <ul class="nav nav-tabs">
                        <li class="active"><a data-toggle="tab" href="#Chat_channels"><?php echo __("Chat Channels"); ?></a></li>
                    </ul>
                    <div class="tab-content">
                        <div id="Chat_channels" class="tab-pane fade in active" style="padding: 10px;">
                            <?php
                            include $global['systemRootPath'] . 'plugin/Chat2/View/Chat_channels/index_body.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript" src="<?php echo $global['webSiteRootURL']; ?>view/css/DataTables/datatables.min.js"></script>
        <script src="<?php echo $global['webSiteRootURL']; ?>js/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
    </body>
</html>
