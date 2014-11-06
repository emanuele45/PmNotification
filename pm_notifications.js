/**
 * PM Notifications
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

(function(){
	var app = angular.module('pmnotifications', ['ngAnimate']);

	app.controller('PmNotifications', ['$http', '$sce', '$scope', '$document', function($http, $sce, $scope, $document) {
		var notifwin = this;
		notifwin.msg = 0;
		notifwin.msgs = {};
		notifwin.replmsgs = {};
		notifwin.show = false;
		notifwin.showReply = false;
		notifwin.numpms = 0;
		notifwin.replyResult = {};
		notifwin.replyMessage = '';
		var element = $(document.getElementById('pmnotifications_controller'));
		var clicked = false;

		/**
		 * This makes possible to click on any place of the page outside the
		 * controller and close the messages container
		 */
		$document.bind('click', function(event) {
			var isClickedElementChildOfPopup = element.find(event.target).length > 0;

			if (isClickedElementChildOfPopup || clicked)
			{
				if (clicked)
					clicked = false;
				return;
			}

			notifwin.hide();
			$scope.$apply();
		});

		/**
		 * Function that fetches the PMs
		 */
		this.loadMessages = function() {
			notifwin.show = false;
			notifwin.fetchMessages();
		};

		/**
		 * Fetches the unread PMs from the server
		 */
		this.fetchMessages = function () {
			$http.get(elk_scripturl + "?action=pmnotification;sa=get;xml;api=json")
				.success(function(data) {
					notifwin.msgs = data;
					notifwin.show = true;
					notifwin.numpms = data.length;
				});
		};

		/**
		 * Cleans up the reply box
		 */
		this.cleanReply = function () {
			notifwin.replmsgs = {};
			notifwin.numpms = notifwin.msgs.length;
			notifwin.replyMessage = '';

			notifwin.showReply = false;
		};

		/**
		 * Prepares and shows the reply box
		 */
		this.replyTo = function(id) {
			clicked = true;
			notifwin.replmsgs = notifwin.msgs;
			notifwin.msgs = {0: this.getMessage(id)};
			notifwin.numpms = 1;

			notifwin.showReply = true;
		};

		/**
		 * Once replied or cancelled a reply, restores the messages
		 */
		this.restoreMsgs = function() {
			notifwin.msgs = notifwin.replmsgs;
			notifwin.cleanReply();
		};

		/**
		 * This does the AJAX call to send a PM
		 */
		this.sendPm = function() {
			var pdata = {
					id_pm: notifwin.msgs[0].id_pm,
					message: notifwin.replyMessage,
				};
			pdata[elk_session_var] = elk_session_id;

			$http({
					method: 'POST',
					url: elk_scripturl + "?action=pmnotification;sa=send;xml;api=json",
					data: $.param(pdata),
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			})
			.success(function(data) {
				notifwin.replyResult = data;
				if (typeof notifwin.replyResult.errors === 'undefined')
				{
					notifwin.fetchMessages();
					notifwin.cleanReply();
					setTimeout(function(){notifwin.hideResult();$scope.$apply();}, 3000);
				}
				else
				{
					// We do not hide the errors in that case, but it may be worth
					// redirect some of the errors to a full post page
				}
			})
			.error(function(data, status, headers, config) {
				notifwin.replyResult = {};
				// Redirect to the full page?
			});
		};

		/**
		 * Returns if the reply has errors associated or not
		 */
		this.hasErrors = function() {
			if (typeof notifwin.replyResult.errors == 'undefined')
				return false;
			else
				return  notifwin.replyResult.errors.length > 0;
		};

		/**
		 * Did we succeed in sending our PM?
		 */
		this.isSuccessful = function() {
			return typeof notifwin.replyResult.success !== 'undefined';
		};

		/**
		 * Hides the result of the send, either if a success or a failure
		 */
		this.hideResult = function() {
			notifwin.replyResult = {};
		};

		/**
		 * Finds a PM by its id_pm
		 */
		this.getMessage = function (id) {
			for (var key in notifwin.msgs)
			{
				if (notifwin.msgs.hasOwnProperty(key) && notifwin.msgs[key].id_pm == id)
				{
						return notifwin.msgs[key];
				}
			}
		};

		/**
		 * Returns if the overlay should be visible or not
		 */
		this.isVisible = function() {
			return notifwin.show;
		};

		/**
		 * Is the rely box visible?
		 */
		this.isReplyVisible = function() {
			return notifwin.showReply;
		};

		/**
		 * Takes care of hiding the overlay setting this.show to false
		 */
		this.hide = function() {
			notifwin.show = false;
		};

		/**
		 * Returns an unsafe string (used for the body
		 */
		this.unsafeString = function(string) {
			return $sce.trustAsHtml(string);
		};
	}]);
})();