var messagesCount = 0;
var autoscrollTimeout;
var myApp;

if (typeof nl2br !== "function") {
    function nl2br(str, is_xhtml) {
        if (typeof str === 'undefined' || str === null) {
            return '';
        }
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
    }
}

$(function () {
    $('.badge').hide();
    $("#chatInput").emojioneArea({
        events: {
            keyup: function (editor, event) {
                // catches everything but enter
                if (event.which == 13) {
                    $('#submitChat').trigger('click');
                } else {
                    //alert("Key pressed: " + event.which);
                }
            }
        }
    });

    listGroupItemClick();

    $('#submitChat').click(function () {
        submitMessage($('#chatInput').data("emojioneArea").getText(), to_users_id);
        $('#chatInput').data("emojioneArea").setText('');
    });

    $('#roomButton').click(function () {
        if ($('#divRooms').hasClass('divRoomsShow')) {
            $('#divRooms').removeClass('divRoomsShow');
        } else {
            $('#divRooms').addClass('divRoomsShow');
        }
    });

    getChatTotalNew();
    $('[data-toggle="tooltip"], [data-toggle="dropdown"]').tooltip();
    $('#onlineList li:first-child').trigger("click");
});

function isScrolledIntoView(elem) {
    var docViewTop = $(window).scrollTop();
    var docViewBottom = docViewTop + $(window).height();

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

function listGroupItemClick() {
    $('.list-group-item').unbind();
    $('.list-group-item').click(function () {
        $('.list-group-item').removeClass('active');
        $(this).addClass('active');
        $('#talkToImage').attr('src', $(this).find('img').attr('src'));
        $('#talkToNameId').html($(this).find('.NameIdentification').html());
        to_users_id = $(this).attr('to_users_id');
        clearChat();
        if ($(this).hasClass('list-group-item-info')) {
            getRoom(room_users_id, 0, true);
        } else {
            getChat(to_users_id, 0, true);
        }
    });
}

function clearChat() {
    $('#chatScreen').empty();
}

function addMessage(id, from_users_id, name, message, messageFooter, isMe, prepend, isBanned) {
    if(typeof message !== 'string'){
        return false;
    }
    message = message.trim();
    if (message === '') {
        return false;
    }
    // item already exists
    if ($('#bubble' + id).length) {
        return false;
    }
    responseFound = true;
    timesWithoutNewMessages = 0;
    getChatTimeOut = 3000;
    var template;
    if (isMe) {
        template = $('#me-bubble').clone();
        name = "";
    } else {
        template = $('#them-bubble').clone();
        if (from_users_id) {
            if (!$("#chatItem" + from_users_id).length) {
                var cssClass = "";
                if (isBanned) {
                    cssClass = "list-group-item-danger";
                }
                var element = '<li  class="list-group-item ' + cssClass + '" to_users_id="' + from_users_id + '" id="chatItem' + from_users_id + '" channelLink="">';
                element += '<img src="' + webSiteRootURL + 'view/img/userSilhouette.jpg" class="img img-circle img-responsive pull-left">';
                element += '<span class="NameIdentification hidden-xs">' + name + '</span>';
                element += '<span class="badge" style="display: none;">0</span></li> ';
                $('#onlineList').append(element);
                listGroupItemClick();
            }
            name = "<a href='#' onclick='$(\"#chatItem" + from_users_id + "\").trigger(\"click\");'>" + name + " : </a>";
        }
    }
    $(template).attr('id', 'bubble' + id);
    $(template).attr('message_id', id);
    $(template).attr('users_id', from_users_id);
    $(template).addClass('bubble' + from_users_id);
    if (isBanned) {
        $(template).addClass('banned');
    }
    $(template).find(".messageNameId").html(name);
    $(template).find(".message").html(message);
    // $(template).find(".messageFooter").html(messageFooter);
    if (prepend) {
        $('#chatScreen').prepend(template);
    } else {
        $('#chatScreen').append(template);
        chatAutoscroll();
    }
    $('#bubble' + id).slideDown('fast');
    messagesCount++;
    return true;
}

function chatAutoscroll() {
    clearTimeout(autoscrollTimeout);
    autoscrollTimeout = setTimeout(function () {
        _chatAutoscroll(false);
    }, 200);
}

function _chatAutoscroll(force) {
    //console.log('chatAutoscroll');
    if (force || $('#autoscroll').is(':checked') || isScrolledIntoView($('#chatScreen').children().last())) {
        $('#chatPanel .panel-body').animate({scrollTop: $('#chatPanel .panel-body').prop("scrollHeight")}, 500);
        $('#scrollDownBtn').fadeOut();
    } else {
        $('#scrollDownBtn').fadeIn();
    }
}

function getTime() {
    date = new Date();
    return date.getHours() + ":" + ("00" + date.getMinutes()).slice(-2) + ":" + ("00" + date.getSeconds()).slice(-2) + " - "
            + date.getDate() + " " + (date.getMonth() + 1) + " " + date.getFullYear();
}

function submitMessage(message, to_users_id) {
    var url = webSiteRootURLChat2 + 'sendMessage.json.php?users_id=' + to_users_id + credentialsE;
    if (!to_users_id || to_users_id === '0') {
        url = webSiteRootURLChat2 + 'sendMessage.json.php?room_users_id=' + room_users_id + credentialsE;
    }
    $.ajax({
        url: url,
        data: {
            message: message
        },
        type: 'post',
        success: function (response) {
            if (!response.error) {
                addMessage(response.id, 0, response.name, response.message, response.messageFooter, true, false);
                timesWithoutNewMessages = 0;
            } else {
                $.toast({
                    heading: 'Error',
                    text: response.errorMsg,
                    icon: 'error'
                });
            }
            $('#chatInput').focus();
        }
    });
}

function getChat(to_users_id, lower_then_id, pleaseWait) {
    if (!to_users_id) {
        return false;
    }
    if (pleaseWait && users_id) {
        $('#pleaseWaitDialog').modal('show');
    }
    $('#chatScreen').removeClass('isRoom');
    $('#chatScreen').addClass('isChat');
    $('.hiddenOnRoom').show();
    $('.hiddenOnChat').hide();
    if ($('#chatItem' + to_users_id).hasClass('list-group-item-danger')) {
        $('#banFromChat').hide();
        $('#removeBanFromChat').show();
    } else {
        $('#banFromChat').show();
        $('#removeBanFromChat').hide();
    }

    if (users_id) {
        $.ajax({
            url: webSiteRootURLChat2 + 'getChat.json.php?to_users_id=' + to_users_id + '&lower_then_id=' + lower_then_id + credentialsE,
            success: function (response) {
                if (!response.error) {
                    var messages = response.messages;
                    if (!lower_then_id || lower_then_id == '0') {
                        messages = messages.reverse();
                    }
                    $.each(messages, function (i, item) {
                        addMessage(item.id, 0, item.name, item.message, item.created, item.isMe, (lower_then_id && lower_then_id != '0'), item.isBanned);
                    });
                    if (pleaseWait) {
                        $('#pleaseWaitDialog').modal('hide');
                    }
                    changeBadge(to_users_id, 0);
                } else {
                    $.toast({
                        heading: 'Error',
                        text: response.errorMsg,
                        icon: 'error'
                    });
                }
            }
        });
    }
}

function getRoom(room_users_id, lower_then_id, pleaseWait) {
    if (!room_users_id) {
        return false;
    }
    if (pleaseWait) {
        $('#pleaseWaitDialog').modal('show');
    }
    $('#chatScreen').addClass('isRoom');
    $('#chatScreen').removeClass('isChat');
    $('.list-group-item').removeClass('active');
    $('#channelItem').addClass('active');
    $('.hiddenOnRoom').hide();
    $('.hiddenOnChat').show();
    to_users_id = 0;
    $.ajax({
        url: webSiteRootURLChat2 + 'getRoom.json.php?room_users_id=' + room_users_id + '&lower_then_id=' + lower_then_id + credentialsE,
        success: function (response) {
            if (!response.error) {
                var messages = response.messages;
                if (!lower_then_id || lower_then_id == '0') {
                    messages = messages.reverse();
                }
                $.each(messages, function (i, item) {
                    addMessage(item.id, item.from_users_id, item.name, item.message, item.created, item.isMe, (lower_then_id && lower_then_id != '0'), item.isBanned);
                });
                if (pleaseWait) {
                    $('#pleaseWaitDialog').modal('hide');
                }
                changeBadge(0, 0);
            } else {
                $.toast({
                    heading: 'Error',
                    text: response.errorMsg,
                    icon: 'error'
                });
            }
        }
    });
}

function loadMore() {
    var bubbles = $('#chatScreen').find('.bubble');
    if (!bubbles) {
        console.log('Bubble not found');
        return false;
    }
    var lower_then_id = $(bubbles[0]).attr('message_id');
    if (!lower_then_id && lower_then_id != '0') {
        console.log('message_id not found');
        return false;
    }
    if (to_users_id) {
        getChat(to_users_id, lower_then_id, true);
    } else {
        getRoom(room_users_id, lower_then_id, true);
    }
}

var start_time;
var request_time;
var getChatTotalNewTimeout;
var getChatTimeOut = 3000;
var timesWithoutNewMessages = 0;

var responseFound = false;

function getChatTotalNew() {
    start_time = new Date().getTime();
    $.ajax({
        url: webSiteRootURLChat2 + 'getChatTotalNew.json.php?room_users_id=' + room_users_id + '&to_users_id=' + to_users_id + '&getChatTimeOut=' + getChatTimeOut + credentialsE,
        success: function (response) {
            request_time = new Date().getTime() - start_time;
            if (!response.error) {
                responseFound = false;
                for (id in response.total) {
                    if (id == to_users_id && response.total[id] > 0) {
                        if (id == 0 && (room_users_id && room_users_id !== '0')) {
                            getRoom(room_users_id, 0, false);
                        } else {
                            $("#chatItem" + to_users_id).addClass('active');
                            getChat(to_users_id, 0, false);
                        }
                    } else {
                        var b = response.total[id];
                        if (response.total[id] > 10) {
                            b = "10+";
                        }
                        changeBadge(id, b);
                    }
                    if (response.status && typeof response.status[id] != 'undefined') {
                        changeStatus(id, response.status[id]);
                    } else if (response.status && typeof response.status[id] == 'undefined') {
                        changeStatus(id, 'f');
                    }
                }
                if (responseFound) {
                    timesWithoutNewMessages = 0;
                    getChatTimeOut = 3000;
                } else {
                    timesWithoutNewMessages++;
                    getChatTimeOut = 3000 + (timesWithoutNewMessages * 1000);
                }
                // if is responding, wait max 30 sec
                if (getChatTimeOut > 30000) {
                    console.log("Chat2: timout > 300000 ");
                    getChatTimeOut = 30000;
                }
            } else {
                console.log("Chat2: error ");
                getChatTimeOut = 10000;
                console.log(response.errorMsg);
            }

            // if takes to long to respond increase the time
            if (request_time > getChatTimeOut) {
                console.log("Chat2: request_time > getChatTimeOut ");
                getChatTimeOut = request_time + (timesWithoutNewMessages * 1000);
            }
            // wait max 1 min to request again
            if (getChatTimeOut > 60000) {
                getChatTimeOut = 60000;
            }
            clearTimeout(getChatTotalNewTimeout);
            console.log("Chat2: Next request in " + getChatTimeOut + " ms timesWithoutNewMessages = "+timesWithoutNewMessages+" ");
            modal.hidePleaseWait(); // this is to make sure we will not have any hanging wait bar
            getChatTotalNewTimeout = setTimeout(function () {
                getChatTotalNew();
            }, getChatTimeOut);
        }
    });
}

function changeBadge(id, total) {
    var oldTotal = $('#chatItem' + id).find('.badge').text();
    var elemId = '#chatItem' + id;
    if (id == 0) {
        elemId = '#channelItem';
        oldTotal = $(elemId).find('.badge').text();
    }

    $(elemId).find('.badge').text(total);
    if (total) {
        if (oldTotal != total) {
            pling();
        }
        $(elemId).find('.badge').fadeIn();
    } else {
        $(elemId).find('.badge').fadeOut();
    }
}

function changeStatus(id, status) {
    if (status == 'o') {
        $('#chatItem' + id).removeClass('offline');
        $('#chatItem' + id).appendTo('#onlineList');
    } else {
        $('#chatItem' + id).addClass('offline');
        $('#chatItem' + id).prependTo('#offlineList');
    }
}

function pling() {
    var audio = new Audio(webSiteRootURLChat2 + 'pling.mp3');
    audio.play();
}

function minimize() {
    $('#divChatRow').slideUp('fast', function () {
        $('#divOpenChat').fadeIn();
    });
    window.parent.minimizeChat2();
    Cookies.set('yptChat2Minimized' + users_id, true, {
        path: '/',
        expires: 365
    });
}

function maximize() {
    $('#divOpenChat').hide();
    $('#divChatRow').slideDown('fast', function () {
    });
    window.parent.maximizeChat2();
    Cookies.set('yptChat2Minimized' + users_id, false, {
        path: '/',
        expires: 365
    });
}

function goToChannel(users_id) {
    var url = webSiteRootURL + "channel/" + users_id;
    if (!users_id) {
        url = webSiteRootURL + "channel/" + room_users_id;
    }
    var win = window.open(url, '_blank');
    win.focus();
}

function banFromChat(users_id) {
    $('#pleaseWaitDialog').modal('show');
    $.ajax({
        url: webSiteRootURLChat2 + 'actions.json.php?room_users_id=' + room_users_id + '&ban=' + users_id + credentialsE,
        success: function (response) {
            if (!response.error) {
                $.toast("User Banned");
                $('#chatItem' + users_id).addClass('list-group-item-danger');
                $('.bubble' + users_id).addClass('banned');
            } else {
                $.toast({
                    heading: 'Error',
                    text: response.msg,
                    icon: 'error'
                });
            }
            $('#pleaseWaitDialog').modal('hide');
        }
    });
}

function removeBanFromChat(users_id) {
    $('#pleaseWaitDialog').modal('show');
    $.ajax({
        url: webSiteRootURLChat2 + 'actions.json.php?room_users_id=' + room_users_id + '&removeBan=' + users_id + credentialsE,
        success: function (response) {
            if (!response.error) {
                $.toast("User Banned Removed");
                $('#chatItem' + users_id).removeClass('list-group-item-danger');
                $('.bubble' + users_id).removeClass('banned');
            } else {
                $.toast({
                    heading: 'Error',
                    text: response.msg,
                    icon: 'error'
                });
            }
            $('#pleaseWaitDialog').modal('hide');
        }
    });
}

function removeMessage(chat_messages_id) {

    swal({
        title: "Are you sure?",
        text: "You will not be able to recover this action!",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
            .then(function(willDelete) {
                if (willDelete) {
                    $('#pleaseWaitDialog').modal('show');
                    $.ajax({
                        url: webSiteRootURLChat2 + 'actions.json.php?room_users_id=' + room_users_id + '&chat_messages_id=' + chat_messages_id + credentialsE,
                        success: function (response) {
                            if (!response.error) {
                                $.toast("Message Removed");
                                $('#bubble' + chat_messages_id).fadeOut();
                            } else {
                                $.toast({
                                    heading: 'Error',
                                    text: response.msg,
                                    icon: 'error'
                                });
                            }
                            $('#pleaseWaitDialog').modal('hide');
                        }
                    });
                } else {

                }
            });

}

$(function () {
    var ul = $('#upload ul');

    $('#drop a').click(function () {
        // Simulate a click on the file input button
        // to show the file browser dialog
        $(this).parent().find('input').click();
    });

    // Prevent the default action when a file is dropped on the window
    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

});

// Helper function that formats the file sizes
function formatFileSize(bytes) {
    if (typeof bytes !== 'number') {
        return '';
    }

    if (bytes >= 1000000000) {
        return (bytes / 1000000000).toFixed(2) + ' GB';
    }

    if (bytes >= 1000000) {
        return (bytes / 1000000).toFixed(2) + ' MB';
    }

    return (bytes / 1000).toFixed(2) + ' KB';
}
function copyChatToClipboard(text) {

    $('#elementChatToCopy').css({'top': mouseY, 'left': mouseX}).fadeIn('slow');
    $('#elementChatToCopy').val(text);
    $('#elementChatToCopy').focus();
    $('#elementChatToCopy').select();
    document.execCommand('copy');
    $.toast({
        heading: 'Success',
        text: "Copied!",
        icon: 'success'
    });
}