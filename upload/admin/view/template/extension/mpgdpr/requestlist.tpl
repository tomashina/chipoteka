<?php echo $header; ?><?php echo $column_left; ?>
<div id="content" class="mp-content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="button" class="btn btn-danger" onclick="confirm('<?php echo $text_confirm; ?>') ? $('#form-mpgdpr_requestlist').submit() : false;"><i class="fa fa-trash-o"></i> <?php echo $button_delete; ?></button>
      </div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-list"></i> <?php echo $text_list; ?></h3>
        <div class="pull-right mpcollapse collapsed" data-toggle="collapse" data-target="#filter_collapse" aria-expanded="false" aria-controls="filter_collapse"><i class="fa fa-chevron-up" data-up="fa fa-chevron-up" data-down="fa fa-chevron-down"></i></div>
      </div>
      <div class="panel-body">
        <div id="filter_collapse" aria-expanded="false" class="well collapse list-filters">
          <div class="row">
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-request_id"><?php echo $entry_request_id; ?></label>
                <input type="text" name="filter_request_id" value="<?php echo $filter_request_id; ?>" placeholder="<?php echo $entry_request_id; ?>" id="input-request_id" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-type"><?php echo $entry_type; ?></label>
                <select name="filter_type" id="input-type" class="form-control">
                  <option value="*"></option>
                  <?php foreach ($request_types as $request_type) {
                  $sel = '';
                  if ($request_type['code']==$filter_type) {
                  $sel = 'selected="selected"';
                  }
                  ?>
                  <option value="<?php echo $request_type['code']; ?>" <?php echo $sel; ?>><?php echo $request_type['value']; ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group">
                <label class="control-label" for="input-email"><?php echo $entry_email; ?></label>
                <input type="text" name="filter_email" value="<?php echo $filter_email; ?>" placeholder="<?php echo $entry_email; ?>" id="input-email" class="form-control" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-useragent"><?php echo $entry_useragent; ?></label>
                <input type="text" name="filter_useragent" value="<?php echo $filter_useragent; ?>" placeholder="<?php echo $entry_useragent; ?>" id="input-useragent" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-server_ip"><?php echo $entry_server_ip; ?></label>
                <input type="text" name="filter_server_ip" value="<?php echo $filter_server_ip; ?>" placeholder="<?php echo $entry_server_ip; ?>" id="input-server_ip" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-client_ip"><?php echo $entry_client_ip; ?></label>
                <input type="text" name="filter_client_ip" value="<?php echo $filter_client_ip; ?>" placeholder="<?php echo $entry_client_ip; ?>" id="input-client_ip" class="form-control" />
              </div>
            </div>
            <div class="col-sm-4">
              <div class="form-group">
                <label class="control-label" for="input-date_start"><?php echo $entry_date_start; ?></label>
                <div class="input-group date">
                  <input type="text" name="filter_date_start" value="<?php echo $filter_date_start; ?>" placeholder="<?php echo $entry_date_start; ?>" data-date-format="YYYY-MM-DD" id="input-date_start" class="form-control" /><span class="input-group-btn"><button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button></span>
                </div>
              </div>
              <div class="form-group">
                <label class="control-label" for="input-date_end"><?php echo $entry_date_end; ?></label>
                <div class="input-group date">
                  <input type="text" name="filter_date_end" value="<?php echo $filter_date_end; ?>" placeholder="<?php echo $entry_date_end; ?>" data-date-format="YYYY-MM-DD" id="input-date_end" class="form-control" /><span class="input-group-btn"><button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button></span>
                </div>
              </div>
              <div class="form-group">
                <div class="row">
                  <div class="col-sm-6">
                    <label class="control-label" for="input-time_lap_value"><?php echo $entry_time_lap_value; ?></label>
                    <input type="text" name="filter_time_lap_value" value="<?php echo $filter_time_lap_value; ?>" placeholder="<?php echo $entry_time_lap_value; ?>" id="input-time_lap_value" class="form-control" />
                  </div>
                  <div class="col-sm-6">
                    <label class="control-label" for="input-time_lap"><?php echo $entry_time_lap; ?></label>
                    <select name="filter_time_lap" id="input-time_lap" class="form-control">
                      <option value="*"></option>
                      <option value="DAY" <?php if ($filter_time_lap=='DAY') { ?>selected="selected"<?php } ?>><?php echo $entry_days; ?></option>
                      <option value="WEEK" <?php if ($filter_time_lap=='WEEK') { ?>selected="selected"<?php } ?>><?php echo $entry_weeks; ?></option>
                      <option value="MONTH" <?php if ($filter_time_lap=='MONTH') { ?>selected="selected"<?php } ?>><?php echo $entry_months; ?></option>
                      <option value="YEAR" <?php if ($filter_time_lap=='YEAR') { ?>selected="selected"<?php } ?>><?php echo $entry_years; ?></option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-12">
              <div class="form-group text-right">
                <button type="button" id="button-refresh-filter" class="btn btn-danger refresh-filter"><i class="fa fa-refresh"></i> <?php echo $button_clear; ?></button>
                <button type="button" id="button-filter" class="btn btn-primary"><i class="fa fa-filter"></i> <?php echo $button_filter; ?></button>
              </div>
            </div>
          </div>
        </div>
        <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form-mpgdpr_requestlist">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" /></td>
                  <td class="text-left"><a href="<?php echo $sort_requestlist_id; ?>" <?php if ($sort == 'r.mpgdpr_requestlist_id') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_request_id; ?></a></td>
                  <td class="text-left"><a href="<?php echo $sort_requessttype; ?>" <?php if ($sort == 'r.requessttype') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_type; ?></a></td>
                  <!-- /*13 sep 2019 gdpr session starts*/ -->
                  <td class="text-left"><?php echo $column_custom_string; ?></td>
                  <!-- /*13 sep 2019 gdpr session ends*/ -->
                  <td class="text-left"><a href="<?php echo $sort_email; ?>" <?php if ($sort == 'r.email') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_email; ?></a></td>
                  <td class="text-left"><?php echo $column_other; ?></td>
                  <!-- // 01-05-2022: updation start -->
                  <td class="text-left"><?php echo $column_action; ?></td>
                  <!-- // 01-05-2022: updation end -->
                </tr>
              </thead>
              <tbody>
                <?php if ($requests) { ?>
                <?php foreach ($requests as $request) { ?>
                <tr id="mpgdpr_request<?php echo $request['mpgdpr_requestlist_id']; ?>">
                  <td class="text-center"><?php if (in_array($request['mpgdpr_requestlist_id'], $selected)) { ?>
                    <input type="checkbox" name="selected[]" value="<?php echo $request['mpgdpr_requestlist_id']; ?>" checked="checked" />
                    <?php } else { ?>
                    <input type="checkbox" name="selected[]" value="<?php echo $request['mpgdpr_requestlist_id']; ?>" />
                    <?php } ?></td>
                  <td class="text-left"><?php echo $request['mpgdpr_requestlist_id']; ?></td>
                  <td class="text-left"><?php echo $request['type']; ?></td>
                  <!-- /*13 sep 2019 gdpr session starts*/ -->
                  <td class="text-left"><?php echo $request['custom_string']; ?></td>
                  <!-- /*13 sep 2019 gdpr session ends*/ -->
                  <td class="text-left"><?php echo $request['email']; ?></td>
                  <td class="text-left">
                    <table class="table table-bordered table-hover">
                      <tr>
                        <td><?php echo $column_server_ip; ?></td>
                        <td><?php echo $request['server_ip']; ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $column_client_ip; ?></td>
                        <td><?php echo $request['client_ip']; ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $column_useragent; ?></td>
                        <td><?php echo $request['useragent']; ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $column_acceptlanguage; ?></td>
                        <td><?php echo $request['acceptlanguage']; ?></td>
                      </tr>
                      <!-- /*13 sep 2019 gdpr session starts*/ -->
                      <tr>
                        <td><a href="<?php echo $sort_date_added; ?>" <?php if ($sort == 'r.date_added') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_date; ?></a></td>
                        <td><?php echo $request['date_added']; ?></td>
                      </tr>
                      <!-- /*13 sep 2019 gdpr session ends*/ -->
                    </table>
                  </td>
                  <!-- // 01-05-2022: updation start -->
                  <td class="text-left"><button type="button" class="btn btn-danger mpgdpr_request-delete" data-id="<?php echo $request['mpgdpr_requestlist_id']; ?>"><i class="fa fa-trash-o" data-class="fa fa-trash-o"></i> <?php echo $button_delete; ?></button></td>
                  <!-- // 01-05-2022: updation end -->
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr>
                  <td class="text-center" colspan="10"><?php echo $text_no_results; ?></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </form>
        <div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
          <div class="col-sm-6 text-right"><?php echo $results; ?></div>
        </div>
      </div>
    </div>
  </div>
<script type="text/javascript"><!--
$('#button-filter').on('click', function() {
  var url = 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestlist&<?php echo $get_token; ?>=<?php echo $token; ?>';

  var filter_request_id = $('input[name=\'filter_request_id\']').val();

  if (filter_request_id) {
    url += '&filter_request_id=' + encodeURIComponent(filter_request_id);
  }

  var filter_type = $('select[name=\'filter_type\']').val();

  if (filter_type != '*') {
    url += '&filter_type=' + encodeURIComponent(filter_type);
  }

  var filter_email = $('input[name=\'filter_email\']').val();

  if (filter_email) {
    url += '&filter_email=' + encodeURIComponent(filter_email);
  }

  var filter_useragent = $('input[name=\'filter_useragent\']').val();

  if (filter_useragent) {
    url += '&filter_useragent=' + encodeURIComponent(filter_useragent);
  }

  var filter_server_ip = $('input[name=\'filter_server_ip\']').val();

  if (filter_server_ip) {
    url += '&filter_server_ip=' + encodeURIComponent(filter_server_ip);
  }

  var filter_client_ip = $('input[name=\'filter_client_ip\']').val();

  if (filter_client_ip) {
    url += '&filter_client_ip=' + encodeURIComponent(filter_client_ip);
  }

  var filter_date_start = $('input[name=\'filter_date_start\']').val();

  if (filter_date_start) {
    url += '&filter_date_start=' + encodeURIComponent(filter_date_start);
  }

  var filter_date_end = $('input[name=\'filter_date_end\']').val();

  if (filter_date_end) {
    url += '&filter_date_end=' + encodeURIComponent(filter_date_end);
  }

  var filter_time_lap = $('select[name=\'filter_time_lap\']').val();

  if (filter_time_lap != '*') {
    url += '&filter_time_lap=' + encodeURIComponent(filter_time_lap);
  }

  var filter_time_lap_value = $('input[name=\'filter_time_lap_value\']').val();

  if (filter_time_lap_value) {
    url += '&filter_time_lap_value=' + encodeURIComponent(filter_time_lap_value);
  }

  location = url;
});

$('#button-refresh-filter').on('click', function() {

  var url = 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestlist&<?php echo $get_token; ?>=<?php echo $token; ?>';

  location = url;
});
// 01-05-2022: updation start
$('.mpgdpr_request-delete').on('click', function() {

  if (confirm('<?php echo $text_confirm; ?>')) {
    var $this = $(this);
    var $i = $(this).find('i');
    var mpgdpr_requestlist_id = $(this).attr('data-id');

    $.ajax({
      url: 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestlist/deleteRequestList&<?php echo $get_token; ?>=<?php echo $token; ?>',
      type: 'post',
      data: 'mpgdpr_requestlist_id=' + mpgdpr_requestlist_id,
      dataType: 'json',
      beforeSend: function() {
        $this.attr('disabled','disabled');
        $i.attr('class', 'fa fa-spinner fa-spin');
      },
      complete: function() {
        $this.removeAttr('disabled');
        $i.attr('class', $i.attr('data-class'));
      },
      success: function(json) {
        $('.alert, .text-danger').remove();

        if (json['success']) {
          $('#form-mpgdpr_requestlist').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
          $('#mpgdpr_request'+mpgdpr_requestlist_id).fadeOut(2000, function() {
            $('#mpgdpr_request'+mpgdpr_requestlist_id).remove();
          });
        }
        if (json['error']) {
          if (typeof json['error']['warning'] != 'undefined') {
            alert(json['error']['warning'])
          }
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }
});
// 01-05-2022: updation end
//--></script>

<script type="text/javascript"><!--
// filters collapse starts
$('#filter_collapse').on('hidden.bs.collapse', function (e) {
  let $trigger = $(e.target).data('bs.collapse').$trigger;
  let $i = $trigger.find('i');
  $i.attr('class', $i.attr('data-up'));
  sessionStorage.setItem('mpgdpr_admin_filter_collapse', 'up');
});

$('#filter_collapse').on('shown.bs.collapse', function (e) {
  let $trigger = $(e.target).data('bs.collapse').$trigger;
  let $i = $trigger.find('i');
  $i.attr('class', $i.attr('data-down'));
  sessionStorage.setItem('mpgdpr_admin_filter_collapse', 'down');
});
if (sessionStorage.getItem('mpgdpr_admin_filter_collapse') == 'down') {
  $('#filter_collapse').collapse('show');
}
if (sessionStorage.getItem('mpgdpr_admin_filter_collapse') == 'up') {
  $('#filter_collapse').collapse('hide');
}
// filters collapse ends
//--></script><style type="text/css">.mpcollapse { cursor: pointer;  }</style>
<script src="view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
<link href="view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css" type="text/css" rel="stylesheet" media="screen" />
<script type="text/javascript"><!--
$('.date').datetimepicker({
  pickTime: false
});
//--></script>
</div>
<?php echo $footer; ?>