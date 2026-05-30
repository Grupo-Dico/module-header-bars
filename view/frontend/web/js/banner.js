define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var intervalId = null;

        function getClosedKey(bannerId) {
            return 'leanzote_banner_closed_' + bannerId;
        }

        function isBannerClosed(bannerId) {
            bannerId = parseInt(bannerId, 10);

            if (!bannerId) {
                return false;
            }

            try {
                return window.sessionStorage.getItem(getClosedKey(bannerId)) === '1';
            } catch (error) {
                return false;
            }
        }

        function markBannerClosed(bannerId) {
            bannerId = parseInt(bannerId, 10);

            if (!bannerId) {
                return false;
            }

            try {
                window.sessionStorage.setItem(getClosedKey(bannerId), '1');
            } catch (error) {
                return false;
            }

            return true;
        }

        function parseDate(value) {
            if (!value) {
                return null;
            }

            var timestamp = Date.parse(value);

            return isNaN(timestamp) ? null : timestamp;
        }

        function firstValue() {
            var index;

            for (index = 0; index < arguments.length; index++) {
                if (arguments[index] !== undefined && arguments[index] !== null && arguments[index] !== '') {
                    return arguments[index];
                }
            }

            return '';
        }

        function setStyle($element, property, value, important) {
            if (!value) {
                return $element;
            }

            $element.each(function () {
                this.style.setProperty(property, value, important ? 'important' : '');
            });

            return $element;
        }

        function applyColors($element, backgroundColor, textColor) {
            setStyle($element, 'background-color', backgroundColor, true);
            setStyle($element, 'color', textColor, true);

            return $element;
        }

        function clearTicker() {
            if (!intervalId) {
                return;
            }

            window.clearInterval(intervalId);
            intervalId = null;
        }

        function updateLayoutOffsets(totalHeight) {
            var offset = totalHeight > 0 ? totalHeight + 'px' : '0';
            var $stickyHeader = $('header.page-header.sticky:visible, .page-header.sticky:visible').first();
            var stickyHeaderHeight = $stickyHeader.length ? $stickyHeader.outerHeight() : 0;

            document.documentElement.style.setProperty('--leanzote-banner-stack-height', offset);
            document.documentElement.style.setProperty('--leanzote-sticky-header-height', stickyHeaderHeight + 'px');
            $('body')
                .toggleClass('leanzote-banner-visible', totalHeight > 0)
                .css('margin-top', offset);
        }

        function shouldShowBanner($banner) {
            var bannerId = $banner.data('banner-id');
            var endDate = parseDate($banner.data('end-date'));
            var now = Date.now();

            if (isBannerClosed(bannerId)) {
                return false;
            }

            if (endDate && now >= endDate) {
                return false;
            }

            return true;
        }

        function showBanner($banner) {
            if ($banner.is(':visible')) {
                return;
            }

            $banner.stop(true, true).css('display', 'flex').hide().fadeIn(300);
        }

        function checkBannerVisibility() {
            var $banners = $root.find('.leanzote-banner');
            var totalHeight = 0;
            var visibleBanners = 0;

            $banners.each(function () {
                var $banner = $(this);

                if (!shouldShowBanner($banner)) {
                    $banner.hide();
                    return;
                }

                $banner.css('top', totalHeight + 'px');
                showBanner($banner);
                totalHeight += $banner.outerHeight();
                visibleBanners++;
            });

            updateLayoutOffsets(visibleBanners > 0 ? totalHeight : 0);
        }

        function buildButton(banner) {
            var button = banner.button || {};
            var backgroundColor = firstValue(button.background_color, banner.button_color_background, '#000000');
            var textColor = firstValue(button.text_color, banner.button_color_text, '#FFFFFF');

            if (!button.enabled || !button.text || !button.link) {
                return $();
            }

            return applyColors($('<a/>', {
                'class': 'leanzote-banner__button',
                href: button.link,
                text: button.text
            }), backgroundColor, textColor);
        }

        function buildCounter(banner) {
            var counter = banner.counter || {};
            var bannerId = parseInt(banner.id, 10);
            var counterId = 'banner-counter-' + bannerId;
            var backgroundColor = firstValue(counter.background_color, banner.counter_bg_color, '#000000');
            var textColor = firstValue(counter.text_color, banner.counter_color_text, '#FFFFFF');

            if (!isCounterActive(counter)) {
                return $();
            }

            var $counter = applyColors($('<div/>', {
                id: counterId,
                'class': 'leanzote-banner__counter leanzote-banner__counter--hidden'
            }), backgroundColor, textColor).data({
                'start-date': counter.start_date || null,
                'end-date': counter.end_date || null
            });

            var $numbers = $('<div/>', {'class': 'leanzote-counter__numbers'});
            var units = [
                ['days', 'Dias'],
                ['hours', 'HRS'],
                ['minutes', 'MIN'],
                ['seconds', 'SECS']
            ];

            $.each(units, function (index, unit) {
                if (index > 0) {
                    $numbers.append(
                        $('<span/>', {'class': 'leanzote-counter__separator', text: '|'})
                            .each(function () {
                                this.style.setProperty('color', textColor, 'important');
                            })
                    );
                }

                $numbers.append(
                    $('<div/>', {'class': 'leanzote-counter__time-block'})
                        .append($('<span/>', {'class': 'leanzote-counter__' + unit[0], text: '00'}))
                        .append(
                            $('<span/>', {'class': 'leanzote-counter__unit', text: unit[1]})
                                .each(function () {
                                    this.style.setProperty('color', textColor, 'important');
                                })
                        )
                );
            });

            $counter.append($numbers);
            setStyle($counter.find('span'), 'color', textColor, true);

            return $counter;
        }

        function isCounterActive(counter) {
            var startDate;
            var endDate;
            var now;

            if (!counter || !counter.enabled || !counter.end_date) {
                return false;
            }

            startDate = parseDate(counter.start_date);
            endDate = parseDate(counter.end_date);
            now = Date.now();

            if (startDate && now < startDate) {
                return false;
            }

            return !!endDate && now < endDate;
        }

        function setCounterVisible($counter, visible) {
            $counter
                .toggleClass('leanzote-banner__counter--hidden', !visible)
                .css('display', '');
        }

        function buildBanner(banner, index) {
            var bannerId = parseInt(banner.id, 10);
            var buttonBefore = !!(banner.button && banner.button.before_counter);
            var backgroundColor = firstValue(banner.background_color, '#FFFFFF');
            var textColor = firstValue(banner.text_color, '#333333');
            var $content = $('<div/>', {'class': 'leanzote-banner__content'});
            var $actions = $('<div/>', {'class': 'leanzote-banner__actions'});
            var $button = buildButton(banner);
            var $counter = buildCounter(banner);
            var $bannerElement;
            var $closeButton;

            if (buttonBefore) {
                $actions.addClass('button-before');
            }

            $content.append($('<span/>', {
                'class': 'leanzote-banner__text',
                text: banner.content || ''
            }));

            if (buttonBefore) {
                $actions.append($button);
                $actions.append($counter);
            } else {
                $actions.append($counter);
                $actions.append($button);
            }

            $content.append($actions);

            $bannerElement = $('<div/>', {
                id: 'leanzote-banner-' + bannerId,
                'class': 'leanzote-banner'
            }).css({
                top: (index * 60) + 'px',
                display: 'none'
            }).data({
                'banner-id': bannerId,
                'end-date': banner.end_date || null
            });
            $closeButton = $('<button/>', {
                'class': 'leanzote-banner__close',
                'data-banner-id': bannerId,
                type: 'button',
                text: 'x'
            });

            applyColors($bannerElement, backgroundColor, textColor);
            setStyle($content, 'color', textColor, true);
            setStyle($content.find('.leanzote-banner__text'), 'color', textColor, true);
            setStyle($closeButton, 'color', textColor, true);

            return $bannerElement.append($content).append($closeButton);
        }

        function updateCounter($counter) {
            var startDate = parseDate($counter.data('start-date'));
            var endDate = parseDate($counter.data('end-date'));
            var now = Date.now();
            var diff;

            if (startDate && now < startDate) {
                setCounterVisible($counter, false);
                return;
            }

            if (!endDate || now >= endDate) {
                setCounterVisible($counter, false);
                return;
            }

            diff = endDate - now;
            setCounterVisible($counter, true);
            $counter.find('.leanzote-counter__days').text(String(Math.floor(diff / 86400000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__hours').text(String(Math.floor((diff % 86400000) / 3600000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__minutes').text(String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0'));
            $counter.find('.leanzote-counter__seconds').text(String(Math.floor((diff % 60000) / 1000)).padStart(2, '0'));
        }

        function tick() {
            $root.find('.leanzote-banner__counter').each(function () {
                updateCounter($(this));
            });

            checkBannerVisibility();
        }

        function renderBanners(banners) {
            var renderedCount = 0;

            $root.empty();

            if (!banners.length) {
                clearTicker();
                updateLayoutOffsets(0);
                return;
            }

            $.each(banners, function (index, banner) {
                if (isBannerClosed(banner.id)) {
                    return;
                }

                $root.append(buildBanner(banner, renderedCount));
                renderedCount++;
            });

            if (!renderedCount) {
                clearTicker();
                updateLayoutOffsets(0);
                return;
            }

            tick();

            clearTicker();
            intervalId = window.setInterval(tick, 1000);
        }

        $root.on('click', '.leanzote-banner__close', function () {
            var bannerId = $(this).data('banner-id');
            var $banner = $('#leanzote-banner-' + bannerId);

            markBannerClosed(bannerId);
            $banner.fadeOut(300, function () {
                $banner.remove();
                if (!$root.find('.leanzote-banner').length) {
                    clearTicker();
                }

                checkBannerVisibility();
            });
        });

        $(window).on('resize.leanzoteBanner', checkBannerVisibility);

        $.ajax({
            url: config.endpointUrl,
            type: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                current_path: window.location.pathname
            }
        }).done(function (response) {
            renderBanners(response && response.success && $.isArray(response.banners) ? response.banners : []);
        }).fail(function () {
            renderBanners([]);
        });
    };
});
