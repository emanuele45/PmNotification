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
		notifwin.numpms = 0;
		notifwin.replyResult = {};
		var element = $(document.getElementById('pmnotifications_controller'));
		var clicked = false;

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
		 * @todo extract the fetching itself, so that it can be reused for pagination
		 */
		this.loadMessages = function() {
			notifwin.show = false;

			$http.get(elk_scripturl + "?action=pmnotification;sa=get;xml;api=json")
				.success(function(data) {
					notifwin.msgs = data;
					notifwin.show = true;
					notifwin.numpms = data.length;
				});
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
			notifwin.replmsgs = {};
			notifwin.numpms = notifwin.msgs.length;

			notifwin.showReply = false;
		};

		/**
		 * This does the AJAX call to send a PM
		 */
		this.sendPm = function() {
			var pdata = {
					id_pm: notifwin.msgs[0].id_pm,
					message: $scope.replyMessage,
				};
			pdata[elk_session_var] = elk_session_id;

			$http({
					method: 'POST',
					url: elk_scripturl + "?action=pmnotification;sa=send;xml;api=json",
					data: $.param(pdata),
					headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			})
			.success(function(data) {
// 				alert(data.errors.length);
				notifwin.replyResult = data;
			})
			.error(function(data, status, headers, config) {
				alert(123);
				notifwin.replyResult = {};
			});
		};

		this.hasErrors = function() {
			if (typeof notifwin.replyResult.errors == 'undefined')
				return false;
			else
				return  notifwin.replyResult.errors.length > 0;
		};
		this.isSuccessful = function() {
			return typeof notifwin.replyResult.success !== 'undefined';
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