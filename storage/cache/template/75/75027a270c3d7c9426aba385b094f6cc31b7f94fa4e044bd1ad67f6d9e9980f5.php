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

/* basel/template/product/single_product.twig */
class __TwigTemplate_4339078212f97602ee1a51ec02cb6abe669d19bc8cceee7f68c74363d6a2cfbe extends \Twig\Template
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
        echo "
<div class=\"col-12  product-card-wrapper\">

    <div class=\"card product-card\">
        <div class=\"position-relative\" >
            <button class=\"btn btn-primary btn-basket \"  type=\"button\" onclick=\"cart.add('";
        // line 6
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "product_id", [], "any", false, false, false, 6);
        echo "', '";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "minimum", [], "any", false, false, false, 6);
        echo "');\"><i class=\"fad fs-xl fa-shopping-basket\"></i><span class=\"sr-only\" ";
        echo ($context["button_cart"] ?? null);
        echo "</span></button>
            <div class=\"card-img-top d-block overflow-hidden\" ><img src=\"";
        // line 7
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "thumb", [], "any", false, false, false, 7);
        echo "\" alt=\"";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "name", [], "any", false, false, false, 7);
        echo "\"></div>
            <div class=\"product-badge-cpta product-badge-cpta__free-shipping\">
                <span> Besplatna dostava</span>
                <i class=\"fas fa-shipping-fast\"></i>
            </div>
        </div>
      <a class=\"stretched-link\"  href=\"";
        // line 13
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "href", [], "any", false, false, false, 13);
        echo "\">
        ";
        // line 15
        echo "
        <div class=\"card-body\">
            <h3 class=\"product-title\">";
        // line 17
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "name", [], "any", false, false, false, 17);
        echo "</h3>
                ";
        // line 18
        if (twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 18)) {
            // line 19
            echo "                    <div class=\"product-price\">
                        ";
            // line 20
            if (twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 20)) {
                // line 21
                echo "                            <span>";
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 21);
                echo "</span>
                            <span><del>";
                // line 22
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 22);
                echo "</del></span>
                        ";
            } else {
                // line 24
                echo "                            <span> ";
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 24);
                echo "</span>
                        ";
            }
            // line 26
            echo "                    </div><!-- .price -->
                ";
        }
        // line 28
        echo "
            ";
        // line 29
        if (((twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 29) && twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 29)) && ($context["salebadge_status"] ?? null))) {
            // line 30
            echo "                <div class=\"product-badge-cpta product-badge-cpta__discount\">
                    <span> Black Friday</span>
                    <span> Uštedi <strong>";
            // line 32
            echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "sale_badge", [], "any", false, false, false, 32);
            echo "</strong></span>
                </div>
            ";
        }
        // line 35
        echo "

            <div class=\"product-price--web\">
                <span>
                    Web cijena: 
                </span>
                <span>
                    ";
        // line 42
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 42);
        echo "
                </span>
            </div>
        </div>
        
        <div class=\"product-delivery-time\">
            <i class=\"fas fa-shipping-timed\"></i>
               <div class=\"d-flex flex-column\">
               <span>
               Proizvod kod dobavljača
               </span>
               <span>
              Šaljemo do 05.05.2021
               </span>
               </div>
        </div>

    </a>

  </div>
    
</div>";
    }

    public function getTemplateName()
    {
        return "basel/template/product/single_product.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  126 => 42,  117 => 35,  111 => 32,  107 => 30,  105 => 29,  102 => 28,  98 => 26,  92 => 24,  87 => 22,  82 => 21,  80 => 20,  77 => 19,  75 => 18,  71 => 17,  67 => 15,  63 => 13,  52 => 7,  44 => 6,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "basel/template/product/single_product.twig", "/Applications/MAMP/htdocs/chipoteka/upload/catalog/view/theme/basel/template/product/single_product.twig");
    }
}
