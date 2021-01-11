<?php
global $isLive;
$isLive = 1;
require_once '../../videos/configuration.php';

if (!empty($_GET['embed'])) {
    include $global['systemRootPath'] . 'plugin/Live/view/videoEmbeded.php';
    return false;
}

require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/subscribe.php';
require_once $global['systemRootPath'] . 'objects/functions.php';
require_once $global['systemRootPath'] . 'plugin/Live/Objects/LiveTransmition.php';

if (!empty($_GET['c'])) {
    $user = User::getChannelOwner($_GET['c']);
    if (!empty($user)) {
        $_GET['u'] = $user['user'];
    }
}

$livet = LiveTransmition::getFromDbByUserName($_GET['u']);
$lt = new LiveTransmition($livet['id']);
if(!$lt->userCanSeeTransmition()){
    forbiddenPage("You are not allowed see this streaming");
}

$uuid = LiveTransmition::keyNameFix($livet['key']);

$u = new User(0, $_GET['u'], false);
$user_id = $u->getBdId();
$video['users_id'] = $user_id;
$subscribe = Subscribe::getButton($user_id);
$name = $u->getNameIdentificationBd();
$name = "<a href='" . User::getChannelLink($user_id) . "' >{$name} " . User::getEmailVerifiedIcon($user_id) . "</a>";

$liveTitle = $livet['title'];
$liveDescription = $livet['description'];
$liveImg = User::getPhoto($user_id);
if(!empty($_REQUEST['playlists_id_live'])){
    $liveTitle = PlayLists::getNameOrSerieTitle($_REQUEST['playlists_id_live']);
    $liveDescription = PlayLists::getDescriptionIfIsSerie($_REQUEST['playlists_id_live']);
    $liveImg = PlayLists::getImage($_REQUEST['playlists_id_live']);
}


$video['creator'] = '<div class="pull-left"><img src="' . $liveImg . '" alt="User Photo" class="img img-responsive img-circle" style="max-width: 40px;"/></div><div class="commentDetails" style="margin-left:45px; font-size:25px;"><div class="commenterName text-muted"><strong>' . $name . '</strong><br></div></div>';
$video['creator1'] = '<div class="commentDetails" style="margin-left:45px;"><div class="commenterName text-muted"><strong></strong><br>' . $subscribe . '</div></div>';
$video['creator2'] = '<div class="commentDetails" style="margin-left:45px;"><div class="commenterName text-muted"><strong></strong><br>' . $subscribe . '</div></div>';

$img = "{$global['webSiteRootURL']}plugin/Live/getImage.php?u={$_GET['u']}&format=jpg";
$imgw = 640;
$imgh = 360;

$liveDO = AVideoPlugin::getObjectData("Live");
$video['type'] = 'video';
AVideoPlugin::getModeYouTubeLive($user_id);

$isCompressed = AVideoPlugin::loadPluginIfEnabled('TheaterButton') && TheaterButton::isCompressed();

$sideAd = getAdsSideRectangle();

$modeYoutubeBottomClass1 = "col-sm-7 col-md-8 col-lg-12";
$modeYoutubeBottomClass2 = "col-sm-4 col-md-3 col-lg-3 ";
if(empty($sideAd) && !AVideoPlugin::loadPluginIfEnabled("Chat2")){
    $modeYoutubeBottomClass1 = "col-sm-12 col-md-12 col-lg-10";
    $modeYoutubeBottomClass2 = "hidden ";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $liveTitle; ?> - <?php echo __("Live Video"); ?> - <?php echo $config->getWebSiteTitle(); ?></title>
        <link href="<?php echo $global['webSiteRootURL']; ?>js/video.js/video-js.min.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>css/player.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo $global['webSiteRootURL']; ?>js/webui-popover/jquery.webui-popover.min.css" rel="stylesheet" type="text/css"/>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>

        <meta property="fb:app_id"             content="774958212660408" />
        <meta property="og:url"                content="<?php echo Live::getLinkToLiveFromUsers_id($user_id); ?>" />
        <meta property="og:type"               content="video.other" />
        <meta property="og:title"              content="<?php echo str_replace('"', '', $liveTitle); ?> - <?php echo $config->getWebSiteTitle(); ?>" />
        <meta property="og:description"        content="<?php echo str_replace('"', '', $liveTitle); ?>" />
        <meta property="og:image"              content="<?php echo $img; ?>" />
        <meta property="og:image:width"        content="<?php echo $imgw; ?>" />
        <meta property="og:image:height"       content="<?php echo $imgh; ?>" />
        <?php
        //echo AVideoPlugin::getHeadCode();
        ?>
        <style>
        .container-fluid{
          background-color: #000000 !important;
        }
       
        .commenterName{
            margin-left: 10px;
        }
        .panel-footer{
            border-color:#2f2f2f !important;
        }
        .hideonmob{
            display:block;
            position: absolute;

        }
        .hovercust:hover .hideonmob{
            opacity : 0;
        }
@media screen and (max-width: 430px) {
.hideonmob{
    display:none;
}
.hovercust:hover + .hideonmob{
    display:block;
}
html, body {margin: 0; height: 100%; overflow: hidden}
}

        </style>
    </head>

    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
            ?>
            <div class="container-fluid principalContainer" id="modeYoutubePrincipal">
                <?php
                if (!$isCompressed) {
                    ?>
                    <div class="" id="modeYoutubeTop" >
                        <div class="col-md-12">
                            <center style="margin:5px;">
                                <?php echo getAdsLeaderBoardTop(); ?>
                            </center>
                        </div>
                        <div class="col-md-12">
                        <?php
$_REQUEST['live_servers_id'] = Live::getLiveServersIdRequest();
$poster = Live::getPosterImage($livet['users_id'], $_REQUEST['live_servers_id']);
?>
<link href="<?php echo $global['webSiteRootURL']; ?>plugin/Live/view/live.css" rel="stylesheet" type="text/css"/>
<div class="row main-video" id="mvideo">
    <div class="secC col-sm-8 col-md-9 hovercust" style="padding-right:1px;padding-left:1px;">
        <div id="videoContainer ">
            <div id="floatButtons" style="display: none;">
                <p class="btn btn-outline btn-xs move">
                    <i class="fas fa-expand-arrows-alt"></i>
                </p>
                <button type="button" class="btn btn-outline btn-xs" onclick="closeFloatVideo();floatClosed = 1;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="main-video" class="embed-responsive embed-responsive-16by9">
                <video poster="<?php echo $global['webSiteRootURL']; ?><?php echo $poster; ?>?<?php echo filectime($global['systemRootPath'] . $poster); ?>" controls playsinline webkit-playsinline="webkit-playsinline"
                       class="embed-responsive-item video-js vjs-default-skin vjs-big-play-centered liveVideo vjs-16-9"
                       id="mainVideo">
                    <source src="<?php echo Live::getM3U8File($uuid); ?>" type='application/x-mpegURL'>
                </video>
                <?php
                if (AVideoPlugin::isEnabled("0e225f8e-15e2-43d4-8ff7-0cb07c2a2b3b")) {
                    require_once $global['systemRootPath'] . 'plugin/VideoLogoOverlay/VideoLogoOverlay.php';
                    $style = VideoLogoOverlay::getStyle();
                    $url = VideoLogoOverlay::getLink();
                    ?>
                    <div style="<?php echo $style; ?>">
                        <a href="<?php echo $url; ?>" target="_blank"> <img src="<?php echo $global['webSiteRootURL']; ?>videos/logoOverlay.png" alt="Logo" class="img-responsive col-lg-12 col-md-8 col-sm-7 col-xs-6"></a>
                    </div>
                <?php } ?>


            </div>
            <div style="z-index: 999; position: absolute; top:5px; left: 5px; opacity: 0.8; filter: alpha(opacity=80);">
                <?php
                $streamName = $uuid;
                include $global['systemRootPath'] . 'plugin/Live/view/onlineLabel.php';
                include $global['systemRootPath'] . 'plugin/Live/view/onlineUsers.php';
                ?>
            </div>
        </div>
    </div>
    <div class="<?php echo $modeYoutubeBottomClass2; ?> rightBar" id="yptRightBar">
                        <!-- <div class="list-group-item ">
                           
                            echo $sideAd;
                            ?>
                        </div> -->
                    </div>
</div>
<script>
<?php
//comment
echo PlayerSkins::getStartPlayerJS();
?>
</script>


                        </div>
                        <div class="col-md-12">
                            <center style="margin:5px;">
                                <?php echo getAdsLeaderBoardTop2(); ?>
                            </center>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="row" id="modeYoutubeBottom" style="margin: 0;">
                    <div class="<?php echo $modeYoutubeBottomClass1; ?>" id="modeYoutubeBottomContent">
                        <?php
                        if ($isCompressed) {
                            ?>
                            <div class="" id="modeYoutubeTop" >
                                <div class="col-md-12">
                                    <center >
                                        <?php echo getAdsLeaderBoardTop(); ?>
                                    </center>
                                </div>
                                <div class="col-md-12">
                                    <?php
                                    require "{$global['systemRootPath']}plugin/Live/view/liveVideo.php";
                                    ?>
                                </div>
                                <div class="col-md-12">
                                    <center>
                                        <?php echo getAdsLeaderBoardTop2(); ?>
                                    </center>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="panel" style="background-color: #000000 !important;">
                            <div class="panel-body">
                                <h1 itemprop="name">
                                    <?php
                                    if($lt->isAPrivateLive()){
                                    ?>
                                    <i class="fas fa-lock"></i>
                                    <?php
                                    }else{
                                    ?>
                                    <i class="fas fa-video"></i>
                                    <?php
                                    }
                                    ?>
                                   <?php echo $liveTitle; ?>

                                </h1>
                                <style>
.btncustom {
  background-color: #3F3F3F;
  border: none;
  color: white;
  padding: 5px 10px;
  font-size: 16px;
  margin-right: 2px;
  cursor: pointer;
}
.btn1 {
  background-color: #31AB77;
  border: none;
  color: white;
  padding: 5px 10px;
  font-size: 16px;
  cursor: pointer;
}
/* Darker background on mouse-over */
.btncustom:hover {
  background-color: #31AB77;
}
.btn1:hover {
  background-color: #3F3F3F;
}
.btn-group>.btn:first-child:not(:last-child):not(.dropdown-toggle){
    background-color:#31AB77;
    color:white;
}
.btn-group>.btn:last-child:not(:first-child), .btn-group>.dropdown-toggle:not(:first-child){
    background-color:#31AB77;
    color:white;

}
.watch8-action-buttons{
    border-top: 1px solid #333333;
    width : 105%;
}
@media screen and (min-width: 1367px) {
  .custom1 {
    margin-right: 1%;
    margin-left : 5%;
  }
  
}
body{
    font-family: 'Rubik', sans-serif;

}
</style>

<div class="col-md-12 watch8-action-buttons text-muted"></div>
                                <div class="col-xs-4 col-md-5 col-sm-4 col-lg-6" ><?php echo $video['creator']; ?></div>
                                <div class="col-xs-8 col-md-7 col-sm-8 col-lg-6" >
                         <a href="#" class="btn btn-default no-outline custom1" id="shareBtn">
                                                                                  
                         <i class="fas fa-share-alt"></i>
                            <script>
                                            $(document).ready(function () {
                                                $("#shareDiv").slideUp();
                                                $("#shareBtn").click(function () {
                                                    $(".menusDiv").not("#shareDiv").slideUp();
                                                    $("#shareDiv").slideToggle();
                                                    return false;
                                                });
                                            });
                                        </script>
                                        </a>
                                        <button class="btn btn-m" style=" background-color:#3F3F3F;"><i class="far fa-star"></i> &nbspSubscribe</button>
                                        <?php echo $subscribe; ?>
                                </div>
                                <div class="col-md-12 watch8-action-buttons text-muted"></div>
                                <p><?php echo nl2br(textToLink($liveDescription)); ?></p>
                                <div class="row">
                                    <!-- <div class="col-md-12 watch8-action-buttons text-muted">
                                      
                                        
                                        <?php echo AVideoPlugin::getWatchActionButton(0); ?>
                                    </div> -->
                                </div>
                                <?php
                                $link = Live::getLinkToLiveFromUsers_id($user_id);
                                getShareMenu($liveTitle, $link, $link, $link .= "?embed=1");
                                ?>
                                <!-- <div class="row">

                                    <div class="col-lg-12 col-sm-12 col-xs-12 extraVideos nopadding"></div>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    <!-- <div class=" echo $modeYoutubeBottomClass2; ?> rightBar" id="yptRightBar">
                        <div class="list-group-item ">
                           
                            echo $sideAd;
                            ?>
                        </div>
                    </div> -->
                    <div class="col-lg-1"></div>
                </div>

            </div>


            <script src="<?php echo $global['webSiteRootURL']; ?>js/video.js/video.min.js" type="text/javascript"></script>
            <?php
            echo AVideoPlugin::afterVideoJS();
            include $global['systemRootPath'] . 'view/include/footer.php';
            ?>
            <script src="<?php echo $global['webSiteRootURL']; ?>js/webui-popover/jquery.webui-popover.min.js" type="text/javascript"></script>
            <script src="<?php echo $global['webSiteRootURL']; ?>js/bootstrap-list-filter/bootstrap-list-filter.min.js" type="text/javascript"></script>
    </body>
</html>

<?php
include $global['systemRootPath'] . 'objects/include_end.php';
?>
