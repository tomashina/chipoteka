<!DOCTYPE html>
<html dir="<?php echo $direction; ?>" lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8" />
<title><?php echo $title; ?></title>
</head>
<body>
<div id="content">
  <?php if (!empty($policy_title)) { ?>
  <h4><?php echo $policy_title; ?></h4>
  <?php echo $policy_description; ?>
  <?php } else { ?>
  <h1><?php echo $error; ?></h1>
  <?php } ?>
</div>
</body>
</html>