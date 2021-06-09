<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* basel/template/product/question.twig */
class __TwigTemplate_9a0a1798f516127cde74014e8fb420ea8a71a65e3a0a884d0a3143f011532756 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<div class=\"row\">
<div class=\"col-sm-6\">
\t<h4><b>";
        // line 3
        echo ($context["basel_text_recent_questions"] ?? null);
        echo "</b></h4>
\t<div id=\"question\"></div>
</div>
<div class=\"col-sm-6 right\">
<form class=\"form-horizontal\" id=\"form-question\">
    
    <h4 id=\"ask_heading\"><b>";
        // line 9
        echo ($context["basel_heading_ask"] ?? null);
        echo "</b></h4>
    
    <div class=\"form-group required\">
      <div class=\"col-sm-12\">
        <label class=\"control-label\" for=\"input-name\">";
        // line 13
        echo ($context["basel_entry_name"] ?? null);
        echo "</label>
        <input type=\"text\" name=\"q_name\" value=\"\" id=\"input-name\" class=\"form-control grey\" />
      </div>
    </div>
    
    <div class=\"form-group required\">
      <div class=\"col-sm-12\">
        <label class=\"control-label\" for=\"input-email\">";
        // line 20
        echo ($context["basel_entry_email"] ?? null);
        echo "</label>
        <input type=\"text\" name=\"q_email\" value=\"\" id=\"input-email\" class=\"form-control grey\" />
      </div>
    </div>
    
    <div class=\"form-group required\">
      <div class=\"col-sm-12\">
        <label class=\"control-label\" for=\"input-question\">";
        // line 27
        echo ($context["basel_entry_question"] ?? null);
        echo "</label>
        <textarea name=\"q_text\" rows=\"5\" id=\"input-question\" class=\"form-control grey\"></textarea>
      </div>
    </div>
    
    <div class=\"form-group required margin-b10\">
      <div class=\"col-sm-12\">
      <label class=\"control-label\">";
        // line 34
        echo ($context["basel_entry_captcha"] ?? null);
        echo "</label>
        <div class=\"input-group\">
        <span class=\"input-group-addon captcha_addon grey\"><img src=\"index.php?route=extension/basel/question/question_captcha\" id=\"captcha_product_questions\" alt=\"\" class=\"captchaimg\" /></span>
        <input type=\"text\" name=\"captcha_product_questions\" value=\"\" id=\"input-captcha_product_questions\" class=\"form-control grey\" />
        </div>
      </div>
    </div>
    
     <div class=\"buttons clearfix\">
      <div class=\"text-right\">
        <button type=\"button\" id=\"button-question\" data-loading-text=\"";
        // line 44
        echo ($context["text_loading"] ?? null);
        echo "\" class=\"btn btn-outline\">";
        echo ($context["basel_button_send"] ?? null);
        echo "</button>
      </div>
    </div>

  </form>
</div>
</div>
              
<script><!--
\$('#question').delegate('.pagination a', 'click', function(e) {
  e.preventDefault();
\t\$(\"html,body\").animate({scrollTop:((\$(\"#question\").offset().top)-50)},500);
    \$('#question').fadeOut(50);

    \$('#question').load(this.href);

    \$('#question').fadeIn(500);
\t
});

\$('#question').load('index.php?route=extension/basel/question/question_list&product_id=";
        // line 64
        echo ($context["product_id"] ?? null);
        echo "');

\$('#button-question').on('click', function() {
\t\$.ajax({
\t\turl: 'index.php?route=extension/basel/question/ask_question&product_id=";
        // line 68
        echo ($context["product_id"] ?? null);
        echo "',
\t\ttype: 'post',
\t\tdataType: 'json',
\t\tdata: 'name=' + encodeURIComponent(\$('input[name=\\'q_name\\']').val()) + 
\t\t\t  '&email=' + encodeURIComponent(\$('input[name=\\'q_email\\']').val()) + 
\t\t\t  '&text=' + encodeURIComponent(\$('textarea[name=\\'q_text\\']').val()) + 
\t\t\t  '&captcha_product_questions=' + encodeURIComponent(\$('input[name=\\'captcha_product_questions\\']').val()),
\t\t
\t\tbeforeSend: function() {
\t\t\t\$('#button-question').button('loading');
\t\t},
\t\tcomplete: function() {
\t\t\t\$('#button-question').button('reset');
\t\t\t\$('#captcha_product_questions').attr('src', 'index.php?route=extension/basel/question/question_captcha');
\t\t\t\$('input[name=\\'captcha_product_questions\\']').val('');

\t\t},
\t\tsuccess: function(json) {
\t\t\t\$('.alert-success, .alert-danger').remove();
\t\t\t
\t\t\tif (json.error) {
\t\t\t\t\$('#ask_heading').after('<div class=\"alert alert-sm alert-danger\"><i class=\"fa fa-exclamation-circle\"></i> ' + json.error + '</div>');
\t\t\t}
\t\t\t
\t\t\tif (json.success) {
\t\t\t\t\$('#ask_heading').after('<div class=\"alert alert-sm alert-success\"><i class=\"fa fa-check-circle\"></i> ' + json.success + '</div>');
\t\t\t\t
\t\t\t\t\$('input[name=\\'q_name\\']').val('');
\t\t\t\t\$('input[name=\\'q_email\\']').val('');
\t\t\t\t\$('textarea[name=\\'q_text\\']').val('');
\t\t\t\t\$('input[name=\\'captcha_product_questions\\']').val('');
\t\t\t}
\t\t}
\t});
});
//--></script>";
    }

    public function getTemplateName()
    {
        return "basel/template/product/question.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  132 => 68,  125 => 64,  100 => 44,  87 => 34,  77 => 27,  67 => 20,  57 => 13,  50 => 9,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "basel/template/product/question.twig", "");
    }
}
