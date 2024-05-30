<?php
/**
 * Plugin Name:       Buytiti - Product- Slider 
 * Plugin URI:        https://buytiti.com
 * Description:       Plugin para mostrar productos de Buytiti sin API renderizados como slider/carrusel
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.13
 * Author:            Fernando Isaac González Medina
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buytitislidersinapi
 *
 * @package Buytiti
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Función para obtener productos publicados con stock por categoría y cantidad
function buytiti_get_woocommerce_products_bs_in_stock( $category = '', $cantidad = 10 , $bestsellers = 'false') {
    if ( $cantidad == -1 ) {
        $cantidad = -1; // Mostrar todos los productos
    }

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $cantidad,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            )
        ),
    );

    if ( filter_var($bestsellers, FILTER_VALIDATE_BOOLEAN) ) {
        // Obtener la fecha de 15 días atrás
        $date_15_days_ago = date('Y-m-d H:i:s', strtotime('-20 days'));

        // Filtrar por productos vendidos en los últimos 15 días
        $args['date_query'] = array(
            array(
                'column' => 'post_date_gmt',
                'after'  => $date_15_days_ago
            )
        );

        // Añadir la condición de que las ventas totales sean mayores a 10
        $args['meta_query'][] = array(
            'key'     => 'total_sales',
            'value'   => '10',
            'compare' => '>',
            'type'    => 'NUMERIC'
        );

        $args['meta_key'] = 'total_sales';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
    } else {
        $args['orderby'] = 'post_date_gmt';
        $args['order'] = 'DESC';
    }

    if ( ! empty( $category ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $category, // Filtrar por categoría
            ),
        );
    }

    $query = new WP_Query($args);

    return $query->posts;
}

// Encolar Slick Carousel y estilos
function buytiti_enqueue_scripts_to_slider() {
    // Asegurarse de que jQuery está encolado
    wp_enqueue_script('jquery');

    // Encolar Slick Carousel y asegurarse de que jQuery es una dependencia
    wp_enqueue_script('slick-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), '1.8.1', true);

    // Encolar estilos de Slick Carousel
    wp_enqueue_style('slick-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');
    wp_enqueue_style('slick-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css');

    // Encolar estilos de TI WooCommerce Wishlist
    wp_enqueue_style('ti-woocommerce-wishlist', plugins_url('ti-woocommerce-wishlist/assets/css/style.css'));

    // Encolar scripts de TI WooCommerce Wishlist
    wp_enqueue_script('ti-woocommerce-wishlist', plugins_url('ti-woocommerce-wishlist/assets/js/frontend.js'), array('jquery'), '', true);

    // Estilos personalizados
    $custom_css_prod = "
        .slick-hidden {
            display: none;
        }
        
        .slick-track{
           margin-left: .2rem !important;
        }
        
        a{ 
           text-decoration: none !important;
        }

		.slick-next.slick-arrow{
            background-color: coral !important;
			width: 2rem;
            height: 2rem;
            margin-top: .7rem;
		}
		.slick-prev.slick-arrow{
			background-color: coral !important;
			width: 2rem;
            height: 2rem;
            margin-top: .7rem;
		}
        
        .slick-prev {
             left: -5px !important;
             z-index: 10 !important;
         }
         
         .slick-next {
            right: -5px !important;
            z-index: 10 !important;
          }
          
          .product-discount-buytiti {
    position: absolute;
    top: 6.5%;
    right: 0;
    background-color: coral;
    color: #ffffff;
    padding: 3px;
    z-index: 1;
    font-size: .9rem;
    font-weight: 600;
    border-radius: 0px 0px 0px 10px;
    width: 3rem;
}

		.product-sale-price{
			font-size: 1.2rem;
			font-weight: 700;
			color: red;
		}

		.product-image-movil-buytiti{
	 		max-width: 140px !important;
            height: auto;
            margin-top: 2.5rem !important;
            transition: 1s;
            margin-bottom: 3.2rem !important;
            margin: auto;
            transition: 1s;
		}	
        
        .product-image-movil-buytiti:hover{ 
             transform: scale(1.1);
              cursor: pointer;
        }

        .buytiti-product {
			position: relative;
			text-align: center; /* Centrar el contenido */
			border: 1px solid #ddd; /* Bordes para los productos */
			padding: 10px;
			border-radius: 20px;
			height: 30.5rem;
			width: 14.5rem;
			width: 100%; /* Configurar el ancho para que ocupe todo el espacio disponible */
        }

		.buytiti-product-slider .buytiti-product {
			margin-right: 0.5rem; /* Establecer el margen derecho entre los productos */
		}

        .product-info {
            margin-bottom: 10px.
        }

        .product-new-label {
			position: absolute; 
			top: 0; 
			left: 0; 
			z-index: 1;
			background-color: #f2fff6;
			border: 1px solid #058427;
			border-radius: 20px 0px 0px 0px;    
			color: #058427;
			width: 4rem;
			font-size: 1rem;
        }

        .product-sale-label {
            position: absolute;
    top: 0;
    right: 0;
    background-color: #ff0000;
    color: #ffffff;
    padding: 3px;
    z-index: 1;
    font-size: .9rem;
    font-weight: 600;
    border-radius: 0px 20px 0px 0px;
        }

		.product-sale-label.live-offer-label{
			position: absolute;
			top: 0;
			right: 0;
			background-color: #F7CACA;
			color: red;
			padding: 3px;
			z-index: 1;
			font-size: .9rem;
			font-weight: 700;
			border-radius: 0px 20px 0px 10px;
			width: 8.5rem;
		}

        .product-sale-label.live-offer-label::before{
			content: '•'; /* El punto */
			position: absolute;
			left: 0; /* Alineado a la izquierda */
			top: -5%; /* Centrar verticalmente */
			transform: translateY(-50%); /* Ajustar para centrar */
			color: red; /* Color del punto */
			animation: pulse 1s infinite; /* Aplica la animación de pulso */
			font-size: 2rem;
			margin-left: -.2rem;
			padding-left: .2rem;
		}

		@keyframes pulse {
			0% {
				transform: scale(1);
				opacity: 1;
			}
			50% {
				transform: scale(1.2); /* Aumenta el tamaño */
				opacity: 0.7; /* Reduce la opacidad */
			}
			100% {
				transform: scale(1);
				opacity: 1; /* Vuelve al estado original */
			}
		}

		.product-sale-label.clearance-label{
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e4c311; 
            color: #ffffff; 
            padding: 3px;
            z-index: 1;  
            font-size: .9rem;
            font-weight: 700;
            border-radius: 0px 20px 0px 10px;
		}

		.product-stock-buytiti-movil{
			background-color: #fde5cb;
			border: 1px solid #ff7942;
			border-radius: 15px 0 0 15px;
			color: coral;
			font-size: .6rem;
			font-weight: 700;
			right: 0;
			padding: 0 3px;
			width: 5.2rem;
			position: absolute;
			top: 43%;
            display: none; 
		}

		.product-sku-movil{
            color: #00c9b7;
            font-size: .8rem;
            font-weight: 700;
            text-align: center;
            height: 2rem;
            display: block ruby;
		}

		.product-name-movil-buytiti{
			color: #5c5c5c !important;
			font-size: .9rem !important;
            height: 4.2rem;
			overflow: hidden ;
			font-weight: 700 !important; 
		}

		.product-brand-category{
			color: #8b8b8b !important;
			font-size: .7rem;
			font-weight: 600;
			letter-spacing: .3px;
			text-align: center;
			text-transform: uppercase;
            margin-bottom: .5rem;
            height: 2.5rem;
		}

		.add-to-cart-button{
			width: 8rem;
    justify-content: center;
    display: flex;
    margin-left: -2rem;
    align-items: center;
    background-color: #ef7e28 !important;
    height: 2.2rem;
    right: 0;
    position: absolute;
    border-radius: 20px 0px 20px 0px !important;
    bottom: 0;
		}

		.quantity-input{
			position: absolute;
			left: 0;
			bottom: 0;
			border-radius: 0px 10px 0px 20px !important;
			height: 2rem !important;
			max-width: 70px !important;
		}
		.product-regular-price-tachado {
			text-decoration: line-through; /* Asegurarse de que esté tachado */
			color: #999; /* Color para el precio regular cuando está tachado */
		}

		.product-regular-price{
			font-size: 1.3rem;
            font-weight: 700;
            color: #ff7942;
		}
        
        @media (max-width: 980px) {
            .buytiti-product {
                height: 33.5rem !important;
            }
            .product-name-movil-buytiti{
                color: #5c5c5c !important;
                font-size: .8rem !important;
                height: 4rem;
                margin-bottom: 0px !important;
            }
            .product-brand-category {
                margin-bottom: .3rem;
                height: 3rem;
            }
            .product-sku-movil {
                height: 3rem !important;
                display: block !important;
            }
                .wp-block-columns .wp-block-column:not(:last-child) {
                  margin-bottom: 0px !important;
                }
              
        }
        
         @media screen and (min-width: 301px) and (max-width: 400px) {
                  .buytiti-product {
			            height: 33.5rem !important;
                  }
                  
                  .add-to-cart-button {
                      width: 6.8rem;
                      font-size: .8rem;
                  }
                  .product-discount-buytiti {
                         top: 6%;
                   }
                  
        }
        /* Estilos para la etiqueta de Vendidos */
        .product-sold-count {
            position: absolute;
            right: 0;
            background-color:#E17F01;
            color: #ffffff;
            padding: 3px;
            z-index: 1;
            font-size: .9rem;
            font-weight: 700;
            border-radius: 0px 20px 0px 10px;
            top: 0;
        }

        /* Estilos para la imagen de la insignia */
        .product-badge-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 2.5rem;
        }
        /*Estilos para page favoritos */
        .tinv-wishlist .tinvwl_add_to_wishlist_button.tinvwl-icon-custom img, a.wishlist_products_counter.top_wishlist-custom img, span.wishlist_products_counter.top_wishlist-custom img {
            max-height: 3vh !important;
            max-width: 6vw !important;
        }
        .tinv-header{
            display: none !important; 
        }
        .tinv-wraper.tinv-wishlist {
            position: absolute !important;
            right: 5% !important;
            bottom: 59% !important;
        }
        .tinv-wishlist .tinvwl_add_to_wishlist_button.tinvwl-icon-custom.no-txt {
            width: 21px !important;
        }

        .tinv-wishlist .product-action .button {
            background-color: #ef7e28 !important;
        }

        .tinv-wishlist .product-action {
        width: 175px !important;

        }
        .tinv-wishlist tfoot .tinvwl-to-right {
        float: right !important;
        }

        .tinv-wishlist tfoot .tinvwl-to-right>* {
        background-color: #ef7e28 !important;
        }
        .product-name .tinvwl-full{
        font-size: 3vh !important;
        font-weight: bold !important;
        }
        td.product-name a {
            color: #545555 !important;
            font-weight: bold;
        }
        td.product-price span.woocommerce-Price-amount.amount bdi {
            font-size: 3vh;
            color: #ef7e28 !important;
            font-weight: bold;
        }
        th.product-price{
            color: #545555 !important;
            font-weight: bold;
            font-size: 3vh;
        }
        .wishlist_products_counter_number{
            font-size:20px !important;
            color: orange !important;
        }
        #block-33 p {
         margin-bottom: 0em !important;   
        }

        .woocommerce-js form .form-row label {
            font-size: 1.3rem !important;
          }

        @media only screen and (max-width: 768px) {
            .tinv-wishlist table thead th .tinvwl-mobile {
                display: none;
            }
        }
    ";

    // Añadir estilos en línea después de Slick CSS
    wp_add_inline_style('slick-css', $custom_css_prod);

wp_add_inline_style('slick-css', $custom_css_prod);

// Encolar script de AJAX para añadir al carrito
$custom_js = "
jQuery(document).ready(function($) {
    $('.add-to-cart-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var productID = form.find('input[name=\"add-to-cart\"]').val();
        var quantity = form.find('input[name=\"quantity\"]').val();

        $.ajax({
            type: 'POST',
            url: '" . admin_url('admin-ajax.php') . "',
            data: {
                action: 'buytiti_add_to_cart',
                product_id: productID,
                quantity: quantity,
            },
            success: function(response) {
                if (response.success) {
                    alert('Producto añadido al carrito');
                } else {
                    alert('Error al añadir el producto al carrito');
                }
            },
        });
    });
});
";

wp_add_inline_script('slick-js', $custom_js);
}
add_action('wp_enqueue_scripts', 'buytiti_enqueue_scripts_to_slider');

// Manejar solicitud AJAX para añadir al carrito
function buytiti_add_to_cart_ajax() {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    $added = WC()->cart->add_to_cart($product_id, $quantity);

    if ($added) {
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_buytiti_add_to_cart', 'buytiti_add_to_cart_ajax');
add_action('wp_ajax_nopriv_buytiti_add_to_cart', 'buytiti_add_to_cart_ajax');

// Función para mostrar el slider
function get_product_bs_brand($product) {
    $attributes = $product->get_attributes();

    foreach ($attributes as $attribute) {
        if ($attribute->get_name() == 'Marca') {
            $options = $attribute->get_options();
            if (!empty($options)) {
                return $options[0]; // Devuelve el primer valor de la lista
            }
        }
    }

    return 'BUYTITI'; // Si no hay atributo "Marca", se devuelve "BUYTITI"
}

// Función para obtener la categoría más profunda
function get_deepest_category_in_products($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');

    if (empty($terms)) {
        return 'Sin Categoría'; // Devuelve un valor por defecto si no hay categorías
    }

    // Ordenar las categorías por la cantidad de ancestros para encontrar la más profunda
    usort($terms, function ($a, $b) {
        return count(get_ancestors($a->term_id, 'product_cat')) - count(get_ancestors($b->term_id, 'product_cat'));
    });

    // Retorna la categoría más profunda
    return end($terms)->name;
}

// Modificación del slider para mostrar la marca y la categoría
function buytiti_product_bs_slider( $atts = array() ) {
    $atts = shortcode_atts(array(
        'category' => '',
        'cantidad' => 10,
        'bestsellers' => 'false'
    ), $atts, 'buytiti_slider_no_api' );

    // $products = buytiti_get_woocommerce_products_in_stock($category, $cantidad, $bestsellers);
    $products = buytiti_get_woocommerce_products_bs_in_stock( $atts['category'], $atts['cantidad'],  $atts['bestsellers'] );

    if (empty($products)) {
        return '<p>No se encontraron productos en stock.</p>';
    }

    ob_start();
    ?>
    <div class="buytiti-product-slider slick-hidden">
        <?php foreach ($products as $product) : ?>
            <?php
            $product_id = $product->ID;
            $product_obj = wc_get_product($product_id);

            // Obtener la marca y la categoría más profunda
            $brand = get_product_bs_brand($product_obj);
            $deepest_category = get_deepest_category_in_products($product_id);

            // Obtén todas las imágenes del producto
            $product_images = $product_obj->get_gallery_image_ids();

            // Asegúrate de que el producto tenga al menos dos imágenes
            if (count($product_images) >= 2) {
                // Obtén la URL de la segunda imagen
                $second_image_url = wp_get_attachment_url($product_images[1]);
            } else {
                // Si no hay una segunda imagen, usa la imagen principal
                $second_image_url = $product_obj->get_image_id();
            }

            $product_image = $product_obj->get_image();
            $product_sku = $product_obj->get_sku();
            $product_name = $product_obj->get_name();
            $product_price = $product_obj->get_price_html();
            $product_stock = $product_obj->get_stock_quantity();

			// Obtener los precios del producto
           $sale_price = $product_obj->get_sale_price();
           $regular_price = $product_obj->get_regular_price();

		   // Variable para almacenar el precio a mostrar
$display_price = '';
$price_class = ''; // Variable para la clase CSS del precio

// Verificar si hay un precio de venta
if ($sale_price && $regular_price) {
    // Mostrar el precio regular tachado
    $display_price .= '<span class="product-regular-price-tachado">' . wc_price($regular_price) . '</span>';

    // Mostrar el precio de venta
    $display_price .= ' <span class="product-sale-price">' . wc_price($sale_price) . '</span>';
} else {
    // Si no hay precio de venta, mostrar solo el precio regular sin tachado
    $display_price = '<span class="product-regular-price">' . wc_price($regular_price) . '</span>';
}

// Asignar clase CSS según si el producto tiene precio de venta o no
$price_class = ($sale_price) ? 'product-price-on-sale' : 'product-price-regular';


			// Clase CSS para el SKU
            $sku_class = 'product-sku-movil';
			// Clase CSS para el nombre del producto
            $product_name_class = 'product-name-movil-buytiti'; // Nombre de la clase

            // Calcular si el producto es "nuevo" (7 días o menos desde su creación)
            $created_date = new DateTime($product->post_date_gmt);
            $now = new DateTime();
            $interval = $now->diff($created_date);
            $is_new = $interval->days <= 7;

            // Verificar etiquetas de oferta y asignar clases CSS específicas
$sale_label = '';
$sale_class = ''; // Para mantener la clase CSS que corresponde a la etiqueta

if ($product_obj->get_sale_price()) {
    if (has_term('Ofertas en Vivo', 'product_cat', $product_id)) {
        $sale_label = 'Ofertas en Vivo';
        $sale_class = 'live-offer-label'; // Clase CSS para "Ofertas en Vivo"
    } elseif (has_term('LIQUIDACIONES', 'product_cat', $product_id)) {
        $sale_label = 'LIQUIDACIÓN';
        $sale_class = 'clearance-label'; // Clase CSS para "LIQUIDACIÓN"
    } else {
        $sale_label = 'Oferta';
        $sale_class = 'default-sale-label'; // Clase CSS para una oferta estándar
    }
}
			   // Si el producto tiene un precio de venta y un precio regular, calcular el porcentaje de descuento
			   $sale_price = $product_obj->get_sale_price();
			   $regular_price = $product_obj->get_regular_price();
			   if ( $sale_price && $regular_price > 0 ) {
				   $descuento = ( ( $regular_price - $sale_price ) / $regular_price ) * 100;
				   $display_price .= ' <span class="product-discount-buytiti">-' . round($descuento) . '%</span>'; // Etiqueta de descuento
				}
                // Obtener el número de ventas del producto
                $total_sales = get_post_meta($product_id, 'total_sales', true);
            ?>
            <div class="buytiti-product">
                <?php if ($atts['bestsellers'] === 'true') : ?>
                    <img src="https://i0.wp.com/buytiti.com/wp-content/uploads/insignia.png?w=3000&ssl=1" alt="Insignia" class="product-badge-image" />
                <?php elseif ($is_new) : ?>
                    <div class="product-new-label">Nuevo</div>
                <?php endif; ?>

                <?php if ($sale_label && $atts['bestsellers'] !== 'true') : ?>
                    <div class="product-sale-label <?php echo esc_attr($sale_class); ?>">
                        <?php echo esc_html($sale_label); ?>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>">
                    <img src="<?php echo esc_url(wp_get_attachment_url($product_obj->get_image_id())); ?>" class="product-image-movil-buytiti" data-src="<?php echo esc_url(wp_get_attachment_url($product_obj->get_image_id())); ?>" data-hover="<?php echo esc_url($second_image_url); ?>">
                    
                    <!-- Botón de agregar a favoritos -->
                    <div class="ti-wishlist-button">
                        <?php echo do_shortcode('[ti_wishlists_addtowishlist loop=yes product_id="' . $product_id . '"]'); ?>
                    </div>
                    
                    <div class="product-stock-buytiti-movil">Disponible: <?php echo esc_html($product_stock); ?></div>

                    <div class="product-info">
                        <span class="<?php echo esc_attr($sku_class); ?>">SKU: <?php echo esc_html($product_sku); ?></span>
                        <!-- Mostrar la marca y la categoría -->
                        <div class="product-brand-category">
                            <?php echo esc_html($brand); ?> - <?php echo esc_html($deepest_category); ?>
                        </div>
                        <h3 class="<?php echo esc_attr($product_name_class); ?>"><?php echo esc_html($product_name); ?></h3>
                        <span class="<?php echo esc_attr($price_class); ?>"><?php echo wp_kses_post($display_price); ?></span>
                    </div>
                </a>
                
                <!-- Mostrar el número de ventas -->
                <?php if ($atts['bestsellers'] === 'true') : ?>
                    <div class="product-sold-count">Vendidos: <?php echo esc_html($total_sales); ?></div>
                <?php endif; ?>

                <form class="add-to-cart-form" method="post">
                    <input type="hidden" name="add-to-cart" value="<?php echo esc_attr($product_id); ?>">
                    <input type="number" class="quantity-input" name="quantity" value="1" min="1" max="<?php echo esc_attr($product_stock); ?>">
                    <button type="submit" class="add-to-cart-button">Añadir al carrito</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.buytiti-product-slider').slick({
                infinite: true,
                autoplay: true, 
                autoplaySpeed: 3000, // Velocidad del autoplay en milisegundos (3 segundos)
                speed: 300,
                slidesToShow: 4,
                slidesToScroll: 1,
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1,
                            infinite: true, 
                            autoplay: true, 
                        }
                    },
                    {
                        breakpoint: 600,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1,
                            infinite: true,
                            autoplay: true, 
                        }
                    },
                    {
                        breakpoint: 480,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 1,
                                infinite: true, 
                                autoplay: true, 
                            }
                    }
                ]
            });

            $('.buytiti-product-slider').removeClass('slick-hidden');

            $('.add-to-cart-form').on('submit', function() {
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            });

            $('.product-image-movil-buytiti').hover(
    function() {
        // Al pasar el cursor sobre la imagen, cambia la imagen por la segunda imagen
        $(this).data('src', $(this).attr('src')).attr('src', $(this).data('hover'));
    },
    function() {
        // Al alejar el cursor de la imagen, cambia la imagen de nuevo a la imagen original
        $(this).attr('src', $(this).data('src'));
    }
);

        });
    </script>
    <?php
    return ob_get_clean();
}


// Shortcode para el slider
function buytiti_product_bs_slider_shortcode( $atts = array() ) {
    return buytiti_product_bs_slider( $atts );
}
add_shortcode('buytiti_slider_no_api', 'buytiti_product_bs_slider_shortcode');

// [buytiti_slider_no_api category="" cantidad="40" bestsellers="true"]