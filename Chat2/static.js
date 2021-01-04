var yptchat2LastWidth = 0;
var yptchat2LastWidthTimeout = 0;
var yptchat2LastWidthNow = 0;
var yptchat2prependTo = "";
$(function () {
    if ($(window).width() <= 769) {
        chat2appendTo("#modeYoutubeTop");
    } else {
        chat2prependTo("#yptRightBar");
    }
    // this is because a bug on mobile when pops up the virtual keyboard, fires the resize
    yptchat2LastWidthNow = $(window).width();
    yptchat2LastWidth = yptchat2LastWidthNow;
    $(window).resize(function () {
        var yptchat2LastWidthNow = $(window).width();
        if (yptchat2LastWidth !== yptchat2LastWidthNow) {
            yptchat2LastWidth = yptchat2LastWidthNow;
            clearTimeout(yptchat2LastWidthTimeout);
            yptchat2LastWidthTimeout = setTimeout(function () {
                if ($(window).width() <= 769) {
                    chat2appendTo("#modeYoutubeTop");
                } else {
                    chat2prependTo("#yptRightBar");
                }
            }, 500);
        }
    });

    setInterval(function(){fixChat2Height();}, 500);
});
function chat2prependTo(id) {
    if (yptchat2prependTo !== id) {
        yptchat2prependTo = id;
        $("#yptchat2").prependTo(id);
    }
}
function chat2appendTo(id) {
    if (yptchat2prependTo !== id) {
        yptchat2prependTo = id;
        $("#yptchat2").appendTo(id);
    }
}
function fixChat2Height() {
    if ($(window).width() <= 769) {
        var h1 = $('#mvideo').height();
        var h2 = $(window).height();
        $("#yptchat2").css({"max-height": (h2 - h1 - 60)});
    }
}