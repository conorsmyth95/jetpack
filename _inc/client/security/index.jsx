/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { get } from 'lodash';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import { getModule } from 'state/modules';
import { getSettings } from 'state/settings';
import { isDevMode, isUnavailableInDevMode } from 'state/connection';
import { isModuleFound } from 'state/search';
import { isPluginActive, isPluginInstalled } from 'state/site/plugins';
import QuerySite from 'components/data/query-site';
import QueryAkismetKeyCheck from 'components/data/query-akismet-key-check';
import BackupsScan from './backups-scan';
import Antispam from './antispam';
import { ManagePlugins } from './manage-plugins';
import { Monitor } from './monitor';
import { Private } from './private';
import { Protect } from './protect';
import { SSO } from './sso';

export class Security extends Component {
	static displayName = 'SecuritySettings';

	/**
	 * Check if Akismet plugin is being searched and matched.
	 *
	 * @returns {boolean} False if the plugin is inactive or if the search doesn't match it. True otherwise.
	 */
	isAkismetFound = () => {
		if ( ! this.props.isPluginActive( 'akismet/akismet.php' ) ) {
			return false;
		}

		if ( this.props.searchTerm ) {
			const akismetData = this.props.isPluginInstalled( 'akismet/akismet.php' );
			return (
				[
					'akismet',
					'antispam',
					'spam',
					'comments',
					akismetData.Description,
					akismetData.PluginURI,
				]
					.join( ' ' )
					.toLowerCase()
					.indexOf( this.props.searchTerm.toLowerCase() ) > -1
			);
		}

		return true;
	};

	render() {
		const commonProps = {
			settings: this.props.settings,
			getModule: this.props.module,
			isDevMode: this.props.isDevMode,
			isUnavailableInDevMode: this.props.isUnavailableInDevMode,
			rewindStatus: this.props.rewindStatus,
			siteRawUrl: this.props.siteRawUrl,
		};

		const foundProtect = this.props.isModuleFound( 'protect' ),
			foundSso = this.props.isModuleFound( 'sso' ),
			foundAkismet = this.isAkismetFound(),
			rewindActive = 'active' === get( this.props.rewindStatus, [ 'state' ], false ),
			foundBackups = this.props.isModuleFound( 'vaultpress' ) || rewindActive,
			foundMonitor = this.props.isModuleFound( 'monitor' ),
			foundPrivateSites = this.props.isModuleFound( 'private' );

		if ( ! this.props.searchTerm && ! this.props.active ) {
			return null;
		}

		if ( ! foundSso && ! foundProtect && ! foundAkismet && ! foundBackups && ! foundMonitor && ! foundPrivateSites ) {
			return null;
		}

		return (
			<div>
				<QuerySite />
				<Card
					title={
						this.props.searchTerm
							? __( 'Security' )
							: __(
									'Keep your site safe with state-of-the-art security and receive notifications of technical problems.'
							  )
					}
					className="jp-settings-description"
				/>
				{ foundBackups && <BackupsScan { ...commonProps } /> }
				{ foundMonitor && <Monitor { ...commonProps } /> }
				{ foundAkismet && (
					<div>
						<Antispam { ...commonProps } />
						<QueryAkismetKeyCheck />
					</div>
				) }
				<ManagePlugins { ...commonProps } />
				{ foundProtect && <Protect { ...commonProps } /> }
				{ foundSso && <SSO { ...commonProps } /> }
				{ foundPrivateSites && <Private { ...commonProps } /> }
			</div>
		);
	}
}

export default connect( state => {
	return {
		module: module_name => getModule( state, module_name ),
		settings: getSettings( state ),
		isDevMode: isDevMode( state ),
		isUnavailableInDevMode: module_name => isUnavailableInDevMode( state, module_name ),
		isModuleFound: module_name => isModuleFound( state, module_name ),
		isPluginActive: plugin_slug => isPluginActive( state, plugin_slug ),
		isPluginInstalled: plugin_slug => isPluginInstalled( state, plugin_slug ),
	};
} )( Security );
