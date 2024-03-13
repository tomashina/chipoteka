<?php echo $header; ?><?php echo $column_left; ?>
<div id="content" class="mp-content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">

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
                <label class="control-label" for="input-email"><?php echo $entry_email; ?></label>
                <input type="text" name="filter_email" value="<?php echo $filter_email; ?>" placeholder="<?php echo $entry_email; ?>" id="input-email" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-request_id"><?php echo $entry_request_id; ?></label>
                <input type="text" name="filter_request_id" value="<?php echo $filter_request_id; ?>" placeholder="<?php echo $entry_request_id; ?>" id="input-request_id" class="form-control" />
              </div>
              <div class="form-group">
                <label class="control-label" for="input-status"><?php echo $entry_status; ?></label>
                <select name="filter_status" id="input-status" class="form-control">
                  <option value="*"><?php echo $text_all; ?></option>
                  <?php foreach ($deletion_statuses as $deletion_status) { ?>
                  <option value="<?php echo $deletion_status['value']; ?>" <?php if ($filter_status==$deletion_status['value']) { ?>selected="selected"<?php } ?>><?php echo $deletion_status['text']; ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="col-sm-4">
             <div class="form-group">
                <label class="control-label" for="input-date_deletion"><?php echo $entry_date_deletion; ?></label>
                <div class="input-group date">
                  <input type="text" name="filter_date_deletion" value="<?php echo $filter_date_deletion; ?>" placeholder="<?php echo $entry_date_deletion; ?>" data-date-format="YYYY-MM-DD" id="input-date_deletion" class="form-control" /><span class="input-group-btn"><button type="button" class="btn btn-default"><i class="fa fa-calendar"></i></button></span>
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
            <div class="col-sm-12">
              <div class="form-group text-right">
                <button type="button" id="button-refresh-filter" class="btn btn-danger refresh-filter"><i class="fa fa-refresh"></i> <?php echo $button_clear; ?></button>
                <button type="button" id="button-filter" class="btn btn-primary"><i class="fa fa-filter"></i> <?php echo $button_filter; ?></button>
              </div>
            </div>
          </div>
        </div>
        <form action="<?php echo $delete; ?>" method="post" enctype="multipart/form-data" id="form-deleteme_request">
          <div class="table-responsive">
            <table class="table table-bordered table-hover">
              <thead>
                <tr>
                  <td class="text-left"><a href="<?php echo $sort_deleteme_id; ?>" <?php if ($sort == 'd.mpgdpr_deleteme_id') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_request_id; ?></a></td>
                  <td class="text-left"><a href="<?php echo $sort_email; ?>" <?php if ($sort == 'd.email') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_email; ?></a></td>
                  <td class="text-left"><a href="<?php echo $sort_status; ?>" <?php if ($sort == 'd.status') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_status; ?></a></td>
                  <td class="text-left"><a href="<?php echo $sort_date_deletion; ?>" <?php if ($sort == 'd.date_deletion') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $column_date_deletion; ?></a></td>
                  <td class="text-left"><?php echo $column_date; ?></td>
                  <td class="text-right"><?php echo $column_action; ?></td>
                </tr>
              </thead>
              <tbody>
                <?php if ($requests) { ?>
                <?php foreach ($requests as $request) { ?>
                <tr class="requestid" id="requestid-<?php echo $request['mpgdpr_deleteme_id']; ?>">
                  <td class="text-left"><?php echo $request['mpgdpr_deleteme_id']; ?></td>
                  <td class="text-left"><?php echo $request['email']; ?></td>
                  <td class="text-left status_text"><?php echo $request['status_text']; ?></td>
                  <td class="text-left date_deletion"><?php echo $request['date_deletion']; ?></td>
                  <td class="text-left">
                    <!-- // 01-05-2022: updation start -->
                    <ul class="list-group">
                      <li class="list-group-item"><strong><a href="<?php echo $sort_date_added; ?>" <?php if ($sort == 'd.date_added') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $text_date_added; ?></a> :</strong> <?php echo $request['date_added']; ?></li>
                      <?php /*<li class="list-group-item"><strong><?php echo $text_date_modified; ?> :</strong> <?php echo $request['date_modified']; ?></li> */ ?>
                      <li class="list-group-item"><strong><a href="<?php echo $sort_expire_on; ?>" <?php if ($sort == 'd.expire_on') { ?>class="<?php echo strtolower($order); ?>"<?php } ?>><?php echo $text_expire_on; ?></a> :</strong> <?php echo $request['expire_on']; ?></li>
                    </ul>
                    <!-- // 01-05-2022: updation end -->
                  </td>
                  <td class="text-right">
                    <?php if ($request['status'] != $requestanonymouse_confirmed) { ?>
                    <button disabled="disabled" type="button" class="btn btn-primary" data-toggle="tooltip" title="<?php echo $button_delete_customer; ?>"><i class="fa fa-trash-o"></i></button>
                    <button disabled="disabled" type="button" class="btn btn-danger" data-toggle="tooltip" title="<?php echo $button_deny; ?>"><i class="fa fa-minus-circle"></i></button>
                    <?php } else { ?>
                    <button type="button" class="btn btn-primary deleteaction" data-toggle="tooltip" title="<?php echo $button_delete_customer; ?>"><i class="fa fa-trash-o"></i></button>
                    <button type="button" class="btn btn-danger denyaction" data-toggle="tooltip" title="<?php echo $button_deny; ?>"><i class="fa fa-minus-circle"></i></button>

                    <?php } ?>
                    <!-- // 01-05-2022: updation start -->
                    <?php if ($request['status'] == $requestanonymouse_awating /* && !$request['expire']  */) { ?>
                    <button type="button" data-href="<?php echo $request['approve']; ?>" class="btn btn-success approveaction" data-toggle="tooltip" title="<?php echo $button_approve; ?>"><i class="fa fa-thumbs-o-up" data-class="fa fa-thumbs-o-up"></i></button>
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
        </form>
        <div class="row">
          <div class="col-sm-6 text-left"><?php echo $pagination; ?></div>
          <div class="col-sm-6 text-right"><?php echo $results; ?></div>
        </div>
      </div>
    </div>
  </div>
  <!-- modal popup for delete start -->
  <div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-labelledby="deleteLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="upload-report requestid" data-id="">
            <h4><?php echo $text_request_id; ?>-(<span class="request_id"></span>)</h4>
            <h3 class="modal-title" id="deleteLabel"><?php echo $text_deleteanonymouse_warning; ?></h3>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-reply"></i> <?php echo $button_cancel; ?></button>
          <button type="button" class="btn btn-primary dodeleteaction"><i class="fa fa-trash-o"></i> <?php echo $button_delete_customer; ?></button>
        </div>
      </div>
    </div>
  </div>
  <!-- modal popup for delete end -->
  <!-- modal popup for deny start -->
  <div class="modal fade" id="modal-deny" tabindex="-1" role="dialog" aria-labelledby="denyLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h5 class="modal-title" id="denyLabel"><?php echo $text_deny_reason; ?></h5>
        </div>
        <div class="modal-body">
          <div class="deny-reason requestid" data-id="">
            <h4><?php echo $text_request_id; ?>-(<span class="request_id"></span>)</h4>
            <div class="row">
             <label class="col-sm-12 control-label" for="input-denyreason"><?php echo $entry_denyreason; ?></label>
                <div class="col-sm-12">
                  <textarea name="denyreason" id="input-denyreason" class="form-control" placeholder="<?php echo $entry_denyreason; ?>"></textarea>
                </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-reply"></i> <?php echo $button_cancel; ?></button>
          <button type="button" class="btn btn-danger dodenyaction"><i class="fa fa-minus-circle"></i> <?php echo $button_deny; ?></button>
        </div>
      </div>
    </div>
  </div>
  <!-- modal popup for deny end -->
<script type="text/javascript"><!--
$('#button-filter').on('click', function() {
  var url = 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestanonymouse&<?php echo $get_token; ?>=<?php echo $token; ?>';

  var filter_status = $('select[name=\'filter_status\']').val();
  if (filter_status != '*') {
    url += '&filter_status=' + encodeURIComponent(filter_status);
  }

  var filter_email = $('input[name=\'filter_email\']').val();
  if (filter_email) {
    url += '&filter_email=' + encodeURIComponent(filter_email);
  }

  var filter_request_id = $('input[name=\'filter_request_id\']').val();
  if (filter_request_id) {
    url += '&filter_request_id=' + encodeURIComponent(filter_request_id);
  }

  var filter_date_deletion = $('input[name=\'filter_date_deletion\']').val();
  if (filter_date_deletion) {
    url += '&filter_date_deletion=' + encodeURIComponent(filter_date_deletion);
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

  var url = 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestanonymouse&<?php echo $get_token; ?>=<?php echo $token; ?>';

  location = url;
});

$('.denyaction').on('click', function() {
  var $this = $(this);
  var date_deletion = $('#input-date_deletion').val();
  $('.alert, .text-danger').remove();
  var go = true;
  if (date_deletion=='' || date_deletion=='0000-00-00') {
    go = false;
    $('.panel.panel-default').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> <?php echo $error_date_deletion; ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
    $('html, body').animate({ scrollTop: 0 }, 'slow');
  }

  if (go) {
    var $tr = $this.parents('.requestid');
    var requestid = $tr.attr('id').replace('requestid-','');

    $('#modal-deny').find('.request_id').html(requestid);
    $('#modal-deny').find('.requestid').attr('data-id', requestid);

    $('#modal-deny').modal('show');
  }
});

$('#modal-deny').on('hidden.bs.modal', function() {
  $(this).find('.request_id').html('');
  $(this).find('.requestid').attr('data-id', '');
});

$('.dodenyaction').on('click', function() {
  var $this = $(this);
  var date_deletion = $('#input-date_deletion').val();
  $('.alert, .text-danger').remove();
  var go = true;

  if (go) {
  var oldclass = $this.find('i').attr('class');
  var data = [];
  var requestid = $('#modal-deny').find('.requestid').attr('data-id');
  data.push('mpgdpr_deleteme_id=' + requestid);
  data.push('date_deletion=' + date_deletion);

  var form_data =  $('#modal-deny').find('.deny-reason textarea').serialize();
  data.push(form_data);

  $.ajax({
    url: 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestanonymouse/denyAction&<?php echo $get_token; ?>=<?php echo $token; ?>&o=1',
    type: 'post',
    data: data.join('&'),
    dataType: 'json',
    beforeSend: function() {
      $this.find('i').removeClass(oldclass).addClass('fa fa-spinner fa-spin');
      // $this.button('loading');
      $this.attr('disabled','disabled');
    },
    complete: function() {
      $this.find('i').removeClass('fa fa-spinner fa-spin');
      // $this.button('reset');
      $this.removeAttr('disabled');
       $this.find('i').addClass(oldclass);
    },
    success: function(json) {
      $('.alert, .text-danger').remove();

      if (json['error']) {
        $('#modal-deny').find('.modal-header').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      }
      if (json['denyreason']) {
        $('#modal-deny').find('.modal-header').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['denyreason'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        $('#modal-deny').find('.deny-reason').append('<div class="text-danger">' + json['denyreason'] + '</div>');
      }
      if (json['date_deletion']) {
        $('#modal-deny').find('.modal-header').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['date_deletion'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        $('#input-date_deletion').parent().parent().append('<div class="text-danger">' + json['date_deletion'] + '</div>');
        $('#modal-deny').modal('hide');
        $('html, body').animate({ scrollTop: 0 }, 'slow');
      }


      if (json['success']) {
        $('#modal-deny').find('.modal-header').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

        setTimeout(function() {
          // disable further action and disable the buttons

          $('#requestid-'+requestid).find('.deleteaction').attr('disabled','disabled').removeClass('deleteaction');
          $('#requestid-'+requestid).find('.denyaction').attr('disabled','disabled').removeClass('denyaction');
          $('#requestid-'+requestid).find('.date_deletion').html(date_deletion);
          $('#requestid-'+requestid).find('.status_text').html(json['text_deny']);

          $('#modal-deny').modal('hide');
        }, 2000);

      }
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
  }
});


$('.deleteaction').on('click', function() {
  var $this = $(this);
  var date_deletion = $('#input-date_deletion').val();
  $('.alert, .text-danger').remove();
  var go = true;
  if (date_deletion=='' || date_deletion=='0000-00-00') {
    go = false;
    $('.panel.panel-default').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> <?php echo $error_date_deletion; ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
    $('html, body').animate({ scrollTop: 0 }, 'slow');
  }

  if (go) {
    var $tr = $this.parents('.requestid');
    var requestid = $tr.attr('id').replace('requestid-','');

    $('#modal-delete').find('.request_id').html(requestid);
    $('#modal-delete').find('.requestid').attr('data-id', requestid);

    $('#modal-delete').modal('show');
  }
});
$('#modal-delete').on('hidden.bs.modal', function() {
  $(this).find('.request_id').html('');
  $(this).find('.requestid').attr('data-id', '');
});

$('.dodeleteaction').on('click', function() {
  var $this = $(this);

  var date_deletion = $('#input-date_deletion').val();
  $('.alert, .text-danger').remove();
  var go = true;

  if (go) {
  var oldclass = $this.find('i').attr('class');

   var data = [];
   var requestid = $('#modal-delete').find('.requestid').attr('data-id');
   data.push('mpgdpr_deleteme_id=' + requestid);
   data.push('date_deletion=' + date_deletion);


  $.ajax({
    url: 'index.php?route=<?php echo $extension_path; ?>mpgdpr/requestanonymouse/deleteAction&<?php echo $get_token; ?>=<?php echo $token; ?>&o=1',
    type: 'post',
    data: data.join('&'),
    dataType: 'json',
    beforeSend: function() {
      $this.find('i').removeClass(oldclass).addClass('fa fa-spinner fa-spin');
      // $this.button('loading');
      $this.attr('disabled','disabled');
    },
    complete: function() {
      $this.find('i').removeClass('fa fa-spinner fa-spin');
      // $this.button('reset');
      $this.removeAttr('disabled');
       $this.find('i').addClass(oldclass);
    },
    success: function(json) {
      $('.alert, .text-danger').remove();

      if (json['error']) {
        $('#modal-delete').find('.modal-header').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
      }
      if (json['date_deletion']) {
        $('#modal-delete').find('.modal-header').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['date_deletion'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        $('#input-date_deletion').parent().parent().append('<div class="text-danger">' + json['date_deletion'] + '</div>');
        $('#modal-delete').modal('hide');
        $('html, body').animate({ scrollTop: 0 }, 'slow');
      }

      if (json['success']) {
        $('#modal-delete').find('.modal-header').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

        setTimeout(function() {
          // disable further action and disable the buttons
          $('#requestid-'+requestid).find('.deleteaction').attr('disabled','disabled').removeClass('deleteaction');
          $('#requestid-'+requestid).find('.denyaction').attr('disabled','disabled').removeClass('denyaction');
          $('#requestid-'+requestid).find('.date_deletion').html(date_deletion);
          $('#requestid-'+requestid).find('.status_text').html(json['text_complete']);
          $('#modal-success').modal('hide');
        }, 2000);

      }
      $('html, body').animate({ scrollTop: 0 }, 'slow');
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });
  }
});
// 01-05-2022: updation start
$('.approveaction').on('click', function() {
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
        if (typeof json['error']['warning'] != 'undefined') {
          $('#form-deleteme_request').before('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error']['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        }
      }

      if (json['success']) {
        $('#form-deleteme_request').before('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
        $('html, body').animate({ scrollTop: ($('#form-deleteme_request').offset().top - 50) }, 'slow');

        setTimeout(() => {
          window.location.reload();
        }, 1000);
      }
    },
    error: function(xhr, ajaxOptions, thrownError) {
      alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    }
  });

})
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