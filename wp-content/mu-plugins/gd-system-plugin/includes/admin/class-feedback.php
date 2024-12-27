<?php

namespace WPaaS\Admin;

use \WPaaS\Plugin;


if (!defined('ABSPATH')) {

	exit;

}

/**
 * Class to handle NPS feedback
 *
 */
final class Feedback
{

	/**
	 * Constructor.
	 */
	public function __construct()
	{

		if ($GLOBALS['wpaas_feature_flag']->get_feature_flag_value('nps_survey', false) === true) {
			// Because this is a MU-Plugins, is_user_logged() will always return false if we don't check after init.
			add_action('init', [$this, 'init']);
		}

	}

	public function init()
	{
		if (is_admin() &&
			current_user_can('administrator') &&
			!Plugin::is_staging_site() &&
			defined('GD_RUM_ENABLED') &&
			GD_RUM_ENABLED &&
			Plugin::is_gd() &&
			(!defined('DOING_AJAX') || !DOING_AJAX)
		) {
			add_action('admin_print_footer_scripts', [$this, 'get_nps_survey'], PHP_INT_MAX);
		}
	}

	public function get_nps_survey()
	{
		if (defined('GD_SITE_CREATED')) {
			$siteCreationDate = (new  \DateTime())->setTimestamp(GD_SITE_CREATED);
		}

		$data = json_encode([
			'customerId'             => defined('GD_CUSTOMER_ID') ? GD_CUSTOMER_ID : null,
			'guid'                   => defined('GD_ACCOUNT_UID') ? GD_ACCOUNT_UID : null,
			'productId'              => defined('GD_ACCOUNT_UID') ? GD_ACCOUNT_UID : null,
			'product_name'           => 'MWP',
			'coblocksVersion'        => defined('COBLOCKS_VERSION') ? COBLOCKS_VERSION : null,
			'goThemeVersion'         => defined('GO_THEME_VERSION') ? GO_THEME_VERSION : null,
			'mwpSystemPluginVersion' => Plugin::version(),
			'wpUserId'               => get_current_user_id(),
			'wpVersion'              => get_bloginfo('version'),
			'mwpPlanName'            => defined('GD_PLAN_NAME') ? GD_PLAN_NAME : null,
			'wpLocale'               => get_locale(),
			'woocommerceVersion'     => defined('WC_VERSION') ? WC_VERSION : null,
			'isFullPageCDN'          => defined('GD_CDN_FULLPAGE') ? GD_CDN_FULLPAGE : null,
			'siteCreatedAt'          => defined('GD_SITE_CREATED') ? $siteCreationDate->format(\DateTime::ATOM) : null,
			'siteAgeDays'            => defined('GD_SITE_CREATED') ? floor((time() - GD_SITE_CREATED) / 86400) : 0,

		]);

		echo '<script type=\'text/javascript\'> var nps_survey_metadata = JSON.parse(\''.$data.'\'); </script>';
		echo '<script type=\'text/javascript\'> window.nps_survey_metadata = nps_survey_metadata; 
					if(typeof QSI === "undefined") {
					    QSI = {};
					    QSI.config = {
					      externalReference: window.nps_survey_metadata.customerId
					    };
					}</script>';

		echo '<!--BEGIN QUALTRICS WEBSITE FEEDBACK SNIPPET-->
				<script type=\'text/javascript\'>
				(function(){var g=function(e,h,f,g){
				this.get=function(a){for(var a=a+"=",c=document.cookie.split(";"),b=0,e=c.length;b<e;b++){for(var d=c[b];" "==d.charAt(0);)d=d.substring(1,d.length);if(0==d.indexOf(a))return d.substring(a.length,d.length)}return null};
				this.set=function(a,c){var b="",b=new Date;b.setTime(b.getTime()+6048E5);b="; expires="+b.toGMTString();document.cookie=a+"="+c+b+"; path=/; "};
				this.check=function(){var a=this.get(f);if(a)a=a.split(":");else if(100!=e)"v"==h&&(e=Math.random()>=e/100?0:100),a=[h,e,0],this.set(f,a.join(":"));else return!0;var c=a[1];if(100==c)return!0;switch(a[0]){case "v":return!1;case "r":return c=a[2]%Math.floor(100/c),a[2]++,this.set(f,a.join(":")),!c}return!0};
				this.go=function(){if(this.check()){var a=document.createElement("script");a.type="text/javascript";a.src=g;document.body&&document.body.appendChild(a)}};
				this.start=function(){var t=this;"complete"!==document.readyState?window.addEventListener?window.addEventListener("load",function(){t.go()},!1):window.attachEvent&&window.attachEvent("onload",function(){t.go()}):t.go()};};
				try{(new g(100,"r","QSI_S_ZN_71EvPslLzoT2zGe","https://zn71evpsllzot2zge-godaddy.siteintercept.qualtrics.com/SIE/?Q_ZID=ZN_71EvPslLzoT2zGe")).start()}catch(i){}})();
				</script><div id=\'ZN_71EvPslLzoT2zGe\'><!--DO NOT REMOVE-CONTENTS PLACED HERE--></div>
				<!--END WEBSITE FEEDBACK SNIPPET-->';


	}
}
