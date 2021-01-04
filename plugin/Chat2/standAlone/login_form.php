<style>

    .form-signin {
        width: 100%;
        max-width: 330px;
        padding: 15px;
        margin: 0 auto;
    }
    .form-signin .checkbox {
        font-weight: 400;
    }
    .form-signin .form-control {
        position: relative;
        box-sizing: border-box;
        height: auto;
        padding: 10px;
        font-size: 16px;
    }
    .form-signin .form-control:focus {
        z-index: 2;
    }
    .form-signin input[type="email"] {
        margin-bottom: -1px;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 0;
    }
    .form-signin input[type="password"] {
        margin-bottom: 10px;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }
</style>
<div class="row">
    <div class="col-xs-12" id="divChatInput">
        <button onclick="$('#loginForm').modal('show');" class="btn btn-block btn-default btn-xs"><?php echo __("Login"); ?></a>
    </div>
</div>
<div id="loginForm" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo __("Login"); ?></h4>
            </div>
            <div class="modal-body">
                <form class="form-signin" method="post">
                    <img class="mb-4" src="<?php echo $global['webSiteRootURL']; ?>videos/userPhoto/logo.png" alt="">
                    <h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
                    <label for="inputUser" class="sr-only">User address</label>
                    <input type="text" id="inputUser" name="inputUser" class="form-control" placeholder="User address" required autofocus>
                    <label for="inputPassword" class="sr-only">Password</label>
                    <input type="password" id="inputPassword" name="inputPassword" class="form-control" placeholder="Password" required>
                    <button class="btn btn-lg btn-success btn-block" type="submit"><span class="fas fa-sign-in-alt"></span> <?php echo __("Sign in"); ?></button>

                    <?php
                    if (empty($advancedCustomUser->disableNativeSignUp)) {
                        ?>
                        <a href="<?php echo $global['webSiteRootURL']; ?>signUp?redirectUri=<?php echo urlencode(getSelfURI()); ?>&siteRedirectUri=<?php echo urlencode(getSelfURI()); ?>" class="btn btn-default btn-block" ><span class="fa fa-user-plus"></span> <?php echo __("Sign up"); ?></a>
                        <?php
                    }
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        $("#loginForm form").submit(function (e) {
            e.preventDefault(); // avoid to execute the actual submit of the form.
            modal.showPleaseWait();
            $.ajax({
                type: "POST",
                url: "<?php echo $global['webSiteRootURLStandAlone']; ?>standAlone/login.json.php",
                data: $("#loginForm form").serialize(), // serializes the form's elements.
                success: function (response) {
                    if (!response.isLogged) {
                        modal.hidePleaseWait();
                        if (response.error) {
                            avideoAlert("<?php echo __("Sorry!"); ?>", response.error, "error");
                        } else {
                            avideoAlert("<?php echo __("Sorry!"); ?>", "<?php echo __("Your user or password is wrong!"); ?>", "error");
                        }
                        if (response.isCaptchaNeed) {
                            $("#btnReloadCapcha").trigger('click');
                            $('#captchaForm').slideDown();
                        }
                    } else {
                        setTimeout(function(){modal.hidePleaseWait();},2000);
                        var url = window.location.href;
                        if (url.indexOf('?') > -1) {
                            url += '&user=' + response.user + '&pass=' + response.pass
                        } else {
                            url += '?user=' + response.user + '&pass=' + response.pass
                        }
                        window.location.href = url;
                        //location.reload();
                    }
                }
            });
        });

    });
</script>