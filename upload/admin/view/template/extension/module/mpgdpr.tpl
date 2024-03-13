<?php echo $header; ?><?php echo $column_left; ?>
<div id="content" class="mp-content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-gdpr" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo $button_save; ?></button>
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
    <?php if ($success) { ?>
    <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i> <?php echo $success; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>

        <div class="pull-right">
          <span><?php echo $text_store; ?></span>
          <button type="button" data-toggle="dropdown" class="btn btn-default btn-xs dropdown-toggle"><span><?php echo $store_name; ?> &nbsp; &nbsp; </span> <i class="fa fa-angle-down"></i></button>
          <ul class="dropdown-menu pull-right">
            <?php foreach ($stores as $store) { ?>
            <li><a href="<?php echo $store['href']; ?>"><?php echo $store['name']; ?></a></li>
            <?php } ?>
          </ul>
        </div><div class="cleafix"></div>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-gdpr" class="form-horizontal">

          <?php /*<!-- // 29 dec 2022 changes starts -->*/ ?>
          <?php if ($disable_events) { ?>
          <div class="activate_evs">
            <div class="alert alert-warning"><?php echo $text_disable_events; ?></div>
            <script type="text/javascript">
              $('.mpgdpr_activate_evs').on('click', function() {
                let $this = $(this);
                $.ajax({
                  url: 'index.php?route=<?php echo $extension_path; ?>module/mpgdpr/activateEvents&<?php echo $get_token; ?>=<?php echo $token; ?>',
                  type: 'get',
                  data: 'ae=1',
                  dataType: 'json',
                  beforeSend: function() {
                    $('.alert-dismissible').remove();
                    $this.button('loading');
                  },
                  complete: function() {
                    $this.button('reset');
                  },
                  success: function(json) {
                    if (json['success']) {
                      $this.parent('.alert').after('<div class="alert alert-success alert-dismissible"><i class="fa fa-exclamation-circle"></i> '+ json['success'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                      setTimeout(() => {
                        $('.activate_evs').remove();
                      }, 5000);
                    }
                    if (json['error']) {
                      if (json['error']['warning']) {
                        $this.parent('.alert').after('<div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> '+ json['error']['warning'] +' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                      }
                    }
                  },
                  error: function(xhr, ajaxOptions, thrownError) {
                    console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                  }
                });
              });
            </script>
          </div>
          <?php } ?>
          <?php if ($files) { ?>
          <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $text_files_permission; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <button type="button" class="btn btn-primary" id="addfiles"><i class="fa fa-plus-circle" data-class="fa fa-plus-circle"></i> <?php echo $button_files_permission; ?></button>
          </div>
          <script type="text/javascript">
          $('#addfiles').on('click', function() {
            var el = this;
            $.ajax({
              url: 'index.php?route=<?php echo $extension_path; ?>module/mpgdpr/updatePermissions&<?php echo $get_token; ?>=<?php echo $token; ?>',
              type: 'get',
              data: '',
              dataType: 'json',
              beforeSend: function() {
                $(el).attr('disabled','disabled');
                $(el).find('i').attr('class', 'fa fa-refresh fa-spin');
              },
              complete: function() {
                $(el).removeAttr('disabled');
                $(el).find('i').attr('class', $(el).find('i').attr('data-class'));
              },
              success: function(json) {
                $('.alert-dismissible, .text-danger').remove();

                if (json['redirect']) {

                  if (json['timeout']) {
                    setTimeout(()=>{
                      location = json['redirect'];
                    }, json['timeout']);
                  } else {
                    location = json['redirect'];
                  }
                }

                if (json['success']) {
                  $(el).parent('.alert').after('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                }
              },
              error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
              }
            });
          });
          </script>
          <?php } ?>
          <?php /*<!-- // 29 dec 2022 changes ends -->*/ ?>

          <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><i class="fa fa-cogs"></i> <span><?php echo $tab_general; ?></span></a></li>
            <li><a href="#tab-other" data-toggle="tab"><i class="fa fa-server"></i> <span><?php echo $tab_other; ?></span></a></li>
            <li><a href="#tab-cookieconsent" data-toggle="tab"><i class="fa fa-save"></i> <span><?php echo $tab_cookieconsent; ?></span></a></li>
            <!-- // 01-05-2022: updation start -->
            <li><a href="#tab-emailtemplate" data-toggle="tab"><i class="fa fa-envelope"></i> <span><?php echo $tab_emailtemplate; ?></span></a></li>
            <!-- // 01-05-2022: updation end -->
            <li><a href="#tab-support" data-toggle="tab"><i class="fa fa-thumbs-up"></i> <span><?php echo $tab_modulepoints; ?></span></a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-general">
              <div class="row">
                <div class="col-md-3 col-sm-12">
                  <ul class="nav nav-pills nav-stacked ostab">
                    <li class="active"><a href="#tab-enable" data-toggle="tab"><i class="fa fa-cog"></i> <span><?php echo $tab_settings; ?></span></a></li>
                    <li><a href="#tab-captcha" data-toggle="tab"><i class="fa fa-shield"></i> <span><?php echo $legend_captcha; ?></span></a></li>
                    <li><a href="#tab-requesttime" data-toggle="tab"><i class="fa fa-desktop"></i> <span><?php echo $legend_requesttimeout; ?></span></a></li>
                    <li><a href="#tab-upload" data-toggle="tab"><i class="fa fa-file-o"></i> <span><?php echo $legend_upload; ?></span></a></li>
                  </ul>
                </div>
                <div class="col-md-8 col-sm-12">
                  <div class="tab-content">
                    <div class="tab-pane active" id="tab-enable">
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_status) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_status" value="1" <?php echo (!empty($mpgdpr_status)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_status) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_status" value="0" <?php echo (empty($mpgdpr_status)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_status; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation start -->
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-default_google_analytic"><?php echo $entry_default_google_analytic; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_default_google_analytic) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_default_google_analytic" value="1" <?php echo (!empty($mpgdpr_default_google_analytic)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_default_google_analytic) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_default_google_analytic" value="0" <?php echo (empty($mpgdpr_default_google_analytic)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_default_google_analytic; ?></span>
                        </div>
                      </div>
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-policy_data"><?php echo $entry_policy_data; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_policy_data) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_policy_data" value="1" <?php echo (!empty($mpgdpr_policy_data)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_policy_data) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_policy_data" value="0" <?php echo (empty($mpgdpr_policy_data)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_policy_data; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation end -->
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-hasright_todelete"><?php echo $entry_hasright_todelete; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_hasright_todelete) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_hasright_todelete" value="1" <?php echo (!empty($mpgdpr_hasright_todelete)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_hasright_todelete) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_hasright_todelete" value="0" <?php echo (empty($mpgdpr_hasright_todelete)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_hasright_todelete; ?></span>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-maxrequests"><?php echo $entry_maxrequests; ?> </label>
                        <div class="col-sm-5">
                          <input type="text" name="mpgdpr_maxrequests" value="<?php echo $mpgdpr_maxrequests; ?>" class="form-control" />
                          <span class="help"><?php echo $help_maxrequests; ?></span>
                        </div>
                      </div>
                      <?php /*
                      <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-keyword"><?php echo $entry_keyword; ?> </label>
                        <div class="col-sm-5">
                          <input type="text" name="mpgdpr_keyword" value="<?php echo $mpgdpr_keyword; ?>" class="form-control" />

                          <span class="help"><?php echo $help_keyword; ?></span>
                        </div>
                      </div> */ ?>
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-login_gdprforms"><?php echo $entry_login_gdprforms; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_login_gdprforms) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_login_gdprforms" value="1" <?php echo (!empty($mpgdpr_login_gdprforms)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_login_gdprforms) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_login_gdprforms" value="0" <?php echo (empty($mpgdpr_login_gdprforms)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_login_gdprforms; ?></span>
                        </div>
                      </div>
                      <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i> <small><?php echo $text_acceptpolicy_gdpr; ?></small></h4>
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-acceptpolicy_customer"><?php echo $entry_acceptpolicy_customer; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_acceptpolicy_customer) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_customer" value="1" <?php echo (!empty($mpgdpr_acceptpolicy_customer)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_acceptpolicy_customer) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_customer" value="0" <?php echo (empty($mpgdpr_acceptpolicy_customer)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_acceptpolicy_customer; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-policy_customer"><?php echo $entry_policy_customer; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_policy_customer" id="input-policy_customer" class="form-control">
                            <option value="0"><?php echo $text_default_page; ?></option>
                            <?php foreach ($information_pages as $information_page) { ?>
                            <option value="<?php echo $information_page['information_id']; ?>" <?php if ($mpgdpr_policy_customer == $information_page['information_id']) { ?>selected="selected"<?php } ?>><?php echo $information_page['title']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_policy_customer; ?></span>
                        </div>
                      </div>
                      <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <small><?php echo $text_acceptpolicy_gdpr; ?></small></h4>
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-acceptpolicy_contactus"><?php echo $entry_acceptpolicy_contactus; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_acceptpolicy_contactus) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_contactus" value="1" <?php echo (!empty($mpgdpr_acceptpolicy_contactus)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_acceptpolicy_contactus) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_contactus" value="0" <?php echo (empty($mpgdpr_acceptpolicy_contactus)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_acceptpolicy_contactus; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-policy_contactus"><?php echo $entry_policy_contactus; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_policy_contactus" id="input-policy_contactus" class="form-control">
                            <option value="0"><?php echo $text_none; ?></option>
                            <?php foreach ($information_pages as $information_page) { ?>
                            <option value="<?php echo $information_page['information_id']; ?>" <?php if ($mpgdpr_policy_contactus == $information_page['information_id']) { ?>selected="selected"<?php } ?> ><?php echo $information_page['title']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_policy_contactus; ?></span>
                        </div>
                      </div>
                      <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <small><?php echo $text_acceptpolicy_gdpr; ?></small></h4>
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-acceptpolicy_checkout"><?php echo $entry_acceptpolicy_checkout; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_acceptpolicy_checkout) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_checkout" value="1" <?php echo (!empty($mpgdpr_acceptpolicy_checkout)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_acceptpolicy_checkout) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_acceptpolicy_checkout" value="0" <?php echo (empty($mpgdpr_acceptpolicy_checkout)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_acceptpolicy_checkout; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-policy_checkout"><?php echo $entry_policy_checkout; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_policy_checkout" id="input-policy_checkout" class="form-control">
                            <option value="0"><?php echo $text_default_page; ?></option>
                            <?php foreach ($information_pages as $information_page) { ?>
                            <option value="<?php echo $information_page['information_id']; ?>" <?php if ($mpgdpr_policy_checkout == $information_page['information_id']) { ?>selected="selected"<?php } ?>><?php echo $information_page['title']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_policy_checkout; ?></span>
                        </div>
                      </div>
                      <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <small><?php echo $text_export_format; ?></small></h4>
                      <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-export_format"><?php echo $entry_export_format; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_export_format" id="input-export_format" class="form-control">
                            <option value="csv" <?php if ($mpgdpr_export_format == 'csv' ) { ?>selected="selected"<?php } ?>><?php echo $text_csv; ?></option>
                            <option value="xls" <?php if ($mpgdpr_export_format == 'xls' ) { ?>selected="selected"<?php } ?>><?php echo $text_xls; ?></option>
                            <option value="xlsx" <?php if ($mpgdpr_export_format == 'xlsx' ) { ?>selected="selected"<?php } ?>><?php echo $text_xlsx; ?></option>
                            <option value="json" <?php if ($mpgdpr_export_format == 'json' ) { ?>selected="selected"<?php } ?>><?php echo $text_json; ?></option>
                            <option value="xml" <?php if ($mpgdpr_export_format == 'xml' ) { ?>selected="selected"<?php } ?>><?php echo $text_xml; ?></option>
                          </select>
                          <span class="help"><?php echo $help_export_format; ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-captcha">
                      <div class="form-group mp-buttons">
                        <label class="col-sm-2 control-label" for="input-captcha_gdprforms"><?php echo $entry_captcha_gdprforms; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_captcha_gdprforms) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_captcha_gdprforms" value="1" <?php echo (!empty($mpgdpr_captcha_gdprforms)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_yes; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_captcha_gdprforms) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_captcha_gdprforms" value="0" <?php echo (empty($mpgdpr_captcha_gdprforms)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_no; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_captcha_gdprforms; ?></span>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label"><?php echo $entry_captcha; ?></label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_captcha" id="input-captcha" class="form-control">
                            <option value=""><?php echo $text_none; ?></option>
                            <?php foreach ($captchas as $captcha) { ?>
                            <?php if ($captcha['value'] == $mpgdpr_captcha) { ?>
                            <option value="<?php echo $captcha['value']; ?>" selected="selected"><?php echo $captcha['text']; ?></option>
                            <?php } else { ?>
                            <option value="<?php echo $captcha['value']; ?>"><?php echo $captcha['text']; ?></option>
                            <?php } ?>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_captcha; ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-requesttime">
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-requestget_personaldata"><?php echo $entry_requestget_personaldata; ?></label>
                        <div class="col-sm-5">
                          <input type="number" name="mpgdpr_timeout[requestget_personaldata]" value="<?php echo isset($mpgdpr_timeout['requestget_personaldata']) ? $mpgdpr_timeout['requestget_personaldata'] : 2; ?>" placeholder="<?php echo $entry_requestget_personaldata; ?>" id="input-requestget_personaldata" class="form-control" />
                          <span class="help"><?php echo $help_requestget_personaldata; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-requestdelete_personaldata"><?php echo $entry_requestdelete_personaldata; ?></label>
                        <div class="col-sm-5">
                          <input type="number" name="mpgdpr_timeout[requestdelete_personaldata]" value="<?php echo isset($mpgdpr_timeout['requestdelete_personaldata']) ? $mpgdpr_timeout['requestdelete_personaldata'] : 2; ?>" placeholder="<?php echo $entry_requestdelete_personaldata; ?>" id="input-requestdelete_personaldata" class="form-control" />
                          <span class="help"><?php echo $help_requestdelete_personaldata; ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-upload">
                      <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-file-ext-allowed"><?php echo $entry_file_ext_allowed; ?></label>
                        <div class="col-sm-5">
                          <textarea name="mpgdpr_file_ext_allowed" rows="5" placeholder="<?php echo $entry_file_ext_allowed; ?>" id="input-file-ext-allowed" class="form-control"><?php echo $mpgdpr_file_ext_allowed; ?></textarea>
                          <span class="help"><?php echo $help_file_ext_allowed; ?></span>
                        </div>
                      </div>
                      <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-file-mime-allowed"><?php echo $entry_file_mime_allowed; ?></label>
                        <div class="col-sm-5">
                          <textarea name="mpgdpr_file_mime_allowed" rows="5" placeholder="<?php echo $entry_file_mime_allowed; ?>" id="input-file-mime-allowed" class="form-control"><?php echo $mpgdpr_file_mime_allowed; ?></textarea>
                          <span class="help"><?php echo $help_file_mime_allowed; ?></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="tab-pane" id="tab-other">
              <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $text_access_personaldata; ?> <br/><small><?php echo $help_access_personaldata; ?></small></h4>

              <ul class="nav nav-tabs" id="languageother">
                <?php foreach ($languages as $language) { ?>
                <li><a href="#languageother<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                <?php } ?>
              </ul>
              <div class="tab-content">
                <?php foreach ($languages as $language) { ?>
                <div class="tab-pane" id="languageother<?php echo $language['language_id']; ?>">
                  <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $entry_locationservices; ?></label>
                    <div class="col-sm-6">
                      <textarea name="mpgdpr_services[<?php echo $language['language_id']; ?>][locationservices]" id="input-locationservices<?php echo $language['language_id']; ?>" class="form-control" cols="10"><?php echo isset($mpgdpr_services[$language['language_id']]) ? $mpgdpr_services[$language['language_id']]['locationservices'] :''; ?></textarea>
                      <span class="help"><?php echo $help_locationservices; ?></span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-2 control-label"><?php echo $entry_otherservices; ?></label>
                    <div class="col-sm-6">
                      <textarea name="mpgdpr_services[<?php echo $language['language_id']; ?>][otherservices]" id="input-otherservices<?php echo $language['language_id']; ?>" class="form-control" cols="10"><?php echo isset($mpgdpr_services[$language['language_id']]) ? $mpgdpr_services[$language['language_id']]['otherservices'] :''; ?></textarea>
                      <span class="help"><?php echo $help_otherservices; ?></span>
                    </div>
                  </div>
                </div>
                <?php } ?>
              </div>
            </div>
            <div class="tab-pane" id="tab-cookieconsent">
              <div class="row">
                <div class="col-md-3 col-sm-12">
                  <ul class="nav nav-pills nav-stacked ostab">
                    <li class="active"><a href="#tab-cgeneral" data-toggle="tab"><i class="fa fa-cogs"></i> <span><?php echo $legend_general; ?></span></a></li>
                    <li><a href="#tab-cookiemanager" data-toggle="tab"><i class="fa fa-stack-overflow"></i> <span><?php echo $legend_cookiemanager; ?></span></a></li>
                    <!-- // 01-05-2022: updation start -->
                    <li><a href="#tab-consentbar" data-toggle="tab"><i class="fa fa-bars"></i> <span><?php echo $legend_consentbar; ?></span></a></li>
                    <!-- // 01-05-2022: updation end -->
                    <li><a href="#tab-language" data-toggle="tab"><i class="fa fa-language"></i> <span><?php echo $legend_language; ?></span></a></li>
                  </ul>
                </div>
                <div class="col-md-9 col-sm-12">
                  <div class="tab-content">
                    <div class="tab-pane active" id="tab-cgeneral">
                      <div class="form-group required mp-buttons">
                        <label class="col-sm-2 control-label" for="input-cbstatus"><?php echo $entry_cbstatus; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_cbstatus) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbstatus" value="1" <?php echo (!empty($mpgdpr_cbstatus)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_cbstatus) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbstatus" value="0" <?php echo (empty($mpgdpr_cbstatus)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_cbstatus; ?></span>
                        </div>
                      </div>
                      <div class="form-group required mp-buttons">
                        <label class="col-sm-2 control-label" for="input-cbpolicy"><?php echo $entry_cbpolicy; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_cbpolicy) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbpolicy" value="1" <?php echo (!empty($mpgdpr_cbpolicy)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_cbpolicy) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbpolicy" value="0" <?php echo (empty($mpgdpr_cbpolicy)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_cbpolicy; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cbpolicy_page"><?php echo $entry_cbpolicy_page; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_cbpolicy_page" id="input-cbpolicy_page" class="form-control">
                            <option value="0"><?php echo $text_default_page; ?></option>
                            <?php foreach ($information_pages as $information_page) { ?>
                            <option value="<?php echo $information_page['information_id']; ?>" <?php if ($mpgdpr_cbpolicy_page == $information_page['information_id']) { ?>selected="selected"<?php } ?>><?php echo $information_page['title']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_cbpolicy_page; ?></span>
                        </div>
                      </div>
                      <div class="form-group required mp-buttons">
                        <label class="col-sm-2 control-label" for="input-cbpptrack"><?php echo $entry_cbpptrack; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_cbpptrack) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbpptrack" value="1" <?php echo (!empty($mpgdpr_cbpptrack)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_cbpptrack) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbpptrack" value="0" <?php echo (empty($mpgdpr_cbpptrack)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_cbpptrack; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cbinitial"><?php echo $entry_cbinitial; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_cbinitial" id="input-cbinitial" class="form-control">
                            <?php foreach ($cbinitials as $cbinitial) { ?>
                            <option value="<?php echo $cbinitial['value']; ?>" <?php if ($mpgdpr_cbinitial == $cbinitial['value']) { ?>selected="selected"<?php } ?>><?php echo $cbinitial['text']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_cbinitial; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cbaction_close"><?php echo $entry_cbaction_close; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_cbaction_close" id="input-cbaction_close" class="form-control">
                            <?php foreach ($cbactions_close as $cbaction_close) { ?>
                            <option value="<?php echo $cbaction_close['value']; ?>" <?php if ($mpgdpr_cbaction_close == $cbaction_close['value']) { ?>selected="selected"<?php } ?>><?php echo $cbaction_close['text']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_cbaction_close; ?></span>
                        </div>
                      </div>
                      <div class="form-group required mp-buttons">
                        <label class="col-sm-2 control-label" for="input-cbshowagain"><?php echo $entry_cbshowagain; ?> </label>
                        <div class="col-sm-5">
                          <div class="btn-group btn-group-justified" data-toggle="buttons">
                            <label class="btn btn-primary <?php echo !empty($mpgdpr_cbshowagain) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbshowagain" value="1" <?php echo (!empty($mpgdpr_cbshowagain)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_enabled; ?>
                            </label>
                            <label class="btn btn-primary <?php echo empty($mpgdpr_cbshowagain) ? 'active' : '';  ?>">
                              <input type="radio" name="mpgdpr_cbshowagain" value="0" <?php echo (empty($mpgdpr_cbshowagain)) ? 'checked="checked"' : '';  ?> />
                              <?php echo $text_disabled; ?>
                            </label>
                          </div>
                          <span class="help"><?php echo $help_cbshowagain; ?></span>
                        </div>
                      </div>
                    </div>
                    <div class="tab-pane" id="tab-cookiemanager">
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_stricklyrequired"><?php echo $entry_cookie_stricklyrequired; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_stricklyrequired" id="input-cookie_stricklyrequired" class="form-control"><?php echo $mpgdpr_cookie_stricklyrequired; ?></textarea>
                          <span class="help"><?php echo $help_cookie_stricklyrequired; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_analytics"><?php echo $entry_cookie_analytics; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_analytics" id="input-cookie_analytics" class="form-control"><?php echo $mpgdpr_cookie_analytics; ?></textarea>
                          <span class="help"><?php echo $help_cookie_analytics; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation start -->
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_analytics_allow"><?php echo $entry_cookie_analytics_allow; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_analytics_allow" id="input-cookie_analytics_allow" class="form-control"><?php echo $mpgdpr_cookie_analytics_allow; ?></textarea>
                          <span class="help"><?php echo $help_cookie_analytics_allow; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_analytics_deny"><?php echo $entry_cookie_analytics_deny; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_analytics_deny" id="input-cookie_analytics_deny" class="form-control"><?php echo $mpgdpr_cookie_analytics_deny; ?></textarea>
                          <span class="help"><?php echo $help_cookie_analytics_deny; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation end -->
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_marketing"><?php echo $entry_cookie_marketing; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_marketing" id="input-cookie_marketing" class="form-control"><?php echo $mpgdpr_cookie_marketing; ?></textarea>
                          <span class="help"><?php echo $help_cookie_marketing; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation start -->
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_marketing_allow"><?php echo $entry_cookie_marketing_allow; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_marketing_allow" id="input-cookie_marketing_allow" class="form-control"><?php echo $mpgdpr_cookie_marketing_allow; ?></textarea>
                          <span class="help"><?php echo $help_cookie_marketing_allow; ?></span>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_marketing_deny"><?php echo $entry_cookie_marketing_deny; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_marketing_deny" id="input-cookie_marketing_deny" class="form-control"><?php echo $mpgdpr_cookie_marketing_deny; ?></textarea>
                          <span class="help"><?php echo $help_cookie_marketing_deny; ?></span>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation end -->
                      <!-- // 29 dec 2022 changes starts -->
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-custom_js_code"><?php echo $entry_custom_js_code; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_custom_js_code" id="input-custom_js_code" class="form-control"><?php echo $mpgdpr_custom_js_code; ?></textarea>
                          <span class="help"><?php echo $help_custom_js_code; ?></span>
                        </div>
                      </div>
                      <!-- // 29 dec 2022 changes ends -->
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cookie_domain"><?php echo $entry_cookie_domain; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="7" name="mpgdpr_cookie_domain" id="input-cookie_domain" class="form-control"><?php echo $mpgdpr_cookie_domain; ?></textarea>
                          <span class="help"><?php echo $help_cookie_domain; ?></span>
                        </div>
                      </div>
                    </div>
                    <!-- // 01-05-2022: updation start -->
                    <div class="tab-pane" id="tab-consentbar">
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cbposition"><?php echo $entry_cbposition; ?> </label>
                        <div class="col-sm-5">
                          <select name="mpgdpr_cbposition" id="input-cbposition" class="form-control">
                            <?php foreach ($cbpositions as $cbposition) { ?>
                            <option value="<?php echo $cbposition['value']; ?>" <?php if ($mpgdpr_cbposition == $cbposition['value']) { ?>selected="selected"<?php } ?>><?php echo $cbposition['text']; ?></option>
                            <?php } ?>
                          </select>
                          <span class="help"><?php echo $help_cbposition; ?></span>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-12">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_cbcolors; ?> <br/> <small><?php echo $help_cbcolors; ?></small></h4>

                          <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-cbbox_bg"><?php echo $entry_cbboxbg; ?></label>
                            <div class="col-sm-5">
                               <div class="colorpicker colorpicker-component input-group">
                                <span class="input-group-addon"><i></i></span>
                                <input type="text" name="mpgdpr_cbcolor[box_bg]" value="<?php echo isset($mpgdpr_cbcolor['box_bg']) ? $mpgdpr_cbcolor['box_bg'] : ''; ?>" placeholder="<?php echo $entry_cbboxbg; ?>" id="input-cbbox_bg" class="form-control" />
                              </div>
                            </div>
                          </div>
                          <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-cbbox_text"><?php echo $entry_cbboxtext; ?></label>
                            <div class="col-sm-5">
                               <div class="colorpicker colorpicker-component input-group">
                                <span class="input-group-addon"><i></i></span>
                                <input type="text" name="mpgdpr_cbcolor[box_text]" value="<?php echo isset($mpgdpr_cbcolor['box_text']) ? $mpgdpr_cbcolor['box_text'] : ''; ?>" placeholder="<?php echo $entry_cbboxtext; ?>" id="input-cbbox_text" class="form-control" />
                              </div>
                            </div>
                          </div>
                          <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-cbbtn_bg"><?php echo $entry_cbbtnbg; ?></label>
                            <div class="col-sm-5">
                               <div class="colorpicker colorpicker-component input-group">
                                <span class="input-group-addon"><i></i></span>
                                <input type="text" name="mpgdpr_cbcolor[btn_bg]" value="<?php echo isset($mpgdpr_cbcolor['btn_bg']) ? $mpgdpr_cbcolor['btn_bg'] : ''; ?>" placeholder="<?php echo $entry_cbbtnbg; ?>" id="input-cbbtn_bg" class="form-control" />
                              </div>
                            </div>
                          </div>
                          <div class="form-group required">
                            <label class="col-sm-2 control-label" for="input-cbbtn_text"><?php echo $entry_cbbtntext; ?></label>
                            <div class="col-sm-5">
                               <div class="colorpicker colorpicker-component input-group">
                                <span class="input-group-addon"><i></i></span>
                                <input type="text" name="mpgdpr_cbcolor[btn_text]" value="<?php echo isset($mpgdpr_cbcolor['btn_text']) ? $mpgdpr_cbcolor['btn_text'] : ''; ?>" placeholder="<?php echo $entry_cbbtntext; ?>" id="input-cbbtn_text" class="form-control" />
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="form-group required">
                        <label class="col-sm-2 control-label" for="input-cbcss"><?php echo $entry_cbcss; ?> </label>
                        <div class="col-sm-5">
                          <textarea rows="5" name="mpgdpr_cbcss" id="input-cbcss" class="form-control"><?php echo $mpgdpr_cbcss; ?></textarea>
                          <span class="help"><?php echo $help_cbcss; ?></span>
                        </div>
                      </div>
                    </div>
                    <!-- // 01-05-2022: updation end -->
                    <div class="tab-pane" id="tab-language">
                      <div class="row">
                        <div class="col-sm-12">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_cookielanguage; ?> <br/> <small><?php echo $help_cookielanguage; ?></small></h4>

                          <ul class="nav nav-tabs" id="cookielanguage">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#cookielanguage<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="cookielanguage<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookietext_msg<?php echo $language['language_id']; ?>"><?php echo $entry_cookietext_msg; ?> </label>
                                <div class="col-sm-5">
                                  <textarea rows="4" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][msg]" id="input-cookietext_msg<?php echo $language['language_id']; ?>" class="form-control"><?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['msg'] : ''; ?></textarea>
                                  <span class="help"><?php echo $help_cookietext_msg; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookietext_policy<?php echo $language['language_id']; ?>"><?php echo $entry_cookietext_policy; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][text_policy]" id="input-cookietext_policy<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['text_policy'] : ''; ?>" />
                                  <span class="help"><?php echo $help_cookietext_policy; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookiebtn_accept<?php echo $language['language_id']; ?>"><?php echo $entry_cookiebtn_accept; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][btn_accept]" id="input-cookiebtn_accept<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['btn_accept'] : ''; ?>" />
                                  <span class="help"><?php echo $help_cookiebtn_accept; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookiebtn_deny<?php echo $language['language_id']; ?>"><?php echo $entry_cookiebtn_deny; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][btn_deny]" id="input-cookiebtn_deny<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['btn_deny'] : ''; ?>" />
                                  <span class="help"><?php echo $help_cookiebtn_deny; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookiebtn_prefrence<?php echo $language['language_id']; ?>"><?php echo $entry_cookiebtn_prefrence; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][btn_prefrence]" id="input-cookiebtn_prefrence<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['btn_prefrence'] : ''; ?>" />
                                  <span class="help"><?php echo $help_cookiebtn_prefrence; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-cookiebtn_showagain<?php echo $language['language_id']; ?>"><?php echo $entry_cookiebtn_showagain; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_cookielang[<?php echo $language['language_id']; ?>][btn_showagain]" id="input-cookiebtn_showagain<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_cookielang[$language['language_id']]) ? $mpgdpr_cookielang[$language['language_id']]['btn_showagain'] : ''; ?>" />
                                  <span class="help"><?php echo $help_cookiebtn_showagain; ?></span>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation start -->
                      <div class="row">
                        <div class="col-sm-12">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_prefrence_cookie; ?> <br/> <small><?php echo $help_prefrence_cookie; ?></small></h4>

                          <ul class="nav nav-tabs" id="cookieprefrence">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#cookieprefrence<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="cookieprefrence<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_heading<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_heading; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][heading]" id="input-prefrence_cookie_heading<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['heading'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookie_heading; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_strickly<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_strickly; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][strickly]" id="input-prefrence_cookie_strickly<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['strickly'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookie_strickly; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_strickly_detail<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_strickly_detail; ?> </label>
                                <div class="col-sm-5">
                                  <textarea rows="4" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][strickly_detail]" id="input-prefrence_cookie_strickly_detail<?php echo $language['language_id']; ?>" class="form-control"><?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['strickly_detail'] : ''; ?></textarea>
                                  <span class="help"><?php echo $help_prefrence_cookie_strickly_detail; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_analytics<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_analytics; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][analytics]" id="input-prefrence_cookie_analytics<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['analytics'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookie_analytics; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_analytics_detail<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_analytics_detail; ?> </label>
                                <div class="col-sm-5">
                                  <textarea rows="4" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][analytics_detail]" id="input-prefrence_cookie_analytics_detail<?php echo $language['language_id']; ?>" class="form-control"><?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['analytics_detail'] : ''; ?></textarea>
                                  <span class="help"><?php echo $help_prefrence_cookie_analytics_detail; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_marketing<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_marketing; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][marketing]" id="input-prefrence_cookie_marketing<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['marketing'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookie_marketing; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookie_marketing_detail<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookie_marketing_detail; ?> </label>
                                <div class="col-sm-5">
                                  <textarea rows="4" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][marketing_detail]" id="input-prefrence_cookie_marketing_detail<?php echo $language['language_id']; ?>" class="form-control"><?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['marketing_detail'] : ''; ?></textarea>
                                  <span class="help"><?php echo $help_prefrence_cookie_marketing_detail; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookiebtn_close<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookiebtn_close; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][btn_close]" id="input-prefrence_cookiebtn_close<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['btn_close'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookiebtn_close; ?></span>
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-prefrence_cookiebtn_update<?php echo $language['language_id']; ?>"><?php echo $entry_prefrence_cookiebtn_update; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langcookiepref[<?php echo $language['language_id']; ?>][btn_update]" id="input-prefrence_cookiebtn_update<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langcookiepref[$language['language_id']]) ? $mpgdpr_langcookiepref[$language['language_id']]['btn_update'] : ''; ?>" />
                                  <span class="help"><?php echo $help_prefrence_cookiebtn_update; ?></span>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-sm-12">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_restrict_processing; ?> <br/> <small><?php echo $help_restrict_processing; ?></small></h4>

                          <ul class="nav nav-tabs" id="restrictprocessing">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#restrictprocessing<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="restrictprocessing<?php echo $language['language_id']; ?>">

                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-restrict_processing_alert<?php echo $language['language_id']; ?>"><?php echo $entry_restrict_processing_alert; ?> </label>
                                <div class="col-sm-5">
                                  <input type="text" name="mpgdpr_langrestrictprocessing[<?php echo $language['language_id']; ?>][alert]" id="input-restrict_processing_alert<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_langrestrictprocessing[$language['language_id']]) ? $mpgdpr_langrestrictprocessing[$language['language_id']]['alert'] : ''; ?>" />
                                  <span class="help"><?php echo $help_restrict_processing_alert; ?></span>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                      <!-- // 01-05-2022: updation end -->
                    </div>
                    <a target="_blank" href="https://cookieconsent.insites.com/">* Cookie Conset js library Originally developed by insite :)</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- // 01-05-2022: updation start -->
            <div class="tab-pane" id="tab-emailtemplate">
              <ul class="nav nav-tabs">
                <li class="active"><a href="#tab-emailtemplate_front" data-toggle="tab"><?php echo $tab_emailtemplate_front; ?></a></li>
                <li><a href="#tab-emailtemplate_admin" data-toggle="tab"><?php echo $tab_emailtemplate_admin; ?></a></li>
              </ul>
              <div class="tab-content">
                <div class="tab-pane active" id="tab-emailtemplate_front">
                  <div class="row">
                    <div class="col-sm-5">
                      <span class="help"><?php echo $help_email_user_sc; ?></span>
                    </div>
                    <div class="col-sm-7">
                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <td><?php echo $text_sc_text; ?></td>
                              <td><?php echo $text_sc_var; ?></td>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td><?php echo $sc_code; ?></td>
                              <td>{code}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_verification_url; ?></td>
                              <td>{verification_url}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_user_email; ?></td>
                              <td>{user_email}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_name; ?></td>
                              <td>{store_name}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_link; ?></td>
                              <td>{store_link}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_logo; ?></td>
                              <td>{store_logo}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="col-sm-2 control-label" for="input-mail_admin_email"><?php echo $entry_mail_admin_email; ?> </label>
                    <div class="col-sm-5">
                      <input type="text" name="mpgdpr_mail_admin_email" value="<?php echo $mpgdpr_mail_admin_email; ?>" class="form-control" />
                      <span class="help"><?php echo $help_mail_admin_email; ?></span>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-3 col-sm-12">
                      <ul class="nav nav-pills nav-stacked ostab">
                        <li class="active"><a href="#tab-emailtemplate_rfp" data-toggle="tab"><?php echo $legend_restrict_processing; ?></a></li>
                        <li><a href="#tab-emailtemplate_nrfp" data-toggle="tab"><?php echo $legend_restrict_processing_no; ?></a></li>
                        <li><a href="#tab-emailtemplate_apd" data-toggle="tab"><?php echo $legend_access_personaldata; ?></a></li>
                        <li><a href="#tab-emailtemplate_rcapd" data-toggle="tab"><?php echo $legend_rc_access_personaldata; ?></a></li>
                        <li><a href="#tab-emailtemplate_rdpd" data-toggle="tab"><?php echo $legend_requestdelete_personaldata; ?></a></li>
                        <li><a href="#tab-emailtemplate_rcrdpd" data-toggle="tab"><?php echo $legend_rc_requestdelete_personaldata; ?></a></li>
                        <li><a href="#tab-emailtemplate_frdpd" data-toggle="tab"><?php echo $legend_requestdelete_personaldata_complete; ?></a></li>
                      </ul>
                    </div>
                    <div class="col-md-9 col-sm-12">
                      <div class="tab-content">
                        <div class="tab-pane active" id="tab-emailtemplate_rfp">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_restrict_processing; ?> <br/> <small><?php echo $help_emailtemplate_restrict_processing; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_rfp"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['rfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rfp]" value="1" <?php echo (!empty($mpgdpr_mail_admin['rfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['rfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rfp]" value="0" <?php echo (empty($mpgdpr_mail_admin['rfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rfp_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rfp_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rfp_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rfp_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rfp][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rfp_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rfp']) && isset($mpgdpr_emailtemplate['rfp']['admin']) && isset($mpgdpr_emailtemplate['rfp']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rfp']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rfp_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rfp][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rfp_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rfp']) && isset($mpgdpr_emailtemplate['rfp']['admin']) && isset($mpgdpr_emailtemplate['rfp']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rfp']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_rfp"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['rfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rfp]" value="1" <?php echo (!empty($mpgdpr_mail_user['rfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['rfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rfp]" value="0" <?php echo (empty($mpgdpr_mail_user['rfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rfp_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rfp_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rfp_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rfp_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rfp][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rfp_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rfp']) && isset($mpgdpr_emailtemplate['rfp']['user']) && isset($mpgdpr_emailtemplate['rfp']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rfp']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rfp_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rfp][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rfp_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rfp']) && isset($mpgdpr_emailtemplate['rfp']['user']) && isset($mpgdpr_emailtemplate['rfp']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rfp']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_nrfp">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_restrict_processing_no; ?> <br/> <small><?php echo $help_emailtemplate_restrict_processing_no; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_nrfp"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['nrfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[nrfp]" value="1" <?php echo (!empty($mpgdpr_mail_admin['nrfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['nrfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[nrfp]" value="0" <?php echo (empty($mpgdpr_mail_admin['nrfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_nrfp_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_nrfp_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_nrfp_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_nrfp_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[nrfp][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_nrfp_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['nrfp']) && isset($mpgdpr_emailtemplate['nrfp']['admin']) && isset($mpgdpr_emailtemplate['nrfp']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['nrfp']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_nrfp_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[nrfp][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_nrfp_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['nrfp']) && isset($mpgdpr_emailtemplate['nrfp']['admin']) && isset($mpgdpr_emailtemplate['nrfp']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['nrfp']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_nrfp"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['nrfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[nrfp]" value="1" <?php echo (!empty($mpgdpr_mail_user['nrfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['nrfp']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[nrfp]" value="0" <?php echo (empty($mpgdpr_mail_user['nrfp'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_nrfp_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_nrfp_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_nrfp_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_nrfp_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[nrfp][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_nrfp_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['nrfp']) && isset($mpgdpr_emailtemplate['nrfp']['user']) && isset($mpgdpr_emailtemplate['nrfp']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['nrfp']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_nrfp_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[nrfp][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_nrfp_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['nrfp']) && isset($mpgdpr_emailtemplate['nrfp']['user']) && isset($mpgdpr_emailtemplate['nrfp']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['nrfp']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_apd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_access_personaldata; ?> <br/> <small><?php echo $help_emailtemplate_access_personaldata; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_apd"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['apd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[apd]" value="1" <?php echo (!empty($mpgdpr_mail_admin['apd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['apd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[apd]" value="0" <?php echo (empty($mpgdpr_mail_admin['apd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_apd_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_apd_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_apd_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_apd_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[apd][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_apd_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['apd']) && isset($mpgdpr_emailtemplate['apd']['admin']) && isset($mpgdpr_emailtemplate['apd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['apd']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_apd_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[apd][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_apd_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['apd']) && isset($mpgdpr_emailtemplate['apd']['admin']) && isset($mpgdpr_emailtemplate['apd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['apd']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_apd"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['apd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[apd]" value="1" <?php echo (!empty($mpgdpr_mail_user['apd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['apd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[apd]" value="0" <?php echo (empty($mpgdpr_mail_user['apd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_apd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_apd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_apd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_apd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[apd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_apd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['apd']) && isset($mpgdpr_emailtemplate['apd']['user']) && isset($mpgdpr_emailtemplate['apd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['apd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_apd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[apd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_apd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['apd']) && isset($mpgdpr_emailtemplate['apd']['user']) && isset($mpgdpr_emailtemplate['apd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['apd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_rcapd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_rc_access_personaldata; ?> <br/> <small><?php echo $help_emailtemplate_rc_access_personaldata; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_rcapd"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['rcapd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rcapd]" value="1" <?php echo (!empty($mpgdpr_mail_admin['rcapd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['rcapd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rcapd]" value="0" <?php echo (empty($mpgdpr_mail_admin['rcapd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rcapd_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rcapd_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rcapd_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcapd_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rcapd][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rcapd_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rcapd']) && isset($mpgdpr_emailtemplate['rcapd']['admin']) && isset($mpgdpr_emailtemplate['rcapd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcapd']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcapd_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rcapd][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rcapd_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rcapd']) && isset($mpgdpr_emailtemplate['rcapd']['admin']) && isset($mpgdpr_emailtemplate['rcapd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcapd']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_rcapd"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['rcapd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rcapd]" value="1" <?php echo (!empty($mpgdpr_mail_user['rcapd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['rcapd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rcapd]" value="0" <?php echo (empty($mpgdpr_mail_user['rcapd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rcapd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rcapd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rcapd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcapd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rcapd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rcapd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rcapd']) && isset($mpgdpr_emailtemplate['rcapd']['user']) && isset($mpgdpr_emailtemplate['rcapd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcapd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcapd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rcapd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rcapd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rcapd']) && isset($mpgdpr_emailtemplate['rcapd']['user']) && isset($mpgdpr_emailtemplate['rcapd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcapd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                        </div>

                        <div class="tab-pane" id="tab-emailtemplate_rdpd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_requestdelete_personaldata; ?> <br/> <small><?php echo $help_emailtemplate_requestdelete_personaldata; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_rdpd"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['rdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rdpd]" value="1" <?php echo (!empty($mpgdpr_mail_admin['rdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['rdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rdpd]" value="0" <?php echo (empty($mpgdpr_mail_admin['rdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rdpd_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rdpd_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rdpd_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rdpd_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rdpd][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rdpd_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rdpd']) && isset($mpgdpr_emailtemplate['rdpd']['admin']) && isset($mpgdpr_emailtemplate['rdpd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rdpd']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rdpd_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rdpd][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rdpd_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rdpd']) && isset($mpgdpr_emailtemplate['rdpd']['admin']) && isset($mpgdpr_emailtemplate['rdpd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rdpd']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_rdpd"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['rdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rdpd]" value="1" <?php echo (!empty($mpgdpr_mail_user['rdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['rdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rdpd]" value="0" <?php echo (empty($mpgdpr_mail_user['rdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rdpd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rdpd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rdpd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rdpd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rdpd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rdpd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rdpd']) && isset($mpgdpr_emailtemplate['rdpd']['user']) && isset($mpgdpr_emailtemplate['rdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rdpd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rdpd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rdpd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rdpd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rdpd']) && isset($mpgdpr_emailtemplate['rdpd']['user']) && isset($mpgdpr_emailtemplate['rdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rdpd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_rcrdpd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_rc_requestdelete_personaldata; ?> <br/> <small><?php echo $help_emailtemplate_rc_requestdelete_personaldata; ?></small></h4>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_admin; ?> <br/> <small><?php echo $help_emailtemplate_admin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_admin_rcrdpd"><?php echo $entry_mail_admin; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_admin['rcrdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rcrdpd]" value="1" <?php echo (!empty($mpgdpr_mail_admin['rcrdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_admin['rcrdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_admin[rcrdpd]" value="0" <?php echo (empty($mpgdpr_mail_admin['rcrdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_admin; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rcrdpd_admin">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rcrdpd_admin<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rcrdpd_admin<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcrdpd_admin_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rcrdpd][admin][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rcrdpd_admin_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rcrdpd']) && isset($mpgdpr_emailtemplate['rcrdpd']['admin']) && isset($mpgdpr_emailtemplate['rcrdpd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcrdpd']['admin'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcrdpd_admin_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rcrdpd][admin][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rcrdpd_admin_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rcrdpd']) && isset($mpgdpr_emailtemplate['rcrdpd']['admin']) && isset($mpgdpr_emailtemplate['rcrdpd']['admin'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcrdpd']['admin'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_rcrdpd"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['rcrdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rcrdpd]" value="1" <?php echo (!empty($mpgdpr_mail_user['rcrdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['rcrdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[rcrdpd]" value="0" <?php echo (empty($mpgdpr_mail_user['rcrdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>
                          <ul class="nav nav-tabs" id="lang_emailtemplate_rcrdpd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_rcrdpd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_rcrdpd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcrdpd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[rcrdpd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_rcrdpd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['rcrdpd']) && isset($mpgdpr_emailtemplate['rcrdpd']['user']) && isset($mpgdpr_emailtemplate['rcrdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcrdpd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_rcrdpd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[rcrdpd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_rcrdpd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['rcrdpd']) && isset($mpgdpr_emailtemplate['rcrdpd']['user']) && isset($mpgdpr_emailtemplate['rcrdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['rcrdpd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_frdpd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_requestdelete_personaldata_complete; ?> <br/> <small><?php echo $help_emailtemplate_requestdelete_personaldata_complete; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_user_final"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['frdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[frdpd]" value="1" <?php echo (!empty($mpgdpr_mail_user['frdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['frdpd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[frdpd]" value="0" <?php echo (empty($mpgdpr_mail_user['frdpd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user_final; ?></span>
                            </div>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <ul class="nav nav-tabs" id="lang_emailtemplate_frdpd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_frdpd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_frdpd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_frdpd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[frdpd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_frdpd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['frdpd']) && isset($mpgdpr_emailtemplate['frdpd']['user']) && isset($mpgdpr_emailtemplate['frdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['frdpd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_frdpd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[frdpd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_frdpd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['frdpd']) && isset($mpgdpr_emailtemplate['frdpd']['user']) && isset($mpgdpr_emailtemplate['frdpd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['frdpd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="tab-pane" id="tab-emailtemplate_admin">
                  <div class="row">
                    <div class="col-sm-5">
                      <span class="help"><?php echo $help_email_from_admin_sc; ?></span>
                    </div>
                    <div class="col-sm-7">
                      <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <td><?php echo $text_sc_text; ?></td>
                              <td><?php echo $text_sc_var; ?></td>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td><?php echo $sc_deny_reason; ?></td>
                              <td>{denyreason}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_user_email; ?></td>
                              <td>{user_email}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_name; ?></td>
                              <td>{store_name}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_link; ?></td>
                              <td>{store_link}</td>
                            </tr>
                            <tr>
                              <td><?php echo $sc_store_logo; ?></td>
                              <td>{store_logo}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-3 col-sm-12">
                      <ul class="nav nav-pills nav-stacked ostab">
                        <li class="active"><a href="#tab-emailtemplate_aarpb" data-toggle="tab"><?php echo $legend_anonymouse_processing; ?></a></li>
                        <li><a href="#tab-emailtemplate_aard" data-toggle="tab"><?php echo $legend_anonymouse_deny; ?></a></li>
                        <li><a href="#tab-emailtemplate_aapdsr" data-toggle="tab"><?php echo $legend_access_personaldata_send_report; ?></a></li>
                        <li><a href="#tab-emailtemplate_aapdd" data-toggle="tab"><?php echo $legend_access_personaldata_deny; ?></a></li>
                      </ul>
                    </div>
                    <div class="col-md-9 col-sm-12">
                      <div class="tab-content">
                        <div class="tab-pane active" id="tab-emailtemplate_aarpb">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_anonymouse_begin; ?> <br/> <small><?php echo $help_emailtemplate_anonymouse_begin; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_anonymouse_begin"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['aarpb']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aarpb]" value="1" <?php echo (!empty($mpgdpr_mail_user['aarpb'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['aarpb']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aarpb]" value="0" <?php echo (empty($mpgdpr_mail_user['aarpb'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <ul class="nav nav-tabs" id="lang_emailtemplate_aarpb_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_aarpb_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_aarpb_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aarpb_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[aarpb][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_aarpb_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['aarpb']) && isset($mpgdpr_emailtemplate['aarpb']['user']) && isset($mpgdpr_emailtemplate['aarpb']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aarpb']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aarpb_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[aarpb][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_aarpb_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['aarpb']) && isset($mpgdpr_emailtemplate['aarpb']['user']) && isset($mpgdpr_emailtemplate['aarpb']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aarpb']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_aard">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_anonymouse_deny; ?> <br/> <small><?php echo $help_emailtemplate_anonymouse_deny; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_anonymouse_begin"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['aard']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aard]" value="1" <?php echo (!empty($mpgdpr_mail_user['aard'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['aard']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aard]" value="0" <?php echo (empty($mpgdpr_mail_user['aard'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <ul class="nav nav-tabs" id="lang_emailtemplate_aard_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_aard_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_aard_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aard_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[aard][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_aard_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['aard']) && isset($mpgdpr_emailtemplate['aard']['user']) && isset($mpgdpr_emailtemplate['aard']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aard']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aard_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[aard][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_aard_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['aard']) && isset($mpgdpr_emailtemplate['aard']['user']) && isset($mpgdpr_emailtemplate['aard']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aard']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_aapdsr">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_personaldata_send_report; ?> <br/> <small><?php echo $help_emailtemplate_personaldata_send_report; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_anonymouse_begin"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['aapdsr']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aapdsr]" value="1" <?php echo (!empty($mpgdpr_mail_user['aapdsr'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['aapdsr']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aapdsr]" value="0" <?php echo (empty($mpgdpr_mail_user['aapdsr'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <ul class="nav nav-tabs" id="lang_emailtemplate_aapdsr_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_aapdsr_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_aapdsr_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aapdsr_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[aapdsr][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_aapdsr_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['aapdsr']) && isset($mpgdpr_emailtemplate['aapdsr']['user']) && isset($mpgdpr_emailtemplate['aapdsr']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aapdsr']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aapdsr_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[aapdsr][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_aapdsr_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['aapdsr']) && isset($mpgdpr_emailtemplate['aapdsr']['user']) && isset($mpgdpr_emailtemplate['aapdsr']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aapdsr']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                        <div class="tab-pane" id="tab-emailtemplate_aapdd">
                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_personaldata_deny; ?> <br/> <small><?php echo $help_emailtemplate_personaldata_deny; ?></small></h4>

                          <div class="form-group mp-buttons">
                            <label class="col-sm-2 control-label" for="input-mail_anonymouse_begin"><?php echo $entry_mail_user; ?> </label>
                            <div class="col-sm-5">
                              <div class="btn-group btn-group-justified" data-toggle="buttons">
                                <label class="btn btn-primary <?php echo !empty($mpgdpr_mail_user['aapdd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aapdd]" value="1" <?php echo (!empty($mpgdpr_mail_user['aapdd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_yes; ?>
                                </label>
                                <label class="btn btn-primary <?php echo empty($mpgdpr_mail_user['aapdd']) ? 'active' : '';  ?>">
                                  <input type="radio" name="mpgdpr_mail_user[aapdd]" value="0" <?php echo (empty($mpgdpr_mail_user['aapdd'])) ? 'checked="checked"' : '';  ?> />
                                  <?php echo $text_no; ?>
                                </label>
                              </div>
                              <span class="help"><?php echo $help_mail_user; ?></span>
                            </div>
                          </div>

                          <h4 class="subtitle"><i class="fa fa-exclamation-triangle"></i>  <?php echo $entry_emailtemplate_user; ?> <br/> <small><?php echo $help_emailtemplate_user; ?></small></h4>

                          <ul class="nav nav-tabs" id="lang_emailtemplate_aapdd_user">
                            <?php foreach ($languages as $language) { ?>
                            <li><a href="#lang_emailtemplate_aapdd_user<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="<?php echo $language['lang_flag']; ?>" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                            <?php } ?>
                          </ul>
                          <div class="tab-content">
                            <?php foreach ($languages as $language) { ?>
                            <div class="tab-pane" id="lang_emailtemplate_aapdd_user<?php echo $language['language_id']; ?>">
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aapdd_user_subject<?php echo $language['language_id']; ?>"><?php echo $entry_email_subject; ?> </label>
                                <div class="col-sm-10">
                                  <input type="text" name="mpgdpr_emailtemplate[aapdd][user][<?php echo $language['language_id']; ?>][subject]" id="input-emailtemplate_aapdd_user_subject<?php echo $language['language_id']; ?>" class="form-control" value="<?php echo isset($mpgdpr_emailtemplate['aapdd']) && isset($mpgdpr_emailtemplate['aapdd']['user']) && isset($mpgdpr_emailtemplate['aapdd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aapdd']['user'][$language['language_id']]['subject'] : ''; ?>" />
                                </div>
                              </div>
                              <div class="form-group required">
                                <label class="col-sm-2 control-label" for="input-emailtemplate_aapdd_user_msg<?php echo $language['language_id']; ?>"><?php echo $entry_email_msg; ?> </label>
                                <div class="col-sm-10">
                                  <textarea name="mpgdpr_emailtemplate[aapdd][user][<?php echo $language['language_id']; ?>][msg]" id="input-emailtemplate_aapdd_user_msg<?php echo $language['language_id']; ?>" class="form-control summernote" data-toggle="summernote" data-lang="<?php echo $summernote; ?>"><?php echo isset($mpgdpr_emailtemplate['aapdd']) && isset($mpgdpr_emailtemplate['aapdd']['user']) && isset($mpgdpr_emailtemplate['aapdd']['user'][$language['language_id']]) ? $mpgdpr_emailtemplate['aapdd']['user'][$language['language_id']]['msg'] : ''; ?></textarea>
                                </div>
                              </div>
                            </div>
                            <?php } ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- // 01-05-2022: updation end -->
            <div class="tab-pane" id="tab-support">
              <div class="bs-callout bs-callout-info">
                <h4>ModulePoints <?php echo $heading_title; ?></h4>
                <center><strong><?php echo $heading_title; ?> </strong></center> <br/>
              </div>
              <fieldset>
                <div class="form-group">
                  <div class="col-md-12 col-xs-12">
                    <h4 class="text-mpsuccess text-center"><i class="fa fa-thumbs-up" aria-hidden="true"></i> Thanks For Choosing Our Extension</h4>
                    <h4 class="text-mpsuccess text-center"><i class="fa fa-phone" aria-hidden="true"></i>Kindly Write Us At Support Email For Support</h4>
                    <ul class="list-group">
                      <li class="list-group-item clearfix">support@modulepoints.com <span class="badge"><a href="mailto:support@modulepoints.com?Subject=Request Support: <?php echo $heading_title; ?> Extension"><i class="fa fa-envelope"></i> Contact Us</a></span></li>
                    </ul>
                  </div>
                </div>
              </fieldset>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- // 01-05-2022: updation start -->
  <?php echo $texteditor; ?>
  <!-- // 01-05-2022: updation end -->
  <script type="text/javascript"><!--
  $(function() { $('.colorpicker').colorpicker(); });
  $('.nav-tabs').each(function() {
    $(this).find('a:first').tab('show');
  });
  //--></script>
</div>
<?php echo $footer; ?>