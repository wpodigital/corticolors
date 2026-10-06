<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.1' );

/**
 * Target category/page slugs for specific CWV optimizations.
 */
define( 'CWV_TARGET_SLUGS', [
	'estores-enrollables-screen-a-medida',
	'estores-motorizados',
	'estores-sin-taladrar',
	'mecanismos-para-estores',
	'estores-exterior',
	'estores-termicos',
	'mecanismo-para-estores-con-cadena-paqueto-plegable',
	'estores-enrollables-a-medida',
	'estores-salon',
] );
/**
 * Check if the current page matches any of the CWV target slugs or is home.
 *
 * @return bool
 */

/**
 * Optimización Core Web Vitals: Dashicons solo es necesario para los iconos
 * de la barra de administración de WordPress. Si esa barra no se muestra
 * (visitantes anónimos, la mayoría del tráfico), se elimina para ahorrar
 * ~35 KiB de CSS sin afectar al diseño ni al JS del sitio.
 *
 * @return void
 */
function corticolors_dequeue_dashicons_frontend() {
	if ( ! is_admin_bar_showing() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'corticolors_dequeue_dashicons_frontend', 100 );

function cwv_is_target_page() {
	if ( is_front_page() || is_home() ) {
		return true;
	}
	$slug = get_query_var( 'name' ) ?: ( get_queried_object() ? get_queried_object()->slug ?? '' : '' );
	if ( in_array( $slug, CWV_TARGET_SLUGS, true ) ) {
		return true;
	}
	// Also match WooCommerce product categories.
	if ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		if ( $term && in_array( $term->slug, CWV_TARGET_SLUGS, true ) ) {
			return true;
		}
	}
	// Match pages by slug.
	if ( is_page() ) {
		$page = get_queried_object();
		if ( $page && in_array( $page->post_name, CWV_TARGET_SLUGS, true ) ) {
			return true;
		}
	}
	return false;
}


/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

	// Conditional CSS for products with discount: enqueue as stylesheet instead of inline wp_head.
	if ( is_product() ) {
		global $post;
		if ( $post && get_post_meta( $post->ID, 'tiene_descuento', true ) === 'si' ) {
			wp_add_inline_style(
				'hello-elementor-child-style',
				'.summary.entry-summary p.price{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;}'
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );

// ============================================================
// CWV #4 — Defer non-critical scripts (analytics, tracking…)
// ============================================================
add_filter( 'script_loader_tag', 'cwv_defer_non_critical_scripts', 10, 3 );
function cwv_defer_non_critical_scripts( $tag, $handle, $src ) {
	$defer_handles = [
		'google-tag-manager',
		'gtag',
		'google-analytics',
		'ga',
		'facebook-pixel',
		'fb-pixel',
		'hotjar',
		'clarity',
	];
	foreach ( $defer_handles as $h ) {
		if ( false !== strpos( $handle, $h ) ) {
			return str_replace( ' src=', ' defer src=', $tag );
		}
	}
	return $tag;
}

// ============================================================
// CWV #6 — Font-display: swap for Google Fonts
// ============================================================
add_filter( 'style_loader_src', 'cwv_google_fonts_display_swap', 10, 2 );
function cwv_google_fonts_display_swap( $src, $handle ) {
	if ( strpos( $src, 'fonts.googleapis.com' ) !== false ) {
		if ( strpos( $src, 'display=' ) === false ) {
			$src = add_query_arg( 'display', 'swap', $src );
		}
	}
	return $src;
}

// ============================================================
// CWV #1 & #2 — Preconnect + Preload LCP hero per target page
// ============================================================
add_action( 'wp_head', 'cwv_preconnect_and_preload', 1 );
function cwv_preconnect_and_preload() {
	// Preconnect to external origins used by the site.
	$preconnect_origins = [
		'https://fonts.googleapis.com',
		'https://fonts.gstatic.com',
	];
	foreach ( $preconnect_origins as $origin ) {
		echo '<link rel="preconnect" href="' . esc_url( $origin ) . '" crossorigin>' . "\n";
	}

	// DNS-prefetch fallback for older browsers.
	echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
	echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";

	if ( ! cwv_is_target_page() ) {
		return;
	}

	// Map of slug => hero image URL.
	// These are placeholder paths; replace with actual image URLs from the media library.
	$hero_images = [
		''                                                      => 'https://corticolors.com/wp-content/uploads/imagenes-decorativas/corticolors-tienda-de-estores-en-valencia-online.jpeg',
		'estores-enrollables-screen-a-medida'                   => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-motorizados'                                    => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-sin-taladrar'                                   => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'mecanismos-para-estores'                                => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-exterior'                                       => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-termicos'                                       => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'mecanismo-para-estores-con-cadena-paqueto-plegable'     => 'https://corticolors.com/wp-content/uploads/mecanismos-estores/mecanismo-estores-cadena-corticolors.jpg',
		'estores-enrollables-a-medida'                           => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
		'estores-salon'                                          => 'https://corticolors.com/wp-content/uploads/logo/logo-corticolors.png',
	];

	$current_slug = '';
	if ( is_front_page() || is_home() ) {
		$current_slug = '';
	} elseif ( is_tax( 'product_cat' ) ) {
		$term = get_queried_object();
		$current_slug = $term ? $term->slug : '';
	} elseif ( is_page() ) {
		$page = get_queried_object();
		$current_slug = $page ? $page->post_name : '';
	} else {
		$current_slug = get_query_var( 'name' );
	}

	if ( isset( $hero_images[ $current_slug ] ) && ! empty( $hero_images[ $current_slug ] ) ) {
		$hero_url = esc_url( $hero_images[ $current_slug ] );
		echo '<link rel="preload" as="image" href="' . $hero_url . '" fetchpriority="high">' . "\n";
	}
}

/**
* Quitar productos relacionados en WooCommerce
*/
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/*Detalles cliente Pagina gracias*/
add_action( 'woocommerce_thankyou', 'adding_customers_details_to_thankyou', 10, 1 );
function adding_customers_details_to_thankyou( $order_id ) {
    // Only for non logged in users
    if ( ! $order_id || is_user_logged_in() ) return;

    $order = wc_get_order($order_id); // Get an instance of the WC_Order object

    wc_get_template( 'order/order-details-customer.php', array('order' => $order ));
}

/*Pedidos contrarembolso en espera*/
function wooc_cod_status( $status ) {
return 'on-hold';
}
add_filter( 'woocommerce_cod_process_payment_order_status', 'wooc_cod_status', 15 );


#/Comentarios blog span
add_filter( 'comment_form_defaults', 'custom_reply_title' );
function custom_reply_title( $defaults ){
$defaults['title_reply_before'] = '<span id="reply-title" class="comment-reply-title">';
$defaults['title_reply_after'] = '</span>';
return $defaults;
}

#/Descripción corta productos
  add_action('wp', 'ocultar_descripcion_corta_producto');
function ocultar_descripcion_corta_producto() {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
}

#/ Quitar "tienda" migas de pan Yoast Seo
function wpseo_remove_store_breadcrumb($links) {
    $store_url = home_url('/tienda/'); // Reemplaza '/tienda/' con la URL real de tu tienda
    foreach ($links as $key => $link) {
        if (isset($link['url']) && $link['url'] == $store_url) {
            unset($links[$key]);
        }
    }
    return $links;
}
add_filter('wpseo_breadcrumb_links', 'wpseo_remove_store_breadcrumb');

// Función para reemplazar las migas de pan de WooCommerce por las de Yoast SEO
function reemplazar_migas_de_pan() {
    if ( function_exists('yoast_breadcrumb') ) {
        yoast_breadcrumb('<nav id="breadcrumbs">','</nav>');
    }
}

// Reemplazar las migas de pan de WooCommerce por las de Yoast SEO
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
add_action('woocommerce_before_main_content', 'reemplazar_migas_de_pan', 20);

// Ocultar otros métodos de envío cuando el envío gratuito está disponible.
function my_hide_shipping_when_free_is_available( $rates ) {
$free = array();
foreach ( $rates as $rate_id => $rate ) {
if ( 'free_shipping' === $rate->method_id ) {
$free[ $rate_id ] = $rate;
break;
}
}
return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'my_hide_shipping_when_free_is_available', 100 );

// Combiar metadesripción hellow elementor y Yoast
// Creamos la función
function eliminar_meta_sobrante() {
// Eliminamos esa molesta metadesc
remove_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );
}
// llamamos a la función declarada
add_action( 'after_setup_theme', 'eliminar_meta_sobrante' );

/* Remove the default WooCommerce 3 JSON/LD structured data */
function remove_output_structured_data() {
remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 ); // This removes structured data from all frontend pages
}
add_action( 'init', 'remove_output_structured_data' );

// Ocultamos otras formas de envío excepto la recogida en tienda si hay disponible envío gratuito.

add_filter( 'woocommerce_package_rates', 'esl_hide_shipping_when_free_is_available', 10, 2 );

function esl_hide_shipping_when_free_is_available( $rates, $package ) {
	$new_rates = array();
	foreach ( $rates as $rate_id => $rate ) { 
		if ( 'free_shipping' === $rate->method_id ) {
			$new_rates[ $rate_id ] = $rate;
			break;
		}
	}

	if ( ! empty( $new_rates ) ) { 
		foreach ( $rates as $rate_id => $rate ) {
			if ('local_pickup' === $rate->method_id ) {
				$new_rates[ $rate_id ] = $rate;
				break;
			}
		}
		return $new_rates;
	}

	return $rates;
}

// // Valoraciones de producto schema pro
add_filter( 'wp_schema_pro_add_woocommerce_review', '__return_true' );

// Quitar sku de la página del producto
add_filter( 'wc_product_sku_enabled', '__return_false' );



	add_action('woocommerce_product_meta_start', function () {
		ob_start();
	}, 55);

	add_action('woocommerce_product_meta_end', function () {
		$html = ob_get_clean();
		$html = preg_replace('<span class="posted_in">.*<\/span>', '', $html);
		echo $html;
	}, 55);

// Añadir noindex y canonical a feeds (RSS, Atom)
add_action('wp_head', function () {
    if (is_feed()) {
        global $post;

        // Sacar la URL original del contenido
        if (isset($post->ID)) {
            $canonical = get_permalink($post->ID);
        } else {
            $canonical = home_url();
        }

        echo '<meta name="robots" content="noindex, follow">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }
});




// AUTOR INFORMACION
add_shortcode('autor_rol', function () {
    $author_id = get_the_author_meta('ID');
    if (!$author_id) return '';

    $user = get_user_by('id', $author_id);
    if (!$user || empty($user->roles)) return '';

    $role_key = $user->roles[0]; // primer rol

    // nombre legible del rol
    $wp_roles = wp_roles();
    $role_name = $wp_roles->roles[$role_key]['name'] ?? $role_key;

    return esc_html($role_name);
});


add_shortcode('autor_redes', function () {

    $author_id = get_the_author_meta('ID');
    if (!$author_id) return '';

    // Claves de WordPress → Icono → URL base (si aplica)
    $redes = [
        'facebook'   => ['icon' => 'fab fa-facebook-f', 'prefix' => 'https://'],
        'instagram'  => ['icon' => 'fab fa-instagram',  'prefix' => 'https://'],
        'linkedin'   => ['icon' => 'fab fa-linkedin-in','prefix' => 'https://'],
        'youtube'    => ['icon' => 'fab fa-youtube',    'prefix' => 'https://'],
        'twitter'    => ['icon' => 'fab fa-twitter',    'prefix' => 'https://'],
        'pinterest'  => ['icon' => 'fab fa-pinterest-p','prefix' => 'https://'],
        'soundcloud' => ['icon' => 'fab fa-soundcloud', 'prefix' => 'https://'],
        'tumblr'     => ['icon' => 'fab fa-tumblr',     'prefix' => 'https://'],
        'user_url'   => ['icon' => 'fas fa-globe',      'prefix' => '']
    ];

    $html = '<div class="autor-redes" style="display:flex;gap:10px;align-items:center;">';

    foreach ($redes as $key => $data) {

        // Obtener valor del campo del autor
        if ($key === 'user_url') {
            $url = get_the_author_meta('user_url', $author_id);
        } else {
            $url = get_user_meta($author_id, $key, true);

            // fallback por si el theme usa otro nombre
            if (!$url) {
                $url = get_user_meta($author_id, 'contactmethod_'.$key, true);
            }
        }

        if (!$url) continue; // Si no hay red, no muestra icono

        // Asegurar https si el usuario no lo escribió
        if ($data['prefix'] && !preg_match('~^https?://~i', $url)) {
            $url = $data['prefix'] . ltrim($url, '/');
        }

        $html .= sprintf(
            '<a href="%s" target="_blank" rel="nofollow noopener" style="text-decoration:none;">
                <i class="%s" style="font-size:18px;"></i>
            </a>',
            esc_url($url),
            esc_attr($data['icon'])
        );
    }

    $html .= '</div>';

    return $html;
});




/**
 * [autores_blog columns="4" role_label="1" only_roles="author,editor,administrator"]
 * Muestra usuarios que tengan posts publicados.
 */
add_shortcode('autores_blog', function($atts){

    $atts = shortcode_atts([
        'columns' => 4,
        'only_roles' => '',     // opcional: filtrar roles (csv)
        'role_label' => 1,      // 1=mostrar nombre del rol, 0=ocultar
        'avatar_size' => 160,
    ], $atts);

    $only_roles = array_filter(array_map('trim', explode(',', $atts['only_roles'])));

    // Obtener usuarios (limitamos a quienes tengan al menos 1 post)
    $users = get_users([
        'fields' => ['ID','display_name'],
        'orderby' => 'display_name',
        'order' => 'ASC'
    ]);

    if (empty($users)) return '';

    $cols = max(1, min(6, (int)$atts['columns']));

    $html = '<div class="autores-grid" style="display:grid;gap:24px;grid-template-columns:repeat('.$cols.',minmax(0,1fr));">';

    foreach ($users as $u){

        $user = get_user_by('id', $u->ID);
        if (!$user) continue;

        // Filtrar por roles si se pide
        if (!empty($only_roles)) {
            $has = false;
            foreach ((array)$user->roles as $r) {
                if (in_array($r, $only_roles, true)) { $has = true; break; }
            }
            if (!$has) continue;
        }

        // Contar posts publicados por autor
        $count = count_user_posts($u->ID, 'post', true); // true = solo publicados
        if ($count < 1) continue; // SOLO autores con blogs

        $author_url  = get_author_posts_url($u->ID);
        $avatar      = get_avatar($u->ID, (int)$atts['avatar_size']);
        $name        = esc_html($user->display_name);

        // Rol legible
        $role_name = '';
        if ((int)$atts['role_label'] === 1 && !empty($user->roles)) {
            $role_key = $user->roles[0];
            $wp_roles = wp_roles();
            $role_name = $wp_roles->roles[$role_key]['name'] ?? $role_key;
        }

        $html .= '<div class="autor-card">';

        $html .= '<a href="'.esc_url($author_url).'" class="urlautor">';
        $html .= '<div class="avatarautor">'.$avatar.'</div>';
        $html .= '<div class="nombreautor">'.$name.'</div>';

        if ($role_name) {
            $html .= '<div style="opacity:.85;margin-top:6px;" class="rolautor">'.esc_html($role_name).'</div>';
        }

        $html .= '<div class="contadorautor">'.$count.' artículos</div>';
        $html .= '</a>';

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
});

// Jet Engine Solución temporal ?nocache
add_filter(
    'jet-engine/listings/ajax-listing-url',
    function( $url ) {
        $url = preg_replace( '/\?nocache=\d+\b/', '', $url );
        $url = preg_replace( '/&nocache=\d+\b/', '', $url );
       
        return $url;
    }
);
// Noindex + canonical para URLs con parámetros no indexables
add_filter('wpseo_robots', function($robots) {
    $params = ['nocache', 'ivrating', 'sort', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'gad_source', 'filter_estancia', 'pagenum'];
    foreach ($params as $param) {
        if (isset($_GET[$param])) {
            return 'noindex, follow';
        }
    }
    return $robots;
});

add_action('wp_head', function () {
    $params = ['nocache', 'ivrating', 'sort', 'utm_source', 'utm_medium', 'utm_campaign', 'gclid', 'gad_source', 'filter_estancia', 'pagenum'];
    foreach ($params as $param) {
        if (isset($_GET[$param])) {
            $canonical_url = is_singular() ? get_permalink() : home_url($_SERVER['REQUEST_URI']);
            $canonical_url = strtok($canonical_url, '?');
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
            break;
        }
    }
}, 1);

/*Añadir enlace finalizar compra*/
add_action( 'wp_footer', 'cortico_enlace_envios_js', 99 );
function cortico_enlace_envios_js() {
    if ( ! is_checkout() ) {
        return;
    }
    ?>
    <script>
    (function () {
        var html = ' y las <a class="cortico-envios" href="https://corticolors.com/condiciones-de-envios-y-devoluciones/" target="_blank" rel="noopener">condiciones de envíos y devoluciones</a>';

        function insertar() {
            var destino = document.querySelector('.woocommerce-terms-and-conditions-checkbox-text')
                       || document.querySelector('.woocommerce-terms-and-conditions-wrapper label');
            if ( destino && ! destino.querySelector('.cortico-envios') ) {
                destino.insertAdjacentHTML('beforeend', html);
            }
        }

        document.addEventListener('DOMContentLoaded', insertar);
        if ( window.jQuery ) {
            jQuery(document.body).on('updated_checkout', insertar);
        }
    })();
    </script>
    <?php
}
/* ════════════════════════════════════════════════════════════════════
 * VERSIÓN ANTIGUA — MONOS CREATIVOS — DESACTIVADA (no se ejecuta)
 * ════════════════════════════════════════════════════════════════════
 * ─── INICIO CÓDIGO ANTIGUO ──────────────────────────────────────────
 *
 * MONOS CREATIVOS AJUSTE SCROLL FICHA PRODUCTO
 *
 * add_action('wp_footer', function() {
 * ?>
 * <script>
 * (function($) {
 * var fixedPos = 0;
 *
 * // Capture scroll position before popup opens.
 * $(document).on('click', '.btn-productp, .btn-personalize, a[href^="#"]', function() {
 * fixedPos = $(window).scrollTop();
 * });
 *
 * // Lock body scroll while popup is open.
 * $(window).on('elementor/popup/show', function() {
 * fixedPos = $(window).scrollTop();
 * $('body').css({ overflow: 'hidden', height: '100vh', 'touch-action': 'none' });
 * });
 *
 * // On hide: restore scroll, clean URL — no rAF loop needed.
 * $(window).on('elementor/popup/hide', function() {
 * // Restore body first.
 * $('body').css({ overflow: '', height: '', 'touch-action': '' });
 *
 * // Single synchronous scroll-restore via two rAF frames to beat Elementor's focus jump.
 * requestAnimationFrame(function() {
 * requestAnimationFrame(function() {
 * window.scrollTo(0, fixedPos);
 * if (window.location.hash) {
 *     history.replaceState(null, null, window.location.pathname + window.location.search);
 * }
 * });
 * });
 * });
 *
 * })(jQuery);
 * </script>
 * <?php
 * }, 999);
 *
 * ─── FIN CÓDIGO ANTIGUO ─────────────────────────────────────────────
 */
 
 
/* ════════════════════════════════════════════════════════════════════
 * VERSIÓN NUEVA — ACTIVA
 * ════════════════════════════════════════════════════════════════════
 */
 
add_action( 'wp_footer', 'cortico_popup_scroll_fix', 999 );
function cortico_popup_scroll_fix() {
 
	if ( is_admin() ) {
		return;
	}
	?>
<style id="cortico-popup-lock-css">
body.cc-popup-lock {
	position: fixed;
	left: 0;
	right: 0;
	width: 100%;
	overflow: hidden;
	touch-action: none;
}
 
/* Desactiva el scroll suave SOLO durante la restauración de posición.
   Sin esto, si el sitio tiene scroll-behavior:smooth, el salto de vuelta
   se anima y se ve cómo la página viaja desde arriba hasta la posición. */
html.cc-no-smooth,
html.cc-no-smooth body {
	scroll-behavior: auto !important;
}
</style>
<script id="cortico-popup-scroll-fix">
(function ($) {
	'use strict';
 
	if ( ! $ ) { return; }
 
	var savedScroll = 0;   // posición a la que hay que volver
	var clickScroll = 0;   // posición capturada en el último clic
	var clickAt     = 0;   // timestamp de ese clic
	var openPopups  = 0;   // popups abiertos simultáneamente
	var guardUntil  = 0;   // ventana de vida del parche de focus()
	var nativeFocus = HTMLElement.prototype.focus;
	var smoothTimer = null;
 
	var GUARD_MS = 800;
 
	/* ----------------------------------------------------------------
	 * Parche de focus(): con la navegación accesible activa, Elementor
	 * devuelve el foco al botón disparador al cerrar el popup y el
	 * navegador hace scroll hacia él. Con preventScroll el foco SÍ se
	 * mueve (accesibilidad intacta) pero sin desplazar la página.
	 * ---------------------------------------------------------------- */
	HTMLElement.prototype.focus = function ( options ) {
		if ( Date.now() < guardUntil ) {
			options = $.extend( {}, options || {}, { preventScroll: true } );
		}
		try {
			return nativeFocus.call( this, options );
		} catch ( e ) {
			return nativeFocus.call( this );
		}
	};
 
	/* ----------------------------------------------------------------
	 * ¿Es un ancla "muerta"? Es decir, un href="#algo" cuyo destino no
	 * existe en la página. Esos son los disparadores de popup y son los
	 * únicos que cancelamos.
	 * ---------------------------------------------------------------- */
	function isDeadAnchor( href ) {
		if ( ! href || href.charAt( 0 ) !== '#' ) {
			return false;
		}
 
		var id = href.slice( 1 );
 
		if ( ! id ) {
			return true;  // href="#" puro
		}
 
		// Los enlaces #elementor-action:... los gestiona Elementor.
		if ( id.indexOf( 'elementor-action' ) === 0 ) {
			return false;
		}
 
		try {
			id = decodeURIComponent( id );
		} catch ( e ) {}
 
		if ( document.getElementById( id ) ) {
			return false;
		}
 
		try {
			if ( document.getElementsByName( id ).length ) {
				return false;
			}
		} catch ( e ) {}
 
		return true;
	}
 
	function currentScroll() {
		return window.pageYOffset || document.documentElement.scrollTop || 0;
	}
 
	function lockBody() {
		var gap = window.innerWidth - document.documentElement.clientWidth;
 
		$( document.body )
			.addClass( 'cc-popup-lock' )
			.css({
				top: ( -savedScroll ) + 'px',
				paddingRight: gap > 0 ? gap + 'px' : ''
			});
	}
 
	/* ----------------------------------------------------------------
	 * Salto de scroll SECO. Si el sitio tiene scroll-behavior:smooth,
	 * un scrollTo normal se anima y se ve el viaje de la página.
	 * Se desactiva el suavizado solo durante la restauración.
	 * ---------------------------------------------------------------- */
	function setScroll( y ) {
		var html = document.documentElement;
 
		html.classList.add( 'cc-no-smooth' );
 
		try {
			window.scrollTo({ top: y, left: 0, behavior: 'instant' });
		} catch ( e ) {
			window.scrollTo( 0, y );
		}
 
		if ( currentScroll() !== y ) {
			window.scrollTo( 0, y );
		}
 
		clearTimeout( smoothTimer );
		smoothTimer = setTimeout( function () {
			html.classList.remove( 'cc-no-smooth' );
		}, 600 );
	}
 
	function unlockBody() {
		document.documentElement.classList.add( 'cc-no-smooth' );
 
		$( document.body )
			.removeClass( 'cc-popup-lock' )
			.css({ top: '', paddingRight: '' });
 
		setScroll( savedScroll );
	}
 
	/* ----------------------------------------------------------------
	 * Limpia el hash de la URL, pero solo si es basura de popup. Un
	 * ancla legítimo se respeta.
	 * ---------------------------------------------------------------- */
	function cleanHash() {
		var h = window.location.hash;
 
		if ( ! h ) {
			return;
		}
 
		if ( h.indexOf( 'elementor-action' ) !== -1 || isDeadAnchor( h ) ) {
			history.replaceState( null, '', window.location.pathname + window.location.search );
		}
	}
 
	/* ----------------------------------------------------------------
	 * CLIC: capturamos posición y cancelamos la navegación del ancla.
	 * En fase de captura (true) para adelantarnos a todo lo demás.
	 * preventDefault NO impide que Elementor abra el popup: solo anula
	 * la navegación por defecto del navegador.
	 * ---------------------------------------------------------------- */
	document.addEventListener( 'click', function ( e ) {
 
		if ( openPopups === 0 ) {
			clickScroll = currentScroll();
			clickAt     = Date.now();
		}
 
		var a = ( e.target && e.target.closest ) ? e.target.closest( 'a[href]' ) : null;
 
		if ( ! a ) {
			return;
		}
 
		if ( isDeadAnchor( a.getAttribute( 'href' ) ) ) {
			guardUntil = Date.now() + GUARD_MS;
			e.preventDefault();
		}
 
	}, true );
 
	/* ---------------------------------------------------------------- */
 
	$( document ).on( 'elementor/popup/show', function () {
		guardUntil = Date.now() + GUARD_MS;
 
		if ( openPopups === 0 ) {
			// Si el clic es reciente, su posición es la buena: en ese
			// momento nada se había movido todavía.
			savedScroll = ( Date.now() - clickAt < 1000 ) ? clickScroll : currentScroll();
			lockBody();
		}
 
		openPopups++;
		cleanHash();
	});
 
	$( document ).on( 'elementor/popup/hide', function () {
		guardUntil = Date.now() + GUARD_MS;
 
		openPopups = Math.max( 0, openPopups - 1 );
 
		if ( openPopups === 0 ) {
			unlockBody();
 
			// Reintentos por si algún addon mueve el foco más tarde.
			setTimeout( function () { setScroll( savedScroll ); }, 0 );
			setTimeout( function () { setScroll( savedScroll ); }, 150 );
			setTimeout( function () { setScroll( savedScroll ); }, 400 );
		}
 
		cleanHash();
	});
 
	$( document ).on( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && openPopups > 0 ) {
			guardUntil = Date.now() + GUARD_MS;
		}
	});
 
	/* ----------------------------------------------------------------
	 * Red de seguridad: si el body queda bloqueado por cualquier motivo
	 * (cierre que no dispara el evento, botón atrás, bfcache), se libera.
	 * ---------------------------------------------------------------- */
	$( window ).on( 'pageshow', function () {
		openPopups = 0;
		$( document.body )
			.removeClass( 'cc-popup-lock' )
			.css({ top: '', paddingRight: '' });
	});
 
})( window.jQuery );
</script>
	<?php
}