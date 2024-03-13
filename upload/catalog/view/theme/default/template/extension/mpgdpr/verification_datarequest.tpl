  <?php echo $header; ?>
<div class="container">
  <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
    <?php } ?>
  </ul>
  <?php if ($success) { ?>
  <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?></div>
  <?php } ?>
  <?php if ($error_warning) { ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?></div>
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
      <h1 class="text-center"><?php echo $heading_title; ?></h1>
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" class="form-horizontal">
          <p class="text-center"><?php echo $text_message; ?></p>
          <div class="row">
            <div class="col-sm-offset-4 col-sm-4">
              <div class="form-group">
                <label style="text-align: left;" class="col-sm-12 control-label"><?php echo $entry_code; ?></label>
                <div class="col-sm-12">
                  <input type="text" name="code" value="<?php echo $code; ?>" placeholder="<?php echo $entry_code; ?>" id="input-code" class="form-control" />
                </div>
              </div>
              <button type="button" class="btn btn-success verification"><i class="fa fa-shield"></i> <?php echo $button_verify; ?></button>
            </div>
          </div>
    
        <div class="buttons clearfix">
          <div class="pull-right">
            <input type="submit" value="<?php echo $button_continue; ?>" class="btn btn-primary" />
          </div>
        </div>
      </form>
      <script type="text/javascript"><!--
        $('.verification').on('click', function() {
          var $this = $(this);
          var code = $('#input-code').val();

          var oldclass = $this.find('i').attr('class');

          $.ajax({
            url: 'index.php?route=<?php echo $extension_path; ?>mpgdpr/verification_datarequest/verification&o=1',
            type: 'post',
            data: 'code=' + code,
            dataType: 'json',
            beforeSend: function() {
              $this.find('i').removeClass(oldclass).addClass('fa fa-spinner fa-spin');
              $this.attr('disabled','disabled');
            },
            complete: function() {
              $this.find('i').removeClass('fa fa-spinner fa-spin');
              $this.removeAttr('disabled');
              $this.find('i').addClass(oldclass);
            },
            success: function(json) {
              $('.alert, .text-danger').remove();

              if (json['error']) {
                $this.after('<div class="alert alert-danger"><i class="fa fa-check-circle"></i> ' + json['error'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              }

               if (json['code_empty']) {
                $this.after('<div class="text-danger">' + json['code_empty'] + '</div>');
              }
             
              if (json['success']) {
               $this.after('<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
              }
            },
            error: function(xhr, ajaxOptions, thrownError) {
              alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
          });
        });
      //--></script> 

      <?php echo $content_bottom; ?></div>
    <?php echo $column_right; ?></div>
</div>
<?php echo $footer; ?> 