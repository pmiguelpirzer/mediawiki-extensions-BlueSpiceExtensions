<?php
class ApiSidebar extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}
    
    public function execute() {
        //todo: context
        $params = $this->extractRequestParams();
        $apiResult = $this->getResult();
        $result = array();
        if ($params['sidebar'] == true){
            foreach ($this->getSkin()->buildSidebar() as $key => $aSidebar){
                if (count($aSidebar) < 1)
                    continue;
                $aTemp = array();
                foreach ($aSidebar as $aElement){
                    $aTemp[$aElement['id']] = $aElement;
                }
                $result['sidebar'][$key] = $aTemp;
            }
        }
        if ($params['usersidebar'] == true){
            global $wgUser;
            $aViews = array();            
            wfRunHooks( 'BSBlueSpiceSkinFocusSidebar', array( &$aViews, $wgUser, $this->getSkin() ) );
            foreach ( $aViews as $oView ) {
                if ( $oView !== null && $oView instanceof ViewBaseElement ) {
                    if (isset($params['format']) && $params['format'] == 'json')
                        $sContent = $oView->execute(array('format' => 'json'));
                    else
                        $sContent = $oView->execute();
                    $result['usersidebar'][$oView->getId()][] = $sContent;
                    $apiResult->setIndexedTagName($result['usersidebar'][$oView->getId()], 'content');
                } else {
                    wfDebugLog( 'BS::Skin', 'BlueSpiceTemplate::printViews: Invalid view.' );
                }
            }
        }
        $apiResult->addValue( null, $this->getModuleName(), $result );
    }

	public function getAllowedParams() {
		global $wgFeedClasses;
		$feedFormatNames = array_keys( $wgFeedClasses );
		return array (
			'sidebar' => array(
				ApiBase::PARAM_TYPE => 'boolean'
			),
			'usersidebar' => array(
				ApiBase::PARAM_TYPE => 'boolean'
			),
			'format' => array(
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return array(
			'action=sidebar&sidebar=1'
				=> 'apihelp-sidebar-example'
		);
	}

	public function getHelpUrls() {
		return 'http://help.bluespice.com/index.php/Navigationsleiste';
	}
}
