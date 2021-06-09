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

/* basel/template/product/category.twig */
class __TwigTemplate_38f40d46e0f26d0b623d61561a19443ffbbda8a8168da8e2002013d9edf1aa71 extends \Twig\Template
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
        echo ($context["header"] ?? null);
        echo "
<main class=\"offcanvas-enabled\" style=\"padding-top: 5rem;\">


    <!-- Custom page title-->
    <div class=\"page-title-overlap pt-4 pe-lg-3\">
        <div class=\"container-fluid py-2\">

            <div class=\"pe-lg-4 text-center text-lg-start\">
                <h1 class=\"category-page-title\" id=\"page-title\">";
        // line 10
        echo ($context["heading_title"] ?? null);
        echo "</h1>
            </div>
            <div class=\" mb-3 mb-lg-0\">
                <nav aria-label=\"breadcrumb\">
                    <ol class=\"breadcrumb flex-lg-nowrap justify-content-center justify-content-lg-start\">
                        ";
        // line 15
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["breadcrumbs"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["breadcrumb"]) {
            // line 16
            echo "                            <li class=\"breadcrumb-item\"><a class=\"text-nowrap\" href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "href", [], "any", false, false, false, 16);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "text", [], "any", false, false, false, 16);
            echo "</a></li>
                        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['breadcrumb'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 18
        echo "                    </ol>
                </nav>
            </div>
        </div>
    </div>



    <div id=\"content\" class=\"container-fluid pb-5 mb-2 mb-md-4\">

        <!-- Toolbar-->

            <!-- Products grid-->
        <div id=\"product-view\">

            <div class=\"product-filter rounded-3 p-4 mt-n5 mb-4\">
                <div class=\"d-flex justify-content-between align-items-center\">
                    <div class=\"dropdown me-2\"><a class=\"btn btn-outline-secondary dropdown-toggle\" href=\"#shop-filters\" data-bs-toggle=\"collapse\"><i class=\"fas fa-filter me-2\"></i> Filter</a></div>
                    <div class=\"d-flex\">
                        ";
        // line 37
        echo ($context["pagination"] ?? null);
        echo "
                    </div>
                    <div class=\"d-none d-sm-flex\">
                        <label class=\"text-light fs-sm opacity-75 text-nowrap me-2 d-none d-sm-block\" for=\"sorting\">";
        // line 40
        echo ($context["text_sort"] ?? null);
        echo "</label>
                        <select id=\"input-sort\" class=\"form-select \" onchange=\"location = this.value;\">
                            ";
        // line 42
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($context["sorts"]);
        foreach ($context['_seq'] as $context["_key"] => $context["sorts"]) {
            // line 43
            echo "                                ";
            if ((twig_get_attribute($this->env, $this->source, $context["sorts"], "value", [], "any", false, false, false, 43) == sprintf("%s-%s", ($context["sort"] ?? null), ($context["order"] ?? null)))) {
                // line 44
                echo "                                    <option value=\"";
                echo twig_get_attribute($this->env, $this->source, $context["sorts"], "href", [], "any", false, false, false, 44);
                echo "\" selected=\"selected\"> ";
                echo twig_get_attribute($this->env, $this->source, $context["sorts"], "text", [], "any", false, false, false, 44);
                echo "</option>
                                ";
            } else {
                // line 46
                echo "                                    <option value=\"";
                echo twig_get_attribute($this->env, $this->source, $context["sorts"], "href", [], "any", false, false, false, 46);
                echo "\" >";
                echo twig_get_attribute($this->env, $this->source, $context["sorts"], "text", [], "any", false, false, false, 46);
                echo "</option>
                                ";
            }
            // line 48
            echo "                            ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['sorts'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 49
        echo "                        </select>
                    </div>
                </div>
                <!-- Toolbar with expandable filters-->
                <div class=\"collapse\" id=\"shop-filters\">

                    ";
        // line 55
        echo ($context["content_top"] ?? null);
        echo "

                </div>
            </div>
            <div class=\"row pt-3 mx-n2 product-holder \">
                ";
        // line 60
        if (($context["products"] ?? null)) {
            // line 61
            echo "                <!-- Product-->
                    ";
            // line 62
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["products"] ?? null));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["product"]) {
                // line 63
                echo "                        ";
                $this->loadTemplate("basel/template/product/single_product.twig", "basel/template/product/category.twig", 63)->display($context);
                // line 64
                echo "                    ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['length'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['product'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 65
            echo "                ";
        }
        // line 66
        echo "                ";
        if (( !($context["categories"] ?? null) &&  !($context["products"] ?? null))) {
            // line 67
            echo "                    <p>";
            echo ($context["text_empty"] ?? null);
            echo "</p>
                ";
        }
        // line 69
        echo "            </div>
            <hr class=\"my-3\">

            <nav class=\"d-flex justify-content-between pt-2 pagination-navigation\" aria-label=\"Page navigation\">
                ";
        // line 73
        echo ($context["pagination"] ?? null);
        echo "
            </nav>
        </div>
    </div>
    </div>
";
        // line 78
        echo ($context["footer"] ?? null);
        echo "
";
    }

    public function getTemplateName()
    {
        return "basel/template/product/category.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  214 => 78,  206 => 73,  200 => 69,  194 => 67,  191 => 66,  188 => 65,  174 => 64,  171 => 63,  154 => 62,  151 => 61,  149 => 60,  141 => 55,  133 => 49,  127 => 48,  119 => 46,  111 => 44,  108 => 43,  104 => 42,  99 => 40,  93 => 37,  72 => 18,  61 => 16,  57 => 15,  49 => 10,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "basel/template/product/category.twig", "");
    }
}
