<?php
/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

function template_pmnotifications_below()
{
	global $txt;

	echo '
	<div id="pmnotifications_controller" ng-app="pmnotifications" ng-controller="PmNotifications as notifwin">
		<i style="
			position: fixed;
			bottom: 10px;
			right: 10px;
			padding: 1px 6px;
			cursor: pointer;
			background-color: #fff;
			box-shadow: inset 0 0 5px #000;
			border-radius: 5px" class="fa fa-envelope-o fa-4x" ng-click="notifwin.loadMessages()"></i>
		<div class="pmnotifications_title" ng-show="notifwin.isVisible()">
			<i class="pmnotifications_close fa fa-power-off fa-2x" ng-click="notifwin.hide()"></i>
			<span>', $txt['pmnotif_new_msgs'] , '</span></div>
		<div class="ng-hide pmnotifications_container" ng-show="notifwin.isVisible()">
			<ul id="pmnotifications" class="num_msg_{{notifwin.numpms}} forumposts">
				<li ng-animate="\'animate\'" id="msg_{{message.id_pm}}" class="windowbg messagebox" ng-repeat="message in notifwin.msgs">
					<span class="modifier">
						<img class="pavatar" ng-src="{{message.avatar.href}}" />
						<span class="name">{{message.from_name}}</span>
					</span>
					<div class="pmessage">
						<div ng-bind-html="notifwin.unsafeString(message.body)"></div>
						<span class="pbuttons" ng-show="!notifwin.isReplyVisible()">
							<i ng-click="notifwin.replyTo(message.id_pm)" class="click fa fa-reply-all fa-lg" ng-show="!notifwin.isReplyVisible()"></i>
							<i class="fa fa-remove fa-lg"></i>
						</span>
					</div>
				</li>
				<li class="ng-hide_no preplybox" ng-show="notifwin.isReplyVisible()">
					<textarea my-directive ngRequired ng-model="notifwin.replyMessage"></textarea>
						<span class="pbuttons" ng-show="notifwin.isReplyVisible()">
							<i class="click fa fa-history fa-lg" ng-click="notifwin.restoreMsgs()"></i>
							<i class="click fa fa-send-o fa-lg" ng-click="notifwin.sendPm()"></i>
						</span>
				</li>
			</ul>
			<div ng-show="notifwin.hasErrors()||notifwin.isSuccessful()" class="ng-hide pmnotifications_results">
				<i class="click fa fa-check-circle-o fa-lg" ng-click="notifwin.hideResult()"></i>
				<ul class="error" ng-show="notifwin.hasErrors()" id="pmnotifications_errors">
					<li ng-animate="\'animate\'" ng-repeat="error in notifwin.replyResult.errors">
						{{error}}
					</li>
				</ul>
				<ul class="success" ng-show="notifwin.isSuccessful()">
					<li>{{notifwin.replyResult.success}}</li>
				</ul>
			</div>
		</div>
	</div>';
}
