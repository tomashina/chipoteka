<?php echo $header; ?>
<div class="container">
  <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
    <?php } ?>
  </ul>
  <?php if ($success) { ?>
  <div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> <?php echo $success; ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php } ?>
  <?php if ($error_warning) { ?>
  <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
  <?php } ?>
  <div class="row"><?php echo $column_left; ?>
    <?php if ($column_left && $column_right) { ?>
    <?php $class = 'col-sm-6'; ?>
    <?php } elseif ($column_left || $column_right) { ?>
    <?php $class = 'col-sm-9'; ?>
    <?php } else { ?>
    <?php $class = 'col-sm-12'; ?>
    <?php } ?>
    <div id="content" class="<?php echo $class; ?>"><?php echo $content_top; ?>
      <h1><?php echo $heading_title; ?></h1>
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" class="form-horizontal">
        <p><?php echo $text_datarequest; ?></p>
        <ul class="list list-unstyled">
          <li><?php echo $text_step1; ?></li>
          <li><?php echo $text_step2; ?></li>
          <li><?php echo $text_step3; ?></li>
        </ul>
        <fieldset>
          <div class="form-group required">
            <label class="col-sm-2 control-label"><?php echo $entry_email; ?></label>
            <div class="col-sm-10">
              <input type="email" name="email" value="<?php echo $email; ?>" placeholder="<?php echo $entry_email; ?>" id="input-email" class="form-control" />
              <?php if ($error_email) { ?>
              <div class="text-danger"><?php echo $error_email; ?></div>
              <?php } ?>
            </div>
          </div>
          <?php echo $captcha; ?>
        </fieldset>
        <div class="buttons clearfix">
          <div class="pull-left"><a href="<?php echo $back; ?>" class="btn btn-default"><?php echo $button_back; ?></a></div>
          <div class="pull-right">
            <input type="submit" value="<?php echo $button_continue; ?>" class="btn btn-primary" />
          </div>
        </div>
      </form>
      <?php if ($customer_id) { ?>
      <!-- // 01-05-2022: updation start -->
      <h1 class="text-center"><?php echo $text_gdpr_datarequest_list; ?></h1>
      <div class="well">
        <div class="row">
          <div class="col-sm-4">
            <div class="form-group">
              <label class="control-label" for="input-request_id"><?php echo $entry_request_id; ?></label>
              <input type="text" name="filter_request_id" value="<?php echo $filter_request_id; ?>" placeholder="<?php echo $entry_request_id; ?>" id="input-request_id" class="form-control" />
            </div>
            <div class="form-group">
              <label class="control-label" for="input-status"><?php echo $entry_status; ?></label>
              <select name="filter_status" id="input-status" class="form-control">
                <option value="*"><?php echo $text_all; ?></option>
                <?php foreach($requestaccess_statuses as $requestaccess_status) { ?>
                <option value="<?php echo $requestaccess_status['value']; ?>" <?php if($filter_status==$requestaccess_status['value']) { ?>selected="selected"<?php } ?>><?php echo $requestaccess_status['text']; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          <div class="col-sm-4">
           <div class="form-group">
              <label class="control-label" for="input-filter_date_send"><?php echo $entry_date_send; ?></label>
              <div class="input-group date">
                <input type="text" name="filter_date_send" value="<?php echo $filter_date_send; ?>" placeholder="<?php echo $entry_date_send; ?>" data-date-format="YYYY-MM-DD" id="input-filter_date_send" class="form-control" /><span class="input-group-btn"><button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button></span>
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
                    <option value="DAY" <?php if($filter_time_lap=='DAY') { ?>selected="selected"<?php } ?>><?php echo $entry_days; ?></option>
                    <option value="WEEK" <?php if($filter_time_lap=='WEEK') { ?>selected="selected"<?php } ?>><?php echo $entry_weeks; ?></option>
                    <option value="MONTH" <?php if($filter_time_lap=='MONTH') { ?>selected="selected"<?php } ?>><?php echo $entry_months; ?></option>
                    <option value="YEAR" <?php if($filter_time_lap=='YEAR') { ?>selected="selected"<?php } ?>><?php echo $entry_years; ?></option>
                  </select>
                </div>
              </div>
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
          </div>
        </div>
        <div class="row">
          <div class="col-sm-12 text-right">
            <button type="button" id="button-filter" class="btn btn-primary"><i class="fa fa-filter"></i> <?php echo $button_filter; ?></button>
            <button type="button" id="button-reset" class="btn btn-danger"><i class="fa fa-refresh"></i> <?php echo $button_reset; ?></button>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table id="table-requestaccessdata" class="table table-bordered table-hover">
          <thead>
            <tr>
              <td style="width: 1px;" class="text-center"><input type="checkbox" onclick="$('input[name*=\'selected\']').prop('checked', this.checked);" /></td>
              <td class="text-left"><a href="<?php echo $sort_datarequest_id; ?>" <?php if ($sort == 'dr.mpgdpr_datarequest_id') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_request_id; ?></a></td>
              <td class="text-left"><?php echo $column_email; ?></td>
              <td class="text-left"><a href="<?php echo $sort_status; ?>" <?php if ($sort == 'dr.status') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_status; ?></a></td>
              <td class="text-left"><a href="<?php echo $sort_date_send; ?>" <?php if ($sort == 'dr.date_send') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_date_send; ?></a></td>
              <td class="text-left"><?php echo $column_date; ?></td>
              <td class="text-right"><?php echo $column_action; ?></td>
            </tr>
          </thead>
          <tbody>
            <?php if ($requests) { ?>
            <?php foreach ($requests as $request) { ?>
            <tr class="requestid" id="requestid-<?php echo $request['mpgdpr_datarequest_id']; ?>">
              <td class="text-center"><?php if (in_array($request['mpgdpr_datarequest_id'], $selected)) { ?>
              <input type="checkbox" name="selected[]" value="<?php echo $request['mpgdpr_datarequest_id']; ?>" checked="checked" />
              <?php } else { ?>
              <input type="checkbox" name="selected[]" value="<?php echo $request['mpgdpr_datarequest_id']; ?>" />
              <?php } ?></td>
              <td class="text-left"><?php echo $request['mpgdpr_datarequest_id']; ?></td>
              <td class="text-left"><?php echo $request['email']; ?></td>
              <td class="text-left status_text"><?php echo $request['status_text']; ?></td>
              <td class="text-left date_send"><?php echo $request['date_send']; ?></td>
              <td class="text-left">
                <!-- // 01-05-2022: updation start -->
                <ul class="list-group">
                  <li class="list-group-item"><strong><a href="<?php echo $sort_date_added; ?>" <?php if ($sort == 'dr.date_added') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $text_date_added; ?></a> :</strong> <?php echo $request['date_added']; ?></li>
                  <?php /* <li class="list-group-item"><strong><?php echo $text_date_modified; ?> :</strong> <?php echo $request['date_modified']; ?></li> */ ?>
                  <li class="list-group-item"><strong><a href="<?php echo $sort_expire_on; ?>" <?php if ($sort == 'dr.expire_on') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $text_expire_on; ?></a> :</strong> <?php echo $request['expire_on']; ?></li>
                </ul>
                <!-- // 01-05-2022: updation end -->
              </td>
              <td class="text-right">
                <!-- // 01-05-2022: updation start -->
                <?php if ($request['status'] == $requestaccess_awating && $request['expire']) { ?>
                <button type="button" data-href="<?php echo $request['resentcode']; ?>" class="btn btn-primary resentcodeaction" data-toggle="tooltip" title="<?php echo $button_resentcode; ?>"><i class="fa fa-refresh" data-class="fa fa-refresh"></i></button>
                <?php } else { ?>
                <button type="button" class="btn btn-primary" disabled="disabled"><i class="fa fa-refresh"></i></button>
                <?php } ?>
                <?php if ($request['status'] == $requestaccess_awating && !$request['expire']) { ?>
                <a target="_blank" href="<?php echo $request['approve']; ?>" class="btn btn-success approveaction" data-toggle="tooltip" title="<?php echo $button_approve; ?>"><i class="fa fa-thumbs-o-up" data-class="fa fa-thumbs-o-up"></i></a>
                <?php } else { ?>
                <button type="button" class="btn btn-success" disabled="disabled"><i class="fa fa-thumbs-o-up"></i></button>
                <?php } ?>
                <!-- // 01-05-2022: updation end -->
              </td>
            </tr>
            <?php } ?>
            <?php } else { ?>
            <tr>
              <td class="text-center" colspan="9"><?php echo $text_no_results; ?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
      <div class="row">
        <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
        <div class="col-sm-6 text-right"><?php echo $results; ?></div>
      </div>
      <!-- // 01-05-2022: updation end -->
      <?php } ?>

      <?php echo $content_bottom; ?></div>
    <?php echo $column_right; ?></div>
  <?php if ($customer_id) { ?>
  <style type="text/css">
    .list-group a {border: none;color: inherit;padding: 0px;}
    .list-group a:hover {color: inherit;background: transparent;border: none;text-shadow: none;}
  </style>
  <!-- // 01-05-2022: updation start -->
  <script type="text/javascript"><!--
    $('#button-filter').on('click', function() {
      var url = 'index.php?route=<?php echo $extension_path;?>/mpgdpr/account/datarequest';

      var filter_status = $('select[name=\'filter_status\']').val();
      if (filter_status != '*') {
        url += '&filter_status=' + encodeURIComponent(filter_status);
      }

      var filter_request_id = $('input[name=\'filter_request_id\']').val();
      if (filter_request_id) {
        url += '&filter_request_id=' + encodeURIComponent(filter_request_id);
      }

      var filter_date_send = $('input[name=\'filter_date_send\']').val();
      if (filter_date_send) {
        url += '&filter_date_send=' + encodeURIComponent(filter_date_send);
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
    $('#button-reset').on('click', function() {
      var url = 'index.php?route=<?php echo $extension_path;?>/mpgdpr/account/datarequest';
      location = url;
    });

    $('.resentcodeaction').on('click', function() {
      var $this = $(this);
      var $i = $(this).find('i');

      $('.alert, .text-danger').remove();

      $.ajax({
        url: $this.data('href'),
        type: 'get',
        data: '',
        dataType: 'json',
        beforeSend: function() {
          // $this.button('loading');
          $this.attr('disabled','disabled');
          $i.attr('class','fa fa-spinner fa-spin');
        },
        complete: function() {
          // $this.button('reset');
          $this.removeAttr('disabled');
          $i.attr('class', $i.attr('data-class'));
        },
        success: function(json) {
          $('.alert, .text-danger').remove();

          if (json['error']) {
            $('#table-requestaccessdata').before('<div class="alert alert-danger alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            $('html, body').animate({ scrollTop: ($('#table-requestaccessdata').offset().top - 50) }, 'slow');
          }

          if (json['success']) {
            $('#table-requestaccessdata').before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
            $('html, body').animate({ scrollTop: ($('#table-requestaccessdata').offset().top - 50) }, 'slow');
          }

        },
        error: function(xhr, ajaxOptions, thrownError) {
          alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
      });
    });
  //--></script>
  <?php foreach ($scripts as $script) { ?>
  <script src="<?php echo $script; ?>" type="text/javascript"></script>
  <?php } ?>
  <?php foreach ($styles as $style) { ?>
  <link href="<?php echo $style; ?>" type="text/css" rel="stylesheet" media="screen" />
  <?php } ?>
  <script type="text/javascript"><!--
    $('.date').datetimepicker({
      pickTime: false
    });
  //--></script>
  <!-- // 01-05-2022: updation end -->
  <?php } ?>
</div>
<?php echo $footer; ?> 