<?php echo $header; ?>
<div id="account-mpgdpr" class="container">
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
  <style type="text/css">
    #account-mpgdpr .btn-link {
      padding: 0;
    }
  </style>
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
      <h2><?php echo $text_right_rectification; ?></h2>
      <div><?php echo $text_right_rectification_info; ?></div>
      <ul class="list-unstyled">
        <li><a href="<?php echo $account; ?>"><?php echo $text_edit; ?></a></li>
        <li><a href="<?php echo $address; ?>"><?php echo $text_address; ?></a></li>
        <li><a href="<?php echo $password; ?>"><?php echo $text_password; ?></a></li>
        <li><a href="<?php echo $newsletter; ?>"><?php echo $text_newsletter; ?></a></li>
      </ul>
      <h2><?php echo $text_right_portability; ?></h2>
      <div><?php echo $text_right_portability_info; ?></div>
      <ul class="list-unstyled">
        <li><button class="btn-link download-request" data-request="personal_data"><?php echo $text_port_personal_data; ?></button></li>
        <li><button class="btn-link download-request" data-request="addresses"><?php echo $text_port_address; ?></button></li>
        <li><button class="btn-link download-request" data-request="orders"><?php echo $text_port_orders; ?></button></li>
        <li><button class="btn-link download-request" data-request="gdpr_requests"><?php echo $text_my_gdpr_requests; ?></button></li>
        <li><button class="btn-link download-request" data-request="wishlists"><?php echo $text_my_wishlists; ?></button></li>
        <li><button class="btn-link download-request" data-request="transactions"><?php echo $text_my_transactions; ?></button></li>
        <li><button class="btn-link download-request" data-request="history"><?php echo $text_my_history; ?></button></li>
        <?php if($customer_search) { ?><li><button class="btn-link download-request" data-request="search"><?php echo $text_my_search; ?></button></li><?php } ?>
        <li><button class="btn-link download-request" data-request="rewardspoints"><?php echo $text_my_rewardspoints; ?></button></li>
        <li><button class="btn-link download-request" data-request="activities"><?php echo $text_my_activities; ?></button></li>
      </ul>

      <h2><?php echo $text_right_restriction; ?></h2>
      <div><?php echo $text_right_restriction_info; ?></div>
      <ul class="list-unstyled">
        <li><a href="<?php echo $my_restrictions; ?>"><?php echo $text_my_restrictions; ?></a></li>
      </ul>

      <h2><?php echo $text_right_personsal_data; ?></h2>
      <div><?php echo $text_right_personsal_data_info; ?></div>
      <ul class="list-unstyled">
        <li><a href="<?php echo $data_request; ?>"><?php echo $text_personsal_data_request; ?></a></li>
      </ul>

      <h2><?php echo $text_right_forget_me; ?></h2>
      <div><?php echo $text_right_forget_me_info; ?></div>
      <ul class="list-unstyled">
        <li><a href="<?php echo $deleteme; ?>"><?php echo $text_forget_me; ?></a></li>
      </ul>

      <div class="buttons clearfix">
        <div class="pull-left"><a href="<?php echo $back; ?>" class="btn btn-default"><?php echo $button_back; ?></a></div>
      </div>
      <?php echo $content_bottom; ?></div>
    <?php echo $column_right; ?></div>
    <script type="text/javascript">
      $('.download-request').on('click', function() {
        var route = '';
        var $this = $(this);
        var request = $(this).attr('data-request');

        switch(request) {
          case 'personal_data':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getAccountData';
          break;
          case 'addresses':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getAddresses';
          break;
          case 'orders':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getOrders';
          break;
          case 'gdpr_requests':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getGDPRRequests';
          break;
          case 'wishlists':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getWishlists';
          break;
          case 'transactions':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getTransactions';
          break;
          case 'history':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getHistory';
          break;
          <?php if ($customer_search) { ?>case 'search':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getSearchHistory';
          break;<?php } ?>
          case 'rewardspoints':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getRewardPointsHistory';
          break;
          case 'activities':
          route = '<?php echo $extension_path; ?>mpgdpr/account/mpgdpr/getActivityHistory';
          break;
        }
        if(route) {
        $.ajax({
          url: 'index.php?route='+route,
          type: 'post',
          data: 'request='+request,
          dataType: 'json',
          beforeSend: function() {
            $this.append('<span>&nbsp;<i class="fa fa-spin fa-spinner"></i></span>');
            $this.css('pointer-events','none');
          },
          complete: function() {
            $this.find('span').remove();
            $this.css('pointer-events','');
          },
          success: function(json) {
            $('.alert, .text-danger').remove();

            if (json['redirect']) {
              location = json['redirect'];
            }

            if (json['warning']) {
              $('#content').parent().before('<div class="alert alert-warning"><i class="fa fa-check-circle"></i> ' + json['warning'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

              $('html, body').animate({ scrollTop: 0 }, 'slow');

            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
        }
      });
    </script>
</div>
<?php echo $footer; ?>