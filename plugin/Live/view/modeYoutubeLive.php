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
    <div class="secC col-sm-8 col-md-9" style="padding-right:1px;padding-left:1px;">
        <div id="videoContainer">
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
                        <div class="list-group-item ">
                            <?php
                            echo $sideAd;
                            ?>
                        </div>
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
</style>
<div class="col-sm-7 col-md-8 col-lg-9" id="modeYoutubeBottomContent">
                                                <div class="panel">
                            <div class="panel-body">
                                <h1 itemprop="name">
                                                                        <i class="fas fa-video"></i>
                                                                       AMA Ghost Recon
                                </h1>
                                <div class="col-xs-12 col-sm-12 col-lg-12"><div class="pull-left"><img src="http://157.230.2.203/VOD/videos/userPhoto/photo2.png?1608491521" alt="User Photo" class="img img-responsive img-circle" style="max-width: 40px;"></div><div class="commentDetails" style="margin-left:45px;"><div class="commenterName text-muted"><strong><a href="http://157.230.2.203/VOD/channel/C" class="btn btn-xs btn-default">corey  <i class="fas fa-check-circle" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="E-Mail Verified"></i></a></strong><br><div class="btn-group"><button class="btn btn-xs subsB subscribeButton2 subscribed subs2"><i class="fas fa-play-circle"></i> <b class="text">Subscribed</b></button><button class="btn btn-xs subsB subscribed subs2"><b class="textTotal2">23</b></button></div><span class=" notify2 "><button onclick="toogleNotify2();" class="btn btn-default btn-xs " data-toggle="tooltip" title="" data-original-title="Stop getting notified for every new video">
                                <i class="fa fa-bell"></i>
                            </button></span><span class=" notNotify2 hidden"><button onclick="toogleNotify2();" class="btn btn-default btn-xs " data-toggle="tooltip" title="" data-original-title="Get notified for every new video">
                                <i class="fa fa-bell-slash"></i>
                            </button></span><input type="hidden" placeholder="E-mail" class="form-control" id="subscribeEmail2" value="raimundas.sereika@gmail.com"><script>
                    function toogleNotify2(){
                        email = $('#subscribeEmail2').val();
                        subscribeNotify(email, '2');
                    }
                    $(document).ready(function () {
                        $(".subscribeButton2").off("click");
                        $(".subscribeButton2").click(function () {
                            email = $('#subscribeEmail2').val();
                            subscribe(email, '2');
                        });
                        $('[data-toggle="tooltip"]').tooltip(); 
                    });
                </script></div></div></div>
                                <p></p>
                                <div class="row">
                                    <div class="col-md-12 watch8-action-buttons text-muted">
                                        <a href="#" class="btn btn-default no-outline" id="shareBtn">
                                            <span class="fa fa-share"></span> Share                                        </a>
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
                                                                            </div>
                                </div>
                                <div class="row bgWhite list-group-item menusDiv" id="shareDiv" style="display: none;">
    <div class="tabbable-panel">
        <div class="tabbable-line text-muted">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link " href="#tabShare" data-toggle="tab">
                        <span class="fa fa-share"></span>
                        Share                    </a>
                </li>

                                    <li class="nav-item">
                        <a class="nav-link " href="#tabEmbed" data-toggle="tab">
                            <span class="fa fa-code"></span>
                            Embed                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="#tabEmail" data-toggle="tab">
                            <span class="fa fa-envelope"></span>
                            E-mail                        </a>
                    </li>
                                </ul>
            <div class="tab-content clearfix">
                <div class="tab-pane active" id="tabShare">
                         
<link href="http://157.230.2.203/VOD/view/css/social.css" rel="stylesheet" type="text/css">
<ul class="social-network social-circle">
    <li><a href="https://www.facebook.com/sharer.php?u=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;title=AMA+Ghost+Recon" target="_blank" class="icoFacebook" title="" data-toggle="tooltip" data-original-title="Facebook"><i class="fab fa-facebook-square"></i></a></li>
    <li><a href="http://twitter.com/intent/tweet?text=AMA+Ghost+Recon+http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC" target="_blank" class="icoTwitter" title="" data-toggle="tooltip" data-original-title="Twitter"><i class="fab fa-twitter"></i></a></li>
    <li><a href="http://www.tumblr.com/share?v=3&amp;u=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;quote=AMA+Ghost+Recon&amp;s=" target="_blank" class="icoTumblr" title="" data-toggle="tooltip" data-original-title="Tumblr"><i class="fab fa-tumblr"></i></a></li>
    <li><a href="http://pinterest.com/pin/create/button/?url=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;description=" target="_blank" class="icoPinterest" title="" data-toggle="tooltip" data-original-title="Pinterest"><i class="fab fa-pinterest-p"></i></a></li>
    <li><a href="http://www.reddit.com/submit?url=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;title=AMA+Ghost+Recon" target="_blank" class="icoReddit" title="" data-toggle="tooltip" data-original-title="Reddit"><i class="fab fa-reddit-alien"></i></a></li>
    <li><a href="http://www.linkedin.com/shareArticle?mini=true&amp;url=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;title=AMA+Ghost+Recon&amp;summary=&amp;source=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC" target="_blank" class="icoLinkedin" title="" data-toggle="tooltip" data-original-title="LinkedIn"><i class="fab fa-linkedin-in"></i></a></li>
    <li><a href="http://wordpress.com/press-this.php?u=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;quote=AMA+Ghost+Recon&amp;s=" target="_blank" class="icoWordpress" title="" data-toggle="tooltip" data-original-title="Wordpress"><i class="fab fa-wordpress-simple"></i></a></li>
    <li><a href="https://pinboard.in/popup_login/?url=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC&amp;title=AMA+Ghost+Recon&amp;description=" target="_blank" class="icoPinboard" title="" data-toggle="tooltip" data-original-title="Pinboard"><i class="fas fa-thumbtack"></i></a></li>
    <li><a href="https://parler.com/new-post?message=AMA+Ghost+Recon&amp;url=http%3A%2F%2F157.230.2.203%2FVOD%2Flive%2F0%2FC" target="_blank" class="icoParler" title="" data-toggle="tooltip" data-original-title="Parler">
            <i class="fas"><img src="http://157.230.2.203/VOD/view/img/social/parler.png" style="max-width: 16px; max-height: 16px"></i>
        </a></li>
</ul>
                </div>
                <div class="tab-pane" id="tabEmbed">
                    <h4><span class="glyphicon glyphicon-share"></span> Share Video (Iframe):     <button id="getButtontCopyToClipboard5ff6f5a735c8b" class="btn btn-default btn-sm btn-xs pull-right" data-toggle="tooltip" data-placement="left" title="" data-original-title="Copy to Clipboard"><i class="fas fa-clipboard"></i> Copy to Clipboard</button>
    <script>
        var timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735c8b;
        $(document).ready(function () {
            $('#getButtontCopyToClipboard5ff6f5a735c8b').click(function () {
                clearTimeout(timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735c8b);
                $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').removeClass("fa-clipboard");
                $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').addClass("text-success");
                $('#getButtontCopyToClipboard5ff6f5a735c8b').addClass('bg-success');
                $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').addClass("fa-clipboard-check");
                timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735c8b = setTimeout(function () {
                    $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').removeClass("fa-clipboard-check");
                    $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').removeClass("text-success");
                    $('#getButtontCopyToClipboard5ff6f5a735c8b').removeClass('bg-success');
                    $('#getButtontCopyToClipboard5ff6f5a735c8b').find('i').addClass("fa-clipboard");
                }, 3000);
                copyToClipboard($('#textAreaEmbed').val());
            })
        });
    </script>
    getButtontCopyToClipboard5ff6f5a735c8b</h4> 
                    <textarea class="form-control" style="min-width: 100%" rows="5" id="textAreaEmbed" readonly="readonly">&lt;div class="embed-responsive embed-responsive-16by9"&gt;&lt;iframe width="640" height="360" style="max-width: 100%;max-height: 100%; border:none;" src="http://157.230.2.203/VOD/live/0/C?embed=1" frameborder="0" allowfullscreen="allowfullscreen" allow="autoplay" scrolling="no"&gt;iFrame is not supported!&lt;/iframe&gt;&lt;/div&gt;                    </textarea>
                    <h4><span class="glyphicon glyphicon-share"></span> Share Video (Object):     <button id="getButtontCopyToClipboard5ff6f5a735ca5" class="btn btn-default btn-sm btn-xs pull-right" data-toggle="tooltip" data-placement="left" title="" data-original-title="Copy to Clipboard"><i class="fas fa-clipboard"></i> Copy to Clipboard</button>
    <script>
        var timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735ca5;
        $(document).ready(function () {
            $('#getButtontCopyToClipboard5ff6f5a735ca5').click(function () {
                clearTimeout(timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735ca5);
                $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').removeClass("fa-clipboard");
                $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').addClass("text-success");
                $('#getButtontCopyToClipboard5ff6f5a735ca5').addClass('bg-success');
                $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').addClass("fa-clipboard-check");
                timeOutCopyToClipboard_getButtontCopyToClipboard5ff6f5a735ca5 = setTimeout(function () {
                    $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').removeClass("fa-clipboard-check");
                    $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').removeClass("text-success");
                    $('#getButtontCopyToClipboard5ff6f5a735ca5').removeClass('bg-success');
                    $('#getButtontCopyToClipboard5ff6f5a735ca5').find('i').addClass("fa-clipboard");
                }, 3000);
                copyToClipboard($('#textAreaEmbedObject').val());
            })
        });
    </script>
    getButtontCopyToClipboard5ff6f5a735ca5</h4>
                    <textarea class="form-control" style="min-width: 100%" rows="5" id="textAreaEmbedObject" readonly="readonly">&lt;div class="embed-responsive embed-responsive-16by9"&gt;&lt;object width="640" height="360"&gt;&lt;param name="movie" value="http://157.230.2.203/VOD/live/0/C?embed=1"&gt;&lt;/param&gt;&lt;param name="allowFullScreen" value="true"&gt;&lt;/param&gt;&lt;param name="allowscriptaccess" value="always"&gt;&lt;/param&gt;&lt;embed src="http://157.230.2.203/VOD/live/0/C?embed=1" allowscriptaccess="always" allowfullscreen="true" width="640" height="360"&gt;&lt;/embed&gt;&lt;/object&gt;&lt;/div&gt;                    </textarea>
                </div>
                                    <div class="tab-pane" id="tabEmail">
                                                    <form class="well form-horizontal" action="http://157.230.2.203/VOD/sendEmail" method="post" id="contact_form">
                                <fieldset>
                                    <!-- Text input-->
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">E-mail</label>
                                        <div class="col-md-8 inputGroupContainer">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="glyphicon glyphicon-envelope"></i></span>
                                                <input name="email" placeholder="E-mail Address" class="form-control" type="text">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Text area -->

                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Message</label>
                                        <div class="col-md-8 inputGroupContainer">
                                            <div class="input-group">
                                                <span class="input-group-addon"><i class="glyphicon glyphicon-pencil"></i></span>
                                                <textarea class="form-control" name="comment" placeholder="Message">I would like to share this video with you: http://157.230.2.203/VOD/live/0/C</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-md-4 control-label">Type the code</label>
                                        <div class="col-md-8 inputGroupContainer">
                                            <div class="input-group">
                                                <span class="input-group-addon"><img src="http://157.230.2.203/VOD/captcha?1610020263" id="captcha"></span>
                                                <span class="input-group-addon"><span class="btn btn-xs btn-success" id="btnReloadCapcha"><span class="glyphicon glyphicon-refresh"></span></span></span>
                                                <input name="captcha" placeholder="Type the code" class="form-control" type="text" style="height: 60px;" maxlength="5" id="captchaText">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Button -->
                                    <div class="form-group">
                                        <label class="col-md-4 control-label"></label>
                                        <div class="col-md-8">
                                            <button type="submit" class="btn btn-primary">Send <span class="glyphicon glyphicon-send"></span></button>
                                        </div>
                                    </div>

                                </fieldset>
                            </form>
                            <script>
                                $(document).ready(function () {
                                    $('#btnReloadCapcha').click(function () {
                                        $('#captcha').attr('src', 'http://157.230.2.203/VOD/captcha?' + Math.random());
                                        $('#captchaText').val('');
                                    });
                                    $('#contact_form').submit(function (evt) {
                                        evt.preventDefault();
                                        modal.showPleaseWait();
                                        $.ajax({
                                            url: 'http://157.230.2.203/VOD/objects/sendEmail.json.php',
                                            data: $('#contact_form').serializeArray(),
                                            type: 'post',
                                            success: function (response) {
                                                modal.hidePleaseWait();
                                                if (!response.error) {
                                                    avideoAlert("Congratulations!", "Your message has been sent!", "success");
                                                } else {
                                                    avideoAlert("Your message could not be sent!", response.error, "error");
                                                }
                                                $('#btnReloadCapcha').trigger('click');
                                            }
                                        });
                                        return false;
                                    });
                                });
                            </script>
                                            </div>

                                </div>
        </div>
    </div>
</div>   
                                <div class="row">

                                    <div class="col-lg-12 col-sm-12 col-xs-12 extraVideos nopadding"></div>
                                </div>
                            </div>
                        </div>
                    </div>
  }
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
                                        <button class="btn btn-m" style=" background-color:#3F3F3F;"><i class="fas fa-check-circle"></i>Subscribe</button>
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
                    <div class="<?php echo $modeYoutubeBottomClass2; ?> rightBar" id="yptRightBar">
                        <div class="list-group-item ">
                            <?php
                            echo $sideAd;
                            ?>
                        </div>
                    </div>
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
