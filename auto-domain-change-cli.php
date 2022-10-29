<?php

/**
 * Migrate a WordPress site from one domain to another
 */
class cli_auto_domain_change extends WP_CLI_Command {
	/**
	* Switch the domain name by updating all the WordPress tables
	* ## OPTIONS
	*
	* <old_domain>
	* : The domain name to be changed
	*
	* <new_domain>
	* : The new domain name to use
	*
	* [--change-https=<yes|no>]
	* : Also change secure https links
	* ---
	* default: 'yes'
	* options:
	*   - 'yes'
	*   - 'no'
	*
	* [--change-www=<yes|no>]
	* : Change both www.old-domain.com and old-domain.com
	* ---
	* default: 'yes'
	* options:
	*   - 'yes'
	*   - 'no'
	*
	* [--scheme=<scheme>]
	* : Scheme to used for the new domain
	* ---
	* default: https
	* options:
	*   - same
	*   - https
	*   - http
	*
	* ## EXAMPLES
	*
	*   wp auto-domain-change update --scheme https old-domain.com new-domain.com
	*/
	function update( $args, $assoc_args ) {
		$scheme = $assoc_args['scheme'];
		$change_https = $assoc_args['change-https'] === 'yes';
		$change_www = $assoc_args['change-www'] === 'yes';
		$old_domain = $args[0];
		$new_domain = $args[1];

		if ($scheme === "same") {
			$scheme = null;
		}

		update_option('auto_domain_change-https', $change_https);
		update_option('auto_domain_change-www', $change_www);

		$adc = new auto_domain_change();
		WP_CLI::log( sprintf( 'Changing domain from %s to %s', $old_domain, $new_domain ) );
		$adc->do_change($old_domain, $new_domain, $scheme);
		WP_CLI::success("OK");
	}
}
