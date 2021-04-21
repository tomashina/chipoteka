<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-instagram" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
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
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-instagram" class="form-horizontal">
          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-name"><?php echo $entry_name; ?></label>
            <div class="col-sm-10">
              <input type="text" name="name" value="<?php echo $name; ?>" placeholder="<?php echo $entry_name; ?>" id="input-name" class="form-control" />
              <?php if ($error_name) { ?>
              <div class="text-danger"><?php echo $error_name; ?></div>
              <?php } ?>
            </div>
          </div>
          
         	<div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $entry_status; ?></label>
            <div class="col-sm-10 toggle-btn">
            <?php if (!empty($status)) { ?>
            <label><input type="radio" name="status" value="0" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="status" value="1" checked="checked" /><span><?php echo $text_enabled; ?></span></label>
            <?php } else { ?>
            <label><input type="radio" name="status" value="0" checked="checked" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="status" value="1" /><span><?php echo $text_enabled; ?></span></label>
            <?php } ?>
            </div>                   
            </div>
            
            <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $entry_full_width; ?></label>
            <div class="col-sm-10 toggle-btn">
            <?php if (!empty($full_width)) { ?>
            <label><input type="radio" name="full_width" value="0" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="full_width" value="1" checked="checked" /><span><?php echo $text_enabled; ?></span></label>
            <?php } else { ?>
            <label><input type="radio" name="full_width" value="0" checked="checked" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="full_width" value="1" /><span><?php echo $text_enabled; ?></span></label>
            <?php } ?>
            </div>                   
            </div>
            
            <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $text_use_block_title; ?></label>
            <div class="col-sm-10 toggle-btn">
            <?php if (!empty($use_title)) { ?>
            <label><input type="radio" class="title_select" name="use_title" value="0" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" class="title_select" name="use_title" value="1" checked="checked" /><span><?php echo $text_enabled; ?></span></label>
            <?php } else { ?>
            <label><input type="radio" class="title_select" name="use_title" value="0" checked="checked" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" class="title_select" name="use_title" value="1" /><span><?php echo $text_enabled; ?></span></label>
            <?php } ?>
            </div>                   
            </div>
            
            
            <div class="form-group title_field" style="display:<?php if (!empty($use_title)) { echo 'block'; } else { echo 'none'; } ?>">
            <label class="col-sm-2 control-label"><?php echo $entry_inline_title; ?></label>
            <div class="col-sm-10 toggle-btn">
            <?php if (!empty($title_inline)) { ?>
            <label><input type="radio" name="title_inline" value="0" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="title_inline" value="1" checked="checked" /><span><?php echo $text_enabled; ?></span></label>
            <?php } else { ?>
            <label><input type="radio" name="title_inline" value="0" checked="checked" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" name="title_inline" value="1" /><span><?php echo $text_enabled; ?></span></label>
            <?php } ?>
            </div>
            </div>                   

            <div class="form-group title_field" style="display:<?php if (!empty($use_title)) { echo 'block'; } else { echo 'none'; } ?>">
            <label class="col-sm-2 control-label"><?php echo $text_block_pre_line; ?></label>
            <div class="col-sm-10">
            <?php foreach ($languages as $language) { ?>
            <div class="input-group">
            <span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
            <input type="text" name="title_pl[<?php echo $language['language_id']; ?>]" value="<?php echo isset($title_pl[$language['language_id']]) ? $title_pl[$language['language_id']] : ''; ?>" class="form-control" />
            </div>
            <?php } ?>
            </div>
            </div>
            
            <div class="form-group title_field" style="display:<?php if (!empty($use_title)) { echo 'block'; } else { echo 'none'; } ?>">
            <label class="col-sm-2 control-label"><?php echo $text_block_title; ?></label>
            <div class="col-sm-10">
            <?php foreach ($languages as $language) { ?>
            <div class="input-group">
            <span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
            <input type="text" name="title_m[<?php echo $language['language_id']; ?>]" value="<?php echo isset($title_m[$language['language_id']]) ? $title_m[$language['language_id']] : ''; ?>" class="form-control" />
            </div>
            <?php } ?>
            </div>
            </div>
            
            <div class="form-group title_field" style="display:<?php if (!empty($use_title)) { echo 'block'; } else { echo 'none'; } ?>">
            <label class="col-sm-2 control-label"><?php echo $text_block_sub_line; ?></label>
            <div class="col-sm-10">
            <?php foreach ($languages as $language) { ?>
            <div class="input-group">
            <span class="input-group-addon"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /></span>
            <textarea type="text" name="title_b[<?php echo $language['language_id']; ?>]" class="form-control"><?php echo isset($title_b[$language['language_id']]) ? $title_b[$language['language_id']] : ''; ?></textarea>
            </div>
            <?php } ?>
            </div>
            </div>
            
            <div class="form-group ">
            <label class="col-sm-2 control-label"><?php echo $entry_username; ?></label>
            <div class="col-sm-10">
              <input type="text" name="username" value="<?php echo $username; ?>" class="form-control" />
              <input type="hidden" name="access_token" value="<?php echo $access_token; ?>" class="form-control" />
            </div>
          </div>
          
          
          <div class="form-group ">
            <label class="col-sm-2 control-label"><?php echo $entry_limit; ?></label>
            <div class="col-sm-10">
              <input type="text" name="limit" value="<?php echo $limit; ?>" class="form-control" />
            </div>
          </div>
          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-columns"><?php echo $entry_resolution; ?></label>
            <div class="col-sm-10">
              <select name="resolution" class="form-control">
                <?php if ($resolution) { ?>
                <option value="0"><?php echo $text_low; ?></option>
                <option value="1" selected="selected"><?php echo $text_high; ?></option>
                <?php } else { ?>
                <option value="0" selected="selected"><?php echo $text_low; ?></option>
                <option value="1"><?php echo $text_high; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-columns"><?php echo $entry_columns; ?></label>
            <div class="col-sm-10">
              <select name="columns" id="input-columns" class="form-control">
               
                <?php if ($columns == '6') { ?>
                <option value="6" selected="selected"><?php echo $text_grid6; ?></option>
                <?php } else { ?>
                <option value="6"><?php echo $text_grid6; ?></option>
                <?php } ?>
                
                <?php if ($columns == '5') { ?>
                <option value="5" selected="selected"><?php echo $text_grid5; ?></option>
                <?php } else { ?>
                <option value="5"><?php echo $text_grid5; ?></option>
                <?php } ?>
                
                <?php if ($columns == '4') { ?>
                <option value="4" selected="selected"><?php echo $text_grid4; ?></option>
                <?php } else { ?>
                <option value="4"><?php echo $text_grid4; ?></option>
                <?php } ?>
                
                <?php if ($columns == '3') { ?>
                <option value="3" selected="selected"><?php echo $text_grid3; ?></option>
                <?php } else { ?>
                <option value="3"><?php echo $text_grid3; ?></option>
                <?php } ?>
                
                <?php if ($columns == '2') { ?>
                <option value="2" selected="selected"><?php echo $text_grid2; ?></option>
                <?php } else { ?>
                <option value="2"><?php echo $text_grid2; ?></option>
                <?php } ?>
                
                <?php if ($columns == '1') { ?>
                <option value="1" selected="selected"><?php echo $text_grid1; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_grid1; ?></option>
				<?php } ?>
                
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-columns"><?php echo $entry_columns_md; ?></label>
            <div class="col-sm-10">
              <select name="columns_md" id="input-columns" class="form-control">
               
                <?php if ($columns_md == '6') { ?>
                <option value="6" selected="selected"><?php echo $text_grid6; ?></option>
                <?php } else { ?>
                <option value="6"><?php echo $text_grid6; ?></option>
                <?php } ?>
                
                <?php if ($columns_md == '5') { ?>
                <option value="5" selected="selected"><?php echo $text_grid5; ?></option>
                <?php } else { ?>
                <option value="5"><?php echo $text_grid5; ?></option>
                <?php } ?>
                
                <?php if ($columns_md == '4') { ?>
                <option value="4" selected="selected"><?php echo $text_grid4; ?></option>
                <?php } else { ?>
                <option value="4"><?php echo $text_grid4; ?></option>
                <?php } ?>
                
                <?php if ($columns_md == '3') { ?>
                <option value="3" selected="selected"><?php echo $text_grid3; ?></option>
                <?php } else { ?>
                <option value="3"><?php echo $text_grid3; ?></option>
                <?php } ?>
                
                <?php if ($columns_md == '2') { ?>
                <option value="2" selected="selected"><?php echo $text_grid2; ?></option>
                <?php } else { ?>
                <option value="2"><?php echo $text_grid2; ?></option>
                <?php } ?>
                
                <?php if ($columns_md == '1') { ?>
                <option value="1" selected="selected"><?php echo $text_grid1; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_grid1; ?></option>
				<?php } ?>
                
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-columns"><?php echo $entry_columns_sm; ?></label>
            <div class="col-sm-10">
              <select name="columns_sm" id="input-columns" class="form-control">
               
                <?php if ($columns_sm == '6') { ?>
                <option value="6" selected="selected"><?php echo $text_grid6; ?></option>
                <?php } else { ?>
                <option value="6"><?php echo $text_grid6; ?></option>
                <?php } ?>
                
                <?php if ($columns_sm == '5') { ?>
                <option value="5" selected="selected"><?php echo $text_grid5; ?></option>
                <?php } else { ?>
                <option value="5"><?php echo $text_grid5; ?></option>
                <?php } ?>
                
                <?php if ($columns_sm == '4') { ?>
                <option value="4" selected="selected"><?php echo $text_grid4; ?></option>
                <?php } else { ?>
                <option value="4"><?php echo $text_grid4; ?></option>
                <?php } ?>
                
                <?php if ($columns_sm == '3') { ?>
                <option value="3" selected="selected"><?php echo $text_grid3; ?></option>
                <?php } else { ?>
                <option value="3"><?php echo $text_grid3; ?></option>
                <?php } ?>
                
                <?php if ($columns_sm == '2') { ?>
                <option value="2" selected="selected"><?php echo $text_grid2; ?></option>
                <?php } else { ?>
                <option value="2"><?php echo $text_grid2; ?></option>
                <?php } ?>
                
                <?php if ($columns_sm == '1') { ?>
                <option value="1" selected="selected"><?php echo $text_grid1; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_grid1; ?></option>
				<?php } ?>
                
              </select>
            </div>
          </div>
          
          <div class="form-group ">
            <label class="col-sm-2 control-label"><?php echo $text_padding; ?></label>
            <div class="col-sm-10">
              <input type="text" name="padding" value="<?php echo $padding; ?>" class="form-control" />
            </div>
          </div>
          	
          <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $text_use_margin; ?></label>
            <div class="col-sm-10 toggle-btn">
            <?php if (!empty($use_margin)) { ?>
            <label><input type="radio" class="margin_select" name="use_margin" value="0" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" class="margin_select" name="use_margin" value="1" checked="checked" /><span><?php echo $text_enabled; ?></span></label>
            <?php } else { ?>
            <label><input type="radio" class="margin_select" name="use_margin" value="0" checked="checked" /><span><?php echo $text_disabled; ?></span></label>
            <label><input type="radio" class="margin_select" name="use_margin" value="1" /><span><?php echo $text_enabled; ?></span></label>
            <?php } ?>
            </div>                   
            </div>
          
          <div class="form-group margin_field" style="display:<?php if (!empty($use_margin)) { echo 'block'; } else { echo 'none'; } ?>">
            <label class="col-sm-2 control-label"><?php echo $text_margin; ?></label>
            <div class="col-sm-10">
              <input type="text" name="margin" value="<?php echo $margin; ?>" class="form-control" />
            </div>
          </div>
          
        </form>
      </div>
    </div>
  </div>

<script type="text/javascript">
$('.title_select').on('change', function() {
  	if ($(this).val() == '1') {
		$('.title_field').css('display', 'block');
	} else {
		$('.title_field').css('display', 'none');
	}
});
$('.margin_select').on('change', function() {
  	if ($(this).val() == '1') {
		$('.margin_field').css('display', 'block');
	} else {
		$('.margin_field').css('display', 'none');
	}
});
</script>
<style>
.toggle-btn {
	font-size:0;
}
.toggle-btn label {
	margin-bottom:0px;
}
.toggle-btn input[type="radio"] {
	display:none;
}
.toggle-btn span {
	font-size:12px;
	background:#f5f5f5;
	font-weight:normal;
	cursor:pointer;
	padding:8px 12px;
	display:inline-block;
	background:#fafafa;
   color:#666666;
    -webkit-box-shadow: inset 0 1px 4px rgba(41, 41, 41, 0.15);
    -moz-box-shadow: inset 0 1px 4px 0 rgba(41, 41, 41, 0.15);
    box-shadow: inset 0 1px 4px rgba(41, 41, 41, 0.15);
	-webkit-text-shadow:1px 1px 0 #ffffff;
	-moz-text-shadow:1px 1px 0 #ffffff;
	text-shadow:1px 1px 0 #ffffff;
}
.toggle-btn label:first-child span {
	border-radius:3px 0 0 3px
}
.toggle-btn label:last-child span {
	border-radius:0 3px 3px 0;
}
.toggle-btn input[type="radio"]:checked + span {
   background:#1e91cf;
   color:#ffffff;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
	-webkit-text-shadow:1px 1px 0 rgba(0, 0, 0, 0.3);
	-moz-text-shadow:1px 1px 0 rgba(0, 0, 0, 0.3);
	text-shadow:1px 1px 0 rgba(0, 0, 0, 0.3);
}
.toggle-btn label:first-child input[type="radio"]:checked + span {
   background:#9f9f9f;
}
.title_field, .carousel_field, .button_field, .margin_field, .subs_field {
	background:#fafafa;
}
</style>
</div>
<?php echo $footer; ?>