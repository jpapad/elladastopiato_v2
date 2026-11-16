<?php
/**
 * Σελίδα «Επικοινωνία»: κάρτες «Τι μπορείτε να μας στείλετε» + email.
 *
 *   [ellada_contact_aside]
 *
 * Κάθε κάρτα επιλέγει το αντίστοιχο «Θέμα» στη φόρμα του Elementor (πεδίο με id `topic`)
 * και πηγαίνει στο μήνυμα. Λειτουργεί και από τη διεύθυνση: /epikoinonia/?thema=topos
 */

defined( 'ABSPATH' ) || exit;

/** slug (για ?thema=) => [τιμή του πεδίου «Θέμα», τίτλος κάρτας, κείμενο, εικονίδιο SVG] */
function ellada_contact_topics() {
	return array(
		'syntagi'    => array( 'Πρόταση συνταγής', 'Προτείνετε μια συνταγή', 'Μια συνταγή της γιαγιάς ή του τόπου σας που αξίζει να τη μάθουν όλοι.', '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18a9 9 0 0 1-18 0Z"/><path d="M7 4c0 1.5 1 1.5 1 3M12 3c0 1.5 1 1.5 1 3M17 4c0 1.5 1 1.5 1 3"/></svg>' ),
		'topos'      => array( 'Τοπικό σημείο στον χάρτη', 'Βάλτε τον τόπο σας στον χάρτη', 'Παραγωγοί, ταβέρνες, αγορές, πανηγύρια — πείτε μας πού και τι.', '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>' ),
		'lathos'     => array( 'Διόρθωση / λάθος', 'Βρήκατε λάθος;', 'Σε μια συνταγή, μια περιοχή ή ένα σημείο του χάρτη. Θα το διορθώσουμε.', '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>' ),
		'synergasia' => array( 'Συνεργασία', 'Συνεργασίες', 'Φωτογραφίες, κείμενα, τύπος ή μια ιδέα που θέλετε να κάνουμε μαζί.', '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.4A8.5 8.5 0 1 1 21 12Z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/></svg>' ),
	);
}

add_shortcode(
	'ellada_contact_aside',
	function () {
		ellada_front_style();
		$email = 'info@elladastopiato.gr';
		$html  = '<div class="ellada-contact">';
		$html .= '<p class="ellada-contact__mail">ή γράψτε μας απευθείας στο <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>';
		$html .= '<h2 class="ellada-contact__title">Τι μπορείτε να μας στείλετε</h2><ul class="ellada-contact__cards">';
		foreach ( ellada_contact_topics() as $slug => $t ) {
			$html .= sprintf(
				'<li><a class="ellada-contact__card" href="%1$s" data-topic="%2$s"><span class="ellada-contact__icon" aria-hidden="true">%3$s</span><span class="ellada-contact__name">%4$s</span><span class="ellada-contact__text">%5$s</span></a></li>',
				esc_url( add_query_arg( 'thema', $slug ) . '#form-field-message' ),
				esc_attr( $t[0] ),
				$t[3], // σταθερό SVG από τον κώδικα
				esc_html( $t[1] ),
				esc_html( $t[2] )
			);
		}
		$html .= '</ul></div>';

		$slugs = array();
		foreach ( ellada_contact_topics() as $slug => $t ) {
			$slugs[ $slug ] = $t[0];
		}
		// Μικρό script: κάρτα / ?thema= → επιλογή θέματος στη φόρμα.
		$html .= '<script>(function(){var m=' . wp_json_encode( $slugs, JSON_UNESCAPED_UNICODE ) . ';'
			. 'function pick(v,go){var s=document.getElementById("form-field-topic");if(!s||!v)return;s.value=v;'
			. 'document.querySelectorAll(".ellada-contact__card").forEach(function(c){c.classList.toggle("is-active",c.getAttribute("data-topic")===v)});'
			. 'if(go){var msg=document.getElementById("form-field-message");if(msg){msg.scrollIntoView({behavior:"smooth",block:"center"});msg.focus({preventScroll:true});}}}'
			. 'document.addEventListener("click",function(e){var c=e.target.closest&&e.target.closest(".ellada-contact__card");if(!c)return;e.preventDefault();pick(c.getAttribute("data-topic"),true);});'
			. 'document.addEventListener("change",function(e){if(e.target&&e.target.id==="form-field-topic")pick(e.target.value,false);});'
			. 'function init(){var q=new URLSearchParams(location.search).get("thema");if(q&&m[q])pick(m[q],false);}'
			. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",init);else init();})();</script>';
		return $html;
	}
);
