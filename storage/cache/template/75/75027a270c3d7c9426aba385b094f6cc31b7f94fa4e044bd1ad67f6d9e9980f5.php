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
      <a class=\"stretched-link\" href=\"";
        // line 5
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "href", [], "any", false, false, false, 5);
        echo "\">

        ";
        // line 8
        echo "
            <div class=\"position-relative\">

                <button class=\"btn btn-primary btn-basket\" type=\"button\" onclick=\"cart.add('";
        // line 11
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "product_id", [], "any", false, false, false, 11);
        echo "', '";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "minimum", [], "any", false, false, false, 11);
        echo "');\"><i class=\"fad fs-xl fa-shopping-basket\"></i><span class=\"sr-only\" ";
        echo ($context["button_cart"] ?? null);
        echo "</span></button>

                <div class=\"card-img-top d-block overflow-hidden\" href=\"";
        // line 13
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "href", [], "any", false, false, false, 13);
        echo "\"><img src=\"";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "thumb", [], "any", false, false, false, 13);
        echo "\" alt=\"";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "name", [], "any", false, false, false, 13);
        echo "\"></div>

                <div class=\"product-badge-cpta product-badge-cpta__free-shipping\">
                     <span> Besplatna dostava</span>
                     <i class=\"fas fa-shipping-fast\"></i>    
                </div>

            </div>

        <div class=\"card-body\">
            <h3 class=\"product-title\"><a href=\"";
        // line 23
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "href", [], "any", false, false, false, 23);
        echo "\">";
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "name", [], "any", false, false, false, 23);
        echo "</a></h3>

                ";
        // line 25
        if (twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 25)) {
            // line 26
            echo "
                    <div class=\"product-price\">
                        ";
            // line 28
            if (twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 28)) {
                // line 29
                echo "                            <span>";
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 29);
                echo "</span>
                            <span><del>";
                // line 30
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "special", [], "any", false, false, false, 30);
                echo "</del></span>
                        ";
            } else {
                // line 32
                echo "                            <span> ";
                echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 32);
                echo "</span>
                        ";
            }
            // line 34
            echo "                    </div><!-- .price -->

                ";
        }
        // line 37
        echo "                ";
        if (twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "rating", [], "any", false, false, false, 37)) {
            // line 38
            echo "
                <div class=\"star-rating ";
            // line 39
            echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "rating", [], "any", false, false, false, 39);
            echo "\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star\"></i>
                </div>
                ";
        }
        // line 42
        echo "

                <div class=\"product-badge-cpta product-badge-cpta__discount\">
                    <span> Black Friday</span>
                    <span> Uštedi <strong>54%</strong></span>
                </div>


            <div class=\"product-price--web\">

                <span>
                    Web cijena: 
                </span>
                <span>
                    ";
        // line 56
        echo twig_get_attribute($this->env, $this->source, ($context["product"] ?? null), "price", [], "any", false, false, false, 56);
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
        return array (  143 => 56,  127 => 42,  121 => 39,  118 => 38,  115 => 37,  110 => 34,  104 => 32,  99 => 30,  94 => 29,  92 => 28,  88 => 26,  86 => 25,  79 => 23,  62 => 13,  53 => 11,  48 => 8,  43 => 5,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "basel/template/product/single_product.twig", "/Applications/MAMP/htdocs/chipoteka/upload/catalog/view/theme/basel/template/product/single_product.twig");
    }
}
