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

/* basel/template/product/product.twig */
class __TwigTemplate_a6f875ffcf13258e94339ba0a2c48bae28ad36271a99907a1a6d87d30aa4bcfb extends \Twig\Template
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

\t\t\t";
        // line 3
        if (($context["product_disabled"] ?? null)) {
            echo "<div class=\"container\"><div class=\"alert alert-warning\" role=\"alert\">";
            echo ($context["product_disabled"] ?? null);
            echo "</div></div>";
        }
        // line 4
        echo "\t\t\t
<main class=\"offcanvas-enabled\" style=\"padding-top: 5rem;\">
    <!-- Custom page title-->
    <div class=\"page-title-overlap bg-dark pt-4 ps-lg-4 pe-lg-3\">
        <div class=\"container  py-2 py-lg-3\">

            <div class=\"pe-lg-4 text-center text-lg-start\">
                <h1 class=\"h3 text-light mb-2\" id=\"page-title\">";
        // line 11
        echo ($context["heading_title"] ?? null);
        echo "</h1>
            </div>
            <div class=\" mb-3 mb-lg-0 pt-lg-2\">
                <nav aria-label=\"breadcrumb\">
                    <ol class=\"breadcrumb breadcrumb-light flex-lg-nowrap justify-content-center justify-content-lg-start\">
                        ";
        // line 16
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["breadcrumbs"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["breadcrumb"]) {
            // line 17
            echo "                            <li class=\"breadcrumb-item\"><a class=\"text-nowrap\" href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "href", [], "any", false, false, false, 17);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "text", [], "any", false, false, false, 17);
            echo "</a></li>
                        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['breadcrumb'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 19
        echo "                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class=\"container\">
        <div class=\"bg-light shadow-lg rounded-3\">
            <!-- Tabs-->
            <ul class=\"nav nav-tabs\" role=\"tablist\">
                <li class=\"nav-item\"><a class=\"nav-link py-4 px-sm-4 active\" href=\"#general\" data-bs-toggle=\"tab\" role=\"tab\">General <span class='d-none d-sm-inline'>Info</span></a></li>
                <li class=\"nav-item\"><a class=\"nav-link py-4 px-sm-4\" href=\"#specs\" data-bs-toggle=\"tab\" role=\"tab\"><span class='d-none d-sm-inline'>Tech</span> Specs</a></li>
                <li class=\"nav-item\"><a class=\"nav-link py-4 px-sm-4\" href=\"#reviews\" data-bs-toggle=\"tab\" role=\"tab\">Reviews <span class=\"fs-sm opacity-60\">(74)</span></a></li>
            </ul>
            <div class=\"px-4 pt-lg-3 pb-3 mb-5\">
                <div class=\"tab-content px-lg-3\">
                    <!-- General info tab-->
                    <div class=\"tab-pane fade show active\" id=\"general\" role=\"tabpanel\">
                        <div class=\"row\">
                            <!-- Product gallery-->
                            <div class=\"col-lg-7 pe-lg-0\">
                                <div class=\"product-gallery\">
                                    <div class=\"product-gallery-preview order-sm-2\">
                                        ";
        // line 41
        if ((($context["thumb"] ?? null) || ($context["images"] ?? null))) {
            // line 42
            echo "                                            ";
            if (($context["thumb"] ?? null)) {
                // line 43
                echo "                                                <div class=\"product-gallery-preview-item active\" id=\"first\"><img class=\"image-zoom\" src=\"";
                echo ($context["thumb"] ?? null);
                echo "\" data-zoom=\"";
                echo ($context["popup"] ?? null);
                echo "\" alt=\"";
                echo ($context["heading_title"] ?? null);
                echo "\">
                                                    <div class=\"image-zoom-pane\"></div>
                                                </div>
                                            ";
            }
            // line 47
            echo "                                            ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["images"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["image"]) {
                // line 48
                echo "                                                <div class=\"product-gallery-preview-item\" id=\"image";
                echo twig_get_attribute($this->env, $this->source, $context["image"], "imageid", [], "any", false, false, false, 48);
                echo "\"><img class=\"image-zoom\" src=\"";
                echo twig_get_attribute($this->env, $this->source, $context["image"], "popup", [], "any", false, false, false, 48);
                echo "\"  alt=\"Product image\"></div>
                                            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['image'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 50
            echo "                                        ";
        }
        // line 51
        echo "                                    </div>
                                    ";
        // line 52
        if (($context["images"] ?? null)) {
            // line 53
            echo "                                        <div class=\"product-gallery-thumblist order-sm-1\">
                                            ";
            // line 54
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["images"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["image"]) {
                // line 55
                echo "                                                ";
                if (($context["thumb"] ?? null)) {
                    // line 56
                    echo "                                                    <a class=\"product-gallery-thumblist-item active\" href=\"#first\"><img src=\"";
                    echo ($context["thumb_sm"] ?? null);
                    echo "\" alt=\"";
                    echo ($context["heading_title"] ?? null);
                    echo "\"></a>
                                                ";
                }
                // line 58
                echo "                                                <a class=\"product-gallery-thumblist-item\" href=\"#image";
                echo twig_get_attribute($this->env, $this->source, $context["image"], "imageid", [], "any", false, false, false, 58);
                echo "\"><img src=\"";
                echo twig_get_attribute($this->env, $this->source, $context["image"], "thumb", [], "any", false, false, false, 58);
                echo "\" alt=\"";
                echo ($context["heading_title"] ?? null);
                echo "\"></a>
                                            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['image'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 60
            echo "                                        </div>
                                    ";
        }
        // line 62
        echo "                                </div>
                            </div>
                            <!-- Product details-->
                            <div class=\"col-lg-5 pt-4 pt-lg-0\">
                                <div class=\"product-details ms-auto pb-3\">
                                    <div class=\"mb-3\">
                                        ";
        // line 68
        if ( !($context["special"] ?? null)) {
            // line 69
            echo "                                            <span class=\"h3 fw-normal text-accent me-1\">";
            echo ($context["price"] ?? null);
            echo "</span>
                                        ";
        } else {
            // line 71
            echo "                                            <span class=\"h3 fw-normal text-accent me-1\">";
            echo ($context["price"] ?? null);
            echo "</span>
                                            <del class=\"text-muted fs-lg me-3\">";
            // line 72
            echo ($context["special"] ?? null);
            echo "</del>
                                            ";
            // line 73
            if (((($context["price"] ?? null) && ($context["special"] ?? null)) && ($context["sale_badge"] ?? null))) {
                // line 74
                echo "                                                <span class=\"badge bg-danger badge-shadow align-middle mt-n2\">";
                echo ($context["sale_badge"] ?? null);
                echo "</span>
                                            ";
            }
            // line 76
            echo "                                            <span id=\"special_countdown\"></span>
                                        ";
        }
        // line 78
        echo "                                    </div>

                                    <form class=\"mb-grid-gutter\" method=\"post\">
                                        <div class=\"mb-3 d-flex align-items-center\">
                                            <select class=\"form-select me-3\" style=\"width: 5rem;\">
                                                <option value=\"1\">1</option>
                                                <option value=\"2\">2</option>
                                                <option value=\"3\">3</option>
                                                <option value=\"4\">4</option>
                                                <option value=\"5\">5</option>
                                            </select>
                                            <button class=\"btn btn-primary btn-shadow d-block w-100\" type=\"submit\"><i class=\"ci-cart fs-lg me-2\"></i>Add to Cart</button>
                                        </div>
                                    </form>

                                    <div class=\"d-flex mb-4\">
                                        <div class=\"w-100 me-3\">
                                            <button class=\"btn btn-secondary d-block w-100\" type=\"button\"><i class=\"ci-heart fs-lg me-2\"></i><span class='d-none d-sm-inline'>Add to </span>Wishlist</button>
                                        </div>
                                        <div class=\"w-100\">
                                            <button class=\"btn btn-secondary d-block w-100\" type=\"button\"><i class=\"ci-compare fs-lg me-2\"></i>Compare</button>
                                        </div>
                                    </div>
                                    <!-- Product panels-->
                                    <div class=\"accordion mb-4\" id=\"productPanels\">
                                        <div class=\"accordion-item\">
                                            <h3 class=\"accordion-header\"><a class=\"accordion-button\" href=\"#productInfo\" role=\"button\" data-bs-toggle=\"collapse\" aria-expanded=\"true\" aria-controls=\"productInfo\"><i class=\"ci-announcement text-muted fs-lg align-middle mt-n1 me-2\"></i>Osnovne značajke</a></h3>
                                            <div class=\"accordion-collapse collapse show\" id=\"productInfo\" data-bs-parent=\"#productPanels\">
                                                <div class=\"accordion-body\">
                                                    ";
        // line 107
        if ((($context["meta_description_status"] ?? null) && ($context["meta_description"] ?? null))) {
            // line 108
            echo "                                                        <p class=\"fs-md\">";
            echo ($context["meta_description"] ?? null);
            echo "</p>
                                                    ";
        }
        // line 110
        echo "                                                    <h6 class=\"fs-sm mb-2\">";
        echo ($context["text_model"] ?? null);
        echo "</h6>
                                                    <ul class=\"fs-sm ps-4 mb-0\">
                                                        <li>";
        // line 112
        echo ($context["model"] ?? null);
        echo "</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class=\"accordion-item\">
                                            <h3 class=\"accordion-header\"><a class=\"accordion-button collapsed\" href=\"#shippingOptions\" role=\"button\" data-bs-toggle=\"collapse\" aria-expanded=\"true\" aria-controls=\"shippingOptions\"><i class=\"ci-delivery text-muted lead align-middle mt-n1 me-2\"></i>Shipping options</a></h3>
                                            <div class=\"accordion-collapse collapse\" id=\"shippingOptions\" data-bs-parent=\"#productPanels\">
                                                <div class=\"accordion-body fs-sm\">
                                                    <div class=\"d-flex justify-content-between border-bottom pb-2\">
                                                        <div>
                                                            <div class=\"fw-semibold text-dark\">Courier</div>
                                                            <div class=\"fs-sm text-muted\">2 - 4 days</div>
                                                        </div>
                                                        <div>\$26.50</div>
                                                    </div>
                                                    <div class=\"d-flex justify-content-between border-bottom py-2\">
                                                        <div>
                                                            <div class=\"fw-semibold text-dark\">Local shipping</div>
                                                            <div class=\"fs-sm text-muted\">up to one week</div>
                                                        </div>
                                                        <div>\$10.00</div>
                                                    </div>
                                                    <div class=\"d-flex justify-content-between border-bottom py-2\">
                                                        <div>
                                                            <div class=\"fw-semibold text-dark\">Flat rate</div>
                                                            <div class=\"fs-sm text-muted\">5 - 7 days</div>
                                                        </div>
                                                        <div>\$33.85</div>
                                                    </div>
                                                    <div class=\"d-flex justify-content-between border-bottom py-2\">
                                                        <div>
                                                            <div class=\"fw-semibold text-dark\">UPS ground shipping</div>
                                                            <div class=\"fs-sm text-muted\">4 - 6 days</div>
                                                        </div>
                                                        <div>\$18.00</div>
                                                    </div>
                                                    <div class=\"d-flex justify-content-between pt-2\">
                                                        <div>
                                                            <div class=\"fw-semibold text-dark\">Local pickup from store</div>
                                                            <div class=\"fs-sm text-muted\">&mdash;</div>
                                                        </div>
                                                        <div>\$0.00</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class=\"accordion-item\">
                                            <h3 class=\"accordion-header\"><a class=\"accordion-button collapsed\" href=\"#localStore\" role=\"button\" data-bs-toggle=\"collapse\" aria-expanded=\"true\" aria-controls=\"localStore\"><i class=\"ci-location text-muted fs-lg align-middle mt-n1 me-2\"></i>Find in local store</a></h3>
                                            <div class=\"accordion-collapse collapse\" id=\"localStore\" data-bs-parent=\"#productPanels\">
                                                <div class=\"accordion-body\">
                                                    <select class=\"form-select\">
                                                        <option value>Select your country</option>
                                                        <option value=\"Argentina\">Argentina</option>
                                                        <option value=\"Belgium\">Belgium</option>
                                                        <option value=\"France\">France</option>
                                                        <option value=\"Germany\">Germany</option>
                                                        <option value=\"Spain\">Spain</option>
                                                        <option value=\"UK\">United Kingdom</option>
                                                        <option value=\"USA\">USA</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Sharing-->
                                    <label class=\"form-label d-inline-block align-middle my-2 me-3\">Share:</label><a class=\"btn-share btn-twitter me-2 my-2\" href=\"#\"><i class=\"ci-twitter\"></i>Twitter</a><a class=\"btn-share btn-instagram me-2 my-2\" href=\"#\"><i class=\"ci-instagram\"></i>Instagram</a><a class=\"btn-share btn-facebook my-2\" href=\"#\"><i class=\"ci-facebook\"></i>Facebook</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tech specs tab-->
                    <div class=\"tab-pane fade\" id=\"specs\" role=\"tabpanel\">
                        <div class=\"d-md-flex justify-content-between align-items-start pb-4 mb-4 border-bottom\">
                            <div class=\"d-flex align-items-center me-md-3\"><img src=\"img/shop/single/gallery/th05.jpg\" width=\"90\" alt=\"Product thumb\">
                                <div class=\"ps-3\">
                                    <h6 class=\"fs-base mb-2\">Smartwatch Youth Edition</h6>
                                    <div class=\"h4 fw-normal text-accent\">\$124.<small>99</small></div>
                                </div>
                            </div>
                            <div class=\"d-flex align-items-center pt-3\">
                                <select class=\"form-select me-2\" style=\"width: 5rem;\">
                                    <option value=\"1\">1</option>
                                    <option value=\"2\">2</option>
                                    <option value=\"3\">3</option>
                                    <option value=\"4\">4</option>
                                    <option value=\"5\">5</option>
                                </select>
                                <button class=\"btn btn-primary btn-shadow me-2\" type=\"button\"><i class=\"ci-cart fs-lg me-sm-2\"></i><span class=\"d-none d-sm-inline\">Add to Cart</span></button>
                                <div class=\"me-2\">
                                    <button class=\"btn btn-secondary btn-icon\" type=\"button\" data-bs-toggle=\"tooltip\" title=\"Add to Wishlist\"><i class=\"ci-heart fs-lg\"></i></button>
                                </div>
                                <div>
                                    <button class=\"btn btn-secondary btn-icon\" type=\"button\" data-bs-toggle=\"tooltip\" title=\"Compare\"><i class=\"ci-compare fs-lg\"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Specs table-->
                        <div class=\"row pt-2\">
                            <div class=\"col-lg-5 col-sm-6\">
                                <h3 class=\"h6\">General specs</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Model:</span><span>Amazfit Smartwatch</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Gender:</span><span>Unisex</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Smartphone app:</span><span>Amazfit Watch</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">OS campitibility:</span><span>Android / iOS</span></li>
                                </ul>
                                <h3 class=\"h6\">Physical specs</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Shape:</span><span>Rectangular</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Body material:</span><span>Plastics / Ceramics</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Band material:</span><span>Silicone</span></li>
                                </ul>
                                <h3 class=\"h6\">Display</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Display type:</span><span>Color</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Display size:</span><span>1.28\"</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Screen resolution:</span><span>176 x 176</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Touch screen:</span><span>No</span></li>
                                </ul>
                            </div>
                            <div class=\"col-lg-5 col-sm-6 offset-lg-1\">
                                <h3 class=\"h6\">Functions</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Phone calls:</span><span>Incoming call notification</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Monitoring:</span><span>Heart rate / Physical activity</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">GPS support:</span><span>Yes</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Sensors:</span><span>Heart rate, Gyroscope, Geomagnetic, Light sensor</span></li>
                                </ul>
                                <h3 class=\"h6\">Battery</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Battery:</span><span>Li-Pol</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Battery capacity:</span><span>190 mAh</span></li>
                                </ul>
                                <h3 class=\"h6\">Dimensions</h3>
                                <ul class=\"list-unstyled fs-sm pb-2\">
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Dimensions:</span><span>195 x 20 mm</span></li>
                                    <li class=\"d-flex justify-content-between pb-2 border-bottom\"><span class=\"text-muted\">Weight:</span><span>32 g</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Reviews tab-->
                    <div class=\"tab-pane fade\" id=\"reviews\" role=\"tabpanel\">
                        <div class=\"d-md-flex justify-content-between align-items-start pb-4 mb-4 border-bottom\">
                            <div class=\"d-flex align-items-center me-md-3\"><img src=\"img/shop/single/gallery/th05.jpg\" width=\"90\" alt=\"Product thumb\">
                                <div class=\"ps-3\">
                                    <h6 class=\"fs-base mb-2\">Smartwatch Youth Edition</h6>
                                    <div class=\"h4 fw-normal text-accent\">\$124.<small>99</small></div>
                                </div>
                            </div>
                            <div class=\"d-flex align-items-center pt-3\">
                                <select class=\"form-select me-2\" style=\"width: 5rem;\">
                                    <option value=\"1\">1</option>
                                    <option value=\"2\">2</option>
                                    <option value=\"3\">3</option>
                                    <option value=\"4\">4</option>
                                    <option value=\"5\">5</option>
                                </select>
                                <button class=\"btn btn-primary btn-shadow me-2\" type=\"button\"><i class=\"ci-cart fs-lg me-sm-2\"></i><span class=\"d-none d-sm-inline\">Add to Cart</span></button>
                                <div class=\"me-2\">
                                    <button class=\"btn btn-secondary btn-icon\" type=\"button\" data-bs-toggle=\"tooltip\" title=\"Add to Wishlist\"><i class=\"ci-heart fs-lg\"></i></button>
                                </div>
                                <div>
                                    <button class=\"btn btn-secondary btn-icon\" type=\"button\" data-bs-toggle=\"tooltip\" title=\"Compare\"><i class=\"ci-compare fs-lg\"></i></button>
                                </div>
                            </div>
                        </div>
                        <!-- Reviews-->
                        <div class=\"row pt-2 pb-3\">
                            <div class=\"col-lg-4 col-md-5\">
                                <h2 class=\"h3 mb-4\">74 Reviews</h2>
                                <div class=\"star-rating me-2\"><i class=\"ci-star-filled fs-sm text-accent me-1\"></i><i class=\"ci-star-filled fs-sm text-accent me-1\"></i><i class=\"ci-star-filled fs-sm text-accent me-1\"></i><i class=\"ci-star-filled fs-sm text-accent me-1\"></i><i class=\"ci-star fs-sm text-muted me-1\"></i></div><span class=\"d-inline-block align-middle\">4.1 Overall rating</span>
                                <p class=\"pt-3 fs-sm text-muted\">58 out of 74 (77%)<br>Customers recommended this product</p>
                            </div>
                            <div class=\"col-lg-8 col-md-7\">
                                <div class=\"d-flex align-items-center mb-2\">
                                    <div class=\"text-nowrap me-3\"><span class=\"d-inline-block align-middle text-muted\">5</span><i class=\"ci-star-filled fs-xs ms-1\"></i></div>
                                    <div class=\"w-100\">
                                        <div class=\"progress\" style=\"height: 4px;\">
                                            <div class=\"progress-bar bg-success\" role=\"progressbar\" style=\"width: 60%;\" aria-valuenow=\"60\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div>
                                        </div>
                                    </div><span class=\"text-muted ms-3\">43</span>
                                </div>
                                <div class=\"d-flex align-items-center mb-2\">
                                    <div class=\"text-nowrap me-3\"><span class=\"d-inline-block align-middle text-muted\">4</span><i class=\"ci-star-filled fs-xs ms-1\"></i></div>
                                    <div class=\"w-100\">
                                        <div class=\"progress\" style=\"height: 4px;\">
                                            <div class=\"progress-bar\" role=\"progressbar\" style=\"width: 27%; background-color: #a7e453;\" aria-valuenow=\"27\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div>
                                        </div>
                                    </div><span class=\"text-muted ms-3\">16</span>
                                </div>
                                <div class=\"d-flex align-items-center mb-2\">
                                    <div class=\"text-nowrap me-3\"><span class=\"d-inline-block align-middle text-muted\">3</span><i class=\"ci-star-filled fs-xs ms-1\"></i></div>
                                    <div class=\"w-100\">
                                        <div class=\"progress\" style=\"height: 4px;\">
                                            <div class=\"progress-bar\" role=\"progressbar\" style=\"width: 17%; background-color: #ffda75;\" aria-valuenow=\"17\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div>
                                        </div>
                                    </div><span class=\"text-muted ms-3\">9</span>
                                </div>
                                <div class=\"d-flex align-items-center mb-2\">
                                    <div class=\"text-nowrap me-3\"><span class=\"d-inline-block align-middle text-muted\">2</span><i class=\"ci-star-filled fs-xs ms-1\"></i></div>
                                    <div class=\"w-100\">
                                        <div class=\"progress\" style=\"height: 4px;\">
                                            <div class=\"progress-bar\" role=\"progressbar\" style=\"width: 9%; background-color: #fea569;\" aria-valuenow=\"9\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div>
                                        </div>
                                    </div><span class=\"text-muted ms-3\">4</span>
                                </div>
                                <div class=\"d-flex align-items-center\">
                                    <div class=\"text-nowrap me-3\"><span class=\"d-inline-block align-middle text-muted\">1</span><i class=\"ci-star-filled fs-xs ms-1\"></i></div>
                                    <div class=\"w-100\">
                                        <div class=\"progress\" style=\"height: 4px;\">
                                            <div class=\"progress-bar bg-danger\" role=\"progressbar\" style=\"width: 4%;\" aria-valuenow=\"4\" aria-valuemin=\"0\" aria-valuemax=\"100\"></div>
                                        </div>
                                    </div><span class=\"text-muted ms-3\">2</span>
                                </div>
                            </div>
                        </div>
                        <hr class=\"mt-4 mb-3\">
                        <div class=\"row py-4\">
                            <!-- Reviews list-->
                            <div class=\"col-md-7\">
                                <div class=\"d-flex justify-content-end pb-4\">
                                    <div class=\"d-flex flex-nowrap align-items-center\">
                                        <label class=\"fs-sm text-muted text-nowrap me-2 d-none d-sm-block\" for=\"sort-reviews\">Sort by:</label>
                                        <select class=\"form-select form-select-sm\" id=\"sort-reviews\">
                                            <option>Newest</option>
                                            <option>Oldest</option>
                                            <option>Popular</option>
                                            <option>High rating</option>
                                            <option>Low rating</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Review-->
                                <div class=\"product-review pb-4 mb-4 border-bottom\">
                                    <div class=\"d-flex mb-3\">
                                        <div class=\"d-flex align-items-center me-4 pe-2\"><img class=\"rounded-circle\" src=\"img/shop/reviews/01.jpg\" width=\"50\" alt=\"Rafael Marquez\">
                                            <div class=\"ps-3\">
                                                <h6 class=\"fs-sm mb-0\">Rafael Marquez</h6><span class=\"fs-ms text-muted\">June 28, 2019</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star\"></i>
                                            </div>
                                            <div class=\"fs-ms text-muted\">83% of users found this review helpful</div>
                                        </div>
                                    </div>
                                    <p class=\"fs-md mb-2\">Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est...</p>
                                    <ul class=\"list-unstyled fs-ms pt-1\">
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Pros:&nbsp;</span>Consequuntur magni, voluptatem sequi, tempora</li>
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Cons:&nbsp;</span>Architecto beatae, quis autem</li>
                                    </ul>
                                    <div class=\"text-nowrap\">
                                        <button class=\"btn-like\" type=\"button\">15</button>
                                        <button class=\"btn-dislike\" type=\"button\">3</button>
                                    </div>
                                </div>
                                <!-- Review-->
                                <div class=\"product-review pb-4 mb-4 border-bottom\">
                                    <div class=\"d-flex mb-3\">
                                        <div class=\"d-flex align-items-center me-4 pe-2\"><img class=\"rounded-circle\" src=\"img/shop/reviews/02.jpg\" width=\"50\" alt=\"Barbara Palson\">
                                            <div class=\"ps-3\">
                                                <h6 class=\"fs-sm mb-0\">Barbara Palson</h6><span class=\"fs-ms text-muted\">May 17, 2019</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i>
                                            </div>
                                            <div class=\"fs-ms text-muted\">99% of users found this review helpful</div>
                                        </div>
                                    </div>
                                    <p class=\"fs-md mb-2\">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                                    <ul class=\"list-unstyled fs-ms pt-1\">
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Pros:&nbsp;</span>Consequuntur magni, voluptatem sequi, tempora</li>
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Cons:&nbsp;</span>Architecto beatae, quis autem</li>
                                    </ul>
                                    <div class=\"text-nowrap\">
                                        <button class=\"btn-like\" type=\"button\">34</button>
                                        <button class=\"btn-dislike\" type=\"button\">1</button>
                                    </div>
                                </div>
                                <!-- Review-->
                                <div class=\"product-review pb-4 mb-4 border-bottom\">
                                    <div class=\"d-flex mb-3\">
                                        <div class=\"d-flex align-items-center me-4 pe-2\"><img class=\"rounded-circle\" src=\"img/shop/reviews/03.jpg\" width=\"50\" alt=\"Daniel Adams\">
                                            <div class=\"ps-3\">
                                                <h6 class=\"fs-sm mb-0\">Daniel Adams</h6><span class=\"fs-ms text-muted\">May 8, 2019</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star\"></i><i class=\"star-rating-icon ci-star\"></i>
                                            </div>
                                            <div class=\"fs-ms text-muted\">75% of users found this review helpful</div>
                                        </div>
                                    </div>
                                    <p class=\"fs-md mb-2\">Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem.</p>
                                    <ul class=\"list-unstyled fs-ms pt-1\">
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Pros:&nbsp;</span>Consequuntur magni, voluptatem sequi</li>
                                        <li class=\"mb-1\"><span class=\"fw-medium\">Cons:&nbsp;</span>Architecto beatae,  quis autem, voluptatem sequ</li>
                                    </ul>
                                    <div class=\"text-nowrap\">
                                        <button class=\"btn-like\" type=\"button\">26</button>
                                        <button class=\"btn-dislike\" type=\"button\">9</button>
                                    </div>
                                </div>
                                <div class=\"text-center\">
                                    <button class=\"btn btn-outline-accent\" type=\"button\"><i class=\"ci-reload me-2\"></i>Load more reviews</button>
                                </div>
                            </div>
                            <!-- Leave review form-->
                            <div class=\"col-md-5 mt-2 pt-4 mt-md-0 pt-md-0\">
                                <div class=\"bg-secondary py-grid-gutter px-grid-gutter rounded-3\">
                                    <h3 class=\"h4 pb-2\">Write a review</h3>
                                    <form class=\"needs-validation\" method=\"post\" novalidate>
                                        <div class=\"mb-3\">
                                            <label class=\"form-label\" for=\"review-name\">Your name<span class=\"text-danger\">*</span></label>
                                            <input class=\"form-control\" type=\"text\" required id=\"review-name\">
                                            <div class=\"invalid-feedback\">Please enter your name!</div><small class=\"form-text text-muted\">Will be displayed on the comment.</small>
                                        </div>
                                        <div class=\"mb-3\">
                                            <label class=\"form-label\" for=\"review-email\">Your email<span class=\"text-danger\">*</span></label>
                                            <input class=\"form-control\" type=\"email\" required id=\"review-email\">
                                            <div class=\"invalid-feedback\">Please provide valid email address!</div><small class=\"form-text text-muted\">Authentication only - we won't spam you.</small>
                                        </div>
                                        <div class=\"mb-3\">
                                            <label class=\"form-label\" for=\"review-rating\">Rating<span class=\"text-danger\">*</span></label>
                                            <select class=\"form-select\" required id=\"review-rating\">
                                                <option value=\"\">Choose rating</option>
                                                <option value=\"5\">5 stars</option>
                                                <option value=\"4\">4 stars</option>
                                                <option value=\"3\">3 stars</option>
                                                <option value=\"2\">2 stars</option>
                                                <option value=\"1\">1 star</option>
                                            </select>
                                            <div class=\"invalid-feedback\">Please choose rating!</div>
                                        </div>
                                        <div class=\"mb-3\">
                                            <label class=\"form-label\" for=\"review-text\">Review<span class=\"text-danger\">*</span></label>
                                            <textarea class=\"form-control\" rows=\"6\" required id=\"review-text\"></textarea>
                                            <div class=\"invalid-feedback\">Please write a review!</div><small class=\"form-text text-muted\">Your review must be at least 50 characters.</small>
                                        </div>
                                        <div class=\"mb-3\">
                                            <label class=\"form-label\" for=\"review-pros\">Pros</label>
                                            <textarea class=\"form-control\" rows=\"2\" placeholder=\"Separated by commas\" id=\"review-pros\"></textarea>
                                        </div>
                                        <div class=\"mb-4\">
                                            <label class=\"form-label\" for=\"review-cons\">Cons</label>
                                            <textarea class=\"form-control\" rows=\"2\" placeholder=\"Separated by commas\" id=\"review-cons\"></textarea>
                                        </div>
                                        <button class=\"btn btn-primary btn-shadow d-block w-100\" type=\"submit\">Submit a Review</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product carousel (You may also like)-->
    <div class=\"container pt-lg-2 pb-5 mb-md-3 ps-lg-4 pe-lg-3\">
        <h2 class=\"h3 text-center pb-4\">You may also like</h2>
        <div class=\"tns-carousel tns-controls-static tns-controls-outside\">
            <div class=\"tns-carousel-inner\" data-carousel-options=\"{&quot;items&quot;: 2, &quot;controls&quot;: true, &quot;nav&quot;: false, &quot;responsive&quot;: {&quot;0&quot;:{&quot;items&quot;:1},&quot;500&quot;:{&quot;items&quot;:2, &quot;gutter&quot;: 18},&quot;768&quot;:{&quot;items&quot;:3, &quot;gutter&quot;: 20}, &quot;1100&quot;:{&quot;items&quot;:4, &quot;gutter&quot;: 30}}}\">
                <!-- Product-->
                <div>
                    <div class=\"card product-card card-static\">
                        <button class=\"btn-wishlist btn-sm\" type=\"button\" data-bs-toggle=\"tooltip\" data-bs-placement=\"left\" title=\"Add to wishlist\"><i class=\"ci-heart\"></i></button><a class=\"card-img-top d-block overflow-hidden\" href=\"#\"><img src=\"img/shop/catalog/66.jpg\" alt=\"Product\"></a>
                        <div class=\"card-body py-2\"><a class=\"product-meta d-block fs-xs pb-1\" href=\"#\">Smartwatches</a>
                            <h3 class=\"product-title fs-sm\"><a href=\"#\">Health &amp; Fitness Smartwatch</a></h3>
                            <div class=\"d-flex justify-content-between\">
                                <div class=\"product-price\"><span class=\"text-accent\">\$250.<small>00</small></span></div>
                                <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product-->
                <div>
                    <div class=\"card product-card card-static\">
                        <button class=\"btn-wishlist btn-sm\" type=\"button\" data-bs-toggle=\"tooltip\" data-bs-placement=\"left\" title=\"Add to wishlist\"><i class=\"ci-heart\"></i></button><a class=\"card-img-top d-block overflow-hidden\" href=\"#\"><img src=\"img/shop/catalog/67.jpg\" alt=\"Product\"></a>
                        <div class=\"card-body py-2\"><a class=\"product-meta d-block fs-xs pb-1\" href=\"#\">Smartwatches</a>
                            <h3 class=\"product-title fs-sm\"><a href=\"#\">Heart Rate &amp; Activity Tracker</a></h3>
                            <div class=\"d-flex justify-content-between\">
                                <div class=\"product-price text-accent\">\$26.<small>99</small></div>
                                <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-half active\"></i><i class=\"star-rating-icon ci-star\"></i><i class=\"star-rating-icon ci-star\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product-->
                <div>
                    <div class=\"card product-card card-static\">
                        <button class=\"btn-wishlist btn-sm\" type=\"button\" data-bs-toggle=\"tooltip\" data-bs-placement=\"left\" title=\"Add to wishlist\"><i class=\"ci-heart\"></i></button><a class=\"card-img-top d-block overflow-hidden\" href=\"#\"><img src=\"img/shop/catalog/64.jpg\" alt=\"Product\"></a>
                        <div class=\"card-body py-2\"><a class=\"product-meta d-block fs-xs pb-1\" href=\"#\">Smartwatches</a>
                            <h3 class=\"product-title fs-sm\"><a href=\"#\">Smart Watch Series 5, Aluminium</a></h3>
                            <div class=\"d-flex justify-content-between\">
                                <div class=\"product-price text-accent\">\$349.<small>99</small></div>
                                <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product-->
                <div>
                    <div class=\"card product-card card-static\">
                        <button class=\"btn-wishlist btn-sm\" type=\"button\" data-bs-toggle=\"tooltip\" data-bs-placement=\"left\" title=\"Add to wishlist\"><i class=\"ci-heart\"></i></button><a class=\"card-img-top d-block overflow-hidden\" href=\"#\"><img src=\"img/shop/catalog/68.jpg\" alt=\"Product\"></a>
                        <div class=\"card-body py-2\"><a class=\"product-meta d-block fs-xs pb-1\" href=\"#\">Smartwatches</a>
                            <h3 class=\"product-title fs-sm\"><a href=\"#\">Health &amp; Fitness Smartwatch</a></h3>
                            <div class=\"d-flex justify-content-between\">
                                <div class=\"product-price text-accent\">\$118.<small>00</small></div>
                                <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product-->
                <div>
                    <div class=\"card product-card card-static\">
                        <button class=\"btn-wishlist btn-sm\" type=\"button\" data-bs-toggle=\"tooltip\" data-bs-placement=\"left\" title=\"Add to wishlist\"><i class=\"ci-heart\"></i></button><a class=\"card-img-top d-block overflow-hidden\" href=\"#\"><img src=\"img/shop/catalog/69.jpg\" alt=\"Product\"></a>
                        <div class=\"card-body py-2\"><a class=\"product-meta d-block fs-xs pb-1\" href=\"#\">Smartwatches</a>
                            <h3 class=\"product-title fs-sm\"><a href=\"#\">Heart Rate &amp; Activity Tracker</a></h3>
                            <div class=\"d-flex justify-content-between\">
                                <div class=\"product-price text-accent\">\$25.<small>00</small></div>
                                <div class=\"star-rating\"><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-filled active\"></i><i class=\"star-rating-icon ci-star-half active\"></i><i class=\"star-rating-icon ci-star\"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
";
        // line 551
        echo ($context["footer"] ?? null);
    }

    public function getTemplateName()
    {
        return "basel/template/product/product.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  704 => 551,  262 => 112,  256 => 110,  250 => 108,  248 => 107,  217 => 78,  213 => 76,  207 => 74,  205 => 73,  201 => 72,  196 => 71,  190 => 69,  188 => 68,  180 => 62,  176 => 60,  163 => 58,  155 => 56,  152 => 55,  148 => 54,  145 => 53,  143 => 52,  140 => 51,  137 => 50,  126 => 48,  121 => 47,  109 => 43,  106 => 42,  104 => 41,  80 => 19,  69 => 17,  65 => 16,  57 => 11,  48 => 4,  42 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "basel/template/product/product.twig", "");
    }
}