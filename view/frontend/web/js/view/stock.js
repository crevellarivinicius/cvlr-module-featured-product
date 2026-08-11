/**
 * Copyright © Vinícius Crevellari. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Knockout component that keeps the featured product salable quantity
 * fresh by polling the stock endpoint. Declared through jsLayout in
 * view/frontend/layout/cms_index_index.xml.
 */
define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/translate'
], function ($, Component, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Crevellari_FeaturedProduct/stock',
            stockUrl: '',
            refreshInterval: 10,
            lowStockThreshold: 5,
            initialQty: null,
            initialIsSalable: false,
            initialUpdatedAt: ''
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            this.timerHandle = null;
            this.visibilityHandler = this.onVisibilityChange.bind(this);

            this.removePlaceholder();
            this.bindVisibility();
            this.refresh();
            this.startPolling();

            return this;
        },

        /** @inheritdoc */
        initObservable: function () {
            this._super()
                .observe({
                    qty: this.initialQty,
                    isSalable: this.initialIsSalable,
                    updatedAt: this.initialUpdatedAt,
                    hasError: false,
                    justUpdated: false
                });

            this.qtyLabel = ko.computed(function () {
                var qty = this.qty();

                if (qty === null || isNaN(qty)) {
                    return $t('Checking availability...');
                }

                return this.formatQty(qty);
            }, this);

            this.isOutOfStock = ko.computed(function () {
                return this.qty() !== null && (!this.isSalable() || this.qty() <= 0);
            }, this);

            this.isLowStock = ko.computed(function () {
                return !this.isOutOfStock() &&
                    this.lowStockThreshold > 0 &&
                    this.qty() !== null &&
                    this.qty() <= this.lowStockThreshold;
            }, this);

            this.updatedAtLabel = ko.computed(function () {
                var iso = this.updatedAt(),
                    date;

                if (!iso) {
                    return '';
                }

                date = new Date(iso);

                if (isNaN(date.getTime())) {
                    return '';
                }

                return $t('Updated at %1').replace('%1', date.toLocaleTimeString());
            }, this);

            return this;
        },

        /**
         * Fetch the current salable quantity from the server.
         */
        refresh: function () {
            var self = this;

            if (!this.stockUrl) {
                return;
            }

            $.ajax({
                url: this.stockUrl,
                type: 'GET',
                dataType: 'json',
                cache: false,
                global: false
            }).done(function (response) {
                if (response && response.success) {
                    self.applyStockData(response);
                    self.hasError(false);
                } else {
                    self.hasError(true);
                }
            }).fail(function () {
                // Keep the last known value on screen and retry on the next tick
                self.hasError(true);
            });
        },

        /**
         * Apply a successful payload to the observables, pulsing on change.
         *
         * @param {Object} response
         */
        applyStockData: function (response) {
            var newQty = parseFloat(response.qty),
                changed = this.qty() !== null && newQty !== this.qty(),
                self = this;

            this.qty(newQty);
            this.isSalable(Boolean(response['is_salable']));
            this.updatedAt(response['updated_at'] || new Date().toISOString());

            if (changed) {
                this.justUpdated(true);
                setTimeout(function () {
                    self.justUpdated(false);
                }, 1200);
            }
        },

        /**
         * Start the periodic refresh.
         */
        startPolling: function () {
            var intervalMs = Math.max(parseInt(this.refreshInterval, 10) || 10, 5) * 1000;

            this.stopPolling();
            this.timerHandle = setInterval(this.refresh.bind(this), intervalMs);
        },

        /**
         * Stop the periodic refresh.
         */
        stopPolling: function () {
            if (this.timerHandle) {
                clearInterval(this.timerHandle);
                this.timerHandle = null;
            }
        },

        /**
         * Pause polling while the tab is in the background and refresh
         * immediately when the visitor comes back.
         */
        bindVisibility: function () {
            document.addEventListener('visibilitychange', this.visibilityHandler);
        },

        /**
         * Visibility change callback.
         */
        onVisibilityChange: function () {
            if (document.hidden) {
                this.stopPolling();
            } else {
                this.refresh();
                this.startPolling();
            }
        },

        /**
         * Remove the server-side rendered placeholder once Knockout takes over.
         */
        removePlaceholder: function () {
            var placeholder = document.querySelector('[data-role="stock-placeholder"]');

            if (placeholder && placeholder.parentNode) {
                placeholder.parentNode.removeChild(placeholder);
            }
        },

        /**
         * Format the quantity: whole numbers without decimals, otherwise 2 decimals.
         *
         * @param {Number} qty
         * @return {String}
         */
        formatQty: function (qty) {
            if (qty % 1 === 0) {
                return qty.toLocaleString();
            }

            return qty.toLocaleString(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            });
        },

        /** @inheritdoc */
        destroy: function () {
            this.stopPolling();
            document.removeEventListener('visibilitychange', this.visibilityHandler);
            this._super();
        }
    });
});
