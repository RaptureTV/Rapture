<?php
global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../../videos/configuration.php';
}
if (!User::isAdmin()) {
    header("Location: {$global['webSiteRootURL']}?error=" . __("You can not do this"));
    exit;
}
?>


<div class="panel panel-default">
    <div class="panel-heading">
        <i class="fas fa-cog"></i> <?php echo __("Configurations"); ?>
        <a href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/standAlone/instructions.php" class="btn btn-info btn-sm btn-xs pull-right"><i class="fas fa-info-circle"></i> Instructions</a>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-default ">
                    <div class="panel-heading"><i class="far fa-plus-square"></i> <?php echo __("Create"); ?></div>
                    <div class="panel-body">
                        <form id="panelChat_channelsForm">
                            <div class="row">
                                <input type="hidden" name="id" id="Chat_channelsid" value="" >
                                <div class="form-group col-sm-12">
                                    <label for="Chat_channelsname"><?php echo __("Name"); ?>:</label>
                                    <input type="text" id="Chat_channelsname" name="name" class="form-control input-sm" placeholder="<?php echo __("Name"); ?>" required="true">
                                </div>
                                <div class="form-group col-sm-12">
                                    <label for="Chat_channelsurl"><?php echo __("Url"); ?>:</label>
                                    <input type="text" id="Chat_channelsurl" name="url" class="form-control input-sm" placeholder="<?php echo __("Url"); ?>" required="true">
                                </div>
                                <div class="form-group col-sm-12">
                                    <label for="Chat_channelsusers_id"><?php echo __("Channel"); ?>:</label>
                                    <select class="form-control input-sm" name="users_id" id="Chat_channelsusers_id">
                                        <option value="0"><?php echo __("All Channels"); ?></option>
                                        <?php
                                        $options = Chat_channels::getAllUsers();
                                        foreach ($options as $value) {
                                            echo '<option value="' . $value['id'] . '">' . $value['user'] . ' - ' . User::getNameIdentificationById($value['id']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group col-sm-12">
                                    <label for="status"><?php echo __("Status"); ?>:</label>
                                    <select class="form-control input-sm" name="status" id="Chat_channelsstatus">
                                        <option value="a"><?php echo __("Active"); ?></option>
                                        <option value="i"><?php echo __("Inactive"); ?></option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-12">
                                    <div class="btn-group pull-right">
                                        <span class="btn btn-success" id="newChat2Link"><i class="fas fa-plus"></i> <?php echo __("New"); ?></span>
                                        <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> <?php echo __("Save"); ?></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="panel panel-default ">
                    <div class="panel-heading">
                        <i class="fas fa-edit"></i> <?php echo __("Edit"); ?>
                        <a href="<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/listRooms.php" class="btn btn-primary btn-sm btn-xs pull-right"><i class="far fa-comment-dots"></i> Chat Rooms</a>
                    </div>
                    <div class="panel-body">
                        <table id="Chat_channelsTable" class="display table table-bordered table-responsive table-striped table-hover table-condensed" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo __("Name"); ?></th>
                                    <th><?php echo __("Url"); ?></th>
                                    <th><?php echo __("Status"); ?></th>
                                    <th><?php echo __("Channel"); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo __("Name"); ?></th>
                                    <th><?php echo __("Url"); ?></th>
                                    <th><?php echo __("Status"); ?></th>
                                    <th><?php echo __("Channel"); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="Chat_channelsbtnModelLinks" style="display: none;">
    <div class="btn-group pull-right">
        <button href="" class="instructions_Chat_channels btn btn-info btn-xs">
            <i class="fas fa-info-circle"></i>
        </button>
        <button href="" class="edit_Chat_channels btn btn-default btn-xs">
            <i class="fa fa-edit"></i>
        </button>
        <button href="" class="delete_Chat_channels btn btn-danger btn-xs">
            <i class="fa fa-trash"></i>
        </button>
    </div>
</div>

<script type="text/javascript">
    function clearChat_channelsForm() {
        $('#Chat_channelsid').val('');
        $('#Chat_channelsname').val('');
        $('#Chat_channelsurl').val('');
        $('#Chat_channelsusers_id').val('');
        $('#Chat_channelsstatus').val('');
    }
    $(document).ready(function () {
        $('#addChat2Btn').click(function () {
            $.ajax({
                url: '<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/View/addChat_channelsVideo.php',
                data: $('#panelChat_channelsForm').serialize(),
                type: 'post',
                success: function (response) {
                    if (response.error) {
                        avideoAlert("<?php echo __("Sorry!"); ?>", response.msg, "error");
                    } else {
                        avideoAlert("<?php echo __("Congratulations!"); ?>", "<?php echo __("Your register has been saved!"); ?>", "success");
                        $("#panelChat_channelsForm").trigger("reset");
                    }
                    clearChat_channelsForm();
                    tableVideos.ajax.reload();
                    modal.hidePleaseWait();
                }
            });
        });
        var Chat_channelstableVar = $('#Chat_channelsTable').DataTable({
            "ajax": "<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/View/Chat_channels/list.json.php",
            "columns": [
                {"data": "id"},
                {"data": "name"},
                {"data": "url"},
                {"data": "status"},
                {"data": "channelName"},
                {
                    sortable: false,
                    data: null,
                    defaultContent: $('#Chat_channelsbtnModelLinks').html()
                }
            ],
            select: true,
        });
        $('#newChat_channels').on('click', function (e) {
            e.preventDefault();
            $('#panelChat_channelsForm').trigger("reset");
            $('#Chat_channelsid').val('');
        });
        $('#panelChat_channelsForm').on('submit', function (e) {
            e.preventDefault();
            modal.showPleaseWait();
            $.ajax({
                url: '<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/View/Chat_channels/add.json.php',
                data: $('#panelChat_channelsForm').serialize(),
                type: 'post',
                success: function (response) {
                    if (response.error) {
                        avideoAlert("<?php echo __("Sorry!"); ?>", response.msg, "error");
                    } else {
                        avideoAlert("<?php echo __("Congratulations!"); ?>", "<?php echo __("Your register has been saved!"); ?>", "success");
                        $("#panelChat_channelsForm").trigger("reset");
                    }
                    Chat_channelstableVar.ajax.reload();
                    $('#Chat_channelsid').val('');
                    modal.hidePleaseWait();
                }
            });
        });
        $('#Chat_channelsTable').on('click', 'button.delete_Chat_channels', function (e) {
            e.preventDefault();
            var tr = $(this).closest('tr')[0];
            var data = Chat_channelstableVar.row(tr).data();
            swal({
                title: "<?php echo __("Are you sure?"); ?>",
                text: "<?php echo __("You will not be able to recover this action!"); ?>",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                    .then(function(willDelete) {
                        if (willDelete) {
                            modal.showPleaseWait();
                            $.ajax({
                                type: "POST",
                                url: "<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/View/Chat_channels/delete.json.php",
                                data: data

                            }).done(function (resposta) {
                                if (resposta.error) {
                                    avideoAlert("<?php echo __("Sorry!"); ?>", resposta.msg, "error");
                                }
                                Chat_channelstableVar.ajax.reload();
                                modal.hidePleaseWait();
                            });
                        } else {

                        }
                    });
        });
        $('#Chat_channelsTable').on('click', 'button.edit_Chat_channels', function (e) {
            e.preventDefault();
            var tr = $(this).closest('tr')[0];
            var data = Chat_channelstableVar.row(tr).data();
            $('#Chat_channelsid').val(data.id);
            $('#Chat_channelsname').val(data.name);
            $('#Chat_channelsurl').val(data.url);
            $('#Chat_channelsusers_id').val(data.users_id);
            $('#Chat_channelsstatus').val(data.status);
        });
        $('#Chat_channelsTable').on('click', 'button.instructions_Chat_channels', function (e) {
            e.preventDefault();
            var tr = $(this).closest('tr')[0];
            var data = Chat_channelstableVar.row(tr).data();
            $('#Chat_channelsid').val(data.id);
            document.location = "<?php echo $global['webSiteRootURL']; ?>plugin/Chat2/standAlone/instructions.php?id="+data.id;
        });
    });
</script>
