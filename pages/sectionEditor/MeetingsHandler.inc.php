<?php

/**
* class MeetingsHandler for SectionEditor and Editor Roles (STO)
* page handler class for minutes-related operations
* @var unknown_type
*/
define('SECTION_EDITOR_ACCESS_EDIT', 0x00001);
define('SECTION_EDITOR_ACCESS_REVIEW', 0x00002);
// Filter section
define('FILTER_SECTION_ALL', 0);

import('classes.submission.sectionEditor.SectionEditorAction');
import('classes.handler.Handler');
import('lib.pkp.classes.who.Meeting');
import('lib.pkp.classes.who.MeetingAction');

class MeetingsHandler extends Handler {
<<<<<<< HEAD
=======
/**
* Constructor
**/
	var $submissions;
	
function MeetingsHandler() {
	parent::Handler();
	
	$this->addCheck(new HandlerValidatorJournal($this));
	// FIXME This is kind of evil
	$page = Request::getRequestedPage();
	if ( $page == 'sectionEditor' )
	$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SECTION_EDITOR)));
	elseif ( $page == 'editor' )
	$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_EDITOR)));

}

/**
* Setup common template variables.
* @param $subclass boolean set to true if caller is below this handler in the hierarchy
*/
function setupTemplate($subclass = false, $meetingId = 0, $parentPage = null, $showSidebar = true) {
	parent::setupTemplate();
	Locale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_MANAGER));
	$templateMgr =& TemplateManager::getManager();
	$isEditor = Validation::isEditor();
	
	if (Request::getRequestedPage() == 'editor') {
		$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
	
	} else {
		$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole');
	}
	
	$roleSymbolic = $isEditor ? 'editor' : 'sectionEditor';
	$roleKey = $isEditor ? 'user.role.editor' : 'user.role.sectionEditor';
	$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey), array(Request::url(null, $roleSymbolic, 'meetings'), 'editor.meetings'))
	: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey));
	
	if($meetingId!=0)
		$pageHierarchy[] = array(Request::url(null, 'sectionEditor', 'setMeeting', $meetingId), "#$meetingId", true);
	
	$templateMgr->assign('pageHierarchy', $pageHierarchy);
}


/**
* Display submission management instructions.
* @param $args (type)
*/
function instructions($args) {
	$this->setupTemplate();
	import('classes.submission.proofreader.ProofreaderAction');
	if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof', 'referenceLinking'))) {
		Request::redirect(null, null, 'index');
	}
}

function meetings($args) {
	$this->validate();
	$this->setupTemplate(false);
	$journal =& Request::getJournal();
	$journalId = $journal->getId();
	$user =& Request::getUser();
	$userId = $user->getId();
	
	$meetingDao = DAORegistry::getDAO('MeetingDAO');
	$meetingSubmissionDao = DAORegistry::getDAO('MeetingSubmissionDAO');
	$articleDao = DAORegistry::getDAO('ArticleDAO');
	
	$sort = Request::getUserVar('sort');
	$sort = isset($sort) ? $sort : 'id';
	$sortDirection = Request::getUserVar('sortDirection');
		
	$meetings =& $meetingDao->getMeetingsOfUser($userId, $sort, $sortDirection);
	
	$map = array();
		
	foreach($meetings as $meeting) {
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meeting->getId());
		$submissions = array();
		foreach($submissionIds as $submissionId) {
			$submission = $articleDao->getArticle($submissionId, $journalId, false);
			array_push($submissions, $submission);
		}
		$map[$meeting->getId()] = $submissions;
	}
	
	$templateMgr =& TemplateManager::getManager();
	$templateMgr->assign_by_ref('meetings', $meetings);
	$templateMgr->assign_by_ref('submissions', $submissions); 
	$templateMgr->assign_by_ref('map', $map); 
	$templateMgr->assign('sort', $sort);
	$templateMgr->assign('sortDirection', $sortDirection);
	$templateMgr->assign('pageToDisplay', $page);
	$templateMgr->assign('sectionEditor', $user->getFullName());
	$templateMgr->display('sectionEditor/meetings/meetings.tpl');
}


/**
* Added by MSB
* Display the setMeeting page
* @param $args (type)
*/

function setMeeting($args) {
	$this->validate();
	$meetingId = isset($args[0]) ? $args[0]: 0;
	$this->setupTemplate(true, $meetingId);
	$journal =& Request::getJournal();
	$journalId = $journal->getId();
	$user =& Request::getUser();
	
	$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
	$sectionDao =& DAORegistry::getDAO('SectionDAO');
	
	$sections =& $sectionDao->getSectionTitles($journalId);

	$submissions =& $editorSubmissionDao->getEditorSubmissionsForERCReview(
		$journalId,
		FILTER_SECTION_ALL,
		$user->getId()
	);
	
	$this->submissions =& $submissions;
	
	$meetingId = isset($args[0]) ? $args[0]: 0;
	/*LIST THE SUBMISSIONS*/
	$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
	$selectedProposals =$meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
	
	/*MEETING DETAILS*/
	$meetingDao =& DAORegistry::getDAO('MeetingDAO');
	$meeting =$meetingDao->getMeetingById($meetingId);
			
	/*RESPONSES FROM REVIEWERS*/
	$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
	$reviewers = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);

	
	$templateMgr =& TemplateManager::getManager();
	$templateMgr->assign('helpTopicId', $helpTopicId);
	$templateMgr->assign('sectionOptions', $filterSectionOptions);
	$templateMgr->assign_by_ref('submissions', $submissions);
	$templateMgr->assign('filterSection', $filterSection);
	$templateMgr->assign('pageToDisplay', $page);
	$templateMgr->assign('sectionEditor', $user->getFullName());
	$templateMgr->assign_by_ref('selectedProposals', $selectedProposals);
	$templateMgr->assign_by_ref('meeting', $meeting);
	$templateMgr->assign_by_ref('reviewers', $reviewers);

	// Set search parameters
	$duplicateParameters = array(
		'searchField', 'searchMatch', 'search',
		'dateFromMonth', 'dateFromDay', 'dateFromYear',
		'dateToMonth', 'dateToDay', 'dateToYear',
		'dateSearchField'
		);
	foreach ($duplicateParameters as $param)
		$templateMgr->assign($param, Request::getUserVar($param));
	
	$templateMgr->assign('dateFrom', $fromDate);
	$templateMgr->assign('dateTo', $toDate);
	$templateMgr->assign('fieldOptions', Array(
		SUBMISSION_FIELD_TITLE => 'article.title',
		SUBMISSION_FIELD_AUTHOR => 'user.role.author',
		SUBMISSION_FIELD_EDITOR => 'user.role.editor'
		));
	$templateMgr->assign('dateFieldOptions', Array(
		SUBMISSION_FIELD_DATE_SUBMITTED => 'submissions.submitted',
		SUBMISSION_FIELD_DATE_COPYEDIT_COMPLETE => 'submissions.copyeditComplete',
		SUBMISSION_FIELD_DATE_LAYOUT_COMPLETE => 'submissions.layoutComplete',
		SUBMISSION_FIELD_DATE_PROOFREADING_COMPLETE => 'submissions.proofreadingComplete'
		));
	
	import('classes.issue.IssueAction');
	$issueAction = new IssueAction();
	$templateMgr->register_function('print_issue_id', array($issueAction, 'smartyPrintIssueId'));
	$templateMgr->assign('sort', $sort);
	$templateMgr->assign('sortDirection', $sortDirection);
	$templateMgr->assign('baseUrl', Config::getVar('general', "base_url"));
	$templateMgr->display('sectionEditor/meetings/setMeeting.tpl');
	}
	
>>>>>>> refs/remotes/gay/master
	/**
	* Constructor
	**/
	var $meeting;
		
	function MeetingsHandler() {
		parent::Handler();
		
		$this->addCheck(new HandlerValidatorJournal($this));
		// FIXME This is kind of evil
		$page = Request::getRequestedPage();
		if ( $page == 'sectionEditor' )
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SECTION_EDITOR)));
		elseif ( $page == 'editor' )
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_EDITOR)));
	
	}

	/**
	* Setup common template variables.
	* @param $subclass boolean set to true if caller is below this handler in the hierarchy
	*/
	function setupTemplate($subclass = false, $meetingId = 0, $parentPage = null, $showSidebar = true) {
		parent::setupTemplate();
		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_OJS_EDITOR, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OJS_AUTHOR, LOCALE_COMPONENT_OJS_MANAGER));
		$templateMgr =& TemplateManager::getManager();
		$isEditor = Validation::isEditor();
		
		if (Request::getRequestedPage() == 'editor') {
			$templateMgr->assign('helpTopicId', 'editorial.editorsRole');
		
		} else {
			$templateMgr->assign('helpTopicId', 'editorial.sectionEditorsRole');
		}
		
		$roleSymbolic = $isEditor ? 'editor' : 'sectionEditor';
		$roleKey = $isEditor ? 'user.role.editor' : 'user.role.sectionEditor';
		$pageHierarchy = $subclass ? array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey), array(Request::url(null, $roleSymbolic, 'meetings'), 'editor.meetings'))
		: array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, $roleSymbolic), $roleKey));
		
		if($meetingId!=0)
			$pageHierarchy[] = array(Request::url(null, 'sectionEditor', 'setMeeting', $meetingId), "#$meetingId", true);
		
		$templateMgr->assign('pageHierarchy', $pageHierarchy);
	}


	/**
	* Display submission management instructions.
	* @param $args (type)
	*/
	function instructions($args) {
		$this->setupTemplate();
		import('classes.submission.proofreader.ProofreaderAction');
		if (!isset($args[0]) || !ProofreaderAction::instructions($args[0], array('copy', 'proof', 'referenceLinking'))) {
			Request::redirect(null, null, 'index');
		}
	}

	function meetings($args) {
		$this->validate(0, true);
		$this->setupTemplate(false);
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$user =& Request::getUser();
		$userId = $user->getId();
		
		$meetingDao = DAORegistry::getDAO('MeetingDAO');
		$meetingSubmissionDao = DAORegistry::getDAO('MeetingSubmissionDAO');
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		
		$sort = Request::getUserVar('sort');
		$sort = isset($sort) ? $sort : 'id';
		$sortDirection = Request::getUserVar('sortDirection');
			
		$meetings =& $meetingDao->getMeetingsOfUser($userId, $sort, $sortDirection);
		
		$map = array();
			
		foreach($meetings as $meeting) {
			$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meeting->getId());
			$submissions = array();
			foreach($submissionIds as $submissionId) {
				$submission = $articleDao->getArticle($submissionId, $journalId, false);
				array_push($submissions, $submission);
			}
			$map[$meeting->getId()] = $submissions;
		}
		
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('meetings', $meetings);
		$templateMgr->assign_by_ref('submissions', $submissions); 
		$templateMgr->assign_by_ref('map', $map); 
		$templateMgr->assign('sort', $sort);
		$templateMgr->assign('sortDirection', $sortDirection);
		$templateMgr->assign('pageToDisplay', $page);
		$templateMgr->assign('sectionEditor', $user->getFullName());
		$templateMgr->display('sectionEditor/meetings/meetings.tpl');
	}


	/**
	* Added by MSB
	* Display the setMeeting page
	* @param $args (type)
	*/
	
	function setMeeting($args) {
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId, false, true);
		$this->setupTemplate(true, $meetingId);
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$user =& Request::getUser();
		
		$editorSubmissionDao =& DAORegistry::getDAO('EditorSubmissionDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		
		$editor = $user->getId();
		
		$submissions =& $editorSubmissionDao->getEditorSubmissionsForERCReview(
			$journalId,
			$filterSection,
			$editorId
		);
	
		$this->submissions =& $submissions;
		
		/*LIST THE SUBMISSIONS*/
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$selectedProposals =$meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		
		/*MEETING DETAILS*/
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		/*RESPONSES FROM REVIEWERS*/
		$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
		$reviewers = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
	
		
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('meeting', $meeting);
		$templateMgr->assign_by_ref('reviewers', $reviewers);
		$templateMgr->assign_by_ref('submissions', $submissions);
		$templateMgr->assign_by_ref('selectedProposals', $selectedProposals);
	
		$templateMgr->assign('baseUrl', Config::getVar('general', "base_url"));
		$templateMgr->display('sectionEditor/meetings/setMeeting.tpl');
	}
	
	/**
	* Added by MSB 07/06/11
	* Store meeting details such as proposals to discuss and meeting date
	* @ param $args (type)
	*/
	
	function saveMeeting($args){
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId, false, true);
		$selectedSubmissions = Request::getUserVar('selectedProposals');
		$meetingDate = Request::getUserVar('meetingDate');
		$meetingId = MeetingAction::saveMeeting($meetingId,$selectedSubmissions,$meetingDate, null);
		Request::redirect(null, null, 'viewMeeting', array($meetingId));
	}
		

	/**
	 * Added by MSB July 07 2011
	 * Set the meeting final
	 * @param $args (type)
	 */	
	function setMeetingFinal($args){
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId);
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meetingDao->setMeetingFinal($meetingId);
		Request::redirect(null, null, 'notifyReviewersFinalMeeting', $meetingId);
	}

	function viewMeeting($args){
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId);
		$this->setupTemplate(true, $meetingId);
		$journal =& Request::getJournal();
		$journalId = $journal->getId();
		$user =& Request::getUser();
		
		/*MEETING DETAILS*/
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		if(isset($meeting) && $meeting->getUploader()==$user->getId()){
		
			/*LIST THE SUBMISSIONS*/
			$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
			$selectedProposals =$meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
			$articleDao =& DAORegistry::getDAO('ArticleDAO');
			$submissions = array();
			foreach($selectedProposals as $submission) {
				$submissions[$submission] = $articleDao->getArticle($submission, $journalId, false);
			}
			
			
			/*RESPONSES FROM REVIEWERS*/
			$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
			$reviewers = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
		
			
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign('sectionEditor', $user->getFullName());
			$templateMgr->assign_by_ref('meeting', $meeting);
			$templateMgr->assign_by_ref('reviewers', $reviewers);
			$templateMgr->assign_by_ref('submissions', $submissions);
			$templateMgr->assign('baseUrl', Config::getVar('general', "base_url"));
			$templateMgr->display('sectionEditor/meetings/viewMeeting.tpl');
		
		}else{
			Request::redirect(null, null, 'meetings', null);
		}

	}
	
	function cancelMeeting($args){
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate(meetingId);
		if(MeetingAction::cancelMeeting($meetingId, null)){
			Request::redirect(null, null, 'meetings', null);
		}
		
	}
	
	/** 
	 * Notify reviewers if new meeting is set
	 * Added by ayveemallare 7/12/2011
	 * Enter description here ...
	 * @param int $meetingId
	 */
	function notifyReviewersNewMeeting($args) {
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId);
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
		$reviewerIds = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
	
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		$this->setupTemplate(true, $meetingId);
		if (SectionEditorAction::notifyReviewersNewMeeting($meeting, $reviewerIds, $submissionIds, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'viewMeeting', $meetingId);
		}
	}
	
	/**
	 * Notify reviewers if meeting is rescheduled
	 * Added by ayveemallare 7/12/2011
	 * @param int $meetingId, datetime $oldDate
	 */
	function notifyReviewersChangeMeeting($args) {
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId);
		$oldDate = isset($args[1]) ? $args[1]: 0;
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
		$reviewerIds = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
	
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		$this->setupTemplate(true, $meetingId);
		if (SectionEditorAction::notifyReviewersChangeMeeting($oldDate, $meeting, $reviewerIds, $submissionIds, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'viewMeeting', $meetingId);
		}
	}
	
	/**
	 * Notify reviewers if meeting schedule is made final.
	 * Added by ayveemallare 7/12/2011
	 * @param int $meetingId
	 */
	function notifyReviewersFinalMeeting($args) {
		$meetingId = isset($args[0]) ? $args[0]: 0;
		$this->validate($meetingId);
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
		$reviewerIds = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
	
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		$this->setupTemplate(true, $meetingId);
		if (SectionEditorAction::notifyReviewersFinalMeeting($meeting, $reviewerIds, $submissionIds, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'viewMeeting', $meetingId);
		}
	}
	
	/**
	 * Notify reviewers if meeting is cancelled
	 * Added by ayveemallare 7/12/2011
	 * @param int $meetingId
	 */
	function notifyReviewersCancelMeeting($args) {
		$this->validate($meetingId);
		$meetingId = isset($args[0]) ? $args[0]: 0;
		//$this->validate($meetinGId, SECTION)
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		$meetingReviewerDao =& DAORegistry::getDAO('MeetingReviewerDAO');	
		$reviewerIds = $meetingReviewerDao->getMeetingReviewersByMeetingId($meetingId);
	
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		$this->setupTemplate(true, $meetingId);
		if (SectionEditorAction::notifyReviewersCancelMeeting($meeting, $reviewerIds, $submissionIds, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'cancelMeeting', $meetingId);
		}
	}
	
	/**
	 * Remind reviewers of schedule meeting
	 * Added by ayveemallare 7/12/2011
	 * @param $args
	 */
	function remindReviewersMeeting($args = null) {
		$meetingId = Request::getUserVar('meetingId');
		$reviewerId = Request::getUserVar('reviewerId');
		$this->validate($meetingId);
		$meetingDao =& DAORegistry::getDAO('MeetingDAO');
		$meeting =$meetingDao->getMeetingById($meetingId);
		
		$meetingSubmissionDao =& DAORegistry::getDAO('MeetingSubmissionDAO');
		$submissionIds = $meetingSubmissionDao->getMeetingSubmissionsByMeetingId($meetingId);
		$this->setupTemplate(true, $meetingId);
		if (SectionEditorAction::remindReviewersMeeting($meeting, $reviewerId, $submissionIds, Request::getUserVar('send'))) {
			Request::redirect(null, null, 'viewMeeting', $meetingId);
		}
	}
	
	function validate($meetingId = 0, $isList = false, $isSetMeeting = false) {
		parent::validate();
		$isValid = true;
		$user = Request::getUser();
		if($meetingId != 0) {
			$meetingDao =& DAORegistry::getDAO('MeetingDAO');
			$meeting = $meetingDao->getMeetingById($meetingId);
			
			if($meeting == null)
				$isValid = false;
			else if($meeting->getUploader() != $user->getId())
				$isValid = false;
			if($isValid)
				$this->meeting =& $meeting;
		} else {
			if(!$isList && !$isSetMeeting)
				$isValid = false;	
		}
		
		if(!$isValid) 
			Request::redirect(null, Request::getRequestedPage());
		
		return true;
	}


}

?>
