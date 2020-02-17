<?php
# Translation for Custom Status Code: open
switch( $g_active_language ) {

	default: # english
		//Status Open
		$s_status_enum_string = '10:new,11:accepted,12:partiallyaccepted,13:declined,14:pending,15:re-open,16:inforequest,17:infosubmitted,18:readyforprocess,19:offered,20:feedback,21:paid_approved,22:finalized,23:re-underwrite,24:withdrawn,25:issued_unpaid,26:begin_consultation,27:re_consult,28:more_time_request,30:acknowledged,40:confirmed,50:assigned,60:open,61:renew,62:next_payment,80:resolved,81:terminated,82:finish_consultation,90:closed';
		$s_open_bug_title = 'Mark issue Ready for open';
		$s_open_bug_button = 'Ready for open';
		$s_email_notification_title_for_status_bug_open = 'The following issue is ready for OPEN.';

		$s_accepted_bug_title = 'Mark issue accepted';
		$s_accepted_bug_button = 'Ready for accepted';
		$s_email_notification_title_for_status_bug_accepted = 'The following issue is ready for accepted.';

		$s_partiallyaccepted_bug_title = 'Mark issue partially accepted';
		$s_partiallyaccepted_bug_button = 'Ready for partially accepted';
		$s_email_notification_title_for_status_bug_partiallyaccepted = 'The following issue is ready for partially accepted.';

		$s_declined_bug_title = 'Mark issue declined';
		$s_declined_bug_button = 'Ready for declined';
		$s_email_notification_title_for_status_bug_declined = 'The following issue is ready for declined.';

		$s_pending_bug_title = 'Mark issue Ready for pending';
		$s_pending_bug_button = 'Ready for pending';
		$s_email_notification_title_for_status_bug_pending = 'The following issue is ready for pending.';

		$s_reopen_bug_title = 'Mark issue Ready for re-open';
		$s_reopen_bug_button = 'Ready for re-open';
		$s_email_notification_title_for_status_bug_reopen = 'The following issue is ready for re-open.';

		$s_inforequest_bug_title = 'Mark issue for Info Request';
		$s_inforequest_bug_button = 'Info Request';
		$s_email_notification_title_for_status_bug_inforequest = 'The following issue request Information.';

		$s_infosubmitted_bug_title = 'Mark issue as Info Submitted';
		$s_infosubmitted_bug_button = 'Info Submitted';
		$s_email_notification_title_for_status_bug_infosubmitted = 'The following issue has been submitted Information.';

		$s_readyforprocess_bug_title = 'Mark issue Ready for process';
		$s_readyforprocess_bug_button = 'Ready for process';
		$s_email_notification_title_for_status_bug_readyforprocess = 'The following issue is ready for process.';

		$s_offered_bug_title = 'Mark issue for Offered';
		$s_offered_bug_button = 'Offered';
		$s_email_notification_title_for_status_bug_offered = 'The following issue is offered.';

		$s_paid_approved_bug_title = 'Mark issue as Paid & Approved';
		$s_paid_approved_bug_button = 'Paid & Approved';
		$s_email_notification_title_for_status_bug_paid_approved = 'The following issue has been Paid & Approved.';

		$s_finalized_bug_title = 'Mark issue Finalized';
		$s_finalized_bug_button = 'Finalized';
		$s_email_notification_title_for_status_bug_finalized = 'The following issue is Finalized.';

		$s_withdraw_bug_title = 'Mark issue Withdraw';
		$s_withdraw_bug_button = 'Finalized';
		$s_email_notification_title_for_status_bug_finalized = 'The following issue is Finalized.';

		$s_reunderwrite_bug_title = 'Mark issue as Re-Underwrite';
		$s_reunderwrite_bug_button = 'Re-Underwrite';
		$s_email_notification_title_for_status_bug_reunderwrite = 'The following issue is set to be Re-Underwrite.';

		$s_withdrawn_bug_title = 'Mark issue as Withdrawn';
        $s_withdrawn_bug_button = 'Withdrawn';
        $s_email_notification_title_for_status_bug_withdrawn = 'The following issue is set to be Withdrawn.';

        $s_issued_unpaid_bug_title = 'Mark issue as Issued but Unpaid';
        $s_issued_unpaid_bug_button = 'Issued but Unpaid';
        $s_email_notification_title_for_status_bug_issued_unpaid = 'The following issue is set to be Issued but Unpaid.';

        $s_terminated_bug_title = 'Mark issue as Terminated';
        $s_terminated_bug_button = 'Terminated';
        $s_email_notification_title_for_status_bug_terminated = 'The following issue is set to be Terminated.';

        $s_renew_bug_title = 'Mark issue as Renew';
        $s_renew_bug_button = 'Renew';
        $s_email_notification_title_for_status_bug_renew = 'The following issue is set to be Renew.';

        $s_next_payment_bug_title = 'Mark issue as Next Payment';
        $s_next_payment_bug_button = 'Next Payment';
        $s_email_notification_title_for_status_bug_next_payment = 'The following issue is set to be Next Payment.';

        $s_begin_consultation_bug_title = 'Mark issue for Begin Consultation';
        $s_begin_consultation_bug_button = 'Begin Consultation';
        $s_email_notification_title_for_status_bug_begin_consultation = 'The following issue is Begin Consultation.';

        $s_more_time_request_bug_title = 'Mark issue for More Time Request';
        $s_more_time_request_bug_button = 'More Time Request';
        $s_email_notification_title_for_status_bug_more_time_request = 'The following issue is More Time Request.';

        $s_re_consult_bug_title = 'Mark issue for Re-Consult';
        $s_re_consult_bug_button = 'Re-Consult';
        $s_email_notification_title_for_status_bug_re_consult = 'The following issue is Re-Consult.';

        $s_finish_consultation_bug_title = 'Mark issue for Finish Consultation';
        $s_finish_consultation_bug_button = 'Finish Consultation';
        $s_email_notification_title_for_status_bug_finish_consultation = 'The following issue is Finish Consultation.';

        //break;
		//Resolution Accepted
		/*
		$s_resolution_enum_string = '10:open,11:accepted,12:partialy accepted,13:declined,20:fixed,30:reopened,40:unable to duplicate,50:not fixable,60:duplicate,70:not a bug,80:suspended,90:wont fix';
		$s_accepted_bug_title = 'Mark issue is accepted';
		$s_accepted_bug_button = 'Accepted';
		$s_email_notification_title_for_resolution_bug_accepted = 'The following issue is accepted.';

		$s_partialyaccepted_bug_title = 'Mark issue is partialy accepted';
		$s_partialyaccepted_bug_button = 'PartialyAccepted';
		$s_email_notification_title_for_resolution_bug_partialyaccepted = 'The following issue is partialy accepted.';

		$s_declined_bug_title = 'Mark issue is declined';
		$s_declined_bug_button = 'Declined';
		$s_email_notification_title_for_resolution_bug_declined = 'The following issue is declined.';
		*/
}
