/*global define*/
define([
    'jquery',
    'underscore',
    'oroui/js/mediator',
    'routing',
    'oroemail/js/app/models/email-attachment-model',
    'oroui/js/app/views/base/view',
    'oroemail/js/app/models/email-notification-collection'
], function ($, _, mediator, routing, EmailAttachmentModel, BaseView, EmailNotificationCollection) {
    'use strict';

    var EmailAttachmentView;

    EmailAttachmentView = BaseView.extend({
        contextsView: null,
        countNewEmail:null,
        inputName: '',
        events: {
            'click a.mark-as-read': 'onClickMarkAsRead',
            'click .info': 'onClickOpenEmail'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.template = _.template($('#email-notification-item').html());
            this.$containerContextTargets = $(options.el).find('.items');
            this.countNewEmail = this.getDefaultCount();
            this.$el.show();
            this.initCollection().initEvents();
        },

        initCollection: function () {
            var emails = this.getDefaultData();
            this.collection = new EmailNotificationCollection(emails);

            return this;
        },

        render: function () {
            var $view, i;
            this.$containerContextTargets.empty();
            this.initViewType();

            for (i in this.collection.models) {
                if (this.collection.models.hasOwnProperty(i)) {
                    $view = this.getView(this.collection.models[i]);
                    this.$containerContextTargets.append($view);
                }
            }
        },

        getView: function (model) {
            var view = this.template({
                entity: model
            });
            var $view = $(view);
            $view.find('.replay a').attr('data-url', model.get('route'));

            if (model.get('seen')) {
                $view.removeClass('new');
                $view.find('.icon-envelope').removeClass('new');
            }

            return $view;
        },

        onClickMarkAsRead: function () {
            var self = this;
            $.ajax({
                url: routing.generate('oro_email_mark_all_as_seen'),
                success: function () {
                    self.collection.markAllAsRead();
                    self.render();
                    self.setCount(0);
                    mediator.trigger('datagrid:doRefresh:user-email-grid');
                }
            });
        },

        getClankEvent: function () {
            return $(this.el).data('clank-event');
        },

        getDefaultData: function () {
            return $(this.el).data('emails');
        },

        getDefaultCount: function () {
            return $(this.el).data('count');
        },

        initViewType: function () {
            if (!this.isActiveTypeDropDown('notification')) {
                if (this.collection.models.length === 0) {
                    this.setModeDropDownMenu('empty');
                    this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                } else {
                    this.setModeDropDownMenu('content');
                    if (this.countNewEmail > 0) {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').addClass('new');
                    } else {
                        this.$el.find('.oro-dropdown-toggle .icon-envelope').removeClass('new');
                    }
                }
            }
        },

        resetModeDropDownMenu: function() {
            this.$el.find('.dropdown-menu').removeClass('content empty notification');

            return this;
        },
        setModeDropDownMenu:function(type) {
            this.resetModeDropDownMenu();
            this.$el.find('.dropdown-menu').addClass(type);
        },

        isActiveTypeDropDown: function(type) {
            return this.$el.find('.dropdown-menu').hasClass(type);
        },

        onClickOpenEmail: function (e) {
            var id  = $(e.currentTarget).data('id');
            mediator.execute(
                'redirectTo',
                {
                    url: routing.generate('oro_email_view', {id: id})
                }
            );
            var model = this.collection.find(function(item){
                return Number(item.get('id')) === id;
            });

            this.$el.find('#'+model.cid).removeClass('new');
            this.$el.find('#'+model.cid).find('.icon-envelope').removeClass('new');
            this.initViewType();
        },

        setCount: function (count) {
            this.countNewEmail = count;
            if (count > 10) {
                count = '10+';
            }

            if (count === 0) {
                count = '';
            }
            this.$el.find('.icon-envelope span').html(count);
            this.initViewType();
        },

        initEvents: function () {
            var self = this;

            this.$el.click(function() {
                if (self.isActiveTypeDropDown('notification')) {
                    self.open();
                    self.setModeDropDownMenu('content');
                }
                self.initViewType();
            });

            this.collection.on('reset', function () {
                self.$containerContextTargets.html('');
                self.setCount(0);
            });

            this.collection.on('add', function (model) {
                var $view = self.getView(model);
                self.$containerContextTargets.append($view);
                self.initLayout();
            });
        },

        showNotification: function() {
            if (!this.isOpen()) {
                this.open();
                this.setModeDropDownMenu('notification');
            }
        },

        isOpen: function() {
            this.$el.hasClass('open');
        },

        close: function() {
            this.$el.removeClass('open');
        },

        open: function () {
            this.$el.addClass('open');
        }
    });

    return EmailAttachmentView;
});
