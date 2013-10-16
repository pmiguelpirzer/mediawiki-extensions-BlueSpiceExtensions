/**
 * PagesVisited Panel
 *
 * Part of BlueSpice for MediaWiki
 *
 * @author     Stephan Muggli <muggli@hallowelt.biz>
 * @package    Bluespice_Extensions
 * @subpackage PagesVisited
 * @copyright  Copyright (C) 2013 Hallo Welt! - Medienwerkstatt GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 * @filesource
 */

Ext.define( 'BS.PagesVisited.Panel', {
	extend: 'Ext.grid.Panel',

	initComponent: function() {
		this.store = Ext.create( 'Ext.data.JsonStore', {
			proxy: {
				type: 'ajax',
				url: bs.util.getAjaxDispatcherUrl( 'PagesVisited::getData', [ bsPagesVisitedUserID ] ),
				reader: {
					type: 'json',
					root: 'page',
					idProperty: 'pv_page',
					totalProperty: 'totalCount'
				}
			},
			autoLoad: true,
			remoteSort: true,
			fields: [ 'pv_page', 'pv_page_title', 'pv_ts' ],
			sortInfo: {
				field: 'id',
				direction: 'ASC'
			}
		});

		this.colPage = Ext.create( 'Ext.grid.column.Template', {
			id: 'pvpage',
			header: mw.message( 'bs-readers-headerUsername' ).plain(),
			sortable: true,
			dataIndex: 'pv_page',
			tpl: '<a href="{pv_page}">{pv_page_title}</a>',
			filterable: true,
			flex: 1
		} );
		this.colTs = Ext.create( 'Ext.grid.column.Template', {
			id: 'pvts',
			header: mw.message( 'bs-readers-headerTs' ).plain(),
			sortable: true,
			dataIndex: 'pv_ts',
			tpl: '{pv_ts}',
			flex: 1
		} );

		this.columns = [
			this.colPage,
			this.colTs
		];
		this.callParent( arguments );
	}
} );