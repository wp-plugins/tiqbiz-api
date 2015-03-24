(function($) {
    $(function() {
        if (!window.tiqbiz_api_data) {
            return;
        }

        var queue = tiqbiz_api_data.action_queue;

        if (!queue) {
            return;
        }

        var timeout = tiqbiz_api_data.timeout;

        var sync_banner = $('#tiqbiz_api_sync_progress').removeClass('updated');
        var success_banner = $('#tiqbiz_api_sync_success')

        var progress_list = sync_banner.find('ol');
        
        var tiqbiz_api = this;

        this.init = function init() {
            var requests = [];

            $.each(queue, function(index, action) {
                id_callback = tiqbiz_api.createIdCallback(action.internal_id, action.nonce);

                requests.push(
                    tiqbiz_api.apiAction(action.method, action.payload, id_callback)
                );
            });

            var promise = $.when.apply($, requests);

            promise.done(function() {
                setTimeout(function() {
                    sync_banner.fadeOut(function() {
                        success_banner.fadeIn();
                    });
                }, 1000);
            }).fail(function() {
                setTimeout(function() {
                    sync_banner.removeClass('in-progress');
                    var log_item = tiqbiz_api.log('It looks like some errors occurred during the sync process');
                    tiqbiz_api.logError(log_item, '- please try saving the posts in question again');
                    tiqbiz_api.log('If this happens frequently, please contact the Tiqbiz team');
                }, 1000);
            });
        };

        this.apiAction = function apiAction(method, payload, id_callback) {
            if (!method) {
                return;
            }

            payload = payload || '';

            var data = {};
            data.action = 'tiqbiz_api_action';
            data.method = method;
            data.payload = payload;

            var log_item = tiqbiz_api.log('Syncing post "' + payload.title + '"...');

            return $.Deferred(function(deferred) {
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    data: data,
                    timeout: timeout * 1000,

                    success: function(response) {
                        if (response && response.success) {
                            tiqbiz_api.logSuccess(log_item);

                            if (response.data && typeof response.data.id != 'undefined') {
                                $.when(id_callback(response.data.id)).done(function() {
                                    deferred.resolve();
                                }).fail(function() {
                                    deferred.reject();
                                });
                            } else {
                                deferred.resolve();
                            }
                        } else {
                            if ((/Invalid post ID/i).test(response.error_message)) {
                                // clear out the tiqbiz api id as it doesn't seem to have a match on the tiqbiz server
                                $.when(id_callback(null)).always(function() {
                                    deferred.reject();
                                });
                            } else {
                                deferred.reject();
                            }

                            tiqbiz_api.logError(log_item, response.error_message || 'Invalid response from Tiqbiz API');
                        }
                    },

                    error: function() {
                        tiqbiz_api.logError(log_item, 'No response from Tiqbiz API');
                        deferred.reject();
                    }
                });
            });

        };

        this.createIdCallback = function createIdCallback(internal_id, nonce) {
            var id_callback = function id_callback(tiqbiz_api_id) {
                var log_item = tiqbiz_api.log('Updating internal metadata (post ' + internal_id + ')...');

                var data = {};
                data.action = 'tiqbiz_api_id_callback';
                data.internal_id = internal_id;
                data.nonce = nonce;
                data.tiqbiz_api_id = tiqbiz_api_id;

                return $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    data: data,

                    success: function(response) {
                        if (response && response.success) {
                            tiqbiz_api.logSuccess(log_item);
                        } else {
                            tiqbiz_api.logError(log_item, response.error_message || 'Invalid response from website backend');
                        }
                    },

                    error: function() {
                        tiqbiz_api.logError(log_item, 'No response from website backend');
                    }
                });
            };

            return id_callback;
        };

        this.log = function log(message) {
            return $('<li>').text(message).appendTo(progress_list);
        };

        this.logAppend = function logAppend(log_item, message_append, classname) {
            log_item.html(log_item.html() + ' ' + '<span class="' + (classname || '') + '">' + message_append) + '</span>';
        };

        this.logError = function logError(log_item, message) {
            tiqbiz_api.logAppend(log_item, message.replace(/\.$/, ''), 'error');
        }

        this.logSuccess = function logSuccess(log_item) {
            tiqbiz_api.logAppend(log_item, 'OK', 'success');
        }

        tiqbiz_api.init();
    });
})(jQuery);