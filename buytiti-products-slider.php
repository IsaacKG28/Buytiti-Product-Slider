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
    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            )
        ),
    );

    if ( $cantidad != -1 ) {
        $args['posts_per_page'] = $cantidad;
    }

    if ( filter_var($bestsellers, FILTER_VALIDATE_BOOLEAN) ) {
        $date_15_days_ago = date('Y-m-d H:i:s', strtotime('-20 days'));

        $args['date_query'] = array(
            array(
                'column' => 'post_date_gmt',
                'after'  => $date_15_days_ago
            )
        );

        $args['meta_query'][] = array(
            'key'     => 'total_sales',
            'value'   => 10,
            'compare' => '>',
        );

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
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query($args);

    return $query->posts;
}

function buytiti_enqueue_scripts_to_slider() {
    // Encolar jQuery si no está ya encolado
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    // Encolar estilos personalizados del slider
    wp_enqueue_style('buytiti-product-slider', plugin_dir_url(__FILE__) . 'buytiti-product-slider.css');

    // Script personalizado de AJAX para añadir al carrito
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
                            // Actualizar el contador del carrito (si tienes uno)
                            var cart_count = $('.cart-count');
                            if (cart_count.length) {
                                var current_count = parseInt(cart_count.text());
                                cart_count.text(current_count + parseInt(quantity));
                            }
                            // Actualizar el contenido del carrito
                            $.ajax({
                                type: 'POST',
                                url: '" . admin_url('admin-ajax.php') . "',
                                data: {
                                    action: 'buytiti_get_cart_content'
                                },
                                success: function(response) {
                                    if (response.fragments) {
                                        $.each(response.fragments, function(key, value) {
                                            $(key).replaceWith(value);
                                        });
                                    }
                                }
                            });
                        } else {
                            alert('Error al añadir el producto al carrito');
                        }
                    },
                    error: function() {
                        alert('Error en la solicitud AJAX');
                    }
                });
            });

            $('.buytiti-product-slider').slick({
                infinite: true,
                autoplay: true,
                autoplaySpeed: 3000,
                speed: 300,
                slidesToShow: 5,
                slidesToScroll: 1,
                responsive: [
                    {
                        breakpoint: 1590,
                        settings: {
                            slidesToShow: 4,
                            slidesToScroll: 1,
                            infinite: true,
                            autoplay: true,
                        }
                    },
                    {
                        breakpoint: 1366,
                        settings: {
                            slidesToShow: 3,
                            slidesToScroll: 1,
                            infinite: true,
                            autoplay: true,
                        }
                    },
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

            $('.product-image-movil-buytiti').hover(
                function() {
                    $(this).data('src', $(this).attr('src')).attr('src', $(this).data('hover'));
                },
                function() {
                    $(this).attr('src', $(this).data('src'));
                }
            );
        });
    ";

    // Añadir el script en línea después de que jQuery y Slick estén encolados
    wp_add_inline_script('jquery', $custom_js);
}
add_action('wp_enqueue_scripts', 'buytiti_enqueue_scripts_to_slider');

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
    <?php
    return ob_get_clean();
}


// Shortcode para el slider
function buytiti_product_bs_slider_shortcode( $atts = array() ) {
    return buytiti_product_bs_slider( $atts );
}
add_shortcode('buytiti_slider_no_api', 'buytiti_product_bs_slider_shortcode');

// [buytiti_slider_no_api category="" cantidad="40" bestsellers="true"]