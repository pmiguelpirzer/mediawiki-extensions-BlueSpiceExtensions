<?php
/**
 * Provides the review tasks api for BlueSpice.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * This file is part of BlueSpice for MediaWiki
 * For further information visit http://www.blue-spice.org
 *
 * @author     Patric Wirth <wirth@hallowelt.com>
 * @package    Bluespice_Extensions
 * @copyright  Copyright (C) 2016 Hallo Welt! GmbH, All rights reserved.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License v2 or later
 */

/**
 * Review Api class
 * @package BlueSpice_Extensions
 */
class BSApiReviewTasks extends BSApiTasksBase {

	/**
	 * Methods that can be called by task param
	 * @var array
	 */
	protected $aTasks = array(
		'editReview' => [
			'examples' => [
				[
					'pid' => 12,
					'editable' => true,
					'sequential' => false,
					'abortable' => true,
					'startdate' => '20170308032520',
					'enddate' => '20170318032520',
					'steps' => [
						[
							'userid' => 1,
							'status' => ''
						],
						[
							'userid' => 3,
							'status' => ''
						]
					]
				],
				[
					'pid' => 12,
					'editable' => true,
					'sequential' => false,
					'abortable' => true,
					'startdate' => '20170308032520',
					'enddate' => '20170318032520',
					'steps' => [
						[
							'userid' => 1,
							'status' => ''
						],
						[
							'userid' => 3,
							'status' => ''
						]
					],
					'tmpl_save' => true,
					'tmpl_name' => 'My template'
				]
			],
			'params' => [
				'pid' => [
					'desc' => 'Valid page id',
					'type' => 'integer',
					'required' => true
				],
				'editable' => [
					'desc' => 'Flag that indicates whether the review process can be edited',
					'type' => 'boolean',
					'required' => true
				],
				'sequential' => [
					'desc' => 'Flag that indicates whether the steps of the review process should be processed one by one or in parallel',
					'type' => 'boolean',
					'required' => true
				],
				'abortable' => [
					'desc' => 'Flag that indicates whether the review process can be aborted',
					'type' => 'boolean',
					'required' => true
				],
				'startdate' => [
					'desc' => 'Start date for review in format YmdHis',
					'type' => 'string',
					'required' => true
				],
				'enddate' => [
					'desc' => 'End date for review in format YmdHis',
					'type' => 'string',
					'required' => true
				],
				'steps' => [
					'desc' => 'An array ob objects that describe each step of the review process',
					'type' => 'array',
					'required' => true
				],
				'tmpl_save' => [
					'desc' => 'Save to template',
					'type' => 'boolean',
					'required' => false
				],
				'tmpl_name' => [
					'desc' => 'Template name',
					'type' => 'string',
					'required' => false,
					'default' => ''
				],
				'tmpl_choice' => [
					'desc' => 'The id of a review template',
					'type' => 'integer',
					'required' => false,
					'default' => -1
				],
			]
		],
		'deleteReview' => [
			'examples' => [
				[
					'pid' => 12
				]
			],
			'params' => [
				'pid' => [
					'desc' => 'Valid page id',
					'type' => 'integer',
					'required' => true
				]
			]
		],
		'vote' => [
			'examples' => [
				[
					'articleID' => 12,
					'vote' => 'yes',
					'comment' => 'Review comment'
				]
			],
			'params' => [
				'articleID' => [
					'desc' => 'Valid Page ID',
					'type' => 'integer',
					'required' => true
				],
				'vote' => [
					'desc' => '',
					'type' => 'string',
					'required' => true
				],
				'comment' => [
					'desc' => '',
					'type' => 'string',
					'required' => false,
					'default' => ''
				]
			]
		],
	);

	/**
	 * Returns an array of tasks and their required permissions
	 * array( 'taskname' => array('read', 'edit') )
	 * @return array
	 */
	protected function getRequiredTaskPermissions() {
		return array(
			'editReview' => array(
				'workflowedit',
			),
			'deleteReview' => array(
				'workflowedit',
			),
			'vote' => array(
				'workflowview',
			),
		);
	}

	protected function task_editReview( $oTaskData, $aParams ) {
		$oReturn = $this->makeStandardReturn();

		$iPageId = empty( $oTaskData->pid )
			? -1
			: (int) $oTaskData->pid
		;

		$oTitle = Title::newFromID( $iPageId );
		if( $oTitle instanceof Title === false ) {
			$oReturn->message = wfMessage(
				'bs-review-save-noid'
			)->plain();
			return $oReturn;
		}

		$oReviewProcess = BsReviewProcess::newFromPid(
			(int) $oTitle->getArticleID()
		);
		$oUser = $this->getUser();
		if( $oReviewProcess && !Review::userCanEdit($oReviewProcess, $oUser) ) {
			$oReturn->message = wfMessage(
				'bs-review-save-norights'
			)->plain();
			return $oReturn;
		}

		$oStatus = Review::doEditReview(
			$oTitle,
			$oTaskData,
			$this->getUser(),
			$oReviewProcess
		);
		if( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getHTML();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage( 'bs-review-save-success' )->plain();
		$this->runUpdates();

		return $oReturn;
	}

	protected function task_deleteReview( $oTaskData, $aParams ) {
		$oReturn = $this->makeStandardReturn();

		$iPageId = empty( $oTaskData->pid )
			? -1
			: (int) $oTaskData->pid
		;

		$oTitle = Title::newFromID( $iPageId );
		if( !$oTitle || $oTitle->isSpecialPage() || !$oTitle->exists() ) {
			$oReturn->message = wfMessage(
				'bs-review-save-noid'
			)->plain();
			return $oReturn;
		}

		$oReviewProcess = BsReviewProcess::newFromPid(
			(int) $oTitle->getArticleID()
		);
		$oUser = $this->getUser();
		if( $oReviewProcess && !Review::userCanEdit($oReviewProcess, $oUser) ) {
			$oReturn->message = wfMessage(
				'bs-review-save-norights'
			)->plain();
			return $oReturn;
		}

		$oStatus = Review::doDeleteReview(
			$oTitle,
			$this->getUser(),
			$oReviewProcess
		);
		if( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getHTML();
			return $oReturn;
		}

		$oReturn->success = true;
		$oReturn->message = wfMessage( 'bs-review-save-removed' )->plain();
		$this->runUpdates();

		return $oReturn;
	}

	protected function task_vote( $oTaskData, $aParams ) {
		$oReturn = $this->makeStandardReturn();

		//TODO: Use Context
		$iArticleId = empty( $oTaskData->articleID )
			? 0
			: (int)$oTaskData->articleID
		;
		$oTitle = Title::newFromID( $iArticleId );
		if( !$oTitle instanceof Title ) {
			$oReturn->message = wfMessage( 'bs-review-save-noid' )->plain();
		}
		$oStatus = Review::doVote(
			$oTitle,
			$oTaskData,
			$this->getUser()
		);
		if( !$oStatus->isOK() ) {
			$oReturn->message = $oStatus->getHTML();
			return $oReturn;
		}

		$oReturn->message = wfMessage( 'bs-review-review-saved' )->plain();
		$oReturn->success = true;
		$this->runUpdates();

		return $oReturn;
	}
}