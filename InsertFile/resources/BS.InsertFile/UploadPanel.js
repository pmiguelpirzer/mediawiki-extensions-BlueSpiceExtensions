Ext.define( 'BS.InsertFile.UploadPanel', {
	extend: 'Ext.form.Panel',
	require:[
		'BS.form.action.MediaWikiApiAction'
	],
	fieldDefaults: {
		anchor: '100%',
		labelWidth: 70,
		labelAlign: 'right',
		msgTarget: 'under'
	},
	fileUpload: true,
	layout: {
		type: 'vbox',
		align: 'stretch'  // Child items are stretched to full width
	},
	bodyPadding: 5,
	
	//Custom settings
	allowedFileExtensions: mw.config.get( 'wgFileExtensions' ),

	initComponent: function() {

		//HINT: https://www.mediawiki.org/wiki/API:Upload#Uploading
		this.fuFile = Ext.create('Ext.form.field.File', {
			fieldLabel: mw.message('bs-insertfile-uploadFileFieldLabel').plain(),
			buttonText: mw.message('bs-insertfile-uploadButtonText').plain(),
			id: this.getId()+'-file',
			name: 'file',
			emptyText: mw.message('bs-insertfile-uploadFileEmptyText').plain(),
			validator: this.validateFile,
			validateOnChange: true
		});
		this.fuFile.on( 'change', this.fuFileChange, this );
		
		this.tfFileName = Ext.create('Ext.form.TextField', {
			fieldLabel: mw.message('bs-insertfile-uploadDestFileLabel').plain(),
			id: this.getId()+'-filename',
			name: 'filename'
		});
		this.tfFileName.on( 'change', this.tfFileNameChange, this );
		
		this.taDescription = Ext.create('Ext.form.field.TextArea', {
			fieldLabel: mw.message('bs-insertfile-uploadDescFileLabel').plain(),
			id: this.getId()+'-text',
			value: mw.message('bs-insertfile-upload-default-description').plain(),
			name: 'text'
		});
		
		this.storeLicenses = Ext.create( 'Ext.data.Store', {
			proxy: {
				type: 'ajax',
				url: bs.util.getAjaxDispatcherUrl('InsertFileAJAXBackend::getLicenses'),
				reader: {
					type: 'json',
					root: 'items',
					idProperty: 'value'
				}
			},
			extraParams: {
				type: this.storeFileType
			},
			remoteSort: true,
			fields: ['text', 'value', 'indent']
		});
		
		this.cbLicences = Ext.create('Ext.form.ComboBox',{
			fieldLabel: mw.message('bs-insertfile-license').plain(),
			//autoSelect: true,
			//forceSelection: true,
			//typeAhead: true,
			//triggerAction: 'all',
			//lazyRender: true,
			mode: 'local',
			store: this.storeLicenses,
			valueField: 'value',
			displayField: 'text',
			tpl: new Ext.XTemplate(
				'<tpl for=".">',
				  '<tpl if="this.hasValue(value) == false">',
				    '<div class="x-combo-list-item no-value">{text}</div>',
				  '</tpl>',
				  '<tpl if="this.hasValue(value)">',
				    '<div class="x-combo-list-item indent-{indent}">{text}</div>',
				  '</tpl>',
				'</tpl>',
				{
					compiled: true,
					disableFormats: true,
					// member functions:
					hasValue: function(value) {
						return value != '';
					}
				}
			)
		});
		
		this.cbxWatch = Ext.create('Ext.form.field.Checkbox', {
			boxLabel: mw.message('bs-insertfile-uploadWatchThisLabel').plain(),
			id: this.getId()+'watch_page',
			name: 'watch'
		});
		
		this.cbxWarnings = Ext.create('Ext.form.field.Checkbox', {
			boxLabel: mw.message('bs-insertfile-uploadIgnoreWarningsLabel').plain(),
			id: this.getId()+'ignorewarnings',
			name: 'ignorewarnings'
		});
		
		this.bsCategories = Ext.create( 'BS.form.CategoryBoxSelect', {
			fieldLabel: mw.message('bs-insertfile-categories').plain()
		});
		
		this.fsDetails = Ext.create( 'Ext.form.FieldSet', {
			title: 'Details',
			collapsed: true,
			collapsible: true,
			anchor: '98%',
			defaults: {
				anchor: '100%',
				labelWidth: 70,
				labelAlign: 'right'
			}
		});
		
		var panelItems = [
			this.tfFileName,
			this.fuFile,
			this.fsDetails
		];
		var detailsItems = [
			//this.bsCategories,
			this.taDescription,
			this.cbLicences,
			this.cbxWarnings,
			this.cbxWatch
		];
		
		$(document).trigger( 'BSUploadPanelInitComponent', [ this, panelItems, detailsItems ] );
		
		this.fsDetails.add( detailsItems );
		this.items = panelItems;

		this.addEvents( 'uploadcomplete' );

		this.callParent(arguments);
	},
	
	fuFileChange:  function(field, value, eOpts) {
		//Remove path info
		value = value.replace(/^.*?([^\\\/:]*?\.[a-z0-9]+)$/img, "$1");
		value = value.replace(/\s/g, "_");
		if( mw.config.get('bsIsWindows') ) {
			value = value.replace(/[^\u0000-\u007F]/gmi, ''); //Replace Non-ASCII
		}

		this.tfFileName.setValue(value);
		this.tfFileName.fireEvent('change', this.tfFileName, value);
	},
	
	tfFileNameChange: function(field, value) {
		Ext.Ajax.request({
			url: bs.util.getAjaxDispatcherUrl( 'SpecialUpload::ajaxGetExistsWarning', [ value ] ),
			success: function(response, options) {
				if(!(response.responseText.trim() == ''
					|| response.responseText == '&#160;'
					|| response.responseText == '&nbsp;')) {

					bs.util.alert( 
						this.getId()+'-existswarning',
						{
							title: 'Status',
							text: response.responseText
						}
					);
				}
			},
			scope: this
		});
	},
	
	checkFileSize: function( ExtCmpId ) {
		//No FileAPI? No love.
		if(typeof window.FileReader == 'undefined') return true;
		
		var allowedSize = mw.config.get('bsMaxUploadSize');
		if( allowedSize == null ) return true;
		
		var filesize = this.fuFile.fileInputEl.dom.files[0].size;
		if( filesize > allowedSize.php || filesize > allowedSize.mediawiki) {
			return false;
		}
		return true;
	},
	
	uploadFile: function( sessionKeyForReupload ) {
		var desc = this.taDescription.getValue();
		desc += this.cbLicences.getValue();
		this.taDescription.setValue( desc );

		this.cbLicences.disable(); //To prevent the form from submitting a generated name
		
		var params = {
			action: 'upload',
			token: mw.user.tokens.get('editToken'),
			format: 'json'
		}
		
		if( sessionKeyForReupload ) {
			params.sessionkey = sessionKeyForReupload
		}

		this.getForm().doAction( Ext.create('BS.form.action.MediaWikiApiCall', {
			form: this.getForm(), //Required
			url: mw.util.wikiScript('api'),
			params: params,
			success: this.onUploadSuccess,
			failure: this.onUploadFailure,
			scope: this
		}));
		
		//We mask only the FormPanel, because masking the whole document using
		// "waitMsg" param on MediaWikiApiCall does no automatic unmasking.
		//This is because MediaWikiApiCall overrides the onSuccess/onFailure
		//methods of action "Submit"
		this.getEl().mask(
			mw.message('bs-insertfile-upload-waitMessage').plain(),
			Ext.baseCSSPrefix + 'mask-loading'
		);
	},
	
	onUploadSuccess: function( response, action ) {
		this.getEl().unmask();
		this.cbLicences.enable();
		
		var res = Ext.decode(response.responseText);
		if( res.error ) {
			bs.util.alert(
				this.getId()+'-error',
				{
					title: mw.message('bs-insertfile-error').plain(),
					text: res.error.info
				}
			);
			return;
		}

		this.fireEvent( 'uploadcomplete', this, res.upload );
		this.getForm().reset();
	},
	
	onUploadFailure: function( response, action ) {
		//This would only happen when a server error occured but not when the 
		//MediaWiki API returns an JSON encoded error
		this.getForm().reset();
		this.cbLicences.enable();
	},

	//scope: "this" == fuFile
	validateFile: function( value ) {
		if( value == "" ) return true;
		var me = this.up('form');

		var nameParts = value.split('.');
		var fileExtension = nameParts[nameParts.length-1].toLowerCase();
		
		if( $.inArray( fileExtension, me.allowedFileExtensions ) == -1 ) {
			return mw.message('bs-insertfile-allowedFiletypesAre').plain()
				+ " " + me.allowedFileExtensions.join(', ');
		}
		
		if( me.checkFileSize() == false ) {
			return mw.message( 'largefileserver' ).plain();
		}
		
		return true;
	}
});